<?php declare(strict_types=1); ?>
<?php
$periodLabel = report_period_label($filters, $selectedPeriod);
$unitLabel = (string) ($selectedUnitLabel ?? 'Semua Unit');
$sections = (array) ($report['sections'] ?? []);
$formatAmount = static function (float $amount): string {
    $formatted = ledger_currency_print(abs($amount));
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
<div class="print-sheet classic-report cash-flow-report cash-flow-statement-print">
    <?php render_print_header($profile, 'Laporan Arus Kas', $periodLabel, $unitLabel); ?>

    <?php if (($warnings ?? []) !== []): ?>
        <div class="print-alert-box mb-3">
            <?php foreach ((array) $warnings as $warning): ?>
                <div>- <?= e((string) $warning) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="print-table cash-flow-statement-table mb-0">
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
                <tr class="section-row">
                    <td colspan="2"><?= e((string) $config['title']) ?></td>
                </tr>
                <?php if ($inRows === [] && $outRows === []): ?>
                    <tr>
                        <td>Tidak ada mutasi kas</td>
                        <td class="text-end nowrap"><?= e($formatAmount(0.0)) ?></td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($inRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['label'] ?? 'Penerimaan kas')) ?></td>
                        <td class="text-end nowrap"><?= e($formatAmount((float) ($row['amount'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($outRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['label'] ?? 'Pengeluaran kas')) ?></td>
                        <td class="text-end nowrap"><?= e($formatAmount(-1 * (float) ($row['amount'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="subtotal-row">
                    <td><?= e((string) $config['subtotal']) ?></td>
                    <td class="text-end nowrap"><?= e($formatAmount($net)) ?></td>
                </tr>
                <tr class="spacer-row"><td colspan="2"></td></tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>Kenaikan (penurunan) kas</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['net_cash_change'] ?? 0))) ?></td>
            </tr>
            <tr class="total-row">
                <td>Saldo kas awal</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['opening_cash'] ?? 0))) ?></td>
            </tr>
            <tr class="grand-total-row">
                <td>Saldo kas akhir</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['closing_cash'] ?? 0))) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="print-footnote">
        Catatan: jurnal saldo awal pada tanggal mulai periode dihitung sebagai saldo kas awal, bukan sebagai mutasi arus kas berjalan.
    </div>

    <?php render_print_signature($profile); ?>
</div>
<style>
.cash-flow-statement-print .report-letterhead { margin-bottom: 14px !important; }
.cash-flow-statement-table th {
    background: #10b8df !important;
    color: #fff !important;
    border: 1px solid #10b8df !important;
    font-weight: 800 !important;
}
.cash-flow-statement-table td {
    border: 0 !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 6px 8px !important;
}
.cash-flow-statement-table .section-row td {
    background: #f5f5f5 !important;
    border-bottom: 0 !important;
    font-weight: 800 !important;
    padding-top: 9px !important;
}
.cash-flow-statement-table .subtotal-row td {
    border-top: 1px solid #6b7280 !important;
    font-weight: 800 !important;
}
.cash-flow-statement-table .spacer-row td {
    border: 0 !important;
    height: 12px !important;
    padding: 0 !important;
}
.cash-flow-statement-table .total-row td,
.cash-flow-statement-table .grand-total-row td {
    background: #f5f5f5 !important;
    border-bottom: 1px solid #cbd5e1 !important;
    font-weight: 800 !important;
}
.cash-flow-statement-table .grand-total-row td {
    border-bottom: 2px solid #94a3b8 !important;
}
</style>
<script>window.print();</script>
