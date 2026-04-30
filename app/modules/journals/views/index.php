<?php declare(strict_types=1); ?>
<?php
$listing = is_array($listing ?? null) ? $listing : listing_paginate($journals ?? []);
$journals = $listing['items'] ?? ($journals ?? []);
$listingPath = '/journals';
$importErrors = Session::pull('import_errors', []);
$importSuccess = Session::pull('import_success', '');
$importResult = Session::pull('import_result', []);
$importFeedbackUrl = (string) Session::pull('import_feedback_url', '');
$currentRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/journals', PHP_URL_PATH) ?: '/journals';
$currentRequestQuery = parse_url($_SERVER['REQUEST_URI'] ?? '/journals', PHP_URL_QUERY);
$journalBulkRedirect = $currentRequestPath . ($currentRequestQuery ? '?' . $currentRequestQuery : '');
$currentRoleCode = (string) (Auth::user()['role_code'] ?? '');
?>

<style>
.journal-page .journal-table th,
.journal-page .journal-table td {
    vertical-align: top;
    white-space: normal;
}
.journal-page .journal-table th {
    font-size: .78rem;
    letter-spacing: .04em;
}
.journal-page .coa-table-wrapper {
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
    padding-bottom: .35rem;
}
.journal-page .journal-table {
    width: max-content;
    min-width: 100%;
}
.journal-page .journal-table thead .col-actions {
    z-index: 4;
    background: #f8fafc;
}
.journal-page .journal-action-menu[open] {
    z-index: 60;
}
.journal-page .journal-scroll-note {
    padding: .9rem 1rem 0;
    font-size: .82rem;
    color: #64748b;
}
.journal-page .journal-table .col-journal { min-width: 110px; }
.journal-page .journal-table .col-date { min-width: 95px; }
.journal-page .journal-table .col-period { min-width: 96px; }
.journal-page .journal-table .col-unit { min-width: 210px; max-width: 260px; }
.journal-page .journal-table .col-desc { min-width: 260px; max-width: 340px; }
.journal-page .journal-table .col-template { min-width: 130px; }
.journal-page .journal-table .col-attach { min-width: 72px; }
.journal-page .journal-table .col-amount { min-width: 120px; }
.journal-page .journal-table .col-status { min-width: 86px; }
.journal-page .journal-table .col-actions { min-width: 90px; width: 90px; position: sticky; right: 0; z-index: 3; background: #fff; box-shadow: -10px 0 18px rgba(15, 23, 42, .08); }
.journal-page .journal-wrap {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
}
.journal-page .journal-wrap--two {
    -webkit-line-clamp: 2;
}
.journal-page .journal-action-menu {
    position: relative;
    display: inline-block;
}
.journal-page .journal-action-menu summary {
    list-style: none;
}
.journal-page .journal-action-menu summary::-webkit-details-marker {
    display: none;
}
.journal-page .journal-action-trigger {
    min-width: 78px;
    justify-content: center;
    position: relative;
    z-index: 1;
    background: #ffffff !important;
    border-color: rgba(37, 99, 235, .28) !important;
    color: #1e3a8a !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
    opacity: 1 !important;
}
.journal-page .journal-action-trigger:hover,
.journal-page .journal-action-trigger:focus,
.journal-page .journal-action-menu[open] .journal-action-trigger {
    background: #eef4ff !important;
    border-color: rgba(37, 99, 235, .4) !important;
    color: #1d4ed8 !important;
}
.journal-page .journal-action-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 30;
    width: 220px;
    background: #fff;
    border: 1px solid rgba(16,24,40,.08);
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
    padding: .5rem;
}
.journal-page .journal-action-panel a,
.journal-page .journal-action-panel button {
    display: flex;
    width: 100%;
    align-items: center;
    gap: .5rem;
    border: 0;
    background: transparent;
    color: #24324b;
    border-radius: 10px;
    padding: .7rem .8rem;
    text-decoration: none;
    text-align: left;
    font-weight: 500;
}
.journal-page .journal-action-panel a:hover,
.journal-page .journal-action-panel button:hover {
    background: rgba(59, 130, 246, .08);
    color: #1d4ed8;
}
.journal-page .journal-action-panel .journal-action-danger {
    color: #dc2626;
}
.journal-page .journal-action-panel .journal-action-danger:hover {
    background: rgba(220, 38, 38, .08);
    color: #b91c1c;
}
.journal-page .journal-card {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 18px;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
}
.journal-page .journal-card + .journal-card {
    margin-top: 1rem;
}
.journal-page .journal-card__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem 1rem;
}
.journal-page .journal-card__label {
    display: block;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    margin-bottom: .25rem;
}
.journal-page .journal-card__value {
    color: #24324b;
    font-weight: 600;
}
.journal-page .journal-card__desc {
    border-top: 1px dashed rgba(148, 163, 184, .35);
    margin-top: .85rem;
    padding-top: .85rem;
}
.journal-page .journal-card__actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-top: .95rem;
}
.journal-page .journal-card__actions .btn {
    flex: 1 1 auto;
}
.journal-page .journal-bulk-card {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.98));
}
.journal-page .journal-bulk-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: end;
}
.journal-page .journal-bulk-toolbar .form-label {
    font-size: .78rem;
    color: #64748b;
}
.journal-page .journal-bulk-meta {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    align-items: center;
}
.journal-page .journal-bulk-counter {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    background: #eef4ff;
    color: #1d4ed8;
    font-weight: 600;
    font-size: .85rem;
}
.journal-page .journal-bulk-hint {
    font-size: .82rem;
    color: #64748b;
}
.journal-page .journal-bulk-check-col {
    min-width: 52px;
    width: 52px;
    position: sticky;
    left: 0;
    z-index: 4;
    background: #fff;
    box-shadow: 8px 0 18px rgba(15, 23, 42, .05);
}
.journal-page .journal-table thead .journal-bulk-check-col {
    background: #f8fafc;
    z-index: 5;
}
.journal-page .journal-bulk-checkbox {
    width: 1.05rem;
    height: 1.05rem;
}
.journal-page .journal-bulk-select-all {
    width: 1.1rem;
    height: 1.1rem;
}
.journal-page .journal-bulk-row-active td {
    background: rgba(59, 130, 246, .05);
}
.journal-page .journal-bulk-row-active .journal-bulk-check-col,
.journal-page .journal-bulk-row-active .col-actions {
    background: #f8fbff;
}
@media (max-width: 991.98px) {
    .journal-page .journal-card__meta {
        grid-template-columns: 1fr;
    }
    .journal-page .journal-action-panel {
        position: fixed;
        left: 1rem;
        right: 1rem;
        top: auto;
        bottom: 1rem;
        width: auto;
        max-width: none;
    }
}
</style>

<div class="journal-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Jurnal Umum</h1>
            <p class="text-secondary mb-0">Kelola jurnal double-entry, cetak bukti transaksi, dan telusuri riwayat posting dengan lebih rapi.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= e(base_url('/journals/print-list?' . report_filters_query($filters ?? []))) ?>" target="_blank" class="btn btn-outline-info">Cetak Daftar Jurnal</a>
            <a href="<?= e(base_url('/journals/create?template=cash_in')) ?>" class="btn btn-outline-success">Transaksi Cepat</a>
            <a href="<?= e(base_url('/journals/create')) ?>" class="btn btn-primary">Tambah Jurnal</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Import / Export Jurnal</h2>
                    <p class="text-secondary mb-0">Unduh template, impor jurnal dari Excel, atau ekspor jurnal sesuai filter yang sedang aktif.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= e(base_url('/imports/template?type=journal')) ?>" class="btn btn-outline-light">Unduh Template Jurnal</a>
                    <a href="<?= e(base_url('/journals/export?' . report_filters_query($filters ?? []))) ?>" class="btn btn-outline-info">Export Jurnal</a>
                </div>
            </div>

            <?php if ($importSuccess !== ''): ?>
                <div class="alert alert-success"><?= e($importSuccess) ?></div>
            <?php endif; ?>
            <?php if ($importErrors !== []): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-2">Import jurnal dibatalkan karena ditemukan masalah:</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($importErrors as $message): ?>
                            <?php if (str_starts_with((string) $message, 'Unduh file audit perbaikan: ')): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <li><?= e((string) $message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($importFeedbackUrl !== ''): ?>
                        <div class="mt-3">
                            <a href="<?= e($importFeedbackUrl) ?>" class="btn btn-sm btn-outline-light">Unduh File Audit Import</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (($importResult['type'] ?? '') === 'JURNAL' && (int) ($importResult['imported'] ?? 0) > 0): ?>
                <div class="text-secondary small mb-3">Total jurnal dari import terakhir: <strong><?= e((string) (int) $importResult['imported']) ?></strong>.</div>
            <?php endif; ?>

            <?php if (Auth::hasRole(['admin', 'bendahara'])): ?>
                <form method="post" action="<?= e(base_url('/imports/journal')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="redirect_to" value="/journals">
                    <div class="col-12 col-xl-5">
                        <label class="form-label">File Import Jurnal (.xlsx)</label>
                        <input type="file" class="form-control" name="journal_file" accept=".xlsx" required>
                        <div class="form-text text-secondary">Satu file dapat berisi beberapa grup jurnal. Import dibatalkan penuh jika ada grup yang tidak seimbang atau periodenya salah.</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label">Masuk ke Unit Usaha</label>
                        <select class="form-select" name="journal_business_unit_id">
                            <option value="">Global / Semua unit</option>
                            <?php foreach (($unitOptions ?? []) as $unit): ?>
                                <option value="<?= e((string) ($unit['id'] ?? '')) ?>" <?= old('journal_business_unit_id', '') === (string) ($unit['id'] ?? '') ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-secondary">Pilih unit tujuan sebelum import. Kosongkan jika jurnal memang ingin masuk ke global.</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Import Jurnal</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="get" action="<?= e(base_url('/journals')) ?>" class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label">Periode</label>
                    <select name="period_id" class="form-select">
                        <?= report_period_select_options($periods ?? [], (int) ($filters['period_id'] ?? 0), 'Semua periode') ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Unit Usaha</label>
                    <select name="unit_id" class="form-select">
                        <option value="">Semua Unit</option>
                        <?php foreach (($unitOptions ?? []) as $unit): ?>
                            <option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-lg-2 d-grid">
                    <button type="submit" class="btn btn-outline-light">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($journals ?? []) !== []): ?>
    <div class="card shadow-sm mb-4 journal-bulk-card d-none d-lg-block">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Aksi Massal Jurnal</h2>
                    <p class="text-secondary mb-0">Tandai jurnal pada halaman ini lalu pindahkan unit usaha atau hapus sekaligus tanpa edit satu per satu.</p>
                </div>
                <div class="journal-bulk-meta">
                    <span class="journal-bulk-counter"><span id="journalBulkSelectedCount">0</span> jurnal ditandai</span>
                    <button type="button" class="btn btn-outline-light btn-sm" id="journalBulkSelectAll">Tandai semua di halaman</button>
                    <button type="button" class="btn btn-outline-light btn-sm" id="journalBulkClear">Bersihkan tandai</button>
                </div>
            </div>
            <form method="post" action="<?= e(base_url('/journals/bulk-action')) ?>" id="journalBulkForm" class="journal-bulk-toolbar">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="redirect_to" value="<?= e($journalBulkRedirect) ?>">
                <div>
                    <label class="form-label">Aksi</label>
                    <select name="bulk_action" id="journalBulkAction" class="form-select">
                        <option value="">Pilih aksi</option>
                        <option value="change_unit">Ubah unit usaha</option>
                        <option value="delete">Hapus jurnal terpilih</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Unit tujuan</label>
                    <select name="bulk_business_unit_id" id="journalBulkUnit" class="form-select">
                        <option value="">Global / Semua unit</option>
                        <?php foreach (($unitOptions ?? []) as $unit): ?>
                            <option value="<?= e((string) ($unit['id'] ?? '')) ?>"><?= e((string) (($unit['unit_code'] ?? '') . ' - ' . ($unit['unit_name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2 flex-wrap align-self-end">
                    <button type="submit" class="btn btn-primary" id="journalBulkSubmit">Proses jurnal terpilih</button>
                </div>
                <div class="journal-bulk-hint align-self-center">Unit tujuan hanya wajib dipilih saat aksi <strong>Ubah unit usaha</strong>. Jurnal pada periode tutup akan dilewati otomatis.</div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm d-none d-lg-block">
        <div class="journal-scroll-note">Geser tabel ke kanan bila kolom aksi belum terlihat penuh.</div>
        <div class="card-body p-0">
            <div class="table-responsive coa-table-wrapper">
                <table class="table table-dark table-hover align-middle mb-0 coa-table journal-table">
                    <thead>
                    <tr>
                        <th class="journal-bulk-check-col text-center"><input type="checkbox" class="form-check-input journal-bulk-select-all" id="journalBulkHeadCheckbox" aria-label="Tandai semua jurnal di halaman"></th>
                        <th class="col-journal">No. Jurnal</th>
                        <th class="col-date">Tanggal</th>
                        <th class="col-period">Periode</th>
                        <th class="col-unit">Unit Usaha</th>
                        <th class="col-desc">Keterangan</th>
                        <th class="col-template">Template Cetak</th>
                        <th class="col-attach text-center">Lampiran</th>
                        <th class="col-amount text-end">Debit</th>
                        <th class="col-amount text-end">Kredit</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (($journals ?? []) === []): ?>
                        <tr><td colspan="12" class="text-center text-secondary py-5">Belum ada data jurnal.</td></tr>
                    <?php else: foreach ($journals as $journal): ?>
                        <?php
                        $detailUrl = base_url('/journals/detail?id=' . (int) $journal['id']);
                        $printUrl = base_url('/journals/print?id=' . (int) $journal['id']);
                        $receiptUrl = base_url('/journals/print-receipt?id=' . (int) $journal['id']);
                        $duplicateUrl = base_url('/journals/create?duplicate_id=' . (int) $journal['id']);
                        $editUrl = base_url('/journals/edit?id=' . (int) $journal['id']);
                        $unitLabel = ((string) ($journal['unit_name'] ?? '') !== '' ? ($journal['unit_code'] . ' - ' . $journal['unit_name']) : 'Semua / belum ditentukan');
                        $workflowStatus = (string) ($journal['workflow_status'] ?? 'POSTED');
                        $workflowActions = journal_workflow_allowed_actions($workflowStatus, $currentRoleCode);
                        ?>
                        <tr data-journal-id="<?= e((string) (int) $journal['id']) ?>">
                            <td class="journal-bulk-check-col text-center">
                                <input type="checkbox" class="form-check-input journal-bulk-checkbox" name="journal_ids[]" value="<?= e((string) (int) $journal['id']) ?>" form="journalBulkForm" aria-label="Tandai jurnal <?= e((string) $journal['journal_no']) ?>">
                            </td>
                            <td class="fw-semibold col-journal"><?= e((string) $journal['journal_no']) ?></td>
                            <td class="col-date"><?= e(format_id_date((string) $journal['journal_date'])) ?></td>
                            <td class="col-period">
                                <div><?= e((string) $journal['period_name']) ?></div>
                                <div class="small text-secondary"><?= e((string) $journal['period_code']) ?></div>
                            </td>
                            <td class="col-unit" title="<?= e($unitLabel) ?>"><div class="journal-wrap journal-wrap--two"><?= e($unitLabel) ?></div></td>
                            <td class="col-desc" title="<?= e((string) $journal['description']) ?>"><div class="journal-wrap"><?= e((string) $journal['description']) ?></div></td>
                            <td class="col-template">
                                <span class="badge <?= journal_is_receipt_enabled($journal) ? 'text-bg-warning' : 'text-bg-secondary' ?>">
                                    <?= e(journal_print_template_label((string) ($journal['print_template'] ?? 'standard'))) ?>
                                </span>
                            </td>
                            <td class="col-attach text-center"><span class="badge text-bg-dark border"><?= e((string) ((int) ($journal['attachment_count'] ?? 0))) ?></span></td>
                            <td class="col-amount text-end fw-semibold"><?= e(number_format((float) $journal['total_debit'], 2, ',', '.')) ?></td>
                            <td class="col-amount text-end fw-semibold"><?= e(number_format((float) $journal['total_credit'], 2, ',', '.')) ?></td>
                            <td class="col-status">
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge <?= e(journal_workflow_badge_class($workflowStatus)) ?>"><?= e(journal_workflow_label($workflowStatus)) ?></span>
                                    <span class="badge <?= (string) $journal['period_status'] === 'OPEN' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $journal['period_status'] === 'OPEN' ? 'Periode Buka' : 'Periode Tutup' ?></span>
                                </div>
                            </td>
                            <td class="col-actions text-end">
                                <details class="journal-action-menu">
                                    <summary class="btn btn-sm btn-outline-primary journal-action-trigger">Aksi</summary>
                                    <div class="journal-action-panel">
                                        <a href="<?= e($detailUrl) ?>">Detail jurnal</a>
                                        <a href="<?= e($printUrl) ?>" target="_blank" rel="noopener">Cetak standar</a>
                                        <?php if (journal_is_receipt_enabled($journal)): ?>
                                            <a href="<?= e($receiptUrl) ?>" target="_blank" rel="noopener">Cetak kwitansi</a>
                                        <?php endif; ?>
                                        <a href="<?= e($duplicateUrl) ?>">Duplikat jurnal</a>
                                        <?php if ((string) $journal['period_status'] === 'OPEN'): ?>
                                            <a href="<?= e($editUrl) ?>">Edit jurnal</a>
                                            <?php foreach ($workflowActions as $workflowAction): ?>
                                                <form method="post" action="<?= e(base_url('/journals/workflow-action')) ?>" class="m-0 journal-workflow-form" data-workflow-action="<?= e((string) $workflowAction) ?>">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="redirect_to" value="<?= e($journalBulkRedirect) ?>">
                                                    <input type="hidden" name="journal_id" value="<?= e((string) (int) $journal['id']) ?>">
                                                    <input type="hidden" name="workflow_action" value="<?= e((string) $workflowAction) ?>">
                                                    <input type="hidden" name="workflow_reason" value="">
                                                    <button type="submit" class="<?= in_array($workflowAction, ['void', 'reverse'], true) ? 'journal-action-danger' : '' ?>"><?= e(journal_workflow_action_label((string) $workflowAction)) ?></button>
                                                </form>
                                            <?php endforeach; ?>
                                            <form method="post" action="<?= e(base_url('/journals/delete?id=' . (int) $journal['id'])) ?>" onsubmit="return confirm('Hapus jurnal ini?');" class="m-0">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit" class="journal-action-danger">Hapus jurnal</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-lg-none">
        <?php if (($journals ?? []) === []): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-secondary py-5">Belum ada data jurnal.</div>
            </div>
        <?php else: foreach ($journals as $journal): ?>
            <?php
            $detailUrl = base_url('/journals/detail?id=' . (int) $journal['id']);
            $printUrl = base_url('/journals/print?id=' . (int) $journal['id']);
            $receiptUrl = base_url('/journals/print-receipt?id=' . (int) $journal['id']);
            $duplicateUrl = base_url('/journals/create?duplicate_id=' . (int) $journal['id']);
            $editUrl = base_url('/journals/edit?id=' . (int) $journal['id']);
            $unitLabel = ((string) ($journal['unit_name'] ?? '') !== '' ? ($journal['unit_code'] . ' - ' . $journal['unit_name']) : 'Semua / belum ditentukan');
            $workflowStatus = (string) ($journal['workflow_status'] ?? 'POSTED');
            $workflowActions = journal_workflow_allowed_actions($workflowStatus, $currentRoleCode);
            ?>
            <div class="journal-card">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="small text-secondary mb-1">No. Jurnal</div>
                        <div class="fw-semibold text-dark"><?= e((string) $journal['journal_no']) ?></div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <span class="badge <?= e(journal_workflow_badge_class($workflowStatus)) ?>"><?= e(journal_workflow_label($workflowStatus)) ?></span>
                        <span class="badge <?= (string) $journal['period_status'] === 'OPEN' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $journal['period_status'] === 'OPEN' ? 'Periode Buka' : 'Periode Tutup' ?></span>
                    </div>
                </div>
                <div class="journal-card__meta">
                    <div>
                        <span class="journal-card__label">Tanggal</span>
                        <span class="journal-card__value"><?= e(format_id_date((string) $journal['journal_date'])) ?></span>
                    </div>
                    <div>
                        <span class="journal-card__label">Periode</span>
                        <span class="journal-card__value"><?= e((string) $journal['period_name']) ?></span>
                    </div>
                    <div>
                        <span class="journal-card__label">Unit Usaha</span>
                        <span class="journal-card__value"><?= e($unitLabel) ?></span>
                    </div>
                    <div>
                        <span class="journal-card__label">Template</span>
                        <span class="journal-card__value"><?= e(journal_print_template_label((string) ($journal['print_template'] ?? 'standard'))) ?></span>
                    </div>
                    <div>
                        <span class="journal-card__label">Debit</span>
                        <span class="journal-card__value"><?= e(number_format((float) $journal['total_debit'], 2, ',', '.')) ?></span>
                    </div>
                    <div>
                        <span class="journal-card__label">Kredit</span>
                        <span class="journal-card__value"><?= e(number_format((float) $journal['total_credit'], 2, ',', '.')) ?></span>
                    </div>
                </div>
                <div class="journal-card__desc">
                    <span class="journal-card__label">Keterangan</span>
                    <div class="text-dark"><?= e((string) $journal['description']) ?></div>
                    <div class="small text-secondary mt-2">Lampiran: <?= e((string) ((int) ($journal['attachment_count'] ?? 0))) ?></div>
                </div>
                <div class="journal-card__actions">
                    <a href="<?= e($detailUrl) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                    <a href="<?= e($printUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info">Cetak</a>
                    <details class="journal-action-menu flex-fill">
                        <summary class="btn btn-sm btn-outline-primary journal-action-trigger d-flex">Menu</summary>
                        <div class="journal-action-panel">
                            <?php if (journal_is_receipt_enabled($journal)): ?>
                                <a href="<?= e($receiptUrl) ?>" target="_blank" rel="noopener">Cetak kwitansi</a>
                            <?php endif; ?>
                            <a href="<?= e($duplicateUrl) ?>">Duplikat jurnal</a>
                            <?php if ((string) $journal['period_status'] === 'OPEN'): ?>
                                <a href="<?= e($editUrl) ?>">Edit jurnal</a>
                                <?php foreach ($workflowActions as $workflowAction): ?>
                                    <form method="post" action="<?= e(base_url('/journals/workflow-action')) ?>" class="m-0 journal-workflow-form" data-workflow-action="<?= e((string) $workflowAction) ?>">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="redirect_to" value="<?= e($journalBulkRedirect) ?>">
                                        <input type="hidden" name="journal_id" value="<?= e((string) (int) $journal['id']) ?>">
                                        <input type="hidden" name="workflow_action" value="<?= e((string) $workflowAction) ?>">
                                        <input type="hidden" name="workflow_reason" value="">
                                        <button type="submit" class="<?= in_array($workflowAction, ['void', 'reverse'], true) ? 'journal-action-danger' : '' ?>"><?= e(journal_workflow_action_label((string) $workflowAction)) ?></button>
                                    </form>
                                <?php endforeach; ?>
                                <form method="post" action="<?= e(base_url('/journals/delete?id=' . (int) $journal['id'])) ?>" onsubmit="return confirm('Hapus jurnal ini?');" class="m-0">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="journal-action-danger">Hapus jurnal</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </details>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('journalBulkForm');
    if (!form) {
        return;
    }

    const checkboxes = Array.from(document.querySelectorAll('.journal-bulk-checkbox'));
    const headCheckbox = document.getElementById('journalBulkHeadCheckbox');
    const countEl = document.getElementById('journalBulkSelectedCount');
    const selectAllBtn = document.getElementById('journalBulkSelectAll');
    const clearBtn = document.getElementById('journalBulkClear');
    const actionSelect = document.getElementById('journalBulkAction');
    const unitSelect = document.getElementById('journalBulkUnit');

    const updateRows = function () {
        checkboxes.forEach(function (checkbox) {
            const row = checkbox.closest('tr');
            if (!row) {
                return;
            }
            row.classList.toggle('journal-bulk-row-active', checkbox.checked);
        });
    };

    const updateState = function () {
        const selected = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
        if (countEl) {
            countEl.textContent = String(selected);
        }
        if (headCheckbox) {
            headCheckbox.checked = selected > 0 && selected === checkboxes.length;
            headCheckbox.indeterminate = selected > 0 && selected < checkboxes.length;
        }
        if (unitSelect) {
            unitSelect.disabled = !(actionSelect && actionSelect.value === 'change_unit');
        }
        updateRows();
    };

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateState);
    });

    if (headCheckbox) {
        headCheckbox.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = headCheckbox.checked;
            });
            updateState();
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = true;
            });
            updateState();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });
            updateState();
        });
    }

    if (actionSelect) {
        actionSelect.addEventListener('change', updateState);
    }

    form.addEventListener('submit', function (event) {
        const selected = checkboxes.filter(function (checkbox) { return checkbox.checked; });
        if (selected.length === 0) {
            event.preventDefault();
            window.alert('Pilih minimal satu jurnal yang ingin diproses.');
            return;
        }
        if (!actionSelect || actionSelect.value === '') {
            event.preventDefault();
            window.alert('Pilih aksi massal terlebih dahulu.');
            return;
        }
        if (actionSelect.value === 'change_unit' && unitSelect && unitSelect.value === '') {
            const proceedGlobal = window.confirm('Unit tujuan masih kosong. Jurnal terpilih akan dipindahkan ke Global / Semua unit. Lanjutkan?');
            if (!proceedGlobal) {
                event.preventDefault();
                return;
            }
        }
        if (actionSelect.value === 'delete') {
            const confirmed = window.confirm('Hapus semua jurnal yang ditandai? Tindakan ini tidak bisa dibatalkan.');
            if (!confirmed) {
                event.preventDefault();
                return;
            }
        }
    });

    updateState();
})();
</script>

<script>
(function () {
    const menus = Array.from(document.querySelectorAll('.journal-action-menu'));
    if (menus.length === 0) {
        return;
    }

    document.addEventListener('click', function (event) {
        menus.forEach(function (menu) {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            menus.forEach(function (menu) {
                menu.removeAttribute('open');
            });
        }
    });

    menus.forEach(function (menu) {
        const summary = menu.querySelector('summary');
        if (!summary) {
            return;
        }
        summary.addEventListener('click', function (event) {
            event.preventDefault();
            const isOpen = menu.hasAttribute('open');
            menus.forEach(function (otherMenu) {
                otherMenu.removeAttribute('open');
            });
            if (!isOpen) {
                menu.setAttribute('open', 'open');
            }
        });
    });
})();
</script>

<script>
(function () {
    const forms = Array.from(document.querySelectorAll('.journal-workflow-form'));
    if (forms.length === 0) {
        return;
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const action = form.getAttribute('data-workflow-action') || '';
            if (action === 'void' || action === 'reverse') {
                const label = action === 'void' ? 'void' : 'reversal';
                const reason = window.prompt('Tuliskan alasan ' + label + ' jurnal ini:');
                if (reason === null || reason.trim().length < 3) {
                    event.preventDefault();
                    window.alert('Alasan minimal 3 karakter agar jejak audit jelas.');
                    return;
                }
                const input = form.querySelector('input[name="workflow_reason"]');
                if (input) {
                    input.value = reason.trim();
                }
                return;
            }

            const confirmed = window.confirm('Jalankan aksi workflow "' + action + '" untuk jurnal ini?');
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
})();
</script>

<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
