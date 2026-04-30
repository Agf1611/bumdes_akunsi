<?php

declare(strict_types=1);

final class CashFlowController extends Controller
{
    private function model(): CashFlowModel
    {
        return new CashFlowModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('cash_flow/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman laporan arus kas belum dapat dibuka. Pastikan data jurnal, COA, dan periode akuntansi sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $viewData['title'] = 'Cetak Laporan Arus Kas';
            $viewData['profile'] = app_profile();
            $this->view('cash_flow/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak arus kas belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $pdf = new ReportPdf('L');
            report_pdf_init($pdf, $profile, 'Laporan Arus Kas Sederhana', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
            report_pdf_note($pdf, 'Disajikan dengan metode langsung.');

            $widths = [64, 42];
            $pdf->tableRow(['Komponen', 'Aktual'], $widths, ['L', 'R'], 8.5, true);
            foreach ([
                'Kas Awal' => (float) ($viewData['report']['opening_cash'] ?? 0),
                'Kas Bersih Operasi' => (float) ($viewData['report']['total_operating'] ?? 0),
                'Kas Bersih Investasi' => (float) ($viewData['report']['total_investing'] ?? 0),
                'Kas Bersih Pendanaan' => (float) ($viewData['report']['total_financing'] ?? 0),
                'Kenaikan / Penurunan Kas' => (float) ($viewData['report']['net_cash_change'] ?? 0),
                'Kas Akhir' => (float) ($viewData['report']['closing_cash'] ?? 0),
            ] as $label => $values) {
                $pdf->tableRow([
                    $label,
                    ledger_currency((float) $values),
                ], $widths, ['L', 'R'], 8.5, false);
            }
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-arus-kas.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF arus kas belum dapat dibuat. Pastikan data laporan valid lalu coba lagi.', $e);
        }
    }

    public function xlsx(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $rows = [];
            foreach ([
                'Kas Awal' => (float) ($viewData['report']['opening_cash'] ?? 0),
                'Kas Bersih Operasi' => (float) ($viewData['report']['total_operating'] ?? 0),
                'Kas Bersih Investasi' => (float) ($viewData['report']['total_investing'] ?? 0),
                'Kas Bersih Pendanaan' => (float) ($viewData['report']['total_financing'] ?? 0),
                'Kenaikan / Penurunan Kas' => (float) ($viewData['report']['net_cash_change'] ?? 0),
                'Kas Akhir' => (float) ($viewData['report']['closing_cash'] ?? 0),
            ] as $label => $values) {
                $rows[] = [
                    $label,
                    ledger_currency((float) $values),
                ];
            }

            report_download_xlsx(
                'cash_flow_' . date('Ymd_His') . '.xlsx',
                'Arus Kas',
                'Laporan Arus Kas',
                $viewData['filters'],
                ['Komponen', 'Aktual'],
                $rows,
                []
            );
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export XLSX arus kas belum dapat diproses.');
            $this->redirect('/cash-flow');
        }
    }

    private function buildReportData(): array
    {
        $activePeriod = current_accounting_period();
        $defaultPeriodId = $activePeriod ? (int) ($activePeriod['id'] ?? 0) : 0;

        $filters = [
            'period_id' => (int) get_query('period_id', $defaultPeriodId),
            'period_to_id' => (int) get_query('period_to_id', 0),
            'filter_scope' => report_normalize_filter_scope((string) get_query('filter_scope', 'period')),
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
        $cashAccounts = [];
        $warnings = [];
        $report = $this->emptyReport();
        $comparisonReport = $this->emptyReport();
        $comparison = ['enabled' => false, 'label' => 'Tanpa Pembanding'];

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $cashAccounts = $this->model()->getDetectedCashAccounts();

            if ($cashAccounts === []) {
                $warnings[] = 'Belum ada akun kas/bank yang terdeteksi dari COA. Pastikan nama akun kas atau bank sudah konsisten.';
            } else {
                $report = $this->buildCashFlowReport($cashAccounts, (string) $filters['date_from'], (string) $filters['date_to'], (int) $filters['unit_id'], $warnings);
                if ($filters['comparison_period_id'] > 0) {
                    $selectedComparisonPeriod = $this->model()->findPeriodById((int) $filters['comparison_period_id']);
                }
                $comparison = report_resolve_comparison_range(
                    (string) $filters['comparison_mode'],
                    (string) $filters['date_from'],
                    (string) $filters['date_to'],
                    $selectedPeriod,
                    $selectedComparisonPeriod
                );
                if (($comparison['enabled'] ?? false) === true) {
                    $comparisonReport = $this->buildCashFlowReport($cashAccounts, (string) $comparison['date_from'], (string) $comparison['date_to'], (int) $filters['unit_id'], $warnings);
                }
            }
        }

        return [[
            'title' => 'Laporan Arus Kas Sederhana',
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
            'cashAccounts' => $cashAccounts,
            'warnings' => array_values(array_unique($warnings)),
            'report' => $report,
            'comparison' => $comparison,
            'comparison_report' => $comparisonReport,
            'assumptions' => cash_flow_assumptions(),
            'limitations' => cash_flow_limitations(),
        ], $selectedPeriod, $selectedUnit];
    }

    private function buildCashFlowReport(array $cashAccounts, string $dateFrom, string $dateTo, int $unitId, array &$warnings): array
    {
        $report = $this->emptyReport();
        $cashAccountIds = array_map(static fn (array $row): int => (int) $row['id'], $cashAccounts);
        $report['opening_cash'] = $this->model()->getOpeningCashBalance($cashAccountIds, $dateFrom, $unitId);
        $journalRows = $this->model()->getJournalRows($cashAccountIds, $dateFrom, $dateTo, $unitId);

        foreach ($journalRows as $row) {
            $netAmount = (float) $row['cash_debit'] - (float) $row['cash_credit'];
            if (abs($netAmount) < 0.005) {
                continue;
            }

            [$section, $classificationNote, $ambiguous] = cash_flow_determine_section($row);
            if ($ambiguous) {
                $warnings[] = 'Jurnal ' . (string) $row['journal_no'] . ' memiliki lawan akun campuran sehingga klasifikasi arus kas memakai prioritas sederhana.';
            }

            $entry = [
                'journal_date' => (string) $row['journal_date'],
                'journal_no' => (string) $row['journal_no'],
                'description' => (string) $row['description'],
                'label' => $this->resolveStatementLabel($row),
                'counterpart_accounts' => (string) ($row['counterpart_accounts'] ?? ''),
                'classification_note' => $classificationNote,
                'cashflow_component_codes' => (string) ($row['explicit_cashflow_codes'] ?? ''),
                'cashflow_component_names' => (string) ($row['explicit_cashflow_names'] ?? ''),
                'cash_in' => $netAmount > 0 ? $netAmount : 0.0,
                'cash_out' => $netAmount < 0 ? abs($netAmount) : 0.0,
                'net_amount' => $netAmount,
                'unit_label' => trim((string) ($row['unit_code'] ?? '')) !== '' ? ((string) $row['unit_code'] . ' - ' . (string) ($row['unit_name'] ?? '')) : 'Pusat / umum',
            ];

            if ($section === 'OPERATING') {
                $report['operating_rows'][] = $entry;
                $report['total_operating'] += $netAmount;
            } elseif ($section === 'INVESTING') {
                $report['investing_rows'][] = $entry;
                $report['total_investing'] += $netAmount;
            } else {
                $report['financing_rows'][] = $entry;
                $report['total_financing'] += $netAmount;
            }
        }

        $report['net_cash_change'] = $report['total_operating'] + $report['total_investing'] + $report['total_financing'];
        $report['closing_cash'] = $report['opening_cash'] + $report['net_cash_change'];
        $report['actual_closing_cash'] = $report['closing_cash'];
        $report['difference'] = 0.0;
        $report['row_count'] = count($report['operating_rows']) + count($report['investing_rows']) + count($report['financing_rows']);
        $report['sections'] = $this->buildSectionSummaries($report);

        return $report;
    }

    private function resolveStatementLabel(array $row): string
    {
        $counterpartLabel = $this->formatCounterpartAccounts((string) ($row['counterpart_accounts'] ?? ''));
        if ($counterpartLabel !== '') {
            return $counterpartLabel;
        }

        $componentLabel = trim((string) ($row['explicit_cashflow_names'] ?? ''));
        if ($componentLabel !== '') {
            return $componentLabel;
        }

        $description = trim((string) ($row['description'] ?? ''));
        return $description !== '' ? $description : 'Mutasi kas';
    }

    private function formatCounterpartAccounts(string $counterpartAccounts): string
    {
        $counterpartAccounts = trim($counterpartAccounts);
        if ($counterpartAccounts === '') {
            return '';
        }

        $labels = [];
        foreach (preg_split('/\s*\|\s*/', $counterpartAccounts) ?: [] as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $label = preg_replace('/^[0-9][0-9.\-]*\s*-\s*/', '', $part) ?? $part;
            $label = trim($label);
            if ($label !== '') {
                $labels[mb_strtolower($label)] = $label;
            }
        }

        return implode(' / ', array_values($labels));
    }

    private function buildSectionSummaries(array $report): array
    {
        $summaries = [];
        $map = [
            'OPERATING' => ['title' => 'Arus Kas dari Aktivitas Operasi', 'rows' => (array) ($report['operating_rows'] ?? []), 'total' => (float) ($report['total_operating'] ?? 0)],
            'INVESTING' => ['title' => 'Arus Kas dari Aktivitas Investasi', 'rows' => (array) ($report['investing_rows'] ?? []), 'total' => (float) ($report['total_investing'] ?? 0)],
            'FINANCING' => ['title' => 'Arus Kas dari Aktivitas Pendanaan', 'rows' => (array) ($report['financing_rows'] ?? []), 'total' => (float) ($report['total_financing'] ?? 0)],
        ];

        foreach ($map as $section => $config) {
            $inRows = [];
            $outRows = [];
            foreach ($config['rows'] as $row) {
                if ((float) ($row['cash_in'] ?? 0) > 0) {
                    $this->addStatementRow($inRows, (string) ($row['label'] ?? ''), (float) ($row['cash_in'] ?? 0));
                }
                if ((float) ($row['cash_out'] ?? 0) > 0) {
                    $this->addStatementRow($outRows, (string) ($row['label'] ?? ''), (float) ($row['cash_out'] ?? 0));
                }
            }
            $summaries[$section] = [
                'title' => (string) $config['title'],
                'in_rows' => $inRows,
                'out_rows' => $outRows,
                'total_in' => array_sum(array_column($inRows, 'amount')),
                'total_out' => array_sum(array_column($outRows, 'amount')),
                'net' => (float) $config['total'],
                'net_label' => 'Arus kas bersih',
            ];
        }

        return $summaries;
    }

    private function addStatementRow(array &$rows, string $label, float $amount): void
    {
        $label = trim($label) !== '' ? trim($label) : 'Mutasi kas';
        $key = mb_strtolower($label);

        if (!isset($rows[$key])) {
            $rows[$key] = [
                'label' => $label,
                'amount' => 0.0,
            ];
        }

        $rows[$key]['amount'] += $amount;
    }

    private function emptyReport(): array
    {
        return [
            'sections' => [],
            'operating_rows' => [],
            'investing_rows' => [],
            'financing_rows' => [],
            'opening_cash' => 0.0,
            'total_operating' => 0.0,
            'total_investing' => 0.0,
            'total_financing' => 0.0,
            'net_cash_change' => 0.0,
            'closing_cash' => 0.0,
            'actual_closing_cash' => 0.0,
            'difference' => 0.0,
            'row_count' => 0,
        ];
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

        [$filters, $period, , $periodErrors] = report_resolve_period_filter($filters, fn (int $id): ?array => $this->model()->findPeriodById($id));
        $errors = array_merge($errors, $periodErrors);

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
            $this->redirect('/cash-flow');
        }

        return [$filters, $period, $unit];
    }

    private function isValidDate(string $date): bool
    {
        return report_is_valid_date($date);
    }
}
