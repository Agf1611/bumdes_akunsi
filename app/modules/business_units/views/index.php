<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($rows ?? []); $rows = $listing['items']; $listingPath = '/business-units'; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Master Unit Usaha</h1>
        <p class="text-secondary mb-0">Kelola unit usaha yang dapat dipakai pada transaksi, dashboard, dan laporan multi-unit.</p>
    </div>
    <a href="<?= e(base_url('/business-units/create')) ?>" class="btn btn-primary">Tambah Unit Usaha</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= e(base_url('/business-units')) ?>" class="row g-3 align-items-end">
            <div class="col-md-10">
                <label class="form-label">Pencarian</label>
                <input type="text" class="form-control" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari kode atau nama unit usaha">
            </div>
            <div class="col-md-2 d-grid">
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
                        <th>Nama Unit</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th class="text-end">Dipakai Jurnal</th>
                        <th class="text-end table-action-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (($rows ?? []) === []): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-5">Belum ada data unit usaha.</td></tr>
                <?php else: foreach ($rows as $unit): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $unit['unit_code']) ?></td>
                        <td><?= e((string) $unit['unit_name']) ?></td>
                        <td><?= e((string) ($unit['description'] ?? '-')) ?></td>
                        <td><span class="badge <?= (int) $unit['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $unit['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="text-end"><?= e(number_format((int) ($unit['journal_count'] ?? 0), 0, ',', '.')) ?></td>
                        <td class="text-end table-action-col">
                            <div class="table-action-menu">
                                <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button>
                                <div class="table-action-panel">
                                    <a href="<?= e(base_url('/business-units/edit?id=' . (int) $unit['id'])) ?>">Edit unit usaha</a>
                                    <form method="post" action="<?= e(base_url('/business-units/toggle-active?id=' . (int) $unit['id'])) ?>" class="m-0">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button type="submit"><?= (int) $unit['is_active'] === 1 ? 'Nonaktifkan unit usaha' : 'Aktifkan unit usaha' ?></button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('/business-units/delete?id=' . (int) $unit['id'])) ?>" onsubmit="return confirm('Hapus unit usaha ini?');" class="m-0">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button type="submit" class="table-action-danger">Hapus unit usaha</button>
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
