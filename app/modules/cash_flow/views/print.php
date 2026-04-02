<?php declare(strict_types=1);

$cashFlowTitle = 'Laporan Arus Kas';
$currency = static function (float $amount): string {
    return ledger_currency_print($amount);
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

$difference = (float) ($report['difference'] ?? 0.0);
$actualClosing = (float) ($report['actual_closing_cash'] ?? ($report['closing_cash'] ?? 0.0));
?>
<div class="print-sheet classic-report portrait-report cashflow-print-official">
    <?php render_print_header($profile, $cashFlowTitle, report_period_label($filters, $selectedPeriod), $selectedUnitLabel ?? 'Semua Unit'); ?>

    <div class="cashflow-print-heading text-center mb-3">
        <div class="cashflow-print-title">LAPORAN ARUS KAS</div>
        <div class="cashflow-print-subtitle">Metode langsung · disajikan dalam rupiah</div>
    </div>

    <?php if (($warnings ?? []) !== []): ?>
        <div class="cashflow-print-warning mb-3">
            <?php foreach ((array) $warnings as $warning): ?>
                <div>• <?= e((string) $warning) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered cashflow-print-summary mb-3">
        <tbody>
            <tr>
                <th>Saldo kas awal</th>
                <td class="text-end nowrap"><?= e($currency((float) ($report['opening_cash'] ?? 0))) ?></td>
                <th>Kenaikan (penurunan) kas</th>
                <td class="text-end nowrap"><?= e($currency((float) ($report['net_cash_change'] ?? 0))) ?></td>
            </tr>
            <tr>
                <th>Saldo kas akhir menurut arus kas</th>
                <td class="text-end nowrap"><?= e($currency((float) ($report['closing_cash'] ?? 0))) ?></td>
                <th>Saldo kas / bank riil</th>
                <td class="text-end nowrap"><?= e($currency($actualClosing)) ?></td>
            </tr>
        </tbody>
    </table>

    <?php foreach ($sectionCodes as $sectionCode): $sectionData = $sections[$sectionCode]; ?>
        <div class="cashflow-print-section-title"><?= e((string) ($sectionData['title'] ?? ('Arus Kas dari ' . ($sectionOrder[$sectionCode] ?? $sectionCode)))) ?></div>
        <table class="table table-bordered cashflow-print-table mb-3">
            <tbody>
                <tr class="subhead-row"><th colspan="2">Arus Kas Masuk</th></tr>
                <?php if (($sectionData['in_rows'] ?? []) === []): ?>
                    <tr><td colspan="2" class="text-center">Tidak ada penerimaan kas pada bagian ini.</td></tr>
                <?php else: foreach ((array) $sectionData['in_rows'] as $row): ?>
                    <tr><td><?= e((string) ($row['label'] ?? '-')) ?></td><td class="text-end nowrap"><?= e($currency((float) ($row['amount'] ?? 0))) ?></td></tr>
                <?php endforeach; endif; ?>
                <tr class="total-row"><th>Jumlah arus kas masuk</th><th class="text-end nowrap"><?= e($currency((float) ($sectionData['total_in'] ?? 0))) ?></th></tr>
                <tr class="subhead-row"><th colspan="2">Arus Kas Keluar</th></tr>
                <?php if (($sectionData['out_rows'] ?? []) === []): ?>
                    <tr><td colspan="2" class="text-center">Tidak ada pengeluaran kas pada bagian ini.</td></tr>
                <?php else: foreach ((array) $sectionData['out_rows'] as $row): ?>
                    <tr><td><?= e((string) ($row['label'] ?? '-')) ?></td><td class="text-end nowrap"><?= e($currency((float) ($row['amount'] ?? 0))) ?></td></tr>
                <?php endforeach; endif; ?>
                <tr class="total-row"><th>Jumlah arus kas keluar</th><th class="text-end nowrap"><?= e($currency((float) ($sectionData['total_out'] ?? 0))) ?></th></tr>
                <tr class="net-row"><th><?= e((string) ($sectionData['net_label'] ?? 'Arus kas bersih')) ?></th><th class="text-end nowrap"><?= e($currency((float) ($sectionData['net'] ?? 0))) ?></th></tr>
            </tbody>
        </table>
    <?php endforeach; ?>

    <table class="table table-bordered cashflow-print-summary mb-0">
        <tbody>
            <tr><th>Selisih rekonsiliasi</th><td class="text-end nowrap <?= abs($difference) < 0.005 ? 'text-success' : 'text-danger' ?>"><?= e($currency($difference)) ?></td></tr>
        </tbody>
    </table>

    <?php render_print_signature($profile); ?>
</div>
<style>
.cashflow-print-official { font-size: 12px; }
.cashflow-print-title { font-size: 18px; font-weight: 800; letter-spacing: .04em; }
.cashflow-print-subtitle { font-size: 11px; color: #334155; margin-top: 2px; }
.cashflow-print-warning {
    border: 1px solid #c2410c;
    background: #fff7ed;
    color: #9a3412;
    padding: 8px 10px;
    font-size: 11px;
}
.cashflow-print-section-title {
    margin: 14px 0 6px;
    font-weight: 800;
    font-size: 13px;
    color: #ea580c;
    text-transform: uppercase;
}
.cashflow-print-summary,
.cashflow-print-table { font-size: 12px; }
.cashflow-print-summary th,
.cashflow-print-summary td,
.cashflow-print-table th,
.cashflow-print-table td {
    border: 1px solid #334155 !important;
    padding: 6px 8px !important;
    background: #fff;
}
.cashflow-print-summary th { width: 28%; background: #f8fafc; }
.cashflow-print-table .subhead-row th { background: #eff6ff; font-weight: 800; color: #0f172a; }
.cashflow-print-table .total-row th,
.cashflow-print-table .total-row td { background: #f8fafc; font-weight: 800; }
.cashflow-print-table .net-row th,
.cashflow-print-table .net-row td { background: #fff7ed; font-weight: 800; color: #9a3412; }
.nowrap { white-space: nowrap; }
</style>
<script>window.print();</script>
