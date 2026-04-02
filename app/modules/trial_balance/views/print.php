<?php declare(strict_types=1); ?>
<section class="print-sheet classic-report landscape-report trial-balance-report">
    <?php render_print_header($profile, $reportTitle ?? 'Neraca Saldo', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>
    <div class="report-note text-center mb-2">Disajikan dalam Rupiah penuh</div>
    <table class="table table-bordered print-table report-table-compact">
        <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:12%">Kode Akun</th>
            <th>Nama Akun</th>
            <th style="width:10%">Tipe</th>
            <th style="width:12%" class="text-end">Debit</th>
            <th style="width:12%" class="text-end">Kredit</th>
            <th style="width:12%" class="text-end">Saldo Debit</th>
            <th style="width:12%" class="text-end">Saldo Kredit</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="text-center text-muted">Tidak ada data neraca saldo untuk filter yang dipilih.</td></tr>
        <?php else: foreach ($rows as $index => $row): ?>
            <?php
                $closingDebit = ((string) ($row['closing_side'] ?? 'D')) === 'D' ? (float) $row['closing_balance'] : 0.0;
                $closingCredit = ((string) ($row['closing_side'] ?? 'D')) === 'K' ? (float) $row['closing_balance'] : 0.0;
            ?>
            <tr>
                <td class="text-center"><?= e((string) ($index + 1)) ?></td>
                <td><?= e((string) $row['account_code']) ?></td>
                <td><?= e((string) $row['account_name']) ?></td>
                <td><?= e(report_account_type_label((string) $row['account_type'])) ?></td>
                <td class="text-end nowrap"><?= e(ledger_currency_print((float) $row['period_debit'])) ?></td>
                <td class="text-end nowrap"><?= e(ledger_currency_print((float) $row['period_credit'])) ?></td>
                <td class="text-end nowrap"><?= $closingDebit !== 0.0 ? e(ledger_currency_print($closingDebit)) : '-' ?></td>
                <td class="text-end nowrap"><?= $closingCredit !== 0.0 ? e(ledger_currency_print($closingCredit)) : '-' ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) $summary['total_debit'])) ?></th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) $summary['total_credit'])) ?></th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) $summary['ending_debit_total'])) ?></th>
            <th class="text-end nowrap"><?= e(ledger_currency_print((float) $summary['ending_credit_total'])) ?></th>
        </tr>
        </tfoot>
    </table>
    <?php render_print_signature($profile); ?>
    <script>window.print();</script>
</section>
