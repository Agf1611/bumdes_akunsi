<?php declare(strict_types=1);
$reconDiff = (float) ((($reconciliation['difference'] ?? null) ?? ($report['reconciliation_difference'] ?? 0)));
$reconBadgeClass = function_exists('report_reconciliation_badge_class') ? report_reconciliation_badge_class($reconDiff) : (abs($reconDiff) < 0.00001 ? 'text-bg-success' : 'text-bg-warning');
$reconStatus = function_exists('report_reconciliation_status') ? report_reconciliation_status($reconDiff) : (abs($reconDiff) < 0.00001 ? 'Sinkron' : 'Perlu cek');
$reconNote = function_exists('report_reconciliation_note') ? report_reconciliation_note($reconDiff, 'saldo akhir buku pembantu piutang', 'saldo kontrol akun piutang') : ((abs($reconDiff) < 0.00001) ? 'saldo akhir buku pembantu piutang sudah sama dengan saldo kontrol akun piutang.' : 'saldo akhir buku pembantu piutang belum sama dengan saldo kontrol akun piutang.');
$status = array_merge(['partners_table' => false, 'partner_id_column' => false, 'business_unit_column' => false], is_array($featureStatus ?? null) ? $featureStatus : []);
$warnings = is_array($warnings ?? null) ? $warnings : [];
$selectedPartnerLabel = trim((string) (($selectedPartner['partner_code'] ?? '') !== '' ? ($selectedPartner['partner_code'] . ' - ' . $selectedPartner['partner_name']) : ($selectedPartner['partner_name'] ?? '')));
$selectedAccountLabel = trim((string) (($selectedAccount['account_code'] ?? '') !== '' ? ($selectedAccount['account_code'] . ' - ' . $selectedAccount['account_name']) : ''));
$hasFilters = (bool) ($hasFilters ?? false);
$movementSummary = $movementSummary ?? ['debit_total' => 0.0, 'credit_total' => 0.0, 'journal_count' => 0, 'partner_count' => 0, 'last_transaction_date' => null];
$agingBuckets = is_array($agingBuckets ?? null) ? $agingBuckets : [];
$topPartners = is_array($topPartners ?? null) ? $topPartners : [];
$reconciliation = is_array($reconciliation ?? null) ? $reconciliation : ['control_balance' => 0.0, 'difference' => 0.0, 'is_reconciled' => true];
$lastTransactionLabel = trim((string) ($movementSummary['last_transaction_date'] ?? '')) !== '' ? format_id_date((string) $movementSummary['last_transaction_date']) : '-';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Buku Pembantu Piutang</h1>
        <p class="text-secondary mb-0">Pantau mutasi, umur piutang, dan mitra dengan saldo terbesar langsung dari jurnal yang sudah diberi metadata referensi.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($hasFilters): ?>
<div class="card shadow-sm border-0 mb-4"><div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
    <div>
        <div class="text-secondary small mb-1">Status Rekonsiliasi Piutang</div>
        <span class="badge <?= e($reconBadgeClass) ?> px-3 py-2"><?= e($reconStatus) ?></span>
        <div class="text-secondary small mt-2"><?= e($reconNote) ?></div>
    </div>
    <div class="text-lg-end">
        <div class="text-secondary small">Selisih</div>
        <div class="fw-semibold <?= abs((float) ($reconciliation['difference'] ?? 0)) <= 0.01 ? 'text-success' : 'text-warning' ?>"><?= e(ledger_currency(abs((float) ($reconciliation['difference'] ?? 0)))) ?></div>
    </div>
</div></div>
            <a href="<?= e(base_url('/receivable-ledgers/print?' . http_build_query($filters))) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">Cetak</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$status['partners_table'] || !$status['partner_id_column'] || !$status['business_unit_column']): ?>
    <div class="alert alert-warning">Fitur buku pembantu piutang berjalan dalam mode kompatibilitas. Beberapa filter metadata mungkin belum aktif sampai patch jurnal/reference selesai dijalankan.</div>
<?php endif; ?>
<?php foreach ($warnings as $warning): ?>
    <div class="alert alert-warning"><?= e($warning) ?></div>
<?php endforeach; ?>

<div class="card dashboard-card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Mitra / Debitur</label>
                <select name="partner_id" class="form-select">
                    <option value="">Semua mitra / semua debitur</option>
                    <?php foreach ($partners as $partner): ?>
                        <option value="<?= e((string) $partner['id']) ?>" <?= (int) ($filters['partner_id'] ?? 0) === (int) $partner['id'] ? 'selected' : '' ?>><?= e(trim(($partner['partner_code'] ? $partner['partner_code'] . ' - ' : '') . $partner['partner_name'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Kosongkan jika ingin melihat ringkasan seluruh debitur terlebih dahulu.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Akun Piutang</label>
                <select name="account_id" class="form-select">
                    <option value="">Semua akun piutang</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= e((string) $account['id']) ?>" <?= (int) ($filters['account_id'] ?? 0) === (int) $account['id'] ? 'selected' : '' ?>><?= e($account['account_code'] . ' - ' . $account['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Periode</label>
                <select name="period_id" class="form-select">
                    <option value="">Semua periode</option>
                    <?php foreach ($periods as $period): ?>
                        <option value="<?= e((string) $period['id']) ?>" <?= (int) ($filters['period_id'] ?? 0) === (int) $period['id'] ? 'selected' : '' ?>><?= e($period['period_code'] . ' - ' . $period['period_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun</label>
                <select name="fiscal_year" class="form-select">
                    <option value="">Semua tahun</option>
                    <?php foreach (($reportYears ?? []) as $year): ?>
                        <option value="<?= e((string) $year) ?>" <?= (int) ($filters['fiscal_year'] ?? 0) === (int) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Unit Usaha</label>
                <select name="unit_id" class="form-select">
                    <option value="">Semua unit</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= e((string) $unit['id']) ?>" <?= (int) ($filters['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="date_from" class="form-control" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="date_to" class="form-control" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="<?= e(base_url('/receivable-ledgers')) ?>" class="btn btn-light border">Reset</a>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$hasFilters): ?>
    <div class="alert alert-info mb-4">Mulai dengan memilih periode atau rentang tanggal. Anda bisa meninjau ringkasan seluruh mitra terlebih dahulu, lalu mempersempit ke satu debitur jika perlu.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mitra Terpilih</div><div class="fw-semibold"><?= e($selectedPartnerLabel !== '' ? $selectedPartnerLabel : 'Semua mitra / debitur') ?></div><div class="text-secondary small">Lingkup buku pembantu</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Akun</div><div class="fw-semibold"><?= e($selectedAccountLabel !== '' ? $selectedAccountLabel : 'Semua akun piutang') ?></div><div class="text-secondary small">Filter akun piutang</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Saldo Awal</div><div class="fw-semibold"><?= e(ledger_currency((float) ($summary['opening_balance'] ?? 0))) ?></div><div class="text-secondary small">Sebelum periode filter</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Saldo Akhir</div><div class="fw-semibold"><?= e(ledger_currency((float) ($summary['closing_balance'] ?? 0))) ?></div><div class="text-secondary small">Per <?= e($lastTransactionLabel) ?></div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mutasi Debit</div><div class="h5 mb-0"><?= e(ledger_currency((float) ($movementSummary['debit_total'] ?? 0))) ?></div><div class="text-secondary small mt-2">Penambahan piutang</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mutasi Kredit</div><div class="h5 mb-0"><?= e(ledger_currency((float) ($movementSummary['credit_total'] ?? 0))) ?></div><div class="text-secondary small mt-2">Pelunasan / pengurang</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Jumlah Jurnal</div><div class="h5 mb-0"><?= e(number_format((int) ($movementSummary['journal_count'] ?? 0), 0, ',', '.')) ?></div><div class="text-secondary small mt-2">Dalam rentang filter</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mitra Aktif</div><div class="h5 mb-0"><?= e(number_format((int) ($movementSummary['partner_count'] ?? 0), 0, ',', '.')) ?></div><div class="text-secondary small mt-2">Terdeteksi pada mutasi</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Umur Piutang</h2>
                        <p class="text-secondary small mb-0">Bucket umur dihitung dari tanggal jurnal sampai tanggal akhir filter.</p>
                    </div>
                    <span class="badge text-bg-light">Per <?= e($lastTransactionLabel) ?></span>
                </div>
                <div class="vstack gap-3">
                    <?php if ($agingBuckets === []): ?>
                        <div class="text-secondary small">Belum ada data umur piutang.</div>
                    <?php else: ?>
                        <?php foreach ($agingBuckets as $bucket): ?>
                            <?php $amount = (float) ($bucket['amount'] ?? 0); ?>
                            <div>
                                <div class="d-flex justify-content-between small mb-1"><span class="fw-semibold"><?= e((string) ($bucket['label'] ?? '-')) ?></span><span><?= e(ledger_currency($amount)) ?></span></div>
                                <div class="progress" role="progressbar" aria-label="<?= e((string) ($bucket['label'] ?? '-')) ?>">
                                    <div class="progress-bar" style="width: <?= e((string) min(100, max(3, abs($amount) > 0 ? round((abs($amount) / max(1, abs((float) ($summary['closing_balance'] ?? 0)))) * 100, 2) : 3))) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Mitra Dengan Saldo Terbesar</h2>
                        <p class="text-secondary small mb-0">Gunakan daftar ini untuk memilih debitur prioritas yang perlu ditagih atau direview.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Mitra</th><th class="text-end">Mutasi</th><th class="text-end">Saldo</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($topPartners === []): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada saldo piutang untuk ditampilkan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topPartners as $partnerRow): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e(trim((string) (($partnerRow['partner_code'] ?? '') . ' - ' . ($partnerRow['partner_name'] ?? '')), ' -')) ?></div>
                                        <div class="text-secondary small"><?= e(number_format((int) ($partnerRow['journal_count'] ?? 0), 0, ',', '.')) ?> jurnal</div>
                                    </td>
                                    <td class="text-end text-secondary"><?= e(ledger_currency((float) (($partnerRow['debit_total'] ?? 0) - ($partnerRow['credit_total'] ?? 0)))) ?></td>
                                    <td class="text-end fw-semibold"><?= e(ledger_currency((float) ($partnerRow['balance'] ?? 0))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card dashboard-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Mitra</th>
                        <th>Akun</th>
                        <th>Uraian</th>
                        <th>Unit</th>
                        <th>Tag</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary-subtle">
                        <td colspan="9" class="fw-semibold">Saldo Awal</td>
                        <td class="text-end fw-semibold"><?= e(ledger_currency((float) ($summary['opening_balance'] ?? 0))) ?></td>
                    </tr>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="10" class="text-center text-secondary py-5">Belum ada mutasi piutang untuk filter yang dipilih.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e(format_id_date((string) $row['journal_date'])) ?></td>
                                <td><?= e((string) $row['journal_no']) ?></td>
                                <td><?= e((string) $row['partner_label']) ?></td>
                                <td><?= e(trim($row['account_code'] . ' - ' . $row['account_name'])) ?></td>
                                <td><?= e((string) $row['description']) ?></td>
                                <td><?= e((string) $row['unit_label']) ?></td>
                                <td><?= e((string) ($row['entry_tag'] !== '' ? strtoupper((string) $row['entry_tag']) : '-')) ?></td>
                                <td class="text-end"><?= e(ledger_currency((float) $row['debit'])) ?></td>
                                <td class="text-end"><?= e(ledger_currency((float) $row['credit'])) ?></td>
                                <td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['balance'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end">Total Mutasi / Saldo Akhir</th>
                        <th class="text-end"><?= e(ledger_currency((float) ($summary['total_debit'] ?? 0))) ?></th>
                        <th class="text-end"><?= e(ledger_currency((float) ($summary['total_credit'] ?? 0))) ?></th>
                        <th class="text-end"><?= e(ledger_currency((float) ($summary['closing_balance'] ?? 0))) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
