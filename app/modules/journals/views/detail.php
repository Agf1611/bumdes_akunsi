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
$attachmentPreviewKind = static function (array $attachment): string {
    $extension = strtolower((string) ($attachment['file_ext'] ?? pathinfo((string) ($attachment['original_name'] ?? ''), PATHINFO_EXTENSION)));
    return match ($extension) {
        'pdf' => 'pdf',
        'jpg', 'jpeg', 'png', 'webp' => 'image',
        default => '',
    };
};
?>

<style>
.journal-attachment-actions { display:flex; gap:.5rem; justify-content:flex-end; flex-wrap:wrap; }
.journal-attachment-preview-trigger { min-width:90px; }
.journal-attachment-modal[hidden] { display:none !important; }
.journal-attachment-modal { position:fixed; inset:0; z-index:1080; background:rgba(15,23,42,.68); backdrop-filter:blur(5px); display:flex; align-items:center; justify-content:center; padding:1.2rem; }
.journal-attachment-modal__dialog { width:min(1120px,100%); max-height:calc(100vh - 2.4rem); background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%); border:1px solid rgba(148,163,184,.28); border-radius:24px; box-shadow:0 30px 80px rgba(15,23,42,.28); overflow:hidden; }
.journal-attachment-modal__header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:1.1rem 1.25rem 1rem; border-bottom:1px solid rgba(148,163,184,.2); background:rgba(255,255,255,.92); }
.journal-attachment-modal__title { margin:0; font-size:1.05rem; color:#0f172a; }
.journal-attachment-modal__meta { color:#64748b; font-size:.86rem; margin-top:.3rem; }
.journal-attachment-modal__close { border:1px solid rgba(148,163,184,.3); background:#fff; color:#334155; width:42px; height:42px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:1.25rem; line-height:1; box-shadow:0 10px 25px rgba(15,23,42,.08); }
.journal-attachment-modal__close:hover { background:#eff6ff; color:#1d4ed8; }
.journal-attachment-modal__body { padding:1rem 1.25rem 1.25rem; background:linear-gradient(180deg,rgba(248,250,252,.9),rgba(255,255,255,.96)); }
.journal-attachment-modal__frame { border:1px solid rgba(148,163,184,.24); border-radius:18px; background:#ffffff; overflow:hidden; min-height:68vh; display:flex; align-items:center; justify-content:center; }
.journal-attachment-modal__iframe, .journal-attachment-modal__image { width:100%; height:68vh; border:0; background:#fff; }
.journal-attachment-modal__image { object-fit:contain; padding:.85rem; }
.journal-attachment-modal__empty { width:100%; padding:2.25rem; text-align:center; color:#64748b; }
.journal-attachment-modal__toolbar { display:flex; justify-content:space-between; align-items:center; gap:.75rem; padding:0 0 1rem; flex-wrap:wrap; }
.journal-attachment-modal__toolbar .btn { min-width:150px; }
@media (max-width:767.98px) {
  .journal-attachment-modal { padding:.75rem; }
  .journal-attachment-modal__dialog { max-height:calc(100vh - 1.5rem); border-radius:18px; }
  .journal-attachment-modal__header, .journal-attachment-modal__body { padding-left:1rem; padding-right:1rem; }
  .journal-attachment-modal__frame, .journal-attachment-modal__iframe, .journal-attachment-modal__image { min-height:56vh; height:56vh; }
  .journal-attachment-actions { justify-content:stretch; }
  .journal-attachment-actions .btn { flex:1 1 100%; }
}
</style>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Detail Jurnal</h1>
        <p class="text-secondary mb-0">Lihat rincian jurnal umum, template cetak, dan lampiran bukti transaksi.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-light">Kembali</a>
        <a href="<?= e(base_url('/journals/create?duplicate_id=' . (int) $header['id'])) ?>" class="btn btn-outline-secondary">Duplikat</a>
        <a href="<?= e(base_url('/journals/print?id=' . (int) $header['id'])) ?>" target="_blank" class="btn btn-outline-info">Cetak Standar</a>
        <?php if (journal_is_receipt_enabled($header)): ?>
            <a href="<?= e(base_url('/journals/print-receipt?id=' . (int) $header['id'])) ?>" target="_blank" class="btn btn-outline-success">Cetak Kwitansi</a>
        <?php endif; ?>
        <?php if ($periodIsOpen): ?>
            <a href="<?= e(base_url('/journals/edit?id=' . (int) $header['id'])) ?>" class="btn btn-primary">Edit Jurnal</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
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
            <div class="card-body">
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
    <div class="card-body">
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
            <table class="table table-dark align-middle mb-0 journal-entry-table">
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
    <div class="card-body">
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
            <div class="alert alert-dark mb-0">Belum ada lampiran untuk jurnal ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
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
                                <?php
                                $previewKind = $attachmentPreviewKind($attachment);
                                $downloadUrl = base_url('/journals/attachments/download?id=' . (int) $attachment['id']);
                                $previewUrl = $downloadUrl . '&mode=preview';
                                ?>
                                <div class="journal-attachment-actions">
                                    <?php if ($previewKind !== ''): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary journal-attachment-preview-trigger"
                                            data-journal-preview
                                            data-preview-kind="<?= e($previewKind) ?>"
                                            data-preview-title="<?= e($displayName) ?>"
                                            data-preview-original="<?= e((string) ($attachment['original_name'] ?? '-')) ?>"
                                            data-preview-url="<?= e($previewUrl) ?>"
                                            data-download-url="<?= e($downloadUrl) ?>"
                                        >Pratinjau</button>
                                    <?php endif; ?>
                                    <a href="<?= e($downloadUrl) ?>" class="btn btn-sm btn-outline-info">Unduh</a>
                                    <?php if ($periodIsOpen && ($attachmentFeatureStatus['enabled'] ?? false)): ?>
                                        <form method="post" action="<?= e(base_url('/journals/attachments/delete?id=' . (int) $attachment['id'])) ?>" onsubmit="return confirm('Hapus lampiran ini?');" class="m-0">
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
        <div class="journal-attachment-modal" id="journalAttachmentModal" hidden aria-hidden="true">
    <div class="journal-attachment-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="journalAttachmentModalTitle">
        <div class="journal-attachment-modal__header">
            <div>
                <h3 class="journal-attachment-modal__title" id="journalAttachmentModalTitle">Pratinjau bukti transaksi</h3>
                <div class="journal-attachment-modal__meta" id="journalAttachmentModalMeta">File lampiran jurnal akan ditampilkan di sini.</div>
            </div>
            <button type="button" class="journal-attachment-modal__close" id="journalAttachmentModalClose" aria-label="Tutup pratinjau">&times;</button>
        </div>
        <div class="journal-attachment-modal__body">
            <div class="journal-attachment-modal__toolbar">
                <div class="small text-secondary">Pratinjau mendukung PDF, JPG, JPEG, PNG, dan WEBP.</div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="#" class="btn btn-outline-light" id="journalAttachmentModalOpenTab" target="_blank" rel="noopener">Buka Tab Baru</a>
                    <a href="#" class="btn btn-primary" id="journalAttachmentModalDownload">Unduh File</a>
                </div>
            </div>
            <div class="journal-attachment-modal__frame">
                <iframe class="journal-attachment-modal__iframe" id="journalAttachmentModalPdf" title="Pratinjau PDF lampiran jurnal" hidden></iframe>
                <img class="journal-attachment-modal__image" id="journalAttachmentModalImage" alt="Pratinjau gambar lampiran jurnal" hidden>
                <div class="journal-attachment-modal__empty" id="journalAttachmentModalFallback" hidden>Browser ini belum dapat menampilkan pratinjau file. Gunakan tombol <strong>Buka Tab Baru</strong> atau <strong>Unduh File</strong>.</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('journalAttachmentModal');
    if (!modal) {
        return;
    }

    var closeButton = document.getElementById('journalAttachmentModalClose');
    var titleNode = document.getElementById('journalAttachmentModalTitle');
    var metaNode = document.getElementById('journalAttachmentModalMeta');
    var pdfFrame = document.getElementById('journalAttachmentModalPdf');
    var imageNode = document.getElementById('journalAttachmentModalImage');
    var fallbackNode = document.getElementById('journalAttachmentModalFallback');
    var openTabNode = document.getElementById('journalAttachmentModalOpenTab');
    var downloadNode = document.getElementById('journalAttachmentModalDownload');
    var lastFocusedElement = null;
    var previousOverflow = '';

    function resetPreview() {
        pdfFrame.hidden = true;
        imageNode.hidden = true;
        fallbackNode.hidden = true;
        pdfFrame.removeAttribute('src');
        imageNode.removeAttribute('src');
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        resetPreview();
        document.body.style.overflow = previousOverflow;
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    function openModal(trigger) {
        lastFocusedElement = trigger;
        previousOverflow = document.body.style.overflow || '';
        document.body.style.overflow = 'hidden';

        var previewKind = trigger.getAttribute('data-preview-kind') || '';
        var previewTitle = trigger.getAttribute('data-preview-title') || 'Pratinjau bukti transaksi';
        var previewOriginal = trigger.getAttribute('data-preview-original') || '';
        var previewUrl = trigger.getAttribute('data-preview-url') || '#';
        var downloadUrl = trigger.getAttribute('data-download-url') || previewUrl;

        titleNode.textContent = previewTitle;
        metaNode.textContent = previewOriginal !== '' ? previewOriginal : 'Lampiran jurnal';
        openTabNode.href = previewUrl;
        downloadNode.href = downloadUrl;

        resetPreview();

        if (previewKind === 'pdf') {
            pdfFrame.src = previewUrl;
            pdfFrame.hidden = false;
        } else if (previewKind === 'image') {
            imageNode.src = previewUrl;
            imageNode.hidden = false;
        } else {
            fallbackNode.hidden = false;
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        closeButton.focus();
    }

    document.querySelectorAll('[data-journal-preview]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openModal(trigger);
        });
    });

    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>

<?php endif; ?>
    </div>
</div>