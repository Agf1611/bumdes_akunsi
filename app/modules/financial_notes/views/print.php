<?php declare(strict_types=1); ?>
<div class="print-sheet">
    <?php render_print_header($profile, 'Catatan atas Laporan Keuangan', report_period_label($filters, $selectedPeriod), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="mb-3 text-secondary">Catatan atas Laporan Keuangan ini merupakan bagian yang tidak terpisahkan dari laporan keuangan BUMDes untuk periode yang disajikan dan disusun mengikuti struktur KepmenDesa PDTT Nomor 136 Tahun 2022.</div>

    <?php foreach ($notes as $note): ?>
        <section class="mb-4 page-break-inside-avoid">
            <h2 class="h6 fw-bold mb-2"><?= e((string) ($note['title'] ?? '')) ?></h2>
            <?php foreach (($note['paragraphs'] ?? []) as $paragraph): ?>
                <p class="mb-2"><?= e((string) $paragraph) ?></p>
            <?php endforeach; ?>
            <?php if (financial_notes_has_rows((array) ($note['rows'] ?? []))): ?>
                <table class="table table-bordered print-table align-middle mt-2">
                    <thead><tr><th style="width:16%;">Kode Akun</th><th>Nama Akun</th><th style="width:20%;" class="text-end">Nilai</th></tr></thead>
                    <tbody>
                        <?php foreach (($note['rows'] ?? []) as $row): ?>
                            <?php if (abs((float) ($row['amount'] ?? 0)) <= 0.004) { continue; } ?>
                            <tr>
                                <td><?= e((string) ($row['account_code'] ?? '-')) ?></td>
                                <td><?= e((string) ($row['account_name'] ?? '-')) ?></td>
                                <td class="text-end"><?= e(financial_notes_currency((float) ($row['amount'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end"><?= e(financial_notes_currency(financial_notes_table_total((array) ($note['rows'] ?? [])))) ?></th></tr></tfoot>
                </table>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php render_print_signature($profile); ?>
</div>
<script>window.print();</script>
