<?php declare(strict_types=1);
$headerProfile = $profile ?? app_profile();
$periodLabel = report_period_label($filters, $selectedPeriod);
$cityDate = lpj_approval_city_date($headerProfile, (string) ($signatoryInput['approval_date'] ?? date('Y-m-d')));
$documentReference = lpj_document_reference($headerProfile, $signatoryInput ?? []);
$approvalBasis = lpj_approval_basis($signatoryInput ?? [], $headerProfile);
$meetingReference = lpj_meeting_reference($signatoryInput ?? []);
$sectionOutline = lpj_section_outline(get_defined_vars());
$formalStatement = lpj_formal_statement($headerProfile, get_defined_vars());
$recipientSummary = lpj_recipient_summary($signatoryInput ?? []);
$appendixSummary = lpj_appendix_summary($signatoryInput ?? [], get_defined_vars());
?>
<div class="print-sheet lpj-sheet">
    <section class="lpj-cover page-break-after">
        <div class="lpj-cover__logo">
            <?php if (!empty($headerProfile['logo_path'])): ?>
                <img src="<?= e(upload_url((string) $headerProfile['logo_path'])) ?>" alt="Logo BUMDes">
            <?php else: ?>
                <div class="lpj-cover__fallback">BUMDes</div>
            <?php endif; ?>
        </div>
        <div class="lpj-cover__eyebrow">BADAN USAHA MILIK DESA</div>
        <div class="lpj-cover__eyebrow">BUM DESA</div>
        <h1 class="lpj-cover__name"><?= e((string) ($headerProfile['bumdes_name'] ?? 'BUMDes')) ?></h1>
        <div class="lpj-cover__title"><?= e(lpj_document_title((string) $packageType)) ?></div>
        <div class="lpj-cover__meta">Periode: <?= e($periodLabel) ?></div>
        <div class="lpj-cover__meta">Unit Usaha: <?= e($selectedUnitLabel) ?></div>
        <div class="lpj-cover__badge">Dokumen Pertanggungjawaban Resmi</div>
        <div class="lpj-cover__meta">Nomor Dokumen: <?= e($documentReference) ?></div>
        <div class="lpj-cover__meta">Tanggal Pengesahan: <?= e($cityDate) ?></div>
        <div class="lpj-cover__footer">
            <?php if (!empty($headerProfile['address'])): ?><div><?= e((string) $headerProfile['address']) ?></div><?php endif; ?>
            <?php if (report_profile_location($headerProfile) !== ''): ?><div><?= e(report_profile_location($headerProfile)) ?></div><?php endif; ?>
            <?php if (report_profile_legal($headerProfile) !== ''): ?><div><?= e(report_profile_legal($headerProfile)) ?></div><?php endif; ?>
        </div>
    </section>


    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Sampul Pengantar Paket LPJ', $periodLabel, $selectedUnitLabel); ?>
        <div class="lpj-cover-letter mb-3"><p class="mb-0"><?= e(lpj_cover_letter_paragraph($headerProfile, get_defined_vars())) ?></p></div>
        <div class="table-responsive"><table class="print-table"><tbody>
            <tr><th style="width: 28%;">Ditujukan Kepada</th><td><?= e($recipientSummary) ?></td></tr>
            <tr><th>Nomor Dokumen</th><td><?= e($documentReference) ?></td></tr>
            <tr><th>Periode</th><td><?= e($periodLabel) ?></td></tr>
            <tr><th>Ringkasan Lampiran</th><td><?= e($appendixSummary) ?></td></tr>
        </tbody></table></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="lpj-approval page-break-after">
        <h2 class="report-title text-center mb-2">Halaman Pengesahan</h2>
        <div class="text-center text-secondary small mb-3">Dokumen ini menjadi bagian dari arsip dan bahan pertanggungjawaban operasional BUM Desa.</div>
        <div class="table-responsive mb-3">
            <table class="print-table">
                <tbody>
                <tr><th style="width: 28%;">Jenis Dokumen</th><td><?= e(lpj_document_title((string) $packageType)) ?></td></tr>
                <tr><th>Periode Laporan</th><td><?= e($periodLabel) ?></td></tr>
                <tr><th>Unit Usaha</th><td><?= e($selectedUnitLabel) ?></td></tr>
                <tr><th>Nomor Dokumen</th><td><?= e($documentReference) ?></td></tr>
                <tr><th>Tanggal Pengesahan</th><td><?= e($cityDate) ?></td></tr>
                <tr><th>Dasar Pengesahan</th><td><?= e($approvalBasis) ?></td></tr>
                <tr><th>Referensi Rapat / BA</th><td><?= e($meetingReference) ?></td></tr>
                </tbody>
            </table>
        </div>
        <div class="lpj-approval__statement mb-4">
            <div class="fw-semibold mb-2">Pernyataan</div>
            <p class="mb-0"><?= e($formalStatement) ?></p>
        </div>
        <div class="lpj-sign-meta mb-3"><?= e($cityDate) ?></div>
        <div class="lpj-sign-grid">
            <?php foreach ($signatories as $signer): ?>
                <div class="lpj-sign-card">
                    <div class="lpj-sign-role"><?= e((string) ($signer['role'] ?? '')) ?></div>
                    <div class="lpj-sign-position"><?= e((string) ($signer['position'] ?? '')) ?></div>
                    <div class="lpj-sign-pad">
                        <?php if (($signer['signature_url'] ?? '') !== ''): ?><img src="<?= e((string) $signer['signature_url']) ?>" alt="Tanda tangan"><?php endif; ?>
                    </div>
                    <div class="lpj-sign-name"><?= e((string) ($signer['name'] ?? '-')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="lpj-sign-note mt-3">Catatan: tanda tangan dan/atau stempel dapat dilengkapi sesuai kebutuhan administrasi setempat sebelum dokumen final didistribusikan.</div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Daftar Isi Paket LPJ', $periodLabel, $selectedUnitLabel); ?>
        <div class="lpj-outline-table table-responsive">
            <table class="print-table">
                <thead><tr><th style="width: 8%;">No</th><th>Bagian Dokumen</th><th style="width: 44%;">Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($sectionOutline as $idx => $section): ?>
                    <tr>
                        <td class="text-center"><?= e((string) ($idx + 1)) ?></td>
                        <td><?= e((string) ($section['title'] ?? '')) ?></td>
                        <td><?= e((string) ($section['note'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="small text-secondary mt-3">Susunan paket ini dapat disesuaikan kembali sebelum disampaikan secara resmi, namun dianjurkan tetap menjaga urutan inti laporan dan lampiran.</div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, $packageLabel . ' - Ringkasan Eksekutif', $periodLabel, $selectedUnitLabel); ?>
        <div class="row g-3 mb-4">
            <?php foreach (lpj_summary_cards($summary) as $card): ?>
                <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary mb-1"><?= e((string) $card['label']) ?></div><div class="fw-bold mb-1"><?= e((string) $card['value']) ?></div><div class="small text-secondary"><?= e((string) $card['note']) ?></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="mb-3"><div class="fw-semibold mb-2">Ringkasan Eksekutif</div><p><?= e((string) ($narratives['executive_summary'] ?? '')) ?></p></div>
        <div class="mb-3"><div class="fw-semibold mb-2">Keadaan dan Jalannya BUMDes</div><p><?= e((string) ($narratives['business_overview'] ?? '')) ?></p></div>
        <div class="mb-0"><div class="fw-semibold mb-2">Kegiatan Utama</div><p><?= e((string) ($narratives['activities_summary'] ?? '')) ?></p></div>
        <?php if ($issues !== []): ?><div class="alert alert-secondary mt-3 mb-0"><div class="fw-semibold mb-2">Sorotan Perhatian</div><ul class="mb-0 ps-3"><?php foreach ($issues as $issue): ?><li><?= e((string) $issue) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Lampiran LPJ - Laporan Laba Rugi', $periodLabel, $selectedUnitLabel); ?>
        <div class="table-responsive"><table class="print-table"><thead><tr><th style="width: 16%;">Kode</th><th>Nama Akun</th><th style="width: 22%;" class="text-end">Nilai</th></tr></thead><tbody><tr><td colspan="3" class="fw-semibold">Pendapatan</td></tr><?php if ($profitLoss['revenue_rows'] === []): ?><tr><td colspan="3" class="text-center text-secondary">Tidak ada akun pendapatan.</td></tr><?php else: foreach ($profitLoss['revenue_rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td></tr><?php endforeach; endif; ?><tr><td colspan="2" class="text-end fw-semibold">Total Pendapatan</td><td class="text-end fw-bold"><?= e(ledger_currency((float) $profitLoss['total_revenue'])) ?></td></tr><tr><td colspan="3" class="fw-semibold">Beban</td></tr><?php if ($profitLoss['expense_rows'] === []): ?><tr><td colspan="3" class="text-center text-secondary">Tidak ada akun beban.</td></tr><?php else: foreach ($profitLoss['expense_rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td></tr><?php endforeach; endif; ?></tbody><tfoot><tr><th colspan="2" class="text-end"><?= e(profit_loss_result_label((float) $profitLoss['net_income'])) ?></th><th class="text-end"><?= e(ledger_currency((float) $profitLoss['net_income'])) ?></th></tr></tfoot></table></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Lampiran LPJ - Neraca', $periodLabel, $selectedUnitLabel); ?>
        <div class="table-responsive"><table class="print-table"><thead><tr><th style="width: 16%;">Kode</th><th>Nama Akun</th><th style="width: 20%;" class="text-end">Saldo Akhir</th><?php if (!empty($balanceSheet['comparison_enabled'])): ?><th style="width: 20%;" class="text-end">Pembanding</th><?php endif; ?></tr></thead><tbody><tr><td colspan="<?= !empty($balanceSheet['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">Aset</td></tr><?php foreach ($balanceSheet['asset_rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; ?><tr><td colspan="2" class="text-end fw-semibold">Total Aset</td><td class="text-end fw-bold"><?= e(ledger_currency((float) $balanceSheet['total_assets'])) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end fw-bold"><?= e(ledger_currency((float) ($balanceSheet['comparison_total_assets'] ?? 0))) ?></td><?php endif; ?></tr><tr><td colspan="<?= !empty($balanceSheet['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">Liabilitas</td></tr><?php foreach ($balanceSheet['liability_rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; ?><tr><td colspan="2" class="text-end fw-semibold">Total Liabilitas</td><td class="text-end fw-bold"><?= e(ledger_currency((float) $balanceSheet['total_liabilities'])) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end fw-bold"><?= e(ledger_currency((float) ($balanceSheet['comparison_total_liabilities'] ?? 0))) ?></td><?php endif; ?></tr><tr><td colspan="<?= !empty($balanceSheet['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">Ekuitas</td></tr><?php foreach ($balanceSheet['equity_rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; ?><?php if (abs((float) ($balanceSheet['current_earnings'] ?? 0)) > 0.004 || abs((float) ($balanceSheet['comparison_current_earnings'] ?? 0)) > 0.004): ?><tr><td></td><td>Laba / Rugi Berjalan</td><td class="text-end"><?= e(ledger_currency((float) ($balanceSheet['current_earnings'] ?? 0))) ?></td><?php if (!empty($balanceSheet['comparison_enabled'])): ?><td class="text-end"><?= e(ledger_currency((float) ($balanceSheet['comparison_current_earnings'] ?? 0))) ?></td><?php endif; ?></tr><?php endif; ?></tbody><tfoot><tr><th colspan="2" class="text-end">Total Liabilitas + Ekuitas</th><th class="text-end"><?= e(ledger_currency((float) $balanceSheet['total_liabilities_equity'])) ?></th><?php if (!empty($balanceSheet['comparison_enabled'])): ?><th class="text-end"><?= e(ledger_currency((float) ($balanceSheet['comparison_total_liabilities_equity'] ?? 0))) ?></th><?php endif; ?></tr></tfoot></table></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Lampiran LPJ - Arus Kas & Perubahan Ekuitas', $periodLabel, $selectedUnitLabel); ?>
        <div class="row g-4">
            <div class="col-lg-6"><div class="table-responsive"><table class="print-table"><thead><tr><th>Komponen Arus Kas</th><th class="text-end">Nilai</th></tr></thead><tbody><tr><td>Kas Awal</td><td class="text-end"><?= e(ledger_currency((float) $cashFlow['opening_cash'])) ?></td></tr><tr><td>Arus Kas Bersih Operasional</td><td class="text-end"><?= e(ledger_currency((float) $cashFlow['total_operating'])) ?></td></tr><tr><td>Arus Kas Bersih Investasi</td><td class="text-end"><?= e(ledger_currency((float) $cashFlow['total_investing'])) ?></td></tr><tr><td>Arus Kas Bersih Pendanaan</td><td class="text-end"><?= e(ledger_currency((float) $cashFlow['total_financing'])) ?></td></tr></tbody><tfoot><tr><th>Kas Akhir</th><th class="text-end"><?= e(ledger_currency((float) $cashFlow['closing_cash'])) ?></th></tr></tfoot></table></div><?php if ($cashFlow['warnings'] !== []): ?><div class="small text-secondary mt-2">Catatan: <?= e(implode(' ', $cashFlow['warnings'])) ?></div><?php endif; ?></div>
            <div class="col-lg-6"><div class="table-responsive"><table class="print-table"><thead><tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Saldo Akhir</th></tr></thead><tbody><?php if ($equityChanges['rows'] === []): ?><tr><td colspan="3" class="text-center text-secondary">Tidak ada data ekuitas.</td></tr><?php else: foreach ($equityChanges['rows'] as $row): ?><tr><td><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end"><?= e(ledger_currency((float) $row['closing_amount'])) ?></td></tr><?php endforeach; endif; ?></tbody><tfoot><tr><th colspan="2" class="text-end">Total Ekuitas Akhir</th><th class="text-end"><?= e(ledger_currency((float) $equityChanges['final_equity_total'])) ?></th></tr></tfoot></table></div></div>
        </div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Lampiran LPJ - Catatan atas Laporan Keuangan', $periodLabel, $selectedUnitLabel); ?>
        <div class="row g-4"><?php foreach ($financialNotes as $section): ?><div class="col-lg-6"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold mb-2"><?= e((string) ($section['title'] ?? '')) ?></div><?php foreach ((array) ($section['paragraphs'] ?? []) as $paragraph): ?><p class="small mb-2"><?= e((string) $paragraph) ?></p><?php endforeach; ?><?php $rows = lpj_visible_note_rows((array) ($section['rows'] ?? []), 4); if ($rows !== []): ?><div class="table-responsive mt-2"><table class="print-table"><thead><tr><th style="width: 18%;">Kode</th><th>Akun</th><th style="width: 26%;" class="text-end">Nilai</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e((string) ($row['account_code'] ?? '-')) ?></td><td><?= e((string) ($row['account_name'] ?? '-')) ?></td><td class="text-end"><?= e(financial_notes_currency((float) ($row['amount'] ?? 0))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div><?php endforeach; ?></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section>
        <?php render_print_header($headerProfile, 'Lampiran LPJ - Keadaan, Masalah, dan Tindak Lanjut', $periodLabel, $selectedUnitLabel); ?>
        <div class="mb-3"><div class="fw-semibold mb-2">Keadaan dan Jalannya BUMDes</div><p><?= e((string) ($narratives['business_overview'] ?? '')) ?></p></div>
        <div class="mb-3"><div class="fw-semibold mb-2">Kegiatan Utama</div><p><?= e((string) ($narratives['activities_summary'] ?? '')) ?></p></div>
        <div class="mb-3"><div class="fw-semibold mb-2">Masalah / Catatan Penting</div><p><?= e((string) ($narratives['problems_summary'] ?? '')) ?></p></div>
        <div class="mb-0"><div class="fw-semibold mb-2">Tindak Lanjut / Rencana Perbaikan</div><p><?= e((string) ($narratives['follow_up_summary'] ?? '')) ?></p></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section class="page-break-after">
        <?php render_print_header($headerProfile, 'Lembar Disposisi dan Lampiran', $periodLabel, $selectedUnitLabel); ?>
        <div class="table-responsive"><table class="print-table"><tbody>
            <tr><th style="width: 28%;">Tujuan Penyerahan</th><td><?= e($recipientSummary) ?></td></tr>
            <tr><th>Ringkasan Lampiran</th><td><?= e($appendixSummary) ?></td></tr>
            <tr><th>Referensi Rapat / BA</th><td><?= e($meetingReference) ?></td></tr>
            <tr><th>Catatan Disposisi</th><td>...........................................................................................................................................................</td></tr>
            <tr><th>Tindak Lanjut</th><td>...........................................................................................................................................................</td></tr>
        </tbody></table></div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

    <section>
        <?php render_print_header($headerProfile, 'Halaman Tanda Terima Dokumen', $periodLabel, $selectedUnitLabel); ?>
        <p class="mb-3">Lembar ini digunakan sebagai bukti serah terima paket LPJ kepada pihak penerima dokumen.</p>
        <div class="table-responsive"><table class="print-table"><tbody>
            <tr><th style="width: 28%;">Dokumen</th><td><?= e(lpj_document_title((string) $packageType)) ?></td></tr>
            <tr><th>Nomor Dokumen</th><td><?= e($documentReference) ?></td></tr>
            <tr><th>Pihak Penerima</th><td><?= e($recipientSummary) ?></td></tr>
            <tr><th>Tanggal Terima</th><td>........................................................</td></tr>
            <tr><th>Nama Penerima</th><td>........................................................</td></tr>
            <tr><th>Jabatan / Instansi</th><td>........................................................</td></tr>
        </tbody></table></div>
        <div class="lpj-receipt-sign">
            <div><div class="mb-5">Penerima,</div><div class="lpj-sign-line"></div><div class="small text-secondary">Nama jelas & tanda tangan</div></div>
            <div><div class="mb-5">Penyerah,</div><div class="lpj-sign-line"></div><div class="small text-secondary"><?= e(profile_director_name($headerProfile)) ?></div></div>
        </div>
        <div class="lpj-page-footer"><span><?= e($documentReference) ?></span><span>Paket LPJ BUMDes</span><span class="lpj-page-number"></span></div>
    </section>

</div>
