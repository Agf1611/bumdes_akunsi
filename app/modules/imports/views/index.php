<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Import Excel</h1>
        <p class="text-secondary mb-0">Import data COA dan jurnal umum dari file Excel .xlsx dengan validasi ketat agar aman untuk shared hosting.</p>
    </div>
</div>

<?php if ($importSuccess): ?>
    <div class="alert alert-success mb-4" role="alert"><?= e((string) $importSuccess) ?></div>
<?php endif; ?>

<?php if (is_array($importErrors) && $importErrors !== []): ?>
    <div class="alert alert-danger mb-4" role="alert">
        <div class="fw-semibold mb-2">Import dibatalkan karena ditemukan masalah:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($importErrors as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card dashboard-card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Template Import COA</h2>
                    <a href="<?= e(base_url('/imports/template?type=coa')) ?>" class="btn btn-sm btn-outline-light">Unduh Template</a>
                </div>
                <div class="small text-secondary mb-3">Kolom wajib: <strong class="text-light">account_code, account_name, account_type, account_category, parent_code, is_header, is_active</strong></div>
                <form method="post" action="<?= e(base_url('/imports/coa')) ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label for="coa_file" class="form-label">File Excel COA (.xlsx)</label>
                        <input type="file" class="form-control" id="coa_file" name="coa_file" accept=".xlsx" required>
                        <div class="form-text text-secondary">Ukuran file maksimal 2 MB. Import COA dibatalkan penuh jika ada satu saja baris yang salah.</div>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="coa_overwrite" name="coa_overwrite">
                        <label class="form-check-label" for="coa_overwrite">Timpa akun yang sudah ada berdasarkan kode akun yang sama</label>
                        <div class="form-text text-secondary">Gunakan opsi ini bila file hasil export/template COA Anda sudah diedit lalu ingin diunggah ulang tanpa mengubah relasi jurnal lama.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Import COA</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card dashboard-card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Template Import Jurnal Umum</h2>
                    <a href="<?= e(base_url('/imports/template?type=journal')) ?>" class="btn btn-sm btn-outline-light">Unduh Template</a>
                </div>
                <div class="small text-secondary mb-3">Kolom wajib: <strong class="text-light">import_ref, journal_date, description, period_code, account_code, line_description, debit, credit</strong></div>
                <form method="post" action="<?= e(base_url('/imports/journal')) ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="redirect_to" value="/imports">
                    <div class="mb-3">
                        <label for="journal_file" class="form-label">File Excel Jurnal (.xlsx)</label>
                        <input type="file" class="form-control" id="journal_file" name="journal_file" accept=".xlsx" required>
                        <div class="form-text text-secondary">Ukuran file maksimal 2 MB. Semua baris dengan import_ref yang sama akan digabung menjadi satu jurnal.</div>
                    </div>
                    <div class="mb-3">
                        <label for="journal_business_unit_id" class="form-label">Masuk ke Unit Usaha</label>
                        <select class="form-select" id="journal_business_unit_id" name="journal_business_unit_id">
                            <option value="">Global / Semua unit</option>
                            <?php foreach (($unitOptions ?? []) as $unit): ?>
                                <option value="<?= e((string) ($unit['id'] ?? '')) ?>" <?= old('journal_business_unit_id', '') === (string) ($unit['id'] ?? '') ? 'selected' : '' ?>><?= e(trim((string) ($unit['unit_code'] ?? '')) . ' - ' . trim((string) ($unit['unit_name'] ?? ''))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-secondary">Kosongkan bila jurnal hasil import memang ingin masuk ke global. Pilihan ini diterapkan ke semua jurnal dalam file yang diunggah.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Jurnal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Strategi Import yang Dipakai</h2>
        <ul class="mb-0 ps-3 small text-secondary">
            <li>File wajib berformat .xlsx agar parser ringan dan aman untuk shared hosting.</li>
            <li>Header kolom dicek secara ketat. Jika tidak sesuai template, import langsung dibatalkan.</li>
            <li>Validasi data dilakukan penuh terlebih dahulu, baru data disimpan dalam transaksi database.</li>
            <li>Jika ada satu error saja, seluruh import dibatalkan agar data akuntansi tetap konsisten.</li>
            <li>Untuk jurnal, sistem membuat nomor jurnal otomatis dari aplikasi, bukan dari file Excel.</li>
        </ul>
    </div>
</div>
