<?php declare(strict_types=1); ?>
<?php
$checks = is_array($checks ?? null) ? $checks : [];
$backupSummary = is_array($backupSummary ?? null) ? $backupSummary : [];
$backupStale = is_array($backupStale ?? null) ? $backupStale : [];
$recoveryState = is_array($recoveryState ?? null) ? $recoveryState : [];
$recoveryReadiness = is_array($recoveryReadiness ?? null) ? $recoveryReadiness : [];
$dataAudit = is_array($dataAudit ?? null) ? $dataAudit : [];
$dataAuditChecks = is_array($dataAudit['checks'] ?? null) ? $dataAudit['checks'] : [];
$dataCounts = is_array($dataAudit['counts'] ?? null) ? $dataAudit['counts'] : [];

$statusClass = static fn (string $status): string => match ($status) {
    'ok' => 'text-bg-success',
    'warning' => 'text-bg-warning',
    default => 'text-bg-danger',
};
$overallCritical = count(array_filter($checks, static fn (array $check): bool => (string) ($check['status'] ?? '') === 'critical'));
$overallWarning = count(array_filter($checks, static fn (array $check): bool => (string) ($check['status'] ?? '') === 'warning'));
$recoveryGuides = [
    'Buat backup baru segera setelah recovery selesai agar baseline terbaru tersimpan.',
    'Cek login admin, role, periode aktif, dan profil BUMDes.',
    'Buka dashboard, jurnal, dan laporan utama untuk memastikan data inti terbaca.',
    'Simpan satu salinan backup di lokasi terpisah karena backup lokal tunggal belum cukup aman.',
];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-semibold small mb-2">Admin system health</div>
        <h1 class="h3 mb-1">Health Check Aplikasi</h1>
        <p class="text-secondary mb-0">Pantau backup, recovery readiness, migration, dan kondisi data inti sebelum aplikasi dipakai serius atau dibagikan.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(base_url('/backups')) ?>" class="btn btn-primary">Kelola Backup</a>
        <a href="<?= e(base_url('/updates')) ?>" class="btn btn-outline-primary">Update Aplikasi</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Status keseluruhan</div>
                <div class="h4 mb-1"><?= $overallCritical > 0 ? 'Perlu tindakan' : ($overallWarning > 0 ? 'Ada peringatan' : 'Sehat') ?></div>
                <span class="badge <?= e($overallCritical > 0 ? 'text-bg-danger' : ($overallWarning > 0 ? 'text-bg-warning' : 'text-bg-success')) ?>">
                    <?= e((string) $overallCritical) ?> kritis &middot; <?= e((string) $overallWarning) ?> warning
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Backup tersedia</div>
                <div class="h4 mb-1"><?= e((string) ($backupSummary['count'] ?? 0)) ?> file</div>
                <div class="text-secondary small"><?= e((string) ($backupSummary['latest_age_label'] ?? 'Belum ada')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Versi manifest</div>
                <div class="h5 mb-1"><?= e((string) (($manifest['release_version'] ?? '') ?: '-')) ?></div>
                <div class="text-secondary small"><?= e((string) (($manifest['release_date'] ?? '') ?: '-')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Recovery terakhir</div>
                <div class="h6 mb-1"><?= e((string) (($recoveryState['source_file_name'] ?? '') ?: 'Belum ada metadata')) ?></div>
                <div class="text-secondary small"><?= e((string) (($recoveryState['restored_at'] ?? '') ?: 'Belum tercatat')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 border-<?= ($backupStale['level'] ?? 'ok') === 'critical' ? 'danger' : (($backupStale['level'] ?? 'ok') === 'warning' ? 'warning' : 'success') ?>">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <h2 class="h5 mb-2">Recovery Readiness</h2>
                <p class="text-secondary mb-0"><?= e((string) ($backupStale['note'] ?? 'Status backup belum terbaca.')) ?></p>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                <div>
                    <div class="small text-secondary">Backup terakhir</div>
                    <div class="fw-semibold"><?= e((string) (($backupSummary['latest_file']['name'] ?? '') ?: 'Belum ada')) ?></div>
                </div>
                <div>
                    <div class="small text-secondary">Migration pending</div>
                    <div class="fw-semibold"><?= e((string) ($recoveryReadiness['pending_migrations_count'] ?? 0)) ?></div>
                </div>
                <div>
                    <div class="small text-secondary">Folder backup</div>
                    <div class="fw-semibold"><?= !empty($recoveryReadiness['directories']['backup']['writable']) ? 'Writable' : 'Perlu cek' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Checklist Teknis</h2>
        <div class="row g-3">
            <?php foreach ($checks as $check): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                            <div class="fw-semibold"><?= e((string) ($check['label'] ?? '-')) ?></div>
                            <span class="badge <?= e($statusClass((string) ($check['status'] ?? 'critical'))) ?>"><?= e(strtoupper((string) ($check['status'] ?? 'critical'))) ?></span>
                        </div>
                        <div class="text-secondary small"><?= e((string) ($check['message'] ?? '')) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Audit Data Pasca-Restore</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">User</div><div class="h5 mb-0"><?= e((string) ($dataCounts['users'] ?? 0)) ?></div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">Role</div><div class="h5 mb-0"><?= e((string) ($dataCounts['roles'] ?? 0)) ?></div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">Periode</div><div class="h5 mb-0"><?= e((string) ($dataCounts['periods'] ?? 0)) ?></div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">Jurnal</div><div class="h5 mb-0"><?= e((string) ($dataCounts['journals'] ?? 0)) ?></div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">Unit Usaha</div><div class="h5 mb-0"><?= e((string) ($dataCounts['business_units'] ?? 0)) ?></div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="small text-secondary">COA / Aset</div><div class="h5 mb-0"><?= e((string) ($dataCounts['coa_accounts'] ?? 0)) ?> / <?= e((string) ($dataCounts['assets'] ?? 0)) ?></div></div></div>
                </div>
                <div class="vstack gap-3">
                    <?php foreach ($dataAuditChecks as $check): ?>
                        <div class="d-flex justify-content-between align-items-start gap-3 border rounded-4 p-3">
                            <div>
                                <div class="fw-semibold"><?= e((string) ($check['label'] ?? '-')) ?></div>
                                <div class="text-secondary small"><?= e((string) ($check['message'] ?? '')) ?></div>
                            </div>
                            <span class="badge <?= e($statusClass((string) ($check['status'] ?? 'critical'))) ?>"><?= e(strtoupper((string) ($check['status'] ?? 'critical'))) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Panduan Pasca-Restore</h2>
                <div class="list-group list-group-flush">
                    <?php foreach ($recoveryGuides as $guide): ?>
                        <div class="list-group-item px-0"><?= e($guide) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-warning mt-4 mb-0">Backup lokal tunggal belum cukup aman. Buat backup baru hari ini dan simpan juga salinannya di lokasi terpisah.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-2">Migration Lokal</h2>
                <p class="text-secondary small mb-3">Migration pending harus nol sebelum update atau distribusi.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Migration</th>
                            <th class="text-end">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($localMigrations ?? []) as $migration): ?>
                            <?php $isPending = in_array($migration, $pendingMigrations ?? [], true); ?>
                            <tr>
                                <td class="fw-semibold"><?= e((string) $migration) ?></td>
                                <td class="text-end"><span class="badge <?= $isPending ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $isPending ? 'PENDING' : 'APPLIED' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (($localMigrations ?? []) === []): ?>
                            <tr><td colspan="2" class="text-center text-secondary py-4">Belum ada migration lokal.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-2">Manifest & Recovery Metadata</h2>
                <p class="text-secondary small mb-3">Manifest harus sinkron dan metadata recovery harus tercatat jelas.</p>
                <?php if (($missingFromManifest ?? []) === []): ?>
                    <div class="alert alert-success">Semua migration lokal sudah tercantum di release manifest.</div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-2">Migration belum masuk manifest:</div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($missingFromManifest as $migration): ?>
                                <li><?= e((string) $migration) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="border rounded-4 p-3">
                    <div class="small text-secondary mb-1">Metadata recovery terakhir</div>
                    <div class="fw-semibold mb-1"><?= e((string) (($recoveryState['source_file_name'] ?? '') ?: 'Belum ada file metadata recovery')) ?></div>
                    <div class="small text-secondary">Report: <?= e((string) (($recoveryState['report_file'] ?? '') ?: '-')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
