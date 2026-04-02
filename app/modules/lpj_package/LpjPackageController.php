<?php

declare(strict_types=1);

final class LpjPackageController extends Controller
{
    private function service(): LpjPackageService
    {
        return new LpjPackageService(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->service()->build($this->requestData(), false);
            $this->view('lpj_package/views/index', $viewData);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/lpj');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman Paket LPJ belum dapat dibuka. Pastikan data laporan, periode, dan profil BUMDes sudah tersedia.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData] = $this->service()->build($this->requestData(), true);
            audit_log('lpj_package', 'print', 'Mencetak paket LPJ BUMDes.', [
                'context' => [
                    'package_type' => $viewData['packageType'],
                    'filters' => $viewData['filters'],
                ],
            ]);
            $viewData['title'] = 'Cetak Paket LPJ BUMDes';
            $this->view('lpj_package/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak Paket LPJ belum dapat dibuka. Pastikan filter laporan valid lalu coba lagi.', $e);
        }
    }

    public function pdf(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->service()->build($this->requestData(), true);
            audit_log('lpj_package', 'pdf', 'Mengekspor paket LPJ BUMDes ke PDF.', [
                'context' => [
                    'package_type' => $viewData['packageType'],
                    'filters' => $viewData['filters'],
                ],
            ]);

            $profile = app_profile();
            $unitLabel = business_unit_label($selectedUnit);
            $periodLabel = report_period_label($viewData['filters'], $selectedPeriod);
            $packageLabel = (string) $viewData['packageLabel'];
            $pdf = new ReportPdf('P');
            $pdf->setMargins(12, 12, 12, 16);
            $pdf->enablePageFooter(lpj_document_reference($profile, (array) ($viewData['signatoryInput'] ?? [])), 'Paket LPJ BUMDes');

            $this->renderCoverPage($pdf, $viewData, $profile, $periodLabel);
            $this->renderCoverLetterPage($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderApprovalPage($pdf, $viewData, $profile);
            $this->renderOutlinePage($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderSummarySection($pdf, $viewData, $profile, $packageLabel, $periodLabel, $unitLabel);
            $this->renderProfitLossSection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderBalanceSheetSection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderCashFlowSection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderEquitySection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderNotesSection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderNarrativeSection($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderDispositionAppendixPage($pdf, $viewData, $profile, $periodLabel, $unitLabel);
            $this->renderReceiptPage($pdf, $viewData, $profile, $periodLabel, $unitLabel);

            $filename = 'paket-lpj-' . strtolower((string) $viewData['packageType']) . '-' . date('Ymd-His') . '.pdf';
            $pdf->output($filename);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'PDF Paket LPJ belum dapat dibuat. Pastikan data laporan valid dan profil BUMDes sudah lengkap.', $e);
        }
    }

    private function renderCoverPage(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel): void
    {
        $pdf->addPage();
        $logoPath = public_path((string) ($profile['logo_path'] ?? ''));
        if ($logoPath !== '' && is_file($logoPath)) {
            $pdf->image($logoPath, 78, 26, 54);
        }

        $usableWidth = $pdf->getUsableWidth();
        $title = lpj_document_title((string) $viewData['packageType']);
        $y = 95;
        $pdf->text(12, $y, 'BADAN USAHA MILIK DESA', 'B', 12, 'C', $usableWidth);
        $pdf->text(12, $y + 7, 'BUM DESA', 'B', 12, 'C', $usableWidth);
        $pdf->text(12, $y + 18, strtoupper((string) ($profile['bumdes_name'] ?? 'BUMDes')), 'B', 18, 'C', $usableWidth);
        $pdf->text(12, $y + 30, $title, 'B', 16, 'C', $usableWidth);
        $pdf->text(12, $y + 40, 'Periode: ' . $periodLabel, '', 10.5, 'C', $usableWidth);
        $pdf->text(12, $y + 47, 'Unit Usaha: ' . (string) $viewData['selectedUnitLabel'], '', 10.5, 'C', $usableWidth);

        $documentNo = trim((string) ($viewData['signatoryInput']['document_no'] ?? ''));
        if ($documentNo !== '') {
            $pdf->text(12, $y + 54, 'Nomor Dokumen: ' . $documentNo, '', 10.2, 'C', $usableWidth);
        }

        $address = trim((string) ($profile['address'] ?? ''));
        $location = trim(report_profile_location($profile));
        $legal = trim(report_profile_legal($profile));
        $metaY = 198;
        if ($address !== '') {
            $pdf->text(20, $metaY, 'Alamat : ' . $address, '', 9.2);
            $metaY += 6;
        }
        if ($location !== '') {
            $pdf->text(20, $metaY, $location, '', 8.8);
            $metaY += 5;
        }
        if ($legal !== '') {
            $pdf->text(20, $metaY, $legal, '', 8.8);
        }
    }

    private function renderCoverLetterPage(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Sampul Pengantar Paket LPJ', $periodLabel, $unitLabel);
        $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), lpj_cover_letter_paragraph($profile, $viewData), '', 9.2, 4.8);
        $pdf->ln(18);
        $pdf->tableRow(['Ditujukan Kepada', lpj_recipient_summary((array) ($viewData['signatoryInput'] ?? []))], [52, 124], ['L', 'L'], 8.8, true);
        $pdf->tableRow(['Nomor Dokumen', lpj_document_reference($profile, (array) ($viewData['signatoryInput'] ?? []))], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Periode', report_period_label($viewData['filters'], $viewData['selectedPeriod'])], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Ringkasan Lampiran', lpj_appendix_summary((array) ($viewData['signatoryInput'] ?? []), $viewData)], [52, 124], ['L', 'L'], 8.8);
        $pdf->ln(10);
        $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), 'Mohon dokumen ini diterima sebagai satu bundel resmi. Ruang ini juga dapat digunakan sebagai halaman pengantar saat paket dicetak, dijilid, dan disampaikan ke Pemerintah Desa, Kecamatan, atau pembina lainnya.', '', 8.5, 4.4);
    }

    private function renderApprovalPage(ReportPdf $pdf, array $viewData, array $profile): void
    {
        $pdf->addPage();
        $usableWidth = $pdf->getUsableWidth();
        $pdf->text(12, 18, 'HALAMAN PENGESAHAN', 'B', 14, 'C', $usableWidth);
        $pdf->text(12, 28, lpj_document_title((string) $viewData['packageType']), '', 10.5, 'C', $usableWidth);
        $pdf->text(12, 35, 'BUM Desa ' . (string) ($profile['bumdes_name'] ?? 'BUMDes'), '', 10.5, 'C', $usableWidth);

        $pdf->tableRow(['Keterangan', 'Isi'], [52, 124], ['L', 'L'], 8.8, true);
        $pdf->tableRow(['Periode Laporan', report_period_label($viewData['filters'], $viewData['selectedPeriod'])], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Unit Usaha', (string) $viewData['selectedUnitLabel']], [52, 124], ['L', 'L'], 8.8);
        $documentNo = lpj_document_reference($profile, (array) ($viewData['signatoryInput'] ?? []));
        $pdf->tableRow(['Nomor Dokumen', $documentNo], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Tanggal Pengesahan', lpj_approval_city_date($profile, (string) ($viewData['signatoryInput']['approval_date'] ?? date('Y-m-d')))], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Dasar Pengesahan', lpj_approval_basis((array) ($viewData['signatoryInput'] ?? []), $profile)], [52, 124], ['L', 'L'], 8.8);
        $pdf->tableRow(['Referensi Rapat / BA', lpj_meeting_reference((array) ($viewData['signatoryInput'] ?? []))], [52, 124], ['L', 'L'], 8.8);
        $pdf->ln(4);
        $pdf->paragraph(12, $pdf->getCursorY(), $usableWidth, lpj_formal_statement($profile, $viewData), '', 8.7, 4.6);
        $pdf->setCursorY($pdf->getCursorY() + 26);

        $signatories = array_values($viewData['signatories']);
        $positions = [
            [18.0, 110.0, 78.0],
            [108.0, 110.0, 78.0],
            [18.0, 176.0, 78.0],
            [108.0, 176.0, 78.0],
        ];

        foreach ($positions as $index => [$x, $y, $width]) {
            $signer = $signatories[$index] ?? ['role' => '', 'position' => '', 'name' => '', 'signature_path' => ''];
            $pdf->text($x, $y, (string) ($signer['role'] ?? ''), '', 8.6, 'C', $width);
            $pdf->text($x, $y + 5, (string) ($signer['position'] ?? ''), '', 8.6, 'C', $width);
            $signaturePath = trim((string) ($signer['signature_path'] ?? ''));
            if ($signaturePath !== '' && is_file($signaturePath)) {
                $pdf->image($signaturePath, $x + 21, $y + 9, 36);
            }
            $pdf->line($x + 5, $y + 36, $x + $width - 5, $y + 36);
            $pdf->text($x, $y + 40, (string) ($signer['name'] ?? '-'), 'B', 8.6, 'C', $width);
        }
    }


    private function renderOutlinePage(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Daftar Isi Paket LPJ', $periodLabel, $unitLabel);
        $pdf->tableRow(['No', 'Bagian Dokumen', 'Keterangan'], [14, 72, 90], ['C', 'L', 'L'], 8.5, true);
        foreach (lpj_section_outline($viewData) as $index => $section) {
            $pdf->tableRow([(string) ($index + 1), (string) ($section['title'] ?? ''), (string) ($section['note'] ?? '')], [14, 72, 90], ['C', 'L', 'L'], 8.4);
        }
        $pdf->ln(4);
        $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), 'Susunan paket ini dirancang agar lebih formal dan mudah diverifikasi. Saat dokumen akan dibahas atau diserahkan secara resmi, lampiran tambahan dapat ditambahkan tanpa mengubah urutan inti paket LPJ.', '', 8.3, 4.4);
    }

    private function renderSummarySection(ReportPdf $pdf, array $viewData, array $profile, string $packageLabel, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, $packageLabel . ' - Ringkasan Eksekutif', $periodLabel, $unitLabel);
        $pdf->tableRow(['Komponen', 'Nilai'], [120, 56], ['L', 'R'], 8.6, true);
        foreach (lpj_summary_cards($viewData['summary']) as $card) {
            $pdf->tableRow([(string) $card['label'], (string) $card['value']], [120, 56], ['L', 'R'], 8.5);
        }
        $pdf->ln(4);
        foreach (['executive_summary', 'business_overview', 'activities_summary'] as $key) {
            $height = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), (string) ($viewData['narratives'][$key] ?? ''), '', 8.4, 4.4);
            $pdf->ln($height + 2.5);
        }
        if ($viewData['issues'] !== []) {
            $pdf->text(12, $pdf->getCursorY(), 'Sorotan Perhatian', 'B', 9.2);
            $pdf->ln(6);
            foreach ($viewData['issues'] as $issue) {
                $height = $pdf->paragraph(16, $pdf->getCursorY(), $pdf->getUsableWidth() - 4, '- ' . (string) $issue, '', 8.2, 4.1);
                $pdf->ln($height + 1.2);
            }
        }
    }

    private function renderProfitLossSection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Laporan Laba Rugi', $periodLabel, $unitLabel);
        $widths = [24, 98, 54];
        $pdf->tableRow(['Kode', 'Nama Akun', 'Nilai'], $widths, ['L', 'L', 'R'], 8.5, true);
        $pdf->tableRow(['', 'Pendapatan', ''], $widths, ['L', 'L', 'R'], 8.5, true);
        if ($viewData['profitLoss']['revenue_rows'] === []) {
            $pdf->tableRow(['-', 'Tidak ada akun pendapatan pada periode ini.', '-'], $widths, ['C', 'L', 'C'], 8.4);
        } else {
            foreach ($viewData['profitLoss']['revenue_rows'] as $row) {
                $pdf->tableRow([(string) $row['account_code'], (string) $row['account_name'], ledger_currency((float) $row['amount'])], $widths, ['L', 'L', 'R'], 8.4);
            }
        }
        $pdf->tableRow(['', 'Total Pendapatan', ledger_currency((float) $viewData['profitLoss']['total_revenue'])], $widths, ['L', 'R', 'R'], 8.5, true);
        $pdf->tableRow(['', 'Beban', ''], $widths, ['L', 'L', 'R'], 8.5, true);
        if ($viewData['profitLoss']['expense_rows'] === []) {
            $pdf->tableRow(['-', 'Tidak ada akun beban pada periode ini.', '-'], $widths, ['C', 'L', 'C'], 8.4);
        } else {
            foreach ($viewData['profitLoss']['expense_rows'] as $row) {
                $pdf->tableRow([(string) $row['account_code'], (string) $row['account_name'], ledger_currency((float) $row['amount'])], $widths, ['L', 'L', 'R'], 8.4);
            }
        }
        $pdf->tableRow(['', 'Total Beban', ledger_currency((float) $viewData['profitLoss']['total_expense'])], $widths, ['L', 'R', 'R'], 8.5, true);
        $pdf->tableRow(['', profit_loss_result_label((float) $viewData['profitLoss']['net_income']), ledger_currency((float) $viewData['profitLoss']['net_income'])], $widths, ['L', 'R', 'R'], 8.5, true);
    }

    private function renderBalanceSheetSection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Neraca', $periodLabel, $unitLabel);
        $comparisonEnabled = (bool) ($viewData['balanceSheet']['comparison_enabled'] ?? false);
        $widths = $comparisonEnabled ? [20, 78, 39, 39] : [24, 98, 54];
        $headers = $comparisonEnabled ? ['Kode', 'Nama Akun', 'Saldo Akhir', 'Pembanding'] : ['Kode', 'Nama Akun', 'Saldo Akhir'];
        $aligns = $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'];
        $pdf->tableRow($headers, $widths, $aligns, 8.4, true);
        $this->renderBalanceRows($pdf, 'Aset', $viewData['balanceSheet']['asset_rows'], (float) $viewData['balanceSheet']['total_assets'], $comparisonEnabled, (float) ($viewData['balanceSheet']['comparison_total_assets'] ?? 0), $widths);
        $this->renderBalanceRows($pdf, 'Liabilitas', $viewData['balanceSheet']['liability_rows'], (float) $viewData['balanceSheet']['total_liabilities'], $comparisonEnabled, (float) ($viewData['balanceSheet']['comparison_total_liabilities'] ?? 0), $widths);
        $this->renderBalanceRows($pdf, 'Ekuitas', $viewData['balanceSheet']['equity_rows'], (float) $viewData['balanceSheet']['total_equity'], $comparisonEnabled, (float) ($viewData['balanceSheet']['comparison_total_equity'] ?? 0), $widths, true, (float) ($viewData['balanceSheet']['current_earnings'] ?? 0), (float) ($viewData['balanceSheet']['comparison_current_earnings'] ?? 0));
        $totalCells = ['', 'Total Liabilitas + Ekuitas', ledger_currency((float) $viewData['balanceSheet']['total_liabilities_equity'])];
        if ($comparisonEnabled) {
            $totalCells[] = ledger_currency((float) ($viewData['balanceSheet']['comparison_total_liabilities_equity'] ?? 0));
        }
        $pdf->tableRow($totalCells, $widths, $comparisonEnabled ? ['L', 'R', 'R', 'R'] : ['L', 'R', 'R'], 8.5, true);
    }

    private function renderBalanceRows(ReportPdf $pdf, string $label, array $rows, float $total, bool $comparisonEnabled, float $comparisonTotal, array $widths, bool $appendEarnings = false, float $currentEarnings = 0.0, float $comparisonEarnings = 0.0): void
    {
        $sectionRow = $comparisonEnabled ? ['', $label, '', ''] : ['', $label, ''];
        $pdf->tableRow($sectionRow, $widths, $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'], 8.5, true);
        if ($rows === []) {
            $emptyRow = $comparisonEnabled ? ['-', 'Tidak ada data untuk bagian ini.', '-', '-'] : ['-', 'Tidak ada data untuk bagian ini.', '-'];
            $pdf->tableRow($emptyRow, $widths, $comparisonEnabled ? ['C', 'L', 'C', 'C'] : ['C', 'L', 'C'], 8.4);
        } else {
            foreach ($rows as $row) {
                $cells = [(string) ($row['account_code'] ?? ''), (string) ($row['account_name'] ?? ''), ledger_currency((float) ($row['amount'] ?? 0))];
                if ($comparisonEnabled) {
                    $cells[] = ledger_currency((float) ($row['comparison_amount'] ?? 0));
                }
                $pdf->tableRow($cells, $widths, $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'], 8.3);
            }
        }
        if ($appendEarnings && (abs($currentEarnings) > 0.004 || abs($comparisonEarnings) > 0.004)) {
            $cells = ['', 'Laba / Rugi Berjalan', ledger_currency($currentEarnings)];
            if ($comparisonEnabled) {
                $cells[] = ledger_currency($comparisonEarnings);
            }
            $pdf->tableRow($cells, $widths, $comparisonEnabled ? ['L', 'L', 'R', 'R'] : ['L', 'L', 'R'], 8.3);
        }
        $totalCells = ['', 'Total ' . $label, ledger_currency($total)];
        if ($comparisonEnabled) {
            $totalCells[] = ledger_currency($comparisonTotal);
        }
        $pdf->tableRow($totalCells, $widths, $comparisonEnabled ? ['L', 'R', 'R', 'R'] : ['L', 'R', 'R'], 8.5, true);
    }

    private function renderCashFlowSection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Arus Kas', $periodLabel, $unitLabel);
        $pdf->tableRow(['Komponen', 'Nilai'], [120, 56], ['L', 'R'], 8.5, true);
        $rows = [
            ['Kas Awal', (float) $viewData['cashFlow']['opening_cash']],
            ['Arus Kas Bersih Operasional', (float) $viewData['cashFlow']['total_operating']],
            ['Arus Kas Bersih Investasi', (float) $viewData['cashFlow']['total_investing']],
            ['Arus Kas Bersih Pendanaan', (float) $viewData['cashFlow']['total_financing']],
            ['Kenaikan / Penurunan Kas Bersih', (float) $viewData['cashFlow']['net_cash_change']],
            ['Kas Akhir', (float) $viewData['cashFlow']['closing_cash']],
        ];
        foreach ($rows as $row) {
            $pdf->tableRow([(string) $row[0], ledger_currency((float) $row[1])], [120, 56], ['L', 'R'], 8.4);
        }
        if ($viewData['cashFlow']['warnings'] !== []) {
            $pdf->ln(4);
            $pdf->text(12, $pdf->getCursorY(), 'Catatan Klasifikasi Arus Kas', 'B', 9.1);
            $pdf->ln(6);
            foreach ($viewData['cashFlow']['warnings'] as $warning) {
                $height = $pdf->paragraph(16, $pdf->getCursorY(), $pdf->getUsableWidth() - 4, '- ' . (string) $warning, '', 8.2, 4.1);
                $pdf->ln($height + 1);
            }
        }
    }

    private function renderEquitySection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Perubahan Ekuitas', $periodLabel, $unitLabel);
        $widths = [22, 74, 26, 26, 28];
        $pdf->tableRow(['Kode', 'Nama Akun', 'Saldo Awal', 'Mutasi', 'Saldo Akhir'], $widths, ['L', 'L', 'R', 'R', 'R'], 8.4, true);
        if ($viewData['equityChanges']['rows'] === []) {
            $pdf->tableRow(['-', 'Tidak ada data ekuitas untuk periode ini.', '-', '-', '-'], $widths, ['C', 'L', 'C', 'C', 'C'], 8.3);
        } else {
            foreach ($viewData['equityChanges']['rows'] as $row) {
                $pdf->tableRow([(string) $row['account_code'], (string) $row['account_name'], ledger_currency((float) $row['opening_amount']), ledger_currency((float) $row['movement_amount']), ledger_currency((float) $row['closing_amount'])], $widths, ['L', 'L', 'R', 'R', 'R'], 8.3);
            }
        }
        $pdf->tableRow(['', 'Total Ekuitas Langsung', ledger_currency((float) $viewData['equityChanges']['total_opening_equity']), ledger_currency((float) $viewData['equityChanges']['total_movement_equity']), ledger_currency((float) $viewData['equityChanges']['total_closing_equity'])], $widths, ['L', 'R', 'R', 'R', 'R'], 8.4, true);
        $pdf->tableRow(['', 'Laba / Rugi Berjalan', '', '', ledger_currency((float) $viewData['equityChanges']['net_income'])], $widths, ['L', 'R', 'R', 'R', 'R'], 8.4);
        $pdf->tableRow(['', 'Total Ekuitas Akhir', '', '', ledger_currency((float) $viewData['equityChanges']['final_equity_total'])], $widths, ['L', 'R', 'R', 'R', 'R'], 8.4, true);
    }

    private function renderNotesSection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Catatan atas Laporan Keuangan', $periodLabel, $unitLabel);
        foreach ($viewData['financialNotes'] as $section) {
            if ($pdf->willOverflow(36)) {
                report_pdf_init($pdf, $profile, 'Lampiran LPJ - Catatan atas Laporan Keuangan', $periodLabel, $unitLabel);
            }
            $pdf->text(12, $pdf->getCursorY(), (string) ($section['title'] ?? ''), 'B', 9.4);
            $pdf->ln(5);
            foreach ((array) ($section['paragraphs'] ?? []) as $paragraph) {
                $height = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), (string) $paragraph, '', 8.1, 4.0);
                $pdf->ln($height + 1.2);
            }
            $rows = lpj_visible_note_rows((array) ($section['rows'] ?? []), 4);
            if ($rows !== []) {
                $pdf->tableRow(['Kode', 'Akun', 'Nilai'], [24, 100, 52], ['L', 'L', 'R'], 8.1, true);
                foreach ($rows as $row) {
                    $pdf->tableRow([(string) ($row['account_code'] ?? '-'), (string) ($row['account_name'] ?? '-'), financial_notes_currency((float) ($row['amount'] ?? 0))], [24, 100, 52], ['L', 'L', 'R'], 8.0);
                }
                $pdf->ln(2);
            }
        }
    }

    private function renderNarrativeSection(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lampiran LPJ - Keadaan dan Tindak Lanjut', $periodLabel, $unitLabel);
        $sections = [
            'Keadaan dan Jalannya BUMDes' => (string) ($viewData['narratives']['business_overview'] ?? ''),
            'Kegiatan Utama Periode Laporan' => (string) ($viewData['narratives']['activities_summary'] ?? ''),
            'Masalah atau Catatan Penting' => (string) ($viewData['narratives']['problems_summary'] ?? ''),
            'Tindak Lanjut dan Rencana Perbaikan' => (string) ($viewData['narratives']['follow_up_summary'] ?? ''),
        ];
        foreach ($sections as $heading => $content) {
            $pdf->text(12, $pdf->getCursorY(), $heading, 'B', 9.3);
            $pdf->ln(5);
            $height = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), $content, '', 8.2, 4.2);
            $pdf->ln($height + 3);
        }
    }

    private function renderDispositionAppendixPage(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Lembar Disposisi dan Lampiran', $periodLabel, $unitLabel);
        $pdf->tableRow(['Item', 'Keterangan'], [56, 120], ['L', 'L'], 8.8, true);
        $pdf->tableRow(['Tujuan Penyerahan', lpj_recipient_summary((array) ($viewData['signatoryInput'] ?? []))], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Ringkasan Lampiran', lpj_appendix_summary((array) ($viewData['signatoryInput'] ?? []), $viewData)], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Referensi Rapat / BA', lpj_meeting_reference((array) ($viewData['signatoryInput'] ?? []))], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Catatan Disposisi', '........................................................................................................................................................................'], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Tindak Lanjut', '........................................................................................................................................................................'], [56, 120], ['L', 'L'], 8.8);
    }

    private function renderReceiptPage(ReportPdf $pdf, array $viewData, array $profile, string $periodLabel, string $unitLabel): void
    {
        report_pdf_init($pdf, $profile, 'Halaman Tanda Terima Dokumen', $periodLabel, $unitLabel);
        $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), 'Lembar ini digunakan sebagai bukti bahwa bundel paket LPJ telah diterima oleh pihak yang dituju. Identitas penerima dapat dilengkapi saat penyerahan fisik dokumen.', '', 8.8, 4.5);
        $pdf->ln(10);
        $pdf->tableRow(['Uraian', 'Isi'], [56, 120], ['L', 'L'], 8.8, true);
        $pdf->tableRow(['Dokumen', lpj_document_title((string) ($viewData['packageType'] ?? 'semesteran'))], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Nomor Dokumen', lpj_document_reference($profile, (array) ($viewData['signatoryInput'] ?? []))], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Diserahkan Kepada', lpj_recipient_summary((array) ($viewData['signatoryInput'] ?? []))], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Tanggal Terima', '........................................................'], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Nama Penerima', '........................................................'], [56, 120], ['L', 'L'], 8.8);
        $pdf->tableRow(['Jabatan / Instansi', '........................................................'], [56, 120], ['L', 'L'], 8.8);
        $pdf->ln(14);
        $pdf->text(18, $pdf->getCursorY(), 'Penerima,', '', 8.8);
        $pdf->text(118, $pdf->getCursorY(), 'Penyerah,', '', 8.8);
        $pdf->ln(28);
        $pdf->line(18, $pdf->getCursorY(), 78, $pdf->getCursorY());
        $pdf->line(118, $pdf->getCursorY(), 178, $pdf->getCursorY());
        $pdf->text(18, $pdf->getCursorY() + 4, 'Nama jelas & tanda tangan', '', 8.2);
        $pdf->text(118, $pdf->getCursorY() + 4, profile_director_name($profile), '', 8.2);
    }

    private function requestData(): array
    {
        return [
            'period_id' => $this->requestValue('period_id', 0),
            'fiscal_year' => $this->requestValue('fiscal_year', 0),
            'date_from' => $this->requestValue('date_from', ''),
            'date_to' => $this->requestValue('date_to', ''),
            'unit_id' => $this->requestValue('unit_id', 0),
            'package_type' => $this->requestValue('package_type', 'auto'),
            'document_no' => $this->requestValue('document_no', ''),
            'approval_date' => $this->requestValue('approval_date', date('Y-m-d')),
            'executive_summary' => $this->requestValue('executive_summary', ''),
            'business_overview' => $this->requestValue('business_overview', ''),
            'activities_summary' => $this->requestValue('activities_summary', ''),
            'problems_summary' => $this->requestValue('problems_summary', ''),
            'follow_up_summary' => $this->requestValue('follow_up_summary', ''),
            'advisor_name' => $this->requestValue('advisor_name', ''),
            'advisor_position' => $this->requestValue('advisor_position', 'Penasihat'),
            'supervisor_name' => $this->requestValue('supervisor_name', ''),
            'supervisor_position' => $this->requestValue('supervisor_position', 'Pengawas'),
            'approval_basis' => $this->requestValue('approval_basis', ''),
            'meeting_reference' => $this->requestValue('meeting_reference', ''),
            'recipient_name' => $this->requestValue('recipient_name', ''),
            'recipient_position' => $this->requestValue('recipient_position', ''),
            'recipient_institution' => $this->requestValue('recipient_institution', ''),
            'appendix_summary' => $this->requestValue('appendix_summary', ''),
        ];
    }

    private function requestValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        return $_GET[$key] ?? $default;
    }
}
