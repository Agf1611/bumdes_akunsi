<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Penyusutan Aset</h1>
        <p class="text-secondary mb-0">Metode garis lurus per bulan. Aset biologis atau kategori non-penyusutan tidak akan masuk ke register ini.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('/assets')) ?>" class="btn btn-outline-light">Master Aset</a>
        <form method="post" action="<?= e(base_url('/assets/depreciation/rebuild')) ?>" class="m-0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn-primary">Hitung Ulang Penyusutan</button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Baris Register</div><div class="fs-3 fw-bold"><?= e((string) number_format((int) ($summary['row_count'] ?? 0), 0, ',', '.')) ?></div></div></div></div>
    <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Penyusutan Periode Terfilter</div><div class="fs-5 fw-semibold"><?= e(asset_currency((float) ($summary['total_depreciation'] ?? 0))) ?></div></div></div></div>
    <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Nilai Buku Akhir</div><div class="fs-5 fw-semibold"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></div></div></div></div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
<form method="get" action="<?= e(base_url('/assets/depreciation')) ?>" class="row g-3 align-items-end">
    <div class="col-lg-2"><label class="form-label">Unit</label><select class="form-select" name="unit_id"><option value="0">Semua Unit</option><?php foreach (($units ?? []) as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? 0) === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Kategori</label><select class="form-select" name="category_id"><option value="0">Semua Kategori</option><?php foreach (($categories ?? []) as $category): ?><option value="<?= e((string) $category['id']) ?>" <?= (string) ($filters['category_id'] ?? 0) === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Kelompok</label><select class="form-select" name="group"><option value="">Semua Kelompok</option><?php foreach (($groups ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['group'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Semua Status</option><?php foreach (($statuses ?? []) as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) ($filters['status'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Dari</label><input type="date" class="form-control" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>"></div>
    <div class="col-lg-2"><label class="form-label">Sampai</label><input type="date" class="form-control" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>"></div>
    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Terapkan</button><a href="<?= e(base_url('/assets/depreciation')) ?>" class="btn btn-outline-light">Reset</a></div>
</form>
</div></div>

<div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive coa-table-wrapper"><table class="table table-dark table-hover align-middle mb-0 coa-table">
<thead><tr><th>Tanggal</th><th>Kode Aset</th><th>Nama Aset</th><th>Unit</th><th class="text-end">Penyusutan</th><th class="text-end">Akumulasi</th><th class="text-end">Nilai Buku</th></tr></thead>
<tbody>
<?php if (($rows ?? []) === []): ?>
<tr><td colspan="7" class="text-center text-secondary py-5">Belum ada data penyusutan untuk filter yang dipilih.</td></tr>
<?php else: foreach ($rows as $row): ?>
<tr>
    <td><?= e(asset_safe_date((string) $row['depreciation_date'])) ?></td>
    <td class="fw-semibold"><?= e((string) $row['asset_code']) ?></td>
    <td><div class="fw-semibold"><?= e((string) $row['asset_name']) ?></div><div class="small text-secondary"><?= e((string) $row['category_name']) ?></div></td>
    <td><?= e(business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['unit_code'], 'unit_name' => $row['unit_name']] : null)) ?></td>
    <td class="text-end"><?= e(asset_currency((float) $row['depreciation_amount'])) ?></td>
    <td class="text-end"><?= e(asset_currency((float) $row['accumulated_depreciation'])) ?></td>
    <td class="text-end fw-semibold"><?= e(asset_currency((float) $row['book_value'])) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table></div></div></div>
