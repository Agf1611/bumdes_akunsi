<?php declare(strict_types=1);
$summary = (array) (($last_check['summary'] ?? []));
$remote = (array) (($last_check['remote'] ?? []));
$state = is_array($state ?? null) ? $state : [];
$latestBackup = is_array($latest_backup ?? null) ? $latest_backup : null;
$changedFiles = array_slice((array) (($last_check['files']['changed'] ?? [])), 0, 15);
$newFiles = array_slice((array) (($last_check['files']['new'] ?? [])), 0, 15);
$obsoleteFiles = array_slice((array) (($last_check['files']['obsolete'] ?? [])), 0, 10);
$totalNeedUpdate = (int) ($summary['changed_count'] ?? 0) + (int) ($summary['new_count'] ?? 0);
$updateAvailable = (bool) ($summary['update_available'] ?? false);
$preflight = (array) (($last_check['preflight'] ?? []));
$pendingMigrations = is_array($pending_migrations ?? null) ? $pending_migrations : [];
$currentManifest = is_array($current_manifest ?? null) ? $current_manifest : [];
$updateBlocked = array_key_exists('compatible', $preflight) && !(bool) ($preflight['compatible'] ?? false);
$checkedAt = (string) ($last_check['checked_at'] ?? '');
$statusLabel = $updateAvailable ? 'Update tersedia' : 'Sudah terbaru';
$statusBadgeClass = $updateAvailable ? 'text-bg-warning' : 'text-bg-success';
?>

<section class="module-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Pengaturan</div>
                <h1 class="module-hero__title">Update Aplikasi</h1>
                <p class="module-hero__text">Halaman ini saya sederhanakan agar fokus ke update saja. Cukup cek update, lalu jalankan update jika memang tersedia versi baru.</p>
            </div>
            <div class="module-hero__actions" data-ui-no-iconify="1">
                <form method="post" action="<?= e(base_url('/updates/check')) ?>" class="m-0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-light">Cek Update</button>
                </form>
                <form method="post" action="<?= e(base_url('/updates/apply')) ?>" class="m-0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="confirm_backup" value="1">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= $updateBlocked ? 'disabled' : '' ?>
                        onclick="return confirm('Sistem akan membuat backup database otomatis lalu menjalankan update aplikasi. Lanjutkan?');"
                    >Jalankan Update</button>
                </form>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="small text-secondary mb-1">Status Saat Ini</div>
                            <h2 class="h4 mb-1"><?= e($statusLabel) ?></h2>
                            <div class="text-secondary small">
                                <?= $checkedAt !== '' ? 'Pengecekan terakhir: ' . e(audit_datetime($checkedAt)) : 'Belum pernah cek update dari GitHub.' ?>
                            </div>
                        </div>
                        <div>
                            <span class="badge <?= e($statusBadgeClass) ?>"><?= e($statusLabel) ?></span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="rounded-4 border p-3 h-100">
                                <div class="small text-secondary">Versi Lokal</div>
                                <div class="fw-bold fs-5"><?= e((string) $current_version) ?></div>
                                <div class="small text-secondary mt-1"><?= e((string) ($state['current_commit_short'] ?? ($state['current_commit'] ?? '-'))) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-4 border p-3 h-100">
                                <div class="small text-secondary">Versi GitHub</div>
                                <div class="fw-bold fs-5"><?= e((string) ($remote['version'] ?? 'Belum dicek')) ?></div>
                                <div class="small text-secondary mt-1"><?= e((string) (($remote['commit_short'] ?? '') !== '' ? 'Commit ' . $remote['commit_short'] : 'Klik cek update dulu')) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-4 border p-3 h-100">
                                <div class="small text-secondary">Backup Database Terakhir</div>
                                <div class="fw-bold fs-6"><?= e($latestBackup ? audit_datetime((string) ($latestBackup['modified_at'] ?? '')) : '-') ?></div>
                                <div class="small text-secondary mt-1"><?= e($latestBackup ? ((string) ($latestBackup['name'] ?? '-') . ' · ' . format_bytes((int) ($latestBackup['size'] ?? 0))) : 'Belum ada file backup') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3">
                        Sistem akan membuat backup database otomatis sebelum update dijalankan. Jadi Anda tidak perlu backup manual setiap kali mau update.
                    </div>

                    <?php if ($updateBlocked): ?>
                        <div class="alert alert-danger mb-3">
                            Update sedang diblokir karena paket update belum kompatibel dengan updater lokal. Silakan cek bagian info teknis di bawah.
                        </div>
                    <?php endif; ?>

                    <?php if ($pendingMigrations !== []): ?>
                        <div class="alert alert-warning mb-3">
                            Ada <strong><?= e((string) count($pendingMigrations)) ?> migration</strong> yang akan ikut diproses saat update berhasil dijalankan.
                        </div>
                    <?php endif; ?>

                    <?php if (($last_check['status'] ?? '') === 'failed'): ?>
                        <div class="alert alert-danger mb-0">
                            Update terakhir gagal. Jika perlu, unduh laporan audit di bagian bawah untuk melihat penyebabnya.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Langkah Sederhana</h2>
                    <div class="d-grid gap-3">
                        <div class="rounded-4 border p-3">
                            <div class="fw-semibold mb-1">1. Klik Cek Update</div>
                            <div class="small text-secondary">Lihat dulu apakah ada versi baru dari GitHub.</div>
                        </div>
                        <div class="rounded-4 border p-3">
                            <div class="fw-semibold mb-1">2. Klik Jalankan Update</div>
                            <div class="small text-secondary">Kalau update tersedia, sistem akan backup database lalu memproses update.</div>
                        </div>
                        <div class="rounded-4 border p-3">
                            <div class="fw-semibold mb-1">3. Selesai</div>
                            <div class="small text-secondary">Jika berhasil, aplikasi akan memakai versi terbaru. Jika gagal, cek laporan audit.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <details>
                <summary class="fw-semibold" style="cursor:pointer;">Lihat Info Teknis</summary>
                <div class="pt-4">
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="small text-secondary mb-1">Repository GitHub</div>
                            <div class="fw-semibold mb-2" style="word-break:break-all"><?= e((string) $repo_url) ?></div>
                            <div class="small text-secondary">Branch update: <strong><?= e((string) $branch) ?></strong></div>
                            <div class="small text-secondary mt-2">Manifest lokal: <strong><?= e((string) ($currentManifest['release_version'] ?? 'Belum ada')) ?></strong></div>
                            <div class="small text-secondary">Manifest remote: <strong><?= e((string) ($preflight['remote_release_version'] ?? 'Belum dicek')) ?></strong></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="small text-secondary mb-2">Ringkasan teknis</div>
                            <ul class="small mb-0 ps-3 text-secondary">
                                <li>Capability lokal: <code><?= e((string) ($preflight['updater_capability'] ?? '1')) ?></code></li>
                                <li>Capability remote: <code><?= e((string) ($preflight['required_capability'] ?? '-')) ?></code></li>
                                <li>File berubah / baru: <strong><?= e((string) $totalNeedUpdate) ?></strong></li>
                                <li>File lokal ekstra: <strong><?= e((string) count($obsoleteFiles)) ?></strong></li>
                            </ul>
                        </div>
                    </div>

                    <?php if ($changedFiles !== [] || $newFiles !== []): ?>
                        <div class="mb-4">
                            <h3 class="h6 mb-3">File yang Akan Diperbarui</h3>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                    <tr><th>Path</th><th style="width:120px">Status</th></tr>
                                    </thead>
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
                        </div>
                    <?php endif; ?>

                    <?php if (($report_files ?? []) !== []): ?>
                        <div>
                            <h3 class="h6 mb-3">Laporan Audit Update</h3>
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
                                            <td class="text-end">
                                                <a href="<?= e(base_url('/updates/report?file=' . urlencode((string) ($report['name'] ?? '')))) ?>" class="btn btn-sm btn-outline-light">Unduh</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="small text-secondary">Belum ada laporan audit update.</div>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </div>
</section>
