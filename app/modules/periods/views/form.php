<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($title) ?></h1>
                <p class="text-secondary mb-0">Periode dipakai untuk membatasi transaksi jurnal. Periode tertutup tidak boleh dipakai input transaksi.</p>
            </div>
            <div>
                <a href="<?= e(base_url('/periods')) ?>" class="btn btn-outline-light">Kembali ke Daftar Periode</a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2 small text-center text-md-start">
                    <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">1. Kode & nama periode</span></div>
                    <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">2. Rentang tanggal</span></div>
                    <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">3. Status & periode aktif</span></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e($period ? base_url('/periods/update?id=' . (int) $period['id']) : base_url('/periods/store')) ?>" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <div class="row g-4">
                        <div class="col-12"><div class="border rounded-4 p-3 bg-body-tertiary"><div class="fw-semibold">Langkah 1 · Identitas periode</div><div class="small text-secondary">Gunakan kode dan nama yang mudah dikenali bendahara, misalnya 2026-01 / Januari 2026.</div></div></div>

                        <div class="col-12 col-md-4">
                            <label for="period_code" class="form-label">Kode Periode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="period_code" name="period_code" maxlength="30" value="<?= e($formData['period_code']) ?>" placeholder="Contoh: 2026-01" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="period_name" class="form-label">Nama Periode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="period_name" name="period_name" maxlength="100" value="<?= e($formData['period_name']) ?>" placeholder="Contoh: Januari 2026" required>
                        </div>

                        <div class="col-12"><div class="border rounded-4 p-3 bg-body-tertiary"><div class="fw-semibold">Langkah 2 · Rentang tanggal</div><div class="small text-secondary">Pastikan rentang periode tidak tumpang tindih dengan periode lain dan sesuai siklus pembukuan.</div></div></div>

                        <div class="col-12 col-md-4">
                            <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($formData['start_date']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="end_date" class="form-label">Tanggal Akhir <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($formData['end_date']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label">Status Periode <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $formData['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12"><div class="border rounded-4 p-3 bg-body-tertiary"><div class="fw-semibold">Langkah 3 · Status operasional</div><div class="small text-secondary">Periode aktif harus berstatus buka. Gunakan opsi aktif hanya jika periode ini memang menjadi buku kerja harian.</div></div></div>

                        <div class="col-12 col-md-4">
                            <label for="is_active" class="form-label">Jadikan Periode Aktif</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : '' ?>>Tidak</option>
                                <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : '' ?>>Ya</option>
                            </select>
                            <div class="form-text text-secondary">Jika dipilih ya, sistem akan mematikan status aktif pada periode lain.</div>
                        </div>
                    </div>

                    <div class="alert alert-info bg-info-subtle border border-info-subtle text-dark mt-4 mb-0">
                        <div class="fw-semibold mb-1">Aturan bisnis periode akuntansi</div>
                        <ul class="mb-0 ps-3">
                            <li>Hanya satu periode yang boleh aktif pada satu waktu.</li>
                            <li>Periode aktif harus berstatus buka.</li>
                            <li>Rentang tanggal antarperiode tidak boleh saling tumpang tindih.</li>
                            <li>Periode tertutup tidak boleh dipakai untuk input transaksi.</li>
                        </ul>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
                        <div class="text-secondary small">Pastikan kode, tanggal, dan status periode sudah benar sebelum disimpan.</div>
                        <div class="d-flex gap-2">
                            <a href="<?= e(base_url('/periods')) ?>" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Periode</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
