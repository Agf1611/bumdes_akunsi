<?php

declare(strict_types=1);

$profileData = [
    'bumdes_name' => old('bumdes_name', (string) ($profile['bumdes_name'] ?? '')),
    'address' => old('address', (string) ($profile['address'] ?? '')),
    'village_name' => old('village_name', (string) ($profile['village_name'] ?? '')),
    'district_name' => old('district_name', (string) ($profile['district_name'] ?? '')),
    'regency_name' => old('regency_name', (string) ($profile['regency_name'] ?? '')),
    'province_name' => old('province_name', (string) ($profile['province_name'] ?? '')),
    'legal_entity_no' => old('legal_entity_no', (string) ($profile['legal_entity_no'] ?? '')),
    'nib' => old('nib', (string) ($profile['nib'] ?? '')),
    'npwp' => old('npwp', (string) ($profile['npwp'] ?? '')),
    'phone' => old('phone', (string) ($profile['phone'] ?? '')),
    'email' => old('email', (string) ($profile['email'] ?? '')),
    'director_name' => old('director_name', (string) (($profile['director_name'] ?? '') !== '' ? $profile['director_name'] : ($profile['leader_name'] ?? ''))),
    'director_position' => old('director_position', (string) ($profile['director_position'] ?? 'Direktur')),
    'signature_city' => old('signature_city', (string) ($profile['signature_city'] ?? '')),
    'treasurer_name' => old('treasurer_name', (string) ($profile['treasurer_name'] ?? '')),
    'treasurer_position' => old('treasurer_position', (string) ($profile['treasurer_position'] ?? 'Bendahara')),
    'receipt_signature_mode' => old('receipt_signature_mode', (string) ($profile['receipt_signature_mode'] ?? 'treasurer_recipient_director')),
    'receipt_require_recipient_cash' => (int) old('receipt_require_recipient_cash', (string) ($profile['receipt_require_recipient_cash'] ?? '1')) === 1,
    'receipt_require_recipient_transfer' => (int) old('receipt_require_recipient_transfer', (string) ($profile['receipt_require_recipient_transfer'] ?? '0')) === 1,
    'director_sign_threshold' => old('director_sign_threshold', (string) ($profile['director_sign_threshold'] ?? '0.00')),
    'show_stamp' => (int) old('show_stamp', (string) ($profile['show_stamp'] ?? '1')) === 1,
    'active_period_start' => old('active_period_start', (string) ($profile['active_period_start'] ?? '')),
    'active_period_end' => old('active_period_end', (string) ($profile['active_period_end'] ?? '')),
    'logo_path' => (string) ($profile['logo_path'] ?? ''),
    'signature_path' => (string) ($profile['signature_path'] ?? ''),
    'treasurer_signature_path' => (string) ($profile['treasurer_signature_path'] ?? ''),
];

$receiptModes = [
    'treasurer_only' => 'Bendahara saja',
    'treasurer_recipient' => 'Bendahara + Penerima',
    'treasurer_director' => 'Bendahara + Direktur',
    'treasurer_recipient_director' => 'Bendahara + Penerima + Direktur',
];
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-11">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Pengaturan Profil BUMDes & Penandatangan</h1>
                <p class="text-secondary mb-0">Data ini dipakai untuk identitas aplikasi, kop surat resmi laporan, jurnal standar, dan kwitansi / bukti transaksi.</p>
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
                        <div class="col-12 col-lg-7">
                            <div class="card h-100 bg-dark-subtle">
                                <div class="card-body">
                                    <h2 class="h5 mb-3">Identitas Lembaga</h2>
                                    <div class="mb-3">
                                        <label for="bumdes_name" class="form-label">Nama BUMDes <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bumdes_name" name="bumdes_name" maxlength="150" value="<?= e($profileData['bumdes_name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="address" name="address" rows="4" maxlength="500" required><?= e($profileData['address']) ?></textarea>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="village_name" class="form-label">Desa</label>
                                            <input type="text" class="form-control" id="village_name" name="village_name" maxlength="120" value="<?= e($profileData['village_name']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="district_name" class="form-label">Kecamatan</label>
                                            <input type="text" class="form-control" id="district_name" name="district_name" maxlength="120" value="<?= e($profileData['district_name']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="regency_name" class="form-label">Kabupaten</label>
                                            <input type="text" class="form-control" id="regency_name" name="regency_name" maxlength="120" value="<?= e($profileData['regency_name']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="province_name" class="form-label">Provinsi</label>
                                            <input type="text" class="form-control" id="province_name" name="province_name" maxlength="120" value="<?= e($profileData['province_name']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="legal_entity_no" class="form-label">No. Badan Hukum</label>
                                            <input type="text" class="form-control" id="legal_entity_no" name="legal_entity_no" maxlength="120" value="<?= e($profileData['legal_entity_no']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="nib" class="form-label">NIB</label>
                                            <input type="text" class="form-control" id="nib" name="nib" maxlength="50" value="<?= e($profileData['nib']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="npwp" class="form-label">NPWP</label>
                                            <input type="text" class="form-control" id="npwp" name="npwp" maxlength="50" value="<?= e($profileData['npwp']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Telepon</label>
                                            <input type="text" class="form-control" id="phone" name="phone" maxlength="30" value="<?= e($profileData['phone']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($profileData['email']) ?>">
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label for="active_period_start" class="form-label">Periode Aktif Mulai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="active_period_start" name="active_period_start" value="<?= e($profileData['active_period_start']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="active_period_end" class="form-label">Periode Aktif Sampai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="active_period_end" name="active_period_end" value="<?= e($profileData['active_period_end']) ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <div class="card h-100 bg-dark-subtle">
                                <div class="card-body">
                                    <h2 class="h5 mb-3">Logo Lembaga</h2>
                                    <div class="logo-preview-box d-flex align-items-center justify-content-center mb-3">
                                        <?php if ($profileData['logo_path'] !== ''): ?>
                                            <img src="<?= e(upload_url($profileData['logo_path'])) ?>" alt="Logo BUMDes" class="img-fluid rounded logo-preview-image">
                                        <?php else: ?>
                                            <div class="text-center text-secondary small px-3">Belum ada logo.<br>Unggah logo agar kop surat dan layout aplikasi tampil resmi.</div>
                                        <?php endif; ?>
                                    </div>
                                    <input class="form-control" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <div class="form-text text-secondary mt-2">Format: JPG, PNG, WEBP. Ukuran maksimal 2 MB.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 bg-dark-subtle">
                                <div class="card-body">
                                    <h2 class="h5 mb-3">Penandatangan Direktur</h2>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="director_name" class="form-label">Nama Direktur <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="director_name" name="director_name" maxlength="120" value="<?= e($profileData['director_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="director_position" class="form-label">Jabatan Direktur <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="director_position" name="director_position" maxlength="100" value="<?= e($profileData['director_position']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="signature_city" class="form-label">Kota Tanda Tangan</label>
                                            <input type="text" class="form-control" id="signature_city" name="signature_city" maxlength="100" value="<?= e($profileData['signature_city']) ?>" placeholder="Contoh: Simpang">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="director_sign_threshold" class="form-label">Batas Nominal Wajib Direktur</label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="director_sign_threshold" name="director_sign_threshold" value="<?= e($profileData['director_sign_threshold']) ?>" placeholder="0.00">
                                        </div>
                                        <div class="col-12">
                                            <label for="signature_file" class="form-label">Tanda Tangan Direktur</label>
                                            <div class="logo-preview-box d-flex align-items-center justify-content-center mb-3 signature-preview-box">
                                                <?php if ($profileData['signature_path'] !== ''): ?>
                                                    <img src="<?= e(upload_url($profileData['signature_path'])) ?>" alt="Tanda Tangan Direktur" class="img-fluid rounded signature-preview-image">
                                                <?php else: ?>
                                                    <div class="text-center text-secondary small px-3">Belum ada file tanda tangan direktur.</div>
                                                <?php endif; ?>
                                            </div>
                                            <input class="form-control" type="file" id="signature_file" name="signature_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="card h-100 bg-dark-subtle">
                                <div class="card-body">
                                    <h2 class="h5 mb-3">Penandatangan Bendahara</h2>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="treasurer_name" class="form-label">Nama Bendahara <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="treasurer_name" name="treasurer_name" maxlength="120" value="<?= e($profileData['treasurer_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="treasurer_position" class="form-label">Jabatan Bendahara <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="treasurer_position" name="treasurer_position" maxlength="100" value="<?= e($profileData['treasurer_position']) ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="treasurer_signature_file" class="form-label">Tanda Tangan Bendahara</label>
                                            <div class="logo-preview-box d-flex align-items-center justify-content-center mb-3 signature-preview-box">
                                                <?php if ($profileData['treasurer_signature_path'] !== ''): ?>
                                                    <img src="<?= e(upload_url($profileData['treasurer_signature_path'])) ?>" alt="Tanda Tangan Bendahara" class="img-fluid rounded signature-preview-image">
                                                <?php else: ?>
                                                    <div class="text-center text-secondary small px-3">Belum ada file tanda tangan bendahara.</div>
                                                <?php endif; ?>
                                            </div>
                                            <input class="form-control" type="file" id="treasurer_signature_file" name="treasurer_signature_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-dark-subtle mt-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Aturan Tanda Tangan Kwitansi</h2>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label for="receipt_signature_mode" class="form-label">Mode Tanda Tangan Kwitansi</label>
                                    <select class="form-select" id="receipt_signature_mode" name="receipt_signature_mode">
                                        <?php foreach ($receiptModes as $modeKey => $modeLabel): ?>
                                            <option value="<?= e($modeKey) ?>" <?= $profileData['receipt_signature_mode'] === $modeKey ? 'selected' : '' ?>><?= e($modeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-secondary">Aturan ini dipakai otomatis saat cetak bukti transaksi / kwitansi.</div>
                                </div>
                                <div class="col-lg-6 d-flex flex-column gap-3 pt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="receipt_require_recipient_cash" name="receipt_require_recipient_cash" <?= $profileData['receipt_require_recipient_cash'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="receipt_require_recipient_cash">Penerima wajib tanda tangan untuk transaksi tunai</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="receipt_require_recipient_transfer" name="receipt_require_recipient_transfer" <?= $profileData['receipt_require_recipient_transfer'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="receipt_require_recipient_transfer">Penerima tetap tampil untuk transaksi transfer</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="show_stamp" name="show_stamp" <?= $profileData['show_stamp'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_stamp">Tampilkan ruang stempel pada tanda tangan direktur</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
                        <div class="text-secondary small">Isi nama bendahara dan direktur dengan benar agar otomatis dipakai pada jurnal standar dan kwitansi.</div>
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
