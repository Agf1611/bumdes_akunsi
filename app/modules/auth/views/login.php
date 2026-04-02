<?php declare(strict_types=1); ?>
<div class="row align-items-center g-4 g-xl-5 py-3 py-lg-4 auth-layout-grid">
    <div class="col-12 col-lg-6 col-xl-7 d-none d-lg-block">
        <section class="auth-visual-card h-100">
            <div class="auth-visual-card__eyebrow">Aplikasi Akuntansi BUMDes</div>
            <h1 class="auth-visual-card__title">Kelola transaksi dan laporan keuangan dalam satu workspace yang rapi.</h1>
            <p class="auth-visual-card__lead">Masuk ke aplikasi untuk mengelola chart of accounts, jurnal umum, buku besar, dan laporan keuangan dengan tampilan yang lebih nyaman dibaca di desktop maupun mobile.</p>

            <div class="auth-feature-list mt-4">
                <div class="auth-feature-item">Navigasi lebih bersih dan fokus pada kebutuhan operasional harian.</div>
                <div class="auth-feature-item">Mode light dan dark lebih nyaman untuk digunakan sepanjang hari.</div>
                <div class="auth-feature-item">Cetak laporan tetap jelas dan proporsional saat dibawa ke dokumen fisik.</div>
            </div>
        </section>
    </div>

    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto">
        <section class="card auth-card auth-form-card border-0 shadow-lg">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="auth-mini-badge">Login Aman</div>
                        <h2 class="auth-form-title mt-3 mb-2">Masuk ke Dashboard</h2>
                        <p class="text-secondary mb-0">Gunakan akun resmi Anda untuk mengakses aplikasi akuntansi BUMDes.</p>
                    </div>
                    <button type="button" class="topbar-pill topbar-pill--theme d-none d-sm-inline-flex" id="themeToggle" aria-label="Ubah tema" title="Ubah tema">
                        <span class="theme-toggle-icon" id="themeToggleIcon">☀️</span>
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
                        <input type="password" name="password" id="password" class="form-control form-control-lg" autocomplete="current-password" placeholder="Masukkan password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 ui-btn-primary">Masuk ke Aplikasi</button>
                </form>
            </div>
        </section>
    </div>
</div>
