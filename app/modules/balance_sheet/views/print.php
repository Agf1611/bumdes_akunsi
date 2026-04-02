<?php declare(strict_types=1); ?>
<?php
$currentAsOf = trim((string) ($filters['date_to'] ?? ''));
$comparisonAsOf = trim((string) ($report['comparison_as_of_date'] ?? ''));
$comparisonEnabled = !empty($report['comparison_enabled']);
$currentColumnLabel = $currentAsOf !== '' ? 'Per ' . format_id_date($currentAsOf) : 'Per Tanggal';
$comparisonColumnLabel = $comparisonEnabled && $comparisonAsOf !== '' ? 'Per ' . format_id_date($comparisonAsOf) : 'Pembanding';
?>
<div class="print-sheet classic-report balance-sheet-report-formal">
    <?php render_print_header($profile, $title ?? 'Neraca', report_period_label($filters, $selectedPeriod, true), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="report-heading-block text-center mb-3">
        <div class="report-heading-main">NERACA</div>
        <div class="report-heading-meta">Laporan posisi keuangan per <?= e(format_id_date($currentAsOf !== '' ? $currentAsOf : date('Y-m-d'))) ?></div>
        <div class="report-heading-meta">Disajikan dalam rupiah</div>
    </div>

    <table class="table table-bordered summary-grid mb-3">
        <tr>
            <th>Total Aset</th>
            <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_assets'] ?? 0))) ?></td>
            <th>Total Kewajiban dan Ekuitas</th>
            <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_liabilities_equity'] ?? 0))) ?></td>
        </tr>
    </table>

    <table class="table table-bordered align-middle formal-balance-table mb-0">
        <thead>
        <tr>
            <th style="width:14%;">Kode</th>
            <th>Uraian</th>
            <th style="width:22%;" class="text-end"><?= e($currentColumnLabel) ?></th>
            <?php if ($comparisonEnabled): ?><th style="width:22%;" class="text-end"><?= e($comparisonColumnLabel) ?></th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
            <tr class="section-row"><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>">ASET</td></tr>
            <?php if (($report['asset_rows'] ?? []) === []): ?>
                <tr><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>" class="text-center">Tidak ada akun aset untuk filter yang dipilih.</td></tr>
            <?php else: foreach (($report['asset_rows'] ?? []) as $row): ?>
                <tr>
                    <td><?= e((string) ($row['account_code'] ?? '')) ?></td>
                    <td><?= e((string) ($row['account_name'] ?? '')) ?></td>
                    <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['amount'] ?? 0))) ?></td>
                    <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="subtotal-row">
                <td colspan="2">TOTAL ASET</td>
                <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_assets'] ?? 0))) ?></td>
                <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['comparison_total_assets'] ?? 0))) ?></td><?php endif; ?>
            </tr>

            <tr class="section-row"><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>">KEWAJIBAN</td></tr>
            <?php if (($report['liability_rows'] ?? []) === []): ?>
                <tr><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>" class="text-center">Tidak ada akun kewajiban untuk filter yang dipilih.</td></tr>
            <?php else: foreach (($report['liability_rows'] ?? []) as $row): ?>
                <tr>
                    <td><?= e((string) ($row['account_code'] ?? '')) ?></td>
                    <td><?= e((string) ($row['account_name'] ?? '')) ?></td>
                    <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['amount'] ?? 0))) ?></td>
                    <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="subtotal-row">
                <td colspan="2">TOTAL KEWAJIBAN</td>
                <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_liabilities'] ?? 0))) ?></td>
                <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['comparison_total_liabilities'] ?? 0))) ?></td><?php endif; ?>
            </tr>

            <tr class="section-row"><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>">EKUITAS</td></tr>
            <?php if (($report['equity_rows'] ?? []) === []): ?>
                <tr><td colspan="<?= $comparisonEnabled ? '4' : '3' ?>" class="text-center">Tidak ada akun ekuitas untuk filter yang dipilih.</td></tr>
            <?php else: foreach (($report['equity_rows'] ?? []) as $row): ?>
                <tr>
                    <td><?= e((string) ($row['account_code'] ?? '')) ?></td>
                    <td><?= e((string) ($row['account_name'] ?? '')) ?></td>
                    <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['amount'] ?? 0))) ?></td>
                    <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            <?php if (abs((float) ($report['current_earnings'] ?? 0)) > 0.004 || abs((float) ($report['comparison_current_earnings'] ?? 0)) > 0.004): ?>
                <tr>
                    <td></td>
                    <td>Laba / Rugi Berjalan</td>
                    <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['current_earnings'] ?? 0))) ?></td>
                    <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['comparison_current_earnings'] ?? 0))) ?></td><?php endif; ?>
                </tr>
            <?php endif; ?>
            <tr class="subtotal-row">
                <td colspan="2">TOTAL EKUITAS</td>
                <td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_equity'] ?? 0))) ?></td>
                <?php if ($comparisonEnabled): ?><td class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['comparison_total_equity'] ?? 0))) ?></td><?php endif; ?>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <th colspan="2">TOTAL KEWAJIBAN DAN EKUITAS</th>
                <th class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['total_liabilities_equity'] ?? 0))) ?></th>
                <?php if ($comparisonEnabled): ?><th class="text-end nowrap"><?= e(ledger_currency_print((float) ($report['comparison_total_liabilities_equity'] ?? 0))) ?></th><?php endif; ?>
            </tr>
        </tfoot>
    </table>

    <?php render_print_signature($profile); ?>
</div>
<style>
.report-heading-main { font-size: 18px; font-weight: 700; letter-spacing: .4px; }
.report-heading-meta { font-size: 12px; color: #334155; }
.summary-grid, .formal-balance-table { font-size: 12px; }
.summary-grid th, .summary-grid td,
.formal-balance-table th, .formal-balance-table td {
    border: 1px solid #334155 !important;
    padding: 6px 8px !important;
    background: #fff;
}
.summary-grid th { width: 28%; background: #f8fafc; }
.formal-balance-table thead th { background: #eef2f7; font-weight: 700; }
.formal-balance-table .section-row td { background: #f8fafc; font-weight: 700; text-transform: uppercase; }
.formal-balance-table .subtotal-row td, .formal-balance-table .grand-total-row th { background: #f5f7fb; font-weight: 700; }
.nowrap { white-space: nowrap; }
</style>
<script>window.print();</script>
