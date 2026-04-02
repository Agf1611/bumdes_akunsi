<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e($title) ?></h1>
        <p class="text-secondary mb-0">Data unit usaha akan dipakai untuk transaksi jurnal dan filter laporan per unit.</p>
    </div>
    <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-light">Kembali</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <form method="post" action="<?= e($row ? base_url('/business-units/update?id=' . (int) $row['id']) : base_url('/business-units/store')) ?>" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Kode Unit <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="unit_code" maxlength="30" value="<?= e($formData['unit_code']) ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nama Unit Usaha <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="unit_name" maxlength="120" value="<?= e($formData['unit_name']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control" name="description" rows="4" maxlength="500"><?= e($formData['description']) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Aktif</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-light">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Unit Usaha</button>
            </div>
        </form>
    </div>
</div>
