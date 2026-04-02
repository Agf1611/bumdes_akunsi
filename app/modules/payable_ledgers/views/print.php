<?php declare(strict_types=1); ?>
<?php
$__periodLabel = function_exists('report_period_label') ? report_period_label($filters ?? [], $selectedPeriod ?? null) : ((($periodLabel ?? '') !== '') ? (string) $periodLabel : '-');
$__headline = (function_exists('report_print_period_headline') ? report_print_period_headline($filters ?? [], $selectedPeriod ?? null) : $__periodLabel);
?>
<div class="print-sheet">
    <?php render_print_header($profile ?? app_profile(), $reportTitle ?? 'Buku Pembantu Utang', $periodLabel ?? $__periodLabel, $selectedUnitLabel ?? 'Semua Unit'); ?>
    <?php if (function_exists('report_print_heading_block')) { report_print_heading_block($__headline); } else { echo '<div class="mb-3 text-secondary">' . e((string) $__headline) . '</div>'; } ?>
    <?php if (function_exists('report_print_meta_table')) { report_print_meta_table([
        ['label' => 'Mitra/Kreditur', 'value' => !empty($selectedPartner) ? trim((string) (($selectedPartner['partner_code'] ?? '') . ' - ' . ($selectedPartner['partner_name'] ?? '')), ' -') : 'Semua mitra'],
        ['label' => 'Akun Utang', 'value' => !empty($selectedAccount) ? ((string) ($selectedAccount['account_code'] ?? '') . ' - ' . (string) ($selectedAccount['account_name'] ?? '')) : 'Semua akun utang'],
    ]); } else { echo '<div class="mb-3 text-secondary"></div>'; } ?>
    <table class="table table-bordered print-table report-table-compact">
        <thead>
        <tr>
            <th>Tanggal</th>
            <th>No. Jurnal</th>
            <th>Mitra</th>
            <th>Akun</th>
            <th>Unit</th>
            <th>Keterangan</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Kredit</th>
            <th class="text-end">Saldo</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan="8"><strong>Saldo Awal</strong></td>
            <td class="text-end"><strong><?= e(ledger_currency((float) ($summary['opening_balance'] ?? 0))) ?></strong></td>
        </tr>
        <?php if (($rows ?? []) === []): ?>
            <tr><td colspan="9" class="text-center text-muted">Belum ada data mutasi utang untuk filter yang dipilih.</td></tr>
        <?php else: foreach (($rows ?? []) as $row): ?>
            <tr>
                <td><?= e(format_id_date((string) ($row['journal_date'] ?? ''))) ?></td>
                <td><?= e((string) ($row['journal_no'] ?? '')) ?></td>
                <td><?= e((string) ($row['partner_label'] ?? '-')) ?></td>
                <td><?= e((string) ($row['account_label'] ?? '-')) ?></td>
                <td><?= e((string) ($row['unit_label'] ?? '-')) ?></td>
                <td><?= e((string) ($row['description'] ?? '')) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['debit'] ?? 0))) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['credit'] ?? 0))) ?></td>
                <td class="text-end"><?= e(ledger_currency((float) ($row['balance'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="6" class="text-end">Total Mutasi</th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['total_debit'] ?? 0))) ?></th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['total_credit'] ?? 0))) ?></th>
            <th class="text-end"><?= e(ledger_currency((float) ($summary['closing_balance'] ?? 0))) ?></th>
        </tr>
        </tfoot>
    </table>
    <?php render_print_signature($profile ?? app_profile()); ?>
</div>
<script>window.print();</script>
