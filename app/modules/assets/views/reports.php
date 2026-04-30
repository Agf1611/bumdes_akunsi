<?php declare(strict_types=1); ?>
<?php $auditSummary = is_array($auditSummary ?? null) ? $auditSummary : []; ?>
<section class="asset-page asset-page--reports module-page">
<div class="asset-hero-card mb-4">
    <div class="asset-hero-card__main">
        <div>
            <div class="asset-hero-card__eyebrow">Laporan / Asset</div>
            <h1 class="asset-hero-card__title">Laporan Aset</h1>
            <p class="asset-hero-card__subtitle">Kelola, pantau, dan analisis seluruh aset BUMDes dengan tampilan yang lebih rapi dan mudah dibaca.</p>
        </div>
        <div class="asset-hero-card__actions">
            <a href="<?= e(base_url('/assets/reports/print?' . asset_filter_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
            <a href="<?= e(base_url('/assets/reports/pdf?' . asset_filter_query($filters))) ?>" target="_blank" class="btn btn-outline-light">PDF</a>
            <a href="<?= e(base_url('/assets')) ?>" class="btn btn-primary">Kembali ke Master</a>
        </div>
    </div>

    <div class="asset-metric-grid">
        <article class="asset-metric-card" data-accent="blue">
            <div class="asset-metric-card__header">
                <span class="asset-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7h8"></path>
                        <path d="M8 11h8"></path>
                        <path d="M8 15h5"></path>
                        <path d="M6 3h9l3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z"></path>
                    </svg>
                </span>
                <div class="asset-metric-card__label">Jumlah Register</div>
            </div>
            <div class="asset-metric-card__value"><?= e((string) number_format((int) ($summary['asset_count'] ?? 0), 0, ',', '.')) ?></div>
            <div class="asset-metric-card__meta">Aset tercatat</div>
        </article>
        <article class="asset-metric-card" data-accent="green">
            <div class="asset-metric-card__header">
                <span class="asset-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 3 7 4v10l-7 4-7-4V7l7-4Z"></path>
                        <path d="m12 12 7-4"></path>
                        <path d="M12 12 5 8"></path>
                        <path d="M12 12v9"></path>
                    </svg>
                </span>
                <div class="asset-metric-card__label">Total Qty</div>
            </div>
            <div class="asset-metric-card__value"><?= e((string) number_format((float) ($summary['total_quantity'] ?? 0), 0, ',', '.')) ?></div>
            <div class="asset-metric-card__meta">Total kuantitas</div>
        </article>
        <article class="asset-metric-card" data-accent="orange">
            <div class="asset-metric-card__header">
                <span class="asset-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 12 2 2 4-4"></path>
                        <path d="M12 3 5 6v6c0 4.2 2.7 7.9 7 9 4.3-1.1 7-4.8 7-9V6l-7-3Z"></path>
                    </svg>
                </span>
                <div class="asset-metric-card__label">Aset Aktif</div>
            </div>
            <div class="asset-metric-card__value"><?= e((string) number_format((int) ($summary['active_count'] ?? 0), 0, ',', '.')) ?></div>
            <div class="asset-metric-card__meta">Aset aktif</div>
        </article>
        <article class="asset-metric-card asset-metric-card--wide" data-accent="violet">
            <div class="asset-metric-card__header">
                <span class="asset-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7h18"></path>
                        <path d="M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"></path>
                        <path d="M8 14h8"></path>
                        <path d="M8 18h4"></path>
                    </svg>
                </span>
                <div class="asset-metric-card__label">Nilai Perolehan</div>
            </div>
            <div class="asset-metric-card__value asset-metric-card__value--currency"><?= e(asset_currency((float) ($summary['total_cost'] ?? 0))) ?></div>
            <div class="asset-metric-card__meta">Total nilai perolehan</div>
        </article>
        <article class="asset-metric-card asset-metric-card--wide" data-accent="sky">
            <div class="asset-metric-card__header">
                <span class="asset-metric-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19h16"></path>
                        <path d="M6 19V9"></path>
                        <path d="M12 19V5"></path>
                        <path d="M18 19v-7"></path>
                    </svg>
                </span>
                <div class="asset-metric-card__label">Nilai Buku</div>
            </div>
            <div class="asset-metric-card__value asset-metric-card__value--currency"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></div>
            <div class="asset-metric-card__meta">Pembanding <?= e(asset_currency((float) ($summary['comparison_book_value'] ?? 0))) ?></div>
        </article>
    </div>
</div>

<div class="card shadow-sm mb-4 border-warning-subtle asset-audit-card">
    <div class="card-body p-4">
        <div class="asset-section-head asset-section-head--inline mb-3">
            <div>
                <h2 class="asset-section-head__title">Checkpoint Sinkron Laporan</h2>
                <p class="asset-section-head__subtitle">Sebelum laporan aset dianggap final, pastikan titik audit ini sudah aman.</p>
            </div>
            <div class="asset-section-head__meta">
                Draft susut: <?= e((string) number_format((int) ($auditSummary['depreciation_calculated'] ?? 0), 0, ',', '.')) ?>
                |
                Posted susut: <?= e((string) number_format((int) ($auditSummary['depreciation_posted'] ?? 0), 0, ',', '.')) ?>
            </div>
        </div>
        <div class="asset-chip-list mb-3">
            <span class="asset-chip">Kategori belum dimapping: <?= e((string) ($auditSummary['categories_missing_map'] ?? 0)) ?></span>
            <span class="asset-chip">Perolehan belum tertaut jurnal: <?= e((string) ($auditSummary['acquisition_without_journal'] ?? 0)) ?></span>
            <span class="asset-chip">Status sinkron lama belum rapi: <?= e((string) ($auditSummary['stored_status_mismatch'] ?? 0)) ?></span>
        </div>
        <?php if (($auditSummary['units_missing_register'] ?? []) !== [] || ($auditSummary['units_with_delta'] ?? []) !== []): ?>
            <div class="alert alert-warning mb-0 asset-inline-alert">
                <?php foreach (($auditSummary['units_missing_register'] ?? []) as $row): ?>
                    <div><strong><?= e((string) $row['unit_code']) ?></strong> punya saldo aset tetap di jurnal, tetapi register asetnya belum ada.</div>
                <?php endforeach; ?>
                <?php foreach (($auditSummary['units_with_delta'] ?? []) as $row): ?>
                    <div><strong><?= e((string) $row['unit_code']) ?></strong> masih beda <?= e(asset_currency((float) $row['delta'])) ?> antara menu aset dan jurnal.</div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success mb-0">Untuk unit yang sudah punya register, saldo register aset dan jurnal aset tetap saat ini sudah sejalan.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4 asset-filter-card"><div class="card-body p-4">
<div class="asset-section-head mb-3">
    <div>
        <h2 class="asset-section-head__title">Filter Pencarian</h2>
        <p class="asset-section-head__subtitle">Pilih unit, kategori, status, tanggal, dan nilai buku untuk memfokuskan laporan.</p>
    </div>
</div>
<form method="get" action="<?= e(base_url('/assets/reports')) ?>" class="row g-3 align-items-end asset-filter-form">
    <div class="col-lg-3"><label class="form-label">Pencarian</label><input type="text" class="form-control" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Kode, nama, lokasi"></div>
    <div class="col-lg-2"><label class="form-label">Unit Usaha</label><select class="form-select" name="unit_id"><option value="0">Semua Unit</option><?php foreach (($units ?? []) as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? 0) === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Kategori</label><select class="form-select" name="category_id"><option value="0">Semua Kategori</option><?php foreach (($categories ?? []) as $category): ?><option value="<?= e((string) $category['id']) ?>" <?= (string) ($filters['category_id'] ?? 0) === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Kelompok</label><select class="form-select" name="group"><option value="">Semua Kelompok</option><?php foreach (($groups ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['group'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Sumber Dana</label><select class="form-select" name="funding_source"><option value="">Semua Sumber Dana</option><?php foreach (($fundingSources ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['funding_source'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-1"><label class="form-label">Aktif</label><select class="form-select" name="active"><option value="">Semua</option><option value="1" <?= (string) ($filters['active'] ?? '') === '1' ? 'selected' : '' ?>>Ya</option><option value="0" <?= (string) ($filters['active'] ?? '') === '0' ? 'selected' : '' ?>>Tidak</option></select></div>
    <div class="col-lg-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Semua Status</option><?php foreach (($statuses ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['status'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Dari</label><input type="date" class="form-control" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>"></div>
    <div class="col-lg-2"><label class="form-label">Sampai</label><input type="date" class="form-control" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>"></div>
    <div class="col-lg-2"><label class="form-label">Nilai Buku per</label><input type="date" class="form-control" name="as_of_date" value="<?= e((string) ($filters['as_of_date'] ?? date('Y-m-d'))) ?>"></div>
    <div class="col-lg-2"><label class="form-label">Pembanding per</label><input type="date" class="form-control" name="comparison_date" value="<?= e((string) ($filters['comparison_date'] ?? asset_comparison_date((string) ($filters['as_of_date'] ?? date('Y-m-d'))))) ?>"></div>
    <div class="col-12 d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Tampilkan Laporan</button><a href="<?= e(base_url('/assets/reports')) ?>" class="btn btn-outline-light">Reset</a></div>
</form>
</div></div>

<div class="card shadow-sm asset-table-card">
<div class="asset-table-card__head">
    <div>
        <h2 class="asset-section-head__title mb-1">Daftar Aset</h2>
        <p class="asset-section-head__subtitle mb-0">Menampilkan seluruh aset sesuai filter yang dipilih.</p>
    </div>
    <div class="asset-table-card__meta">Total <?= e((string) number_format((int) ($summary['asset_count'] ?? 0), 0, ',', '.')) ?> aset</div>
</div>
<div class="card-body p-0"><div class="table-responsive coa-table-wrapper"><table class="table table-dark table-hover align-middle mb-0 coa-table asset-table">
<thead><tr><th>Kode</th><th>Nama Aset</th><th class="text-end">Qty</th><th>Satuan</th><th class="text-end">Harga / Unit</th><th class="text-end">Nilai Perolehan</th><th class="text-end">Akum. Susut</th><th class="text-end">Nilai Buku</th><th class="text-end">Nilai Buku Pembanding</th><th class="text-end">Selisih</th><th>Status</th></tr></thead>
<tbody>
<?php if (($rows ?? []) === []): ?>
<tr><td colspan="11" class="text-center text-secondary py-5">Belum ada data aset untuk laporan yang dipilih.</td></tr>
<?php else: foreach ($rows as $row): ?>
<?php $qty = (float) ($row['quantity'] ?? 1); $unitName = (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit'); $unitCost = $qty > 0 ? ((float) ($row['acquisition_cost'] ?? 0) / $qty) : (float) ($row['acquisition_cost'] ?? 0); ?>
<tr>
    <td class="fw-semibold"><?= e((string) $row['asset_code']) ?></td>
    <td>
        <div class="fw-semibold"><?= e((string) $row['asset_name']) ?></div>
        <div class="small text-secondary"><?= e((string) $row['category_name']) ?><?= (string) (($row['subcategory_name'] ?? '') !== '' ? ' · ' . $row['subcategory_name'] : '') ?></div>
        <div class="small text-secondary"><?= e(business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['business_unit_code'] ?? ($row['unit_code'] ?? ''), 'unit_name' => $row['business_unit_name'] ?? ''] : null)) ?></div>
    </td>
    <td class="text-end"><?= e((string) number_format($qty, 0, ',', '.')) ?></td>
    <td><?= e($unitName) ?></td>
    <td class="text-end"><?= e(asset_currency($unitCost)) ?></td>
    <td class="text-end"><?= e(asset_currency((float) $row['acquisition_cost'])) ?></td>
    <td class="text-end"><?= e(asset_currency((float) ($row['current_accumulated_depreciation'] ?? 0))) ?></td>
    <td class="text-end fw-semibold"><?= e(asset_currency((float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0))) ?></td>
    <td class="text-end"><?= e(asset_currency((float) ($row['comparison_book_value'] ?? 0))) ?></td>
    <td class="text-end"><?= e(asset_currency((float) ($row['book_value_delta'] ?? 0))) ?></td>
    <td>
        <span class="badge <?= e(asset_badge_class((string) $row['asset_status'])) ?>"><?= e(asset_status_label((string) $row['asset_status'])) ?></span>
        <div class="small text-secondary mt-1"><?= e(asset_sync_status_label((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?></div>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
<tfoot>
<tr>
    <th colspan="2" class="text-end">Total</th>
    <th class="text-end"><?= e((string) number_format((float) ($summary['total_quantity'] ?? 0), 0, ',', '.')) ?></th>
    <th></th>
    <th></th>
    <th class="text-end"><?= e(asset_currency((float) ($summary['total_cost'] ?? 0))) ?></th>
    <th class="text-end"><?= e(asset_currency((float) ($summary['total_accumulated_depreciation'] ?? 0))) ?></th>
    <th class="text-end"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></th>
    <th class="text-end"><?= e(asset_currency((float) ($summary['comparison_book_value'] ?? 0))) ?></th>
    <th class="text-end"><?= e(asset_currency((float) ($summary['book_value_delta'] ?? 0))) ?></th>
    <th></th>
</tr>
</tfoot>
</table></div></div></div>
</section>
