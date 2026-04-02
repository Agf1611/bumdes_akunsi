<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Laporan Neraca</h1>
        <p class="text-secondary mb-0">Posisi keuangan per tanggal laporan. Anda bisa memakai periode sebagai referensi, tetapi tanggal neraca tetap bisa dipilih bebas.</p>
    </div>
    <?php if ($filters['date_to'] !== ''): ?>
        <div class="d-flex gap-2">
            <a href="<?= e(base_url('/balance-sheet/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
            <a href="<?= e(base_url('/balance-sheet/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-primary">Export PDF</a>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <form method="get" action="<?= e(base_url('/balance-sheet')) ?>" class="row g-3 align-items-end">
        <div class="col-lg-4"><label for="period_id" class="form-label">Periode Referensi</label><select name="period_id" id="period_id" class="form-select"><option value="">Opsional / bantu isi tanggal</option><?php foreach ($periods as $period): ?><option value="<?= e((string) $period['id']) ?>" <?= (string) $filters['period_id'] === (string) $period['id'] ? 'selected' : '' ?>><?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><label for="fiscal_year" class="form-label">Tahun</label><select name="fiscal_year" id="fiscal_year" class="form-select"><option value="">Semua tahun</option><?php foreach (($reportYears ?? []) as $year): ?><option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-3"><label for="unit_id" class="form-label">Unit Usaha</label><select name="unit_id" id="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><label for="date_from" class="form-label">Tanggal Awal (opsional)</label><input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>"></div>
        <div class="col-lg-2"><label for="date_to" class="form-label">Tanggal Neraca</label><input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>"></div>
        <div class="col-lg-1 d-grid"><button type="submit" class="btn btn-primary">Tampil</button></div>
    </form>
</div></div>

<?php
$currentAsOf = trim((string) ($filters['date_to'] ?? ''));
$comparisonAsOf = trim((string) ($report['comparison_as_of_date'] ?? ''));
$currentColumnLabel = $currentAsOf !== '' ? 'Per ' . $currentAsOf : 'Saldo Akhir';
$comparisonColumnLabel = (!empty($report['comparison_enabled']) && $comparisonAsOf !== '') ? 'Per ' . $comparisonAsOf : ((string) ($report['comparison_column_label'] ?? 'Pembanding'));
?>

<?php if ($filters['date_to'] !== ''): ?>
<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Neraca Per Tanggal</div><div class="fw-semibold"><?= e((string) $filters['date_to']) ?></div><div class="text-secondary small"><?= e($selectedPeriod['period_name'] ?? 'Tanggal bebas') ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Unit Usaha</div><div class="fw-semibold"><?= e($selectedUnitLabel) ?></div><div class="text-secondary small">Ruang lingkup laporan</div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Status Neraca</div><div class="fw-semibold <?= $report['is_balanced'] ? 'text-success' : 'text-danger' ?>"><?= $report['is_balanced'] ? 'Seimbang' : 'Belum Seimbang' ?></div><div class="text-secondary small">Selisih <?= e(ledger_currency(abs((float) $report['difference']))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Kolom Pembanding</div><div class="fw-semibold <?= !empty($report['comparison_enabled']) ? 'text-info' : 'text-secondary' ?>"><?= !empty($report['comparison_enabled']) ? 'Aktif' : 'Tidak aktif' ?></div><div class="text-secondary small"><?= e((string) (!empty($report['comparison_enabled']) ? ($report['comparison_label'] ?? '-') : 'Muncul otomatis jika ada jurnal saldo awal tahun berjalan')) ?></div></div></div></div>
</div>

<?php if (!$report['is_balanced']): ?><div class="alert alert-warning">Neraca belum seimbang. Selisih saat ini sebesar <strong><?= e(ledger_currency(abs((float) $report['difference']))) ?></strong>.</div><?php endif; ?>
<?php if (!empty($report['comparison_enabled'])): ?><div class="alert alert-info">Kolom pembanding membaca jurnal saldo awal sebagai posisi <strong><?= e((string) ($report['comparison_label'] ?? '-')) ?></strong>.</div><?php endif; ?>

<div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive balance-sheet-table-wrapper">
<table class="table table-dark table-hover align-middle mb-0 coa-table balance-sheet-table">
<thead><tr><th>No</th><th>Kode Akun</th><th>Uraian</th><th class="text-end"><?= e($currentColumnLabel) ?></th><?php if (!empty($report['comparison_enabled'])): ?><th class="text-end"><?= e($comparisonColumnLabel) ?></th><?php endif; ?></tr></thead><tbody>
<?php $rowNo = 1; ?><tr class="balance-section-row"><td><?= $rowNo++ ?></td><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">ASET</td></tr>
<?php if ($report['asset_rows'] === []): ?><tr><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="text-center text-secondary py-4">Tidak ada akun aset untuk filter yang dipilih.</td></tr><?php else: foreach ($report['asset_rows'] as $row): ?><tr><td><?= $rowNo++ ?></td><td class="fw-semibold"><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end text-secondary"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; endif; ?>
<tr class="balance-total-row"><td><?= $rowNo++ ?></td><td colspan="2" class="text-end fw-semibold">TOTAL ASET</td><td class="text-end fw-bold text-info"><?= e(ledger_currency((float) $report['total_assets'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end fw-bold text-secondary"><?= e(ledger_currency((float) ($report['comparison_total_assets'] ?? 0))) ?></td><?php endif; ?></tr>

<tr class="balance-section-row"><td><?= $rowNo++ ?></td><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">KEWAJIBAN</td></tr>
<?php if ($report['liability_rows'] === []): ?><tr><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="text-center text-secondary py-4">Tidak ada akun liabilitas untuk filter yang dipilih.</td></tr><?php else: foreach ($report['liability_rows'] as $row): ?><tr><td><?= $rowNo++ ?></td><td class="fw-semibold"><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end text-secondary"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; endif; ?>
<tr class="balance-total-row"><td><?= $rowNo++ ?></td><td colspan="2" class="text-end fw-semibold">TOTAL KEWAJIBAN</td><td class="text-end fw-bold text-warning"><?= e(ledger_currency((float) $report['total_liabilities'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end fw-bold text-secondary"><?= e(ledger_currency((float) ($report['comparison_total_liabilities'] ?? 0))) ?></td><?php endif; ?></tr>

<tr class="balance-section-row"><td><?= $rowNo++ ?></td><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="fw-semibold">EKUITAS</td></tr>
<?php if ($report['equity_rows'] === []): ?><tr><td colspan="<?= !empty($report['comparison_enabled']) ? '4' : '3' ?>" class="text-center text-secondary py-4">Tidak ada akun ekuitas untuk filter yang dipilih.</td></tr><?php else: foreach ($report['equity_rows'] as $row): ?><tr><td><?= $rowNo++ ?></td><td class="fw-semibold"><?= e((string) $row['account_code']) ?></td><td><?= e((string) $row['account_name']) ?></td><td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['amount'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end text-secondary"><?= e(ledger_currency((float) ($row['comparison_amount'] ?? 0))) ?></td><?php endif; ?></tr><?php endforeach; endif; ?>
<?php if (abs((float) $report['current_earnings']) > 0.004 || abs((float) ($report['comparison_current_earnings'] ?? 0)) > 0.004): ?><tr><td><?= $rowNo++ ?></td><td></td><td>Laba / Rugi Berjalan</td><td class="text-end fw-semibold"><?= e(ledger_currency((float) $report['current_earnings'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end text-secondary"><?= e(ledger_currency((float) ($report['comparison_current_earnings'] ?? 0))) ?></td><?php endif; ?></tr><?php endif; ?>
<tr class="balance-total-row"><td><?= $rowNo++ ?></td><td colspan="2" class="text-end fw-semibold">TOTAL EKUITAS</td><td class="text-end fw-bold text-success"><?= e(ledger_currency((float) $report['total_equity'])) ?></td><?php if (!empty($report['comparison_enabled'])): ?><td class="text-end fw-bold text-secondary"><?= e(ledger_currency((float) ($report['comparison_total_equity'] ?? 0))) ?></td><?php endif; ?></tr>
</tbody><tfoot><tr><th><?= $rowNo++ ?></th><th colspan="2" class="text-end">TOTAL KEWAJIBAN DAN EKUITAS</th><th class="text-end"><?= e(ledger_currency((float) $report['total_liabilities_equity'])) ?></th><?php if (!empty($report['comparison_enabled'])): ?><th class="text-end"><?= e(ledger_currency((float) ($report['comparison_total_liabilities_equity'] ?? 0))) ?></th><?php endif; ?></tr></tfoot>
</table>
</div></div></div>
<?php else: ?>
<div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-light">Tampil</strong> untuk melihat neraca.</div></div>
<?php endif; ?>
