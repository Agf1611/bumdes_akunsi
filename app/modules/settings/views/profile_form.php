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

$requiredKeys = [
    'bumdes_name',
    'address',
    'director_name',
    'director_position',
    'treasurer_name',
    'treasurer_position',
    'active_period_start',
    'active_period_end',
];
$filledRequiredCount = 0;
foreach ($requiredKeys as $key) {
    if (trim((string) ($profileData[$key] ?? '')) !== '') {
        $filledRequiredCount++;
    }
}

$documentCount = 0;
foreach (['logo_path', 'signature_path', 'treasurer_signature_path'] as $key) {
    if (trim((string) ($profileData[$key] ?? '')) !== '') {
        $documentCount++;
    }
}

$identitySummaryParts = array_filter([
    trim((string) ($profileData['village_name'] ?? '')) !== '' ? 'Desa ' . trim((string) $profileData['village_name']) : '',
    trim((string) ($profileData['district_name'] ?? '')) !== '' ? 'Kec. ' . trim((string) $profileData['district_name']) : '',
    trim((string) ($profileData['regency_name'] ?? '')) !== '' ? trim((string) $profileData['regency_name']) : '',
    trim((string) ($profileData['province_name'] ?? '')) !== '' ? trim((string) $profileData['province_name']) : '',
]);
$identitySummary = $identitySummaryParts !== [] ? implode(' · ', $identitySummaryParts) : 'Wilayah administrasi belum lengkap';

$contactSummaryParts = array_filter([
    trim((string) ($profileData['phone'] ?? '')),
    trim((string) ($profileData['email'] ?? '')),
]);
$contactSummary = $contactSummaryParts !== [] ? implode(' · ', $contactSummaryParts) : 'Kontak belum lengkap';
?>

<style>
.profile-settings-page {
    --profile-border: rgba(148, 163, 184, 0.22);
    --profile-panel: rgba(15, 23, 42, 0.58);
    --profile-panel-soft: rgba(15, 23, 42, 0.4);
}

.profile-settings-page .profile-hero {
    background:
        radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 34%),
        linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.92));
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 28px;
    padding: 1.5rem;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.18);
}

.profile-settings-page .profile-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .4rem .85rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(226, 232, 240, 0.88);
    font-size: .78rem;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.profile-settings-page .profile-hero__title {
    font-size: clamp(1.9rem, 3vw, 2.7rem);
    line-height: 1.05;
    color: #f8fafc;
    margin: 1rem 0 .75rem;
}

.profile-settings-page .profile-hero__text {
    color: rgba(226, 232, 240, 0.82);
    max-width: 56rem;
    margin: 0;
}

.profile-settings-page .profile-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.profile-settings-page .profile-summary-card {
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.08);
    padding: 1rem 1.1rem;
    backdrop-filter: blur(8px);
}

.profile-settings-page .profile-summary-card__label {
    display: block;
    color: rgba(226, 232, 240, 0.72);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: .45rem;
}

.profile-settings-page .profile-summary-card__value {
    display: block;
    color: #fff;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}

.profile-settings-page .profile-summary-card__meta {
    display: block;
    color: rgba(226, 232, 240, 0.74);
    font-size: .9rem;
    margin-top: .45rem;
}

.profile-settings-page .profile-section-card,
.profile-settings-page .profile-side-card {
    border: 1px solid var(--profile-border);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

.profile-settings-page .profile-section-card {
    padding: 1.35rem 1.35rem 1.45rem;
}

.profile-settings-page .profile-side-card {
    padding: 1.2rem;
}

.profile-settings-page .profile-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.profile-settings-page .profile-section-head__title {
    margin: 0;
    font-size: 1.08rem;
    color: #0f172a;
}

.profile-settings-page .profile-section-head__text {
    margin: .35rem 0 0;
    color: #64748b;
    font-size: .94rem;
}

.profile-settings-page .profile-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    color: #fff;
    font-weight: 700;
    flex-shrink: 0;
}

.profile-settings-page .profile-input-hint {
    color: #64748b;
    font-size: .82rem;
    margin-top: .35rem;
}

.profile-settings-page .profile-inline-note {
    border: 1px dashed rgba(59, 130, 246, 0.28);
    border-radius: 18px;
    background: rgba(59, 130, 246, 0.06);
    color: #1e3a8a;
    padding: .9rem 1rem;
    font-size: .9rem;
}

.profile-settings-page .profile-preview-frame {
    min-height: 210px;
    border: 1px dashed rgba(148, 163, 184, 0.5);
    border-radius: 22px;
    background:
        linear-gradient(135deg, rgba(248, 250, 252, 0.94), rgba(241, 245, 249, 0.84));
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 1rem;
}

.profile-settings-page .profile-preview-frame--signature {
    min-height: 170px;
}

.profile-settings-page .logo-preview-image,
.profile-settings-page .signature-preview-image {
    max-height: 180px;
    width: auto;
    object-fit: contain;
}

.profile-settings-page .signature-preview-image {
    max-height: 140px;
}

.profile-settings-page .profile-checklist {
    display: grid;
    gap: .85rem;
}

.profile-settings-page .profile-checklist__item {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 18px;
    background: rgba(248, 250, 252, 0.95);
    padding: .85rem .95rem;
}

.profile-settings-page .profile-checklist__label {
    display: block;
    color: #0f172a;
    font-weight: 600;
    margin-bottom: .2rem;
}

.profile-settings-page .profile-checklist__meta {
    display: block;
    color: #64748b;
    font-size: .86rem;
}

.profile-settings-page .profile-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .38rem .7rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.08);
    color: #1d4ed8;
    font-size: .8rem;
    font-weight: 600;
}

.profile-settings-page .profile-sticky {
    position: sticky;
    top: 1.5rem;
}

.profile-settings-page .profile-aside-stack {
    display: grid;
    gap: 1rem;
}

.profile-settings-page .profile-toggle-card {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 18px;
    background: rgba(248, 250, 252, 0.82);
    padding: .95rem 1rem;
}

.profile-settings-page .profile-toggle-card .form-check {
    margin: 0;
}

@media (max-width: 991.98px) {
    .profile-settings-page .profile-sticky {
        position: static;
    }
}
</style>

<div class="module-page profile-settings-page">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <section class="profile-hero mb-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
                    <div class="pe-xl-4">
                        <span class="profile-hero__eyebrow">Pengaturan Profil</span>
                        <h1 class="profile-hero__title">Profil BUMDes yang lebih cepat diisi dan lebih mudah dicek</h1>
                        <p class="profile-hero__text">Saya rapikan halaman ini agar urutan input lebih masuk akal: mulai dari identitas lembaga, kontak, periode aktif, penandatangan, lalu aturan kwitansi. Jadi saat mengedit, Anda tidak perlu lompat-lompat mencari data.</p>
                    </div>
                    <div class="profile-summary-grid flex-grow-1">
                        <article class="profile-summary-card">
                            <span class="profile-summary-card__label">Data Wajib</span>
                            <span class="profile-summary-card__value"><?= e((string) $filledRequiredCount) ?>/<?= e((string) count($requiredKeys)) ?></span>
                            <span class="profile-summary-card__meta">Nama lembaga, alamat, penandatangan, dan periode aktif</span>
                        </article>
                        <article class="profile-summary-card">
                            <span class="profile-summary-card__label">Dokumen</span>
                            <span class="profile-summary-card__value"><?= e((string) $documentCount) ?>/3</span>
                            <span class="profile-summary-card__meta">Logo, tanda tangan direktur, dan tanda tangan bendahara</span>
                        </article>
                        <article class="profile-summary-card">
                            <span class="profile-summary-card__label">Periode Aktif</span>
                            <span class="profile-summary-card__value"><?= e(active_period_label($profileData['active_period_start'], $profileData['active_period_end'])) ?></span>
                            <span class="profile-summary-card__meta">Dipakai otomatis untuk identitas periode aplikasi</span>
                        </article>
                    </div>
                </div>
            </section>

            <form method="post" action="<?= e(base_url('/settings/profile')) ?>" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                <div class="row g-4">
                    <div class="col-12 col-xl-8">
                        <div class="d-grid gap-4">
                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <span class="profile-pill">Bagian yang paling sering diedit</span>
                                        <h2 class="profile-section-head__title mt-2">Identitas utama lembaga</h2>
                                        <p class="profile-section-head__text">Isi nama lembaga dan alamat resmi yang akan dipakai di header aplikasi, cetak laporan, dan dokumen transaksi.</p>
                                    </div>
                                    <span class="profile-step-badge">1</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="bumdes_name" class="form-label">Nama BUMDes <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg" id="bumdes_name" name="bumdes_name" maxlength="150" value="<?= e($profileData['bumdes_name']) ?>" placeholder="Contoh: BUMDes Maju Sejahtera" autocomplete="organization" required>
                                        <div class="profile-input-hint">Nama ini tampil di sidebar, topbar, kop laporan, dan berbagai cetakan resmi.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="address" name="address" rows="4" maxlength="500" placeholder="Contoh: Jl. Raya Desa No. 12, dekat kantor desa, RT/RW bila perlu" required><?= e($profileData['address']) ?></textarea>
                                        <div class="profile-input-hint">Isi alamat yang paling sering dipakai di dokumen resmi agar tidak perlu diketik ulang di tempat lain.</div>
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <h2 class="profile-section-head__title">Wilayah, legalitas, dan kontak</h2>
                                        <p class="profile-section-head__text">Bagian ini membantu melengkapi identitas administratif BUMDes untuk laporan, backup, dan dokumen legal.</p>
                                    </div>
                                    <span class="profile-step-badge">2</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="village_name" class="form-label">Desa</label>
                                        <input type="text" class="form-control" id="village_name" name="village_name" maxlength="120" value="<?= e($profileData['village_name']) ?>" placeholder="Nama desa">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="district_name" class="form-label">Kecamatan</label>
                                        <input type="text" class="form-control" id="district_name" name="district_name" maxlength="120" value="<?= e($profileData['district_name']) ?>" placeholder="Nama kecamatan">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="regency_name" class="form-label">Kabupaten / Kota</label>
                                        <input type="text" class="form-control" id="regency_name" name="regency_name" maxlength="120" value="<?= e($profileData['regency_name']) ?>" placeholder="Nama kabupaten atau kota">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="province_name" class="form-label">Provinsi</label>
                                        <input type="text" class="form-control" id="province_name" name="province_name" maxlength="120" value="<?= e($profileData['province_name']) ?>" placeholder="Nama provinsi">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="legal_entity_no" class="form-label">No. Badan Hukum</label>
                                        <input type="text" class="form-control" id="legal_entity_no" name="legal_entity_no" maxlength="120" value="<?= e($profileData['legal_entity_no']) ?>" placeholder="Nomor SK / badan hukum">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="nib" class="form-label">NIB</label>
                                        <input type="text" class="form-control" id="nib" name="nib" maxlength="50" value="<?= e($profileData['nib']) ?>" placeholder="Nomor Induk Berusaha">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="npwp" class="form-label">NPWP</label>
                                        <input type="text" class="form-control" id="npwp" name="npwp" maxlength="50" value="<?= e($profileData['npwp']) ?>" placeholder="NPWP lembaga">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Telepon / WhatsApp</label>
                                        <input type="text" class="form-control" id="phone" name="phone" maxlength="30" value="<?= e($profileData['phone']) ?>" placeholder="Contoh: 0812xxxxxxx" autocomplete="tel">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($profileData['email']) ?>" placeholder="Contoh: bumdes@email.id" autocomplete="email">
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <h2 class="profile-section-head__title">Periode aktif dan logo lembaga</h2>
                                        <p class="profile-section-head__text">Periode aktif membantu aplikasi menampilkan konteks bulan kerja saat ini. Logo akan dipakai di sidebar dan cetakan resmi.</p>
                                    </div>
                                    <span class="profile-step-badge">3</span>
                                </div>
                                <div class="row g-4 align-items-start">
                                    <div class="col-lg-6">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="profile-inline-note">
                                                    Jika periode aktif berubah, tampilan ringkasan laporan dan konteks kerja pengguna juga ikut menyesuaikan.
                                                </div>
                                            </div>
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
                                    <div class="col-lg-6">
                                        <label for="logo" class="form-label">Logo Lembaga</label>
                                        <div class="profile-preview-frame mb-3">
                                            <?php if ($profileData['logo_path'] !== ''): ?>
                                                <img src="<?= e(upload_url($profileData['logo_path'])) ?>" alt="Logo BUMDes" class="img-fluid rounded logo-preview-image">
                                            <?php else: ?>
                                                <div class="text-center text-secondary small px-3">Belum ada logo. Unggah logo agar tampilan aplikasi dan dokumen terlihat lebih resmi.</div>
                                            <?php endif; ?>
                                        </div>
                                        <input class="form-control" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        <div class="profile-input-hint">Format yang didukung: JPG, PNG, WEBP. Ukuran maksimal 2 MB.</div>
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <h2 class="profile-section-head__title">Penandatangan direktur</h2>
                                        <p class="profile-section-head__text">Nama, jabatan, kota tanda tangan, dan file tanda tangan ini dipakai pada laporan dan dokumen yang memerlukan otorisasi direktur.</p>
                                    </div>
                                    <span class="profile-step-badge">4</span>
                                </div>
                                <div class="row g-4 align-items-start">
                                    <div class="col-lg-7">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="director_name" class="form-label">Nama Direktur <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="director_name" name="director_name" maxlength="120" value="<?= e($profileData['director_name']) ?>" placeholder="Nama direktur / pimpinan" autocomplete="name" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="director_position" class="form-label">Jabatan Direktur <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="director_position" name="director_position" maxlength="100" value="<?= e($profileData['director_position']) ?>" placeholder="Contoh: Direktur" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="signature_city" class="form-label">Kota Tanda Tangan</label>
                                                <input type="text" class="form-control" id="signature_city" name="signature_city" maxlength="100" value="<?= e($profileData['signature_city']) ?>" placeholder="Contoh: Simpang">
                                                <div class="profile-input-hint">Dipakai pada format tanda tangan seperti "Simpang, 30 April 2026".</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="director_sign_threshold" class="form-label">Batas Nominal Wajib Direktur</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" step="0.01" min="0" class="form-control" id="director_sign_threshold" name="director_sign_threshold" value="<?= e($profileData['director_sign_threshold']) ?>" placeholder="0.00">
                                                </div>
                                                <div class="profile-input-hint">Isi `0` jika semua nominal tetap boleh tanpa tanda tangan direktur.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <label for="signature_file" class="form-label">Tanda Tangan Direktur</label>
                                        <div class="profile-preview-frame profile-preview-frame--signature mb-3">
                                            <?php if ($profileData['signature_path'] !== ''): ?>
                                                <img src="<?= e(upload_url($profileData['signature_path'])) ?>" alt="Tanda Tangan Direktur" class="img-fluid rounded signature-preview-image">
                                            <?php else: ?>
                                                <div class="text-center text-secondary small px-3">Belum ada file tanda tangan direktur.</div>
                                            <?php endif; ?>
                                        </div>
                                        <input class="form-control" type="file" id="signature_file" name="signature_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        <div class="profile-input-hint">Sebaiknya gunakan gambar latar polos agar hasil cetak lebih bersih.</div>
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <h2 class="profile-section-head__title">Penandatangan bendahara</h2>
                                        <p class="profile-section-head__text">Bagian ini penting untuk kwitansi, bukti transaksi, dan dokumen operasional yang memerlukan bendahara.</p>
                                    </div>
                                    <span class="profile-step-badge">5</span>
                                </div>
                                <div class="row g-4 align-items-start">
                                    <div class="col-lg-7">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="treasurer_name" class="form-label">Nama Bendahara <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="treasurer_name" name="treasurer_name" maxlength="120" value="<?= e($profileData['treasurer_name']) ?>" placeholder="Nama bendahara" autocomplete="name" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="treasurer_position" class="form-label">Jabatan Bendahara <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="treasurer_position" name="treasurer_position" maxlength="100" value="<?= e($profileData['treasurer_position']) ?>" placeholder="Contoh: Bendahara" required>
                                            </div>
                                            <div class="col-12">
                                                <div class="profile-inline-note">
                                                    Pastikan nama bendahara sama dengan yang biasa dipakai di kwitansi dan jurnal standar agar hasil cetak konsisten.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <label for="treasurer_signature_file" class="form-label">Tanda Tangan Bendahara</label>
                                        <div class="profile-preview-frame profile-preview-frame--signature mb-3">
                                            <?php if ($profileData['treasurer_signature_path'] !== ''): ?>
                                                <img src="<?= e(upload_url($profileData['treasurer_signature_path'])) ?>" alt="Tanda Tangan Bendahara" class="img-fluid rounded signature-preview-image">
                                            <?php else: ?>
                                                <div class="text-center text-secondary small px-3">Belum ada file tanda tangan bendahara.</div>
                                            <?php endif; ?>
                                        </div>
                                        <input class="form-control" type="file" id="treasurer_signature_file" name="treasurer_signature_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section-card">
                                <div class="profile-section-head">
                                    <div>
                                        <h2 class="profile-section-head__title">Aturan tanda tangan kwitansi</h2>
                                        <p class="profile-section-head__text">Atur siapa saja yang tampil saat cetak kwitansi agar sesuai kebiasaan administrasi BUMDes Anda.</p>
                                    </div>
                                    <span class="profile-step-badge">6</span>
                                </div>
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <label for="receipt_signature_mode" class="form-label">Mode Tanda Tangan Kwitansi</label>
                                        <select class="form-select" id="receipt_signature_mode" name="receipt_signature_mode">
                                            <?php foreach ($receiptModes as $modeKey => $modeLabel): ?>
                                                <option value="<?= e($modeKey) ?>" <?= $profileData['receipt_signature_mode'] === $modeKey ? 'selected' : '' ?>><?= e($modeLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="profile-input-hint">Pilihan ini akan dipakai otomatis saat bukti transaksi atau kwitansi dicetak.</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-grid gap-3">
                                            <div class="profile-toggle-card">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="receipt_require_recipient_cash" name="receipt_require_recipient_cash" <?= $profileData['receipt_require_recipient_cash'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-semibold" for="receipt_require_recipient_cash">Penerima wajib tanda tangan untuk transaksi tunai</label>
                                                </div>
                                                <div class="profile-input-hint">Cocok jika kwitansi kas harus selalu ada bukti pihak penerima.</div>
                                            </div>
                                            <div class="profile-toggle-card">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="receipt_require_recipient_transfer" name="receipt_require_recipient_transfer" <?= $profileData['receipt_require_recipient_transfer'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-semibold" for="receipt_require_recipient_transfer">Penerima tetap tampil untuk transaksi transfer</label>
                                                </div>
                                                <div class="profile-input-hint">Aktifkan jika dokumen transfer juga perlu blok tanda tangan penerima.</div>
                                            </div>
                                            <div class="profile-toggle-card">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="show_stamp" name="show_stamp" <?= $profileData['show_stamp'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-semibold" for="show_stamp">Tampilkan ruang stempel pada tanda tangan direktur</label>
                                                </div>
                                                <div class="profile-input-hint">Berguna jika hasil cetak masih perlu dibubuhi stempel basah.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="profile-sticky">
                            <div class="profile-aside-stack">
                                <aside class="profile-side-card">
                                    <h2 class="h6 mb-3">Ringkasan Cepat</h2>
                                    <div class="profile-checklist">
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label"><?= e($profileData['bumdes_name'] !== '' ? $profileData['bumdes_name'] : 'Nama BUMDes belum diisi') ?></span>
                                            <span class="profile-checklist__meta"><?= e($identitySummary) ?></span>
                                        </div>
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Kontak Lembaga</span>
                                            <span class="profile-checklist__meta"><?= e($contactSummary) ?></span>
                                        </div>
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Direktur</span>
                                            <span class="profile-checklist__meta"><?= e(trim($profileData['director_name']) !== '' ? $profileData['director_name'] . ' · ' . $profileData['director_position'] : 'Data direktur belum lengkap') ?></span>
                                        </div>
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Bendahara</span>
                                            <span class="profile-checklist__meta"><?= e(trim($profileData['treasurer_name']) !== '' ? $profileData['treasurer_name'] . ' · ' . $profileData['treasurer_position'] : 'Data bendahara belum lengkap') ?></span>
                                        </div>
                                    </div>
                                </aside>

                                <aside class="profile-side-card">
                                    <h2 class="h6 mb-3">Panduan Isi Cepat</h2>
                                    <div class="d-grid gap-3 small text-secondary">
                                        <div>
                                            <strong class="text-dark d-block mb-1">1. Isi yang wajib dulu</strong>
                                            Nama BUMDes, alamat, periode aktif, direktur, dan bendahara adalah data yang paling sering dipakai sistem.
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block mb-1">2. Upload file belakangan juga bisa</strong>
                                            Kalau belum ada logo atau tanda tangan, profil tetap bisa disimpan lebih dulu.
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block mb-1">3. Pakai nama jabatan yang benar</strong>
                                            Nama jabatan akan muncul di laporan dan kwitansi, jadi sebaiknya sama dengan dokumen resmi BUMDes.
                                        </div>
                                    </div>
                                </aside>

                                <aside class="profile-side-card">
                                    <h2 class="h6 mb-3">Status File</h2>
                                    <div class="profile-checklist">
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Logo</span>
                                            <span class="profile-checklist__meta"><?= e($profileData['logo_path'] !== '' ? 'Sudah tersedia' : 'Belum diunggah') ?></span>
                                        </div>
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Tanda Tangan Direktur</span>
                                            <span class="profile-checklist__meta"><?= e($profileData['signature_path'] !== '' ? 'Sudah tersedia' : 'Belum diunggah') ?></span>
                                        </div>
                                        <div class="profile-checklist__item">
                                            <span class="profile-checklist__label">Tanda Tangan Bendahara</span>
                                            <span class="profile-checklist__meta"><?= e($profileData['treasurer_signature_path'] !== '' ? 'Sudah tersedia' : 'Belum diunggah') ?></span>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
                    <div class="text-secondary small">Setelah disimpan, data profil ini langsung dipakai untuk identitas aplikasi, laporan, jurnal standar, dan kwitansi.</div>
                    <div class="d-flex gap-2">
                        <a href="<?= e(base_url('/dashboard')) ?>" class="btn btn-outline-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Profil</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
