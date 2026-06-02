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
<style>
.journal-attachment-upload-preview.is-hidden {
    display: none;
}

.journal-detail-page .card,
.journal-detail-page .journal-attachment-upload-preview,
.journal-detail-page .journal-attachment-inline-preview {
    background: var(--bg-panel);
    border-color: var(--border-soft);
    color: var(--text-main);
    box-shadow: var(--shadow-xs);
}

.journal-attachment-upload-preview,
.journal-attachment-inline-preview {
    border: 1px solid var(--border-soft);
    border-radius: 18px;
    background: var(--bg-panel-soft);
    padding: .95rem;
}

.journal-attachment-inline-preview {
    margin-top: .75rem;
    max-width: 360px;
}

.journal-attachment-preview-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin-bottom: .75rem;
    flex-wrap: wrap;
}

.journal-attachment-preview-title {
    font-weight: 700;
    color: var(--text-main);
}

.journal-attachment-preview-meta {
    color: var(--text-muted);
    font-size: .84rem;
}

.journal-attachment-media-frame {
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    background: var(--bg-panel);
    overflow: hidden;
}

.journal-attachment-media-frame img,
.journal-attachment-media-frame iframe {
    display: block;
    width: 100%;
    border: 0;
}

.journal-attachment-media-frame iframe {
    min-height: 260px;
    background: var(--bg-panel);
}

.journal-attachment-media-frame--image {
    padding: .5rem;
}

.journal-attachment-media-frame--image img {
    max-height: 240px;
    object-fit: contain;
    margin: 0 auto;
}

.journal-attachment-file-badge {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border-radius: 999px;
    background: var(--primary-soft);
    color: var(--primary);
    padding: .35rem .7rem;
    font-size: .78rem;
    font-weight: 600;
}

.journal-detail-page .text-dark,
.journal-detail-page .fw-semibold {
    color: var(--text-main) !important;
}

.journal-detail-page .text-secondary,
.journal-detail-page .small,
.journal-detail-page .form-text {
    color: var(--text-muted) !important;
}

.journal-detail-page .table {
    color: var(--text-main);
}

.journal-detail-page .table thead th,
.journal-detail-page .table tfoot th {
    background: var(--bg-panel-soft);
    color: var(--text-muted);
    border-color: var(--border-soft);
}

.journal-detail-page .table td,
.journal-detail-page .table th {
    border-color: var(--border-soft);
    background: transparent;
}

.journal-upload-preview-toggle.is-hidden,
.journal-attachment-preview-frame.is-hidden {
    display: none;
}

.journal-entry-card-list,
.journal-attachment-card-list {
    display: grid;
    gap: .7rem;
}

.journal-entry-card,
.journal-attachment-card {
    border: 1px solid var(--border-soft);
    border-radius: 12px;
    background: var(--bg-panel);
    padding: .8rem;
}

.journal-entry-card__head,
.journal-attachment-card__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem;
    margin-bottom: .65rem;
}

.journal-entry-card__code,
.journal-attachment-card__title {
    color: var(--text-main);
    font-weight: 750;
    line-height: 1.25;
}

.journal-entry-card__name,
.journal-entry-card__desc,
.journal-attachment-card__meta {
    color: var(--text-muted);
    font-size: .82rem;
    line-height: 1.35;
}

.journal-entry-card__amounts,
.journal-attachment-card__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .5rem;
    margin-top: .7rem;
}

.journal-entry-card__amount {
    border: 1px solid var(--border-soft);
    border-radius: 10px;
    background: var(--bg-panel-soft);
    padding: .6rem;
}

.journal-detail-label {
    display: block;
    margin-bottom: .18rem;
    color: var(--text-muted);
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
}

.journal-entry-card__total {
    background: #eef4ff;
    border-color: rgba(37, 99, 235, .18);
}

@media (max-width: 991.98px) {
    body.route-journals-detail .app-frame {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    body.route-journals-detail .app-main {
        width: 100vw !important;
        max-width: 100vw !important;
        margin-left: 0 !important;
        scrollbar-gutter: auto !important;
    }
    body.route-journals-detail .app-content {
        width: 100% !important;
        max-width: 100% !important;
        padding: .5rem 8px 6.75rem !important;
    }
    body.route-journals-detail .content-wrap,
    body.route-journals-detail .content-wrap.container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .journal-detail-page {
        padding-bottom: 1rem;
    }
    .journal-detail-page .module-hero {
        margin-bottom: .75rem !important;
        border-radius: 12px;
        padding: .9rem;
    }
    .journal-detail-page .module-hero__content {
        display: grid;
        gap: .75rem;
    }
    .journal-detail-page .module-hero__eyebrow {
        display: none;
    }
    .journal-detail-page .module-hero__title {
        font-size: 1.25rem;
        line-height: 1.18;
    }
    .journal-detail-page .module-hero__text {
        display: none;
    }
    .journal-detail-page .module-hero__actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: .45rem;
        width: 100%;
    }
    .journal-detail-page .module-hero__actions .btn,
    .journal-detail-page .module-hero__actions .btn.ui-action-btn {
        width: 100%;
        min-width: 0 !important;
        min-height: 42px;
        border-radius: 12px;
        padding: .48rem .55rem !important;
        font-size: .82rem;
        white-space: nowrap;
        justify-content: center;
    }
    .journal-detail-page .module-hero__actions .ui-action-btn__label {
        display: inline !important;
    }
    .journal-detail-page .card {
        border-radius: 12px;
        margin-bottom: .75rem !important;
    }
    .journal-detail-page .card-body,
    .journal-detail-page .card-header {
        padding: .85rem !important;
    }
    .journal-detail-page .card-body.p-0 {
        padding: 0 !important;
    }
    .journal-entry-card-list {
        padding: .75rem !important;
    }
    .journal-detail-page .row {
        --bs-gutter-x: .65rem;
        --bs-gutter-y: .65rem;
    }
    .journal-detail-page .row.g-4 {
        margin-bottom: .75rem !important;
    }
    .journal-detail-page .small,
    .journal-detail-page .form-text {
        font-size: .78rem;
        line-height: 1.4;
    }
    .journal-detail-page .fw-semibold,
    .journal-detail-page .fs-5 {
        font-size: .94rem !important;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    .journal-detail-page h2.h5 {
        font-size: 1rem;
    }
    .journal-detail-page .form-control,
    .journal-detail-page .form-select {
        min-height: 42px;
        border-radius: 10px;
        font-size: .9rem !important;
        padding: .5rem .68rem;
    }
    .journal-attachment-upload-preview,
    .journal-attachment-inline-preview {
        border-radius: 12px;
        padding: .75rem;
    }
    .journal-attachment-card__actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .journal-attachment-card__actions .btn,
    .journal-attachment-card__actions button {
        width: 100%;
        min-height: 40px;
        border-radius: 10px;
        font-size: .82rem;
    }
}
</style>
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
        <div class="table-responsive journal-entry-table-wrap d-none d-lg-block">
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
        <div class="journal-entry-card-list d-lg-none p-3">
            <?php foreach ($details as $detail): ?>
                <?php $metaItems = journal_reference_meta_items($detail); ?>
                <div class="journal-entry-card">
                    <div class="journal-entry-card__head">
                        <div>
                            <span class="journal-detail-label">Akun</span>
                            <div class="journal-entry-card__code"><?= e((string) $detail['account_code']) ?></div>
                            <div class="journal-entry-card__name"><?= e((string) $detail['account_name']) ?></div>
                        </div>
                        <span class="badge text-bg-secondary">#<?= e((string) $detail['line_no']) ?></span>
                    </div>
                    <?php if (trim((string) $detail['line_description']) !== '' || $metaItems !== []): ?>
                        <div class="journal-entry-card__desc">
                            <?= e((string) $detail['line_description']) ?>
                            <?php if ($metaItems !== []): ?>
                                <div class="mt-1"><?= e(implode(' / ', $metaItems)) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="journal-entry-card__amounts">
                        <div class="journal-entry-card__amount">
                            <span class="journal-detail-label">Debit</span>
                            <div class="fw-semibold"><?= e(number_format((float) $detail['debit'], 2, ',', '.')) ?></div>
                        </div>
                        <div class="journal-entry-card__amount">
                            <span class="journal-detail-label">Kredit</span>
                            <div class="fw-semibold"><?= e(number_format((float) $detail['credit'], 2, ',', '.')) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="journal-entry-card journal-entry-card__total">
                <div class="journal-entry-card__amounts mt-0">
                    <div>
                        <span class="journal-detail-label">Total Debit</span>
                        <div class="fw-semibold"><?= e(number_format((float) $header['total_debit'], 2, ',', '.')) ?></div>
                    </div>
                    <div>
                        <span class="journal-detail-label">Total Kredit</span>
                        <div class="fw-semibold"><?= e(number_format((float) $header['total_credit'], 2, ',', '.')) ?></div>
                    </div>
                </div>
            </div>
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
                    <div class="form-text text-secondary">Setelah file dipilih, cek nama file dulu. Jika perlu, klik tombol preview untuk melihat isinya sebelum upload.</div>
                </div>
                <div class="col-12">
                    <div class="journal-attachment-upload-preview is-hidden" id="journalUploadPreview" aria-live="polite">
                        <div class="journal-attachment-preview-head">
                            <div>
                                <div class="journal-attachment-preview-title">Preview Sebelum Upload</div>
                                <div class="journal-attachment-preview-meta" id="journalUploadPreviewMeta">Belum ada file dipilih.</div>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <span class="journal-attachment-file-badge" id="journalUploadPreviewType">-</span>
                                <button type="button" class="btn btn-sm btn-outline-primary journal-upload-preview-toggle is-hidden" id="journalUploadPreviewToggle">Tampilkan Preview</button>
                            </div>
                        </div>
                        <div class="journal-attachment-media-frame journal-attachment-preview-frame is-hidden" id="journalUploadPreviewFrame"></div>
                    </div>
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
            <div class="table-responsive journal-attachment-table-wrap d-none d-lg-block">
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
                        <?php $previewUrl = base_url('/journals/attachments/preview?id=' . (int) $attachment['id']); ?>
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
                                    <?php if (journal_attachment_is_previewable($attachment)): ?>
                                        <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Preview</a>
                                    <?php endif; ?>
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
            <div class="journal-attachment-card-list d-lg-none">
                <?php foreach ($attachments as $index => $attachment): ?>
                    <?php $displayName = trim((string) ($attachment['attachment_title'] ?? '')) !== '' ? (string) $attachment['attachment_title'] : (string) ($attachment['original_name'] ?? '-'); ?>
                    <?php $previewUrl = base_url('/journals/attachments/preview?id=' . (int) $attachment['id']); ?>
                    <div class="journal-attachment-card">
                        <div class="journal-attachment-card__head">
                            <div>
                                <span class="journal-detail-label">Lampiran <?= e((string) ($index + 1)) ?></span>
                                <div class="journal-attachment-card__title"><?= e($displayName) ?></div>
                                <div class="journal-attachment-card__meta"><?= e((string) ($attachment['original_name'] ?? '-')) ?></div>
                            </div>
                            <span class="badge text-bg-secondary"><?= e(journal_attachment_type_label($attachment)) ?></span>
                        </div>
                        <div class="journal-attachment-card__meta">
                            <?= e(journal_attachment_file_size((int) ($attachment['file_size'] ?? 0))) ?> / <?= e(format_id_date((string) substr((string) ($attachment['created_at'] ?? ''), 0, 10))) ?>
                            <?php if (trim((string) ($attachment['attachment_notes'] ?? '')) !== ''): ?>
                                <div class="text-info mt-1"><?= e((string) $attachment['attachment_notes']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="journal-attachment-card__actions">
                            <?php if (journal_attachment_is_previewable($attachment)): ?>
                                <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Preview</a>
                            <?php endif; ?>
                            <a href="<?= e(base_url('/journals/attachments/download?id=' . (int) $attachment['id'])) ?>" class="btn btn-sm btn-outline-info">Unduh</a>
                            <?php if ($periodIsOpen && ($attachmentFeatureStatus['enabled'] ?? false)): ?>
                                <form method="post" action="<?= e(base_url('/journals/attachments/delete?id=' . (int) $attachment['id'])) ?>" onsubmit="return confirm('Hapus lampiran ini?');" class="m-0">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
<script>
(function () {
    var fileInput = document.querySelector('input[name="attachment_file"]');
    var previewBox = document.getElementById('journalUploadPreview');
    var previewMeta = document.getElementById('journalUploadPreviewMeta');
    var previewType = document.getElementById('journalUploadPreviewType');
    var previewFrame = document.getElementById('journalUploadPreviewFrame');
    var previewToggle = document.getElementById('journalUploadPreviewToggle');
    if (!fileInput || !previewBox || !previewMeta || !previewType || !previewFrame || !previewToggle) {
        return;
    }

    var activeObjectUrl = null;
    var previewRendered = false;
    var pendingRenderer = null;

    function clearPreview() {
        if (activeObjectUrl) {
            URL.revokeObjectURL(activeObjectUrl);
            activeObjectUrl = null;
        }
        previewFrame.innerHTML = '';
        previewType.textContent = '-';
        previewMeta.textContent = 'Belum ada file dipilih.';
        previewBox.classList.add('is-hidden');
        previewFrame.classList.add('is-hidden');
        previewToggle.classList.add('is-hidden');
        previewToggle.textContent = 'Tampilkan Preview';
        previewRendered = false;
        pendingRenderer = null;
    }

    function formatSize(size) {
        var bytes = Number(size || 0);
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        var units = ['B', 'KB', 'MB', 'GB'];
        var unitIndex = 0;
        while (bytes >= 1024 && unitIndex < units.length - 1) {
            bytes /= 1024;
            unitIndex++;
        }
        return bytes.toLocaleString('id-ID', {
            minimumFractionDigits: unitIndex === 0 ? 0 : 2,
            maximumFractionDigits: unitIndex === 0 ? 0 : 2
        }) + ' ' + units[unitIndex];
    }

    fileInput.addEventListener('change', function () {
        clearPreview();
        var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
            return;
        }

        var mime = String(file.type || '').toLowerCase();
        var extension = String(file.name || '').split('.').pop().toLowerCase();
        var isImage = mime.indexOf('image/') === 0 || ['jpg', 'jpeg', 'png', 'webp'].indexOf(extension) >= 0;
        var isPdf = mime === 'application/pdf' || extension === 'pdf';

        previewBox.classList.remove('is-hidden');
        previewMeta.textContent = file.name + ' · ' + formatSize(file.size);
        previewType.textContent = isPdf ? 'PDF' : (isImage ? 'Gambar' : 'File');
        previewFrame.classList.add('is-hidden');
        previewToggle.classList.remove('is-hidden');
        previewToggle.textContent = 'Tampilkan Preview';
        previewRendered = false;

        pendingRenderer = function () {
            if (!isImage && !isPdf) {
                previewFrame.className = 'journal-attachment-media-frame journal-attachment-preview-frame';
                previewFrame.innerHTML = '<div class="p-3 text-secondary small">Preview langsung hanya tersedia untuk PDF dan gambar.</div>';
                return;
            }

            activeObjectUrl = URL.createObjectURL(file);
            if (isImage) {
                previewFrame.className = 'journal-attachment-media-frame journal-attachment-media-frame--image journal-attachment-preview-frame';
                previewFrame.innerHTML = '<img src="' + activeObjectUrl + '" alt="Preview lampiran">';
                return;
            }

            previewFrame.className = 'journal-attachment-media-frame journal-attachment-preview-frame';
            previewFrame.innerHTML = '<iframe src="' + activeObjectUrl + '" title="Preview PDF"></iframe>';
        };
    });

    previewToggle.addEventListener('click', function () {
        if (previewFrame.classList.contains('is-hidden')) {
            if (!previewRendered && typeof pendingRenderer === 'function') {
                pendingRenderer();
                previewRendered = true;
            }
            previewFrame.classList.remove('is-hidden');
            previewToggle.textContent = 'Sembunyikan Preview';
            return;
        }

        previewFrame.classList.add('is-hidden');
        previewToggle.textContent = 'Tampilkan Preview';
    });
})();
</script>
