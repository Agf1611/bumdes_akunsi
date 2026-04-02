<?php declare(strict_types=1);

$cashFlowTitle = 'Laporan Arus Kas';
$cashFlowSubtitle = 'Disusun dengan metode langsung, menampilkan arus kas operasi, investasi, dan pendanaan secara ringkas.';

$currency = static function (float $amount): string {
    return ledger_currency($amount);
};

$sectionCodes = ['OPERATING', 'INVESTING', 'FINANCING'];
$sectionOrder = [
    'OPERATING' => 'Aktivitas Operasi',
    'INVESTING' => 'Aktivitas Investasi',
    'FINANCING' => 'Aktivitas Pendanaan',
];

$deriveRows = static function (array $rows, string $flowKey, string $fallbackLabel): array {
    $bucket = [];
    foreach ($rows as $row) {
        $amount = (float) ($row[$flowKey] ?? 0.0);
        if (abs($amount) < 0.005) {
            continue;
        }
        $label = trim((string) ($row['label'] ?? $row['description'] ?? ''));
        if ($label === '') {
            $label = $fallbackLabel;
        }
        if (!isset($bucket[$label])) {
            $bucket[$label] = 0.0;
        }
        $bucket[$label] += $amount;
    }
    $out = [];
    foreach ($bucket as $label => $amount) {
        $out[] = ['label' => (string) $label, 'amount' => (float) $amount];
    }
    return $out;
};

$buildSectionData = static function (string $section) use ($report, $sectionOrder, $deriveRows): array {
    $data = (array) ($report['sections'][$section] ?? []);
    if ($data !== []) {
        $data['title'] = (string) ($data['title'] ?? ('Arus Kas dari ' . ($sectionOrder[$section] ?? $section)));
        $data['in_rows'] = array_values((array) ($data['in_rows'] ?? []));
        $data['out_rows'] = array_values((array) ($data['out_rows'] ?? []));
        $data['total_in'] = (float) ($data['total_in'] ?? 0.0);
        $data['total_out'] = (float) ($data['total_out'] ?? 0.0);
        $data['net'] = (float) ($data['net'] ?? 0.0);
        $data['net_label'] = (string) ($data['net_label'] ?? 'Arus kas bersih');
        return $data;
    }

    $flatRows = match ($section) {
        'OPERATING' => (array) ($report['operating_rows'] ?? []),
        'INVESTING' => (array) ($report['investing_rows'] ?? []),
        default => (array) ($report['financing_rows'] ?? []),
    };

    $inRows = $deriveRows($flatRows, 'cash_in', 'Penerimaan kas lainnya');
    $outRows = $deriveRows($flatRows, 'cash_out', 'Pengeluaran kas lainnya');
    $totalIn = 0.0;
    foreach ($inRows as $row) {
        $totalIn += (float) ($row['amount'] ?? 0.0);
    }
    $totalOut = 0.0;
    foreach ($outRows as $row) {
        $totalOut += (float) ($row['amount'] ?? 0.0);
    }

    return [
        'title' => 'Arus Kas dari ' . ($sectionOrder[$section] ?? $section),
        'in_rows' => $inRows,
        'out_rows' => $outRows,
        'total_in' => $totalIn,
        'total_out' => $totalOut,
        'net' => $totalIn - $totalOut,
        'net_label' => 'Arus kas bersih dari ' . strtolower($sectionOrder[$section] ?? $section),
    ];
};

$sections = [];
foreach ($sectionCodes as $sectionCode) {
    $sections[$sectionCode] = $buildSectionData($sectionCode);
}

$periodLabel = report_period_label($filters, $selectedPeriod);
$selectedUnitDisplay = $selectedUnitLabel ?? 'Semua Unit';
$difference = (float) ($report['difference'] ?? 0.0);
$actualClosing = (float) ($report['actual_closing_cash'] ?? ($report['closing_cash'] ?? 0.0));
?>

<style>
.cashflow-mekari-page .cashflow-hero {
    border: 1px solid rgba(148,163,184,.28);
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(180deg, rgba(255,255,255,.96) 0%, rgba(248,250,252,.98) 100%);
    box-shadow: 0 18px 40px rgba(15,23,42,.08);
}
.cashflow-mekari-page .cashflow-kicker {
    font-size: .78rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    font-weight: 700;
    color: #94a3b8;
}
.cashflow-mekari-page .cashflow-title {
    font-size: clamp(1.9rem, 2.7vw, 2.8rem);
    line-height: 1.06;
    margin: .35rem 0 .6rem;
    font-weight: 800;
    color: #0f172a;
}
.cashflow-mekari-page .cashflow-subtitle {
    color: #475569;
    max-width: 760px;
    font-size: 1rem;
}
.cashflow-mekari-page .cashflow-pillbar {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 1.2rem;
}
.cashflow-mekari-page .cashflow-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border: 1px solid rgba(148,163,184,.3);
    background: #fff;
    border-radius: 999px;
    padding: .7rem 1rem;
    font-size: .92rem;
    color: #334155;
}
.cashflow-mekari-page .cashflow-filter-card,
.cashflow-mekari-page .cashflow-summary-card,
.cashflow-mekari-page .cashflow-section-card,
.cashflow-mekari-page .cashflow-recon-card {
    border: 1px solid rgba(148,163,184,.24);
    border-radius: 24px;
    background: rgba(255,255,255,.98);
    box-shadow: 0 12px 30px rgba(15,23,42,.05);
}
.cashflow-mekari-page .cashflow-filter-card .form-label {
    font-weight: 700;
    color: #334155;
    font-size: .9rem;
}
.cashflow-mekari-page .cashflow-summary-card {
    padding: 1.1rem 1.25rem;
    height: 100%;
}
.cashflow-mekari-page .cashflow-summary-label {
    color: #64748b;
    font-size: .86rem;
    font-weight: 700;
    letter-spacing: .02em;
    margin-bottom: .35rem;
}
.cashflow-mekari-page .cashflow-summary-value {
    font-weight: 800;
    color: #0f172a;
    font-size: clamp(1.15rem, 2vw, 1.5rem);
}
.cashflow-mekari-page .cashflow-summary-note {
    margin-top: .35rem;
    color: #64748b;
    font-size: .82rem;
}
.cashflow-mekari-page .cashflow-section-card {
    overflow: hidden;
}
.cashflow-mekari-page .cashflow-section-head {
    padding: 1.1rem 1.35rem;
    border-bottom: 1px solid rgba(148,163,184,.18);
    background: linear-gradient(90deg, rgba(249,115,22,.08) 0%, rgba(255,255,255,.96) 65%);
}
.cashflow-mekari-page .cashflow-section-title {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    color: #ea580c;
    text-transform: uppercase;
    letter-spacing: .01em;
}
.cashflow-mekari-page .cashflow-section-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    gap: 1rem;
    padding: 1.25rem;
}
.cashflow-mekari-page .cashflow-flow-card {
    border: 1px solid rgba(148,163,184,.18);
    border-radius: 20px;
    overflow: hidden;
    background: #fff;
}
.cashflow-mekari-page .cashflow-flow-head {
    padding: .95rem 1rem;
    font-weight: 800;
    color: #0369a1;
    font-size: 1.05rem;
    border-bottom: 1px solid rgba(148,163,184,.18);
    background: linear-gradient(180deg, rgba(239,246,255,.95) 0%, rgba(255,255,255,.98) 100%);
}
.cashflow-mekari-page .cashflow-flow-table {
    width: 100%;
    border-collapse: collapse;
}
.cashflow-mekari-page .cashflow-flow-table td {
    padding: .88rem 1rem;
    border-top: 1px solid rgba(226,232,240,.85);
    vertical-align: top;
}
.cashflow-mekari-page .cashflow-flow-table td:last-child {
    width: 190px;
    text-align: right;
    white-space: nowrap;
    font-weight: 700;
    color: #0f172a;
}
.cashflow-mekari-page .cashflow-flow-empty {
    padding: 1rem;
    color: #64748b;
    font-size: .93rem;
}
.cashflow-mekari-page .cashflow-total-row td {
    font-weight: 800;
    background: #f8fafc;
}
.cashflow-mekari-page .cashflow-net-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 1rem;
    padding: 1rem 1.25rem 1.15rem;
    border-top: 1px solid rgba(148,163,184,.18);
    font-weight: 800;
    color: #0f172a;
}
.cashflow-mekari-page .cashflow-net-row .cashflow-net-label {
    color: #ea580c;
}
.cashflow-mekari-page .cashflow-recon-table {
    width: 100%;
    border-collapse: collapse;
}
.cashflow-mekari-page .cashflow-recon-table th,
.cashflow-mekari-page .cashflow-recon-table td {
    padding: .95rem 1rem;
    border-top: 1px solid rgba(226,232,240,.85);
}
.cashflow-mekari-page .cashflow-recon-table th {
    font-weight: 700;
    color: #334155;
}
.cashflow-mekari-page .cashflow-recon-table td {
    text-align: right;
    font-weight: 800;
    color: #0f172a;
}
.cashflow-mekari-page .cashflow-recon-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .4rem .8rem;
    font-weight: 700;
    font-size: .82rem;
}
.cashflow-mekari-page .cashflow-recon-badge.ok { background: #dcfce7; color: #166534; }
.cashflow-mekari-page .cashflow-recon-badge.warn { background: #fee2e2; color: #b91c1c; }
.cashflow-mekari-page .cashflow-empty-state {
    border: 1px dashed rgba(148,163,184,.4);
    border-radius: 24px;
    padding: 3.2rem 1.5rem;
    text-align: center;
    color: #64748b;
    background: rgba(255,255,255,.94);
}
@media (max-width: 991.98px) {
    .cashflow-mekari-page .cashflow-section-grid { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
    .cashflow-mekari-page .cashflow-hero,
    .cashflow-mekari-page .cashflow-filter-card .card-body,
    .cashflow-mekari-page .cashflow-section-head,
    .cashflow-mekari-page .cashflow-section-grid,
    .cashflow-mekari-page .cashflow-recon-card .card-body { padding: 1rem; }
    .cashflow-mekari-page .cashflow-flow-table td:last-child { width: 120px; }
    .cashflow-mekari-page .cashflow-title { font-size: 1.6rem; }
}
</style>

<div class="cashflow-mekari-page">
    <div class="cashflow-hero mb-4">
        <div class="cashflow-kicker">Laporan</div>
        <h1 class="cashflow-title"><?= e($cashFlowTitle) ?></h1>
        <p class="cashflow-subtitle"><?= e($cashFlowSubtitle) ?></p>
        <div class="cashflow-pillbar">
            <div class="cashflow-pill"><strong>Periode</strong> <?= e($periodLabel) ?></div>
            <div class="cashflow-pill"><strong>Unit</strong> <?= e($selectedUnitDisplay) ?></div>
            <div class="cashflow-pill"><strong>Metode</strong> Langsung</div>
        </div>
    </div>

    <div class="card cashflow-filter-card mb-4"><div class="card-body p-4">
        <form method="get" action="<?= e(base_url('/cash-flow')) ?>" class="row g-3 align-items-end">
            <div class="col-xl-4 col-lg-5"><label for="period_id" class="form-label">Periode Referensi</label><select name="period_id" id="period_id" class="form-select"><option value="">Opsional / bantu isi tanggal</option><?php foreach ($periods as $period): ?><option value="<?= e((string) $period['id']) ?>" <?= (string) ($filters['period_id'] ?? '') === (string) $period['id'] ? 'selected' : '' ?>><?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?></option><?php endforeach; ?></select></div>
            <div class="col-xl-2 col-lg-3"><label for="fiscal_year" class="form-label">Tahun</label><select name="fiscal_year" id="fiscal_year" class="form-select"><option value="">Semua tahun</option><?php foreach (($reportYears ?? []) as $year): ?><option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option><?php endforeach; ?></select></div>
            <div class="col-xl-3 col-lg-4"><label for="unit_id" class="form-label">Unit Usaha</label><select name="unit_id" id="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-xl-3 col-lg-4"><label for="date_from" class="form-label">Tanggal Mulai</label><input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) ($filters['date_from'] ?? '')) ?>"></div>
            <div class="col-xl-3 col-lg-4"><label for="date_to" class="form-label">Tanggal Akhir</label><input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) ($filters['date_to'] ?? '')) ?>"></div>
            <div class="col-xl-2 col-lg-4 d-grid"><button type="submit" class="btn btn-primary">Tampilkan</button></div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="col-xl-2 col-lg-4 d-grid"><a href="<?= e(base_url('/cash-flow/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-secondary">Print</a></div>
                <div class="col-xl-2 col-lg-4 d-grid"><a href="<?= e(base_url('/cash-flow/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-primary">Export PDF</a></div>
            <?php endif; ?>
        </form>
    </div></div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <?php foreach ((array) ($warnings ?? []) as $warning): ?>
            <div class="alert alert-warning mb-3"><?= e((string) $warning) ?></div>
        <?php endforeach; ?>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Saldo Kas Awal</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['opening_cash'] ?? 0))) ?></div><div class="cashflow-summary-note">Kas / bank pada awal periode laporan</div></div></div>
            <div class="col-xl-3 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Kas Bersih Operasi</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['total_operating'] ?? 0))) ?></div><div class="cashflow-summary-note">Dari aktivitas operasional</div></div></div>
            <div class="col-xl-3 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Kas Bersih Investasi</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['total_investing'] ?? 0))) ?></div><div class="cashflow-summary-note">Dari aktivitas investasi</div></div></div>
            <div class="col-xl-3 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Kas Bersih Pendanaan</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['total_financing'] ?? 0))) ?></div><div class="cashflow-summary-note">Dari aktivitas pendanaan</div></div></div>
            <div class="col-xl-6 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Kenaikan (Penurunan) Kas</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['net_cash_change'] ?? 0))) ?></div><div class="cashflow-summary-note">Perubahan bersih kas dalam periode</div></div></div>
            <div class="col-xl-6 col-md-6"><div class="cashflow-summary-card"><div class="cashflow-summary-label">Saldo Kas Akhir</div><div class="cashflow-summary-value"><?= e($currency((float) ($report['closing_cash'] ?? 0))) ?></div><div class="cashflow-summary-note">Saldo kas menurut laporan arus kas</div></div></div>
        </div>

        <?php foreach ($sectionCodes as $sectionCode): $sectionData = $sections[$sectionCode]; ?>
            <section class="cashflow-section-card mb-4">
                <div class="cashflow-section-head">
                    <h2 class="cashflow-section-title"><?= e((string) ($sectionData['title'] ?? ('Arus Kas dari ' . ($sectionOrder[$sectionCode] ?? $sectionCode)))) ?></h2>
                </div>
                <div class="cashflow-section-grid">
                    <div class="cashflow-flow-card">
                        <div class="cashflow-flow-head">Arus Kas Masuk</div>
                        <?php if (($sectionData['in_rows'] ?? []) === []): ?>
                            <div class="cashflow-flow-empty">Tidak ada penerimaan kas pada bagian ini.</div>
                        <?php else: ?>
                            <table class="cashflow-flow-table">
                                <tbody>
                                <?php foreach ((array) $sectionData['in_rows'] as $row): ?>
                                    <tr>
                                        <td><?= e((string) ($row['label'] ?? '-')) ?></td>
                                        <td><?= e($currency((float) ($row['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="cashflow-total-row"><td>Jumlah arus kas masuk</td><td><?= e($currency((float) ($sectionData['total_in'] ?? 0))) ?></td></tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="cashflow-flow-card">
                        <div class="cashflow-flow-head">Arus Kas Keluar</div>
                        <?php if (($sectionData['out_rows'] ?? []) === []): ?>
                            <div class="cashflow-flow-empty">Tidak ada pengeluaran kas pada bagian ini.</div>
                        <?php else: ?>
                            <table class="cashflow-flow-table">
                                <tbody>
                                <?php foreach ((array) $sectionData['out_rows'] as $row): ?>
                                    <tr>
                                        <td><?= e((string) ($row['label'] ?? '-')) ?></td>
                                        <td><?= e($currency((float) ($row['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="cashflow-total-row"><td>Jumlah arus kas keluar</td><td><?= e($currency((float) ($sectionData['total_out'] ?? 0))) ?></td></tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cashflow-net-row">
                    <span class="cashflow-net-label"><?= e((string) ($sectionData['net_label'] ?? 'Arus kas bersih')) ?></span>
                    <span><?= e($currency((float) ($sectionData['net'] ?? 0))) ?></span>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="card cashflow-recon-card mb-4"><div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Rekonsiliasi Kas</h2>
                    <div class="text-secondary small">Disusun agar saldo akhir laporan arus kas selaras dengan saldo kas / bank riil.</div>
                </div>
                <span class="cashflow-recon-badge <?= abs($difference) < 0.005 ? 'ok' : 'warn' ?>"><?= abs($difference) < 0.005 ? 'Sinkron' : 'Perlu ditinjau' ?></span>
            </div>
            <table class="cashflow-recon-table">
                <tbody>
                    <tr><th>Saldo kas awal</th><td><?= e($currency((float) ($report['opening_cash'] ?? 0))) ?></td></tr>
                    <tr><th>Kenaikan (penurunan) kas</th><td><?= e($currency((float) ($report['net_cash_change'] ?? 0))) ?></td></tr>
                    <tr><th>Saldo kas akhir menurut arus kas</th><td><?= e($currency((float) ($report['closing_cash'] ?? 0))) ?></td></tr>
                    <tr><th>Saldo kas / bank riil</th><td><?= e($currency($actualClosing)) ?></td></tr>
                    <tr><th>Selisih rekonsiliasi</th><td class="<?= abs($difference) < 0.005 ? 'text-success' : 'text-danger' ?>"><?= e($currency($difference)) ?></td></tr>
                </tbody>
            </table>
        </div></div>
    <?php else: ?>
        <div class="cashflow-empty-state">
            Pilih tanggal mulai dan tanggal akhir, lalu klik <strong>Tampilkan</strong> untuk melihat laporan arus kas.
        </div>
    <?php endif; ?>
</div>
