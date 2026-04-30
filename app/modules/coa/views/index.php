<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($accounts ?? []); $accounts = $listing['items']; $listingPath = '/coa'; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<?php $importErrors = Session::pull('import_errors', []); $importSuccess = Session::pull('import_success', ''); $importResult = Session::pull('import_result', []); ?>
<div class="coa-page module-page">
<section class="module-hero mb-4">
    <div class="module-hero__content">
        <div>
            <div class="module-hero__eyebrow">Chart Of Accounts</div>
            <h1 class="module-hero__title">Struktur Akun BUMDes</h1>
            <p class="module-hero__text">Kelola akun inti, impor template, dan pastikan struktur COA rapi sebelum dipakai di jurnal, buku besar, dan laporan.</p>
        </div>
        <div class="module-hero__actions">
            <?php if (Auth::hasRole('admin')): ?>
                <a href="<?= e(base_url('/coa/create')) ?>" class="btn btn-primary">Tambah Akun</a>
            <?php endif; ?>
            <a href="<?= e(base_url('/coa/export?' . http_build_query($filters))) ?>" class="btn btn-outline-info">Export COA</a>
        </div>
    </div>
</section>
<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Total Akun</div>
                <div class="display-6 fw-semibold"><?= e((string) $totalAccounts) ?></div>
                <div class="text-secondary small mt-2">Daftar akun aktif dan nonaktif dalam sistem.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Filter Tipe</div>
                <div class="fs-5 fw-semibold"><?= e($filters['type'] !== '' ? coa_type_label($filters['type']) : 'Semua Tipe') ?></div>
                <div class="text-secondary small mt-2">Gunakan filter untuk memeriksa struktur akun per tipe.</div>
                <div class="text-secondary small mt-1">Paket COA standar siap dimuat: <strong><?= e((string) ($globalCoaCount ?? 0)) ?></strong> akun BUMDes.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card dashboard-card h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="text-secondary small mb-2">Kelola COA</div>
                    <div class="fs-5 fw-semibold">Struktur akun BUMDes</div>
                    <div class="text-secondary small mt-2">Tambah, ubah, nonaktifkan, atau hapus akun sesuai aturan bisnis.</div>
                </div>
                <?php if (Auth::hasRole('admin')): ?>
                    <div class="mt-3">
                        <a href="<?= e(base_url('/coa/create')) ?>" class="btn btn-primary">Tambah Akun</a>
                    </div>
                <?php else: ?>
                    <div class="mt-3 text-secondary small">Anda sedang dalam mode lihat saja untuk modul COA.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Import / Export COA</h2>
                <p class="text-secondary mb-0">Kelola template, impor akun dari Excel, dan ekspor daftar akun langsung dari menu COA.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= e(base_url('/imports/template?type=coa')) ?>" class="btn btn-outline-light">Unduh Template COA</a>
                <a href="<?= e(base_url('/coa/export?' . http_build_query($filters))) ?>" class="btn btn-outline-info">Export COA</a>
                <?php if (Auth::hasRole('admin')): ?>
                    <form method="post" action="<?= e(base_url('/coa/seed-global')) ?>" class="d-inline" onsubmit="return confirm('Tambahkan paket COA standar KepmenDesa 136/2022? Akun yang sudah ada tidak akan ditimpa.');">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn btn-outline-success">Tambahkan COA KepmenDesa 136</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($importSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($importSuccess) ?></div>
        <?php endif; ?>
        <?php if ($importErrors !== []): ?>
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">Import COA dibatalkan karena ditemukan masalah:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($importErrors as $message): ?>
                        <li><?= e((string) $message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (($importResult['type'] ?? '') === 'COA' && (int) ($importResult['imported'] ?? 0) > 0): ?>
            <div class="text-secondary small mb-3">Total akun yang ditambahkan melalui import terakhir: <strong><?= e((string) (int) $importResult['imported']) ?></strong>.</div>
        <?php endif; ?>

        <div class="alert alert-info border-0 mb-3">
            <div class="fw-semibold mb-1">COA Standar KepmenDesa PDTT 136/2022</div>
            <div class="small">Tombol <strong>Tambahkan COA KepmenDesa 136</strong> akan mengisi bagan akun BUM Desa sesuai struktur dokumen: aset, kewajiban, ekuitas, pendapatan usaha, harga pokok, beban usaha, serta pendapatan dan beban lain-lain. Akun yang sudah ada berdasarkan kode akun yang sama tidak akan ditimpa.</div>
        </div>

        <?php if (Auth::hasRole('admin')): ?>
            <form method="post" action="<?= e(base_url('/imports/coa')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="redirect_to" value="/coa">
                <div class="col-12 col-lg-8">
                    <label class="form-label">File Import COA (.xlsx)</label>
                    <input type="file" class="form-control" name="coa_file" accept=".xlsx" required>
                    <div class="form-text text-secondary">Ukuran file maksimal 2 MB. Jika ada satu baris salah, seluruh import COA dibatalkan. Kode akun dibaca apa adanya dan akan dicocokkan ke jurnal berdasarkan kode, bukan nama akun saja.</div>
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2 flex-wrap">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="coa_overwrite" name="coa_overwrite">
                        <label class="form-check-label" for="coa_overwrite">Timpa akun yang sudah ada berdasarkan kode akun yang sama</label>
                        <div class="form-text text-secondary">Gunakan opsi ini bila file hasil export/template COA Anda sudah diedit lalu ingin diunggah ulang tanpa mengubah relasi jurnal lama.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Import COA</button>
                </div>
            </form>
        <?php else: ?>
            <div class="text-secondary small">Import COA hanya tersedia untuk admin. Template dan export tetap bisa diakses sesuai hak peran.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Chart of Accounts</h1>
                <p class="text-secondary mb-0">Kelola daftar akun untuk pembukuan BUMDes sebelum modul jurnal digunakan.</p>
            </div>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="<?= e(base_url('/coa/create')) ?>" class="btn btn-primary">Tambah Akun</a>
            <?php endif; ?>
        </div>

        <form method="get" action="<?= e(base_url('/coa')) ?>" class="row g-3 align-items-end mb-4">
            <div class="col-12 col-lg-5">
                <label for="search" class="form-label">Pencarian Akun</label>
                <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Cari kode atau nama akun">
            </div>
            <div class="col-12 col-lg-3">
                <label for="type" class="form-label">Filter Tipe Akun</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Semua tipe akun</option>
                    <?php foreach ($types as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="<?= e(base_url('/coa')) ?>" class="btn btn-outline-light">Reset</a>
            </div>
        </form>

        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle coa-table mb-0">
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Parent</th>
                    <th>Header/Detail</th>
                    <th>Status</th>
                    <th class="text-end table-action-col">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($accounts === []): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">Belum ada data akun. Silakan tambah akun pertama Anda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['account_code']) ?></td>
                            <td>
                                <div class="fw-medium"><?= e($row['account_name']) ?></div>
                            </td>
                            <td><?= e(coa_type_label($row['account_type'])) ?></td>
                            <td><?= e(coa_category_label($row['account_type'], $row['account_category'])) ?></td>
                            <td>
                                <?php if (!empty($row['parent_code'])): ?>
                                    <div class="small fw-medium"><?= e($row['parent_code']) ?></div>
                                    <div class="text-secondary small"><?= e($row['parent_name']) ?></div>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= (int) $row['is_header'] === 1 ? 'text-bg-info' : 'text-bg-primary' ?>">
                                    <?= (int) $row['is_header'] === 1 ? 'Header' : 'Detail' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= e(coa_status_badge_class((int) $row['is_active'] === 1)) ?>">
                                    <?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-end table-action-col">
                                <?php if (Auth::hasRole('admin')): ?>
                                    <div class="table-action-menu">
                                        <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button>
                                        <div class="table-action-panel">
                                            <a href="<?= e(base_url('/coa/edit?id=' . (int) $row['id'])) ?>">Edit akun</a>
                                            <form method="post" action="<?= e(base_url('/coa/toggle-active?id=' . (int) $row['id'])) ?>" class="m-0">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit"><?= (int) $row['is_active'] === 1 ? 'Nonaktifkan akun' : 'Aktifkan akun' ?></button>
                                            </form>
                                            <form method="post" action="<?= e(base_url('/coa/delete?id=' . (int) $row['id'])) ?>" class="m-0" onsubmit="return confirm('Hapus akun ini? Akun hanya bisa dihapus jika belum dipakai jurnal dan tidak punya akun turunan.');">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit" class="table-action-danger">Hapus akun</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary small">Lihat saja</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
</div>
