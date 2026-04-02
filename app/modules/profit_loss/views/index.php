<?php declare(strict_types=1); ?>
<?php
$reportMode = (string) ($filters['mode'] ?? 'period');
$currentRangeLabel = (string) ($report['current_range_label'] ?? '-');
$cumulativeRangeLabel = (string) ($report['cumulative_range_label'] ?? '-');
$periodNet = (float) ($report['net_income'] ?? 0);
$cumulativeNet = (float) ($report['cumulative_net_income'] ?? 0);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Laporan Laba Rugi</h1>
        <p class="text-secondary mb-0">Laporan kinerja usaha per periode dengan pembanding akumulasi tahun berjalan sampai tanggal akhir.</p>
    </div>
    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <div class="d-flex gap-2">
            <a href="<?= e(base_url('/profit-loss/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
            <a href="<?= e(base_url('/profit-loss/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-primary">Export PDF</a>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
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
            <div class="col-xl-2 col-lg-3 d-grid">
                <button type="submit" class="btn btn-primary">Tampil</button>
            </div>
        </form>
        <div class="small text-secondary mt-3">
            <strong>Catatan:</strong> mode <em>Bulanan / Periode penuh</em> menampilkan satu bulan/periode penuh. Mode <em>Sampai tanggal akhir</em> menampilkan dari awal bulan sampai tanggal akhir yang dipilih.
        </div>
    </div>
</div>

<?php if (($filters['date_to'] ?? '') !== ''): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mode</div><div class="fw-semibold"><?= e((string) ($report['mode_label'] ?? '-')) ?></div><div class="text-secondary small"><?= e($selectedPeriod['period_name'] ?? 'Manual tanggal') ?></div></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Periode Laporan</div><div class="fw-semibold"><?= e($currentRangeLabel) ?></div><div class="text-secondary small">Unit: <?= e($selectedUnitLabel) ?></div></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1"><?= e((string) ($report['current_label'] ?? 'Laba Periode')) ?></div><div class="fs-5 fw-semibold <?= $periodNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(profit_loss_currency($periodNet)) ?></div><div class="text-secondary small"><?= e((string) ($report['period_total_label'] ?? 'Total Periode')) ?></div></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Akumulasi s.d. Tanggal Akhir</div><div class="fs-5 fw-semibold <?= $cumulativeNet >= 0 ? 'text-info' : 'text-danger' ?>"><?= e(profit_loss_currency($cumulativeNet)) ?></div><div class="text-secondary small"><?= e($cumulativeRangeLabel) ?></div></div></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 coa-table profit-loss-formal-table">
                    <thead>
                    <tr>
                        <th style="width:7rem" class="text-center">No</th>
                        <th>Uraian</th>
                        <th style="width:16rem" class="text-center"><?= e((string) ($report['current_column_label'] ?? 'Periode')) ?></th>
                        <th style="width:16rem" class="text-center"><?= e((string) ($report['cumulative_column_label'] ?? 'Akumulasi')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($statement_rows === []): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-5">Tidak ada data laba rugi untuk filter yang dipilih.</td></tr>
                    <?php else: ?>
                        <?php foreach ($statement_rows as $row): ?>
                            <?php
                                $rowType = (string) $row['row_type'];
                                $trClass = 'statement-row';
                                $labelClass = '';
                                if ($rowType === 'section') {
                                    $trClass = 'statement-section';
                                    $labelClass = 'text-danger fw-bold text-uppercase';
                                } elseif ($rowType === 'category') {
                                    $trClass = 'statement-category';
                                    $labelClass = 'fw-bold text-primary';
                                } elseif ($rowType === 'subtotal') {
                                    $trClass = 'statement-subtotal';
                                    $labelClass = 'fw-bold';
                                } elseif ($rowType === 'section_total') {
                                    $trClass = 'statement-total';
                                    $labelClass = 'fw-bold';
                                }
                            ?>
                            <tr class="<?= e($trClass) ?>">
                                <td class="text-center"><?= e((string) $row['order']) ?></td>
                                <td class="<?= e($labelClass) ?>"><?= e((string) $row['label']) ?></td>
                                <td class="text-end <?= $rowType === 'account' ? '' : 'fw-bold' ?>"><?= $row['current_amount'] === null ? '' : e(profit_loss_currency((float) $row['current_amount'])) ?></td>
                                <td class="text-end <?= $rowType === 'account' ? '' : 'fw-bold' ?>"><?= $row['cumulative_amount'] === null ? '' : e(profit_loss_currency((float) $row['cumulative_amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="statement-grand-total">
                            <td></td>
                            <td class="fw-bold"><?= e(strtoupper(profit_loss_display_label())) ?></td>
                            <td class="text-end fw-bold <?= $periodNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(profit_loss_currency($periodNet)) ?></td>
                            <td class="text-end fw-bold <?= $cumulativeNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(profit_loss_currency($cumulativeNet)) ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .profit-loss-formal-table thead th {
            background: #f8fafc;
            color: #0f172a;
            border-bottom: 2px solid #334155;
            font-size: .92rem;
            white-space: nowrap;
        }
        .profit-loss-formal-table td, .profit-loss-formal-table th {
            padding: .75rem .85rem;
            vertical-align: middle;
            border-color: #d9dee8;
            background: #ffffff;
        }
        .profit-loss-formal-table .statement-section td { background: #fff8f5; }
        .profit-loss-formal-table .statement-category td { background: #f8fbff; }
        .profit-loss-formal-table .statement-subtotal td,
        .profit-loss-formal-table .statement-total td,
        .profit-loss-formal-table .statement-grand-total td { background: #f5f7fb; }
    </style>
<?php else: ?>
    <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat laporan laba rugi.</div></div>
<?php endif; ?>
