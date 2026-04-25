<?php declare(strict_types=1);
$summary = (array) (($last_check['summary'] ?? []));
$remote = (array) (($last_check['remote'] ?? []));
$state = is_array($state ?? null) ? $state : [];
$latestBackup = is_array($latest_backup ?? null) ? $latest_backup : null;
$changedFiles = array_slice((array) (($last_check['files']['changed'] ?? [])), 0, 25);
$newFiles = array_slice((array) (($last_check['files']['new'] ?? [])), 0, 25);
$obsoleteFiles = array_slice((array) (($last_check['files']['obsolete'] ?? [])), 0, 20);
$totalNeedUpdate = (int) ($summary['changed_count'] ?? 0) + (int) ($summary['new_count'] ?? 0);
$updateAvailable = (bool) ($summary['update_available'] ?? false);
$preflight = (array) (($last_check['preflight'] ?? []));
$pendingMigrations = is_array($pending_migrations ?? null) ? $pending_migrations : [];
$currentManifest = is_array($current_manifest ?? null) ? $current_manifest : [];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Update Aplikasi</h1>
        <p class="text-secondary mb-0">Sinkronkan file aplikasi dari GitHub tanpa menimpa <code>storage/</code>, <code>public/uploads/</code>, atau <code>app/config/generated.php</code>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="<?= e(base_url('/updates/check')) ?>" class="m-0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn-outline-light">Cek Update GitHub</button>
        </form>
        <form method="post" action="<?= e(base_url('/updates/apply')) ?>" class="m-0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="confirm_backup" value="1">
            <button type="submit" class="btn btn-primary" <?= array_key_exists('compatible', $preflight) && !(bool) ($preflight['compatible'] ?? false) ? 'disabled' : '' ?> onclick="return confirm('Sistem akan membuat backup database otomatis, mengaktifkan mode maintenance, lalu mengganti file aplikasi yang berubah dari GitHub. Lanjutkan update?');">Backup DB &amp; Jalankan Update</button>
        </form>
    </div>
</div>

<div class="alert alert-warning mb-4">
    <strong>Wajib backup database:</strong> setiap update akan otomatis membuat file backup SQL terlebih dahulu. Jika update gagal, sistem juga mencoba rollback file yang sempat berubah, menjalankan preflight compatibility check, dan menyiapkan laporan audit yang bisa diunduh.
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">Preflight Update</h2>
            <div class="small text-secondary mb-3">Update hanya boleh jalan jika paket rilis GitHub kompatibel dengan updater lokal dan migration-nya bisa dikenali.</div>
            <div class="row g-3">
                <div class="col-md-6"><div class="rounded-4 border p-3 h-100"><div class="small text-secondary">Capability Lokal</div><div class="fw-bold"><?= e((string) ($preflight['updater_capability'] ?? '1')) ?></div></div></div>
                <div class="col-md-6"><div class="rounded-4 border p-3 h-100"><div class="small text-secondary">Capability Remote</div><div class="fw-bold"><?= e((string) ($preflight['required_capability'] ?? '-')) ?></div></div></div>
                <div class="col-md-6"><div class="rounded-4 border p-3 h-100"><div class="small text-secondary">Manifest Lokal</div><div class="fw-bold"><?= e((string) ($currentManifest['release_version'] ?? 'Belum ada')) ?></div></div></div>
                <div class="col-md-6"><div class="rounded-4 border p-3 h-100"><div class="small text-secondary">Manifest Remote</div><div class="fw-bold"><?= e((string) ($preflight['remote_release_version'] ?? 'Belum dicek')) ?></div></div></div>
            </div>
            <div class="mt-3">
                <span class="badge <?= !array_key_exists('compatible', $preflight) || (bool) ($preflight['compatible'] ?? false) ? 'text-bg-success' : 'text-bg-danger' ?>">
                    <?= !array_key_exists('compatible', $preflight) || (bool) ($preflight['compatible'] ?? false) ? 'Kompatibel / siap dicek' : 'Tidak kompatibel' ?>
                </span>
            </div>
        </div></div>
    </div>
    <div class="col-xl-6">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">Schema & Migration</h2>
            <div class="small text-secondary mb-3">Migration lokal yang masih pending akan ikut dijalankan saat update sukses diterapkan.</div>
            <?php if ($pendingMigrations === []): ?>
                <div class="text-success fw-semibold">Tidak ada migration lokal yang pending.</div>
            <?php else: ?>
                <div class="mb-2 fw-semibold"><?= e((string) count($pendingMigrations)) ?> migration pending</div>
                <ul class="small mb-0 ps-3 text-secondary">
                    <?php foreach (array_slice($pendingMigrations, 0, 8) as $migrationName): ?>
                        <li><code><?= e((string) $migrationName) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Versi Lokal</div><div class="fw-bold fs-4 mb-1"><?= e((string) $current_version) ?></div><div class="small text-secondary">Commit tersimpan: <?= e((string) ($state['current_commit_short'] ?? ($state['current_commit'] ?? '-'))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Versi GitHub</div><div class="fw-bold fs-4 mb-1"><?= e((string) ($remote['version'] ?? 'Belum dicek')) ?></div><div class="small text-secondary"><?= e((string) (($remote['commit_short'] ?? '') !== '' ? 'Commit ' . $remote['commit_short'] : 'Klik Cek Update GitHub')) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Backup Database Terakhir</div><div class="fw-semibold mb-1"><?= e($latestBackup ? audit_datetime((string) ($latestBackup['modified_at'] ?? '')) : '-') ?></div><div class="small text-secondary"><?= e($latestBackup ? ((string) ($latestBackup['name'] ?? '-') . ' · ' . format_bytes((int) ($latestBackup['size'] ?? 0))) : 'Belum ada file backup') ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Status Update</div><div class="fw-bold fs-4 mb-1 <?= $updateAvailable ? 'text-warning' : 'text-success' ?>"><?= $updateAvailable ? e((string) $totalNeedUpdate . ' file') : 'Terbaru' ?></div><div class="small text-secondary"><?= e((string) (($last_check['checked_at'] ?? '') !== '' ? 'Cek terakhir ' . audit_datetime((string) $last_check['checked_at']) : 'Belum ada pemeriksaan')) ?></div></div></div></div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="small text-secondary mb-1">Repository GitHub</div>
            <div class="fw-semibold mb-2" style="word-break:break-all"><?= e((string) $repo_url) ?></div>
            <div class="small text-secondary">Branch update: <strong><?= e((string) $branch) ?></strong></div>
        </div>
        <div class="col-lg-6">
            <div class="small text-secondary mb-1">Aturan Update</div>
            <ul class="small mb-0 text-secondary ps-3">
                <li>Hanya file aplikasi yang berubah yang akan ditimpa.</li>
                <li>File upload, storage, backup, dan config hasil instalasi tidak disentuh.</li>
                <li>File lokal yang sudah tidak ada di GitHub hanya dilaporkan, tidak dihapus otomatis.</li>
            </ul>
        </div>
    </div>
</div></div>

<?php if (($last_check['status'] ?? '') === 'failed'): ?>
    <div class="alert alert-danger mb-4">
        <strong>Update terakhir gagal.</strong> Sistem sudah mencoba rollback file yang sempat berubah. Unduh laporan audit untuk melihat letak error dan file yang terdampak.
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">File yang Akan Diperbarui</h2>
            <?php if ($changedFiles === [] && $newFiles === []): ?>
                <div class="text-secondary">Belum ada hasil cek update atau belum ditemukan file yang berubah.</div>
            <?php else: ?>
                <div class="small text-secondary mb-3">Menampilkan maksimal 25 file berubah dan 25 file baru dari hasil cek terakhir.</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Path</th><th style="width:120px">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($changedFiles as $item): ?>
                            <tr><td><code><?= e((string) ($item['path'] ?? '-')) ?></code></td><td><span class="badge text-bg-warning">Berubah</span></td></tr>
                        <?php endforeach; ?>
                        <?php foreach ($newFiles as $item): ?>
                            <tr><td><code><?= e((string) ($item['path'] ?? '-')) ?></code></td><td><span class="badge text-bg-info">Baru</span></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-xl-6">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5 mb-3">File Lokal yang Tidak Ada di GitHub</h2>
            <p class="small text-secondary">File ini <strong>tidak dihapus otomatis</strong> saat update, supaya aman untuk patch lokal dan file tambahan di server.</p>
            <?php if ($obsoleteFiles === []): ?>
                <div class="text-secondary">Tidak ada file lokal yang terdeteksi sebagai kandidat pembersihan.</div>
            <?php else: ?>
                <ul class="small mb-0 ps-3">
                    <?php foreach ($obsoleteFiles as $item): ?>
                        <li><code><?= e((string) ($item['path'] ?? '-')) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">Laporan Audit Update</h2>
            <div class="small text-secondary">Unduh laporan jika cek update atau update aplikasi gagal, agar terlihat file mana yang berubah dan letak error-nya.</div>
        </div>
    </div>

    <?php if (($report_files ?? []) === []): ?>
        <div class="text-secondary">Belum ada laporan audit update.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr><th>Nama File</th><th style="width:180px">Dibuat</th><th style="width:120px">Ukuran</th><th style="width:140px" class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach (($report_files ?? []) as $report): ?>
                    <tr>
                        <td><code><?= e((string) ($report['name'] ?? '-')) ?></code></td>
                        <td><?= e(audit_datetime((string) ($report['modified_at'] ?? ''))) ?></td>
                        <td><?= e(format_bytes((int) ($report['size'] ?? 0))) ?></td>
                        <td class="text-end"><a href="<?= e(base_url('/updates/report?file=' . urlencode((string) ($report['name'] ?? '')))) ?>" class="btn btn-sm btn-outline-light">Unduh</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div></div>
