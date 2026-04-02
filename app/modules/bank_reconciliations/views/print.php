<?php declare(strict_types=1); ?>
<?php render_print_header($profile, $reportTitle, $periodLabel, $selectedUnitLabel); ?>

<?php $netGap = (float) ($reconciliation['total_statement_net'] ?? 0) - (float) ($journalSummary['journal_net'] ?? 0); ?>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Saldo Awal</div><div class="fw-semibold"><?= e(bank_reconciliation_currency((float) ($reconciliation['opening_balance'] ?? 0))) ?></div><div class="small text-secondary mt-2">Saldo Akhir</div><div class="fw-semibold"><?= e(bank_reconciliation_currency((float) ($reconciliation['closing_balance'] ?? 0))) ?></div></div></div>
    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Mutasi Statement</div><div class="fw-semibold text-success">Masuk <?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_in'] ?? 0))) ?></div><div class="fw-semibold text-danger">Keluar <?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_out'] ?? 0))) ?></div><div class="small text-secondary mt-2">Bersih <?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_net'] ?? 0))) ?></div></div></div>
    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Mutasi Jurnal Bank</div><div class="fw-semibold text-success">Masuk <?= e(bank_reconciliation_currency((float) ($journalSummary['journal_in'] ?? 0))) ?></div><div class="fw-semibold text-danger">Keluar <?= e(bank_reconciliation_currency((float) ($journalSummary['journal_out'] ?? 0))) ?></div><div class="small text-secondary mt-2">Bersih <?= e(bank_reconciliation_currency((float) ($journalSummary['journal_net'] ?? 0))) ?></div></div></div>
    <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Status Match</div><div class="fw-semibold text-success">Cocok <?= e((string) ($reconciliation['total_matched_rows'] ?? 0)) ?></div><div class="fw-semibold text-warning">Belum <?= e((string) ($reconciliation['total_unmatched_rows'] ?? 0)) ?></div><div class="small text-secondary mt-2">Selisih bank vs jurnal <?= e(bank_reconciliation_currency($netGap)) ?></div></div></div>
</div>

<div class="alert <?= abs($netGap) < 0.01 ? 'alert-success' : 'alert-warning' ?> py-2">
    <?= abs($netGap) < 0.01 ? 'Mutasi bank bersih sudah sama dengan jurnal bank pada rentang ini.' : 'Masih ada selisih antara mutasi bank dan jurnal bank. Periksa baris yang belum match atau jurnal yang belum dibukukan.' ?>
</div>

<table class="table table-bordered align-middle print-table mb-0">
    <thead>
    <tr>
        <th style="width:6%">No</th>
        <th style="width:11%">Tanggal</th>
        <th>Keterangan</th>
        <th style="width:10%">Arah</th>
        <th style="width:12%" class="text-end">Masuk</th>
        <th style="width:12%" class="text-end">Keluar</th>
        <th style="width:11%">Status</th>
        <th style="width:18%">Jurnal</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($lines === []): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada baris mutasi pada sesi rekonsiliasi ini.</td></tr>
    <?php else: ?>
        <?php foreach ($lines as $line): ?>
            <tr>
                <td><?= e((string) ($line['line_no'] ?? 0)) ?></td>
                <td><?= e(format_id_date((string) ($line['transaction_date'] ?? ''))) ?></td>
                <td>
                    <div class="fw-semibold"><?= e((string) ($line['description'] ?? '-')) ?></div>
                    <div class="small text-secondary">Ref: <?= e((string) (($line['reference_no'] ?? '') !== '' ? $line['reference_no'] : '-')) ?></div>
                </td>
                <td><?= e(bank_reconciliation_direction_label((float) ($line['amount_in'] ?? 0), (float) ($line['amount_out'] ?? 0))) ?></td>
                <td class="text-end"><?= e(bank_reconciliation_currency((float) ($line['amount_in'] ?? 0))) ?></td>
                <td class="text-end"><?= e(bank_reconciliation_currency((float) ($line['amount_out'] ?? 0))) ?></td>
                <td>
                    <span class="badge <?= e(bank_reconciliation_status_badge_class((string) ($line['match_status'] ?? 'UNMATCHED'))) ?>"><?= e(bank_reconciliation_status_label((string) ($line['match_status'] ?? 'UNMATCHED'))) ?></span>
                    <?php if ((string) ($line['matched_reason'] ?? '') !== ''): ?><div class="small text-secondary mt-1"><?= e((string) $line['matched_reason']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($line['matched_journal_id'])): ?>
                        <div class="fw-semibold"><?= e((string) ($line['journal_no'] ?? '-')) ?></div>
                        <div class="small text-secondary"><?= e(format_id_date((string) ($line['journal_date'] ?? ''))) ?></div>
                        <div class="small text-secondary"><?= e((string) ($line['journal_description'] ?? '')) ?></div>
                    <?php else: ?>
                        <span class="text-secondary">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
    <tfoot>
    <tr>
        <th colspan="4" class="text-end">Total Statement</th>
        <th class="text-end"><?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_in'] ?? 0))) ?></th>
        <th class="text-end"><?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_out'] ?? 0))) ?></th>
        <th colspan="2" class="text-end">Net <?= e(bank_reconciliation_currency((float) ($reconciliation['total_statement_net'] ?? 0))) ?></th>
    </tr>
    </tfoot>
</table>

<?php if ((string) ($reconciliation['notes'] ?? '') !== ''): ?>
    <div class="mt-3 small text-secondary"><strong>Catatan:</strong> <?= e((string) $reconciliation['notes']) ?></div>
<?php endif; ?>

<?php render_print_signature($profile); ?>
