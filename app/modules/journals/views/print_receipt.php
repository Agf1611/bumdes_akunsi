<?php declare(strict_types=1);

$amount = (float) ($totalAmount ?? 0);
$isNonCash = receipt_payment_is_non_cash((string) ($header['payment_method'] ?? ''));
$showRecipient = receipt_requires_recipient($profile, (string) ($header['payment_method'] ?? ''));
$showDirector = receipt_requires_director($profile, $amount);
$cityDate = report_city_date($profile);
$treasurer = report_treasurer_signature_data($profile);
$director = report_signature_data($profile);
?>
<style>
    @page { size:A4 portrait; margin:8mm 8mm 10mm; }
    body.print-layout { background:#fff !important; color:#111 !important; }
    body.print-layout main.container { max-width:none !important; width:100% !important; padding:4mm !important; }
    .print-sheet.receipt-sheet { max-width:none; color:#111; font-size:10.5px; line-height:1.22; }
    .print-sheet.receipt-sheet .report-letterhead { margin-bottom:6px !important; }
    .print-sheet.receipt-sheet .report-logo-wrap { width:66px; min-width:66px; }
    .print-sheet.receipt-sheet .report-logo-img { max-width:60px; max-height:46px; }
    .print-sheet.receipt-sheet .report-logo-fallback { width:54px; height:42px; font-size:8px; }
    .print-sheet.receipt-sheet .report-org-top { font-size:10px; }
    .print-sheet.receipt-sheet .report-org-name { font-size:17px; margin:0; }
    .print-sheet.receipt-sheet .report-org-meta,
    .print-sheet.receipt-sheet .report-subtitle { font-size:9.1px; line-height:1.15; }
    .print-sheet.receipt-sheet .report-title { font-size:13px; }
    .receipt-frame { border:1px solid #9fb0c5; padding:6px; }
    .receipt-head { display:grid; grid-template-columns:1.2fr .8fr; gap:6px; margin-bottom:6px; }
    .receipt-box { border:1px solid #b9c5d3; }
    .receipt-box table { width:100%; border-collapse:collapse; }
    .receipt-box td { padding:3px 5px; font-size:10px; vertical-align:top; border-bottom:1px solid #dbe3ec; }
    .receipt-box tr:last-child td { border-bottom:none; }
    .receipt-box td:first-child { width:106px; font-weight:700; white-space:nowrap; }
    .receipt-amount { display:flex; flex-direction:column; height:100%; }
    .receipt-amount .label { padding:4px 6px; border-bottom:1px solid #dbe3ec; font-weight:700; }
    .receipt-amount .value { padding:8px 6px 6px; font-size:20px; font-weight:800; text-align:right; white-space:nowrap; }
    .receipt-amount .method { padding:4px 6px; border-top:1px solid #dbe3ec; font-size:10px; }
    .receipt-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:6px; margin-bottom:6px; }
    .receipt-field { border:1px solid #b9c5d3; padding:4px 5px; min-height:38px; }
    .receipt-field.full { grid-column:1/-1; }
    .receipt-label { display:block; font-size:8.2px; text-transform:uppercase; letter-spacing:.05em; color:#546273; margin-bottom:2px; }
    .receipt-value { font-size:10px; font-weight:600; color:#111; white-space:pre-wrap; word-break:break-word; }
    .receipt-value.italic { font-style:italic; font-weight:500; }
    .receipt-note { margin:4px 0 6px; font-size:9.2px; color:#333; }
    .receipt-attachments { border:1px solid #b9c5d3; padding:5px 6px; margin:6px 0; font-size:9.3px; }
    .receipt-attachments h3 { font-size:10px; margin:0 0 4px; }
    .receipt-attachments ol { margin:0; padding-left:18px; }
    .receipt-attachments li { margin-bottom:2px; }
    .receipt-summary { border:1px solid #b9c5d3; padding:6px; margin:6px 0; font-size:9.4px; }
    .receipt-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:6px; }
    .receipt-summary-item { border:1px solid #dbe3ec; padding:4px 5px; min-height:38px; }
    .receipt-summary-item .k { display:block; font-size:8.1px; text-transform:uppercase; letter-spacing:.05em; color:#546273; margin-bottom:2px; }
    .receipt-summary-item .v { font-size:10px; font-weight:700; color:#111; }
    .sign-grid { display:grid; grid-template-columns:repeat(<?= $showRecipient && $showDirector ? 3 : (($showRecipient || $showDirector) ? 2 : 1) ?>, minmax(0,1fr)); gap:10px; margin-top:12px; }
    .sign-card { text-align:center; font-size:9.6px; }
    .sign-title { font-weight:700; margin-bottom:2px; }
    .sign-role { color:#333; min-height:14px; }
    .sign-city { margin-bottom:2px; }
    .sign-pad { height:52px; display:flex; align-items:flex-end; justify-content:center; }
    .sign-pad img { max-width:110px; max-height:42px; object-fit:contain; }
    .sign-spacer { width:110px; height:42px; }
    @media print { .d-print-none { display:none !important; } }
</style>

<div class="print-sheet receipt-sheet">
    <?php render_print_header($profile, $reportTitle ?? 'Bukti Transaksi / Kwitansi', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="receipt-frame">
        <div class="receipt-head">
            <div class="receipt-box">
                <table>
                    <tr><td>No. Bukti / Jurnal</td><td>: <?= e((string) ($header['journal_no'] ?? '-')) ?></td></tr>
                    <tr><td>Tanggal</td><td>: <?= e(format_id_date((string) ($header['journal_date'] ?? ''))) ?></td></tr>
                    <tr><td>Periode</td><td>: <?= e((string) ($header['period_name'] ?? '-')) ?></td></tr>
                    <tr><td>Unit Usaha</td><td>: <?= e((string) ($selectedUnitLabel ?? 'Semua Unit')) ?></td></tr>
                    <tr><td>Referensi</td><td>: <?= e((string) (($header['reference_no'] ?? '') !== '' ? $header['reference_no'] : '-')) ?></td></tr>
                </table>
            </div>
            <div class="receipt-box receipt-amount">
                <div class="label">Total Nominal</div>
                <div class="value">Rp <?= e(number_format($amount, 2, ',', '.')) ?></div>
                <div class="method">Metode: <?= e((string) (($header['payment_method'] ?? '') !== '' ? $header['payment_method'] : '-')) ?></div>
            </div>
        </div>

        <div class="receipt-grid">
            <div class="receipt-field">
                <span class="receipt-label"><?= e((string) (($header['party_title'] ?? '') !== '' ? $header['party_title'] : 'Pihak')) ?></span>
                <div class="receipt-value"><?= e((string) (($header['party_name'] ?? '') !== '' ? $header['party_name'] : '-')) ?></div>
            </div>
            <div class="receipt-field">
                <span class="receipt-label">Metode Pembayaran</span>
                <div class="receipt-value"><?= e((string) (($header['payment_method'] ?? '') !== '' ? $header['payment_method'] : '-')) ?></div>
            </div>
            <div class="receipt-field full">
                <span class="receipt-label">Tujuan Transaksi</span>
                <div class="receipt-value"><?= e((string) (($header['purpose'] ?? '') !== '' ? $header['purpose'] : ($header['description'] ?? '-'))) ?></div>
            </div>
            <div class="receipt-field full">
                <span class="receipt-label">Terbilang</span>
                <div class="receipt-value italic"><?= e((string) (($amountInWords ?? '') !== '' ? $amountInWords : '-')) ?></div>
            </div>
            <div class="receipt-field full">
                <span class="receipt-label">Catatan</span>
                <div class="receipt-value"><?= e((string) (($header['notes'] ?? '') !== '' ? $header['notes'] : '-')) ?></div>
            </div>
        </div>

        <?php if ($isNonCash && !$showRecipient): ?>
            <div class="receipt-note"><strong>Catatan:</strong> Pembayaran dilakukan melalui transfer / non-tunai. Tanda tangan penerima tidak wajib dan bukti transfer dilampirkan terpisah.</div>
        <?php endif; ?>

        <?php if (($attachments ?? []) !== []): ?>
            <div class="receipt-attachments">
                <h3>Lampiran Bukti Transaksi</h3>
                <ol>
                    <?php foreach (($attachments ?? []) as $attachment): ?>
                        <?php $displayName = trim((string) ($attachment['attachment_title'] ?? '')) !== '' ? (string) $attachment['attachment_title'] : (string) ($attachment['original_name'] ?? '-'); ?>
                        <li>
                            <strong><?= e($displayName) ?></strong>
                            <span>(<?= e(journal_attachment_type_label($attachment)) ?>, <?= e(journal_attachment_file_size((int) ($attachment['file_size'] ?? 0))) ?>)</span>
                            <?php if (trim((string) ($attachment['attachment_notes'] ?? '')) !== ''): ?>
                                <span>- <?= e((string) $attachment['attachment_notes']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>

        <div class="sign-grid">
            <?php if ($showRecipient): ?>
                <div class="sign-card">
                    <div class="sign-title">Yang menerima</div>
                    <div class="sign-role"><?= e((string) (($header['party_title'] ?? '') !== '' ? $header['party_title'] : 'Penerima')) ?></div>
                    <div class="sign-city"><?= e($cityDate) ?></div>
                    <div class="sign-pad"><div class="sign-spacer" aria-hidden="true"></div></div>
                    <div class="sign-name"><?= e((string) (($header['party_name'] ?? '') !== '' ? $header['party_name'] : '-')) ?></div>
                </div>
            <?php endif; ?>

            <div class="sign-card">
                <div class="sign-title">Lunas dibayar</div>
                <div class="sign-role"><?= e($treasurer['position']) ?></div>
                <div class="sign-city"><?= e($cityDate) ?></div>
                <div class="sign-pad">
                    <?php if ($treasurer['signature_url'] !== ''): ?>
                        <img src="<?= e($treasurer['signature_url']) ?>" alt="Tanda Tangan Bendahara">
                    <?php else: ?>
                        <div class="sign-spacer" aria-hidden="true"></div>
                    <?php endif; ?>
                </div>
                <div class="sign-name"><?= e($treasurer['name']) ?></div>
            </div>

            <?php if ($showDirector): ?>
                <div class="sign-card">
                    <div class="sign-title">Mengetahui</div>
                    <div class="sign-role"><?= e($director['position']) ?></div>
                    <div class="sign-city"><?= e($cityDate) ?></div>
                    <div class="sign-pad">
                        <?php if ($director['signature_url'] !== ''): ?>
                            <img src="<?= e($director['signature_url']) ?>" alt="Tanda Tangan Direktur">
                        <?php else: ?>
                            <div class="sign-spacer" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                    <div class="sign-name"><?= e($director['name']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-print-none mt-3 d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Sekarang</button>
        <a href="<?= e(base_url('/journals/detail?id=' . (int) ($header['id'] ?? 0))) ?>" class="btn btn-outline-secondary">Kembali ke Detail</a>
    </div>
</div>
