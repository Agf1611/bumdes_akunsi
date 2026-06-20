<?php declare(strict_types=1); ?>
<?php
$type = (string) ($type ?? 'employees');
$page = is_array($page ?? null) ? $page : [];
$row = is_array($row ?? null) ? $row : null;
$units = is_array($units ?? null) ? $units : [];
$accounts = is_array($accounts ?? null) ? $accounts : [];
$formData = is_array($formData ?? null) ? $formData : [];
$items = is_array($items ?? null) ? $items : [];
$isEdit = $row !== null;
$action = $isEdit
    ? base_url('/business-operations/update?type=' . urlencode($type) . '&id=' . (int) $row['id'])
    : base_url('/business-operations/store?type=' . urlencode($type));
$back = (string) ($page['route'] ?? '/business-employees');
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$planItems = $items !== [] ? $items : [['item_name' => '', 'quantity' => '1', 'unit_name' => 'unit', 'unit_price' => '0', 'notes' => '']];
?>
<div class="business-operation-page module-page">
    <section class="operation-hero mb-3">
        <div class="operation-hero__icon"><i class="bi <?= e((string) ($page['icon'] ?? 'bi-grid')) ?>" aria-hidden="true"></i></div>
        <div class="operation-hero__copy">
            <div class="module-hero__eyebrow">Kelola Usaha</div>
            <h1 class="module-hero__title"><?= e($title) ?></h1>
            <p class="module-hero__text mb-0"><?= e((string) ($page['description'] ?? 'Lengkapi data operasional unit usaha.')) ?></p>
        </div>
        <div class="operation-hero__actions">
            <a href="<?= e(base_url($back)) ?>" class="btn btn-outline-light"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Kembali</span></a>
        </div>
    </section>

    <form method="post" action="<?= e($action) ?>" class="operation-card operation-form" novalidate>
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <label class="form-label">Unit Usaha</label>
                <select name="business_unit_id" class="form-select">
                    <option value="0">Semua / Pusat</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int) $unit['id'] ?>" <?= (int) ($formData['business_unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>>
                            <?= e(business_unit_label($unit, false)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($type === 'employees'): ?>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Nama Karyawan <span class="text-danger">*</span></label>
                    <input type="text" name="employee_name" class="form-control" maxlength="150" value="<?= e((string) ($formData['employee_name'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                    <input type="text" name="position_title" class="form-control" maxlength="120" value="<?= e((string) ($formData['position_title'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">No. HP / WA</label>
                    <input type="text" name="phone" class="form-control" maxlength="40" value="<?= e((string) ($formData['phone'] ?? '')) ?>">
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" maxlength="120" value="<?= e((string) ($formData['email'] ?? '')) ?>">
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ACTIVE" <?= ($formData['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
                        <option value="INACTIVE" <?= ($formData['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            <?php elseif ($type === 'business'): ?>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Nama Aktivitas <span class="text-danger">*</span></label>
                    <input type="text" name="activity_name" class="form-control" maxlength="160" value="<?= e((string) ($formData['activity_name'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Jenis / Layanan</label>
                    <input type="text" name="activity_type" class="form-control" maxlength="80" value="<?= e((string) ($formData['activity_type'] ?? '')) ?>" placeholder="Instalasi, voucher, ternak, dll">
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Periode Target</label>
                    <input type="text" name="target_period" class="form-control" maxlength="30" value="<?= e((string) ($formData['target_period'] ?? '')) ?>" placeholder="2026 / Mei 2026">
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Nilai Target</label>
                    <input type="text" name="target_value" class="form-control" inputmode="decimal" value="<?= e((string) ($formData['target_value'] ?? '0')) ?>">
                </div>
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['PLANNED' => 'Rencana', 'RUNNING' => 'Berjalan', 'DONE' => 'Selesai', 'PAUSED' => 'Tunda'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($formData['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($type === 'budgets'): ?>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Tahun <span class="text-danger">*</span></label>
                    <input type="number" name="budget_year" class="form-control" min="2000" max="2100" value="<?= e((string) ($formData['budget_year'] ?? date('Y'))) ?>" required>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Bulan</label>
                    <select name="budget_month" class="form-select">
                        <option value="0">Setahun penuh</option>
                        <?php foreach ($monthNames as $num => $label): ?>
                            <option value="<?= $num ?>" <?= (int) ($formData['budget_month'] ?? 0) === $num ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Jenis</label>
                    <select name="budget_type" class="form-select">
                        <?php foreach (['INCOME' => 'Pendapatan', 'EXPENSE' => 'Belanja/Beban', 'ASSET' => 'Pembelian Aset', 'CAPITAL' => 'Modal'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($formData['budget_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['DRAFT' => 'Draft', 'ACTIVE' => 'Aktif', 'CLOSED' => 'Ditutup'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($formData['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori Anggaran <span class="text-danger">*</span></label>
                    <input type="text" name="category" class="form-control" maxlength="120" value="<?= e((string) ($formData['category'] ?? '')) ?>" placeholder="Contoh: Belanja modem, honor pengurus">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Akun Terkait</label>
                    <select name="account_id" class="form-select">
                        <option value="0">Tidak dikaitkan</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= (int) $account['id'] ?>" <?= (int) ($formData['account_id'] ?? 0) === (int) $account['id'] ? 'selected' : '' ?>>
                                <?= e((string) $account['account_code'] . ' - ' . (string) $account['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nominal <span class="text-danger">*</span></label>
                    <input type="text" name="amount" class="form-control" inputmode="decimal" value="<?= e((string) ($formData['amount'] ?? '0')) ?>" required>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">No. RAB <span class="text-danger">*</span></label>
                    <input type="text" name="plan_no" class="form-control" maxlength="40" value="<?= e((string) ($formData['plan_no'] ?? '')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal RAB <span class="text-danger">*</span></label>
                    <input type="date" name="plan_date" class="form-control" value="<?= e((string) ($formData['plan_date'] ?? date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'REALIZED' => 'Terealisasi', 'CANCELLED' => 'Batal'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($formData['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Judul RAB <span class="text-danger">*</span></label>
                    <input type="text" name="plan_title" class="form-control" maxlength="180" value="<?= e((string) ($formData['plan_title'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kegiatan</label>
                    <input type="text" name="activity_name" class="form-control" maxlength="160" value="<?= e((string) ($formData['activity_name'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <div class="operation-plan-items" id="rabItems">
                        <div class="operation-card__head">
                            <div><span class="operation-card__eyebrow">Rincian</span><h2>Item RAB</h2></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-add-rab-item><i class="bi bi-plus-circle" aria-hidden="true"></i><span>Tambah Item</span></button>
                        </div>
                        <?php foreach ($planItems as $idx => $item): ?>
                            <div class="operation-rab-row">
                                <div>
                                    <label class="form-label">Nama Item</label>
                                    <input type="text" name="item_name[]" class="form-control" value="<?= e((string) ($item['item_name'] ?? '')) ?>" placeholder="Modem, kabel, honor, dll">
                                </div>
                                <div>
                                    <label class="form-label">Qty</label>
                                    <input type="text" name="quantity[]" class="form-control" inputmode="decimal" value="<?= e((string) ($item['quantity'] ?? '1')) ?>">
                                </div>
                                <div>
                                    <label class="form-label">Satuan</label>
                                    <input type="text" name="unit_name[]" class="form-control" maxlength="30" value="<?= e((string) ($item['unit_name'] ?? 'unit')) ?>">
                                </div>
                                <div>
                                    <label class="form-label">Harga</label>
                                    <input type="text" name="unit_price[]" class="form-control" inputmode="decimal" value="<?= e((string) ($item['unit_price'] ?? '0')) ?>">
                                </div>
                                <div>
                                    <label class="form-label">Catatan</label>
                                    <input type="text" name="item_notes[]" class="form-control" maxlength="300" value="<?= e((string) ($item['notes'] ?? '')) ?>">
                                </div>
                                <button type="button" class="btn btn-outline-danger" data-remove-rab-item aria-label="Hapus item"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="700" placeholder="Catatan tambahan bila perlu"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="operation-form-actions">
            <a href="<?= e(base_url($back)) ?>" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Simpan</span></button>
        </div>
    </form>
</div>

<?php if ($type === 'budget_plans'): ?>
<script>
document.addEventListener('click', function (event) {
    const add = event.target.closest('[data-add-rab-item]');
    const remove = event.target.closest('[data-remove-rab-item]');
    const wrap = document.getElementById('rabItems');
    if (add && wrap) {
        const first = wrap.querySelector('.operation-rab-row');
        if (!first) return;
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(function (input) {
            input.value = input.name === 'quantity[]' ? '1' : (input.name === 'unit_name[]' ? 'unit' : '0');
            if (input.name === 'item_name[]' || input.name === 'item_notes[]') input.value = '';
        });
        wrap.appendChild(clone);
    }
    if (remove) {
        const rows = document.querySelectorAll('.operation-rab-row');
        if (rows.length > 1) {
            remove.closest('.operation-rab-row').remove();
        }
    }
});
</script>
<?php endif; ?>
