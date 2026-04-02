<?php declare(strict_types=1);
$period = $checklist['period'] ?? [];
$summary = $checklist['summary'] ?? [];
$checks = $checklist['checks'] ?? [];
$isReady = (bool) ($checklist['is_ready_to_close'] ?? false);
$criticalFailures = (int) ($checklist['critical_failures'] ?? 0);
$warnings = (int) ($checklist['warnings'] ?? 0);
$statusBadge = static function (string $status): array {
    return match ($status) {
        'pass' => ['class' => 'text-bg-success', 'label' => 'Siap'],
        'danger' => ['class' => 'text-bg-danger', 'label' => 'Wajib Dicek'],
        default => ['class' => 'text-bg-warning', 'label' => 'Perlu Review'],
    };
};
$latestBackup = $summary['latest_backup'] ?? ['exists' => false, 'name' => '-', 'modified_label' => '-', 'size_bytes' => 0];
$backupSizeLabel = ((int) ($latestBackup['size_bytes'] ?? 0)) > 0 ? number_format(((int) $latestBackup['size_bytes']) / 1024, 1, ',', '.') . ' KB' : '-';
$user = Auth::user();
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="<?= e(base_url('/periods')) ?>" class="btn btn-outline-light btn-sm">&larr; Kembali</a>
            <span class="badge <?= $isReady ? 'text-bg-success' : 'text-bg-warning' ?>">
                <?= $isReady ? 'Siap Ditutup' : 'Butuh Review' ?>
            </span>
        </div>
        <h1 class="h3 mb-1">Checklist Tutup Buku</h1>
        <p class="text-secondary mb-0">
            <?= e((string) ($period['period_name'] ?? '-')) ?>
            &middot;
            <?= e((string) ($period['period_code'] ?? '-')) ?>
            &middot;
            <?= e(active_period_label((string) ($period['start_date'] ?? ''), (string) ($period['end_date'] ?? ''))) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ((string) ($period['status'] ?? '') === 'OPEN' && (int) ($period['is_active'] ?? 0) === 1): ?>
            <form method="post" action="<?= e(base_url('/periods/toggle-status?id=' . (int) ($period['id'] ?? 0))) ?>" class="m-0">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn <?= $isReady ? 'btn-warning' : 'btn-outline-warning' ?>" <?= $criticalFailures > 0 ? 'onclick="return confirm(\'Masih ada temuan kritis. Anda yakin ingin menutup periode ini?\')"' : '' ?>>
                    Tutup Periode Ini
                </button>
            </form>
        <?php endif; ?>
        <a href="<?= e(base_url('/backups')) ?>" class="btn btn-outline-primary">Buka Backup</a>
        <a href="<?= e(base_url('/bank-reconciliations')) ?>" class="btn btn-outline-info">Buka Rekonsiliasi Bank</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small mb-1">Jurnal Periode</div>
            <div class="display-6 fw-semibold"><?= e(number_format((int) ($summary['journal_count'] ?? 0), 0, ',', '.')) ?></div>
            <div class="text-secondary small">Total transaksi jurnal</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small mb-1">Temuan Kritis</div>
            <div class="display-6 fw-semibold text-danger"><?= e(number_format($criticalFailures, 0, ',', '.')) ?></div>
            <div class="text-secondary small">Jurnal tidak seimbang / tanpa detail</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small mb-1">Perlu Review</div>
            <div class="display-6 fw-semibold text-warning"><?= e(number_format($warnings, 0, ',', '.')) ?></div>
            <div class="text-secondary small">Rekonsiliasi, penyusutan, atau backup</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small mb-1">Backup Terbaru</div>
            <div class="fw-semibold"><?= e((string) ($latestBackup['name'] ?? '-')) ?></div>
            <div class="text-secondary small"><?= e((string) ($latestBackup['modified_label'] ?? '-')) ?> &middot; <?= e($backupSizeLabel) ?></div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-2 small text-center text-md-start">
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">1. Review temuan kritis</span></div>
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">2. Selesaikan warning operasional</span></div>
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">3. Backup lalu tutup buku</span></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Pemeriksaan Wajib Sebelum Tutup Buku</h2>
        <div class="vstack gap-3">
            <?php foreach ($checks as $check): ?>
                <?php $badge = $statusBadge((string) ($check['status'] ?? 'warning')); ?>
                <div class="border rounded-4 p-3 bg-body-tertiary">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <div class="fw-semibold"><?= e((string) ($check['label'] ?? '-')) ?></div>
                        <span class="badge <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                    </div>
                    <p class="text-secondary mb-0"><?= e((string) ($check['message'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Arahan Tindak Lanjut</h2>
        <ul class="mb-3 text-secondary">
            <li>Jika temuan kritis masih ada, periksa menu Jurnal Umum lalu pastikan debit dan kredit sudah seimbang.</li>
            <li>Jika rekonsiliasi bank belum bersih, selesaikan lebih dulu agar saldo kas/bank mudah dipertanggungjawabkan.</li>
            <li>Bila ada aset baru atau penyusutan tertunda, selesaikan posting aset agar neraca dan laba rugi tidak salah saji.</li>
            <li>Buat backup database sebelum menutup periode agar rollback darurat tetap memungkinkan.</li>
        </ul>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-light btn-sm">Review Jurnal</a>
            <a href="<?= e(base_url('/bank-reconciliations')) ?>" class="btn btn-outline-light btn-sm">Rekonsiliasi Bank</a>
            <a href="<?= e(base_url('/assets')) ?>" class="btn btn-outline-light btn-sm">Posting Aset</a>
            <?php if (($user['role_code'] ?? '') === 'admin'): ?><a href="<?= e(base_url('/backups')) ?>" class="btn btn-outline-light btn-sm">Backup Database</a><?php endif; ?>
        </div>
    </div>
</div>
