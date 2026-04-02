<?php declare(strict_types=1); ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Kategori Aset</h1>
        <p class="text-secondary mb-0">Kategori bersifat universal untuk publikasi ke BUMDes lain, tetapi tetap relevan untuk aset WIFI dan TERNAK DOMBA.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('/assets')) ?>" class="btn btn-outline-light">Kembali ke Master Aset</a>
        <a href="<?= e(base_url('/assets/categories/create')) ?>" class="btn btn-primary">Tambah Kategori</a>
    </div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <form method="get" action="<?= e(base_url('/assets/categories')) ?>" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Kelompok</label>
            <select class="form-select" name="group">
                <option value="">Semua Kelompok</option>
                <?php foreach (($groups ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($group ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button type="submit" class="btn btn-primary">Tampil</button></div>
        <div class="col-md-2 d-grid"><a href="<?= e(base_url('/assets/categories')) ?>" class="btn btn-outline-light">Reset</a></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive coa-table-wrapper"><table class="table table-dark table-hover align-middle mb-0 coa-table">
    <thead><tr><th>Kode</th><th>Nama Kategori</th><th>Kelompok</th><th>Penyusutan</th><th class="text-end">Umur Default</th><th class="text-end">Dipakai Aset</th><th>Status</th><th class="text-end table-action-col">Aksi</th></tr></thead>
    <tbody>
    <?php if (($rows ?? []) === []): ?>
        <tr><td colspan="8" class="text-center text-secondary py-5">Belum ada kategori aset.</td></tr>
    <?php else: foreach ($rows as $row): ?>
        <tr>
            <td class="fw-semibold"><?= e((string) $row['category_code']) ?></td>
            <td><div class="fw-semibold"><?= e((string) $row['category_name']) ?></div><div class="small text-secondary"><?= e((string) (($row['description'] ?? '') !== '' ? $row['description'] : '-')) ?></div></td>
            <td><?= e(asset_group_label((string) $row['asset_group'])) ?></td>
            <td><?= e((int) ($row['depreciation_allowed'] ?? 0) === 1 ? asset_method_label((string) $row['default_depreciation_method']) : 'Tidak disusutkan') ?></td>
            <td class="text-end"><?= e(asset_months_label(isset($row['default_useful_life_months']) ? (int) $row['default_useful_life_months'] : null)) ?></td>
            <td class="text-end"><?= e(number_format((int) ($row['asset_count'] ?? 0), 0, ',', '.')) ?></td>
            <td><span class="badge <?= (int) ($row['is_active'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
            <td class="text-end table-action-col"><div class="table-action-menu"><button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button><div class="table-action-panel"><a href="<?= e(base_url('/assets/categories/edit?id=' . (int) $row['id'])) ?>">Edit kategori</a><form method="post" action="<?= e(base_url('/assets/categories/toggle-active?id=' . (int) $row['id'])) ?>" class="m-0"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><button type="submit"><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Nonaktifkan kategori' : 'Aktifkan kategori' ?></button></form></div></div></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table></div></div></div>
