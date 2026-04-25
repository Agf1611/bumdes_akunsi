<?php declare(strict_types=1); ?>
<?php
$header = is_array($header ?? null) ? $header : [];
$details = is_array($details ?? null) ? $details : [];
$attachments = is_array($attachments ?? null) ? $attachments : [];
$attachmentFeatureStatus = is_array($attachmentFeatureStatus ?? null) ? $attachmentFeatureStatus : ['enabled' => false, 'has_journal_attachments_table' => false];
$periodIsOpen = (string) ($header['period_status'] ?? '') === 'OPEN';
$selectedUnitLabel = (string) ($selectedUnitLabel ?? 'Semua / belum ditentukan');
$header = array_replace([
    'id' => 0,
    'journal_no' => '-',
    'journal_date' => '',
    'period_name' => '-',
    'print_template' => 'standard',
    'description' => '-',
    'period_status' => '',
    'total_debit' => 0,
    'total_credit' => 0,
    'party_title' => '',
    'party_name' => '',
    'payment_method' => '',
    'reference_no' => '',
    'purpose' => '',
    'amount_in_words' => '',
    'notes' => '',
], $header);
$receiptCompletion = journal_receipt_completion_summary($header);
$printUrl = base_url('/journals/print?id=' . (int) $header['id']);
$receiptPrintUrl = base_url('/journals/print-receipt?id=' . (int) $header['id']);
$duplicateUrl = base_url('/journals/create?duplicate_id=' . (int) $header['id']);
$editUrl = base_url('/journals/edit?id=' . (int) $header['id']);
?>
<div class="journal-detail-page module-page">
<section class="module-hero mb-4">
    <div class="module-hero__content">
        <div>
            <div class="module-hero__eyebrow">Jurnal Umum</div>
            <h1 class="module-hero__title">Detail Jurnal</h1>
            <p class="module-hero__text">Lihat rincian jurnal umum, template cetak, dan lampiran bukti transaksi dalam tampilan yang lebih ringkas dan mudah dipindai.</p>
        </div>
        <div class="module-hero__actions">
            <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-secondary">Kembali</a>
            <a href="<?= e($duplicateUrl) ?>" class="btn btn-outline-secondary">Duplikat</a>
            <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-outline-info">Cetak Standar</a>
            <?php if (journal_is_receipt_enabled($header)): ?>
                <a href="<?= e($receiptPrintUrl) ?>" target="_blank" class="btn btn-outline-success">Cetak Kwitansi</a>
            <?php endif; ?>
            <?php if ($periodIsOpen): ?>
                <a href="<?= e($editUrl) ?>" class="btn btn-primary">Edit Jurnal</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4"><span class="text-secondary d-block small">Nomor Jurnal</span><span class="fw-semibold"><?= e((string) $header['journal_no']) ?></span></div>
                    <div class="col-md-4"><span class="text-secondary d-block small">Tanggal</span><span class="fw-semibold"><?= e(format_id_date((string) $header['journal_date'])) ?></span></div>
                    <div class="col-md-4"><span class="text-secondary d-block small">Periode</span><span class="fw-semibold"><?= e((string) $header['period_name']) ?></span></div>
                    <div class="col-md-6"><span class="text-secondary d-block small">Unit Usaha</span><span class="fw-semibold"><?= e($selectedUnitLabel ?? 'Semua / belum ditentukan') ?></span></div>
                    <div class="col-md-3"><span class="text-secondary d-block small">Template Cetak</span><span class="fw-semibold"><?= e(journal_print_template_label((string) ($header['print_template'] ?? 'standard'))) ?></span></div>
                    <div class="col-md-3"><span class="text-secondary d-block small">Jumlah Lampiran</span><span class="fw-semibold"><?= e((string) count($attachments)) ?> file</span></div>
                    <?php if ((bool) ($receiptCompletion['enabled'] ?? false)): ?>
                        <div class="col-12"><span class="badge <?= $receiptCompletion['complete'] ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e((string) $receiptCompletion['label']) ?></span></div>
                    <?php endif; ?>
                    <div class="col-12"><span class="text-secondary d-block small">Keterangan</span><span class="fw-semibold"><?= e((string) $header['description']) ?></span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="mb-3"><span class="text-secondary d-block small">Status Periode</span><span class="badge <?= $periodIsOpen ? 'text-bg-success' : 'text-bg-danger' ?> mt-1"><?= $periodIsOpen ? 'Buka' : 'Tutup' ?></span></div>
                <div class="mb-3"><span class="text-secondary d-block small">Total Debit</span><span class="fw-semibold fs-5"><?= e(number_format((float) $header['total_debit'], 2, ',', '.')) ?></span></div>
                <div class="mb-3"><span class="text-secondary d-block small">Total Kredit</span><span class="fw-semibold fs-5"><?= e(number_format((float) $header['total_credit'], 2, ',', '.')) ?></span></div>
                <div><span class="text-secondary d-block small">Status Lampiran</span>
                    <span class="badge <?= ($attachmentFeatureStatus['enabled'] ?? false) ? 'text-bg-info' : 'text-bg-warning' ?> mt-1">
                        <?= ($attachmentFeatureStatus['enabled'] ?? false) ? 'Aktif' : 'Belum aktif di database' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (journal_is_receipt_enabled($header)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Metadata Bukti Transaksi</h2>
        <div class="row g-3">
            <div class="col-md-4"><span class="text-secondary d-block small"><?= e((string) ($header['party_title'] ?: 'Pihak')) ?></span><span class="fw-semibold"><?= e((string) ($header['party_name'] ?: '-')) ?></span></div>
            <div class="col-md-4"><span class="text-secondary d-block small">Metode Pembayaran</span><span class="fw-semibold"><?= e((string) ($header['payment_method'] ?: '-')) ?></span></div>
            <div class="col-md-4"><span class="text-secondary d-block small">No. Referensi</span><span class="fw-semibold"><?= e((string) ($header['reference_no'] ?: '-')) ?></span></div>
            <div class="col-12"><span class="text-secondary d-block small">Tujuan</span><span class="fw-semibold"><?= e((string) ($header['purpose'] ?: '-')) ?></span></div>
            <div class="col-12"><span class="text-secondary d-block small">Terbilang</span><span class="fw-semibold"><?= e(journal_normalize_amount_in_words((string) ($header['amount_in_words'] ?? ''), (float) ($header['total_debit'] ?? 0))) ?></span></div>
            <div class="col-12"><span class="text-secondary d-block small">Catatan</span><span class="fw-semibold"><?= e((string) ($header['notes'] ?: '-')) ?></span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 journal-entry-table">
                <thead>
                    <tr><th style="width:8%">No</th><th>Akun</th><th>Uraian</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr>
                </thead>
                <tbody>
                <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?= e((string) $detail['line_no']) ?></td>
                        <td><div class="fw-semibold"><?= e((string) $detail['account_code']) ?></div><div class="text-secondary small"><?= e((string) $detail['account_name']) ?></div></td>
                        <td><?= e((string) $detail['line_description']) ?><?php $metaItems = journal_reference_meta_items($detail); if ($metaItems !== []): ?><div class="small text-secondary mt-1"><?= e(implode(' • ', $metaItems)) ?></div><?php endif; ?></td>
                        <td class="text-end fw-semibold"><?= e(number_format((float) $detail['debit'], 2, ',', '.')) ?></td>
                        <td class="text-end fw-semibold"><?= e(number_format((float) $detail['credit'], 2, ',', '.')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= e(number_format((float) $header['total_debit'], 2, ',', '.')) ?></th><th class="text-end"><?= e(number_format((float) $header['total_credit'], 2, ',', '.')) ?></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="h5 mb-1">Lampiran Bukti Transaksi</h2>
            <p class="text-secondary mb-0 small">Simpan scan nota, invoice, bukti transfer, atau dokumen pendukung jurnal ini.</p>
        </div>
        <div class="text-secondary small">Tipe file: PDF, JPG, PNG, WEBP. Maksimal 5 MB per file.</div>
    </div>
    <div class="card-body p-4">
        <?php if (!($attachmentFeatureStatus['enabled'] ?? false)): ?>
            <div class="alert alert-warning mb-4">
                Fitur lampiran belum aktif di database. Jalankan file <code>database/patch_stage5_journal_attachments.sql</code> sekali melalui phpMyAdmin, lalu buka ulang halaman ini.
            </div>
        <?php elseif (!$periodIsOpen): ?>
            <div class="alert alert-secondary mb-4">Periode jurnal sudah ditutup, sehingga upload atau hapus lampiran tidak diperbolehkan. Anda masih bisa mengunduh lampiran yang sudah ada.</div>
        <?php else: ?>
            <form method="post" action="<?= e(base_url('/journals/attachments/upload?id=' . (int) $header['id'])) ?>" enctype="multipart/form-data" class="row g-3 mb-4">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div class="col-lg-4">
                    <label class="form-label">Judul Lampiran</label>
                    <input type="text" name="attachment_title" class="form-control" maxlength="150" placeholder="Contoh: Nota pembelian ATK">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Catatan Ringkas</label>
                    <input type="text" name="attachment_notes" class="form-control" maxlength="255" placeholder="Opsional, misalnya supplier / no invoice">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">File Lampiran</label>
                    <input type="file" name="attachment_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Upload Lampiran</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($attachments === []): ?>
            <div class="empty-state-panel empty-state-panel--compact mb-0">
                <div class="empty-state-panel__title">Belum ada lampiran</div>
                <p class="empty-state-panel__text mb-0">Upload nota, invoice, atau bukti transfer agar review jurnal lebih mudah dilakukan di satu tempat.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:5%">No</th>
                            <th>Lampiran</th>
                            <th style="width:11%">Tipe</th>
                            <th style="width:12%" class="text-end">Ukuran</th>
                            <th style="width:16%">Diunggah</th>
                            <th style="width:18%">Oleh</th>
                            <th style="width:16%" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attachments as $index => $attachment): ?>
                        <?php $displayName = trim((string) ($attachment['attachment_title'] ?? '')) !== '' ? (string) $attachment['attachment_title'] : (string) ($attachment['original_name'] ?? '-'); ?>
                        <tr>
                            <td><?= e((string) ($index + 1)) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($displayName) ?></div>
                                <div class="small text-secondary"><?= e((string) ($attachment['original_name'] ?? '-')) ?></div>
                                <?php if (trim((string) ($attachment['attachment_notes'] ?? '')) !== ''): ?>
                                    <div class="small text-info mt-1"><?= e((string) $attachment['attachment_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge text-bg-secondary"><?= e(journal_attachment_type_label($attachment)) ?></span></td>
                            <td class="text-end"><?= e(journal_attachment_file_size((int) ($attachment['file_size'] ?? 0))) ?></td>
                            <td><?= e(format_id_date((string) substr((string) ($attachment['created_at'] ?? ''), 0, 10))) ?><div class="small text-secondary"><?= e(substr((string) ($attachment['created_at'] ?? ''), 11, 5)) ?></div></td>
                            <td><?= e((string) (($attachment['uploaded_by_name'] ?? '') !== '' ? $attachment['uploaded_by_name'] : '-')) ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end flex-wrap">
                                    <a href="<?= e(base_url('/journals/attachments/download?id=' . (int) $attachment['id'])) ?>" class="btn btn-sm btn-outline-info">Unduh</a>
                                    <?php if ($periodIsOpen && ($attachmentFeatureStatus['enabled'] ?? false)): ?>
                                        <form method="post" action="<?= e(base_url('/journals/attachments/delete?id=' . (int) $attachment['id'])) ?>" onsubmit="return confirm('Hapus lampiran ini?');" class="d-inline m-0">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
