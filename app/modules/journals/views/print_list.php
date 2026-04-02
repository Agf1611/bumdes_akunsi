<?php declare(strict_types=1); ?>
<?php
$totalDebit = 0.0;
$totalCredit = 0.0;
$rowNo = 0;
?>
<style>
    .journal-book-sheet{
        color:#111;
        font-size:11px;
        line-height:1.2;
    }
    .journal-book-sheet .report-letterhead{
        margin-bottom:12px !important;
    }
    .journal-book-sheet .report-org-top{font-size:13px;}
    .journal-book-sheet .report-org-name{font-size:22px;}
    .journal-book-sheet .report-org-meta,
    .journal-book-sheet .report-subtitle{font-size:11px;}
    .journal-book-sheet .report-title{font-size:18px;}
    .journal-book-note{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        margin:0 0 8px;
        font-size:10px;
        color:#333;
    }
    .journal-book-table{
        width:100%;
        border-collapse:collapse;
        table-layout:fixed;
        color:#111;
    }
    .journal-book-table th,
    .journal-book-table td{
        border:1px solid #6d8fd6;
        padding:2px 6px;
        vertical-align:top;
        word-wrap:break-word;
    }
    .journal-book-table thead th{
        background:#10b34a;
        color:#fff;
        text-align:center;
        font-weight:700;
        font-size:10px;
    }
    .journal-book-table tbody td{
        font-size:10px;
    }
    .journal-book-table .text-end{text-align:right;}
    .journal-book-table .text-center{text-align:center;}
    .journal-book-table .group-start td{
        border-top:2px solid #5278c7;
    }
    .journal-book-table .muted-cell{
        color:#666;
    }
    .journal-book-table tfoot th{
        background:#eef4ff;
        color:#111;
        font-weight:700;
        border:1px solid #6d8fd6;
        padding:4px 6px;
    }
    .journal-book-signature{
        margin-top:18px;
    }
    .journal-book-footer{
        margin-top:10px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        font-size:10px;
        color:#111;
    }
    @page{
        size:A4 portrait;
        margin:10mm 8mm 10mm 8mm;
    }
    @media print{
        body{background:#fff !important;color:#111 !important;}
        .journal-book-sheet{font-size:10px;}
        .journal-book-table th,
        .journal-book-table td{
            padding:2px 5px;
        }
        .journal-book-note{font-size:9px;}
        .journal-book-signature{page-break-inside:avoid;}
    }
</style>

<div class="journal-book-sheet">
    <?php render_print_header($profile, $reportTitle ?? 'Buku Jurnal', $periodLabel ?? '-', $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="journal-book-note">
        <div>
            <strong>Jenis Cetak:</strong> Buku jurnal / daftar transaksi ringkas
        </div>
        <div>
            Dicetak: <?= e(date('d/m/Y H:i')) ?>
        </div>
    </div>

    <?php if (($journals ?? []) === []): ?>
        <div class="alert alert-warning">Tidak ada jurnal yang sesuai dengan filter cetak.</div>
    <?php else: ?>
        <table class="journal-book-table">
            <thead>
                <tr>
                    <th style="width:9%;">Tanggal</th>
                    <th style="width:15%;">Nomor Bukti</th>
                    <th style="width:31%;">Kode dan Nama Akun</th>
                    <th style="width:12%;">Sisi Kiri<br>(Debit)</th>
                    <th style="width:12%;">Sisi Kanan<br>(Kredit)</th>
                    <th style="width:21%;">Keterangan Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journals as $journal): ?>
                    <?php $isFirstLine = true; ?>
                    <?php foreach (($journal['details'] ?? []) as $detail): ?>
                        <?php
                        $rowNo++;
                        $debit = (float) ($detail['debit'] ?? 0);
                        $credit = (float) ($detail['credit'] ?? 0);
                        $totalDebit += $debit;
                        $totalCredit += $credit;
                        $transactionNote = trim((string) ($detail['line_description'] ?? ''));
                        if ($transactionNote === '') {
                            $transactionNote = (string) ($journal['description'] ?? '-');
                        }
                        $accountLabel = trim((string) ($detail['account_code'] ?? '') . ' ' . (string) ($detail['account_name'] ?? ''));
                        ?>
                        <tr class="<?= $isFirstLine ? 'group-start' : '' ?>">
                            <td class="text-center"><?= $isFirstLine ? e(format_id_date((string) $journal['journal_date'])) : '&nbsp;' ?></td>
                            <td class="text-center"><?= $isFirstLine ? e((string) $journal['journal_no']) : '&nbsp;' ?></td>
                            <td><?= e($accountLabel !== '' ? $accountLabel : '-') ?></td>
                            <td class="text-end"><?= $debit > 0 ? e(number_format($debit, 0, ',', '.')) : '' ?></td>
                            <td class="text-end"><?= $credit > 0 ? e(number_format($credit, 0, ',', '.')) : '' ?></td>
                            <td><?= e($transactionNote) ?></td>
                        </tr>
                        <?php $isFirstLine = false; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end"><?= e(number_format($totalDebit, 0, ',', '.')) ?></th>
                    <th class="text-end"><?= e(number_format($totalCredit, 0, ',', '.')) ?></th>
                    <th>&nbsp;</th>
                </tr>
            </tfoot>
        </table>

        <div class="journal-book-signature">
            <?php render_print_signature($profile); ?>
        </div>
    <?php endif; ?>

    <div class="journal-book-footer">
        <div>printed: <?= e(date('d/m/Y H:i')) ?> - <?= e((string) (($profile['director_name'] ?? '') !== '' ? $profile['director_name'] : 'BUMDes')) ?></div>
        <div class="d-print-none">
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Cetak Sekarang</button>
            <a href="<?= e(base_url('/journals?' . report_filters_query($filters ?? []))) ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>
