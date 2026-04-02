<?php declare(strict_types=1); ?>
<style>
.ledger-book-print {
    max-width: none;
    color: #111;
    font-size: 11px;
    line-height: 1.3;
}
.ledger-book-print .report-letterhead { margin-bottom: 8px !important; }
.ledger-book-print .report-title { font-size: 15px !important; }
.ledger-book-print .report-subtitle,
.ledger-book-print .report-org-meta { font-size: 9.5px !important; }
.ledger-book-meta {
    margin: 6px 0 10px;
    font-size: 10px;
    color: #334155;
}
.ledger-book-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    page-break-inside: auto !important;
    break-inside: auto !important;
}
.ledger-book-table thead { display: table-header-group; }
.ledger-book-table tfoot { display: table-row-group; }
.ledger-book-table tr {
    page-break-inside: avoid;
    break-inside: avoid;
}
.ledger-book-table th,
.ledger-book-table td {
    border: 1px solid #94a3b8 !important;
    padding: 5px 6px !important;
    vertical-align: top;
    font-size: 10px;
    word-break: break-word;
    overflow-wrap: anywhere;
    background: #fff !important;
}
.ledger-book-table thead th {
    background: #eef2f7 !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.ledger-book-table tfoot th,
.ledger-book-table tfoot td {
    background: #f8fafc !important;
    font-weight: 700;
}
.ledger-book-table .text-end { text-align: right; }
.ledger-book-table .text-center { text-align: center; }
.ledger-book-table .opening-row td {
    background: #f8fafc !important;
    font-weight: 700;
}
@media print {
    .ledger-book-print .d-print-none,
    .ledger-book-print .btn,
    .ledger-book-print button { display: none !important; }
    .ledger-book-print .report-signature-block { page-break-inside: avoid; }
    .ledger-book-table,
    .ledger-book-table tbody,
    .ledger-book-table thead,
    .ledger-book-table tfoot {
        page-break-inside: auto !important;
        break-inside: auto !important;
    }
}
</style>
<section class="print-sheet ledger-book-print">
    <?php render_print_header($profile, $reportTitle ?? 'Buku Besar', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="ledger-book-meta"><strong>Akun:</strong> <?= e((string) $selectedAccount['account_code'] . ' - ' . $selectedAccount['account_name']) ?></div>

    <table class="ledger-book-table">
        <thead>
            <tr>
                <th style="width:12%;">Tanggal</th>
                <th style="width:14%;">No. Jurnal</th>
                <th style="width:18%;">Unit</th>
                <th>Keterangan</th>
                <th style="width:12%;" class="text-end">Debit</th>
                <th style="width:12%;" class="text-end">Kredit</th>
                <th style="width:14%;" class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening-row">
                <td colspan="6">Saldo Awal</td>
                <td class="text-end"><?= e(ledger_currency((float) $summary['opening_balance'])) ?></td>
            </tr>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada mutasi jurnal untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(format_id_date((string) $row['journal_date'])) ?></td>
                        <td><?= e((string) $row['journal_no']) ?></td>
                        <td><?= e((string) ($row['unit_label'] ?? '-')) ?></td>
                        <td><?= e((string) $row['description']) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['debit'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['credit'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['balance'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Total Mutasi</th>
                <th class="text-end"><?= e(ledger_currency((float) $summary['total_debit'])) ?></th>
                <th class="text-end"><?= e(ledger_currency((float) $summary['total_credit'])) ?></th>
                <th class="text-end"><?= e(ledger_currency((float) $summary['closing_balance'])) ?></th>
            </tr>
        </tfoot>
    </table>

    <?php render_print_signature($profile); ?>
    <div class="d-print-none mt-4"><button type="button" class="btn btn-primary" onclick="window.print()">Cetak</button></div>
</section>
