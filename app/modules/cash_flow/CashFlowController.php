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
            report_pdf_note($pdf, 'Versi awal memakai pendekatan direct method sederhana dari jurnal kas/bank. Klasifikasi didasarkan pada akun lawan dan bisa kurang presisi jika struktur akun belum detail.');
            foreach ($viewData['warnings'] as $warning) {
                report_pdf_note($pdf, 'Peringatan: ' . $warning);
            }

            $widths = [18, 28, 62, 32, 22, 22, 22];
            $sections = [
                'OPERATING' => ['title' => 'Aktivitas Operasional', 'rows' => $viewData['report']['operating_rows'], 'total' => $viewData['report']['total_operating']],
                'INVESTING' => ['title' => 'Aktivitas Investasi', 'rows' => $viewData['report']['investing_rows'], 'total' => $viewData['report']['total_investing']],
                'FINANCING' => ['title' => 'Aktivitas Pendanaan', 'rows' => $viewData['report']['financing_rows'], 'total' => $viewData['report']['total_financing']],
            ];

            foreach ($sections as $section) {
                if ($pdf->willOverflow(18)) {
                    report_pdf_init($pdf, $profile, 'Laporan Arus Kas Sederhana', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                }
                $pdf->text(12, $pdf->getCursorY(), $section['title'], 'B', 10.5);
                $pdf->ln(6);
                $pdf->tableRow(['Tanggal', 'No. Jurnal', 'Keterangan', 'Unit', 'Masuk', 'Keluar', 'Bersih'], $widths, ['L', 'L', 'L', 'L', 'R', 'R', 'R'], 8.5, true);

                $sectionHeaderPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $section, $widths): void {
                    report_pdf_init($pdfObj, $profile, 'Laporan Arus Kas Sederhana', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                    $pdfObj->text(12, $pdfObj->getCursorY(), $section['title'], 'B', 10.5);
                    $pdfObj->ln(6);
                    $pdfObj->tableRow(['Tanggal', 'No. Jurnal', 'Keterangan', 'Unit', 'Masuk', 'Keluar', 'Bersih'], $widths, ['L', 'L', 'L', 'L', 'R', 'R', 'R'], 8.5, true);
                };

                if ($section['rows'] === []) {
                    $pdf->tableRow(['-', '-', 'Tidak ada mutasi untuk bagian ini.', '-', '-', '-', '-'], $widths, ['C', 'C', 'L', 'C', 'C', 'C', 'C'], 8.5, false, $sectionHeaderPrinter);
                } else {
                    foreach ($section['rows'] as $row) {
                        $description = (string) $row['description'];
                        if ((string) $row['classification_note'] !== '') {
                            $description .= ' [' . (string) $row['classification_note'] . ']';
                        }
                        $pdf->tableRow([
                            (string) $row['journal_date'],
                            (string) $row['journal_no'],
                            $description,
                            (string) $row['unit_label'],
                            ledger_currency((float) $row['cash_in']),
                            ledger_currency((float) $row['cash_out']),
                            ledger_currency(abs((float) $row['net_amount'])),
                        ], $widths, ['L', 'L', 'L', 'L', 'R', 'R', 'R'], 8.5, false, $sectionHeaderPrinter);
                    }
                }

                $pdf->tableRow(['', '', 'Total ' . $section['title'], '', '', '', ledger_currency(abs((float) $section['total']))], $widths, ['L', 'R', 'R', 'L', 'R', 'R', 'R'], 8.5, true, $sectionHeaderPrinter);
                $pdf->ln(4);
            }

            if ($pdf->willOverflow(30)) {
                report_pdf_init($pdf, $profile, 'Laporan Arus Kas Sederhana', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
            }
            $pdf->tableRow(['Komponen', 'Nilai'], [170, 80], ['L', 'R'], 8.5, true);
            $pdf->tableRow(['Kas Awal', ledger_currency((float) $viewData['report']['opening_cash'])], [170, 80], ['L', 'R'], 8.5);
            $pdf->tableRow(['Arus Kas Bersih Operasional', ledger_currency((float) $viewData['report']['total_operating'])], [170, 80], ['L', 'R'], 8.5);
            $pdf->tableRow(['Arus Kas Bersih Investasi', ledger_currency((float) $viewData['report']['total_investing'])], [170, 80], ['L', 'R'], 8.5);
            $pdf->tableRow(['Arus Kas Bersih Pendanaan', ledger_currency((float) $viewData['report']['total_financing'])], [170, 80], ['L', 'R'], 8.5);
            $pdf->tableRow(['Kenaikan / Penurunan Kas Bersih', ledger_currency((float) $viewData['report']['net_cash_change'])], [170, 80], ['L', 'R'], 8.5, true);
            $pdf->tableRow(['Kas Akhir', ledger_currency((float) $viewData['report']['closing_cash'])], [170, 80], ['L', 'R'], 8.5, true);
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-arus-kas.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF arus kas belum dapat dibuat. Pastikan data laporan valid lalu coba lagi.', $e);
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
        $cashAccounts = [];
        $warnings = [];
        $report = [
            'operating_rows' => [],
            'investing_rows' => [],
            'financing_rows' => [],
            'opening_cash' => 0.0,
            'total_operating' => 0.0,
            'total_investing' => 0.0,
            'total_financing' => 0.0,
            'net_cash_change' => 0.0,
            'closing_cash' => 0.0,
            'row_count' => 0,
        ];

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $cashAccounts = $this->model()->getDetectedCashAccounts();

            if ($cashAccounts === []) {
                $warnings[] = 'Belum ada akun kas/bank yang terdeteksi dari COA. Pastikan nama akun kas atau bank sudah konsisten.';
            } else {
                $cashAccountIds = array_map(static fn (array $row): int => (int) $row['id'], $cashAccounts);
                $report['opening_cash'] = $this->model()->getOpeningCashBalance($cashAccountIds, $filters['date_from'], (int) $filters['unit_id']);
                $journalRows = $this->model()->getJournalRows($cashAccountIds, $filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);

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
                        'counterpart_accounts' => (string) ($row['counterpart_accounts'] ?? ''),
                        'classification_note' => $classificationNote,
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
            }

            $report['net_cash_change'] = $report['total_operating'] + $report['total_investing'] + $report['total_financing'];
            $report['closing_cash'] = $report['opening_cash'] + $report['net_cash_change'];
            $report['row_count'] = count($report['operating_rows']) + count($report['investing_rows']) + count($report['financing_rows']);
        }

        return [[
            'title' => 'Laporan Arus Kas Sederhana',
            'filters' => $filters,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'cashAccounts' => $cashAccounts,
            'warnings' => array_values(array_unique($warnings)),
            'report' => $report,
            'assumptions' => cash_flow_assumptions(),
            'limitations' => cash_flow_limitations(),
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
            $this->redirect('/cash-flow');
        }

        return [$filters, $period, $unit];
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
