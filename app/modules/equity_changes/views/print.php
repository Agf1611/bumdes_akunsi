<?php declare(strict_types=1); ?>
<?php
$periodLabel = report_period_label($filters, $selectedPeriod);
$unitLabel = (string) ($selectedUnitLabel ?? 'Semua Unit');
$rows = (array) ($report['rows'] ?? []);
$formatAmount = static fn (float $amount): string => ledger_currency_print($amount);
$rowNumber = 1;
?>
<div class="print-sheet classic-report equity-changes-report kemendesa-statement-print">
    <?php render_print_header($profile, 'Laporan Perubahan Ekuitas', $periodLabel, $unitLabel); ?>

    <?php report_print_heading_block('LAPORAN PERUBAHAN EKUITAS', '(dalam rupiah penuh)'); ?>

    <table class="print-table kemendesa-statement-table mb-2">
        <thead>
            <tr>
                <th style="width:10%;">No.</th>
                <th>Uraian</th>
                <th style="width:28%;" class="text-end">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row">
                <td colspan="3">EKUITAS AWAL DAN MUTASI LANGSUNG</td>
            </tr>
            <tr>
                <td class="text-center"><?= e((string) $rowNumber++) ?></td>
                <td>Saldo ekuitas awal periode</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['total_opening_equity'] ?? 0))) ?></td>
            </tr>
            <tr>
                <td class="text-center"><?= e((string) $rowNumber++) ?></td>
                <td>Mutasi ekuitas langsung selama periode</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['total_movement_equity'] ?? 0))) ?></td>
            </tr>

            <tr class="section-row">
                <td colspan="3">RINCIAN AKUN EKUITAS</td>
            </tr>
            <?php if ($rows === []): ?>
                <tr>
                    <td class="text-center"><?= e((string) $rowNumber++) ?></td>
                    <td>Belum ada rincian akun ekuitas pada filter ini</td>
                    <td class="text-end nowrap">0</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="text-center"><?= e((string) $rowNumber++) ?></td>
                        <td>
                            <?= e((string) ($row['account_name'] ?? '-')) ?>
                            <?php if (trim((string) ($row['account_code'] ?? '')) !== ''): ?>
                                <span class="report-muted-inline">(<?= e((string) $row['account_code']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end nowrap"><?= e($formatAmount((float) ($row['closing_amount'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="subtotal-row">
                <td colspan="2">Total ekuitas langsung akhir periode</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['total_closing_equity'] ?? 0))) ?></td>
            </tr>

            <tr class="section-row">
                <td colspan="3">SALDO LABA / RUGI BERJALAN</td>
            </tr>
            <tr>
                <td class="text-center"><?= e((string) $rowNumber++) ?></td>
                <td>Laba / rugi bersih periode berjalan</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['net_income'] ?? 0))) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="2">EKUITAS AKHIR</td>
                <td class="text-end nowrap"><?= e($formatAmount((float) ($report['final_equity_total'] ?? 0))) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="print-footnote">
        Laporan ini menyajikan perubahan ekuitas untuk periode <?= e($periodLabel) ?> pada <?= e($unitLabel) ?>, termasuk mutasi ekuitas langsung dan pengaruh laba/rugi berjalan.
    </div>

    <?php render_print_signature($profile); ?>
</div>
<script>window.print();</script>
