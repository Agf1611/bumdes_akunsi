<?php declare(strict_types=1); ?>
<?php
$sections = [
    'OPERATING' => ['title' => 'Aktivitas Operasi', 'rows' => (array) ($report['operating_rows'] ?? [])],
    'INVESTING' => ['title' => 'Aktivitas Investasi', 'rows' => (array) ($report['investing_rows'] ?? [])],
    'FINANCING' => ['title' => 'Aktivitas Pendanaan', 'rows' => (array) ($report['financing_rows'] ?? [])],
];
$difference = (float) ($report['difference'] ?? 0);
?>
<div class="print-sheet classic-report cashflow-analytics-print">
    <?php render_print_header($profile, 'Laporan Arus Kas', report_period_label($filters, $selectedPeriod, true), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="report-heading-block text-center mb-3">
        <div class="report-heading-main">LAPORAN ARUS KAS</div>
        <div class="report-heading-meta">Metode langsung · disajikan dalam rupiah</div>
    </div>

    <?php if (($warnings ?? []) !== []): ?>
        <div class="print-alert-box mb-3">
            <?php foreach ((array) $warnings as $warning): ?>
                <div>• <?= e((string) $warning) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered print-variance-table mb-3">
        <thead>
            <tr>
                <th>Komponen</th>
                <th class="text-end">Aktual</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Kas Awal</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['opening_cash'] ?? 0))) ?></td></tr>
            <tr><td>Kas Bersih Operasi</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_operating'] ?? 0))) ?></td></tr>
            <tr><td>Kas Bersih Investasi</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_investing'] ?? 0))) ?></td></tr>
            <tr><td>Kas Bersih Pendanaan</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_financing'] ?? 0))) ?></td></tr>
            <tr><td>Kenaikan / Penurunan Kas</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['net_cash_change'] ?? 0))) ?></td></tr>
            <tr><td>Kas Akhir</td><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['closing_cash'] ?? 0))) ?></td></tr>
        </tbody>
    </table>

    <?php foreach ($sections as $section): ?>
        <div class="print-section-title"><?= e((string) $section['title']) ?></div>
        <table class="table table-bordered print-detail-table mb-3">
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
                <?php if ($section['rows'] === []): ?>
                    <tr><td colspan="7" class="text-center">Tidak ada mutasi kas pada bagian ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($section['rows'] as $row): ?>
                        <tr>
                            <td><?= e(format_id_date((string) ($row['journal_date'] ?? ''))) ?></td>
                            <td><?= e((string) ($row['journal_no'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['label'] ?? $row['description'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['unit_label'] ?? '-')) ?></td>
                            <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['cash_in'] ?? 0))) ?></td>
                            <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['cash_out'] ?? 0))) ?></td>
                            <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['net_amount'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <table class="table table-bordered print-summary-table mb-0">
        <tbody>
            <tr><th>Saldo kas akhir menurut arus kas</th><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['closing_cash'] ?? 0))) ?></td></tr>
            <tr><th>Saldo kas atau bank riil</th><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['actual_closing_cash'] ?? $report['closing_cash'] ?? 0))) ?></td></tr>
            <tr><th>Selisih rekonsiliasi</th><td class="text-end nowrap"><?= e(ledger_currency_print($difference)) ?></td></tr>
        </tbody>
    </table>

    <?php render_print_signature($profile); ?>
</div>
<style>
.cashflow-analytics-print { font-size: 12px; }
.report-heading-main { font-size: 18px; font-weight: 700; letter-spacing: .04em; }
.report-heading-meta { font-size: 12px; color: #334155; }
.print-alert-box {
    border: 1px solid #c2410c;
    background: #fff7ed;
    color: #9a3412;
    padding: 8px 10px;
    font-size: 11px;
}
.print-section-title {
    margin: 12px 0 6px;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
}
.print-variance-table,
.print-detail-table,
.print-summary-table { font-size: 12px; }
.print-variance-table th,
.print-variance-table td,
.print-detail-table th,
.print-detail-table td,
.print-summary-table th,
.print-summary-table td {
    border: 1px solid #334155 !important;
    padding: 6px 8px !important;
    background: #fff;
}
.print-variance-table thead th,
.print-detail-table thead th { background: #eef2f7; font-weight: 700; }
.print-summary-table th { width: 36%; background: #f8fafc; }
.nowrap { white-space: nowrap; }
</style>
<script>window.print();</script>
