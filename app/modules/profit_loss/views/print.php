<?php declare(strict_types=1); ?>
<?php
$modeLabel = (string) ($report['mode_label'] ?? 'Bulanan / Periode');
$currentColumnLabel = (string) ($report['current_column_label'] ?? 'Periode');
$accumulatedColumnLabel = (string) ($report['comparison_column_label'] ?? 'Akumulasi Tahun Berjalan');
$periodNet = (float) ($report['net_income'] ?? 0);
$accumulatedNet = (float) ($report['comparison_net_income'] ?? 0);
?>
<div class="print-sheet classic-report profit-loss-report-formal kemendesa-statement-print">
    <?php render_print_header($profile, $title ?? 'Laporan Laba Rugi', report_period_label($filters, $selectedPeriod), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <?php report_print_heading_block('LAPORAN LABA RUGI', '(dalam rupiah penuh)'); ?>

    <?php report_print_meta_table([
        ['label' => 'Periode Laporan', 'value' => report_period_label($filters, $selectedPeriod)],
        ['label' => 'Unit Usaha', 'value' => (string) ($selectedUnitLabel ?? 'Semua Unit')],
        ['label' => 'Kolom Bulan / Periode', 'value' => $currentColumnLabel],
        ['label' => 'Kolom Akumulasi', 'value' => $accumulatedColumnLabel],
    ]); ?>

    <div class="formal-table-title">Rincian Pendapatan dan Beban</div>

    <table class="table table-bordered print-table report-table-compact formal-pl-table kemendesa-statement-table mb-0">
        <thead>
        <tr>
            <th style="width:7%;" class="text-center">No</th>
            <th>Uraian</th>
            <th style="width:22%;" class="text-center"><?= e($currentColumnLabel) ?></th>
            <th style="width:24%;" class="text-center"><?= e($accumulatedColumnLabel) ?></th>
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
                    <td class="text-end nowrap <?= ($rowType === 'subtotal' || $rowType === 'section_total') ? 'fw-bold' : '' ?>"><?= $row['comparison_amount'] === null ? '' : e(profit_loss_currency_print((float) $row['comparison_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="pl-grand-total">
                <td colspan="2" class="subtotal-label text-end"><?= e(strtoupper(profit_loss_display_label())) ?></td>
                <td class="text-end nowrap fw-bold <?= $periodNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($periodNet)) ?></td>
                <td class="text-end nowrap fw-bold <?= $accumulatedNet >= 0 ? 'positive' : 'negative' ?>"><?= e(profit_loss_currency_print($accumulatedNet)) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="print-footnote">Catatan: nilai disajikan sederhana agar fokus pada hasil usaha periode berjalan.</div>

    <?php render_print_signature($profile); ?>
</div>

<style>
.formal-pl-table { font-size: 11px; }
.formal-pl-table th, .formal-pl-table td {
    border: 1px solid #334155 !important;
    padding: 6px 8px !important;
    background: #fff;
}
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
.print-footnote { margin-top: 10px; font-size: 11px; color: #475569; }
</style>
<script>window.print();</script>
