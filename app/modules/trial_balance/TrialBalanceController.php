<?php

declare(strict_types=1);

final class TrialBalanceController extends Controller
{
    private function model(): TrialBalanceModel
    {
        return new TrialBalanceModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('trial_balance/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman neraca saldo belum dapat dibuka. Pastikan data jurnal, COA, dan periode sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $viewData['title'] = 'Cetak Neraca Saldo';
            $viewData['profile'] = app_profile();
            $viewData['reportTitle'] = 'Neraca Saldo';
            $viewData['periodLabel'] = report_period_label($viewData['filters'], $selectedPeriod);
            $viewData['selectedUnitLabel'] = business_unit_label($selectedUnit);
            $this->view('trial_balance/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak neraca saldo belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $pdf = new ReportPdf('L');
            report_pdf_init($pdf, $profile, 'Neraca Saldo', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
            $widths = [24, 64, 18, 24, 24, 26, 26];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths): void {
                report_pdf_init($pdfObj, $profile, 'Neraca Saldo', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                $pdfObj->tableRow(['Kode', 'Nama Akun', 'Tipe', 'Debit', 'Kredit', 'Saldo', 'Posisi'], $widths, ['L','L','C','R','R','R','C'], 8, true);
            };
            $pdf->tableRow(['Kode', 'Nama Akun', 'Tipe', 'Debit', 'Kredit', 'Saldo', 'Posisi'], $widths, ['L','L','C','R','R','R','C'], 8, true);
            if ($viewData['rows'] === []) {
                $pdf->tableRow(['-', 'Tidak ada data neraca saldo untuk filter yang dipilih.', '-', '-', '-', '-', '-'], $widths, ['C','L','C','C','C','C','C'], 8, false, $headerPrinter);
            } else {
                foreach ($viewData['rows'] as $row) {
                    $pdf->tableRow([
                        (string) $row['account_code'],
                        (string) $row['account_name'],
                        (string) $row['account_type'],
                        ledger_currency((float) $row['period_debit']),
                        ledger_currency((float) $row['period_credit']),
                        ledger_currency((float) $row['closing_balance']),
                        (string) $row['closing_side'],
                    ], $widths, ['L','L','C','R','R','R','C'], 8, false, $headerPrinter);
                }
            }
            $pdf->tableRow(['', 'Total', '', ledger_currency((float) $viewData['summary']['total_debit']), ledger_currency((float) $viewData['summary']['total_credit']), 'D ' . ledger_currency((float) $viewData['summary']['ending_debit_total']) . ' | K ' . ledger_currency((float) $viewData['summary']['ending_credit_total']), ''], $widths, ['L','R','C','R','R','R','C'], 8, true, $headerPrinter);
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('neraca-saldo.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF neraca saldo belum dapat dibuat. Pastikan filter laporan valid lalu coba lagi.', $e);
        }
    }

    private function buildReportData(): array
    {
        $activePeriod = current_accounting_period();
        $defaultPeriodId = $activePeriod ? (int) ($activePeriod['id'] ?? 0) : 0;
        $filters = [
            'period_id' => (int) get_query('period_id', $defaultPeriodId),
            'fiscal_year' => (int) get_query('fiscal_year', 0),
            'unit_id' => (int) get_query('unit_id', 0),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
        ];
        $filters = apply_fiscal_year_filter($filters);

        $periods = $this->model()->getPeriods();
        $units = business_unit_options();
        $selectedPeriod = null;
        $selectedUnit = null;
        $rows = [];
        $summary = ['total_debit' => 0.0, 'total_credit' => 0.0, 'ending_debit_total' => 0.0, 'ending_credit_total' => 0.0, 'account_count' => 0];
        if ($filters['period_id'] > 0 || $filters['unit_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $rawRows = $this->model()->getRows($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            foreach ($rawRows as $row) {
                $periodDebit = (float) $row['period_debit'];
                $periodCredit = (float) $row['period_credit'];
                $closingBalance = trial_balance_closing_balance((float) $row['closing_total_debit'], (float) $row['closing_total_credit'], (string) $row['account_type']);
                $closingSide = trial_balance_closing_side($closingBalance, (string) $row['account_type']);
                $closingAbs = abs($closingBalance);
                $rows[] = [
                    'account_code' => (string) $row['account_code'],
                    'account_name' => (string) $row['account_name'],
                    'account_type' => (string) $row['account_type'],
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'closing_balance' => $closingAbs,
                    'closing_side' => $closingSide,
                ];
                $summary['total_debit'] += $periodDebit;
                $summary['total_credit'] += $periodCredit;
                if ($closingSide === 'D') { $summary['ending_debit_total'] += $closingAbs; }
                elseif ($closingSide === 'K') { $summary['ending_credit_total'] += $closingAbs; }
            }
            $summary['account_count'] = count($rows);
        }
        return [[
            'title' => 'Neraca Saldo',
            'filters' => $filters,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => $units,
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'rows' => $rows,
            'summary' => $summary,
        ], $selectedPeriod, $selectedUnit];
    }

    private function resolveFilters(array $filters): array
    {
        $errors = [];
        $period = null;
        $unit = null;
        if ($filters['unit_id'] > 0) {
            $unit = find_business_unit((int) $filters['unit_id']);
            if (!$unit) {
                $errors[] = 'Unit usaha yang dipilih tidak ditemukan.';
            }
        }
        if ($filters['period_id'] > 0) {
            $period = $this->model()->findPeriodById((int) $filters['period_id']);
            if (!$period) {
                $errors[] = 'Periode yang dipilih tidak ditemukan.';
            } else {
                if ($filters['date_from'] === '') { $filters['date_from'] = (string) $period['start_date']; }
                if ($filters['date_to'] === '') { $filters['date_to'] = (string) $period['end_date']; }
            }
        }
        if ($filters['date_from'] === '' && $filters['date_to'] === '') {
            $errors[] = 'Silakan pilih periode atau isi tanggal filter terlebih dahulu.';
        }
        if ($filters['date_from'] !== '' && !$this->isValidDate($filters['date_from'])) { $errors[] = 'Tanggal mulai tidak valid.'; }
        if ($filters['date_to'] !== '' && !$this->isValidDate($filters['date_to'])) { $errors[] = 'Tanggal akhir tidak valid.'; }
        if ($filters['date_from'] === '' && $filters['date_to'] !== '') { $filters['date_from'] = '1900-01-01'; }
        if ($filters['date_to'] === '' && $filters['date_from'] !== '') { $filters['date_to'] = $filters['date_from']; }
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_to'] < $filters['date_from']) { $errors[] = 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.'; }
        if ($period && $filters['date_from'] < (string) $period['start_date']) { $errors[] = 'Tanggal mulai filter tidak boleh lebih kecil dari tanggal mulai periode yang dipilih.'; }
        if ($period && $filters['date_to'] > (string) $period['end_date']) { $errors[] = 'Tanggal akhir filter tidak boleh lebih besar dari tanggal akhir periode yang dipilih.'; }
        if ($errors !== []) { flash('error', implode(' ', $errors)); $this->redirect('/trial-balance'); }
        return [$filters, $period, $unit];
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
