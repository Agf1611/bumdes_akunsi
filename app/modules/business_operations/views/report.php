<?php declare(strict_types=1); ?>
<?php
$page = is_array($page ?? null) ? $page : [];
$units = is_array($units ?? null) ? $units : [];
$filters = is_array($filters ?? null) ? $filters : [];
$report = is_array($report ?? null) ? $report : null;
$isReady = (bool) ($isReady ?? false);
$fmtMoney = static fn (mixed $value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$budgetTotals = ['INCOME' => 0.0, 'EXPENSE' => 0.0, 'ASSET' => 0.0, 'CAPITAL' => 0.0];
foreach (($report['budgets'] ?? []) as $row) {
    $budgetTotals[(string) $row['budget_type']] = (float) $row['total'];
}
$plannedTotal = (float) ($report['plan']['total'] ?? 0);
$realization = (float) ($report['realization'] ?? 0);
$remaining = ($budgetTotals['EXPENSE'] + $budgetTotals['ASSET']) - $realization;
$monthNames = [0 => 'Semua Bulan', 1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<div class="business-operation-page module-page">
    <section class="operation-hero mb-3">
        <div class="operation-hero__icon"><i class="bi <?= e((string) ($page['icon'] ?? 'bi-bar-chart-line')) ?>" aria-hidden="true"></i></div>
        <div class="operation-hero__copy">
            <div class="module-hero__eyebrow">Kelola Usaha</div>
            <h1 class="module-hero__title"><?= e((string) ($title ?? 'Laporan Rencana Anggaran')) ?></h1>
            <p class="module-hero__text mb-0"><?= e((string) ($page['description'] ?? 'Bandingkan rencana dan realisasi.')) ?></p>
        </div>
        <div class="operation-hero__actions">
            <a href="<?= e(base_url('/budget-plans')) ?>" class="btn btn-light"><i class="bi bi-clipboard2-check" aria-hidden="true"></i><span>RAB</span></a>
        </div>
    </section>

    <?php if (!$isReady): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">Tabel Kelola Usaha belum tersedia. Jalankan patch database lebih dulu.</div>
    <?php endif; ?>

    <section class="operation-card mb-3">
        <form method="get" action="<?= e(base_url('/budget-plan-reports')) ?>" class="operation-filter">
            <select name="unit_id" class="form-select">
                <option value="0">Semua Unit</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= (int) $unit['id'] ?>" <?= (int) ($filters['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>><?= e(business_unit_label($unit, false)) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="year" class="form-control operation-year-input" value="<?= e((string) ($filters['year'] ?? date('Y'))) ?>" min="2000" max="2100">
            <select name="month" class="form-select">
                <?php foreach ($monthNames as $num => $label): ?>
                    <option value="<?= (int) $num ?>" <?= (int) ($filters['month'] ?? 0) === (int) $num ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i><span>Tampilkan</span></button>
        </form>
    </section>

    <?php if ($report !== null): ?>
        <div class="operation-summary-grid mb-3">
            <article class="operation-summary-card"><span>Anggaran Pendapatan</span><strong><?= e($fmtMoney($budgetTotals['INCOME'])) ?></strong></article>
            <article class="operation-summary-card"><span>Anggaran Belanja</span><strong><?= e($fmtMoney($budgetTotals['EXPENSE'])) ?></strong></article>
            <article class="operation-summary-card"><span>Anggaran Aset</span><strong><?= e($fmtMoney($budgetTotals['ASSET'])) ?></strong></article>
            <article class="operation-summary-card"><span>Realisasi Jurnal</span><strong><?= e($fmtMoney($realization)) ?></strong><em>Sisa: <?= e($fmtMoney($remaining)) ?></em></article>
        </div>

        <section class="operation-card">
            <div class="operation-card__head">
                <div>
                    <span class="operation-card__eyebrow"><?= e((string) ($report['plan']['count_rows'] ?? 0)) ?> RAB</span>
                    <h2>Rencana Anggaran</h2>
                </div>
                <strong><?= e($fmtMoney($plannedTotal)) ?></strong>
            </div>
            <div class="table-responsive operation-table-wrap">
                <table class="table align-middle operation-table mb-0">
                    <thead><tr><th>No RAB</th><th>Judul</th><th>Tanggal</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php foreach (($report['plans'] ?? []) as $plan): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $plan['plan_no']) ?></td>
                            <td><?= e((string) $plan['plan_title']) ?><div class="text-secondary small"><?= e((string) ($plan['unit_code'] ?? 'Semua Unit')) ?></div></td>
                            <td><?= e(format_id_date((string) $plan['plan_date'])) ?></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= e((string) $plan['status']) ?></span></td>
                            <td class="text-end fw-bold"><?= e($fmtMoney($plan['total_amount'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (($report['plans'] ?? []) === []): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada RAB pada filter ini.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
