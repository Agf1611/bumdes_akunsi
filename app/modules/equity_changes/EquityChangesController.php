<?php

declare(strict_types=1);

final class EquityChangesController extends Controller
{
    private function model(): EquityChangesModel
    {
        return new EquityChangesModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('equity_changes/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman laporan perubahan ekuitas belum dapat dibuka. Pastikan data jurnal, COA, dan periode akuntansi sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $viewData['title'] = 'Cetak Laporan Perubahan Ekuitas';
            $viewData['profile'] = app_profile();
            $this->view('equity_changes/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak perubahan ekuitas belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $pdf = new ReportPdf('P');
            report_pdf_init($pdf, $profile, 'Laporan Perubahan Ekuitas', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
            report_pdf_note($pdf, 'Versi awal laporan ini menyajikan pergerakan akun ekuitas langsung dan laba/rugi berjalan sebagai komponen terpisah.');

            $widths = [24, 60, 28, 28, 28];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $viewData, $selectedPeriod, $unitLabel, $widths): void {
                report_pdf_init($pdfObj, $profile, 'Laporan Perubahan Ekuitas', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel);
                $pdfObj->tableRow(['Kode', 'Nama Akun', 'Saldo Awal', 'Mutasi', 'Saldo Akhir'], $widths, ['L', 'L', 'R', 'R', 'R'], 8.5, true);
            };
            $pdf->tableRow(['Kode', 'Nama Akun', 'Saldo Awal', 'Mutasi', 'Saldo Akhir'], $widths, ['L', 'L', 'R', 'R', 'R'], 8.5, true);

            if ($viewData['report']['rows'] === []) {
                $pdf->tableRow(['-', 'Tidak ada data ekuitas untuk filter yang dipilih.', '-', '-', '-'], $widths, ['C', 'L', 'C', 'C', 'C'], 8.5);
            } else {
                foreach ($viewData['report']['rows'] as $row) {
                    $pdf->tableRow([
                        (string) $row['account_code'],
                        (string) $row['account_name'],
                        ledger_currency((float) $row['opening_amount']),
                        ledger_currency((float) $row['movement_amount']),
                        ledger_currency((float) $row['closing_amount']),
                    ], $widths, ['L', 'L', 'R', 'R', 'R'], 8.5, false, $headerPrinter);
                }
            }

            $pdf->tableRow(['', 'Total Ekuitas Langsung', ledger_currency((float) $viewData['report']['total_opening_equity']), ledger_currency((float) $viewData['report']['total_movement_equity']), ledger_currency((float) $viewData['report']['total_closing_equity'])], $widths, ['L', 'R', 'R', 'R', 'R'], 8.5, true, $headerPrinter);
            $pdf->ln(4);
            $pdf->tableRow(['Komponen', 'Nilai'], [110, 62], ['L', 'R'], 8.5, true);
            $pdf->tableRow(['Laba / Rugi Berjalan', ledger_currency((float) $viewData['report']['net_income'])], [110, 62], ['L', 'R'], 8.5);
            $pdf->tableRow(['Total Ekuitas Akhir Setelah Laba / Rugi Berjalan', ledger_currency((float) $viewData['report']['final_equity_total'])], [110, 62], ['L', 'R'], 8.5, true);
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-perubahan-ekuitas.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF perubahan ekuitas belum dapat dibuat. Pastikan data laporan valid lalu coba lagi.', $e);
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
        $report = [
            'rows' => [],
            'total_opening_equity' => 0.0,
            'total_movement_equity' => 0.0,
            'total_closing_equity' => 0.0,
            'net_income' => 0.0,
            'final_equity_total' => 0.0,
            'row_count' => 0,
        ];

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $rawRows = $this->model()->getEquityRows($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);

            foreach ($rawRows as $row) {
                $openingAmount = equity_change_amount((float) $row['opening_debit'], (float) $row['opening_credit']);
                $movementAmount = equity_change_amount((float) $row['movement_debit'], (float) $row['movement_credit']);
                $closingAmount = equity_change_amount((float) $row['closing_debit'], (float) $row['closing_credit']);

                $report['rows'][] = [
                    'account_code' => (string) $row['account_code'],
                    'account_name' => (string) $row['account_name'],
                    'opening_amount' => $openingAmount,
                    'movement_amount' => $movementAmount,
                    'closing_amount' => $closingAmount,
                ];
                $report['total_opening_equity'] += $openingAmount;
                $report['total_movement_equity'] += $movementAmount;
                $report['total_closing_equity'] += $closingAmount;
            }

            $report['net_income'] = $this->model()->getNetIncome($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $report['final_equity_total'] = $report['total_closing_equity'] + $report['net_income'];
            $report['row_count'] = count($report['rows']);
        }

        return [[
            'title' => 'Laporan Perubahan Ekuitas',
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
            $this->redirect('/equity-changes');
        }

        return [$filters, $period, $unit];
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
