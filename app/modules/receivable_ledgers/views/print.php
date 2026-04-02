<?php declare(strict_types=1); ?>
<?php
$__periodLabel = function_exists('report_period_label') ? report_period_label($filters ?? [], $selectedPeriod ?? null) : ((($periodLabel ?? '') !== '') ? (string) $periodLabel : '-');
$__headline = (function_exists('report_print_period_headline') ? report_print_period_headline($filters ?? [], $selectedPeriod ?? null) : $__periodLabel);
?>
<div class="print-sheet">
    <?php render_print_header($profile ?? app_profile(), $reportTitle ?? 'Buku Pembantu Piutang', $periodLabel ?? $__periodLabel, $selectedUnitLabel ?? 'Semua Unit'); ?>
    <?php if (function_exists('report_print_heading_block')) { report_print_heading_block($__headline); } else { echo '<div class="mb-3 text-secondary">' . e((string) $__headline) . '</div>'; } ?>
    <?php if (function_exists('report_print_meta_table')) { report_print_meta_table([
        ['label' => 'Mitra/Debitur', 'value' => !empty($selectedPartner) ? trim((string) (($selectedPartner['partner_code'] ?? '') . ' - ' . ($selectedPartner['partner_name'] ?? '')), ' -') : 'Semua mitra'],
        ['label' => 'Akun Piutang', 'value' => !empty($selectedAccount) ? ((string) ($selectedAccount['account_code'] ?? '') . ' - ' . (string) ($selectedAccount['account_name'] ?? '')) : 'Semua akun piutang'],
    ]); } else { echo '<div class="mb-3 text-secondary"></div>'; } ?>

    <table class="report-table report-table--compact">
        <thead>
        <tr>
            <th style="width: 9%;">Tanggal</th>
            <th style="width: 12%;">No. Jurnal</th>
            <th style="width: 14%;">Mitra</th>
            <th style="width: 16%;">Akun</th>
            <th>Uraian</th>
            <th style="width: 11%;">Unit</th>
            <th style="width: 7%;">Tag</th>
            <th class="text-end" style="width: 9%;">Debit</th>
            <th class="text-end" style="width: 9%;">Kredit</th>
            <th class="text-end" style="width: 10%;">Saldo</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan="9"><strong>Saldo Awal</strong></td>
            <td class="text-end"><strong><?= e(ledger_currency((float) ($summary['opening_balance'] ?? 0))) ?></strong></td>
        </tr>
        <?php if (($rows ?? []) === []): ?>
            <tr><td colspan="10" class="text-center text-secondary">Belum ada mutasi piutang untuk filter yang dipilih.</td></tr>
        <?php else: foreach (($rows ?? []) as $row): ?>
            <tr>
                <td><?= e(format_id_date((string) ($row['journal_date'] ?? ''))) ?></td>
                <td><?= e((string) ($row['journal_no'] ?? '')) ?></td>
                <td><?= e((string) ($row['partner_label'] ?? '-')) ?></td>
                <td><?= e(trim((string) ($row['account_code'] ?? '') . ' - ' . (string) ($row['account_name'] ?? ''))) ?></td>
                <td><?= e((string) ($row['description'] ?? '')) ?></td>
                <td><?= e((string) ($row['unit_label'] ?? '-')) ?></td>
                <td><?= e((string) (((($row['entry_tag'] ?? '') !== '') ? strtoupper((string) $row['entry_tag']) : '-'))) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['debit'] ?? 0))) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['credit'] ?? 0))) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['balance'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="7" class="text-end">Total Mutasi / Saldo Akhir</th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['total_debit'] ?? 0))) ?></th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['total_credit'] ?? 0))) ?></th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['closing_balance'] ?? 0))) ?></th>
        </tr>
        </tfoot>
    </table>

    <?php render_print_signature($profile ?? app_profile()); ?>
</div>
<script>window.print();</script>
