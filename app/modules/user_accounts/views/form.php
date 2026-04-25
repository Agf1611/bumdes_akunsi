<?php declare(strict_types=1); ?>
<?php $mfaGloballyEnabled = (bool) (auth_config('mfa')['enabled'] ?? false); ?>
<div class="user-account-form-page module-page">
<section class="module-hero mb-4">
    <div class="module-hero__content">
        <div>
            <div class="module-hero__eyebrow">Pengguna</div>
            <h1 class="module-hero__title"><?= e($title) ?></h1>
            <p class="module-hero__text">Atur nama lengkap, role, username, status aktif, dan password untuk akun bendahara atau pimpinan dengan form yang lebih ringkas.</p>
        </div>
        <div class="module-hero__actions">
            <a href="<?= e(base_url('/user-accounts')) ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</section>

<div class="card shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <form method="post" action="<?= e($row ? base_url('/user-accounts/update?id=' . (int) $row['id']) : base_url('/user-accounts/store')) ?>" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="full_name" maxlength="100" value="<?= e($formData['full_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_code" class="form-select" required>
                        <?php foreach (($roleOptions ?? []) as $role): ?>
                            <option value="<?= e((string) $role['code']) ?>" <?= $formData['role_code'] === (string) $role['code'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Akun</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" maxlength="50" value="<?= e($formData['username']) ?>" placeholder="mis. bendahara.bumdes" required>
                    <div class="form-text text-secondary">Gunakan 4-50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="w-100 rounded-4 border border-secondary-subtle p-3 bg-dark-subtle">
                        <div class="fw-semibold mb-1">Keamanan akun</div>
                        <div class="small text-secondary mb-0">
                            <?php if ($mfaGloballyEnabled): ?>
                                Admin dapat mengubah username login, password, dan MFA kapan saja. Gunakan secret TOTP hanya jika perangkat autentikator pengguna sudah siap.
                            <?php else: ?>
                                OTP login sedang dimatikan secara global. Fokus pengelolaan akun saat ini hanya pada status aktif, username, dan password.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= $row ? 'Password Baru' : 'Password' ?><?= $row ? '' : ' <span class="text-danger">*</span>' ?></label>
                    <input type="password" class="form-control" name="password" minlength="8" <?= $row ? '' : 'required' ?>>
                    <?php if ($row): ?>
                        <div class="form-text text-secondary">Kosongkan jika tidak ingin mengganti password lama.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password<?= $row ? ' Baru' : '' ?><?= $row ? '' : ' <span class="text-danger">*</span>' ?></label>
                    <input type="password" class="form-control" name="password_confirmation" minlength="8" <?= $row ? '' : 'required' ?>>
                </div>

                <?php if ($mfaGloballyEnabled): ?>
                    <div class="col-md-3">
                        <label class="form-label">Multi-Factor Authentication</label>
                        <select name="mfa_enabled" class="form-select">
                            <option value="0" <?= $formData['mfa_enabled'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="1" <?= $formData['mfa_enabled'] === '1' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Secret MFA (TOTP Base32)</label>
                        <input type="text" class="form-control" name="mfa_secret" maxlength="64" value="<?= e($formData['mfa_secret']) ?>" placeholder="Contoh: JBSWY3DPEHPK3PXP">
                        <div class="form-text text-secondary">Wajib diisi jika MFA aktif. Secret ini dipakai di aplikasi autentikator seperti Google Authenticator atau Aegis.</div>
                    </div>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">OTP login sedang dimatikan secara global, jadi pengaturan MFA disembunyikan dari form ini.</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= e(base_url('/user-accounts')) ?>" class="btn btn-outline-light">Batal</a>
                <button type="submit" class="btn btn-primary"><?= $row ? 'Simpan Perubahan Akun' : 'Simpan Akun Pengguna' ?></button>
            </div>
        </form>
    </div>
</div>
</div>
