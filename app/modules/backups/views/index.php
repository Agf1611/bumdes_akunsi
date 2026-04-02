<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($files ?? []); $files = $listing['items']; $listingPath = '/backups'; ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Backup Database</h1>
        <p class="text-secondary mb-0">Buat salinan database aplikasi ke file SQL agar aman sebelum update, migrasi server, atau penutupan periode.</p>
    </div>
    <form method="post" action="<?= e(base_url('/backups/create')) ?>" class="m-0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="btn btn-primary">Buat Backup Sekarang</button>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Jumlah File</div><div class="display-6 fw-bold mb-0"><?= e((string) ($summary['count'] ?? 0)) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Total Ukuran</div><div class="display-6 fw-bold mb-0"><?= e(format_bytes((int) ($summary['total_size'] ?? 0))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Backup Terakhir</div><div class="fw-semibold mb-0"><?= e((string) (($summary['latest'] ?? '') !== '' ? audit_datetime((string) $summary['latest']) : '-')) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Status Sistem</div><div class="fw-semibold mb-1 <?= !empty($summary['db_connected']) && !empty($summary['directory_writable']) ? 'text-success' : 'text-warning' ?>"><?= !empty($summary['db_connected']) && !empty($summary['directory_writable']) ? 'Siap Backup' : 'Perlu Cek' ?></div><div class="small text-secondary">DB <?= !empty($summary['db_connected']) ? 'terhubung' : 'belum terhubung' ?> &middot; folder <?= !empty($summary['directory_writable']) ? 'writable' : 'belum writable' ?></div></div></div></div>
</div>

<div class="alert alert-warning"><strong>Perhatian:</strong> Restore akan mengganti isi database saat ini dengan isi file backup yang dipilih. Lakukan hanya saat Anda yakin dan sebaiknya buat backup baru terlebih dahulu.</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h5 mb-3">Restore dari File Backup Server</h2>
            <p class="text-secondary small mb-3">Gunakan file SQL yang sudah ada di folder backup server. Cocok untuk pemulihan cepat setelah salah input atau sebelum rollback patch.</p>
            <form method="post" action="<?= e(base_url('/backups/restore')) ?>" onsubmit="return confirm('Restore database akan mengganti data saat ini. Lanjutkan?');">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="restore_mode" value="server">
                <div class="mb-3">
                    <label class="form-label">Pilih file backup server</label>
                    <select name="file" class="form-select" required>
                        <option value="">-- pilih file backup --</option>
                        <?php foreach ($files as $file): ?>
                            <option value="<?= e((string) $file['name']) ?>"><?= e((string) $file['name']) ?> (<?= e(format_bytes((int) $file['size'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-warning" <?= $files === [] ? 'disabled' : '' ?>>Restore dari Server</button>
            </form>
        </div>
        <div class="col-lg-6">
            <h2 class="h5 mb-3">Restore dari Upload File SQL</h2>
            <p class="text-secondary small mb-3">Upload file backup .sql yang pernah Anda unduh sebelumnya. Maksimal 30 MB per file restore.</p>
            <form method="post" action="<?= e(base_url('/backups/restore')) ?>" enctype="multipart/form-data" onsubmit="return confirm('Restore database akan mengganti data saat ini. Lanjutkan?');">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="restore_mode" value="upload">
                <div class="mb-3">
                    <label class="form-label">Upload file .sql</label>
                    <input type="file" name="restore_file" class="form-control" accept=".sql" required>
                </div>
                <button type="submit" class="btn btn-warning">Restore dari File</button>
            </form>
        </div>
    </div>
</div></div>

<div class="alert alert-info">Simpan minimal satu file backup sebelum melakukan patch, update source, atau perubahan data besar. Disarankan unduh file backup dan simpan juga di lokasi terpisah.</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="small text-secondary mb-1">Folder Backup Server</div>
            <div class="fw-semibold"><?= e((string) ($summary['directory'] ?? '-')) ?></div>
        </div>
        <div class="col-lg-3">
            <div class="small text-secondary mb-1">Koneksi Database</div>
            <div class="fw-semibold <?= !empty($summary['db_connected']) ? 'text-success' : 'text-danger' ?>"><?= !empty($summary['db_connected']) ? 'Tersambung' : 'Gagal / Belum siap' ?></div>
        </div>
        <div class="col-lg-3">
            <div class="small text-secondary mb-1">Folder Writable</div>
            <div class="fw-semibold <?= !empty($summary['directory_writable']) ? 'text-success' : 'text-danger' ?>"><?= !empty($summary['directory_writable']) ? 'Ya' : 'Tidak' ?></div>
        </div>
    </div>
</div></div>

<div class="card shadow-sm"><div class="card-body p-0">
    <?php if ($files === []): ?>
        <div class="p-5 text-center text-secondary">Belum ada file backup. Klik <strong class="text-light">Buat Backup Sekarang</strong> untuk membuat file SQL pertama.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Nama File</th>
                    <th style="width:16%">Dibuat</th>
                    <th style="width:12%">Ukuran</th>
                    <th style="width:18%">Checksum SHA1</th>
                    <th style="width:16%" class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string) $file['name']) ?></div>
                            <div class="small text-secondary">Salinan database siap diunduh atau dipindahkan ke media lain.</div>
                        </td>
                        <td><?= e(audit_datetime((string) $file['modified_at'])) ?></td>
                        <td><?= e(format_bytes((int) $file['size'])) ?></td>
                        <td><code><?= e((string) $file['sha1']) ?></code></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <a href="<?= e(base_url('/backups/download?file=' . urlencode((string) $file['name']))) ?>" class="btn btn-sm btn-outline-light">Unduh</a>
                                <form method="post" action="<?= e(base_url('/backups/delete')) ?>" class="m-0" onsubmit="return confirm('Hapus file backup ini dari server?');">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="file" value="<?= e((string) $file['name']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
</div></div>
