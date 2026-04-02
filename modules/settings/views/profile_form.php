<?php declare(strict_types=1);
$profileData = [
    'bumdes_name' => old('bumdes_name', (string) ($profile['bumdes_name'] ?? '')),
    'address' => old('address', (string) ($profile['address'] ?? '')),
    'phone' => old('phone', (string) ($profile['phone'] ?? '')),
    'email' => old('email', (string) ($profile['email'] ?? '')),
    'leader_name' => old('leader_name', (string) ($profile['leader_name'] ?? '')),
    'active_period_start' => old('active_period_start', (string) ($profile['active_period_start'] ?? '')),
    'active_period_end' => old('active_period_end', (string) ($profile['active_period_end'] ?? '')),
    'logo_path' => (string) ($profile['logo_path'] ?? ''),
];
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Pengaturan Profil BUMDes</h1>
                <p class="text-secondary mb-0">Data ini dipakai untuk header aplikasi, identitas sistem, dan nanti bisa dipakai untuk laporan PDF.</p>
            </div>
            <div class="text-secondary small">
                Periode aktif saat ini: <strong class="text-light"><?= e(active_period_label($profileData['active_period_start'], $profileData['active_period_end'])) ?></strong>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e(base_url('/settings/profile')) ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <div class="row g-4">
                        <div class="col-12 col-lg-8">
                            <div class="mb-3">
                                <label for="bumdes_name" class="form-label">Nama BUMDes <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bumdes_name" name="bumdes_name" maxlength="150" value="<?= e($profileData['bumdes_name']) ?>" placeholder="Contoh: BUMDes Maju Bersama" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="4" maxlength="500" placeholder="Masukkan alamat lengkap BUMDes" required><?= e($profileData['address']) ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Telepon</label>
                                    <input type="text" class="form-control" id="phone" name="phone" maxlength="30" value="<?= e($profileData['phone']) ?>" placeholder="Contoh: 0812xxxxxxx">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($profileData['email']) ?>" placeholder="contoh@bumdes.id">
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label for="leader_name" class="form-label">Nama Pimpinan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="leader_name" name="leader_name" maxlength="120" value="<?= e($profileData['leader_name']) ?>" placeholder="Nama pimpinan BUMDes" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="active_period_start" class="form-label">Periode Aktif Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="active_period_start" name="active_period_start" value="<?= e($profileData['active_period_start']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="active_period_end" class="form-label">Periode Aktif Sampai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="active_period_end" name="active_period_end" value="<?= e($profileData['active_period_end']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="card bg-dark-subtle h-100">
                                <div class="card-body">
                                    <label for="logo" class="form-label">Logo BUMDes</label>
                                    <div class="logo-preview-box d-flex align-items-center justify-content-center mb-3">
                                        <?php if ($profileData['logo_path'] !== ''): ?>
                                            <img src="<?= e(storage_url($profileData['logo_path'])) ?>" alt="Logo BUMDes" class="img-fluid rounded logo-preview-image">
                                        <?php else: ?>
                                            <div class="text-center text-secondary small px-3">
                                                Belum ada logo.<br>Unggah logo agar header aplikasi dan laporan terlihat lebih rapi.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <input class="form-control" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <div class="form-text text-secondary mt-2">Format: JPG, PNG, WEBP. Ukuran maksimal 2 MB.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
                        <div class="text-secondary small">Pastikan data profil ini benar karena akan dipakai ulang di banyak bagian aplikasi.</div>
                        <div class="d-flex gap-2">
                            <a href="<?= e(base_url('/dashboard')) ?>" class="btn btn-outline-light">Kembali</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Profil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
