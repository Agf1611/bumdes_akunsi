<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e((string) ($title ?? 'Form Referensi')) ?></h1>
        <p class="text-secondary mb-0">Lengkapi data referensi agar metadata jurnal bisa dipilih langsung dari UI aplikasi.</p>
    </div>
    <a href="<?= e(base_url('/reference-masters?type=' . urlencode((string) $type))) ?>" class="btn btn-outline-light">Kembali</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?= e($row ? base_url('/reference-masters/update?type=' . urlencode((string) $type) . '&id=' . (int) $row['id']) : base_url('/reference-masters/store?type=' . urlencode((string) $type))) ?>" class="row g-4" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="col-md-4">
                <label class="form-label">Kode</label>
                <input type="text" name="code" class="form-control" maxlength="40" value="<?= e((string) ($formData['code'] ?? '')) ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control" maxlength="150" value="<?= e((string) ($formData['name'] ?? '')) ?>" required>
            </div>

            <?php if (($config['type_field'] ?? null) !== null): ?>
            <div class="col-md-4">
                <label class="form-label">Jenis</label>
                <select name="type_value" class="form-select">
                    <option value="">Pilih Jenis</option>
                    <?php foreach (($config['type_options'] ?? []) as $value => $label): ?>
                        <option value="<?= e((string) $value) ?>" <?= (string) ($formData['type_value'] ?? '') === (string) $value ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($type === 'partners'): ?>
                <div class="col-md-4">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" maxlength="50" value="<?= e((string) ($formData['phone'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="address" class="form-control" maxlength="255" value="<?= e((string) ($formData['address'] ?? '')) ?>">
                </div>
            <?php elseif ($type === 'inventory' || $type === 'raw-materials'): ?>
                <div class="col-md-4">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit_name" class="form-control" maxlength="30" value="<?= e((string) ($formData['unit_name'] ?? '')) ?>" placeholder="pcs, kg, ekor, dll">
                </div>
            <?php elseif ($type === 'savings'): ?>
                <div class="col-md-4">
                    <label class="form-label">Nama Pemilik</label>
                    <input type="text" name="owner_name" class="form-control" maxlength="150" value="<?= e((string) ($formData['owner_name'] ?? '')) ?>">
                </div>
            <?php endif; ?>

            <div class="col-12">
                <label class="form-label">Catatan / Keterangan</label>
                <textarea name="notes" rows="3" class="form-control" maxlength="255"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <option value="1" <?= (string) ($formData['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= (string) ($formData['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="<?= e(base_url('/reference-masters?type=' . urlencode((string) $type))) ?>" class="btn btn-outline-light">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
