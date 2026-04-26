<?php declare(strict_types=1); ?>
<?php
$ready = (bool) ($checklist['is_ready_to_close'] ?? false);
$reports = [
    ['label' => 'Dashboard Ringkas', 'path' => '/dashboard?' . $reportQuery, 'type' => 'view'],
    ['label' => 'Laba Rugi', 'path' => '/profit-loss?' . $reportQuery, 'type' => 'view'],
    ['label' => 'Neraca', 'path' => '/balance-sheet?' . $reportQuery, 'type' => 'view'],
    ['label' => 'Arus Kas', 'path' => '/cash-flow?' . $reportQuery, 'type' => 'view'],
    ['label' => 'Neraca Saldo', 'path' => '/trial-balance?' . $reportQuery, 'type' => 'view'],
    ['label' => 'Daftar Jurnal', 'path' => '/journals/print-list?' . $reportQuery, 'type' => 'print'],
];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-semibold small mb-2">Monthly closing pack</div>
        <h1 class="h3 mb-1">Paket Tutup Bulan</h1>
        <p class="text-secondary mb-0">Satu tempat untuk mengecek blocker, backup, dan membuka laporan utama periode aktif.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(base_url('/periods/checklist?period_id=' . (int) ($period['id'] ?? 0))) ?>" class="btn btn-outline-primary">Checklist Detail</a>
        <a href="<?= e(base_url('/backups')) ?>" class="btn btn-primary">Backup Sekarang</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <span class="badge <?= $ready ? 'text-bg-success' : 'text-bg-warning' ?> mb-3"><?= $ready ? 'Siap Tutup' : 'Belum Final' ?></span>
                <h2 class="h4 mb-2"><?= e((string) ($period['period_name'] ?? '-')) ?></h2>
                <p class="text-secondary mb-0">
                    <?= e(format_id_date((string) ($period['start_date'] ?? ''))) ?> - <?= e(format_id_date((string) ($period['end_date'] ?? ''))) ?>
                    &middot; dicetak oleh <?= e((string) (Auth::user()['full_name'] ?? '-')) ?>
                </p>
            </div>
            <div class="row g-3 flex-grow-1">
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-secondary">Kritis</div>
                        <div class="h4 mb-0"><?= e((string) (int) ($checklist['critical_failures'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-secondary">Warning</div>
                        <div class="h4 mb-0"><?= e((string) (int) ($checklist['warnings'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-secondary">Backup</div>
                        <div class="fw-semibold"><?= e((string) (($latestBackup['modified_at'] ?? '') ?: 'Belum ada')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Checklist Closing</h2>
                <div class="vstack gap-3">
                    <?php foreach ((array) ($checklist['checks'] ?? []) as $check): ?>
                        <?php
                        $status = (string) ($check['status'] ?? 'warning');
                        $badge = $status === 'pass' ? 'text-bg-success' : ($status === 'danger' ? 'text-bg-danger' : 'text-bg-warning');
                        ?>
                        <div class="d-flex justify-content-between align-items-start gap-3 border rounded-4 p-3">
                            <div>
                                <div class="fw-semibold"><?= e((string) ($check['label'] ?? '-')) ?></div>
                                <div class="text-secondary small"><?= e((string) ($check['message'] ?? '')) ?></div>
                            </div>
                            <span class="badge <?= e($badge) ?>"><?= e(strtoupper($status)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Laporan Dalam Paket</h2>
                <div class="vstack gap-2">
                    <?php foreach ($reports as $report): ?>
                        <a href="<?= e(base_url((string) $report['path'])) ?>" class="d-flex justify-content-between align-items-center text-decoration-none border rounded-4 p-3" <?= $report['type'] === 'print' ? 'target="_blank" rel="noopener"' : '' ?>>
                            <span class="fw-semibold text-dark"><?= e((string) $report['label']) ?></span>
                            <span class="text-primary small"><?= $report['type'] === 'print' ? 'Cetak' : 'Buka' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!$ready): ?>
                    <div class="alert alert-warning mt-4 mb-0">Paket masih bisa dipreview, tetapi statusnya <strong>Belum Final</strong> karena masih ada blocker/warning closing.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
