<?php declare(strict_types=1); ?>
<?php
$periodLabel = report_period_label($filters, $selectedPeriod);
$selectedUnitDisplay = $selectedUnitLabel ?? 'Semua Unit';
$sectionOrder = [
    'OPERATING' => 'Aktivitas Operasi',
    'INVESTING' => 'Aktivitas Investasi',
    'FINANCING' => 'Aktivitas Pendanaan',
];
$currentMetrics = [
    'opening_cash' => ['label' => 'Kas Awal', 'desc' => 'Saldo kas pada awal periode.'],
    'total_operating' => ['label' => 'Kas Bersih Operasi', 'desc' => 'Dari aktivitas operasional.'],
    'total_investing' => ['label' => 'Kas Bersih Investasi', 'desc' => 'Dari aktivitas investasi.'],
    'total_financing' => ['label' => 'Kas Bersih Pendanaan', 'desc' => 'Dari aktivitas pendanaan.'],
    'net_cash_change' => ['label' => 'Perubahan Kas Bersih', 'desc' => 'Kenaikan atau penurunan kas.'],
    'closing_cash' => ['label' => 'Kas Akhir', 'desc' => 'Saldo kas pada akhir periode.'],
];
$visualBase = 1.0;
foreach (['total_operating', 'total_investing', 'total_financing', 'net_cash_change'] as $visualKey) {
    $visualBase = max($visualBase, abs((float) ($report[$visualKey] ?? 0)));
}
?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Laporan Analitis</div>
                <h1 class="module-hero__title">Laporan Arus Kas</h1>
                <p class="module-hero__text">Metode langsung yang kembali fokus ke angka utama, tetap dibantu visual layar dan drill-down jurnal saat dibutuhkan.</p>
            </div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/reports/drilldown?' . http_build_query([
                        'source_report' => 'cash_flow',
                        'period_id' => $filters['period_id'] ?? null,
                        'unit_id' => $filters['unit_id'] ?? null,
                        'date_from' => $filters['date_from'] ?? null,
                        'date_to' => $filters['date_to'] ?? null,
                    ]))) ?>" class="btn btn-outline-light">Drill-down Jurnal</a>
                    <a href="<?= e(base_url('/cash-flow/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/cash-flow/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/cash-flow/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="get" action="<?= e(base_url('/cash-flow')) ?>" class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4">
                    <label for="period_id" class="form-label">Periode Referensi</label>
                    <select name="period_id" id="period_id" class="form-select">
                        <option value="">Opsional / bantu isi tanggal</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= e((string) $period['id']) ?>" <?= (string) ($filters['period_id'] ?? '') === (string) $period['id'] ? 'selected' : '' ?>>
                                <?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?>
                            </option>
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
                            <option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>>
                                <?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="date_from" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-xl-2 col-lg-3">
                    <label for="date_to" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-xl-2 col-lg-3">
                    <div class="form-check pt-4">
                        <input class="form-check-input" type="checkbox" name="show_visual" id="cf_show_visual" value="1" <?= !empty($filters['show_visual']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cf_show_visual">Visual</label>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 d-grid">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <?php foreach ((array) ($warnings ?? []) as $warning): ?>
            <div class="alert alert-warning mb-0"><?= e((string) $warning) ?></div>
        <?php endforeach; ?>

        <section class="report-kpi-grid">
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Periode Aktif</div>
                <div class="report-kpi-card__value report-kpi-card__value--sm"><?= e($periodLabel) ?></div>
                <div class="report-kpi-card__meta">Unit: <?= e($selectedUnitDisplay) ?></div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Jumlah Mutasi</div>
                <div class="report-kpi-card__value"><?= e(number_format((float) ($report['row_count'] ?? 0), 0, ',', '.')) ?></div>
                <div class="report-kpi-card__meta">Baris jurnal kas yang terbaca</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Perubahan Kas Bersih</div>
                <div class="report-kpi-card__value"><?= e(ledger_currency((float) ($report['net_cash_change'] ?? 0))) ?></div>
                <div class="report-kpi-card__meta">Perubahan kas dalam periode aktif</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Kas Akhir</div>
                <div class="report-kpi-card__value"><?= e(ledger_currency((float) ($report['closing_cash'] ?? 0))) ?></div>
                <div class="report-kpi-card__meta">Saldo akhir menurut laporan arus kas</div>
            </article>
        </section>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Komponen</th>
                                <th class="text-end">Aktual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $metricNo = 1; ?>
                            <?php foreach ($currentMetrics as $metricKey => $metric): ?>
                                <tr>
                                    <td><?= $metricNo++ ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e((string) $metric['label']) ?></div>
                                        <div class="text-secondary small"><?= e((string) $metric['desc']) ?></div>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <a href="<?= e(report_drilldown_url(null, $filters, 'cash_flow')) ?>" class="report-value-link"><?= e(ledger_currency((float) ($report[$metricKey] ?? 0))) ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <?php if (!empty($filters['show_visual'])): ?>
            <section class="card shadow-sm report-chart-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                        <div>
                            <div class="module-hero__eyebrow mb-2">Visual Ringkas</div>
                            <h2 class="h4 mb-1">Struktur Arus Kas</h2>
                            <p class="text-secondary mb-0">Visual batang sederhana tetap dipertahankan agar pola kas masuk dan keluar cepat terbaca.</p>
                        </div>
                    </div>
                    <div class="report-mini-chart report-mini-chart--bars">
                        <?php foreach (['total_operating' => 'Operasi', 'total_investing' => 'Investasi', 'total_financing' => 'Pendanaan', 'net_cash_change' => 'Kas Bersih'] as $metricKey => $metricLabel): ?>
                            <?php
                            $currentAmount = (float) ($report[$metricKey] ?? 0);
                            $currentWidth = min(100, max(8, (abs($currentAmount) / $visualBase) * 100));
                            ?>
                            <div class="report-bar-group">
                                <div class="report-bar-group__label"><?= e($metricLabel) ?></div>
                                <div class="report-bar-group__row">
                                    <span class="report-bar-group__legend report-bar-group__legend--income">Aktual</span>
                                    <div class="report-bar-group__track">
                                        <span class="report-bar-group__bar <?= $currentAmount >= 0 ? 'report-bar-group__bar--income' : 'report-bar-group__bar--expense' ?>" style="width: <?= e((string) round($currentWidth, 2)) ?>%"></span>
                                    </div>
                                    <span class="report-bar-group__value"><?= e(ledger_currency($currentAmount)) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach (['OPERATING', 'INVESTING', 'FINANCING'] as $sectionCode): ?>
            <?php
            $rows = match ($sectionCode) {
                'OPERATING' => (array) ($report['operating_rows'] ?? []),
                'INVESTING' => (array) ($report['investing_rows'] ?? []),
                default => (array) ($report['financing_rows'] ?? []),
            };
            $sectionTotal = match ($sectionCode) {
                'OPERATING' => (float) ($report['total_operating'] ?? 0),
                'INVESTING' => (float) ($report['total_investing'] ?? 0),
                default => (float) ($report['total_financing'] ?? 0),
            };
            ?>
            <section class="card shadow-sm report-table-card">
                <div class="card-body p-0">
                    <div class="report-table-head">
                        <div>
                            <div class="module-hero__eyebrow mb-2"><?= e($sectionCode) ?></div>
                            <h2 class="h4 mb-1">Arus Kas dari <?= e($sectionOrder[$sectionCode]) ?></h2>
                            <p class="text-secondary mb-0">Daftar transaksi kas yang diklasifikasikan ke bagian ini.</p>
                        </div>
                        <div class="report-table-head__meta">
                            <div class="report-table-head__stat">
                                <span>Total Aktual</span>
                                <strong><?= e(ledger_currency($sectionTotal)) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 report-analytics-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Jurnal</th>
                                    <th>Uraian</th>
                                    <th>Unit</th>
                                    <th class="text-end">Kas Masuk</th>
                                    <th class="text-end">Kas Keluar</th>
                                    <th class="text-end">Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rows === []): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-secondary py-4">Belum ada mutasi kas pada bagian ini untuk filter yang dipilih.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= e(format_id_date((string) ($row['journal_date'] ?? ''))) ?></td>
                                            <td><?= e((string) ($row['journal_no'] ?? '-')) ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= e((string) ($row['label'] ?? $row['description'] ?? '-')) ?></div>
                                                <?php if (trim((string) ($row['classification_note'] ?? '')) !== ''): ?>
                                                    <div class="text-secondary small"><?= e((string) $row['classification_note']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e((string) ($row['unit_label'] ?? '-')) ?></td>
                                            <td class="text-end"><?= e(ledger_currency((float) ($row['cash_in'] ?? 0))) ?></td>
                                            <td class="text-end"><?= e(ledger_currency((float) ($row['cash_out'] ?? 0))) ?></td>
                                            <td class="text-end fw-semibold report-direction report-direction--<?= ((float) ($row['net_amount'] ?? 0)) >= 0 ? 'up' : 'down' ?>"><?= e(ledger_currency((float) ($row['net_amount'] ?? 0))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <?php $difference = (float) ($report['difference'] ?? 0); ?>
        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="report-table-head">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Rekonsiliasi</div>
                        <h2 class="h4 mb-1">Sinkronisasi Saldo Kas</h2>
                        <p class="text-secondary mb-0">Memastikan saldo akhir laporan arus kas sama dengan saldo kas atau bank yang terbaca sistem.</p>
                    </div>
                    <span class="report-status-badge <?= abs($difference) < 0.005 ? 'report-status-badge--ok' : 'report-status-badge--warn' ?>">
                        <?= abs($difference) < 0.005 ? 'Sinkron' : 'Perlu Tinjau' ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table">
                        <tbody>
                            <tr><td>Saldo kas awal</td><td class="text-end fw-semibold"><?= e(ledger_currency((float) ($report['opening_cash'] ?? 0))) ?></td></tr>
                            <tr><td>Kenaikan atau penurunan kas</td><td class="text-end fw-semibold"><?= e(ledger_currency((float) ($report['net_cash_change'] ?? 0))) ?></td></tr>
                            <tr><td>Saldo kas akhir menurut arus kas</td><td class="text-end fw-semibold"><?= e(ledger_currency((float) ($report['closing_cash'] ?? 0))) ?></td></tr>
                            <tr><td>Saldo kas atau bank riil</td><td class="text-end fw-semibold"><?= e(ledger_currency((float) ($report['actual_closing_cash'] ?? $report['closing_cash'] ?? 0))) ?></td></tr>
                            <tr>
                                <td>Selisih rekonsiliasi</td>
                                <td class="text-end fw-semibold report-direction report-direction--<?= abs($difference) < 0.005 ? 'stable' : 'down' ?>"><?= e(ledger_currency($difference)) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="empty-state-panel">
            <div class="empty-state-panel__title">Belum ada laporan arus kas yang ditampilkan</div>
            <div class="empty-state-panel__text">Pilih periode atau rentang tanggal lalu klik <strong>Tampilkan</strong> untuk melihat arus kas operasi, investasi, dan pendanaan.</div>
        </section>
    <?php endif; ?>
</div>
