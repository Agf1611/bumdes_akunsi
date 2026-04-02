<?php declare(strict_types=1); ?>
<?php
$formData = is_array($formData ?? null) ? $formData : [];
$formData['quantity'] = $formData['quantity'] ?? '1';
$formData['unit_name'] = $formData['unit_name'] ?? '';
$action = $row ? base_url('/assets/update?id=' . (int) $row['id']) : base_url('/assets/store');
$selectedCategoryId = (string) ($formData['category_id'] ?? '');
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e($title ?? 'Form Aset') ?></h1>
        <p class="text-secondary mb-0">Lengkapi register aset BUMDes dengan qty, satuan, harga per unit, total nilai, sumber dana, dan tautan jurnal agar ke depan lebih mudah disinkronkan.</p>
    </div>
    <a href="<?= e($row ? base_url('/assets/detail?id=' . (int) $row['id']) : base_url('/assets')) ?>" class="btn btn-outline-light">Kembali</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <form method="post" action="<?= e($action) ?>" class="row g-4">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

            <div class="col-12 col-lg-4">
                <label class="form-label">Mode Pencatatan</label>
                <select class="form-select" name="entry_mode" id="entry_mode">
                    <?php foreach (($entryModes ?? []) as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $formData['entry_mode'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text text-secondary">Saldo awal gunakan OPENING. Pembelian/perolehan baru gunakan ACQUISITION agar siap disambungkan ke jurnal.</div>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Kode Aset</label>
                <input type="text" class="form-control" name="asset_code" value="<?= e((string) $formData['asset_code']) ?>" maxlength="40" required>
                <div class="form-text text-secondary">Gunakan kode stabil, misalnya WIFI-MDM-028 atau WIFI-STARLINK-001.</div>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Nama Aset</label>
                <input type="text" class="form-control" name="asset_name" value="<?= e((string) $formData['asset_name']) ?>" maxlength="160" required>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label">Kategori Aset</label>
                <select class="form-select" name="category_id" id="category_id" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach (($categories ?? []) as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>"
                                data-group="<?= e((string) $category['asset_group']) ?>"
                                data-life="<?= e((string) ($category['default_useful_life_months'] ?? '')) ?>"
                                data-depreciation="<?= (int) ($category['depreciation_allowed'] ?? 1) === 1 ? '1' : '0' ?>"
                                <?= $selectedCategoryId === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['category_name'] . ' (' . asset_group_label((string) $category['asset_group']) . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Subkategori / Tipe Spesifik</label>
                <input type="text" class="form-control" name="subcategory_name" value="<?= e((string) $formData['subcategory_name']) ?>" maxlength="120" placeholder="Contoh: Router, Modem Pelanggan, Starlink, ODP, Kandang">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Unit Usaha</label>
                <select class="form-select" name="business_unit_id">
                    <option value="">Gabungan / lintas unit</option>
                    <?php foreach (($units ?? []) as $unit): ?>
                        <option value="<?= e((string) $unit['id']) ?>" <?= (string) $formData['business_unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-lg-2">
                <label class="form-label">Qty</label>
                <input type="text" class="form-control" name="quantity" id="quantity" value="<?= e((string) $formData['quantity']) ?>" required>
                <div class="form-text text-secondary">Jumlah item / unit.</div>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label">Satuan</label>
                <input type="text" class="form-control" name="unit_name" value="<?= e((string) $formData['unit_name']) ?>" maxlength="30" placeholder="unit / pcs / roll / set">
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label">Tanggal Perolehan</label>
                <input type="date" class="form-control" name="acquisition_date" value="<?= e((string) $formData['acquisition_date']) ?>" required>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Total Nilai Perolehan</label>
                <input type="text" class="form-control" name="acquisition_cost" id="acquisition_cost" value="<?= e((string) $formData['acquisition_cost']) ?>" required>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Harga per Unit</label>
                <input type="text" class="form-control" id="unit_cost_display" value="-" readonly>
                <div class="form-text text-secondary">Dihitung otomatis dari total nilai perolehan dibagi qty.</div>
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Tanggal Saldo Awal</label>
                <input type="date" class="form-control" name="opening_as_of_date" value="<?= e((string) $formData['opening_as_of_date']) ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Akumulasi Susut Awal</label>
                <input type="text" class="form-control" name="opening_accumulated_depreciation" value="<?= e((string) $formData['opening_accumulated_depreciation']) ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Nilai Residu</label>
                <input type="text" class="form-control" name="residual_value" value="<?= e((string) $formData['residual_value']) ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Umur Manfaat (bulan)</label>
                <input type="number" min="0" class="form-control" name="useful_life_months" id="useful_life_months" value="<?= e((string) $formData['useful_life_months']) ?>">
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Metode Penyusutan</label>
                <select class="form-select" name="depreciation_method">
                    <?php foreach (($methods ?? []) as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $formData['depreciation_method'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Mulai Penyusutan</label>
                <input type="date" class="form-control" name="depreciation_start_date" value="<?= e((string) $formData['depreciation_start_date']) ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Aset Disusutkan?</label>
                <select class="form-select" name="depreciation_allowed" id="depreciation_allowed">
                    <option value="1" <?= (string) $formData['depreciation_allowed'] === '1' ? 'selected' : '' ?>>Ya</option>
                    <option value="0" <?= (string) $formData['depreciation_allowed'] === '0' ? 'selected' : '' ?>>Tidak</option>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Status Aktif Master</label>
                <select class="form-select" name="is_active">
                    <option value="1" <?= (string) $formData['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= (string) $formData['is_active'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label">Lokasi Aset</label>
                <input type="text" class="form-control" name="location" value="<?= e((string) $formData['location']) ?>" maxlength="150" placeholder="Gudang, kantor, menara RT 01, kandang utama">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Supplier / Sumber Perolehan</label>
                <input type="text" class="form-control" name="supplier_name" value="<?= e((string) $formData['supplier_name']) ?>" maxlength="150" placeholder="Nama toko, vendor, hibah, swadaya">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Nomor Referensi</label>
                <input type="text" class="form-control" name="reference_no" value="<?= e((string) $formData['reference_no']) ?>" maxlength="100" placeholder="Invoice / BA / kuitansi / nomor dokumen">
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label">Sumber Dana</label>
                <select class="form-select" name="source_of_funds">
                    <?php foreach (($fundingSources ?? []) as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $formData['source_of_funds'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Detail Sumber Dana</label>
                <input type="text" class="form-control" name="funding_source_detail" value="<?= e((string) $formData['funding_source_detail']) ?>" maxlength="150" placeholder="Contoh: Laba usaha WIFI 2025 / Dana Desa tahap 2">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Akun Lawan Perolehan</label>
                <select class="form-select" name="offset_coa_id">
                    <option value="">Pilih akun lawan (kas / bank / utang / modal / hibah)</option>
                    <?php foreach (($coaOptions ?? []) as $coa): ?>
                        <option value="<?= e((string) $coa['id']) ?>" <?= (string) $formData['offset_coa_id'] === (string) $coa['id'] ? 'selected' : '' ?>><?= e($coa['account_code'] . ' - ' . $coa['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text text-secondary">Ini fondasi supaya pembelian aset nanti mudah dihubungkan ke jurnal.</div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label">Link Jurnal (opsional)</label>
                <select class="form-select" name="linked_journal_id">
                    <option value="">Tidak ditautkan</option>
                    <?php foreach (($journals ?? []) as $journal): ?>
                        <option value="<?= e((string) $journal['id']) ?>" <?= (string) $formData['linked_journal_id'] === (string) $journal['id'] ? 'selected' : '' ?>><?= e($journal['journal_no'] . ' - ' . format_id_date((string) $journal['journal_date']) . ' - ' . $journal['description']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text text-secondary">Kalau jurnal pembelian aset sudah dibuat lebih dulu, tautkan di sini agar kartu aset dan jurnal tidak terpisah.</div>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Kondisi Aset</label>
                <select class="form-select" name="condition_status">
                    <?php foreach (($conditions ?? []) as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $formData['condition_status'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Status Aset</label>
                <select class="form-select" name="asset_status">
                    <?php foreach (($statuses ?? []) as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $formData['asset_status'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Ringkasan Penggunaan</label>
                <input type="text" class="form-control" value="<?= e((string) (($formData['business_unit_id'] ?? '') !== '' ? 'Aset dipakai di unit tertentu.' : 'Aset lintas unit / gabungan BUMDes.')) ?>" readonly>
            </div>

            <div class="col-12">
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" rows="3" name="description" maxlength="1000" placeholder="Spesifikasi, tipe, ukuran, seri, kapasitas, atau informasi penting aset."><?= e((string) $formData['description']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Catatan</label>
                <textarea class="form-control" rows="3" name="notes" maxlength="1000" placeholder="Contoh: modem pelanggan 28 unit, instalasi backbone, asset opening hasil migrasi dari pembukuan lama."><?= e((string) $formData['notes']) ?></textarea>
            </div>

            <div class="col-12 border-top pt-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="small text-secondary">
                    Form ini sudah menyiapkan struktur qty, satuan, dan tautan jurnal. Sinkron otomatis penuh dari jurnal ke aset bisa dibuat di tahap berikutnya tanpa mengubah ulang register yang sudah ada.
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= e($row ? base_url('/assets/detail?id=' . (int) $row['id']) : base_url('/assets')) ?>" class="btn btn-outline-light">Batal</a>
                    <button type="submit" class="btn btn-primary"><?= $row ? 'Simpan Perubahan Aset' : 'Simpan Aset Baru' ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var categorySelect = document.getElementById('category_id');
    var lifeInput = document.getElementById('useful_life_months');
    var depreciationSelect = document.getElementById('depreciation_allowed');
    var qtyInput = document.getElementById('quantity');
    var costInput = document.getElementById('acquisition_cost');
    var unitCostDisplay = document.getElementById('unit_cost_display');
    if (!categorySelect || !lifeInput || !depreciationSelect) {
        return;
    }

    function normalizeNumber(value) {
        if (!value) return 0;
        value = String(value).replace(/Rp/gi, '').replace(/\s+/g, '').replace(/\./g, '').replace(',', '.');
        var num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    }

    function updateUnitCost() {
        if (!unitCostDisplay) return;
        var qty = normalizeNumber(qtyInput ? qtyInput.value : '1');
        var cost = normalizeNumber(costInput ? costInput.value : '0');
        if (qty <= 0) {
            unitCostDisplay.value = '-';
            return;
        }
        unitCostDisplay.value = new Intl.NumberFormat('id-ID').format(cost / qty);
    }

    function applyCategoryDefaults() {
        var option = categorySelect.options[categorySelect.selectedIndex];
        if (!option) {
            return;
        }
        var defaultLife = option.getAttribute('data-life') || '';
        var depreciationAllowed = option.getAttribute('data-depreciation') || '1';
        if (defaultLife !== '' && lifeInput.value === '') {
            lifeInput.value = defaultLife;
        }
        if (depreciationAllowed === '0') {
            depreciationSelect.value = '0';
            lifeInput.value = '';
            lifeInput.setAttribute('readonly', 'readonly');
        } else {
            if (depreciationSelect.value === '0') {
                depreciationSelect.value = '1';
            }
            lifeInput.removeAttribute('readonly');
        }
    }

    categorySelect.addEventListener('change', applyCategoryDefaults);
    depreciationSelect.addEventListener('change', function () {
        if (depreciationSelect.value === '0') {
            lifeInput.value = '';
            lifeInput.setAttribute('readonly', 'readonly');
        } else {
            lifeInput.removeAttribute('readonly');
        }
    });
    if (qtyInput) qtyInput.addEventListener('input', updateUnitCost);
    if (costInput) costInput.addEventListener('input', updateUnitCost);

    applyCategoryDefaults();
    updateUnitCost();
})();
</script>
