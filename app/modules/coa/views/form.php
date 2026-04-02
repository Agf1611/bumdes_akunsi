<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($title) ?></h1>
                <p class="text-secondary mb-0">Isi data akun dengan benar agar struktur COA rapi dan siap dipakai ke modul jurnal.</p>
            </div>
            <div>
                <a href="<?= e(base_url('/coa')) ?>" class="btn btn-outline-light">Kembali ke Daftar Akun</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e($account ? base_url('/coa/update?id=' . (int) $account['id']) : base_url('/coa/store')) ?>" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <label for="account_code" class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_code" name="account_code" maxlength="30" value="<?= e($formData['account_code']) ?>" placeholder="Contoh: 1.101" required>
                            <div class="form-text text-secondary">Kode akun harus unik dan hanya boleh berisi huruf besar, angka, titik, atau tanda hubung.</div>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="account_name" class="form-label">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" maxlength="150" value="<?= e($formData['account_name']) ?>" placeholder="Contoh: Kas di Tangan" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="account_type" class="form-label">Tipe Akun <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <?php foreach ($types as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $formData['account_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="account_category" class="form-label">Kategori Akun <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_category" name="account_category" required data-current-category="<?= e($formData['account_category']) ?>">
                                <?php foreach (coa_categories_for_type($formData['account_type']) as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $formData['account_category'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="parent_id" class="form-label">Parent Akun</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">Tanpa parent</option>
                                <?php foreach ($parentOptions as $parent): ?>
                                    <option value="<?= e((string) $parent['id']) ?>" <?= $formData['parent_id'] === (string) $parent['id'] ? 'selected' : '' ?> data-type="<?= e((string) $parent['account_type']) ?>">
                                        <?= e($parent['account_code'] . ' - ' . $parent['account_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-secondary">Hanya akun header yang aktif dengan tipe yang sama yang dapat menjadi parent.</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="is_header" class="form-label">Status Struktur</label>
                            <select class="form-select" id="is_header" name="is_header">
                                <option value="0" <?= $formData['is_header'] === '0' ? 'selected' : '' ?>>Detail</option>
                                <option value="1" <?= $formData['is_header'] === '1' ? 'selected' : '' ?>>Header</option>
                            </select>
                            <div class="form-text text-secondary">Akun header tidak boleh dipakai untuk jurnal.</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="is_active" class="form-label">Status Aktif</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
                        <div class="text-secondary small">Pastikan tipe, kategori, dan parent akun sudah benar agar struktur laporan tetap konsisten.</div>
                        <div class="d-flex gap-2">
                            <a href="<?= e(base_url('/coa')) ?>" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Akun</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.COA_CATEGORIES = <?= json_encode($categoriesMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
(function () {
    const typeSelect = document.getElementById('account_type');
    const categorySelect = document.getElementById('account_category');
    const parentSelect = document.getElementById('parent_id');

    if (!typeSelect || !categorySelect || !parentSelect || !window.COA_CATEGORIES) {
        return;
    }

    const currentCategory = categorySelect.getAttribute('data-current-category') || '';

    const refreshCategories = function () {
        const selectedType = typeSelect.value;
        const options = window.COA_CATEGORIES[selectedType] || {};
        const selectedBefore = categorySelect.value || currentCategory;
        categorySelect.innerHTML = '';

        Object.keys(options).forEach(function (key) {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = options[key];
            if (key === selectedBefore) {
                option.selected = true;
            }
            categorySelect.appendChild(option);
        });

        if (!categorySelect.value && categorySelect.options.length > 0) {
            categorySelect.options[0].selected = true;
        }
    };

    const refreshParents = function () {
        const selectedType = typeSelect.value;
        const currentParent = parentSelect.value;
        Array.from(parentSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            const optionType = option.getAttribute('data-type') || '';
            option.hidden = optionType !== selectedType;
        });

        const selectedOption = parentSelect.options[parentSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            parentSelect.value = '';
        } else {
            parentSelect.value = currentParent;
        }
    };

    typeSelect.addEventListener('change', function () {
        refreshCategories();
        refreshParents();
    });

    refreshCategories();
    refreshParents();
})();
</script>
