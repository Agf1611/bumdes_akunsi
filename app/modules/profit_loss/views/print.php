<?php declare(strict_types=1); ?>
<?php
$modeLabel = (string) ($report['mode_label'] ?? 'Bulanan / Periode');
$currentColumnLabel = (string) ($report['current_column_label'] ?? 'Periode');
$cumulativeColumnLabel = (string) ($report['cumulative_column_label'] ?? 'Akumulasi');
$periodNet = (float) ($report['net_income'] ?? 0);
$cumulativeNet = (float) ($report['cumulative_net_income'] ?? 0);
?>
<div class="print-sheet classic-report profit-loss-report-formal">
    <?php render_print_header($profile, $title ?? 'Laporan Laba Rugi', report_period_label($filters, $selectedPeriod), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="report-heading-block text-center mb-3">
        <div class="report-heading-main">LAPORAN LABA RUGI</div>
        <div class="report-heading-meta">Mode laporan: <?= e($modeLabel) ?></div>
        <div class="report-heading-meta">Disajikan dalam rupiah</div>
    </div>

    <table class="table table-bordered summary-grid mb-3">
        <tr>
            <th><?= e((string) ($report['current_label'] ?? 'Laba Periode')) ?></th>
            <td class="text-end nowrap <?= $periodNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($periodNet)) ?></td>
            <th>Akumulasi s.d. Tanggal Akhir</th>
            <td class="text-end nowrap <?= $cumulativeNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($cumulativeNet)) ?></td>
        </tr>
    </table>

    <table class="table table-bordered print-table report-table-compact formal-pl-table mb-0">
        <thead>
        <tr>
            <th style="width:9%;" class="text-center">No</th>
            <th>Uraian</th>
            <th style="width:22%;" class="text-center"><?= e($currentColumnLabel) ?></th>
            <th style="width:22%;" class="text-center"><?= e($cumulativeColumnLabel) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($statement_rows === []): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data laba rugi untuk filter yang dipilih.</td></tr>
        <?php else: ?>
            <?php foreach ($statement_rows as $row): ?>
                <?php
                    $rowType = (string) $row['row_type'];
                    $rowClass = match ($rowType) {
                        'section' => 'pl-section',
                        'category' => 'pl-category',
                        'subtotal', 'section_total' => 'pl-subtotal',
                        default => '',
                    };
                    $labelClass = match ($rowType) {
                        'section' => 'section-label',
                        'category' => 'category-label',
                        'subtotal', 'section_total' => 'subtotal-label',
                        default => '',
                    };
                ?>
                <tr class="<?= e($rowClass) ?>">
                    <td class="text-center"><?= e((string) $row['order']) ?></td>
                    <td class="<?= e($labelClass) ?>"><?= e((string) $row['label']) ?></td>
                    <td class="text-end nowrap <?= ($rowType === 'subtotal' || $rowType === 'section_total') ? 'fw-bold' : '' ?>"><?= $row['current_amount'] === null ? '' : e(profit_loss_currency_print((float) $row['current_amount'])) ?></td>
                    <td class="text-end nowrap <?= ($rowType === 'subtotal' || $rowType === 'section_total') ? 'fw-bold' : '' ?>"><?= $row['cumulative_amount'] === null ? '' : e(profit_loss_currency_print((float) $row['cumulative_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="pl-grand-total">
                <td></td>
                <td class="subtotal-label"><?= e(strtoupper(profit_loss_display_label())) ?></td>
                <td class="text-end nowrap fw-bold <?= $periodNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($periodNet)) ?></td>
                <td class="text-end nowrap fw-bold <?= $cumulativeNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($cumulativeNet)) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php render_print_signature($profile); ?>
</div>

<style>
.report-heading-main { font-size: 18px; font-weight: 700; letter-spacing: .4px; }
.report-heading-meta { font-size: 12px; color: #334155; }
.summary-grid, .formal-pl-table { font-size: 12px; }
.summary-grid th, .summary-grid td,
.formal-pl-table th, .formal-pl-table td {
    border: 1px solid #334155 !important;
    padding: 6px 8px !important;
    background: #fff;
}
.summary-grid th { width: 28%; background: #f8fafc; }
.formal-pl-table thead th {
    background: #eef2f7;
    text-align: center;
    font-weight: 700;
}
.formal-pl-table .pl-section td { background: #fff7f5; }
.formal-pl-table .pl-category td { background: #f8fbff; }
.formal-pl-table .pl-subtotal td,
.formal-pl-table .pl-grand-total td { background: #f5f7fb; }
.section-label { color: #a61b1b; font-weight: 700; text-transform: uppercase; }
.category-label { color: #0f4c81; font-weight: 700; }
.subtotal-label { font-weight: 700; }
.positive { color: #166534; }
.negative { color: #b91c1c; }
.nowrap { white-space: nowrap; }
</style>
<script>window.print();</script>
