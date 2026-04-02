<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Laporan Aset</h1>
        <p class="text-secondary mb-0">Laporan aset dengan qty, satuan, harga per unit, total nilai, penyusutan, dan pembanding nilai buku.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('/assets/reports/print?' . asset_filter_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
        <a href="<?= e(base_url('/assets/reports/pdf?' . asset_filter_query($filters))) ?>" target="_blank" class="btn btn-outline-light">PDF</a>
        <a href="<?= e(base_url('/assets')) ?>" class="btn btn-primary">Kembali ke Master</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-2"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Jumlah Register</div><div class="fs-4 fw-bold"><?= e((string) number_format((int) ($summary['asset_count'] ?? 0), 0, ',', '.')) ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Total Qty</div><div class="fs-4 fw-bold"><?= e((string) number_format((float) ($summary['total_quantity'] ?? 0), 0, ',', '.')) ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Aset Aktif</div><div class="fs-4 fw-bold"><?= e((string) number_format((int) ($summary['active_count'] ?? 0), 0, ',', '.')) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Nilai Perolehan</div><div class="fs-5 fw-bold"><?= e(asset_currency((float) ($summary['total_cost'] ?? 0))) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Nilai Buku</div><div class="fs-5 fw-bold"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></div><div class="text-secondary small">Pembanding <?= e(asset_currency((float) ($summary['comparison_book_value'] ?? 0))) ?></div></div></div></div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
<form method="get" action="<?= e(base_url('/assets/reports')) ?>" class="row g-3 align-items-end">
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
    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Tampilkan Laporan</button><a href="<?= e(base_url('/assets/reports')) ?>" class="btn btn-outline-light">Reset</a></div>
</form>
</div></div>

<div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive coa-table-wrapper"><table class="table table-dark table-hover align-middle mb-0 coa-table">
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
