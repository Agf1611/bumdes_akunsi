<?php declare(strict_types=1); ?>
<?php
$listing = is_array($listing ?? null) ? $listing : listing_paginate($rows ?? []);
$rows = $listing['items'] ?? ($rows ?? []);
$listingPath = '/user-accounts';
$mfaGloballyEnabled = (bool) (auth_config('mfa')['enabled'] ?? false);
$summary = is_array($summary ?? null) ? $summary : [];
$currentUserId = (int) ($currentUserId ?? 0);
$searchValue = (string) ($search ?? '');
$selectedRoleCode = (string) ($roleCode ?? '');
?>
<style>
.user-admin-page .user-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.user-admin-page .user-summary-card {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 22px;
    background: rgba(255, 255, 255, .92);
    box-shadow: 0 18px 40px rgba(15, 23, 42, .06);
    padding: 1.05rem 1.1rem;
}

.user-admin-page .user-summary-card__label {
    display: block;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    margin-bottom: .3rem;
}

.user-admin-page .user-summary-card__value {
    display: block;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
}

.user-admin-page .user-summary-card__meta {
    display: block;
    margin-top: .35rem;
    color: #64748b;
    font-size: .88rem;
}

.user-admin-page .user-filter-card,
.user-admin-page .user-table-card,
.user-admin-page .user-help-card {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .06);
}

.user-admin-page .user-help-card {
    background: linear-gradient(135deg, rgba(15, 23, 42, .95), rgba(37, 99, 235, .9));
    color: #e2e8f0;
}

.user-admin-page .user-help-card .text-secondary,
.user-admin-page .user-help-card .small {
    color: rgba(226, 232, 240, .78) !important;
}

.user-admin-page .user-filter-stack {
    display: grid;
    gap: .9rem;
}

.user-admin-page .user-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .4rem .75rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: #1d4ed8;
    font-size: .82rem;
    font-weight: 600;
}

.user-admin-page .user-list-table thead th {
    white-space: nowrap;
    background: #f8fafc;
    color: #334155;
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom-color: #e2e8f0;
}

.user-admin-page .user-list-table tbody tr:hover {
    background: rgba(59, 130, 246, .04);
}

.user-admin-page .user-person {
    display: grid;
    gap: .15rem;
}

.user-admin-page .user-person__name {
    font-weight: 700;
    color: #0f172a;
}

.user-admin-page .user-person__meta {
    color: #64748b;
    font-size: .85rem;
}

.user-admin-page .user-chip {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: .78rem;
    font-weight: 700;
}

.user-admin-page .user-stats {
    display: grid;
    gap: .25rem;
    justify-items: end;
}

.user-admin-page .user-stats__value {
    font-weight: 700;
    color: #0f172a;
}

.user-admin-page .user-stats__meta {
    color: #64748b;
    font-size: .82rem;
}

.user-admin-page .user-action-group {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .5rem;
}

.user-admin-page .user-inline-note {
    border: 1px dashed rgba(59, 130, 246, .28);
    border-radius: 18px;
    background: rgba(59, 130, 246, .06);
    color: #1e3a8a;
    padding: .9rem 1rem;
}
</style>

<div class="user-account-page user-admin-page module-page">
    <section class="module-hero mb-4">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Pengguna</div>
                <h1 class="module-hero__title">Pengelolaan Akun yang lebih rapi dan lebih enak dipakai</h1>
                <p class="module-hero__text">Sekarang akun admin, bendahara, dan pimpinan bisa dikelola dari satu tempat. Saya rapikan tampilannya supaya edit akun, reset password, dan cek status login terasa lebih jelas.</p>
            </div>
            <div class="module-hero__actions">
                <a href="<?= e(base_url('/user-accounts/create')) ?>" class="btn btn-primary">Tambah Akun Pengguna</a>
            </div>
        </div>
    </section>

    <section class="user-summary-grid mb-4">
        <article class="user-summary-card">
            <span class="user-summary-card__label">Total Akun</span>
            <span class="user-summary-card__value"><?= e(number_format((int) ($summary['total_users'] ?? 0), 0, ',', '.')) ?></span>
            <span class="user-summary-card__meta">Admin, bendahara, dan pimpinan yang dapat dikelola</span>
        </article>
        <article class="user-summary-card">
            <span class="user-summary-card__label">Akun Aktif</span>
            <span class="user-summary-card__value"><?= e(number_format((int) ($summary['active_users'] ?? 0), 0, ',', '.')) ?></span>
            <span class="user-summary-card__meta"><?= e(number_format((int) ($summary['inactive_users'] ?? 0), 0, ',', '.')) ?> akun sedang nonaktif</span>
        </article>
        <article class="user-summary-card">
            <span class="user-summary-card__label">Per Role</span>
            <span class="user-summary-card__value"><?= e((string) ($summary['admin_users'] ?? 0)) ?> / <?= e((string) ($summary['bendahara_users'] ?? 0)) ?> / <?= e((string) ($summary['pimpinan_users'] ?? 0)) ?></span>
            <span class="user-summary-card__meta">Admin / Bendahara / Pimpinan</span>
        </article>
        <article class="user-summary-card">
            <span class="user-summary-card__label">MFA</span>
            <span class="user-summary-card__value"><?= $mfaGloballyEnabled ? e(number_format((int) ($summary['mfa_users'] ?? 0), 0, ',', '.')) : 'Off' ?></span>
            <span class="user-summary-card__meta"><?= $mfaGloballyEnabled ? 'Akun dengan OTP aktif' : 'OTP global sedang dimatikan' ?></span>
        </article>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card user-filter-card h-100">
                <div class="card-body p-4">
                    <div class="user-filter-stack">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="user-filter-pill">Filter & Pencarian</span>
                                <h2 class="h5 mt-3 mb-1">Cari akun lebih cepat</h2>
                                <p class="text-secondary mb-0">Pakai nama, username, atau role untuk menyaring akun yang ingin Anda kelola.</p>
                            </div>
                            <?php if ($searchValue !== '' || $selectedRoleCode !== ''): ?>
                                <a href="<?= e(base_url('/user-accounts')) ?>" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
                            <?php endif; ?>
                        </div>
                        <form method="get" action="<?= e(base_url('/user-accounts')) ?>" class="row g-3 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label">Cari Nama atau Username</label>
                                <input type="text" class="form-control" name="search" value="<?= e($searchValue) ?>" placeholder="Contoh: admin, bendahara, nama pengguna">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="">Semua Role</option>
                                    <?php foreach (($roleOptions ?? []) as $role): ?>
                                        <option value="<?= e((string) $role['code']) ?>" <?= $selectedRoleCode === (string) $role['code'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-primary">Tampil</button>
                            </div>
                        </form>
                        <div class="user-inline-note small">
                            Admin juga sudah bisa diedit dari halaman ini. Namun untuk keamanan, akun admin yang sedang Anda pakai tidak bisa dinonaktifkan atau diturunkan rolenya langsung dari sini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card user-help-card h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-semibold mb-2">Panduan Singkat</div>
                    <div class="d-grid gap-3 small">
                        <div>
                            <strong class="d-block text-white mb-1">Edit akun</strong>
                            Gunakan tombol edit untuk ubah nama, role, username, status, atau password tanpa harus reset semuanya.
                        </div>
                        <div>
                            <strong class="d-block text-white mb-1">Reset password</strong>
                            Sistem akan membuat password sementara baru. Cocok jika pengguna lupa password atau perangkat berganti.
                        </div>
                        <div>
                            <strong class="d-block text-white mb-1">Status aktif</strong>
                            Akun nonaktif tidak bisa login, tetapi datanya tetap tersimpan untuk audit dan histori transaksi.
                        </div>
                        <div>
                            <strong class="d-block text-white mb-1">MFA</strong>
                            <?= $mfaGloballyEnabled ? 'Jika OTP global aktif, Anda bisa mengatur secret MFA per akun.' : 'OTP global sedang dimatikan, jadi pengelolaan MFA tidak ditampilkan sebagai fokus utama.' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card user-table-card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 user-list-table">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Role & Status</th>
                            <?php if ($mfaGloballyEnabled): ?><th>Keamanan</th><?php endif; ?>
                            <th class="text-end">Aktivitas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (($rows ?? []) === []): ?>
                        <tr>
                            <td colspan="<?= $mfaGloballyEnabled ? '5' : '4' ?>" class="text-center text-secondary py-5">Belum ada akun yang cocok dengan filter saat ini.</td>
                        </tr>
                    <?php else: foreach ($rows as $userRow): ?>
                        <?php
                        $isCurrentUser = (int) ($userRow['id'] ?? 0) === $currentUserId;
                        $roleBadgeClass = match ((string) ($userRow['role_code'] ?? '')) {
                            'admin' => 'text-bg-danger',
                            'bendahara' => 'text-bg-info',
                            'pimpinan' => 'text-bg-primary',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <tr>
                            <td>
                                <div class="user-person">
                                    <div class="user-person__name">
                                        <?= e((string) $userRow['full_name']) ?>
                                        <?php if ($isCurrentUser): ?>
                                            <span class="user-chip ms-2">Akun Anda</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-person__meta">@<?= e((string) $userRow['username']) ?></div>
                                    <div class="user-person__meta">Dibuat: <?= e(!empty($userRow['created_at']) ? format_id_date(substr((string) $userRow['created_at'], 0, 10)) : '-') ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="d-grid gap-2">
                                    <div>
                                        <span class="badge <?= e($roleBadgeClass) ?>"><?= e((string) $userRow['role_name']) ?></span>
                                        <span class="badge <?= (int) $userRow['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?> ms-1"><?= (int) $userRow['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                                    </div>
                                    <div class="small text-secondary">
                                        <?= (int) $userRow['is_active'] === 1 ? 'Bisa login dan dipakai bekerja.' : 'Disimpan untuk histori, tetapi login ditutup.' ?>
                                    </div>
                                </div>
                            </td>
                            <?php if ($mfaGloballyEnabled): ?>
                                <td>
                                    <div class="d-grid gap-2">
                                        <div>
                                            <span class="badge <?= (int) ($userRow['mfa_enabled'] ?? 0) === 1 ? 'text-bg-warning' : 'text-bg-dark' ?>"><?= (int) ($userRow['mfa_enabled'] ?? 0) === 1 ? 'MFA Aktif' : 'MFA Off' ?></span>
                                        </div>
                                        <div class="small text-secondary"><?= (int) ($userRow['mfa_enabled'] ?? 0) === 1 ? 'Perlu OTP saat login.' : 'Login cukup username dan password.' ?></div>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="text-end">
                                <div class="user-stats">
                                    <div class="user-stats__value"><?= e(number_format((int) ($userRow['journal_count'] ?? 0), 0, ',', '.')) ?> jurnal</div>
                                    <div class="user-stats__meta">Login terakhir: <?= e(!empty($userRow['last_login_at']) ? format_id_date(substr((string) $userRow['last_login_at'], 0, 10)) . ' ' . substr((string) $userRow['last_login_at'], 11, 5) : '-') ?></div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="user-action-group">
                                    <a href="<?= e(base_url('/user-accounts/edit?id=' . (int) $userRow['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" action="<?= e(base_url('/user-accounts/reset-password?id=' . (int) $userRow['id'])) ?>" class="m-0" onsubmit="return confirm('Reset password akun ini dan buat password sementara baru?');">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-info">Reset Password</button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('/user-accounts/toggle-active?id=' . (int) $userRow['id'])) ?>" class="m-0">
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
