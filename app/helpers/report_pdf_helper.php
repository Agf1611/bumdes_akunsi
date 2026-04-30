<?php

declare(strict_types=1);

function report_pdf_init(ReportPdf $pdf, array $profile, string $title, string $periodLabel, string $unitLabel = 'Semua Unit', bool $asOf = false): void
{
    $pdf->setMargins(12, 12, 12, 12);
    $footerLeft = trim((string) ($profile['bumdes_name'] ?? 'BUMDes')) ?: 'BUMDes';
    $footerCenter = trim($title) !== '' ? $title : 'Laporan';
    $pdf->enablePageFooter($footerLeft, $footerCenter);
    $pdf->addPage();

    $logoPath = public_path((string) ($profile['logo_path'] ?? ''));
    if ($logoPath !== '' && is_file($logoPath)) {
        $pdf->image($logoPath, 12, 10, 24);
    }

    $usableWidth = $pdf->getUsableWidth();
    $textX = 40;
    $textWidth = $usableWidth - 28;

    $pdf->text($textX, 12, 'BADAN USAHA MILIK DESA', 'B', 10, 'C', $textWidth);
    $pdf->text($textX, 17, 'BUM DESA', 'B', 10, 'C', $textWidth);
    $pdf->text($textX, 23, strtoupper((string) ($profile['bumdes_name'] ?? 'BUMDes')), 'B', 13, 'C', $textWidth);
    $currentY = 29.0;
    $pdf->text($textX, $currentY, 'Alamat: ' . ((string) ($profile['address'] ?? '-') ?: '-'), '', 8.5, 'C', $textWidth);
    $currentY += 5;

    $locationMeta = report_profile_location($profile);
    if ($locationMeta !== '') {
        $pdf->text($textX, $currentY, $locationMeta, '', 8.2, 'C', $textWidth);
        $currentY += 5;
    }

    $pdf->text($textX, $currentY, 'Telepon: ' . ((string) ($profile['phone'] ?? '-') ?: '-') . ' | Email: ' . ((string) ($profile['email'] ?? '-') ?: '-'), '', 8.5, 'C', $textWidth);
    $currentY += 5;

    $legalMeta = report_profile_legal($profile);
    if ($legalMeta !== '') {
        $pdf->text($textX, $currentY, $legalMeta, '', 8.2, 'C', $textWidth);
        $currentY += 5;
    }

    $dividerY = $currentY + 1;
    $pdf->line(12, $dividerY, 12 + $usableWidth, $dividerY);
    $pdf->line(12, $dividerY + 0.8, 12 + $usableWidth, $dividerY + 0.8);

    $titleY = $dividerY + 7;
    $pdf->text(12, $titleY, $title, 'B', 12);
    $pdf->text(12, $titleY + 6, 'Periode: ' . $periodLabel, '', 9);
    $pdf->text(12, $titleY + 11, 'Unit Usaha: ' . $unitLabel, '', 9);
    $pdf->text(12, $titleY + 16, report_kepmendes_136_reference(), '', 7.5);
    if ($asOf) {
        $pdf->text(12, $titleY + 21, 'Laporan posisi per tanggal akhir filter.', '', 8.5);
        $pdf->setCursorY($titleY + 27);
        return;
    }
    $pdf->setCursorY($titleY + 24);
}

function report_pdf_note(ReportPdf $pdf, string $text): void
{
    $height = $pdf->paragraph(12, $pdf->getCursorY(), $pdf->getUsableWidth(), $text, '', 8.5, 4.2);
    $pdf->ln($height + 2);
}

function report_pdf_footer_note(ReportPdf $pdf, array $profile): void
{
    $pdf->ln(4);
    if ($pdf->willOverflow(42)) {
        $pdf->addPage();
    }

    $signatureCity = trim((string) ($profile['signature_city'] ?? ''));
    $dateLabel = format_id_long_date(date('Y-m-d'));
    $cityDate = $signatureCity !== '' ? $signatureCity . ', ' . $dateLabel : $dateLabel;
    $position = trim((string) ($profile['director_position'] ?? 'Direktur')) ?: 'Direktur';
    $name = profile_director_name($profile);

    $x = 150;
    if ($pdf->getUsableWidth() > 250) {
        $x = 220;
    }

    $pdf->text($x, $pdf->getCursorY(), $cityDate, '', 8.5);
    $pdf->text($x, $pdf->getCursorY() + 5, $position, '', 8.5);

    $signaturePath = public_path((string) ($profile['signature_path'] ?? ''));
    if ($signaturePath !== '' && is_file($signaturePath)) {
        $pdf->image($signaturePath, $x, $pdf->getCursorY() + 8, 28);
    }

    $baseY = $pdf->getCursorY() + 24;
    $pdf->text($x, $baseY, $name, 'B', 9);
    $pdf->line($x, $baseY + 4.5, $x + 40, $baseY + 4.5);
    $pdf->text($x, $baseY + 9, 'Ruang stempel', '', 8);
    $pdf->ln(38);
}
