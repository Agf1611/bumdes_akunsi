<?php declare(strict_types=1); ?>
<?php
$currentAsOf = trim((string) ($filters['date_to'] ?? ''));
$currentColumnLabel = $currentAsOf !== '' ? 'Per ' . format_id_date($currentAsOf) : 'Saldo Akhir';
$comparisonEnabled = !empty($report['comparison_enabled']);
$comparisonColumnLabel = (string) ($report['comparison_column_label'] ?? 'Tahun Sebelumnya');
$compositionTotal = max(1.0, (float) ($report['total_assets'] ?? 0));
$assetShare = ((float) ($report['total_assets'] ?? 0) / $compositionTotal) * 100;
$liabilityShare = ((float) ($report['total_liabilities'] ?? 0) / $compositionTotal) * 100;
$equityShare = ((float) ($report['total_equity'] ?? 0) / $compositionTotal) * 100;
?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Laporan Keuangan</div>
                <h1 class="module-hero__title">Laporan Neraca</h1>
                <p class="module-hero__text">Disusun simpel dan formal agar pemeriksa dapat langsung membaca posisi aset, kewajiban, dan ekuitas pada tanggal laporan.</p>
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

    <div class="card shadow-sm mb-4 report-filter-card"><div class="card-body p-4">
        <div class="report-filter-head">
            <div>
                <h2 class="report-filter-head__title">Filter Laporan</h2>
                <p class="report-filter-head__text">Pilih periode atau tanggal neraca, lalu sistem menampilkan posisi keuangan yang relevan tanpa kolom tambahan yang membingungkan.</p>
            </div>
        </div>
        <form method="get" action="<?= e(base_url('/balance-sheet')) ?>" class="row g-3 align-items-end report-filter-grid">
            <input type="hidden" name="filter_scope" value="<?= e(report_filter_scope($filters)) ?>">
            <div class="col-lg-2"><label for="period_id" class="form-label">Periode Awal</label><select name="period_id" id="period_id" class="form-select"><?= report_period_select_options($periods, (int) ($filters['period_id'] ?? 0), 'Manual tanggal') ?></select></div>
            <div class="col-lg-2"><label for="period_to_id" class="form-label">Sampai Periode</label><select name="period_to_id" id="period_to_id" class="form-select"><?= report_period_select_options($periods, (int) ($filters['period_to_id'] ?? 0), 'Sama dengan periode awal') ?></select></div>
            <div class="col-lg-2"><label for="fiscal_year" class="form-label">Tahun</label><select name="fiscal_year" id="fiscal_year" class="form-select"><option value="">Semua tahun</option><?php foreach (($reportYears ?? []) as $year): ?><option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-3"><label for="unit_id" class="form-label">Unit Usaha</label><select name="unit_id" id="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label for="date_from" class="form-label">Tanggal Awal (opsional)</label><input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>"></div>
            <div class="col-lg-2"><label for="date_to" class="form-label">Tanggal Neraca</label><input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>"></div>
            <div class="col-lg-2"><label for="comparison_mode" class="form-label">Pembanding</label><select name="comparison_mode" id="comparison_mode" class="form-select"><?php foreach (($comparisonModes ?? []) as $mode => $label): ?><option value="<?= e((string) $mode) ?>" <?= (string) ($filters['comparison_mode'] ?? '') === (string) $mode ? 'selected' : '' ?>><?= e((string) $label) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><div class="form-check pt-4"><input class="form-check-input" type="checkbox" name="show_visual" id="bs_show_visual" value="1" <?= !empty($filters['show_visual']) ? 'checked' : '' ?>><label class="form-check-label" for="bs_show_visual">Visual</label></div></div>
            <div class="col-lg-2 d-grid"><button type="submit" class="btn btn-primary">Tampil</button></div>
        </form>
    </div></div>

    <?php if ($filters['date_to'] !== ''): ?>
        <section class="report-summary-strip">
            <article class="report-summary-strip__item"><span class="report-summary-strip__label">Tanggal Neraca</span><span class="report-summary-strip__value"><?= e(format_id_date((string) $filters['date_to'])) ?></span><span class="report-summary-strip__meta"><?= e($selectedPeriod['period_name'] ?? 'Tanggal bebas') ?></span></article>
            <article class="report-summary-strip__item"><span class="report-summary-strip__label">Unit Usaha</span><span class="report-summary-strip__value"><?= e($selectedUnitLabel) ?></span><span class="report-summary-strip__meta">Ruang lingkup laporan</span></article>
            <article class="report-summary-strip__item"><span class="report-summary-strip__label">Status Neraca</span><span class="report-summary-strip__value"><?= $report['is_balanced'] ? 'Seimbang' : 'Belum Seimbang' ?></span><span class="report-summary-strip__meta">Selisih <?= e(ledger_currency(abs((float) $report['difference']))) ?></span></article>
            <article class="report-summary-strip__item"><span class="report-summary-strip__label">Total Aset</span><span class="report-summary-strip__value"><?= e(ledger_currency((float) $report['total_assets'])) ?></span><span class="report-summary-strip__meta">Total posisi keuangan</span></article>
        </section>

        <div class="report-chip-bar">
            <div class="report-chip"><strong>Periode</strong> <?= e(report_period_label($filters, $selectedPeriod)) ?></div>
            <div class="report-chip"><strong>Unit</strong> <?= e((string) $selectedUnitLabel) ?></div>
            <?php if ($comparisonEnabled): ?><div class="report-chip"><strong>Pembanding</strong> <?= e($comparisonColumnLabel) ?></div><?php endif; ?>
            <div class="report-chip"><strong>Status</strong> <?= $report['is_balanced'] ? 'Seimbang' : 'Belum Seimbang' ?></div>
        </div>

        <?php if (!empty($filters['show_visual'])): ?>
            <section class="card shadow-sm report-chart-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                        <div>
                            <div class="module-hero__eyebrow mb-2">Visual Ringkas</div>
                            <h2 class="h4 mb-1">Komposisi Aset, Kewajiban, dan Ekuitas</h2>
                            <p class="report-help-note mb-0">Visual ini membantu membaca komposisi besar. Angka resmi tetap mengikuti tabel neraca di bawah.</p>
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

        <?php if (!$report['is_balanced']): ?>
            <?php if (!empty($report['needs_closing_journal'])): ?>
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <strong>Neraca belum seimbang karena laba/rugi berjalan belum ditutup.</strong>
                        <div>Selisih <strong><?= e(ledger_currency(abs((float) $report['difference']))) ?></strong> sama dengan laba/rugi periode berjalan. Agar format Neraca tetap seperti dokumen pemeriksaan, buat jurnal penutup ke akun ekuitas/modal.</div>
                    </div>
                    <a href="<?= e(base_url('/journals/create')) ?>" class="btn btn-sm btn-primary">Buat Jurnal Penutup</a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Neraca belum seimbang. Selisih saat ini sebesar <strong><?= e(ledger_currency(abs((float) $report['difference']))) ?></strong>. Periksa kembali jurnal, akun COA, dan saldo awal.</div>
            <?php endif; ?>
        <?php endif; ?>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="report-table-head">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Tabel Utama</div>
                        <h2 class="h4 mb-1">Posisi Aset, Kewajiban, dan Ekuitas</h2>
                        <p class="report-help-note mb-0">Setiap bagian disusun per kelompok akun. Nilai akun bisa dibuka ke jurnal sumber jika pemeriksa membutuhkan rincian pendukung.</p>
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table align-middle mb-0 report-analytics-table">
                    <thead><tr><th>No</th><th>Kode Akun</th><th>Uraian</th><th class="text-end"><?= e($currentColumnLabel) ?></th><?php if ($comparisonEnabled): ?><th class="text-end"><?= e($comparisonColumnLabel) ?></th><?php endif; ?></tr></thead><tbody>
                    <?php
                    $rowNo = 1;
                    $sections = [
                        'ASET' => $report['asset_rows'] ?? [],
                        'KEWAJIBAN' => $report['liability_rows'] ?? [],
                        'EKUITAS' => $report['equity_rows'] ?? [],
                    ];
                    foreach ($sections as $sectionLabel => $sectionRows):
                    ?>
                        <tr class="report-row report-row--section"><td><?= $rowNo++ ?></td><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>" class="fw-semibold"><?= e($sectionLabel) ?></td></tr>
                        <?php if ($sectionRows === []): ?>
                            <tr><td colspan="<?= $comparisonEnabled ? '5' : '4' ?>" class="text-center text-secondary py-4">Tidak ada akun untuk bagian ini.</td></tr>
                        <?php else: foreach ($sectionRows as $row): ?>
                            <?php $currentUrl = report_drilldown_url((int) ($row['account_id'] ?? 0), $filters, 'balance_sheet'); ?>
                            <tr>
                                <td><?= $rowNo++ ?></td>
                                <td class="fw-semibold"><?= e((string) $row['account_code']) ?></td>
                                <td><?= e((string) $row['account_name']) ?></td>
                                <td class="text-end fw-semibold report-numeric"><a href="<?= e($currentUrl) ?>" class="report-value-link"><?= e(ledger_currency((float) $row['amount'])) ?></a></td>
                                <?php if ($comparisonEnabled): ?><td class="text-end fw-semibold report-numeric"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?>
                            </tr>
                        <?php endforeach; endif; ?>
                    <?php endforeach; ?>
                    <?php if (abs((float) ($report['current_earnings'] ?? 0)) > 0.004 || ($comparisonEnabled && abs((float) ($report['comparison_current_earnings'] ?? 0)) > 0.004)): ?>
                        <tr>
                            <td><?= $rowNo++ ?></td>
                            <td class="fw-semibold">-</td>
                            <td class="fw-semibold">Laba / Rugi Berjalan</td>
                            <td class="text-end fw-semibold report-numeric"><?= e(ledger_currency((float) ($report['current_earnings'] ?? 0))) ?></td>
                            <?php if ($comparisonEnabled): ?><td class="text-end fw-semibold report-numeric"><?= e(ledger_currency((float) ($report['comparison_current_earnings'] ?? 0))) ?></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>
                </tbody></table>
            </div></div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat neraca.</div></div>
    <?php endif; ?>
</div>
