<?php declare(strict_types=1);

$cityDate = report_city_date($profile);
$treasurer = report_treasurer_signature_data($profile);
$director = report_signature_data($profile);
$showDirector = trim($director['name']) !== '' && $director['name'] !== '-';
?>
<style>
    @page { size:A4 portrait; margin:8mm 8mm 10mm; }
    body.print-layout { background:#fff !important; color:#111 !important; }
    body.print-layout main.container { max-width:none !important; width:100% !important; padding:4mm !important; }
    .print-sheet.journal-sheet { max-width:none; color:#111; font-size:10.5px; line-height:1.24; }
    .print-sheet.journal-sheet .report-letterhead { margin-bottom:6px !important; }
    .print-sheet.journal-sheet .report-logo-wrap { width:66px; min-width:66px; }
    .print-sheet.journal-sheet .report-logo-img { max-width:60px; max-height:46px; }
    .print-sheet.journal-sheet .report-logo-fallback { width:54px; height:42px; font-size:8px; }
    .print-sheet.journal-sheet .report-org-top { font-size:10px; }
    .print-sheet.journal-sheet .report-org-name { font-size:17px; margin:0; }
    .print-sheet.journal-sheet .report-org-meta,
    .print-sheet.journal-sheet .report-subtitle { font-size:9.1px; line-height:1.15; }
    .print-sheet.journal-sheet .report-title { font-size:13px; }
    .meta-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:5px; margin-bottom:6px; }
    .meta-box { border:1px solid #b9c5d3; padding:4px 5px; min-height:38px; }
    .meta-label { display:block; font-size:8.2px; text-transform:uppercase; letter-spacing:.05em; color:#546273; margin-bottom:2px; }
    .meta-value { font-size:10px; font-weight:600; color:#111; }
    .note-box { border:1px solid #b9c5d3; padding:5px 6px; margin-bottom:6px; font-size:10px; }
    .attachment-box { border:1px solid #b9c5d3; padding:5px 6px; margin-top:6px; font-size:9.4px; }
    .attachment-box h3 { font-size:10px; margin:0 0 4px; }
    .attachment-box ol { margin:0; padding-left:18px; }
    .attachment-box li { margin-bottom:2px; }
    .journal-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .journal-table th,
    .journal-table td { border:1px solid #aeb8c7; padding:3px 4px; vertical-align:top; word-break:normal; overflow-wrap:anywhere; font-size:9.3px; }
    .journal-table thead th { background:#eef2f7 !important; color:#111 !important; text-align:center; font-weight:700; }
    .journal-table tfoot th { background:#f8fafc !important; color:#111 !important; font-weight:700; }
    .journal-table .col-no { width:5%; }
    .journal-table .col-account { width:48%; }
    .journal-table .col-desc { width:13%; }
    .journal-table .col-money { width:17%; }
    .num { text-align:right; white-space:nowrap; }
    .sign-grid { display:grid; grid-template-columns:<?= $showDirector ? '1fr 1fr' : '1fr' ?>; gap:16px; margin-top:12px; }
    .sign-card { text-align:center; font-size:9.6px; }
    .sign-title { font-weight:700; margin-bottom:2px; }
    .sign-role { color:#333; min-height:14px; }
    .sign-city { margin-bottom:2px; }
    .sign-pad { height:52px; display:flex; align-items:flex-end; justify-content:center; }
    .sign-pad img { max-width:110px; max-height:42px; object-fit:contain; }
    .sign-spacer { width:110px; height:42px; }
    @media print { .d-print-none { display:none !important; } }
</style>

<div class="print-sheet journal-sheet">
    <?php render_print_header($profile, $reportTitle ?? 'Jurnal Umum', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="meta-grid">
        <div class="meta-box"><span class="meta-label">Nomor Jurnal</span><div class="meta-value"><?= e((string) ($header['journal_no'] ?? '-')) ?></div></div>
        <div class="meta-box"><span class="meta-label">Tanggal</span><div class="meta-value"><?= e(format_id_date((string) ($header['journal_date'] ?? ''))) ?></div></div>
        <div class="meta-box"><span class="meta-label">Periode</span><div class="meta-value"><?= e((string) ($header['period_name'] ?? '-')) ?></div></div>
        <div class="meta-box"><span class="meta-label">Template</span><div class="meta-value"><?= e(function_exists('journal_print_template_label') ? journal_print_template_label((string) ($header['print_template'] ?? 'standard')) : ((string) ($header['print_template'] ?? 'standard'))) ?></div></div>
    </div>

    <div class="note-box"><strong>Keterangan:</strong> <?= e((string) ($header['description'] ?? '-')) ?></div>

    <?php if (($attachments ?? []) !== []): ?>
        <div class="attachment-box">
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

    <table class="journal-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-account">Akun</th>
                <th class="col-desc">Uraian</th>
                <th class="col-money">Debit</th>
                <th class="col-money">Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($details ?? []) as $detail): ?>
                <tr>
                    <td class="text-center"><?= e((string) ($detail['line_no'] ?? '')) ?></td>
                    <td><?= e(trim((string) (($detail['account_code'] ?? '') . ' - ' . ($detail['account_name'] ?? '')), ' -')) ?></td>
                    <td><?= e((string) ($detail['line_description'] ?? '-')) ?><?php $metaItems = journal_reference_meta_items($detail); if ($metaItems !== []): ?><div style="font-size:8.4px;color:#555;margin-top:2px;"><?= e(implode(' • ', $metaItems)) ?></div><?php endif; ?></td>
                    <td class="num"><?= e(number_format((float) ($detail['debit'] ?? 0), 2, ',', '.')) ?></td>
                    <td class="num"><?= e(number_format((float) ($detail['credit'] ?? 0), 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="num">Total</th>
                <th class="num"><?= e(number_format((float) ($header['total_debit'] ?? 0), 2, ',', '.')) ?></th>
                <th class="num"><?= e(number_format((float) ($header['total_credit'] ?? 0), 2, ',', '.')) ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="sign-grid">
        <div class="sign-card">
            <div class="sign-title">Dibuat oleh</div>
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

    <div class="d-print-none mt-3 d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Sekarang</button>
        <a href="<?= e(base_url('/journals/detail?id=' . (int) ($header['id'] ?? 0))) ?>" class="btn btn-outline-secondary">Kembali ke Detail</a>
    </div>
</div>
