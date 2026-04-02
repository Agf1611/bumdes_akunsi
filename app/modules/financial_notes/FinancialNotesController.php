<?php

declare(strict_types=1);

final class FinancialNotesController extends Controller
{
    private function model(): FinancialNotesModel
    {
        return new FinancialNotesModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('financial_notes/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman Catatan atas Laporan Keuangan belum dapat dibuka.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $viewData['profile'] = app_profile();
            $viewData['title'] = 'Cetak Catatan atas Laporan Keuangan';
            $this->view('financial_notes/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak Catatan atas Laporan Keuangan belum dapat dibuka.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $pdf = new ReportPdf('P');
            report_pdf_init($pdf, $profile, 'Catatan atas Laporan Keuangan', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel, false);

            $introHeight = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), 'Catatan atas Laporan Keuangan ini disusun sebagai pelengkap laporan utama BUMDes dan menyajikan informasi tambahan mengenai posisi keuangan, kinerja usaha, dan penjelasan kebijakan akuntansi.', '', 8.8, 4.6);
            $pdf->ln($introHeight + 3);

            foreach (array_values($viewData['notes']) as $section) {
                if ($pdf->willOverflow(18)) {
                    report_pdf_init($pdf, $profile, 'Catatan atas Laporan Keuangan', report_period_label($viewData['filters'], $selectedPeriod), $unitLabel, false);
                }

                $pdf->text(12, $pdf->getCursorY(), (string) ($section['title'] ?? ''), 'B', 10);
                $pdf->ln(5);
                foreach (($section['paragraphs'] ?? []) as $paragraph) {
                    $height = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), (string) $paragraph, '', 8.4, 4.2);
                    $pdf->ln($height + 1.5);
                }
                if (financial_notes_has_rows((array) ($section['rows'] ?? []))) {
                    $widths = [28, 104, 44];
                    $pdf->tableRow(['Kode Akun', 'Nama Akun', 'Nilai'], $widths, ['L', 'L', 'R'], 8.3, true);
                    foreach ((array) ($section['rows'] ?? []) as $row) {
                        if (abs((float) ($row['amount'] ?? 0)) <= 0.004) {
                            continue;
                        }
                        $pdf->tableRow([(string) ($row['account_code'] ?? '-'), (string) ($row['account_name'] ?? '-'), financial_notes_currency((float) ($row['amount'] ?? 0))], $widths, ['L', 'L', 'R'], 8.2, false);
                    }
                    $pdf->tableRow(['', 'Total', financial_notes_currency(financial_notes_table_total((array) ($section['rows'] ?? [])))], $widths, ['L', 'R', 'R'], 8.2, true);
                    $pdf->ln(3);
                }
                $pdf->ln(2);
            }

            report_pdf_footer_note($pdf, $profile);
            $pdf->output('catatan-atas-laporan-keuangan.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'PDF Catatan atas Laporan Keuangan belum dapat dibuat.', $e);
        }
    }

    private function buildReportData(): array
    {
        $activePeriod = current_accounting_period();
        $defaultPeriodId = $activePeriod ? (int) ($activePeriod['id'] ?? 0) : 0;

        $filters = [
            'period_id' => (int) get_query('period_id', $defaultPeriodId),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
            'unit_id' => (int) get_query('unit_id', 0),
        ];

        $selectedPeriod = null;
        $selectedUnit = null;
        $notes = $this->emptyNotes();

        if ($filters['period_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '') {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $notes = $this->compileNotes($filters);
        }

        return [[
            'title' => 'Catatan atas Laporan Keuangan',
            'filters' => $filters,
            'periods' => $this->model()->getPeriods(),
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'notes' => $notes,
            'profile' => app_profile(),
        ], $selectedPeriod, $selectedUnit];
    }

    private function resolveFilters(array $filters): array
    {
        $errors = [];
        $period = null;
        $unit = null;

        if ((int) $filters['period_id'] > 0) {
            $period = $this->model()->findPeriodById((int) $filters['period_id']);
            if (!$period) {
                $errors[] = 'Periode laporan tidak ditemukan.';
            } else {
                if ($filters['date_from'] === '') {
                    $filters['date_from'] = (string) $period['start_date'];
                }
                if ($filters['date_to'] === '') {
                    $filters['date_to'] = (string) $period['end_date'];
                }
            }
        }

        if ($filters['date_from'] === '' || $filters['date_to'] === '') {
            $errors[] = 'Rentang tanggal laporan wajib dipilih.';
        } elseif ($filters['date_to'] < $filters['date_from']) {
            $errors[] = 'Tanggal akhir laporan tidak boleh lebih kecil dari tanggal mulai.';
        }

        if ((int) $filters['unit_id'] > 0) {
            $unit = find_business_unit((int) $filters['unit_id']);
            if (!$unit) {
                $errors[] = 'Unit usaha tidak ditemukan.';
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        return [$filters, $period, $unit];
    }

    private function compileNotes(array $filters): array
    {
        $profile = app_profile();
        $dateFrom = (string) $filters['date_from'];
        $dateTo = (string) $filters['date_to'];
        $unitId = (int) $filters['unit_id'];

        $cashRows = $this->model()->getNamedAssetRows($dateTo, ['kas', 'bank'], $unitId);
        $receivableRows = $this->model()->getNamedAssetRows($dateTo, ['piutang'], $unitId);
        $inventoryRows = $this->model()->getNamedAssetRows($dateTo, ['persediaan', 'stok'], $unitId);
        $fixedAssetRows = $this->model()->getNamedAssetRows($dateTo, ['aset tetap', 'inventaris', 'peralatan', 'kendaraan', 'bangunan', 'mesin', 'tanah'], $unitId);
        $depreciationRows = $this->model()->getNamedAssetRows($dateTo, ['akumulasi', 'penyusutan'], $unitId);
        $liabilityRows = $this->model()->getRowsByType($dateTo, 'LIABILITY', $unitId);
        $equityRows = $this->model()->getRowsByType($dateTo, 'EQUITY', $unitId);
        $revenueRows = $this->model()->getProfitLossRows($dateFrom, $dateTo, 'REVENUE', $unitId);
        $expenseRows = $this->model()->getProfitLossRows($dateFrom, $dateTo, 'EXPENSE', $unitId);
        $netIncome = $this->model()->getNetIncome($dateFrom, $dateTo, $unitId);

        return [
            'general' => [
                'title' => '1. Informasi Umum BUMDes',
                'paragraphs' => array_values(array_filter([
                    'BUMDes ' . ((string) ($profile['bumdes_name'] ?? 'BUMDes')) . ' menyusun laporan keuangan untuk periode ' . format_id_long_date($dateFrom) . ' sampai dengan ' . format_id_long_date($dateTo) . '.',
                    trim((string) ($profile['address'] ?? '')) !== '' ? 'Alamat entitas: ' . trim((string) $profile['address']) . '.' : '',
                    financial_notes_profile_location($profile) !== '' ? 'Wilayah administrasi: ' . financial_notes_profile_location($profile) . '.' : '',
                    financial_notes_profile_legal($profile) !== '' ? 'Identitas legal lembaga: ' . financial_notes_profile_legal($profile) . '.' : '',
                ])),
                'rows' => [],
            ],
            'policies' => [
                'title' => '2. Dasar Penyusunan dan Kebijakan Akuntansi',
                'paragraphs' => financial_notes_policy_points(),
                'rows' => [],
            ],
            'cash' => [
                'title' => '3. Kas dan Setara Kas',
                'paragraphs' => [
                    'Kas dan setara kas merupakan saldo akun kas serta rekening bank yang digunakan untuk operasional BUMDes pada akhir periode laporan.',
                ],
                'rows' => $cashRows,
            ],
            'receivables' => [
                'title' => '4. Piutang Usaha / Piutang Lainnya',
                'paragraphs' => [
                    'Piutang disajikan berdasarkan saldo akun piutang yang tercatat sampai akhir periode. Pengelola perlu meninjau piutang yang menunggak secara berkala.',
                ],
                'rows' => $receivableRows,
            ],
            'inventory' => [
                'title' => '5. Persediaan',
                'paragraphs' => [
                    'Persediaan menunjukkan nilai barang yang masih tersedia untuk kegiatan operasional atau penjualan BUMDes pada tanggal laporan.',
                ],
                'rows' => $inventoryRows,
            ],
            'fixed_assets' => [
                'title' => '6. Aset Tetap dan Akumulasi Penyusutan',
                'paragraphs' => [
                    'Aset tetap disajikan berdasarkan akun aset tetap yang tercatat pada Chart of Accounts. Akumulasi penyusutan atau akun kontra aset ditampilkan terpisah sebagai pengurang nilai buku.',
                    'Total aset tetap bruto sebesar ' . financial_notes_currency(financial_notes_table_total($fixedAssetRows)) . ' dan akumulasi penyusutan sebesar ' . financial_notes_currency(financial_notes_table_total($depreciationRows)) . '.',
                ],
                'rows' => array_merge($fixedAssetRows, $depreciationRows),
            ],
            'liabilities_equity' => [
                'title' => '7. Liabilitas dan Ekuitas',
                'paragraphs' => [
                    'Liabilitas mencerminkan kewajiban BUMDes kepada pihak lain, sedangkan ekuitas merupakan hak residual atas aset setelah dikurangi liabilitas.',
                    'Total liabilitas pada akhir periode adalah ' . financial_notes_currency(financial_notes_table_total($liabilityRows)) . ' dan total ekuitas tercatat sebesar ' . financial_notes_currency(financial_notes_table_total($equityRows) + $netIncome) . ' termasuk hasil usaha berjalan.',
                ],
                'rows' => array_merge($liabilityRows, $equityRows),
            ],
            'performance' => [
                'title' => '8. Kinerja Pendapatan dan Beban',
                'paragraphs' => [
                    'Selama periode berjalan, BUMDes membukukan total pendapatan sebesar ' . financial_notes_currency(financial_notes_table_total($revenueRows)) . ' dan total beban sebesar ' . financial_notes_currency(financial_notes_table_total($expenseRows)) . '.',
                    'Dengan demikian, BUMDes menghasilkan ' . financial_notes_net_result_label($netIncome) . ' sebesar ' . financial_notes_currency(abs($netIncome)) . '.',
                ],
                'rows' => array_merge($revenueRows, $expenseRows),
            ],
        ];
    }

    private function emptyNotes(): array
    {
        return [
            'general' => ['title' => '1. Informasi Umum BUMDes', 'paragraphs' => [], 'rows' => []],
            'policies' => ['title' => '2. Dasar Penyusunan dan Kebijakan Akuntansi', 'paragraphs' => [], 'rows' => []],
            'cash' => ['title' => '3. Kas dan Setara Kas', 'paragraphs' => [], 'rows' => []],
            'receivables' => ['title' => '4. Piutang Usaha / Piutang Lainnya', 'paragraphs' => [], 'rows' => []],
            'inventory' => ['title' => '5. Persediaan', 'paragraphs' => [], 'rows' => []],
            'fixed_assets' => ['title' => '6. Aset Tetap dan Akumulasi Penyusutan', 'paragraphs' => [], 'rows' => []],
            'liabilities_equity' => ['title' => '7. Liabilitas dan Ekuitas', 'paragraphs' => [], 'rows' => []],
            'performance' => ['title' => '8. Kinerja Pendapatan dan Beban', 'paragraphs' => [], 'rows' => []],
        ];
    }
}
