<?php declare(strict_types=1); ?>
<?php
$periodLabel = report_period_label($filters, $selectedPeriod);
$selectedUnitDisplay = $selectedUnitLabel ?? 'Semua Unit';
$sections = (array) ($report['sections'] ?? []);
$formatAmount = static function (float $amount): string {
    $formatted = ledger_currency(abs($amount));
    return $amount < -0.004 ? '(' . $formatted . ')' : $formatted;
};
$sectionConfigs = [
    'OPERATING' => [
        'title' => 'Arus Kas dari Aktivitas Operasional',
        'subtotal' => 'Kas bersih yang diperoleh dari Aktivitas Operasional',
    ],
    'INVESTING' => [
        'title' => 'Arus Kas dari Aktivitas Investasi',
        'subtotal' => 'Kas bersih yang diperoleh dari Aktivitas Investasi',
    ],
    'FINANCING' => [
        'title' => 'Arus Kas dari Aktivitas Keuangan',
        'subtotal' => 'Kas bersih yang diperoleh dari Aktivitas Keuangan',
    ],
];
?>

<div class="module-page report-analytics-page cash-flow-statement-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Laporan Keuangan</div>
                <h1 class="module-hero__title">Laporan Arus Kas</h1>
                <p class="module-hero__text">Format ringkas seperti statement resmi: satu tabel utama, tidak mengulang saldo yang sama, dan mudah dibaca saat pemeriksaan.</p>
            </div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/cash-flow/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/cash-flow/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/cash-flow/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm report-filter-card">
        <div class="card-body p-4">
            <div class="report-filter-head">
                <div>
                    <h2 class="report-filter-head__title">Filter Laporan</h2>
                    <p class="report-filter-head__text">Pilih periode dan unit usaha. Jurnal saldo awal pada tanggal mulai otomatis masuk ke saldo kas awal, bukan ke mutasi berjalan.</p>
                </div>
            </div>
            <form method="get" action="<?= e(base_url('/cash-flow')) ?>" class="row g-3 align-items-end report-filter-grid">
                <input type="hidden" name="filter_scope" value="<?= e(report_filter_scope($filters)) ?>">
                <div class="col-xl-2 col-lg-4">
                    <label for="period_id" class="form-label">Periode Awal</label>
                    <select name="period_id" id="period_id" class="form-select">
                        <?= report_period_select_options($periods, (int) ($filters['period_id'] ?? 0), 'Manual tanggal') ?>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-4">
                    <label for="period_to_id" class="form-label">Sampai Periode</label>
                    <select name="period_to_id" id="period_to_id" class="form-select">
                        <?= report_period_select_options($periods, (int) ($filters['period_to_id'] ?? 0), 'Sama dengan periode awal') ?>
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

        <div class="report-chip-bar">
            <div class="report-chip"><strong>Periode</strong> <?= e($periodLabel) ?></div>
            <div class="report-chip"><strong>Unit</strong> <?= e($selectedUnitDisplay) ?></div>
            <div class="report-chip"><strong>Metode</strong> Langsung</div>
        </div>

        <section class="card shadow-sm report-table-card cash-flow-statement-card">
            <div class="card-body p-0">
                <div class="report-table-head">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Statement</div>
                        <h2 class="h4 mb-1">Arus Kas Per Aktivitas</h2>
                        <p class="report-help-note mb-0">Data yang sama tidak diulang di kartu lain. Baris dengan nama sama digabung agar laporan tidak dobel.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 cash-flow-statement-table-screen">
                        <thead>
                            <tr>
                                <th>Akun &amp; Kategori</th>
                                <th class="text-end"><?= e(str_replace(' s.d. ', ' - ', $periodLabel)) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionConfigs as $key => $config): ?>
                                <?php
                                $section = (array) ($sections[$key] ?? []);
                                $inRows = (array) ($section['in_rows'] ?? []);
                                $outRows = (array) ($section['out_rows'] ?? []);
                                $net = (float) ($section['net'] ?? 0);
                                ?>
                                <tr class="statement-section-row">
                                    <td colspan="2"><?= e((string) $config['title']) ?></td>
                                </tr>
                                <?php if ($inRows === [] && $outRows === []): ?>
                                    <tr>
                                        <td>Tidak ada mutasi kas</td>
                                        <td class="text-end report-numeric"><?= e($formatAmount(0.0)) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($inRows as $row): ?>
                                    <tr>
                                        <td><?= e((string) ($row['label'] ?? 'Penerimaan kas')) ?></td>
                                        <td class="text-end report-numeric"><?= e($formatAmount((float) ($row['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($outRows as $row): ?>
                                    <tr>
                                        <td><?= e((string) ($row['label'] ?? 'Pengeluaran kas')) ?></td>
                                        <td class="text-end report-numeric"><?= e($formatAmount(-1 * (float) ($row['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="statement-subtotal-row">
                                    <td><?= e((string) $config['subtotal']) ?></td>
                                    <td class="text-end report-numeric"><?= e($formatAmount($net)) ?></td>
                                </tr>
                                <tr class="statement-spacer-row"><td colspan="2"></td></tr>
                            <?php endforeach; ?>
                            <tr class="statement-total-row">
                                <td>Kenaikan (penurunan) kas</td>
                                <td class="text-end report-numeric"><?= e($formatAmount((float) ($report['net_cash_change'] ?? 0))) ?></td>
                            </tr>
                            <tr class="statement-total-row">
                                <td>Saldo kas awal</td>
                                <td class="text-end report-numeric"><?= e($formatAmount((float) ($report['opening_cash'] ?? 0))) ?></td>
                            </tr>
                            <tr class="statement-grand-total-row">
                                <td>Saldo kas akhir</td>
                                <td class="text-end report-numeric"><?= e($formatAmount((float) ($report['closing_cash'] ?? 0))) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="empty-state-panel">
            <div class="empty-state-panel__title">Belum ada laporan arus kas yang ditampilkan</div>
            <div class="empty-state-panel__text">Pilih periode atau rentang tanggal lalu klik <strong>Tampilkan</strong> untuk melihat laporan arus kas.</div>
        </section>
    <?php endif; ?>
</div>
