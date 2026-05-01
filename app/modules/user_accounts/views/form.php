<?php declare(strict_types=1); ?>
<?php
$mfaGloballyEnabled = (bool) (auth_config('mfa')['enabled'] ?? false);
$currentUserId = (int) ($currentUserId ?? 0);
$isEditing = is_array($row ?? null);
$isCurrentUser = $isEditing && (int) ($row['id'] ?? 0) === $currentUserId;
$selectedRoleCode = (string) ($formData['role_code'] ?? 'bendahara');
$selectedRoleName = 'Bendahara';
foreach (($roleOptions ?? []) as $roleOption) {
    if ((string) ($roleOption['code'] ?? '') === $selectedRoleCode) {
        $selectedRoleName = (string) ($roleOption['name'] ?? $selectedRoleName);
        break;
    }
}
?>
<style>
.user-account-form-page .user-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(280px, .95fr);
    gap: 1.5rem;
}

.user-account-form-page .user-form-card,
.user-account-form-page .user-form-sidecard {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .06);
}

.user-account-form-page .user-form-section + .user-form-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(226, 232, 240, .9);
}

.user-account-form-page .user-form-section__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .4rem .8rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: #1d4ed8;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.user-account-form-page .user-form-section__title {
    margin: .9rem 0 .35rem;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
}

.user-account-form-page .user-form-section__text {
    color: #64748b;
    margin-bottom: 0;
}

.user-account-form-page .user-form-note,
.user-account-form-page .user-form-warning {
    border-radius: 18px;
    padding: 1rem 1.05rem;
}

.user-account-form-page .user-form-note {
    border: 1px dashed rgba(59, 130, 246, .28);
    background: rgba(59, 130, 246, .06);
    color: #1e3a8a;
}

.user-account-form-page .user-form-warning {
    border: 1px solid rgba(245, 158, 11, .24);
    background: rgba(245, 158, 11, .1);
    color: #92400e;
}

.user-account-form-page .user-side-stat {
    display: grid;
    gap: .2rem;
    padding: 1rem 1.05rem;
    border-radius: 20px;
    background: #f8fafc;
    border: 1px solid rgba(226, 232, 240, .95);
}

.user-account-form-page .user-side-stat__label {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
}

.user-account-form-page .user-side-stat__value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
}

.user-account-form-page .user-side-list {
    display: grid;
    gap: .85rem;
}

.user-account-form-page .user-side-list strong {
    display: block;
    color: #0f172a;
    margin-bottom: .2rem;
}

.user-account-form-page .user-side-list p {
    color: #64748b;
    margin: 0;
    font-size: .94rem;
}

.user-account-form-page .user-security-card {
    border-radius: 20px;
    border: 1px solid rgba(15, 23, 42, .08);
    background: linear-gradient(135deg, rgba(15, 23, 42, .95), rgba(30, 64, 175, .92));
    color: #e2e8f0;
}

.user-account-form-page .user-security-card p,
.user-account-form-page .user-security-card li,
.user-account-form-page .user-security-card .text-secondary {
    color: rgba(226, 232, 240, .82) !important;
}

.user-account-form-page .user-form-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
}

.user-account-form-page .user-form-action i {
    font-size: 1rem;
    line-height: 1;
}

@media (max-width: 991.98px) {
    .user-account-form-page .user-form-grid {
        grid-template-columns: 1fr;
    }

    .user-account-form-page .module-hero__actions,
    .user-account-form-page .module-hero__actions .btn,
    .user-account-form-page .d-flex.justify-content-end .btn {
        width: 100%;
    }
}
</style>

<div class="user-account-form-page module-page">
    <section class="module-hero mb-4">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Pengguna</div>
                <h1 class="module-hero__title"><?= e($title) ?></h1>
                <p class="module-hero__text">Form ini saya rapikan supaya admin lebih mudah mengelola identitas akun, akses login, status aktif, dan keamanan pengguna dalam satu alur yang lebih nyaman.</p>
            </div>
            <div class="module-hero__actions">
                <a href="<?= e(base_url('/user-accounts')) ?>" class="btn btn-outline-secondary user-form-action">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    <span>Data User</span>
                </a>
            </div>
        </div>
    </section>

    <div class="user-form-grid">
        <div class="card user-form-card">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e($row ? base_url('/user-accounts/update?id=' . (int) $row['id']) : base_url('/user-accounts/store')) ?>" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <?php if ($isCurrentUser): ?>
                        <div class="user-form-warning mb-4">
                            <div class="fw-semibold mb-1">Anda sedang mengedit akun yang sedang dipakai login</div>
                            <div class="small mb-0">Untuk menjaga akses, akun ini tidak boleh dinonaktifkan dan role admin aktif tidak boleh diturunkan langsung dari form ini.</div>
                        </div>
                    <?php endif; ?>

                    <section class="user-form-section">
                        <span class="user-form-section__eyebrow">Identitas Akun</span>
                        <h2 class="user-form-section__title">Siapa pengguna akun ini?</h2>
                        <p class="user-form-section__text">Isi data utama dulu supaya akun mudah dikenali saat dipakai di jurnal, audit, dan login harian.</p>

                        <div class="row g-4 mt-1">
                            <div class="col-md-7">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" maxlength="100" value="<?= e((string) ($formData['full_name'] ?? '')) ?>" placeholder="Contoh: Ahmad Fauzi" required>
                                <div class="form-text text-secondary">Nama ini akan tampil di daftar user, histori aktivitas, dan beberapa catatan sistem.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Role Pengguna <span class="text-danger">*</span></label>
                                <select name="role_code" class="form-select" required>
                                    <?php foreach (($roleOptions ?? []) as $role): ?>
                                        <option value="<?= e((string) $role['code']) ?>" <?= $selectedRoleCode === (string) $role['code'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-secondary">Pilih peran sesuai akses kerja: Admin, Bendahara, atau Pimpinan.</div>
                            </div>
                        </div>
                    </section>

                    <section class="user-form-section">
                        <span class="user-form-section__eyebrow">Akses Login</span>
                        <h2 class="user-form-section__title">Atur username dan status akun</h2>
                        <p class="user-form-section__text">Bagian ini mengatur bagaimana pengguna masuk ke sistem dan apakah akunnya sedang dibuka atau ditutup sementara.</p>

                        <div class="row g-4 mt-1">
                            <div class="col-md-8">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" maxlength="50" value="<?= e((string) ($formData['username'] ?? '')) ?>" placeholder="mis. admin.bumdes atau bendahara.unit1" required>
                                <div class="form-text text-secondary">Gunakan 4-50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Akun</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= (string) ($formData['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="0" <?= (string) ($formData['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                                <div class="form-text text-secondary">Akun nonaktif tetap tersimpan, tetapi tidak bisa login.</div>
                            </div>
                        </div>

                        <div class="user-form-note small mt-4">
                            Status nonaktif cocok untuk pengguna lama yang tidak lagi bertugas, tanpa menghapus histori jurnal dan audit yang sudah tercatat.
                        </div>
                    </section>

                    <section class="user-form-section">
                        <span class="user-form-section__eyebrow">Keamanan</span>
                        <h2 class="user-form-section__title"><?= $isEditing ? 'Perbarui password dan MFA bila diperlukan' : 'Buat password awal yang aman' ?></h2>
                        <p class="user-form-section__text">Gunakan password kuat dan aktifkan OTP hanya jika perangkat autentikator pengguna memang sudah siap dipakai.</p>

                        <div class="row g-4 mt-1">
                            <div class="col-md-6">
                                <label class="form-label"><?= $isEditing ? 'Password Baru' : 'Password' ?><?= $isEditing ? '' : ' <span class="text-danger">*</span>' ?></label>
                                <input type="password" class="form-control" name="password" minlength="8" <?= $isEditing ? '' : 'required' ?>>
                                <div class="form-text text-secondary">
                                    <?= $isEditing ? 'Kosongkan jika password lama tidak perlu diganti.' : 'Minimal 8 karakter. Kombinasi huruf besar, huruf kecil, angka, dan simbol lebih aman.' ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password<?= $isEditing ? ' Baru' : '' ?><?= $isEditing ? '' : ' <span class="text-danger">*</span>' ?></label>
                                <input type="password" class="form-control" name="password_confirmation" minlength="8" <?= $isEditing ? '' : 'required' ?>>
                                <div class="form-text text-secondary">Pastikan isinya sama persis dengan password yang dimasukkan di sebelah kiri.</div>
                            </div>

                            <?php if ($mfaGloballyEnabled): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Multi-Factor Authentication</label>
                                    <select name="mfa_enabled" class="form-select">
                                        <option value="0" <?= (string) ($formData['mfa_enabled'] ?? '0') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                                        <option value="1" <?= (string) ($formData['mfa_enabled'] ?? '0') === '1' ? 'selected' : '' ?>>Aktif</option>
                                    </select>
                                    <div class="form-text text-secondary">Jika aktif, pengguna harus memasukkan OTP saat login.</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Secret MFA (TOTP Base32)</label>
                                    <input type="text" class="form-control" name="mfa_secret" maxlength="64" value="<?= e((string) ($formData['mfa_secret'] ?? '')) ?>" placeholder="Contoh: JBSWY3DPEHPK3PXP">
                                    <div class="form-text text-secondary">Isi secret hanya jika perangkat OTP sudah di-setup. Format yang diterima base32 16-64 karakter.</div>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">OTP login sedang dimatikan secara global, jadi pengaturan MFA tidak ditampilkan sebagai fokus utama pada form ini.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-2">
                        <a href="<?= e(base_url('/user-accounts')) ?>" class="btn btn-outline-secondary user-form-action" title="Batal dan kembali">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                            <span>Batal</span>
                        </a>
                        <button type="submit" class="btn btn-primary user-form-action">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span><?= $isEditing ? 'Simpan Perubahan' : 'Simpan Akun' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-grid gap-4">
            <div class="card user-form-sidecard">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-semibold text-secondary mb-2">Ringkasan Cepat</div>
                    <div class="d-grid gap-3">
                        <div class="user-side-stat">
                            <span class="user-side-stat__label">Mode Form</span>
                            <span class="user-side-stat__value"><?= $isEditing ? 'Edit Akun' : 'Akun Baru' ?></span>
                        </div>
                        <div class="user-side-stat">
                            <span class="user-side-stat__label">Role Dipilih</span>
                            <span class="user-side-stat__value"><?= e($selectedRoleName) ?></span>
                        </div>
                        <div class="user-side-stat">
                            <span class="user-side-stat__label">Status Saat Ini</span>
                            <span class="user-side-stat__value"><?= (string) ($formData['is_active'] ?? '1') === '1' ? 'Aktif' : 'Nonaktif' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card user-form-sidecard user-security-card">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-semibold mb-2">Panduan Pengelolaan</div>
                    <div class="user-side-list">
                        <div>
                            <strong>Admin sekarang bisa diedit</strong>
                            <p>Gunakan form yang sama untuk merapikan identitas, login, dan keamanan akun admin tanpa pindah halaman lain.</p>
                        </div>
                        <div>
                            <strong>Nonaktif lebih aman daripada hapus</strong>
                            <p>Jika petugas berganti, sebaiknya akun dinonaktifkan agar histori login dan jurnal tetap utuh.</p>
                        </div>
                        <div>
                            <strong>Password hanya diubah saat perlu</strong>
                            <p>Untuk edit data biasa seperti nama atau role, kolom password boleh dibiarkan kosong.</p>
                        </div>
                        <div>
                            <strong>MFA aktif bila perangkat siap</strong>
                            <p><?= $mfaGloballyEnabled ? 'Pastikan secret yang diisi sudah dipindahkan ke aplikasi autentikator pengguna.' : 'Saat OTP global mati, fokuskan pengelolaan pada username, status, dan password dulu.' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card user-form-sidecard">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-semibold text-secondary mb-2">Checklist Singkat</div>
                    <div class="user-side-list">
                        <div>
                            <strong>Nama lengkap mudah dikenali</strong>
                            <p>Gunakan nama asli pengguna supaya audit dan histori kerja lebih mudah dibaca.</p>
                        </div>
                        <div>
                            <strong>Username rapi dan konsisten</strong>
                            <p>Contoh yang enak dipakai: `admin.bumdes`, `bendahara.pusat`, atau `pimpinan.unit`.</p>
                        </div>
                        <div>
                            <strong>Role sesuai tugas</strong>
                            <p>Pilih role berdasarkan tanggung jawab kerja agar menu dan aksesnya tetap tertata.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
