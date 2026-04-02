<?php declare(strict_types=1);
$periodLabel = report_period_label($filters, $selectedPeriod);
$unitLabel = $selectedUnitLabel ?? 'Semua Unit';
?>
<style>
.equity-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 16px}
.equity-summary-card{border:1px solid #a7b3c3;border-radius:14px;padding:12px 14px;background:#fff;page-break-inside:avoid}
.equity-summary-card__label{font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#4b5563;margin-bottom:6px}
.equity-summary-card__value{font-size:18px;font-weight:800;line-height:1.2;color:#111827}
.equity-summary-card__note{font-size:10px;color:#4b5563;margin-top:4px}
.report-note-box{border:1px solid #a7b3c3;border-radius:14px;padding:10px 12px;background:#f8fafc;margin-bottom:14px}
.report-note-box__title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
@media print{.equity-summary-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.equity-summary-card__value{font-size:16px}}
</style>
<div class="print-sheet">
    <?php render_print_header($profile, 'Laporan Perubahan Ekuitas', $periodLabel, $unitLabel); ?>

    <div class="equity-summary-grid">
        <div class="equity-summary-card">
            <div class="equity-summary-card__label">Saldo Awal</div>
            <div class="equity-summary-card__value"><?= e(ledger_currency((float) $report['total_opening_equity'])) ?></div>
            <div class="equity-summary-card__note">Ekuitas langsung pada awal periode.</div>
        </div>
        <div class="equity-summary-card">
            <div class="equity-summary-card__label">Mutasi Periode</div>
            <div class="equity-summary-card__value"><?= e(ledger_currency((float) $report['total_movement_equity'])) ?></div>
            <div class="equity-summary-card__note">Penambahan dan pengurangan ekuitas langsung selama periode.</div>
        </div>
        <div class="equity-summary-card">
            <div class="equity-summary-card__label">Laba / Rugi Berjalan</div>
            <div class="equity-summary-card__value"><?= e(ledger_currency((float) $report['net_income'])) ?></div>
            <div class="equity-summary-card__note">Laba atau rugi yang menambah atau mengurangi ekuitas.</div>
        </div>
        <div class="equity-summary-card">
            <div class="equity-summary-card__label">Total Ekuitas Akhir</div>
            <div class="equity-summary-card__value"><?= e(ledger_currency((float) $report['final_equity_total'])) ?></div>
            <div class="equity-summary-card__note">Ekuitas akhir setelah laba/rugi berjalan.</div>
        </div>
    </div>

    <div class="report-note-box">
        <div class="report-note-box__title">Ikhtisar Laporan</div>
        <div>Laporan ini menyajikan perubahan saldo ekuitas untuk periode <?= e($periodLabel) ?> pada <?= e($unitLabel) ?>, termasuk mutasi langsung dan pengaruh laba/rugi berjalan.</div>
    </div>

    <table class="table table-bordered align-middle print-table mb-0">
        <thead>
            <tr>
                <th style="width:14%;">Kode</th>
                <th>Nama Akun</th>
                <th style="width:18%;" class="text-end">Saldo Awal</th>
                <th style="width:18%;" class="text-end">Mutasi Periode</th>
                <th style="width:18%;" class="text-end">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($report['rows'] === []): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data perubahan ekuitas untuk filter yang dipilih.</td></tr>
            <?php else: ?>
                <?php foreach ($report['rows'] as $row): ?>
                    <tr>
                        <td><?= e((string) $row['account_code']) ?></td>
                        <td><?= e((string) $row['account_name']) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['opening_amount'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['movement_amount'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['closing_amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total Ekuitas Langsung</th>
                <th class="text-end"><?= e(ledger_currency((float) $report['total_opening_equity'])) ?></th>
                <th class="text-end"><?= e(ledger_currency((float) $report['total_movement_equity'])) ?></th>
                <th class="text-end"><?= e(ledger_currency((float) $report['total_closing_equity'])) ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Laba / Rugi Berjalan</th>
                <th class="text-end"><?= e(ledger_currency((float) $report['net_income'])) ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Total Ekuitas Akhir</th>
                <th class="text-end"><?= e(ledger_currency((float) $report['final_equity_total'])) ?></th>
            </tr>
        </tfoot>
    </table>

    <?php render_print_signature($profile); ?>
</div>
<script>window.print();</script>
