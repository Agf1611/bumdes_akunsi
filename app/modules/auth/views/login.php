<?php declare(strict_types=1); ?>
<?php
$profile = is_array($profile ?? null) ? $profile : app_profile();
$logoPath = trim((string) ($profile['logo_path'] ?? ''));
$bumdesName = trim((string) ($profile['bumdes_name'] ?? '')) ?: (string) app_config('name');
$appTitle = (string) app_config('name');
?>
<div class="row align-items-center justify-content-center py-3 py-lg-4 auth-layout-grid">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 mx-auto">
        <section class="card auth-card auth-form-card border-0 shadow-lg">
            <div class="card-body p-4 p-lg-5">
                <div class="auth-login-head mb-4">
                    <div class="auth-login-brand">
                        <div class="auth-login-logo">
                            <?php if ($logoPath !== ''): ?>
                                <img src="<?= e(upload_url($logoPath)) ?>" alt="Logo BUMDes">
                            <?php else: ?>
                                <span><?= e(strtoupper(substr($bumdesName, 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="auth-login-app"><?= e($appTitle) ?></div>
                            <h1 class="auth-form-title mb-0"><?= e($bumdesName) ?></h1>
                        </div>
                    </div>
                    <button type="button" class="topbar-pill topbar-pill--theme d-none d-sm-inline-flex" id="themeToggle" aria-label="Ubah tema" title="Ubah tema">
                        <span class="theme-toggle-icon" id="themeToggleIcon">*</span>
                        <span class="theme-toggle-text" id="themeToggleText">Light</span>
                    </button>
                </div>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger ui-alert" role="alert">
                        <div class="ui-alert-title">Login gagal</div>
                        <div><?= e($errorMessage) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success ui-alert" role="alert">
                        <div class="ui-alert-title">Informasi</div>
                        <div><?= e($successMessage) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('/login')) ?>" novalidate class="auth-form-grid">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control form-control-lg" value="<?= e(old('username')) ?>" maxlength="50" autocomplete="username" placeholder="Masukkan username" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="auth-password-wrap">
                            <input type="password" name="password" id="password" class="form-control form-control-lg" autocomplete="current-password" placeholder="Masukkan password" required>
                            <button type="button" class="auth-password-toggle" id="togglePasswordVisibility" aria-label="Tampilkan sandi" aria-pressed="false">
                                <svg class="auth-eye auth-eye--show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="auth-eye auth-eye--hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.6 10.6A2 2 0 0 0 13.4 13.4"></path>
                                    <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-2.9 3.8"></path>
                                    <path d="M6.6 6.8C3.6 8.8 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.5-1"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="auth-login-options mt-3">
                            <label class="form-check auth-remember-check">
                                <input class="form-check-input" type="checkbox" name="remember_me" value="1" id="remember_me" <?= old('remember_me') === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Ingat saya</span>
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($mfaEnabled)): ?>
                        <div class="mb-4">
                            <label for="otp_code" class="form-label">Kode OTP MFA</label>
                            <input type="text" name="otp_code" id="otp_code" class="form-control form-control-lg" value="<?= e(old('otp_code')) ?>" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="Isi jika akun Anda memakai MFA">
                            <div class="form-text text-secondary">Kolom ini wajib jika akun Anda sudah mengaktifkan MFA TOTP.</div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100 ui-btn-primary">Masuk</button>
                </form>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    var passwordInput = document.getElementById('password');
    var toggle = document.getElementById('togglePasswordVisibility');
    if (!passwordInput || !toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        var isVisible = passwordInput.getAttribute('type') === 'text';
        passwordInput.setAttribute('type', isVisible ? 'password' : 'text');
        toggle.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
        toggle.setAttribute('aria-label', isVisible ? 'Tampilkan sandi' : 'Sembunyikan sandi');
    });
})();
</script>
