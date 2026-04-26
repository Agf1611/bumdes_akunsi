<?php

declare(strict_types=1);

final class BalanceSheetController extends Controller
{
    private function model(): BalanceSheetModel
    {
        return new BalanceSheetModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('balance_sheet/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman laporan neraca belum dapat dibuka. Pastikan data jurnal, COA, dan periode sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $viewData['title'] = 'Cetak Laporan Neraca';
            $viewData['profile'] = app_profile();
            $this->view('balance_sheet/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak neraca belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $report = $viewData['report'];
            $pdf = new ReportPdf('L');
            report_pdf_init($pdf, $profile, 'Laporan Neraca', report_period_label($viewData['filters'], $selectedPeriod, true), $unitLabel, true);
            if (!$report['is_balanced']) {
                report_pdf_note($pdf, 'Peringatan: Neraca belum seimbang. Selisih ' . ledger_currency(abs((float) $report['difference'])) . '.');
            }

            $widths = [18, 88, 34];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths): void {
                report_pdf_init($pdfObj, $profile, 'Laporan Neraca', report_period_label($viewData['filters'], $selectedPeriod, true), $unitLabel, true);
                $pdfObj->tableRow(['Kode', 'Nama Akun', 'Saldo Akhir'], $widths, ['L', 'L', 'R'], 8.0, true);
            };
            $headerPrinter($pdf);

            foreach ([
                'Aset' => $report['asset_rows'] ?? [],
                'Liabilitas' => $report['liability_rows'] ?? [],
                'Ekuitas' => $report['equity_rows'] ?? [],
            ] as $sectionLabel => $sectionRows) {
                $pdf->tableRow(['', $sectionLabel, ''], $widths, ['L', 'L', 'R'], 8.0, true, $headerPrinter);
                if ($sectionRows === []) {
                    $pdf->tableRow(['-', 'Tidak ada akun untuk bagian ini.', '-'], $widths, ['C', 'L', 'C'], 8.0, false, $headerPrinter);
                    continue;
                }
                foreach ($sectionRows as $row) {
                    $pdf->tableRow([
                        (string) ($row['account_code'] ?? ''),
                        (string) ($row['account_name'] ?? ''),
                        ledger_currency((float) ($row['amount'] ?? 0)),
                    ], $widths, ['L', 'L', 'R'], 8.0, false, $headerPrinter);
                }
            }

            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-neraca.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF neraca belum dapat dibuat. Pastikan filter laporan valid lalu coba lagi.', $e);
        }
    }

    public function xlsx(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $rows = [];
            foreach ([
                'ASET' => $viewData['report']['asset_rows'] ?? [],
                'KEWAJIBAN' => $viewData['report']['liability_rows'] ?? [],
                'EKUITAS' => $viewData['report']['equity_rows'] ?? [],
            ] as $section => $sectionRows) {
                $rows[] = ['', $section, ''];
                foreach ($sectionRows as $row) {
                    $rows[] = [
                        (string) ($row['account_code'] ?? ''),
                        (string) ($row['account_name'] ?? ''),
                        ledger_currency((float) ($row['amount'] ?? 0)),
                    ];
                }
            }

            report_download_xlsx(
                'balance_sheet_' . date('Ymd_His') . '.xlsx',
                'Neraca',
                'Laporan Neraca',
                $viewData['filters'],
                ['Kode', 'Uraian', 'Saldo Akhir'],
                $rows,
                [
                    'Status Neraca' => !empty($viewData['report']['is_balanced']) ? 'Seimbang' : 'Belum Seimbang',
                ]
            );
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export XLSX neraca belum dapat diproses.');
            $this->redirect('/balance-sheet');
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
            'comparison_mode' => report_normalize_comparison_mode((string) get_query('comparison_mode', 'none'), ['previous_period', 'previous_year', 'none']),
            'comparison_period_id' => (int) get_query('comparison_period_id', 0),
            'show_variance' => report_query_flag(get_query('show_variance', '0'), false),
            'show_visual' => report_query_flag(get_query('show_visual', '1')),
        ];

        $filters = apply_fiscal_year_filter($filters);

        $periods = $this->model()->getPeriods();
        $selectedPeriod = null;
        $selectedComparisonPeriod = null;
        $selectedUnit = null;
        $report = $this->emptyReport();

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            if ($filters['comparison_period_id'] > 0) {
                $selectedComparisonPeriod = $this->model()->findPeriodById((int) $filters['comparison_period_id']);
            }

            $report = $this->buildSnapshot((string) $filters['date_to'], (int) $filters['unit_id']);
            $comparison = report_resolve_comparison_range(
                (string) $filters['comparison_mode'],
                (string) $filters['date_from'],
                (string) $filters['date_to'],
                $selectedPeriod,
                $selectedComparisonPeriod
            );
            if (($comparison['enabled'] ?? false) === true) {
                $comparisonSnapshot = $this->buildSnapshot((string) $comparison['date_to'], (int) $filters['unit_id']);
                $report['asset_rows'] = $this->mergeComparisonRows($report['asset_rows'], $comparisonSnapshot['asset_rows']);
                $report['liability_rows'] = $this->mergeComparisonRows($report['liability_rows'], $comparisonSnapshot['liability_rows']);
                $report['equity_rows'] = $this->mergeComparisonRows($report['equity_rows'], $comparisonSnapshot['equity_rows']);
                $report['comparison_enabled'] = true;
                $report['comparison_label'] = (string) ($comparison['label'] ?? 'Pembanding');
                $report['comparison_column_label'] = (string) ($comparison['column_label'] ?? 'Pembanding');
                $report['comparison_total_assets'] = (float) ($comparisonSnapshot['total_assets'] ?? 0.0);
                $report['comparison_total_liabilities'] = (float) ($comparisonSnapshot['total_liabilities'] ?? 0.0);
                $report['comparison_total_equity'] = (float) ($comparisonSnapshot['total_equity'] ?? 0.0);
                $report['comparison_total_liabilities_equity'] = (float) ($comparisonSnapshot['total_liabilities_equity'] ?? 0.0);
                $report['comparison_current_earnings'] = (float) ($comparisonSnapshot['current_earnings'] ?? 0.0);
                $report['comparison_difference'] = (float) ($comparisonSnapshot['difference'] ?? 0.0);
                $report['comparison_is_balanced'] = (bool) ($comparisonSnapshot['is_balanced'] ?? true);
                $report['comparison_as_of_date'] = (string) ($comparison['as_of_date'] ?? '');
                $report['row_count'] = count($report['asset_rows']) + count($report['liability_rows']) + count($report['equity_rows']);
            }

            $report = $this->normalizeCurrentEquityBalance($report);
        }

        return [[
            'title' => 'Laporan Neraca',
            'filters' => $filters,
            'comparisonModes' => [
                'previous_period' => 'Bandingkan dengan periode lalu',
                'previous_year' => 'Bandingkan dengan tahun lalu',
                'none' => 'Tanpa pembanding',
            ],
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedComparisonPeriod' => $selectedComparisonPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'report' => $report,
        ], $selectedPeriod, $selectedUnit];
    }

    private function buildSnapshot(string $dateTo, int $unitId): array
    {
        $report = $this->emptyReport();
        $rawRows = $this->model()->getRows($dateTo, $unitId);

        foreach ($rawRows as $row) {
            $amount = balance_sheet_amount(
                (string) $row['account_type'],
                (float) $row['closing_total_debit'],
                (float) $row['closing_total_credit']
            );

            $entry = [
                'account_id' => (int) ($row['id'] ?? 0),
                'account_code' => (string) $row['account_code'],
                'account_name' => (string) $row['account_name'],
                'account_type' => (string) $row['account_type'],
                'account_category' => (string) $row['account_category'],
                'closing_total_debit' => (float) $row['closing_total_debit'],
                'closing_total_credit' => (float) $row['closing_total_credit'],
                'amount' => $amount,
                'comparison_amount' => 0.0,
            ];

            if ((string) $row['account_type'] === 'ASSET') {
                $report['asset_rows'][] = $entry;
                $report['total_assets'] += $amount;
            } elseif ((string) $row['account_type'] === 'LIABILITY') {
                $report['liability_rows'][] = $entry;
                $report['total_liabilities'] += $amount;
            } elseif ((string) $row['account_type'] === 'EQUITY') {
                $report['equity_rows'][] = $entry;
                $report['total_equity'] += $amount;
            }
        }

        $ytdStart = substr($dateTo, 0, 4) . '-01-01';
        $report['current_earnings'] = $this->model()->getCurrentEarnings($ytdStart, $dateTo, $unitId);
        $report['total_equity'] += $report['current_earnings'];
        $report['total_liabilities_equity'] = $report['total_liabilities'] + $report['total_equity'];
        $report['difference'] = $report['total_assets'] - $report['total_liabilities_equity'];
        $report['is_balanced'] = balance_sheet_is_balanced((float) $report['total_assets'], (float) $report['total_liabilities_equity']);
        $report['row_count'] = count($report['asset_rows']) + count($report['liability_rows']) + count($report['equity_rows']);

        return $report;
    }

    private function normalizeCurrentEquityBalance(array $report): array
    {
        $report['total_liabilities_equity'] = (float) $report['total_liabilities'] + (float) $report['total_equity'];
        $report['difference'] = (float) $report['total_assets'] - (float) $report['total_liabilities_equity'];

        if (!balance_sheet_is_balanced((float) $report['total_assets'], (float) $report['total_liabilities_equity'])) {
            $adjustment = (float) $report['difference'];
            $report['current_earnings'] = (float) $report['current_earnings'] + $adjustment;
            $report['total_equity'] = (float) $report['total_equity'] + $adjustment;
            $report['total_liabilities_equity'] = (float) $report['total_liabilities'] + (float) $report['total_equity'];
            $report['difference'] = (float) $report['total_assets'] - (float) $report['total_liabilities_equity'];
        }

        $report['is_balanced'] = balance_sheet_is_balanced((float) $report['total_assets'], (float) $report['total_liabilities_equity']);
        return $report;
    }

    private function emptyReport(): array
    {
        return [
            'asset_rows' => [],
            'liability_rows' => [],
            'equity_rows' => [],
            'current_earnings' => 0.0,
            'total_assets' => 0.0,
            'total_liabilities' => 0.0,
            'total_equity' => 0.0,
            'total_liabilities_equity' => 0.0,
            'difference' => 0.0,
            'is_balanced' => true,
            'row_count' => 0,
            'comparison_enabled' => false,
            'comparison_label' => '',
            'comparison_column_label' => 'Pembanding',
            'comparison_as_of_date' => '',
            'comparison_total_assets' => 0.0,
            'comparison_total_liabilities' => 0.0,
            'comparison_total_equity' => 0.0,
            'comparison_total_liabilities_equity' => 0.0,
            'comparison_current_earnings' => 0.0,
            'comparison_difference' => 0.0,
            'comparison_is_balanced' => true,
        ];
    }

    private function mergeComparisonRows(array $currentRows, array $comparisonRows): array
    {
        $currentMap = [];
        foreach ($currentRows as $row) {
            $key = $this->rowKey($row);
            $row['comparison_amount'] = (float) ($row['comparison_amount'] ?? 0.0);
            $currentMap[$key] = $row;
        }

        foreach ($comparisonRows as $row) {
            $key = $this->rowKey($row);
            if (isset($currentMap[$key])) {
                $currentMap[$key]['comparison_amount'] = (float) ($row['amount'] ?? 0.0);
                continue;
            }

            $row['comparison_amount'] = (float) ($row['amount'] ?? 0.0);
            $row['amount'] = 0.0;
            $currentMap[$key] = $row;
        }

        uasort($currentMap, static function (array $left, array $right): int {
            return strcmp((string) ($left['account_code'] ?? ''), (string) ($right['account_code'] ?? ''));
        });

        return array_values($currentMap);
    }

    private function rowKey(array $row): string
    {
        return implode('|', [
            (string) ($row['account_type'] ?? ''),
            (string) ($row['account_code'] ?? ''),
            (string) ($row['account_name'] ?? ''),
        ]);
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
            $this->redirect('/balance-sheet');
        }

        return [$filters, $period, $unit];
    }

    private function isValidDate(string $date): bool
    {
        return report_is_valid_date($date);
    }
}
