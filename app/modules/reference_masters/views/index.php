<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($rows ?? []); $rows = $listing['items']; $listingPath = '/reference-masters'; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e((string) ($config['title'] ?? 'Master Referensi')) ?></h1>
        <p class="text-secondary mb-0">Kelola data referensi untuk metadata jurnal agar dropdown bisa diisi langsung dari aplikasi.</p>
    </div>
    <a href="<?= e(base_url('/reference-masters/create?type=' . urlencode((string) $type))) ?>" class="btn btn-primary">Tambah Data</a>
</div>

<?php if (!($isReady ?? false)): ?>
<div class="alert alert-warning mb-4">
    Tabel referensi belum tersedia. Jalankan file <code>database/patch_stage15_reference_masters.sql</code> terlebih dahulu melalui phpMyAdmin.
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= e(base_url('/reference-masters')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="type" value="<?= e((string) $type) ?>">
            <div class="col-lg-4">
                <label class="form-label">Jenis Referensi</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <?php foreach (($types ?? []) as $key => $meta): ?>
                        <option value="<?= e((string) $key) ?>" <?= (string) $type === (string) $key ? 'selected' : '' ?>><?= e((string) ($meta['title'] ?? $key)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Pencarian</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Cari kode, nama, atau keterangan">
            </div>
            <div class="col-lg-2 d-grid">
                <button type="submit" class="btn btn-outline-light">Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle mb-0 coa-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <?php if (($config['type_field'] ?? null) !== null): ?><th>Jenis</th><?php endif; ?>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th class="text-end">Dipakai Jurnal</th>
                        <th class="text-end table-action-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (($rows ?? []) === []): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada data referensi.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <?php $typeValue = ($config['type_field'] ?? null) !== null ? (string) ($row[$config['type_field']] ?? '') : ''; ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) ($row[$config['code_field']] ?? '')) ?></td>
                        <td>
                            <div><?= e((string) ($row[$config['name_field']] ?? '')) ?></div>
                            <?php if (!empty($row['phone'] ?? '')): ?><div class="small text-secondary"><?= e((string) $row['phone']) ?></div><?php endif; ?>
                            <?php if (!empty($row['owner_name'] ?? '')): ?><div class="small text-secondary"><?= e((string) $row['owner_name']) ?></div><?php endif; ?>
                            <?php if (!empty($row['unit_name'] ?? '')): ?><div class="small text-secondary">Satuan: <?= e((string) $row['unit_name']) ?></div><?php endif; ?>
                        </td>
                        <?php if (($config['type_field'] ?? null) !== null): ?>
                            <td><?= e((string) (($config['type_options'][$typeValue] ?? $typeValue) ?: '-')) ?></td>
                        <?php endif; ?>
                        <td><?= e((string) ($row[$config['description_field']] ?? '-')) ?></td>
                        <td><span class="badge <?= ((int) ($row['is_active'] ?? 0) === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= ((int) ($row['is_active'] ?? 0) === 1) ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="text-end"><?= e(number_format((int) ($row['usage_count'] ?? 0), 0, ',', '.')) ?></td>
                        <td class="text-end table-action-col">
                            <div class="table-action-menu">
                                <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button>
                                <div class="table-action-panel">
                                    <a href="<?= e(base_url('/reference-masters/edit?type=' . urlencode((string) $type) . '&id=' . (int) $row['id'])) ?>">Edit referensi</a>
                                    <form method="post" action="<?= e(base_url('/reference-masters/toggle-active?type=' . urlencode((string) $type) . '&id=' . (int) $row['id'])) ?>" class="m-0">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button type="submit"><?= ((int) ($row['is_active'] ?? 0) === 1) ? 'Nonaktifkan referensi' : 'Aktifkan referensi' ?></button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('/reference-masters/delete?type=' . urlencode((string) $type) . '&id=' . (int) $row['id'])) ?>" class="m-0" onsubmit="return confirm('Hapus data referensi ini?');">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button type="submit" class="table-action-danger">Hapus referensi</button>
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
