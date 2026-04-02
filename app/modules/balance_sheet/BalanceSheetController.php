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
            $comparisonEnabled = (bool) ($report['comparison_enabled'] ?? false);

            $pdf = new ReportPdf('P');
            report_pdf_init($pdf, $profile, 'Laporan Neraca', report_period_label($viewData['filters'], $selectedPeriod, true), $unitLabel, true);
            if ($comparisonEnabled) {
                report_pdf_note($pdf, 'Kolom pembanding disajikan otomatis untuk ' . (string) ($report['comparison_label'] ?? '-') . '.');
            }
            if (!$report['is_balanced']) {
                report_pdf_note($pdf, 'Peringatan: Neraca belum seimbang. Selisih ' . ledger_currency(abs((float) $report['difference'])) . '.');
            }

            $widths = $comparisonEnabled ? [20, 78, 41, 41] : [24, 86, 52];
            $comparisonColumnLabel = (string) ($report['comparison_column_label'] ?? 'Tahun Sebelumnya');
            $headCells = $comparisonEnabled ? ['Kode', 'Nama Akun', 'Saldo Akhir', $comparisonColumnLabel] : ['Kode', 'Nama Akun', 'Saldo Akhir'];
            $headAligns = $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths, $headCells, $headAligns): void {
                report_pdf_init($pdfObj, $profile, 'Laporan Neraca', report_period_label($viewData['filters'], $selectedPeriod, true), $unitLabel, true);
                if (($viewData['report']['comparison_enabled'] ?? false) === true) {
                    report_pdf_note($pdfObj, 'Kolom pembanding disajikan otomatis untuk ' . (string) ($viewData['report']['comparison_label'] ?? '-') . '.');
                }
                $pdfObj->tableRow($headCells, $widths, $headAligns, 8.5, true);
            };
            $pdf->tableRow($headCells, $widths, $headAligns, 8.5, true);

            $sectionPrinter = static function (ReportPdf $pdfObj, string $label) use ($widths, $headerPrinter, $comparisonEnabled): void {
                $cells = $comparisonEnabled ? ['', $label, '', ''] : ['', $label, ''];
                $aligns = $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'];
                $pdfObj->tableRow($cells, $widths, $aligns, 8.5, true, $headerPrinter);
            };

            $rowPrinter = static function (ReportPdf $pdfObj, array $row) use ($widths, $comparisonEnabled, $headerPrinter): void {
                $cells = [
                    (string) ($row['account_code'] ?? ''),
                    (string) ($row['account_name'] ?? ''),
                    ledger_currency((float) ($row['amount'] ?? 0)),
                ];
                $aligns = ['L', 'L', 'R'];
                if ($comparisonEnabled) {
                    $cells[] = ledger_currency((float) ($row['comparison_amount'] ?? 0));
                    $aligns[] = 'R';
                }
                $pdfObj->tableRow($cells, $widths, $aligns, 8.5, false, $headerPrinter);
            };

            $emptyRowPrinter = static function (ReportPdf $pdfObj, string $message) use ($widths, $comparisonEnabled, $headerPrinter): void {
                $cells = $comparisonEnabled ? ['-', $message, '-', '-'] : ['-', $message, '-'];
                $aligns = $comparisonEnabled ? ['C', 'L', 'C', 'C'] : ['C', 'L', 'C'];
                $pdfObj->tableRow($cells, $widths, $aligns, 8.5, false, $headerPrinter);
            };

            $totalRowPrinter = static function (ReportPdf $pdfObj, string $label, float $amount, float $comparisonAmount = 0.0) use ($widths, $comparisonEnabled, $headerPrinter): void {
                $cells = ['', $label, ledger_currency($amount)];
                $aligns = ['L', 'R', 'R'];
                if ($comparisonEnabled) {
                    $cells[] = ledger_currency($comparisonAmount);
                    $aligns[] = 'R';
                }
                $pdfObj->tableRow($cells, $widths, $aligns, 8.5, true, $headerPrinter);
            };

            $sectionPrinter($pdf, 'Aset');
            if ($report['asset_rows'] === []) {
                $emptyRowPrinter($pdf, 'Tidak ada akun aset untuk filter yang dipilih.');
            } else {
                foreach ($report['asset_rows'] as $row) {
                    $rowPrinter($pdf, $row);
                }
            }
            $totalRowPrinter($pdf, 'Total Aset', (float) $report['total_assets'], (float) ($report['comparison_total_assets'] ?? 0));

            $sectionPrinter($pdf, 'Liabilitas');
            if ($report['liability_rows'] === []) {
                $emptyRowPrinter($pdf, 'Tidak ada akun liabilitas untuk filter yang dipilih.');
            } else {
                foreach ($report['liability_rows'] as $row) {
                    $rowPrinter($pdf, $row);
                }
            }
            $totalRowPrinter($pdf, 'Total Liabilitas', (float) $report['total_liabilities'], (float) ($report['comparison_total_liabilities'] ?? 0));

            $sectionPrinter($pdf, 'Ekuitas');
            if ($report['equity_rows'] === []) {
                $emptyRowPrinter($pdf, 'Tidak ada akun ekuitas untuk filter yang dipilih.');
            } else {
                foreach ($report['equity_rows'] as $row) {
                    $rowPrinter($pdf, $row);
                }
            }
            if (abs((float) $report['current_earnings']) > 0.004 || abs((float) ($report['comparison_current_earnings'] ?? 0)) > 0.004) {
                $rowPrinter($pdf, [
                    'account_code' => '',
                    'account_name' => 'Laba / Rugi Berjalan',
                    'amount' => (float) $report['current_earnings'],
                    'comparison_amount' => (float) ($report['comparison_current_earnings'] ?? 0),
                ]);
            }
            $totalRowPrinter($pdf, 'Total Ekuitas', (float) $report['total_equity'], (float) ($report['comparison_total_equity'] ?? 0));
            $totalRowPrinter($pdf, 'Total Liabilitas + Ekuitas', (float) $report['total_liabilities_equity'], (float) ($report['comparison_total_liabilities_equity'] ?? 0));
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-neraca.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF neraca belum dapat dibuat. Pastikan filter laporan valid lalu coba lagi.', $e);
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
        ];

        $filters = apply_fiscal_year_filter($filters);

        $periods = $this->model()->getPeriods();
        $selectedPeriod = null;
        $selectedUnit = null;
        $report = $this->emptyReport();

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $report = $this->buildSnapshot($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);

            $comparison = $this->buildOpeningComparison((string) $filters['date_to'], (int) $filters['unit_id']);
            if (($comparison['enabled'] ?? false) === true) {
                $report['asset_rows'] = $this->mergeComparisonRows($report['asset_rows'], $comparison['asset_rows']);
                $report['liability_rows'] = $this->mergeComparisonRows($report['liability_rows'], $comparison['liability_rows']);
                $report['equity_rows'] = $this->mergeComparisonRows($report['equity_rows'], $comparison['equity_rows']);
                $report['comparison_enabled'] = true;
                $report['comparison_label'] = (string) ($comparison['label'] ?? 'Tahun Sebelumnya');
                $report['comparison_column_label'] = (string) ($comparison['column_label'] ?? 'Tahun Sebelumnya');
                $report['comparison_total_assets'] = (float) ($comparison['total_assets'] ?? 0.0);
                $report['comparison_total_liabilities'] = (float) ($comparison['total_liabilities'] ?? 0.0);
                $report['comparison_total_equity'] = (float) ($comparison['total_equity'] ?? 0.0);
                $report['comparison_total_liabilities_equity'] = (float) ($comparison['total_liabilities_equity'] ?? 0.0);
                $report['comparison_current_earnings'] = (float) ($comparison['current_earnings'] ?? 0.0);
                $report['comparison_difference'] = (float) ($comparison['difference'] ?? 0.0);
                $report['comparison_is_balanced'] = (bool) ($comparison['is_balanced'] ?? true);
                $report['row_count'] = count($report['asset_rows']) + count($report['liability_rows']) + count($report['equity_rows']);
            }

            $report = $this->normalizeCurrentEquityBalance($report);
        }

        return [[
            'title' => 'Laporan Neraca',
            'filters' => $filters,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'report' => $report,
        ], $selectedPeriod, $selectedUnit];
    }

    private function buildSnapshot(string $dateFrom, string $dateTo, int $unitId): array
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

    private function buildOpeningComparison(string $dateTo, int $unitId): array
    {
        if (!$this->isValidDate($dateTo)) {
            return ['enabled' => false];
        }

        $reportYear = (int) substr($dateTo, 0, 4);
        if ($reportYear <= 0) {
            return ['enabled' => false];
        }

        $openingDate = sprintf('%04d-01-01', $reportYear);
        $previousYearEnd = sprintf('%04d-12-31', $reportYear - 1);
        $rawRows = $this->model()->getOpeningSnapshotRows($openingDate, $unitId);
        if ($rawRows === []) {
            return ['enabled' => false];
        }

        $comparison = $this->emptyReport();
        foreach ($rawRows as $row) {
            $amount = balance_sheet_amount(
                (string) $row['account_type'],
                (float) $row['opening_total_debit'],
                (float) $row['opening_total_credit']
            );

            $entry = [
                'account_code' => (string) $row['account_code'],
                'account_name' => (string) $row['account_name'],
                'account_type' => (string) $row['account_type'],
                'account_category' => (string) $row['account_category'],
                'closing_total_debit' => (float) $row['opening_total_debit'],
                'closing_total_credit' => (float) $row['opening_total_credit'],
                'amount' => $amount,
                'comparison_amount' => 0.0,
            ];

            if ((string) $row['account_type'] === 'ASSET') {
                $comparison['asset_rows'][] = $entry;
                $comparison['total_assets'] += $amount;
            } elseif ((string) $row['account_type'] === 'LIABILITY') {
                $comparison['liability_rows'][] = $entry;
                $comparison['total_liabilities'] += $amount;
            } elseif ((string) $row['account_type'] === 'EQUITY') {
                $comparison['equity_rows'][] = $entry;
                $comparison['total_equity'] += $amount;
            }
        }

        $comparison['current_earnings'] = 0.0;
        $comparison['total_liabilities_equity'] = $comparison['total_liabilities'] + $comparison['total_equity'];
        $comparison['difference'] = $comparison['total_assets'] - $comparison['total_liabilities_equity'];
        $comparison['is_balanced'] = balance_sheet_is_balanced((float) $comparison['total_assets'], (float) $comparison['total_liabilities_equity']);
        $comparison['row_count'] = count($comparison['asset_rows']) + count($comparison['liability_rows']) + count($comparison['equity_rows']);
        $comparison['enabled'] = true;
        $comparison['label'] = 'Tahun Sebelumnya / ' . format_id_date($previousYearEnd);
        $comparison['column_label'] = 'Tahun Sebelumnya / ' . format_id_date($previousYearEnd);
        $comparison['comparison_as_of_date'] = $previousYearEnd;
        $comparison['opening_journal_date'] = $openingDate;

        return $comparison;
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
            'comparison_period' => null,
            'comparison_label' => '',
            'comparison_column_label' => 'Tahun Sebelumnya',
            'comparison_as_of_date' => '',
            'opening_journal_date' => '',
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
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
