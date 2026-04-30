<?php declare(strict_types=1); ?>
<?php
$groupLabels = [
    'ASSET' => 'Aktiva',
    'LIABILITY' => 'Kewajiban',
    'EQUITY' => 'Modal',
    'REVENUE' => 'Pendapatan',
    'EXPENSE' => 'Beban',
];
$groupedRows = [];
foreach (($rows ?? []) as $row) {
    $type = strtoupper((string) ($row['account_type'] ?? ''));
    $groupedRows[$type][] = $row;
}
$groupOrder = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];
$orderedGroupKeys = array_values(array_unique(array_merge(
    array_values(array_filter($groupOrder, static fn (string $key): bool => isset($groupedRows[$key]))),
    array_keys($groupedRows)
)));
?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Laporan Keuangan</div>
                <h1 class="module-hero__title">Neraca Saldo</h1>
                <p class="module-hero__text">Disusun ringkas agar pemeriksa bisa langsung melihat mutasi debit, kredit, dan saldo akhir setiap akun dengan cepat.</p>
            </div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/trial-balance/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/trial-balance/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/trial-balance/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm mb-4 report-filter-card">
        <div class="card-body p-4">
            <div class="report-filter-head">
                <div>
                    <h2 class="report-filter-head__title">Filter Laporan</h2>
                    <p class="report-filter-head__text">Gunakan periode, tahun, unit usaha, atau tanggal manual untuk mengunci ruang lingkup saldo yang ingin diperiksa.</p>
                </div>
            </div>
            <form method="get" action="<?= e(base_url('/trial-balance')) ?>" class="row g-3 align-items-end report-filter-grid">
                <input type="hidden" name="filter_scope" value="<?= e(report_filter_scope($filters)) ?>">
                <div class="col-lg-2">
                    <label class="form-label">Periode Awal</label>
                    <select name="period_id" class="form-select">
                        <?= report_period_select_options($periods, (int) ($filters['period_id'] ?? 0), 'Manual tanggal') ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Sampai Periode</label>
                    <select name="period_to_id" class="form-select">
                        <?= report_period_select_options($periods, (int) ($filters['period_to_id'] ?? 0), 'Sama dengan periode awal') ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Tahun</label>
                    <select name="fiscal_year" class="form-select">
                        <option value="">Semua tahun</option>
                        <?php foreach (($reportYears ?? []) as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Unit Usaha</label>
                    <select name="unit_id" class="form-select">
                        <option value="">Semua Unit</option>
                        <?php foreach (($units ?? []) as $unit): ?>
                            <option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Mulai</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Akhir</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>">
                </div>
                <div class="col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Tampil</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <section class="report-summary-strip">
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Rentang Data</span>
                <span class="report-summary-strip__value"><?= e(format_id_date((string) $filters['date_from'])) ?> - <?= e(format_id_date((string) $filters['date_to'])) ?></span>
                <span class="report-summary-strip__meta"><?= e($selectedPeriod['period_name'] ?? 'Filter tanggal manual') ?></span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Unit Usaha</span>
                <span class="report-summary-strip__value"><?= e(business_unit_label($selectedUnit)) ?></span>
                <span class="report-summary-strip__meta">Ruang lingkup laporan</span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Jumlah Akun</span>
                <span class="report-summary-strip__value"><?= e((string) $summary['account_count']) ?></span>
                <span class="report-summary-strip__meta">Akun aktif yang terbaca</span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Total Debit</span>
                <span class="report-summary-strip__value"><?= e(ledger_currency((float) ($summary['ending_debit_total'] ?? 0))) ?></span>
                <span class="report-summary-strip__meta">Saldo akun sisi debit</span>
            </article>
            <article class="report-summary-strip__item">
                <span class="report-summary-strip__label">Total Kredit</span>
                <span class="report-summary-strip__value"><?= e(ledger_currency((float) ($summary['ending_credit_total'] ?? 0))) ?></span>
                <span class="report-summary-strip__meta">Saldo akun sisi kredit</span>
            </article>
        </section>

        <div class="report-chip-bar">
            <div class="report-chip"><strong>Periode</strong> <?= e(report_period_label($filters, $selectedPeriod)) ?></div>
            <div class="report-chip"><strong>Unit</strong> <?= e(business_unit_label($selectedUnit)) ?></div>
            <div class="report-chip"><strong>Akun</strong> <?= e(number_format((int) ($summary['account_count'] ?? 0), 0, ',', '.')) ?> akun</div>
        </div>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="report-table-head">
                    <div>
                        <div class="module-hero__eyebrow mb-2">Tabel Utama</div>
                        <h2 class="h4 mb-1">Saldo Akun</h2>
                        <p class="report-help-note mb-0">Format dibuat sederhana seperti neraca saldo pemeriksaan: saldo akun disajikan pada sisi debit atau kredit sesuai posisi akhirnya.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table trial-balance-screen-table">
                        <thead>
                        <tr>
                            <th>Nama Akun</th>
                            <th class="text-end">Debit (Rp)</th>
                            <th class="text-end">Kredit (Rp)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-5">Tidak ada data neraca saldo untuk filter yang dipilih.</td></tr>
                        <?php else: foreach ($orderedGroupKeys as $groupKey): ?>
                            <?php $items = $groupedRows[$groupKey] ?? []; ?>
                            <?php if ($items === []) { continue; } ?>
                            <tr class="trial-balance-screen-table__group">
                                <td colspan="3"><?= e($groupLabels[$groupKey] ?? report_account_type_label($groupKey)) ?></td>
                            </tr>
                            <?php foreach ($items as $row): ?>
                                <?php
                                    $currentUrl = report_drilldown_url((int) ($row['account_id'] ?? 0), $filters, 'trial_balance');
                                    $closingSide = (string) ($row['closing_side'] ?? '-');
                                    $closingBalance = (float) ($row['closing_balance'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <div class="trial-balance-screen-table__account"><?= e((string) $row['account_name']) ?></div>
                                        <div class="trial-balance-screen-table__code"><?= e((string) $row['account_code']) ?></div>
                                    </td>
                                    <td class="text-end fw-semibold report-numeric">
                                        <?php if ($closingSide === 'D' && abs($closingBalance) > 0.004): ?>
                                            <a href="<?= e($currentUrl) ?>" class="report-value-link"><?= e(ledger_currency($closingBalance)) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold report-numeric">
                                        <?php if ($closingSide === 'K' && abs($closingBalance) > 0.004): ?>
                                            <a href="<?= e($currentUrl) ?>" class="report-value-link"><?= e(ledger_currency($closingBalance)) ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; endif; ?>
                        </tbody>
                        <?php if ($rows !== []): ?>
                            <tfoot>
                            <tr>
                                <th class="text-center">TOTAL</th>
                                <th class="text-end"><?= e(ledger_currency((float) ($summary['ending_debit_total'] ?? 0))) ?></th>
                                <th class="text-end"><?= e(ledger_currency((float) ($summary['ending_credit_total'] ?? 0))) ?></th>
                            </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat neraca saldo.</div></div>
    <?php endif; ?>
</div>
