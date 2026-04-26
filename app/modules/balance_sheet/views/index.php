<?php declare(strict_types=1); ?>
<?php
$currentAsOf = trim((string) ($filters['date_to'] ?? ''));
$currentColumnLabel = $currentAsOf !== '' ? 'Per ' . format_id_date($currentAsOf) : 'Saldo Akhir';
$compositionTotal = max(1.0, (float) ($report['total_assets'] ?? 0));
$assetShare = ((float) ($report['total_assets'] ?? 0) / $compositionTotal) * 100;
$liabilityShare = ((float) ($report['total_liabilities'] ?? 0) / $compositionTotal) * 100;
$equityShare = ((float) ($report['total_equity'] ?? 0) / $compositionTotal) * 100;
?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Posisi Keuangan</div>
                <h1 class="module-hero__title">Laporan Neraca</h1>
                <p class="module-hero__text">Posisi keuangan per tanggal laporan yang kembali sederhana, tetap dibantu visual komposisi agar cepat dibaca.</p>
            </div>
            <?php if ($filters['date_to'] !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/balance-sheet/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/balance-sheet/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/balance-sheet/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm mb-4"><div class="card-body p-4">
        <form method="get" action="<?= e(base_url('/balance-sheet')) ?>" class="row g-3 align-items-end">
            <div class="col-lg-3"><label for="period_id" class="form-label">Periode Referensi</label><select name="period_id" id="period_id" class="form-select"><option value="">Opsional / bantu isi tanggal</option><?php foreach ($periods as $period): ?><option value="<?= e((string) $period['id']) ?>" <?= (string) $filters['period_id'] === (string) $period['id'] ? 'selected' : '' ?>><?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label for="fiscal_year" class="form-label">Tahun</label><select name="fiscal_year" id="fiscal_year" class="form-select"><option value="">Semua tahun</option><?php foreach (($reportYears ?? []) as $year): ?><option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-3"><label for="unit_id" class="form-label">Unit Usaha</label><select name="unit_id" id="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label for="date_from" class="form-label">Tanggal Awal (opsional)</label><input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>"></div>
            <div class="col-lg-2"><label for="date_to" class="form-label">Tanggal Neraca</label><input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>"></div>
            <div class="col-lg-2"><div class="form-check pt-4"><input class="form-check-input" type="checkbox" name="show_visual" id="bs_show_visual" value="1" <?= !empty($filters['show_visual']) ? 'checked' : '' ?>><label class="form-check-label" for="bs_show_visual">Visual</label></div></div>
            <div class="col-lg-2 d-grid"><button type="submit" class="btn btn-primary">Tampil</button></div>
        </form>
    </div></div>

    <?php if ($filters['date_to'] !== ''): ?>
        <section class="report-kpi-grid">
            <article class="report-kpi-card"><div class="report-kpi-card__label">Neraca Per Tanggal</div><div class="report-kpi-card__value report-kpi-card__value--sm"><?= e((string) $filters['date_to']) ?></div><div class="report-kpi-card__meta"><?= e($selectedPeriod['period_name'] ?? 'Tanggal bebas') ?></div></article>
            <article class="report-kpi-card"><div class="report-kpi-card__label">Unit Usaha</div><div class="report-kpi-card__value report-kpi-card__value--sm"><?= e($selectedUnitLabel) ?></div><div class="report-kpi-card__meta">Ruang lingkup laporan</div></article>
            <article class="report-kpi-card"><div class="report-kpi-card__label">Status Neraca</div><div class="report-kpi-card__value report-kpi-card__value--sm <?= $report['is_balanced'] ? 'text-success' : 'text-danger' ?>"><?= $report['is_balanced'] ? 'Seimbang' : 'Belum Seimbang' ?></div><div class="report-kpi-card__meta">Selisih <?= e(ledger_currency(abs((float) $report['difference']))) ?></div></article>
            <article class="report-kpi-card"><div class="report-kpi-card__label">Total Aset</div><div class="report-kpi-card__value report-kpi-card__value--sm"><?= e(ledger_currency((float) $report['total_assets'])) ?></div><div class="report-kpi-card__meta">Total posisi keuangan</div></article>
        </section>

        <?php if (!empty($filters['show_visual'])): ?>
            <section class="card shadow-sm report-chart-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                        <div>
                            <div class="module-hero__eyebrow mb-2">Visual Ringkas</div>
                            <h2 class="h4 mb-1">Komposisi Aset, Kewajiban, dan Ekuitas</h2>
                            <p class="text-secondary mb-0">Visual komposisi tetap dipertahankan agar struktur neraca mudah dipindai.</p>
                        </div>
                    </div>
                    <div class="report-composition">
                        <div class="report-composition__bar">
                            <span class="report-composition__slice report-composition__slice--asset" style="width: <?= e((string) round($assetShare, 2)) ?>%"></span>
                            <span class="report-composition__slice report-composition__slice--liability" style="width: <?= e((string) round($liabilityShare, 2)) ?>%"></span>
                            <span class="report-composition__slice report-composition__slice--equity" style="width: <?= e((string) round($equityShare, 2)) ?>%"></span>
                        </div>
                        <div class="report-legend">
                            <span><i class="report-legend__dot report-legend__dot--asset"></i>Aset <?= e(ledger_currency((float) $report['total_assets'])) ?></span>
                            <span><i class="report-legend__dot report-legend__dot--liability"></i>Kewajiban <?= e(ledger_currency((float) $report['total_liabilities'])) ?></span>
                            <span><i class="report-legend__dot report-legend__dot--equity"></i>Ekuitas <?= e(ledger_currency((float) $report['total_equity'])) ?></span>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!$report['is_balanced']): ?><div class="alert alert-warning">Neraca belum seimbang. Selisih saat ini sebesar <strong><?= e(ledger_currency(abs((float) $report['difference']))) ?></strong>.</div><?php endif; ?>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table align-middle mb-0 report-analytics-table">
                    <thead><tr><th>No</th><th>Kode Akun</th><th>Uraian</th><th class="text-end"><?= e($currentColumnLabel) ?></th></tr></thead><tbody>
                    <?php
                    $rowNo = 1;
                    $sections = [
                        'ASET' => $report['asset_rows'] ?? [],
                        'KEWAJIBAN' => $report['liability_rows'] ?? [],
                        'EKUITAS' => $report['equity_rows'] ?? [],
                    ];
                    foreach ($sections as $sectionLabel => $sectionRows):
                    ?>
                        <tr class="report-row report-row--section"><td><?= $rowNo++ ?></td><td colspan="3" class="fw-semibold"><?= e($sectionLabel) ?></td></tr>
                        <?php if ($sectionRows === []): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Tidak ada akun untuk bagian ini.</td></tr>
                        <?php else: foreach ($sectionRows as $row): ?>
                            <?php $currentUrl = report_drilldown_url((int) ($row['account_id'] ?? 0), $filters, 'balance_sheet'); ?>
                            <tr>
                                <td><?= $rowNo++ ?></td>
                                <td class="fw-semibold"><?= e((string) $row['account_code']) ?></td>
                                <td><?= e((string) $row['account_name']) ?></td>
                                <td class="text-end fw-semibold"><a href="<?= e($currentUrl) ?>" class="report-value-link"><?= e(ledger_currency((float) $row['amount'])) ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    <?php endforeach; ?>
                </tbody></table>
            </div></div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat neraca.</div></div>
    <?php endif; ?>
</div>
