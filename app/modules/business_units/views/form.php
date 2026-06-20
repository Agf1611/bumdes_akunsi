<?php declare(strict_types=1); ?>
<div class="business-unit-page module-page">
    <section class="unit-hero unit-hero--form mb-4">
        <div class="unit-hero__copy">
            <div class="module-hero__eyebrow">Setting Unit Usaha</div>
            <h1 class="module-hero__title"><?= e($title) ?></h1>
            <p class="module-hero__text mb-0">Lengkapi identitas operasional dan legal unit usaha. Data ini membantu laporan per unit lebih mudah dibaca.</p>
        </div>
        <div class="unit-hero__actions">
            <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-light">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Kembali</span>
            </a>
        </div>
    </section>

    <div class="card shadow-sm unit-form-card">
        <div class="card-body p-4 p-lg-5">
            <form method="post" action="<?= e($row ? base_url('/business-units/update?id=' . (int) $row['id']) : base_url('/business-units/store')) ?>" novalidate>
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                <div class="unit-form-section">
                    <div class="unit-form-section__title">
                        <span>1</span>
                        <div>
                            <h2>Identitas Unit</h2>
                            <p>Kode dan nama yang muncul di transaksi, dashboard, dan laporan.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="unit_code" maxlength="30" value="<?= e($formData['unit_code']) ?>" placeholder="WIFI / KETAPANG" required>
                            <div class="form-text">Gunakan kode pendek tanpa spasi agar mudah dicari.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama Unit Usaha <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="unit_name" maxlength="120" value="<?= e($formData['unit_name']) ?>" placeholder="Unit Usaha WIFI" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama Usaha Resmi</label>
                            <input type="text" class="form-control" name="legal_name" maxlength="160" value="<?= e($formData['legal_name']) ?>" placeholder="Nama legal jika berbeda dari nama unit">
                        </div>
                    </div>
                </div>

                <div class="unit-form-section">
                    <div class="unit-form-section__title">
                        <span>2</span>
                        <div>
                            <h2>Legal & Kontak</h2>
                            <p>Isi NIB dan kontak masing-masing unit jika tersedia.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">NIB Unit Usaha</label>
                            <input type="text" class="form-control" name="nib" maxlength="50" value="<?= e($formData['nib']) ?>" placeholder="Nomor Induk Berusaha">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Telepon / WA</label>
                            <input type="text" class="form-control" name="phone" maxlength="40" value="<?= e($formData['phone']) ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email Unit</label>
                            <input type="email" class="form-control" name="email" maxlength="120" value="<?= e($formData['email']) ?>" placeholder="unit@email.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Unit</label>
                            <textarea class="form-control" name="address" rows="3" maxlength="500" placeholder="Alamat operasional unit usaha"><?= e($formData['address']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="unit-form-section">
                    <div class="unit-form-section__title">
                        <span>3</span>
                        <div>
                            <h2>Catatan & Status</h2>
                            <p>Tambahkan keterangan singkat dan status aktif unit.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="4" maxlength="500" placeholder="Contoh: Layanan internet desa, voucher, instalasi pelanggan"><?= e($formData['description']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status Aktif</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <div class="form-text">Unit nonaktif tidak ditawarkan sebagai pilihan baru, tetapi riwayat jurnal lama tetap aman.</div>
                        </div>
                    </div>
                </div>

                <div class="unit-form-actions">
                    <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-light">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span>Simpan Unit Usaha</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
