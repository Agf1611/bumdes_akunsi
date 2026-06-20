<?php declare(strict_types=1); ?>
<?php
$reportMode = (string) ($filters['mode'] ?? 'period');
$periodNet = (float) ($report['net_income'] ?? 0);
$accumulatedNet = (float) ($report['comparison_net_income'] ?? 0);
$assetCashUsage = is_array($assetCashUsage ?? null) ? $assetCashUsage : asset_cash_usage_empty($periodNet);
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
                <div class="module-hero__eyebrow">Laporan Keuangan</div>
                <h1 class="module-hero__title">Laporan Laba Rugi</h1>
                <p class="module-hero__text">Disusun sederhana agar pemeriksa mudah membaca hasil bulan berjalan dan akumulasi tahun berjalan dalam satu tabel.</p>
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

    <div class="card shadow-sm report-filter-card">
        <div class="card-body p-4">
            <div class="report-filter-head">
                <div>
                    <h2 class="report-filter-head__title">Filter Laporan</h2>
                    <p class="report-filter-head__text">Pilih periode dan unit usaha. Laporan otomatis menampilkan nilai bulan/periode ini dan akumulasi sampai tanggal akhir.</p>
                </div>
            </div>
            <form method="get" action="<?= e(base_url('/profit-loss')) ?>" class="row g-3 align-items-end report-filter-grid">
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
                <input type="hidden" name="unit_id" value="<?= e((string) ($filters['unit_id'] ?? 0)) ?>">
                <div class="col-12">
                    <div class="small text-secondary">Tanggal manual dipakai jika ingin rentang khusus. Isi tanggal awal lebih dulu, lalu tanggal akhir.</div>
                </div>
                <div class="col-xl-3 col-lg-4">
                    <label for="date_from" class="form-label">Tanggal Awal Manual</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>">
                </div>
                <div class="col-xl-3 col-lg-4">
                    <label for="date_to" class="form-label">Tanggal Akhir Manual</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>">
                </div>
                <div class="col-xl-2 col-lg-4 d-grid">
                    <button type="submit" class="btn btn-primary">Tampil</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <section class="report-summary-strip">
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label"><?= e((string) ($report['current_label'] ?? 'Laba Periode')) ?></span>
                <span class="report-summary-strip__value"><?= e(profit_loss_currency($periodNet)) ?></span>
                <span class="report-summary-strip__meta"><?= e((string) ($report['current_range_label'] ?? '-')) ?></span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Laba Akumulasi</span>
                <span class="report-summary-strip__value"><?= e(profit_loss_currency($accumulatedNet)) ?></span>
                <span class="report-summary-strip__meta"><?= e((string) ($report['comparison_column_label'] ?? 'Akumulasi tahun berjalan')) ?></span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Total Pendapatan</span>
                <span class="report-summary-strip__value"><?= e(profit_loss_currency((float) ($report['total_revenue'] ?? 0))) ?></span>
                <span class="report-summary-strip__meta">Pendapatan usaha pada periode aktif</span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Total Beban</span>
                <span class="report-summary-strip__value"><?= e(profit_loss_currency((float) ($report['total_expense'] ?? 0))) ?></span>
                <span class="report-summary-strip__meta">Beban usaha pada periode aktif</span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Unit Usaha</span>
                <span class="report-summary-strip__value"><?= e((string) ($selectedUnitLabel ?? 'Semua Unit')) ?></span>
                <span class="report-summary-strip__meta"><?= e((string) ($report['mode_label'] ?? 'Mode laporan')) ?></span>
            </article>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Rekonsiliasi Kas</div>
                        <h2 class="h4 mb-1">Penjelasan Belanja Aset</h2>
                        <p class="report-help-note mb-0">Laba/rugi resmi tidak dikurangi pembelian aset; bagian ini menjelaskan penggunaan kas agar laba besar tetapi kas berkurang karena aset tetap terbaca.</p>
                    </div>
                    <?php if ((int) ($assetCashUsage['unlinked_asset_count'] ?? 0) > 0): ?>
                        <span class="badge text-bg-warning">Ada aset belum tertaut jurnal</span>
                    <?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-secondary mb-1">Laba/Rugi sebelum pembelian aset</div>
                            <div class="h5 mb-0"><?= e(profit_loss_currency((float) $assetCashUsage['profit_before_asset_purchase'])) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-secondary mb-1">Pembelian aset dari kas/bank</div>
                            <div class="h5 mb-0"><?= e(profit_loss_currency((float) $assetCashUsage['asset_cash_outflow'])) ?></div>
                            <div class="small text-secondary mt-1">Dari jurnal posted yang tertaut aset.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-secondary mb-1">Indikator sisa setelah pembelian aset</div>
                            <div class="h5 mb-0 <?= ((float) $assetCashUsage['after_asset_purchase_indicator']) >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(profit_loss_currency((float) $assetCashUsage['after_asset_purchase_indicator'])) ?></div>
                            <div class="small text-secondary mt-1">Indikator manajemen, bukan saldo kas akhir resmi.</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <div class="small text-secondary">Total perolehan aset tercatat: <strong class="text-dark"><?= e(profit_loss_currency((float) ($assetCashUsage['asset_acquisition_total'] ?? 0))) ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-secondary">Belum tertaut jurnal: <strong class="text-dark"><?= e((string) ((int) ($assetCashUsage['unlinked_asset_count'] ?? 0))) ?> aset / <?= e(profit_loss_currency((float) ($assetCashUsage['unlinked_asset_total'] ?? 0))) ?></strong></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="report-chip-bar">
            <div class="report-chip"><strong>Periode</strong> <?= e(report_period_label($filters, $selectedPeriod)) ?></div>
            <div class="report-chip"><strong>Mode</strong> <?= e((string) ($report['mode_label'] ?? 'Periode')) ?></div>
            <div class="report-chip"><strong>Akumulasi</strong> <?= e((string) ($report['comparison_column_label'] ?? '-')) ?></div>
            <div class="report-chip"><strong>Unit</strong> <?= e((string) ($selectedUnitLabel ?? 'Semua Unit')) ?></div>
        </div>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="report-table-head">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Tabel Utama</div>
                        <h2 class="h4 mb-1">Struktur Laba Rugi</h2>
                        <p class="report-help-note mb-0">Baris berwarna menandai kelompok dan subtotal. Nilai akun dapat diklik untuk membuka jurnal sumber.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table">
                        <thead>
                        <tr>
                            <th style="width:6rem" class="text-center">No</th>
                            <th>Uraian</th>
                            <th class="text-end"><?= e((string) ($report['current_column_label'] ?? 'Periode')) ?></th>
                            <th class="text-end"><?= e((string) ($report['comparison_column_label'] ?? 'Akumulasi')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($statement_rows === []): ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-5">Tidak ada data laba rugi untuk filter yang dipilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statement_rows as $row): ?>
                                <?php
                                $rowType = (string) $row['row_type'];
                                $drilldownCurrent = (int) ($row['account_id'] ?? 0) > 0 ? report_drilldown_url((int) $row['account_id'], $filters, 'profit_loss') : '';
                                $drilldownAccumulated = '';
                                if ((int) ($row['account_id'] ?? 0) > 0 && (string) ($report['comparison_date_from'] ?? '') !== '' && (string) ($report['comparison_date_to'] ?? '') !== '') {
                                    $drilldownAccumulated = report_drilldown_url((int) $row['account_id'], $filters, 'profit_loss', [
                                        'date_from' => (string) $report['comparison_date_from'],
                                        'date_to' => (string) $report['comparison_date_to'],
                                    ]);
                                }
                                ?>
                                <tr class="report-row report-row--<?= e($rowType) ?>">
                                    <td class="text-center"><?= e((string) $row['order']) ?></td>
                                    <td class="report-row__label"><?= e((string) $row['label']) ?></td>
                                    <td class="text-end fw-semibold report-numeric">
                                        <?php if ($row['current_amount'] === null): ?>
                                            -
                                        <?php elseif ($drilldownCurrent !== ''): ?>
                                            <a href="<?= e($drilldownCurrent) ?>" class="report-value-link"><?= e(profit_loss_currency((float) $row['current_amount'])) ?></a>
                                        <?php else: ?>
                                            <?= e(profit_loss_currency((float) $row['current_amount'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold report-numeric">
                                        <?php if ($row['comparison_amount'] === null): ?>
                                            -
                                        <?php elseif ($drilldownAccumulated !== ''): ?>
                                            <a href="<?= e($drilldownAccumulated) ?>" class="report-value-link"><?= e(profit_loss_currency((float) $row['comparison_amount'])) ?></a>
                                        <?php else: ?>
                                            <?= e(profit_loss_currency((float) $row['comparison_amount'])) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="report-row report-row--grand-total">
                                <td></td>
                                <td class="report-row__label"><?= e(strtoupper(profit_loss_display_label())) ?></td>
                                <td class="text-end fw-bold report-numeric"><?= e(profit_loss_currency($periodNet)) ?></td>
                                <td class="text-end fw-bold report-numeric"><?= e(profit_loss_currency($accumulatedNet)) ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="border-top p-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-5">
                            <div class="fw-semibold">Penjelasan setelah total laba/rugi</div>
                            <div class="small text-secondary">Pembelian aset dicatat sebagai penggunaan kas/investasi, bukan beban laba rugi langsung.</div>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-2">
                                <div class="col-md-4"><div class="small text-secondary">Laba/Rugi</div><div class="fw-bold"><?= e(profit_loss_currency((float) $assetCashUsage['profit_before_asset_purchase'])) ?></div></div>
                                <div class="col-md-4"><div class="small text-secondary">Belanja Aset</div><div class="fw-bold"><?= e(profit_loss_currency((float) $assetCashUsage['asset_cash_outflow'])) ?></div></div>
                                <div class="col-md-4"><div class="small text-secondary">Sisa Indikator</div><div class="fw-bold <?= ((float) $assetCashUsage['after_asset_purchase_indicator']) >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(profit_loss_currency((float) $assetCashUsage['after_asset_purchase_indicator'])) ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat laporan laba rugi.</div>
        </div>
    <?php endif; ?>
</div>
