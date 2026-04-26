<?php

declare(strict_types=1);

final class ProfitLossController extends Controller
{
    private function model(): ProfitLossModel
    {
        return new ProfitLossModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('profit_loss/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman laporan laba rugi belum dapat dibuka. Pastikan data jurnal, COA, dan periode sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $viewData['title'] = 'Cetak Laporan Laba Rugi';
            $viewData['profile'] = app_profile();
            $this->view('profit_loss/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak laba rugi belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $pdf = new ReportPdf('L');
            report_pdf_init($pdf, $profile, 'Laporan Laba Rugi', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);

            $widths = [16, 112, 42];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths): void {
                report_pdf_init($pdfObj, $profile, 'Laporan Laba Rugi', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                $pdfObj->tableRow([
                    'No',
                    'Uraian',
                    (string) $viewData['report']['current_column_label'],
                ], $widths, ['C', 'L', 'R'], 8.0, true);
            };
            $headerPrinter($pdf);

            foreach ($viewData['statement_rows'] as $row) {
                $pdf->tableRow([
                    (string) $row['order'],
                    (string) $row['label'],
                    $row['current_amount'] === null ? '' : profit_loss_currency((float) $row['current_amount']),
                ], $widths, ['C', 'L', 'R'], 8.0, $row['row_type'] !== 'account', $headerPrinter);
            }

            $pdf->tableRow([
                '',
                profit_loss_display_label(),
                profit_loss_currency((float) $viewData['report']['net_income']),
            ], $widths, ['C', 'L', 'R'], 8.0, true, $headerPrinter);

            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-laba-rugi.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF laba rugi belum dapat dibuat. Pastikan filter laporan valid lalu coba lagi.', $e);
        }
    }

    public function xlsx(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $rows = [];
            foreach ($viewData['statement_rows'] as $row) {
                $rows[] = [
                    (string) ($row['order'] ?? ''),
                    (string) ($row['row_type'] ?? ''),
                    (string) ($row['label'] ?? ''),
                    $row['current_amount'] === null ? '' : profit_loss_currency((float) $row['current_amount']),
                ];
            }

            $rows[] = [
                '',
                'grand_total',
                strtoupper(profit_loss_display_label()),
                profit_loss_currency((float) $viewData['report']['net_income']),
            ];

            report_download_xlsx(
                'profit_loss_' . date('Ymd_His') . '.xlsx',
                'Laba Rugi',
                'Laporan Laba Rugi',
                $viewData['filters'],
                ['No', 'Tipe Baris', 'Uraian', (string) $viewData['report']['current_column_label']],
                $rows,
                [
                    'Mode Laporan' => (string) ($viewData['report']['mode_label'] ?? '-'),
                ]
            );
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export XLSX laba rugi belum dapat diproses.');
            $this->redirect('/profit-loss');
        }
    }

    private function buildReportData(): array
    {
        $activePeriod = current_accounting_period();
        $defaultPeriodId = $activePeriod ? (int) ($activePeriod['id'] ?? 0) : 0;

        $filters = [
            'period_id' => (int) get_query('period_id', $defaultPeriodId),
            'fiscal_year' => (int) get_query('fiscal_year', 0),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
            'unit_id' => (int) get_query('unit_id', 0),
            'mode' => trim((string) get_query('mode', 'period')),
            'comparison_mode' => report_normalize_comparison_mode((string) get_query('comparison_mode', 'none')),
            'comparison_period_id' => (int) get_query('comparison_period_id', 0),
            'show_variance' => report_query_flag(get_query('show_variance', '0'), false),
            'show_visual' => report_query_flag(get_query('show_visual', '1')),
        ];

        $filters = apply_fiscal_year_filter($filters);
        $filters['mode'] = $this->normalizeMode((string) ($filters['mode'] ?? 'period'));

        $periods = $this->model()->getPeriods();
        $selectedPeriod = null;
        $selectedComparisonPeriod = null;
        $selectedUnit = null;
        $report = $this->emptyReport();
        $statementRows = [];
        $trend = [];

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            if ($filters['comparison_period_id'] > 0) {
                $selectedComparisonPeriod = $this->model()->findPeriodById((int) $filters['comparison_period_id']);
            }

            $normalized = $this->normalizeCurrentRange($filters, $selectedPeriod);
            $filters['date_from'] = $normalized['date_from'];
            $filters['date_to'] = $normalized['date_to'];
            $filters['mode'] = $normalized['mode'];

            $currentRawRows = $this->model()->getRows($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $current = $this->buildSectionReport($currentRawRows);

            $comparisonContext = report_resolve_comparison_range(
                (string) $filters['comparison_mode'],
                (string) $filters['date_from'],
                (string) $filters['date_to'],
                $selectedPeriod,
                $selectedComparisonPeriod
            );
            $comparison = $this->emptyReport();
            if (($comparisonContext['enabled'] ?? false) === true) {
                $comparisonRawRows = $this->model()->getRows((string) $comparisonContext['date_from'], (string) $comparisonContext['date_to'], (int) $filters['unit_id']);
                $comparison = $this->buildSectionReport($comparisonRawRows);
            }

            $report = $current;
            $report['mode'] = $filters['mode'];
            $report['mode_label'] = $filters['mode'] === 'to_date' ? 'Sampai Tanggal' : 'Bulanan / Periode';
            $report['current_label'] = $filters['mode'] === 'to_date' ? 'Laba Periode s.d. Tanggal Akhir' : 'Laba Periode';
            $report['current_range_label'] = report_period_label($filters, $selectedPeriod);
            $report['current_column_label'] = $this->resolveCurrentColumnLabel($filters, $selectedPeriod);
            $report['period_total_label'] = $filters['mode'] === 'to_date'
                ? 'Total ' . $this->monthYearLabel($filters['date_to']) . ' s.d. ' . format_id_date($filters['date_to'])
                : 'Total ' . $this->resolveCurrentColumnLabel($filters, $selectedPeriod);
            $report['comparison_label'] = (string) ($comparisonContext['label'] ?? 'Pembanding');
            $report['comparison_column_label'] = (string) ($comparisonContext['column_label'] ?? 'Pembanding');
            $report['comparison_date_from'] = (string) ($comparisonContext['date_from'] ?? '');
            $report['comparison_date_to'] = (string) ($comparisonContext['date_to'] ?? '');
            $report['comparison_revenue_rows'] = $comparison['revenue_rows'];
            $report['comparison_expense_rows'] = $comparison['expense_rows'];
            $report['comparison_total_revenue'] = (float) ($comparison['total_revenue'] ?? 0);
            $report['comparison_total_expense'] = (float) ($comparison['total_expense'] ?? 0);
            $report['comparison_net_income'] = (float) ($comparison['net_income'] ?? 0);
            $report['comparison_enabled'] = (bool) ($comparisonContext['enabled'] ?? false);
            $report['comparison_mode'] = (string) ($comparisonContext['mode'] ?? 'none');
            $report['combined_rows'] = $this->combineReportRows($current, $comparison);
            $statementRows = $this->buildStatementRows($report['combined_rows']);
            $trend = $this->buildTrendSeries((string) $filters['date_to'], (int) $filters['unit_id']);
        }

        return [[
            'title' => 'Laporan Laba Rugi',
            'filters' => $filters,
            'modes' => [
                'period' => 'Bulanan / Periode penuh',
                'to_date' => 'Sampai tanggal akhir',
            ],
            'comparisonModes' => [
                'ytd' => 'Aktual vs Akumulasi Tahun Berjalan',
                'previous_period' => 'Aktual vs Periode Lalu',
                'previous_year' => 'Aktual vs Tahun Lalu',
                'none' => 'Tanpa Pembanding',
            ],
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedComparisonPeriod' => $selectedComparisonPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'report' => $report,
            'statement_rows' => $statementRows,
            'trend' => $trend,
        ], $selectedPeriod, $selectedUnit];
    }

    private function resolveFilters(array $filters): array
    {
        $errors = [];
        $period = null;
        $unit = null;
        $filters['period_id'] = (int) ($filters['period_id'] ?? 0);
        $filters['unit_id'] = (int) ($filters['unit_id'] ?? 0);
        $filters['date_from'] = trim((string) ($filters['date_from'] ?? ''));
        $filters['date_to'] = trim((string) ($filters['date_to'] ?? ''));
        $filters['fiscal_year'] = (int) ($filters['fiscal_year'] ?? 0);
        $filters['mode'] = $this->normalizeMode((string) ($filters['mode'] ?? 'period'));
        $filters['comparison_mode'] = report_normalize_comparison_mode((string) ($filters['comparison_mode'] ?? 'ytd'));
        $filters['comparison_period_id'] = (int) ($filters['comparison_period_id'] ?? 0);
        $filters = apply_fiscal_year_filter($filters);

        if ($filters['period_id'] > 0) {
            $period = $this->model()->findPeriodById($filters['period_id']);
            if (!$period) {
                $errors[] = 'Periode yang dipilih tidak ditemukan.';
            } else {
                if ($filters['date_from'] === '') {
                    $filters['date_from'] = (string) $period['start_date'];
                }
                if ($filters['date_to'] === '') {
                    $filters['date_to'] = (string) $period['end_date'];
                }
            }
        }

        if ($filters['unit_id'] > 0) {
            $unit = find_business_unit($filters['unit_id']);
            if (!$unit || (int) ($unit['is_active'] ?? 0) !== 1) {
                $errors[] = 'Unit usaha yang dipilih tidak ditemukan atau tidak aktif.';
            }
        }

        if ($filters['date_from'] === '' && $filters['date_to'] === '') {
            $errors[] = 'Silakan pilih periode atau isi tanggal filter terlebih dahulu.';
        }

        if ($filters['date_from'] !== '' && !$this->isValidDate($filters['date_from'])) {
            $errors[] = 'Tanggal mulai tidak valid.';
        }

        if ($filters['date_to'] !== '' && !$this->isValidDate($filters['date_to'])) {
            $errors[] = 'Tanggal akhir tidak valid.';
        }

        if ($filters['date_from'] === '' && $filters['date_to'] !== '') {
            $filters['date_from'] = '1900-01-01';
        }

        if ($filters['date_to'] === '' && $filters['date_from'] !== '') {
            $filters['date_to'] = $filters['date_from'];
        }

        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_to'] < $filters['date_from']) {
            $errors[] = 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.';
        }

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect('/profit-loss');
        }

        return [$filters, $period, $unit];
    }

    private function buildSectionReport(array $rawRows): array
    {
        $report = $this->emptyReport();

        foreach ($rawRows as $row) {
            $amount = profit_loss_amount(
                (string) $row['account_type'],
                (float) $row['period_debit'],
                (float) $row['period_credit']
            );

            $entry = [
                'account_id' => (int) ($row['id'] ?? 0),
                'account_code' => (string) $row['account_code'],
                'account_name' => (string) $row['account_name'],
                'account_type' => (string) $row['account_type'],
                'account_category' => trim((string) ($row['account_category'] ?? '')),
                'period_debit' => (float) $row['period_debit'],
                'period_credit' => (float) $row['period_credit'],
                'amount' => $amount,
            ];

            if ((string) $row['account_type'] === 'REVENUE') {
                $report['revenue_rows'][] = $entry;
                $report['total_revenue'] += $amount;
            } elseif ((string) $row['account_type'] === 'EXPENSE') {
                $report['expense_rows'][] = $entry;
                $report['total_expense'] += $amount;
            }
        }

        $report['net_income'] = $report['total_revenue'] - $report['total_expense'];
        $report['row_count'] = count($report['revenue_rows']) + count($report['expense_rows']);

        return $report;
    }

    private function combineReportRows(array $current, array $comparison): array
    {
        $combined = [];
        foreach (['REVENUE' => 'revenue_rows', 'EXPENSE' => 'expense_rows'] as $type => $key) {
            foreach (($current[$key] ?? []) as $row) {
                $code = (string) $row['account_code'];
                $combined[$type][$code] = [
                    'account_id' => (int) ($row['account_id'] ?? 0),
                    'account_code' => $code,
                    'account_name' => (string) $row['account_name'],
                    'account_type' => $type,
                    'account_category' => trim((string) ($row['account_category'] ?? '')),
                    'current_amount' => (float) $row['amount'],
                    'comparison_amount' => 0.0,
                ];
            }
            foreach (($comparison[$key] ?? []) as $row) {
                $code = (string) $row['account_code'];
                if (!isset($combined[$type][$code])) {
                    $combined[$type][$code] = [
                        'account_id' => (int) ($row['account_id'] ?? 0),
                        'account_code' => $code,
                        'account_name' => (string) $row['account_name'],
                        'account_type' => $type,
                        'account_category' => trim((string) ($row['account_category'] ?? '')),
                        'current_amount' => 0.0,
                        'comparison_amount' => 0.0,
                    ];
                }
                $combined[$type][$code]['comparison_amount'] = (float) $row['amount'];
                if ($combined[$type][$code]['account_id'] === 0) {
                    $combined[$type][$code]['account_id'] = (int) ($row['account_id'] ?? 0);
                }
            }
            uasort($combined[$type], static function (array $a, array $b): int {
                $catCompare = strcmp((string) $a['account_category'], (string) $b['account_category']);
                if ($catCompare !== 0) {
                    return $catCompare;
                }
                return strcmp((string) $a['account_code'], (string) $b['account_code']);
            });
            $combined[$type] = array_values($combined[$type]);
        }

        return [
            'revenue_rows' => $combined['REVENUE'] ?? [],
            'expense_rows' => $combined['EXPENSE'] ?? [],
        ];
    }

    private function buildStatementRows(array $combined): array
    {
        $rows = [];
        $order = 1;
        $config = [
            'revenue_rows' => 'PENDAPATAN USAHA',
            'expense_rows' => 'BEBAN USAHA',
        ];

        foreach ($config as $key => $sectionTitle) {
            $sectionRows = $combined[$key] ?? [];
            if ($sectionRows === []) {
                continue;
            }

            $sectionType = $key === 'revenue_rows' ? 'REVENUE' : 'EXPENSE';
            $rows[] = [
                'row_type' => 'section',
                'order' => $order++,
                'label' => $sectionTitle,
                'current_amount' => null,
                'comparison_amount' => null,
                'account_id' => 0,
            ];

            $grouped = [];
            foreach ($sectionRows as $row) {
                $category = trim((string) ($row['account_category'] ?? ''));
                if ($category === '') {
                    $category = $sectionType === 'REVENUE' ? 'PENDAPATAN_LAINNYA' : 'BEBAN_LAINNYA';
                }
                $grouped[$category][] = $row;
            }

            $sectionCurrent = 0.0;
            $sectionComparison = 0.0;
            $groupCount = count($grouped);

            foreach ($grouped as $category => $items) {
                $categoryLabel = $this->normalizeCategoryLabel($category, $sectionType);
                $showCategory = !$this->isGenericSectionCategory($category, $sectionType) || $groupCount > 1;

                if ($showCategory) {
                    $rows[] = [
                        'row_type' => 'category',
                        'order' => $order++,
                        'label' => $categoryLabel,
                        'current_amount' => null,
                        'comparison_amount' => null,
                        'account_id' => 0,
                    ];
                }

                $categoryCurrent = 0.0;
                $categoryComparison = 0.0;
                foreach ($items as $item) {
                    $rows[] = [
                        'row_type' => 'account',
                        'order' => $order++,
                        'label' => (string) $item['account_name'],
                        'current_amount' => (float) $item['current_amount'],
                        'comparison_amount' => (float) $item['comparison_amount'],
                        'account_id' => (int) ($item['account_id'] ?? 0),
                    ];
                    $categoryCurrent += (float) $item['current_amount'];
                    $categoryComparison += (float) $item['comparison_amount'];
                }

                if ($showCategory) {
                    $rows[] = [
                        'row_type' => 'subtotal',
                        'order' => '',
                        'label' => 'Total ' . $categoryLabel,
                        'current_amount' => $categoryCurrent,
                        'comparison_amount' => $categoryComparison,
                        'account_id' => 0,
                    ];
                }

                $sectionCurrent += $categoryCurrent;
                $sectionComparison += $categoryComparison;
            }

            $rows[] = [
                'row_type' => 'section_total',
                'order' => '',
                'label' => $key === 'revenue_rows' ? 'TOTAL PENDAPATAN USAHA' : 'TOTAL BEBAN USAHA',
                'current_amount' => $sectionCurrent,
                'comparison_amount' => $sectionComparison,
                'account_id' => 0,
            ];
        }

        return $rows;
    }

    private function buildTrendSeries(string $dateTo, int $unitId): array
    {
        if (!$this->isValidDate($dateTo)) {
            return [];
        }

        $end = new DateTimeImmutable(substr($dateTo, 0, 7) . '-01');
        $points = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $end->modify('-' . $i . ' months');
            $monthEnd = $monthStart->modify('last day of this month');
            $monthReport = $this->buildSectionReport(
                $this->model()->getRows($monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'), $unitId)
            );
            $points[] = [
                'label' => $monthStart->format('Y-m-01'),
                'revenue' => (float) $monthReport['total_revenue'],
                'expense' => (float) $monthReport['total_expense'],
                'net' => (float) $monthReport['net_income'],
            ];
        }

        return $points;
    }

    private function normalizeCategoryLabel(string $label, string $sectionType = ''): string
    {
        $label = trim($label);
        if ($label === '') {
            return $sectionType === 'REVENUE' ? 'Pendapatan Lainnya' : ($sectionType === 'EXPENSE' ? 'Beban Lainnya' : 'Lainnya');
        }

        $normalized = strtoupper(str_replace(['-', ' '], '_', $label));
        $map = [
            'OPERATING_REVENUE' => 'Pendapatan Operasional',
            'OTHER_REVENUE' => 'Pendapatan Lainnya',
            'NON_OPERATING_REVENUE' => 'Pendapatan Non Operasional',
            'OPERATING_EXPENSE' => 'Beban Operasional',
            'OTHER_EXPENSE' => 'Beban Lainnya',
            'NON_OPERATING_EXPENSE' => 'Beban Non Operasional',
            'PENDAPATAN_USAHA' => 'Pendapatan Usaha',
            'BEBAN_USAHA' => 'Beban Usaha',
        ];
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $pretty = str_replace('_', ' ', strtolower($normalized));
        $pretty = preg_replace('/\s+/', ' ', $pretty) ?? $pretty;
        return ucwords($pretty);
    }

    private function isGenericSectionCategory(string $label, string $sectionType): bool
    {
        $normalized = strtoupper(str_replace(['-', ' '], '_', trim($label)));
        $genericRevenue = ['OPERATING_REVENUE', 'PENDAPATAN_USAHA', 'REVENUE', 'PENDAPATAN'];
        $genericExpense = ['OPERATING_EXPENSE', 'BEBAN_USAHA', 'EXPENSE', 'BEBAN'];

        if ($sectionType === 'REVENUE') {
            return in_array($normalized, $genericRevenue, true);
        }
        if ($sectionType === 'EXPENSE') {
            return in_array($normalized, $genericExpense, true);
        }

        return false;
    }

    private function normalizeMode(string $mode): string
    {
        return in_array($mode, ['period', 'to_date'], true) ? $mode : 'period';
    }

    private function normalizeCurrentRange(array $filters, ?array $selectedPeriod): array
    {
        $mode = $this->normalizeMode((string) ($filters['mode'] ?? 'period'));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($selectedPeriod) {
            $periodStart = (string) ($selectedPeriod['start_date'] ?? $dateFrom);
            $periodEnd = (string) ($selectedPeriod['end_date'] ?? $dateTo);
            if ($mode === 'period') {
                $dateFrom = $periodStart;
                $dateTo = $periodEnd !== '' ? $periodEnd : $dateTo;
            } else {
                $dateFrom = $periodStart;
                if ($dateTo === '') {
                    $dateTo = $periodEnd;
                }
            }
        } else {
            if ($dateTo === '' && $dateFrom !== '') {
                $dateTo = $dateFrom;
            }
            if ($mode === 'to_date' && $dateTo !== '') {
                $dateFrom = substr($dateTo, 0, 7) . '-01';
            } elseif ($dateTo !== '' && $dateFrom === '') {
                $dateFrom = substr($dateTo, 0, 7) . '-01';
            }
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'mode' => $mode,
        ];
    }

    private function resolveCurrentColumnLabel(array $filters, ?array $selectedPeriod): string
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $mode = $this->normalizeMode((string) ($filters['mode'] ?? 'period'));

        if ($mode === 'to_date' && $dateTo !== '') {
            return $this->monthYearLabel($dateTo) . ' s.d. ' . format_id_date($dateTo);
        }
        if ($selectedPeriod) {
            return (string) ($selectedPeriod['period_name'] ?? 'Periode Terpilih');
        }
        if ($dateFrom !== '' && $dateTo !== '' && substr($dateFrom, 0, 7) === substr($dateTo, 0, 7)) {
            return $this->monthYearLabel($dateTo);
        }
        if ($dateFrom !== '' && $dateTo !== '') {
            return format_id_date($dateFrom) . ' s.d. ' . format_id_date($dateTo);
        }
        return 'Periode Terpilih';
    }

    private function monthYearLabel(string $date): string
    {
        $monthMap = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
        $month = substr($date, 5, 2);
        $year = substr($date, 0, 4);
        return ($monthMap[$month] ?? $month) . ' ' . $year;
    }

    private function emptyReport(): array
    {
        return [
            'revenue_rows' => [],
            'expense_rows' => [],
            'total_revenue' => 0.0,
            'total_expense' => 0.0,
            'net_income' => 0.0,
            'row_count' => 0,
            'combined_rows' => [
                'revenue_rows' => [],
                'expense_rows' => [],
            ],
            'current_label' => 'Periode Terpilih',
            'current_range_label' => '-',
            'current_column_label' => 'Periode Terpilih',
            'mode' => 'period',
            'mode_label' => 'Bulanan / Periode',
            'period_total_label' => 'Total Periode',
            'comparison_label' => 'Pembanding',
            'comparison_column_label' => 'Pembanding',
            'comparison_date_from' => '',
            'comparison_date_to' => '',
            'comparison_revenue_rows' => [],
            'comparison_expense_rows' => [],
            'comparison_total_revenue' => 0.0,
            'comparison_total_expense' => 0.0,
            'comparison_net_income' => 0.0,
            'comparison_enabled' => false,
            'comparison_mode' => 'none',
        ];
    }

    private function isValidDate(string $date): bool
    {
        return report_is_valid_date($date);
    }
}
