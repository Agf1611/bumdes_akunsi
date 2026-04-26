<?php declare(strict_types=1); ?>
<style>
.ledger-stage6-table th,.ledger-stage6-table td{vertical-align:top;white-space:normal;}
.ledger-stage6-table .ledger-desc{min-width:320px;max-width:420px;}
.ledger-stage6-table .ledger-unit{min-width:170px;max-width:200px;}
.ledger-stage6-table .ledger-num{min-width:130px;}
.ledger-stage6-card{border:1px solid rgba(15,23,42,.08);border-radius:18px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.06);}
.ledger-stage6-card .pair{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px dashed rgba(15,23,42,.08);}
.ledger-stage6-card .pair:last-child{border-bottom:none;}
.ledger-stage6-card .label{font-size:.74rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
</style>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Buku Besar</h1>
        <p class="text-secondary mb-0">Mutasi akun berdasarkan jurnal umum, lengkap dengan saldo berjalan, unit usaha, dan print yang lebih rapi.</p>
    </div>
    <?php if ($selectedAccount): ?>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(base_url('/ledger/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-primary">Cetak</a>
            <a href="<?= e(base_url('/ledger/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-primary">Export PDF</a>
            <a href="<?= e(base_url('/ledger/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-outline-light">Export XLSX</a>
        </div>
    <?php endif; ?>
</div>
<?php if (($errors ?? []) !== []): ?>
<div class="alert alert-danger shadow-sm mb-4">
    <div class="fw-semibold mb-1">Filter buku besar belum bisa diproses</div>
    <ul class="mb-0 ps-3"><?php foreach (($errors ?? []) as $error): ?><li><?= e((string) $error) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<?php if (($warnings ?? []) !== []): ?>
<div class="alert alert-warning shadow-sm mb-4">
    <div class="fw-semibold mb-1">Catatan kompatibilitas</div>
    <ul class="mb-0 ps-3"><?php foreach (($warnings ?? []) as $warning): ?><li><?= e((string) $warning) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<div class="card shadow-sm mb-4"><div class="card-body p-4">
<form method="get" action="<?= e(base_url('/ledger')) ?>" class="row g-3 align-items-end">
    <div class="col-lg-3"><label class="form-label">Pilih Akun</label><select name="account_id" class="form-select" required><option value="">Pilih akun detail</option><?php foreach ($accounts as $account): ?><option value="<?= e((string) $account['id']) ?>" <?= (string) $filters['account_id'] === (string) $account['id'] ? 'selected' : '' ?>><?= e($account['account_code'] . ' - ' . $account['account_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-3"><label class="form-label">Periode</label><select name="period_id" class="form-select"><option value="">Semua periode</option><?php foreach ($periods as $period): ?><option value="<?= e((string) $period['id']) ?>" <?= (string) $filters['period_id'] === (string) $period['id'] ? 'selected' : '' ?>><?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Tahun</label><select name="fiscal_year" class="form-select"><option value="">Semua tahun</option><?php foreach (($reportYears ?? []) as $year): ?><option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-2"><label class="form-label">Unit Usaha</label><select name="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach (($units ?? []) as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-1"><label class="form-label">Mulai</label><input type="date" name="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>"></div>
    <div class="col-lg-1"><label class="form-label">Akhir</label><input type="date" name="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>"></div>
    <div class="col-lg-12 d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary">Tampilkan</button><a href="<?= e(base_url('/ledger')) ?>" class="btn btn-outline-secondary">Reset</a></div>
</form></div></div>
<?php if ($selectedAccount): ?>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Akun</div><div class="fw-semibold"><?= e((string) $selectedAccount['account_code']) ?></div><div class="text-secondary small"><?= e((string) $selectedAccount['account_name']) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Unit Usaha</div><div class="fw-semibold"><?= e(business_unit_label($selectedUnit)) ?></div><div class="text-secondary small">Filter laporan</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Saldo Awal</div><div class="fs-5 fw-semibold"><?= e(ledger_currency((float) $summary['opening_balance'])) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Saldo Akhir</div><div class="fs-5 fw-semibold"><?= e(ledger_currency((float) $summary['closing_balance'])) ?></div></div></div></div>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="d-none d-lg-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 ledger-stage6-table">
                    <thead class="table-light"><tr><th>Tanggal</th><th>No. Jurnal</th><th>Unit</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr></thead>
                    <tbody>
                    <?php if ($rows === []): ?><tr><td colspan="7" class="text-center text-secondary py-5">Tidak ada mutasi jurnal untuk filter yang dipilih.</td></tr><?php else: foreach ($rows as $row): ?><tr><td><?= e(format_id_date((string) $row['journal_date'])) ?></td><td><a href="<?= e(base_url('/journals/detail?id=' . (int) $row['journal_id'])) ?>" class="link-primary link-underline-opacity-0"><?= e((string) $row['journal_no']) ?></a></td><td class="ledger-unit"><?= e((string) ($row['unit_label'] ?? '-')) ?></td><td class="ledger-desc"><?= e((string) $row['description']) ?></td><td class="text-end ledger-num fw-semibold"><?= e(ledger_currency((float) $row['debit'])) ?></td><td class="text-end ledger-num fw-semibold"><?= e(ledger_currency((float) $row['credit'])) ?></td><td class="text-end ledger-num fw-semibold"><?= e(ledger_currency((float) $row['balance'])) ?></td></tr><?php endforeach; endif; ?>
                    </tbody>
                    <tfoot class="table-light"><tr><th colspan="4" class="text-end">Total Mutasi</th><th class="text-end ledger-num"><?= e(ledger_currency((float) $summary['total_debit'])) ?></th><th class="text-end ledger-num"><?= e(ledger_currency((float) $summary['total_credit'])) ?></th><th class="text-end ledger-num"><?= e(ledger_currency((float) $summary['closing_balance'])) ?></th></tr></tfoot>
                </table>
            </div>
        </div>
        <div class="d-lg-none p-3">
            <div class="ledger-stage6-card p-3 mb-3">
                <div class="pair"><div class="label">Saldo Awal</div><div class="fw-semibold"><?= e(ledger_currency((float) $summary['opening_balance'])) ?></div></div>
                <div class="pair"><div class="label">Total Debit</div><div class="fw-semibold"><?= e(ledger_currency((float) $summary['total_debit'])) ?></div></div>
                <div class="pair"><div class="label">Total Kredit</div><div class="fw-semibold"><?= e(ledger_currency((float) $summary['total_credit'])) ?></div></div>
                <div class="pair"><div class="label">Saldo Akhir</div><div class="fw-semibold"><?= e(ledger_currency((float) $summary['closing_balance'])) ?></div></div>
            </div>
            <?php if ($rows === []): ?><div class="text-center text-secondary py-4">Tidak ada mutasi jurnal untuk filter yang dipilih.</div><?php else: foreach ($rows as $row): ?><div class="ledger-stage6-card p-3 mb-3"><div class="d-flex justify-content-between align-items-start gap-3 mb-2"><div><div class="fw-semibold"><?= e((string) $row['journal_no']) ?></div><div class="text-secondary small"><?= e(format_id_date((string) $row['journal_date'])) ?></div></div><div class="text-end"><div class="label">Saldo</div><div class="fw-semibold"><?= e(ledger_currency((float) $row['balance'])) ?></div></div></div><div class="pair"><div class="label">Unit</div><div class="fw-semibold text-end"><?= e((string) ($row['unit_label'] ?? '-')) ?></div></div><div class="pt-2"><div class="label mb-1">Keterangan</div><div><?= e((string) $row['description']) ?></div></div><div class="row g-2 pt-3"><div class="col-6"><div class="ledger-stage6-card p-2"><div class="label">Debit</div><div class="fw-semibold"><?= e(ledger_currency((float) $row['debit'])) ?></div></div></div><div class="col-6"><div class="ledger-stage6-card p-2"><div class="label">Kredit</div><div class="fw-semibold"><?= e(ledger_currency((float) $row['credit'])) ?></div></div></div></div><div class="pt-3"><a href="<?= e(base_url('/journals/detail?id=' . (int) $row['journal_id'])) ?>" class="btn btn-sm btn-outline-primary w-100">Buka detail jurnal</a></div></div><?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php else: ?><div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih akun dan filter yang diinginkan, lalu klik <strong class="text-dark">Tampilkan</strong> untuk melihat mutasi buku besar.</div></div><?php endif; ?>
