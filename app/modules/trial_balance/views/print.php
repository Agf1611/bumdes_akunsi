<?php declare(strict_types=1);

$groupLabels = [
    'ASSET' => 'Aktiva',
    'LIABILITY' => 'Kewajiban',
    'EQUITY' => 'Modal',
    'REVENUE' => 'Pendapatan',
    'EXPENSE' => 'Beban',
];
$groupedRows = [];
foreach ($rows as $row) {
    $type = strtoupper((string) ($row['account_type'] ?? ''));
    $groupedRows[$type][] = $row;
}
$groupOrder = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];
$orderedGroupKeys = array_values(array_unique(array_merge(
    array_values(array_filter($groupOrder, static fn (string $key): bool => isset($groupedRows[$key]))),
    array_keys($groupedRows)
)));
?>
<section class="print-sheet classic-report trial-balance-report trial-balance-simple-print">
    <?php render_print_header($profile, $reportTitle ?? 'Neraca Saldo', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>
    <div class="report-note text-center mb-2">Disajikan dalam rupiah penuh</div>
    <?php report_print_meta_table([
        ['label' => 'Periode Laporan', 'value' => (string) ($periodLabel ?? '-')],
        ['label' => 'Unit Usaha', 'value' => (string) ($selectedUnitLabel ?? 'Semua Unit')],
        ['label' => 'Jumlah Akun', 'value' => (string) count($rows)],
        ['label' => 'Total Debit', 'value' => ledger_currency_print((float) ($summary['ending_debit_total'] ?? 0))],
        ['label' => 'Total Kredit', 'value' => ledger_currency_print((float) ($summary['ending_credit_total'] ?? 0))],
    ]); ?>
    <table class="table table-bordered print-table trial-balance-simple-table">
        <thead>
        <tr>
            <th>Nama Akun</th>
            <th style="width:24%" class="text-end">Debit (Rp)</th>
            <th style="width:24%" class="text-end">Kredit (Rp)</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="3" class="text-center text-muted">Tidak ada data neraca saldo untuk filter yang dipilih.</td></tr>
        <?php else: foreach ($orderedGroupKeys as $groupKey): ?>
            <?php $items = $groupedRows[$groupKey] ?? []; ?>
            <?php if ($items === []) { continue; } ?>
            <tr class="trial-balance-group-row">
                <td colspan="3"><?= e($groupLabels[$groupKey] ?? report_account_type_label($groupKey)) ?></td>
            </tr>
            <?php foreach ($items as $row): ?>
                <?php
                    $closingSide = (string) ($row['closing_side'] ?? '-');
                    $closingBalance = (float) ($row['closing_balance'] ?? 0);
                ?>
                <tr>
                    <td><?= e((string) $row['account_name']) ?></td>
                    <td class="text-end nowrap"><?= $closingSide === 'D' && abs($closingBalance) > 0.004 ? e(ledger_currency_print($closingBalance)) : '' ?></td>
                    <td class="text-end nowrap"><?= $closingSide === 'K' && abs($closingBalance) > 0.004 ? e(ledger_currency_print($closingBalance)) : '' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th class="text-center">TOTAL</th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) ($summary['ending_debit_total'] ?? 0))) ?></th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) ($summary['ending_credit_total'] ?? 0))) ?></th>
        </tr>
        </tfoot>
    </table>
    <?php render_print_signature($profile); ?>
    <script>window.print();</script>
</section>

<style>
.trial-balance-simple-print .report-letterhead { margin-bottom: 5px !important; }
.trial-balance-simple-table {
    border-collapse: collapse !important;
    table-layout: fixed !important;
    font-size: 11px !important;
}
.trial-balance-simple-table th,
.trial-balance-simple-table td {
    border: 1px solid #111 !important;
    padding: 4px 6px !important;
    color: #000 !important;
    background: #fff !important;
}
.trial-balance-simple-table thead th {
    background: #0000ff !important;
    color: #fff !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    text-align: center !important;
}
.trial-balance-simple-table .trial-balance-group-row td {
    background: #fff !important;
    color: #000 !important;
    font-weight: 800 !important;
}
.trial-balance-simple-table tfoot th {
    background: #d9eaf7 !important;
    color: #000 !important;
    font-weight: 800 !important;
}
@media print {
    .trial-balance-simple-table th,
    .trial-balance-simple-table td {
        padding: 3px 5px !important;
        font-size: 10px !important;
        line-height: 1.14 !important;
    }
    .trial-balance-simple-table thead th {
        font-size: 11px !important;
    }
}
</style>
