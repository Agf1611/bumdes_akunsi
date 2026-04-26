<?php declare(strict_types=1); ?>
<?php
$reportMode = (string) ($filters['mode'] ?? 'period');
$periodNet = (float) ($report['net_income'] ?? 0);
$trendPoints = is_array($trend ?? null) ? $trend : [];
$trendMax = 1.0;
foreach ($trendPoints as $point) {
    $trendMax = max($trendMax, (float) ($point['revenue'] ?? 0), (float) ($point['expense'] ?? 0), abs((float) ($point['net'] ?? 0)));
}
?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Laporan Analitis</div>
                <h1 class="module-hero__title">Laporan Laba Rugi</h1>
                <p class="module-hero__text">Laporan kinerja usaha yang kembali sederhana, fokus pada angka utama, tetap dibantu visual tren agar cepat dibaca.</p>
            </div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/profit-loss/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/profit-loss/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/profit-loss/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="get" action="<?= e(base_url('/profit-loss')) ?>" class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4">
                    <label for="period_id" class="form-label">Periode Referensi</label>
                    <select name="period_id" id="period_id" class="form-select">
                        <option value="">Manual tanggal / semua periode</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= e((string) $period['id']) ?>" <?= (string) $filters['period_id'] === (string) $period['id'] ? 'selected' : '' ?>>
                                <?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="mode" class="form-label">Mode Laporan</label>
                    <select name="mode" id="mode" class="form-select">
                        <?php foreach (($modes ?? []) as $modeValue => $modeLabel): ?>
                            <option value="<?= e((string) $modeValue) ?>" <?= $reportMode === (string) $modeValue ? 'selected' : '' ?>><?= e((string) $modeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="fiscal_year" class="form-label">Tahun</label>
                    <select name="fiscal_year" id="fiscal_year" class="form-select">
                        <option value="">Semua tahun</option>
                        <?php foreach (($reportYears ?? []) as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-3 col-lg-4">
                    <label for="unit_id" class="form-label">Unit Usaha</label>
                    <select name="unit_id" id="unit_id" class="form-select">
                        <option value="">Semua Unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="date_to" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>">
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="date_from" class="form-label">Tanggal Awal Manual</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>">
                </div>
                <div class="col-xl-2 col-lg-3">
                    <div class="form-check pt-4">
                        <input class="form-check-input" type="checkbox" name="show_visual" id="show_visual" value="1" <?= !empty($filters['show_visual']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_visual">Visual</label>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 d-grid">
                    <button type="submit" class="btn btn-primary">Tampil</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <section class="report-kpi-grid">
            <article class="report-kpi-card">
                <div class="report-kpi-card__label"><?= e((string) ($report['current_label'] ?? 'Laba Periode')) ?></div>
                <div class="report-kpi-card__value"><?= e(profit_loss_currency($periodNet)) ?></div>
                <div class="report-kpi-card__meta"><?= e((string) ($report['current_range_label'] ?? '-')) ?></div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Total Pendapatan</div>
                <div class="report-kpi-card__value"><?= e(profit_loss_currency((float) ($report['total_revenue'] ?? 0))) ?></div>
                <div class="report-kpi-card__meta">Pendapatan usaha pada periode aktif</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Total Beban</div>
                <div class="report-kpi-card__value"><?= e(profit_loss_currency((float) ($report['total_expense'] ?? 0))) ?></div>
                <div class="report-kpi-card__meta">Beban usaha pada periode aktif</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Unit Usaha</div>
                <div class="report-kpi-card__value report-kpi-card__value--sm"><?= e((string) ($selectedUnitLabel ?? 'Semua Unit')) ?></div>
                <div class="report-kpi-card__meta"><?= e((string) ($report['mode_label'] ?? 'Mode laporan')) ?></div>
            </article>
        </section>

        <?php if (!empty($filters['show_visual']) && $trendPoints !== []): ?>
            <section class="card shadow-sm report-chart-card">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <div class="module-hero__eyebrow mb-2">Visual Ringkas</div>
                            <h2 class="h4 mb-1">Tren Pendapatan, Beban, dan Laba Bersih</h2>
                            <p class="text-secondary mb-0">Visual layar tetap dipertahankan agar arah kinerja bisa dibaca lebih cepat.</p>
                        </div>
                    </div>
                    <div class="report-mini-chart">
                        <?php foreach ($trendPoints as $point): ?>
                            <?php
                            $revenueHeight = max(6, ((float) ($point['revenue'] ?? 0) / $trendMax) * 160);
                            $expenseHeight = max(6, ((float) ($point['expense'] ?? 0) / $trendMax) * 160);
                            $netHeight = max(6, (abs((float) ($point['net'] ?? 0)) / $trendMax) * 160);
                            ?>
                            <div class="report-mini-chart__group">
                                <div class="report-mini-chart__bars">
                                    <span class="report-mini-chart__bar report-mini-chart__bar--revenue" style="height: <?= e((string) round($revenueHeight, 2)) ?>px" title="Pendapatan"></span>
                                    <span class="report-mini-chart__bar report-mini-chart__bar--expense" style="height: <?= e((string) round($expenseHeight, 2)) ?>px" title="Beban"></span>
                                    <span class="report-mini-chart__bar report-mini-chart__bar--net" style="height: <?= e((string) round($netHeight, 2)) ?>px" title="Laba Bersih"></span>
                                </div>
                                <div class="report-mini-chart__label"><?= e(dashboard_month_label((string) ($point['label'] ?? ''))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="report-legend">
                        <span><i class="report-legend__dot report-legend__dot--revenue"></i>Pendapatan</span>
                        <span><i class="report-legend__dot report-legend__dot--expense"></i>Beban</span>
                        <span><i class="report-legend__dot report-legend__dot--net"></i>Laba Bersih</span>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table">
                        <thead>
                        <tr>
                            <th style="width:6rem" class="text-center">No</th>
                            <th>Uraian</th>
                            <th class="text-end"><?= e((string) ($report['current_column_label'] ?? 'Periode')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($statement_rows === []): ?>
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-5">Tidak ada data laba rugi untuk filter yang dipilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statement_rows as $row): ?>
                                <?php
                                $rowType = (string) $row['row_type'];
                                $drilldownCurrent = (int) ($row['account_id'] ?? 0) > 0 ? report_drilldown_url((int) $row['account_id'], $filters, 'profit_loss') : '';
                                ?>
                                <tr class="report-row report-row--<?= e($rowType) ?>">
                                    <td class="text-center"><?= e((string) $row['order']) ?></td>
                                    <td class="report-row__label"><?= e((string) $row['label']) ?></td>
                                    <td class="text-end fw-semibold">
                                        <?php if ($row['current_amount'] === null): ?>
                                            -
                                        <?php elseif ($drilldownCurrent !== ''): ?>
                                            <a href="<?= e($drilldownCurrent) ?>" class="report-value-link"><?= e(profit_loss_currency((float) $row['current_amount'])) ?></a>
                                        <?php else: ?>
                                            <?= e(profit_loss_currency((float) $row['current_amount'])) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat laporan laba rugi.</div>
        </div>
    <?php endif; ?>
</div>
