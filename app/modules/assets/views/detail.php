<?php declare(strict_types=1); ?>
<?php
$qty = (float) ($row['quantity'] ?? 1);
$unitName = (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit');
$unitCost = $qty > 0 ? ((float) ($row['acquisition_cost'] ?? 0) / $qty) : (float) ($row['acquisition_cost'] ?? 0);
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Kartu Aset</h1>
        <p class="text-secondary mb-0">Riwayat lengkap aset, qty/satuan, penyusutan, mutasi, dan tautan jurnal referensi.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('/assets')) ?>" class="btn btn-outline-light">Kembali</a>
        <a href="<?= e(base_url('/assets/edit?id=' . (int) $row['id'])) ?>" class="btn btn-outline-warning">Edit Aset</a>
        <form method="post" action="<?= e(base_url('/assets/delete?id=' . (int) $row['id'])) ?>" class="m-0" onsubmit="return confirm('Hapus aset ini dari master aset? Sistem akan menolak jika aset sudah tertaut jurnal atau penyusutan terposting.');">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="back_to" value="<?= e(base_url('/assets')) ?>">
            <button type="submit" class="btn btn-outline-danger">Hapus Aset</button>
        </form>
        <?php if ((string) ($row['entry_mode'] ?? 'ACQUISITION') === 'ACQUISITION' && (int) ($row['linked_journal_id'] ?? 0) === 0): ?>
            <form method="post" action="<?= e(base_url('/assets/journal/acquisition?id=' . (int) $row['id'])) ?>" class="m-0">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn btn-outline-info">Posting Jurnal Perolehan</button>
            </form>
        <?php endif; ?>
        <a href="<?= e(base_url('/assets/card-print?id=' . (int) $row['id'])) ?>" target="_blank" class="btn btn-primary">Print Kartu</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                    <div>
                        <div class="text-secondary small text-uppercase mb-1">Kode Aset</div>
                        <div class="fs-5 fw-bold"><?= e((string) $row['asset_code']) ?></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge <?= e(asset_badge_class((string) $row['asset_status'])) ?>"><?= e(asset_status_label((string) $row['asset_status'])) ?></span>
                        <span class="badge <?= e(asset_condition_badge_class((string) $row['condition_status'])) ?>"><?= e(asset_condition_label((string) $row['condition_status'])) ?></span>
                        <span class="badge <?= e(asset_sync_badge_class((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?>"><?= e(asset_sync_status_label((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?></span>
                        <span class="badge <?= (int) $row['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $row['is_active'] === 1 ? 'Master aktif' : 'Master nonaktif' ?></span>
                    </div>
                </div>
                <h2 class="h4 mb-1"><?= e((string) $row['asset_name']) ?></h2>
                <p class="text-secondary mb-0"><?= e((string) (($row['subcategory_name'] ?? '') !== '' ? $row['subcategory_name'] : 'Tanpa subkategori spesifik')) ?></p>
                <hr>
                <div class="row g-3">
                    <div class="col-md-6"><div class="small text-secondary">Kategori</div><div class="fw-semibold"><?= e((string) $row['category_name']) ?> <span class="text-secondary">/ <?= e(asset_group_label((string) $row['asset_group'])) ?></span></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Unit Usaha</div><div class="fw-semibold"><?= e(business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['business_unit_code'] ?? ($row['unit_code'] ?? ''), 'unit_name' => $row['business_unit_name'] ?? ''] : null)) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Mode Pencatatan</div><div class="fw-semibold"><?= e(asset_entry_mode_label((string) ($row['entry_mode'] ?? 'ACQUISITION'))) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Qty</div><div class="fw-semibold"><?= e((string) number_format($qty, 0, ',', '.')) ?> <?= e($unitName) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Harga per Unit</div><div class="fw-semibold"><?= e(asset_currency($unitCost)) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Tanggal Perolehan</div><div class="fw-semibold"><?= e(asset_safe_date((string) $row['acquisition_date'])) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Lokasi</div><div class="fw-semibold"><?= e((string) (($row['location'] ?? '') !== '' ? $row['location'] : '-')) ?></div></div>
                    <div class="col-md-4"><div class="small text-secondary">Supplier / Sumber Perolehan</div><div class="fw-semibold"><?= e((string) (($row['supplier_name'] ?? '') !== '' ? $row['supplier_name'] : '-')) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Sumber Dana</div><div class="fw-semibold"><?= e(asset_funding_label((string) ($row['source_of_funds'] ?? ''))) ?><?= (string) (($row['funding_source_detail'] ?? '') !== '' ? ' · ' . $row['funding_source_detail'] : '') ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Referensi</div><div class="fw-semibold"><?= e((string) (($row['reference_no'] ?? '') !== '' ? $row['reference_no'] : '-')) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Akun Lawan Perolehan</div><div class="fw-semibold"><?= e((string) (($row['offset_account_code'] ?? '') !== '' ? $row['offset_account_code'] . ' - ' . $row['offset_account_name'] : '-')) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Link Jurnal</div><div class="fw-semibold"><?= e((string) (($row['linked_journal_no'] ?? '') !== '' ? $row['linked_journal_no'] : '-')) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Saldo Awal Akm. Susut</div><div class="fw-semibold"><?= e(asset_currency((float) ($row['opening_accumulated_depreciation'] ?? 0))) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Metode Penyusutan</div><div class="fw-semibold"><?= e((int) ($row['depreciation_allowed'] ?? 0) === 1 ? asset_method_label((string) $row['depreciation_method']) : 'Tidak disusutkan') ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Umur Manfaat</div><div class="fw-semibold"><?= e(asset_months_label(isset($row['useful_life_months']) ? (int) $row['useful_life_months'] : null)) ?></div></div>
                    <div class="col-md-6"><div class="small text-secondary">Mulai Penyusutan</div><div class="fw-semibold"><?= e(asset_safe_date((string) (($row['depreciation_start_date'] ?? '') !== '' ? $row['depreciation_start_date'] : $row['acquisition_date']))) ?></div></div>
                    <div class="col-12"><div class="small text-secondary">Deskripsi</div><div><?= nl2br(e((string) (($row['description'] ?? '') !== '' ? $row['description'] : '-'))) ?></div></div>
                    <div class="col-12"><div class="small text-secondary">Catatan</div><div><?= nl2br(e((string) (($row['notes'] ?? '') !== '' ? $row['notes'] : '-'))) ?></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="row g-3">
            <div class="col-sm-6 col-xl-12"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Total Nilai Perolehan</div><div class="fs-4 fw-bold"><?= e(asset_currency((float) $row['acquisition_cost'])) ?></div><div class="text-secondary small"><?= e((string) number_format($qty, 0, ',', '.')) . ' ' . e($unitName) ?></div></div></div></div>
            <div class="col-sm-6 col-xl-12"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Harga per Unit</div><div class="fs-5 fw-semibold"><?= e(asset_currency($unitCost)) ?></div></div></div></div>
            <div class="col-sm-6 col-xl-12"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Akumulasi Penyusutan</div><div class="fs-5 fw-semibold"><?= e(asset_currency((float) ($row['current_accumulated_depreciation'] ?? 0))) ?></div><div class="text-secondary small">s.d. <?= e(asset_safe_date((string) (($row['current_depreciation_date'] ?? '') !== '' ? $row['current_depreciation_date'] : date('Y-m-d')))) ?></div></div></div></div>
            <div class="col-sm-6 col-xl-12"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Nilai Buku</div><div class="fs-4 fw-bold"><?= e(asset_currency((float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0))) ?></div></div></div></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Mutasi / Perubahan Status</h2>
                <form method="post" action="<?= e(base_url('/assets/mutation-store?id=' . (int) $row['id'])) ?>" class="row g-3">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <div class="col-md-6"><label class="form-label">Tanggal Mutasi</label><input type="date" class="form-control" name="mutation_date" value="<?= e(date('Y-m-d')) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Jenis Mutasi</label><select class="form-select" name="mutation_type" id="mutation_type" required><?php foreach (($mutationTypes ?? []) as $code => $label): if (in_array($code, ['ACQUISITION', 'UPDATE'], true)) { continue; } ?><option value="<?= e($code) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">Unit Tujuan</label><select class="form-select" name="to_business_unit_id"><option value="">Tetap / tidak berubah</option><?php foreach (($units ?? []) as $unit): ?><option value="<?= e((string) $unit['id']) ?>"><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">Lokasi Tujuan</label><input type="text" class="form-control" name="to_location" maxlength="150" placeholder="Isi bila pindah lokasi"></div>
                    <div class="col-md-6"><label class="form-label">Status Baru</label><select class="form-select" name="new_status"><option value="">Ikuti jenis mutasi</option><?php foreach (($statuses ?? []) as $code => $label): ?><option value="<?= e($code) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">Nomor Referensi</label><input type="text" class="form-control" name="reference_no" maxlength="100"></div>
                    <div class="col-md-6"><label class="form-label">Nominal (opsional)</label><input type="text" class="form-control" name="amount" placeholder="Terutama untuk penjualan / pelepasan"></div>
                    <div class="col-md-6"><label class="form-label">Link Jurnal</label><select class="form-select" name="linked_journal_id"><option value="">Tidak ditautkan</option><?php foreach (($journals ?? []) as $journal): ?><option value="<?= e((string) $journal['id']) ?>"><?= e($journal['journal_no'] . ' - ' . format_id_date((string) $journal['journal_date'])) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Catatan Mutasi</label><textarea class="form-control" rows="3" name="notes" maxlength="1000" placeholder="Contoh: modem dipindah ke area RT 03, router diganti, perangkat rusak berat, dijual, dipindah ke gudang."></textarea></div>
                    <div class="col-12 d-grid d-md-flex justify-content-md-end"><button type="submit" class="btn btn-primary">Simpan Mutasi</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-0">
                <div class="table-responsive coa-table-wrapper">
                    <table class="table table-dark table-hover align-middle mb-0 coa-table">
                        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Perubahan</th><th>Referensi</th><th>Dibuat Oleh</th></tr></thead>
                        <tbody>
                        <?php if (($mutations ?? []) === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-5">Belum ada riwayat mutasi aset.</td></tr>
                        <?php else: foreach ($mutations as $mutation): ?>
                            <tr>
                                <td><?= e(asset_safe_date((string) $mutation['mutation_date'])) ?></td>
                                <td><span class="badge text-bg-secondary"><?= e(asset_mutation_label((string) $mutation['mutation_type'])) ?></span></td>
                                <td>
                                    <div class="small text-secondary">Status: <?= e((string) (($mutation['old_status'] ?? '') !== '' ? asset_status_label((string) $mutation['old_status']) : '-')) ?> → <?= e((string) (($mutation['new_status'] ?? '') !== '' ? asset_status_label((string) $mutation['new_status']) : '-')) ?></div>
                                    <div class="small text-secondary">Unit: <?= e((string) (($mutation['from_unit_code'] ?? '') !== '' ? $mutation['from_unit_code'] . ' - ' . $mutation['from_unit_name'] : '-')) ?> → <?= e((string) (($mutation['to_unit_code'] ?? '') !== '' ? $mutation['to_unit_code'] . ' - ' . $mutation['to_unit_name'] : '-')) ?></div>
                                    <div class="small text-secondary">Lokasi: <?= e((string) (($mutation['from_location'] ?? '') !== '' ? $mutation['from_location'] : '-')) ?> → <?= e((string) (($mutation['to_location'] ?? '') !== '' ? $mutation['to_location'] : '-')) ?></div>
                                    <div><?= e((string) (($mutation['notes'] ?? '') !== '' ? $mutation['notes'] : '-')) ?></div>
                                </td>
                                <td>
                                    <div><?= e((string) (($mutation['reference_no'] ?? '') !== '' ? $mutation['reference_no'] : '-')) ?></div>
                                    <div class="small text-secondary"><?= e((string) (($mutation['linked_journal_no'] ?? '') !== '' ? $mutation['linked_journal_no'] : '-')) ?></div>
                                </td>
                                <td><?= e((string) (($mutation['created_by_name'] ?? '') !== '' ? $mutation['created_by_name'] : '-')) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle mb-0 coa-table">
                <thead><tr><th>Periode</th><th>Tanggal</th><th class="text-end">Penyusutan</th><th class="text-end">Akumulasi</th><th class="text-end">Nilai Buku</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (($depreciations ?? []) === []): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-5">Belum ada jadwal penyusutan. Aset biologis atau aset tanpa penyusutan akan tampil kosong di bagian ini.</td></tr>
                <?php else: foreach ($depreciations as $dep): ?>
                    <tr>
                        <td><?= e(str_pad((string) $dep['period_month'], 2, '0', STR_PAD_LEFT) . '/' . $dep['period_year']) ?></td>
                        <td><?= e(asset_safe_date((string) $dep['depreciation_date'])) ?></td>
                        <td class="text-end"><?= e(asset_currency((float) $dep['depreciation_amount'])) ?></td>
                        <td class="text-end"><?= e(asset_currency((float) $dep['accumulated_depreciation'])) ?></td>
                        <td class="text-end fw-semibold"><?= e(asset_currency((float) $dep['book_value'])) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e((string) $dep['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
