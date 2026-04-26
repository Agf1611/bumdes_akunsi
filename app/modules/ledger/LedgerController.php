<?php

declare(strict_types=1);

final class LedgerController extends Controller
{
    private function model(): LedgerModel
    {
        return new LedgerModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('ledger/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman buku besar belum dapat dibuka. Pastikan data jurnal, COA, dan periode sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            if (($viewData['errors'] ?? []) !== []) {
                flash('error', implode(' ', (array) $viewData['errors']));
                $this->redirect('/ledger');
            }
            $viewData['title'] = 'Cetak Buku Besar';
            $viewData['profile'] = app_profile();
            $viewData['reportTitle'] = 'Buku Besar';
            $viewData['periodLabel'] = report_period_label($viewData['filters'], $selectedPeriod);
            $viewData['selectedUnitLabel'] = business_unit_label($selectedUnit);
            $this->view('ledger/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak buku besar belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            if (($viewData['errors'] ?? []) !== []) {
                flash('error', implode(' ', (array) $viewData['errors']));
                $this->redirect('/ledger');
            }
            if (!$viewData['selectedAccount']) {
                flash('error', 'Silakan pilih akun dan filter yang valid terlebih dahulu untuk membuat PDF buku besar.');
                $this->redirect('/ledger');
            }
            $profile = app_profile();
            $pdf = new ReportPdf('L');
            $unitLabel = business_unit_label($selectedUnit);
            report_pdf_init($pdf, $profile, 'Buku Besar', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
            $accountLabel = $viewData['selectedAccount']['account_code'] . ' - ' . $viewData['selectedAccount']['account_name'];
            report_pdf_note($pdf, 'Akun: ' . $accountLabel . '. Saldo awal ' . (function_exists('ledger_currency_print') ? ledger_currency_print((float) $viewData['summary']['opening_balance']) : ledger_currency((float) $viewData['summary']['opening_balance'])) . ' | saldo akhir ' . (function_exists('ledger_currency_print') ? ledger_currency_print((float) $viewData['summary']['closing_balance']) : ledger_currency((float) $viewData['summary']['closing_balance'])) . '.');

            $widths = [20, 32, 82, 30, 30, 30, 38];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths): void {
                report_pdf_init($pdfObj, $profile, 'Buku Besar', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                report_pdf_note($pdfObj, 'Akun: ' . $viewData['selectedAccount']['account_code'] . ' - ' . $viewData['selectedAccount']['account_name'] . '.');
                $pdfObj->tableRow(['Tanggal', 'No. Jurnal', 'Unit', 'Keterangan', 'Debit', 'Kredit', 'Saldo'], $widths, ['L', 'L', 'L', 'L', 'R', 'R', 'R'], 8, true);
            };
            $pdf->tableRow(['Tanggal', 'No. Jurnal', 'Unit', 'Keterangan', 'Debit', 'Kredit', 'Saldo'], $widths, ['L', 'L', 'L', 'L', 'R', 'R', 'R'], 8, true);
            if ($viewData['rows'] === []) {
                $pdf->tableRow(['-', '-', '-', 'Tidak ada mutasi jurnal untuk filter yang dipilih.', '-', '-', '-'], $widths, ['C','C','C','L','C','C','C'], 8, false, $headerPrinter);
            } else {
                foreach ($viewData['rows'] as $row) {
                    $pdf->tableRow([
                        format_id_date((string) $row['journal_date']),
                        (string) $row['journal_no'],
                        (string) ($row['unit_label'] ?? '-'),
                        (string) $row['description'],
                        function_exists('ledger_currency_print') ? ledger_currency_print((float) $row['debit']) : ledger_currency((float) $row['debit']),
                        function_exists('ledger_currency_print') ? ledger_currency_print((float) $row['credit']) : ledger_currency((float) $row['credit']),
                        function_exists('ledger_currency_print') ? ledger_currency_print((float) $row['balance']) : ledger_currency((float) $row['balance']),
                    ], $widths, ['L','L','L','L','R','R','R'], 8, false, $headerPrinter);
                }
            }
            $pdf->tableRow(['', '', '', 'Total Mutasi', function_exists('ledger_currency_print') ? ledger_currency_print((float) $viewData['summary']['total_debit']) : ledger_currency((float) $viewData['summary']['total_debit']), function_exists('ledger_currency_print') ? ledger_currency_print((float) $viewData['summary']['total_credit']) : ledger_currency((float) $viewData['summary']['total_credit']), function_exists('ledger_currency_print') ? ledger_currency_print((float) $viewData['summary']['closing_balance']) : ledger_currency((float) $viewData['summary']['closing_balance'])], $widths, ['L','L','L','R','R','R','R'], 8, true, $headerPrinter);
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('buku-besar.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF buku besar belum dapat dibuat. Pastikan filter dan data valid lalu coba lagi.', $e);
        }
    }

    public function xlsx(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            if (($viewData['errors'] ?? []) !== []) {
                flash('error', implode(' ', (array) $viewData['errors']));
                $this->redirect('/ledger');
            }
            if (!$viewData['selectedAccount']) {
                flash('error', 'Silakan pilih akun terlebih dahulu untuk export buku besar.');
                $this->redirect('/ledger');
            }

            $rows = [];
            foreach ($viewData['rows'] as $row) {
                $rows[] = [
                    format_id_date((string) $row['journal_date']),
                    (string) $row['journal_no'],
                    (string) ($row['unit_label'] ?? '-'),
                    (string) $row['description'],
                    ledger_currency((float) $row['debit']),
                    ledger_currency((float) $row['credit']),
                    ledger_currency((float) $row['balance']),
                ];
            }
            $rows[] = ['', '', '', 'Total Mutasi', ledger_currency((float) $viewData['summary']['total_debit']), ledger_currency((float) $viewData['summary']['total_credit']), ledger_currency((float) $viewData['summary']['closing_balance'])];

            report_download_xlsx(
                'ledger_' . date('Ymd_His') . '.xlsx',
                'Buku Besar',
                'Buku Besar',
                $viewData['filters'],
                ['Tanggal', 'No. Jurnal', 'Unit', 'Keterangan', 'Debit', 'Kredit', 'Saldo'],
                $rows,
                [
                    'Akun' => (string) ($viewData['selectedAccount']['account_code'] ?? '') . ' - ' . (string) ($viewData['selectedAccount']['account_name'] ?? ''),
                    'Saldo Awal' => ledger_currency((float) $viewData['summary']['opening_balance']),
                    'Saldo Akhir' => ledger_currency((float) $viewData['summary']['closing_balance']),
                ]
            );
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export XLSX buku besar belum dapat diproses.');
            $this->redirect('/ledger');
        }
    }

    private function buildReportData(): array
    {
        $filters = [
            'account_id' => (int) get_query('account_id', 0),
            'period_id' => (int) get_query('period_id', 0),
            'fiscal_year' => (int) get_query('fiscal_year', 0),
            'unit_id' => (int) get_query('unit_id', 0),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
        ];

        $accounts = $this->model()->getAccountOptions();
        $filters = apply_fiscal_year_filter($filters);

        $periods = $this->model()->getPeriods();
        $units = business_unit_options();
        $account = null;
        $period = null;
        $unit = null;
        $rows = [];
        $warnings = [];
        $errors = [];
        $summary = ['opening_balance' => 0.0, 'total_debit' => 0.0, 'total_credit' => 0.0, 'closing_balance' => 0.0];

        if ($filters['account_id'] > 0 || $filters['period_id'] > 0 || $filters['unit_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $account, $period, $unit, $errors, $warnings] = $this->resolveFilters($filters);
            if ($errors === [] && $account !== null) {
                $openingRaw = $this->model()->getOpeningBalance((int) $account['id'], $filters['date_from'] !== '' ? $filters['date_from'] : null, (int) $filters['unit_id']);
                $running = $this->convertRawBalanceForAccount($openingRaw, (string) $account['account_type']);
                $summary['opening_balance'] = $running;
                $mutations = $this->model()->getMutations((int) $account['id'], $filters['date_from'] !== '' ? $filters['date_from'] : null, $filters['date_to'] !== '' ? $filters['date_to'] : null, (int) $filters['unit_id']);
                foreach ($mutations as $mutation) {
                    $debit = (float) $mutation['debit'];
                    $credit = (float) $mutation['credit'];
                    $running = ledger_apply_balance($running, $debit, $credit, (string) $account['account_type']);
                    $summary['total_debit'] += $debit;
                    $summary['total_credit'] += $credit;
                    $rows[] = [
                        'journal_id' => (int) $mutation['journal_id'],
                        'journal_date' => (string) $mutation['journal_date'],
                        'journal_no' => (string) $mutation['journal_no'],
                        'description' => trim((string) $mutation['line_description']) !== '' ? (string) $mutation['line_description'] : (string) $mutation['journal_description'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $running,
                        'unit_label' => trim((string) ($mutation['unit_name'] ?? '')) !== '' ? ((string) ($mutation['unit_code'] ?? '') . ' - ' . (string) $mutation['unit_name']) : 'Semua / belum ditentukan',
                    ];
                }
                $summary['closing_balance'] = $running;
            }
        }

        return [[
            'title' => 'Buku Besar',
            'accounts' => $accounts,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => $units,
            'filters' => $filters,
            'selectedAccount' => $account,
            'selectedPeriod' => $period,
            'selectedUnit' => $unit,
            'rows' => $rows,
            'summary' => $summary,
            'warnings' => $warnings,
            'errors' => $errors,
        ], $period, $unit];
    }

    private function resolveFilters(array $filters): array
    {
        $errors = [];
        $warnings = [];
        $account = null;
        $period = null;
        $unit = null;
        if ($filters['account_id'] <= 0) {
            $errors[] = 'Silakan pilih akun terlebih dahulu untuk menampilkan buku besar.';
        } else {
            $account = $this->model()->findAccountById((int) $filters['account_id']);
            if (!$account) {
                $errors[] = 'Akun yang dipilih tidak ditemukan.';
            } elseif ((int) ($account['is_header'] ?? 0) === 1) {
                $errors[] = 'Buku besar hanya dapat ditampilkan untuk akun detail.';
            }
        }
        if ($filters['unit_id'] > 0) {
            try {
                $unit = find_business_unit((int) $filters['unit_id']);
            } catch (Throwable) {
                $unit = null;
            }
            if (!$unit) {
                $warnings[] = 'Filter unit usaha diabaikan karena data unit belum tersedia atau unit yang dipilih tidak ditemukan.';
                $filters['unit_id'] = 0;
            }
        }
        if ($filters['period_id'] > 0) {
            $period = $this->model()->findPeriodById((int) $filters['period_id']);
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
        if ($filters['date_from'] !== '' && !$this->isValidDate($filters['date_from'])) {
            $errors[] = 'Tanggal mulai tidak valid.';
        }
        if ($filters['date_to'] !== '' && !$this->isValidDate($filters['date_to'])) {
            $errors[] = 'Tanggal akhir tidak valid.';
        }
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_to'] < $filters['date_from']) {
            $errors[] = 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.';
        }
        if ($period && $filters['date_from'] !== '' && $filters['date_from'] < (string) $period['start_date']) {
            $errors[] = 'Tanggal mulai filter tidak boleh lebih kecil dari tanggal mulai periode yang dipilih.';
        }
        if ($period && $filters['date_to'] !== '' && $filters['date_to'] > (string) $period['end_date']) {
            $errors[] = 'Tanggal akhir filter tidak boleh lebih besar dari tanggal akhir periode yang dipilih.';
        }
        return [$filters, $account, $period, $unit, $errors, $warnings];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private function convertRawBalanceForAccount(float $rawBalance, string $accountType): float
    {
        return in_array($accountType, ['ASSET', 'EXPENSE'], true) ? $rawBalance : ($rawBalance * -1);
    }
}
