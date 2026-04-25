<?php declare(strict_types=1); ?>
<?php
$listing = is_array($listing ?? null) ? $listing : listing_paginate($rows ?? []);
$rows = $listing['items'] ?? ($rows ?? []);
$listingPath = '/user-accounts';
$mfaGloballyEnabled = (bool) (auth_config('mfa')['enabled'] ?? false);
?>
<div class="user-account-page module-page">
<section class="module-hero mb-4">
    <div class="module-hero__content">
        <div>
            <div class="module-hero__eyebrow">Pengguna</div>
            <h1 class="module-hero__title">Manajemen Akun Pengguna</h1>
            <p class="module-hero__text">Admin dapat membuat dan mengubah akun bendahara serta pimpinan, termasuk reset password sementara, status akun, dan kontrol login yang lebih jelas.</p>
        </div>
        <div class="module-hero__actions">
            <a href="<?= e(base_url('/user-accounts/create')) ?>" class="btn btn-primary">Tambah Akun Pengguna</a>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <form method="get" action="<?= e(base_url('/user-accounts')) ?>" class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label">Pencarian</label>
                        <input type="text" class="form-control" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama lengkap atau username">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">Semua Role</option>
                            <?php foreach (($roleOptions ?? []) as $role): ?>
                                <option value="<?= e((string) $role['code']) ?>" <?= ($roleCode ?? '') === (string) $role['code'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-light">Cari</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="small text-secondary text-uppercase fw-semibold mb-2">Catatan akses</div>
                <ul class="small mb-0 ps-3 text-secondary">
                    <li>Role admin tidak dikelola dari halaman ini.</li>
                    <li>Password baru minimal 8 karakter.</li>
                    <li>Reset password akan membuat password sementara baru.</li>
                    <li>Jika password dikosongkan saat edit, password lama tetap dipakai.</li>
                    <?php if ($mfaGloballyEnabled): ?>
                        <li>MFA dapat dipaksa per akun dengan secret TOTP.</li>
                    <?php else: ?>
                        <li>OTP login sedang dimatikan secara global, jadi kontrol MFA disembunyikan dari tampilan harian.</li>
                    <?php endif; ?>
                    <li>Akun nonaktif tidak bisa login.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle mb-0 coa-table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <?php if ($mfaGloballyEnabled): ?><th>MFA</th><?php endif; ?>
                        <th>Login Terakhir</th>
                        <th class="text-end">Dipakai Jurnal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (($rows ?? []) === []): ?>
                    <tr><td colspan="<?= $mfaGloballyEnabled ? '8' : '7' ?>" class="text-center text-secondary py-5">Belum ada akun bendahara / pimpinan yang cocok dengan pencarian.</td></tr>
                <?php else: foreach ($rows as $userRow): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $userRow['full_name']) ?></td>
                        <td><?= e((string) $userRow['username']) ?></td>
                        <td><span class="badge <?= ($userRow['role_code'] ?? '') === 'bendahara' ? 'text-bg-info' : 'text-bg-primary' ?>"><?= e((string) $userRow['role_name']) ?></span></td>
                        <td><span class="badge <?= (int) $userRow['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $userRow['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <?php if ($mfaGloballyEnabled): ?>
                            <td><span class="badge <?= (int) ($userRow['mfa_enabled'] ?? 0) === 1 ? 'text-bg-warning' : 'text-bg-dark' ?>"><?= (int) ($userRow['mfa_enabled'] ?? 0) === 1 ? 'Aktif' : 'Off' ?></span></td>
                        <?php endif; ?>
                        <td><?= e(!empty($userRow['last_login_at']) ? format_id_date(substr((string) $userRow['last_login_at'], 0, 10)) . ' ' . substr((string) $userRow['last_login_at'], 11, 5) : '-') ?></td>
                        <td class="text-end"><?= e(number_format((int) ($userRow['journal_count'] ?? 0), 0, ',', '.')) ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <a href="<?= e(base_url('/user-accounts/edit?id=' . (int) $userRow['id'])) ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form method="post" action="<?= e(base_url('/user-accounts/reset-password?id=' . (int) $userRow['id'])) ?>" class="d-inline m-0" onsubmit="return confirm('Reset password akun ini dan buat password sementara baru?');">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-info">Reset Password</button>
                                </form>
                                <form method="post" action="<?= e(base_url('/user-accounts/toggle-active?id=' . (int) $userRow['id'])) ?>" class="d-inline m-0">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-sm <?= (int) $userRow['is_active'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                        <?= (int) $userRow['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
</div>
