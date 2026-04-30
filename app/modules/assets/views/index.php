<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($rows ?? []); $rows = $listing['items']; $listingPath = '/assets'; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<?php $auditSummary = is_array($auditSummary ?? null) ? $auditSummary : []; ?>
<section class="asset-page asset-page--index module-page">
<div class="asset-hero-card mb-4">
    <div class="asset-hero-card__main">
        <div>
            <div class="asset-hero-card__eyebrow">Laporan / Asset</div>
            <h1 class="asset-hero-card__title">Master Aset</h1>
            <p class="asset-hero-card__subtitle">Register aset BUMDes yang lebih rapi dengan qty, satuan, harga per unit, total nilai, lokasi, unit usaha, dan status sinkron jurnal.</p>
        </div>
        <div class="asset-hero-card__actions">
            <a href="<?= e(base_url('/assets/template')) ?>" class="btn btn-outline-light">Unduh Template XLSX</a>
            <a href="<?= e(base_url('/assets/export?' . asset_filter_query($filters))) ?>" class="btn btn-outline-light">Export XLSX</a>
            <a href="<?= e(base_url('/assets/create')) ?>" class="btn btn-primary">Tambah Aset</a>
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
                <div class="asset-metric-card__label">Master Aktif</div>
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
            <div class="asset-metric-card__meta">Setelah akumulasi penyusutan</div>
        </article>
    </div>
</div>

<?php if (($importErrors ?? []) !== []): ?>
    <div class="alert alert-danger shadow-sm">
        <div class="fw-semibold mb-2">Import aset menemukan masalah:</div>
        <ul class="mb-0">
            <?php foreach ($importErrors as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (($importSuccess ?? '') !== ''): ?>
    <div class="alert alert-success shadow-sm"><?= e((string) $importSuccess) ?></div>
<?php endif; ?>

<?php if (($importResult ?? []) !== []): ?>
    <div class="alert alert-info shadow-sm">
        <div class="d-flex flex-wrap gap-3">
            <span><strong>Dibuat:</strong> <?= e((string) ($importResult['created'] ?? 0)) ?></span>
            <span><strong>Diperbarui:</strong> <?= e((string) ($importResult['updated'] ?? 0)) ?></span>
            <span><strong>Dilewati:</strong> <?= e((string) ($importResult['skipped'] ?? 0)) ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4 border-warning-subtle asset-audit-card">
    <div class="card-body p-4">
        <div class="asset-section-head asset-section-head--inline mb-3">
            <div>
                <h2 class="asset-section-head__title">Audit Sinkron Aset</h2>
                <p class="asset-section-head__subtitle">Panel ini menunjukkan titik yang paling sering membuat register aset dan laporan keuangan terasa tidak nyambung.</p>
            </div>
            <div class="asset-section-head__meta">
                Opening: <?= e((string) number_format((int) ($auditSummary['opening_count'] ?? 0), 0, ',', '.')) ?> aset
                |
                Acquisition: <?= e((string) number_format((int) ($auditSummary['acquisition_count'] ?? 0), 0, ',', '.')) ?> aset
            </div>
        </div>
        <div class="asset-audit-grid mb-3">
            <article class="asset-audit-item"><div class="asset-audit-item__label">Kategori Belum Dimapping</div><div class="asset-audit-item__value"><?= e((string) number_format((int) ($auditSummary['categories_missing_map'] ?? 0), 0, ',', '.')) ?></div><div class="asset-audit-item__meta">Kategori tanpa akun aset, akun akumulasi, atau akun beban susut.</div></article>
            <article class="asset-audit-item"><div class="asset-audit-item__label">Perolehan Belum Bertaut Jurnal</div><div class="asset-audit-item__value"><?= e((string) number_format((int) ($auditSummary['acquisition_without_journal'] ?? 0), 0, ',', '.')) ?></div><div class="asset-audit-item__meta">Aset baru yang masih rawan beda dengan buku besar.</div></article>
            <article class="asset-audit-item"><div class="asset-audit-item__label">Status Sinkron Lama Tidak Konsisten</div><div class="asset-audit-item__value"><?= e((string) number_format((int) ($auditSummary['stored_status_mismatch'] ?? 0), 0, ',', '.')) ?></div><div class="asset-audit-item__meta">Data lama tertaut jurnal tetapi status tersimpan belum `POSTED`.</div></article>
            <article class="asset-audit-item"><div class="asset-audit-item__label">Penyusutan Belum Diposting</div><div class="asset-audit-item__value"><?= e((string) number_format((int) ($auditSummary['depreciation_calculated'] ?? 0), 0, ',', '.')) ?></div><div class="asset-audit-item__meta">Sudah dihitung di register, belum masuk jurnal/laporan.</div></article>
        </div>

        <?php if (($auditSummary['units_missing_register'] ?? []) !== [] || ($auditSummary['units_with_delta'] ?? []) !== []): ?>
            <div class="alert alert-warning mb-0 asset-inline-alert">
                <?php foreach (($auditSummary['units_missing_register'] ?? []) as $row): ?>
                    <div><strong><?= e((string) $row['unit_code']) ?></strong> punya saldo aset tetap di jurnal sebesar <?= e(asset_currency((float) $row['ledger_total'])) ?>, tetapi belum ada register aset di menu aset.</div>
                <?php endforeach; ?>
                <?php foreach (($auditSummary['units_with_delta'] ?? []) as $row): ?>
                    <div><strong><?= e((string) $row['unit_code']) ?></strong> masih selisih <?= e(asset_currency((float) $row['delta'])) ?> antara register aset dan saldo akun aset tetap.</div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success mb-0">Register aset per unit yang sudah terisi saat ini sudah sejalan dengan saldo akun aset tetap di jurnal.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4 asset-filter-card">
    <div class="card-body p-4">
        <div class="asset-section-head asset-section-head--inline mb-3">
            <div>
                <h2 class="asset-section-head__title">Import, Export, dan Struktur Template</h2>
                <p class="asset-section-head__subtitle">Template aset sekarang fokus ke kebutuhan BUMDes: qty, satuan, harga per unit, total nilai, status sinkron jurnal, dan catatan yang mudah diaudit.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e(base_url('/assets/template')) ?>" class="btn btn-outline-light">Unduh Template XLSX</a>
                <a href="<?= e(base_url('/assets/export?' . asset_filter_query($filters))) ?>" class="btn btn-outline-light">Export XLSX</a>
            </div>
        </div>
        <form method="post" action="<?= e(base_url('/assets/import')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="col-lg-7">
                <label class="form-label">File Import Aset (.xlsx / .csv)</label>
                <input type="file" class="form-control" name="asset_file" accept=".xlsx,.csv" required>
                <div class="form-text text-secondary">Isi qty dan satuan dengan benar. Harga per unit akan dibaca dari total nilai perolehan dibagi qty. Untuk perolehan baru, isi akun lawan dan hubungkan ke jurnal bila sudah tersedia.</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Status Pengembangan</label>
                <input type="text" class="form-control" value="Struktur qty/satuan siap. Sinkron otomatis dari jurnal butuh patch lanjutan modul jurnal." readonly>
            </div>
            <div class="col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary">Import Aset</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4 asset-filter-card">
    <div class="card-body p-4">
        <div class="asset-section-head mb-3">
            <div>
                <h2 class="asset-section-head__title">Filter Pencarian</h2>
                <p class="asset-section-head__subtitle">Cari berdasarkan kode, unit usaha, kategori, sumber dana, kondisi, tanggal, dan status aset.</p>
            </div>
        </div>
        <form method="get" action="<?= e(base_url('/assets')) ?>" class="row g-3 align-items-end asset-filter-form">
            <div class="col-lg-3"><label class="form-label">Pencarian</label><input type="text" class="form-control" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Kode, nama, supplier, referensi, lokasi"></div>
            <div class="col-lg-2"><label class="form-label">Unit Usaha</label><select class="form-select" name="unit_id"><option value="0">Semua Unit</option><?php foreach (($units ?? []) as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? 0) === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Kategori</label><select class="form-select" name="category_id"><option value="0">Semua Kategori</option><?php foreach (($categories ?? []) as $category): ?><option value="<?= e((string) $category['id']) ?>" <?= (string) ($filters['category_id'] ?? 0) === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Sumber Dana</label><select class="form-select" name="funding_source"><option value="">Semua Sumber Dana</option><?php foreach (($fundingSources ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['funding_source'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-1"><label class="form-label">Aktif</label><select class="form-select" name="active"><option value="">Semua</option><option value="1" <?= (string) ($filters['active'] ?? '') === '1' ? 'selected' : '' ?>>Ya</option><option value="0" <?= (string) ($filters['active'] ?? '') === '0' ? 'selected' : '' ?>>Tidak</option></select></div>
            <div class="col-lg-2"><label class="form-label">Kelompok</label><select class="form-select" name="group"><option value="">Semua Kelompok</option><?php foreach (($groups ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['group'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Semua Status</option><?php foreach (($statuses ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['status'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Kondisi</label><select class="form-select" name="condition"><option value="">Semua Kondisi</option><?php foreach (($conditions ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['condition'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Tanggal Perolehan Dari</label><input type="date" class="form-control" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>"></div>
            <div class="col-lg-2"><label class="form-label">Tanggal Perolehan s.d.</label><input type="date" class="form-control" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>"></div>
            <div class="col-lg-2"><label class="form-label">Nilai Buku per</label><input type="date" class="form-control" name="as_of_date" value="<?= e((string) ($filters['as_of_date'] ?? date('Y-m-d'))) ?>"></div>
            <div class="col-lg-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="<?= e(base_url('/assets')) ?>" class="btn btn-outline-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm asset-table-card">
    <div class="asset-table-card__head">
        <div>
            <h2 class="asset-section-head__title mb-1">Daftar Aset</h2>
            <p class="asset-section-head__subtitle mb-0">Menampilkan register aset sesuai filter aktif.</p>
        </div>
        <div class="asset-table-card__meta">Total <?= e((string) number_format((int) ($listing['total'] ?? 0), 0, ',', '.')) ?> aset</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle mb-0 coa-table asset-table">
                <thead>
                    <tr>
                        <th>Kode Aset</th>
                        <th>Nama / Kategori</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga / Unit</th>
                        <th class="text-end">Total Nilai</th>
                        <th>Lokasi / Unit</th>
                        <th>Status</th>
                        <th class="text-end table-action-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (($rows ?? []) === []): ?>
                    <tr><td colspan="9" class="text-center text-secondary py-5">Belum ada data aset untuk filter yang dipilih.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <?php
                        $qty = (float) ($row['quantity'] ?? 1);
                        $unitName = (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit');
                        $unitCost = $qty > 0 ? ((float) ($row['acquisition_cost'] ?? 0) / $qty) : (float) ($row['acquisition_cost'] ?? 0);
                    ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= e((string) $row['asset_code']) ?>
                            <div class="small text-secondary mt-1"><?= e(asset_entry_mode_label((string) ($row['entry_mode'] ?? 'ACQUISITION'))) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e((string) $row['asset_name']) ?></div>
                            <div class="small text-secondary"><?= e((string) $row['category_name']) ?><?= (string) (($row['subcategory_name'] ?? '') !== '' ? ' · ' . $row['subcategory_name'] : '') ?></div>
                            <div class="small text-secondary mt-1">Dana: <?= e(asset_funding_label((string) ($row['source_of_funds'] ?? ''))) ?><?= (string) (($row['funding_source_detail'] ?? '') !== '' ? ' · ' . $row['funding_source_detail'] : '') ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= e((string) number_format($qty, 0, ',', '.')) ?></td>
                        <td><?= e($unitName) ?></td>
                        <td class="text-end"><?= e(asset_currency($unitCost)) ?></td>
                        <td class="text-end fw-semibold">
                            <?= e(asset_currency((float) $row['acquisition_cost'])) ?>
                            <div class="small text-secondary"><?= e(asset_currency((float) ($row['current_book_value'] ?? $row['acquisition_cost']))) ?> nilai buku</div>
                        </td>
                        <td>
                            <div><?= e((string) (($row['location'] ?? '') !== '' ? $row['location'] : '-')) ?></div>
                            <div class="small text-secondary"><?= e((string) business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['business_unit_code'] ?? ($row['unit_code'] ?? ''), 'unit_name' => $row['business_unit_name'] ?? ''] : null)) ?></div>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge <?= e(asset_badge_class((string) $row['asset_status'])) ?>"><?= e(asset_status_label((string) $row['asset_status'])) ?></span>
                                <span class="badge <?= e(asset_condition_badge_class((string) $row['condition_status'])) ?>"><?= e(asset_condition_label((string) $row['condition_status'])) ?></span>
                                <span class="badge <?= e(asset_sync_badge_class((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?>"><?= e(asset_sync_status_label((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?></span>
                            </div>
                        </td>
                        <td class="text-end table-action-col">
                            <div class="table-action-menu">
                                <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button>
                                <div class="table-action-panel">
                                    <a href="<?= e(base_url('/assets/detail?id=' . (int) $row['id'])) ?>">Detail aset</a>
                                    <a href="<?= e(base_url('/assets/edit?id=' . (int) $row['id'])) ?>">Edit aset</a>
                                    <a href="<?= e(base_url('/assets/card-print?id=' . (int) $row['id'])) ?>" target="_blank" rel="noopener">Cetak kartu aset</a>
                                    <form method="post" action="<?= e(base_url('/assets/delete?id=' . (int) $row['id'])) ?>" class="m-0" onsubmit="return confirm('Hapus aset <?= e((string) $row['asset_code']) ?>? Data hanya boleh dihapus jika belum tertaut jurnal atau penyusutan terposting.');">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="back_to" value="<?= e(base_url('/assets')) ?>">
                                        <button type="submit" class="table-action-danger">Hapus aset</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
</section>
