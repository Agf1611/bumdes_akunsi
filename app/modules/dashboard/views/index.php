<?php declare(strict_types=1);

$user = Auth::user();
$profile = app_profile();
$trendSeries = is_array($trend ?? null) ? $trend : [];
$summary = is_array($summary ?? null) ? $summary : [];
$cashSummary = is_array($cashSummary ?? null) ? $cashSummary : [];
$recentJournals = is_array($recentJournals ?? null) ? $recentJournals : [];
$unitSummaries = is_array($unitSummaries ?? null) ? $unitSummaries : [];
$filterErrors = is_array($filterErrors ?? null) ? $filterErrors : [];
$unitFeatureEnabled = (bool) ($unitFeatureEnabled ?? false);
$selectedUnitLabel = $unitFeatureEnabled ? business_unit_label($selectedUnit ?? null) : 'Semua Unit';
$taskCenter = is_array($taskCenter ?? null) ? $taskCenter : [];
$workspaceRecentItems = is_array($workspaceRecentItems ?? null) ? $workspaceRecentItems : [];
$workspaceFavoritePages = is_array($workspaceFavoritePages ?? null) ? $workspaceFavoritePages : [];
$workspaceSavedFilters = is_array($workspaceSavedFilters ?? null) ? $workspaceSavedFilters : [];

$hour = (int) date('G');
$greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
$displayName = trim((string) ($user['full_name'] ?? 'Admin BUMDes')) !== '' ? (string) ($user['full_name'] ?? 'Admin BUMDes') : 'Admin BUMDes';
$dateContext = dashboard_date_label((string) ($filters['date_to'] ?? date('Y-m-d')), (string) ($filters['date_to'] ?? date('Y-m-d')));

$trendMax = 0.0;
foreach ($trendSeries as $point) {
    $trendMax = max(
        $trendMax,
        (float) ($point['total_revenue'] ?? 0),
        (float) ($point['total_expense'] ?? 0)
    );
}
$trendMax = max($trendMax, 1);

$buildPercentChange = static function (float $current, float $previous): array {
    if (abs($previous) < 0.0001) {
        return [
            'value' => 'Data awal',
            'direction' => 'neutral',
        ];
    }

    $delta = (($current - $previous) / abs($previous)) * 100;
    $direction = $delta > 0.0001 ? 'up' : ($delta < -0.0001 ? 'down' : 'neutral');

    return [
        'value' => number_format(abs($delta), 1, ',', '.') . '%',
        'direction' => $direction,
    ];
};

$currentTrend = $trendSeries !== [] ? $trendSeries[array_key_last($trendSeries)] : ['total_revenue' => 0, 'total_expense' => 0, 'net_profit' => 0];
$previousTrend = count($trendSeries) > 1 ? $trendSeries[count($trendSeries) - 2] : ['total_revenue' => 0, 'total_expense' => 0, 'net_profit' => 0];
$cashInflow = (float) ($cashSummary['cash_inflow'] ?? 0);
$cashOutflow = (float) ($cashSummary['cash_outflow'] ?? 0);

$kpis = [
    [
        'title' => 'Kas & Bank',
        'value' => dashboard_currency_whole((float) ($cashSummary['cash_balance'] ?? 0)),
        'meta' => $buildPercentChange($cashInflow, $cashOutflow),
        'note' => 'Arus kas masuk vs keluar pada rentang aktif',
        'icon' => 'wallet',
        'tone' => 'blue',
    ],
    [
        'title' => 'Pendapatan',
        'value' => dashboard_currency_whole((float) ($summary['total_revenue'] ?? 0)),
        'meta' => $buildPercentChange((float) ($currentTrend['total_revenue'] ?? 0), (float) ($previousTrend['total_revenue'] ?? 0)),
        'note' => 'Dibanding bulan sebelumnya',
        'icon' => 'revenue',
        'tone' => 'green',
    ],
    [
        'title' => 'Beban',
        'value' => dashboard_currency_whole((float) ($summary['total_expense'] ?? 0)),
        'meta' => $buildPercentChange((float) ($currentTrend['total_expense'] ?? 0), (float) ($previousTrend['total_expense'] ?? 0)),
        'note' => 'Beban operasional berjalan',
        'icon' => 'expense',
        'tone' => 'orange',
    ],
    [
        'title' => 'Laba Bersih',
        'value' => dashboard_currency_whole((float) ($summary['net_profit'] ?? 0)),
        'meta' => $buildPercentChange((float) ($currentTrend['net_profit'] ?? 0), (float) ($previousTrend['net_profit'] ?? 0)),
        'note' => 'Pendapatan dikurangi beban',
        'icon' => 'profit',
        'tone' => 'indigo',
    ],
];

$summaryRows = [
    ['label' => 'Kas & Bank', 'value' => dashboard_currency_whole((float) ($cashSummary['cash_balance'] ?? 0))],
    ['label' => 'Total Aset', 'value' => dashboard_currency_whole((float) ($summary['total_assets'] ?? 0)), 'strong' => true],
    ['label' => 'Pendapatan', 'value' => dashboard_currency_whole((float) ($summary['total_revenue'] ?? 0))],
    ['label' => 'Beban', 'value' => dashboard_currency_whole((float) ($summary['total_expense'] ?? 0))],
    ['label' => 'Laba Bersih', 'value' => dashboard_currency_whole((float) ($summary['net_profit'] ?? 0)), 'strong' => true],
    ['label' => 'Jumlah Jurnal', 'value' => number_format((int) ($summary['journal_count'] ?? 0), 0, ',', '.')],
];

$reportLinks = [
    ['title' => 'Laporan Laba Rugi', 'meta' => 'Periode ' . current_accounting_period_label(), 'path' => '/profit-loss', 'icon' => 'profit', 'tone' => 'indigo'],
    ['title' => 'Laporan Posisi Keuangan', 'meta' => 'Aset dan posisi saldo', 'path' => '/balance-sheet', 'icon' => 'balance', 'tone' => 'blue'],
    ['title' => 'Laporan Arus Kas', 'meta' => 'Mutasi kas dan bank', 'path' => '/cash-flow', 'icon' => 'cash', 'tone' => 'green'],
    ['title' => 'Buku Besar', 'meta' => 'Riwayat akun dan saldo', 'path' => '/ledger', 'icon' => 'ledger', 'tone' => 'slate'],
    ['title' => 'Neraca Saldo', 'meta' => 'Kontrol debit dan kredit', 'path' => '/trial-balance', 'icon' => 'trial', 'tone' => 'orange'],
];

$utilityColumns = [
    [
        'title' => 'Halaman Favorit',
        'empty' => 'Belum ada favorit. Simpan halaman penting dari topbar agar cepat dibuka lagi.',
        'items' => array_map(
            static fn (array $item): array => [
                'label' => (string) ($item['title'] ?? 'Favorit'),
                'path' => (string) ($item['path'] ?? '/dashboard'),
            ],
            array_slice($workspaceFavoritePages, 0, 4)
        ),
    ],
    [
        'title' => 'Item Terakhir Dibuka',
        'empty' => 'Belum ada histori halaman yang dibuka pada akun ini.',
        'items' => array_map(
            static fn (array $item): array => [
                'label' => (string) ($item['title'] ?? 'Halaman'),
                'path' => (string) ($item['path'] ?? '/dashboard'),
            ],
            array_slice($workspaceRecentItems, 0, 4)
        ),
    ],
    [
        'title' => 'Filter Tersimpan',
        'empty' => 'Belum ada filter tersimpan. Simpan filter dari topbar saat Anda sedang memfilter halaman.',
        'items' => array_map(
            static fn (array $item): array => [
                'label' => (string) ($item['name'] ?? 'Filter'),
                'path' => (string) ($item['path'] ?? '/dashboard'),
            ],
            array_slice($workspaceSavedFilters, 0, 4)
        ),
    ],
];

$icon = static function (string $name): string {
    $icons = [
        'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5V9"></path><path d="M4 9h15a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H4a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1Z"></path><path d="M16 13h.01"></path></svg>',
        'revenue' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"></path><path d="M9 7h8v8"></path><circle cx="7" cy="17" r="2"></circle></svg>',
        'expense' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h10v10"></path><path d="m17 7-10 10"></path><path d="M17 17H7V7"></path></svg>',
        'profit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 7-8"></path><path d="M14 7h6v6"></path></svg>',
        'ledger' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path><path d="M8 5v14"></path></svg>',
        'balance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"></path><path d="M5 7h5"></path><path d="M14 7h5"></path><path d="M3 7a2 2 0 0 0 4 0"></path><path d="M17 7a2 2 0 0 0 4 0"></path><path d="M5 7l-2 7"></path><path d="M19 7l2 7"></path><path d="M1 14h6"></path><path d="M17 14h6"></path></svg>',
        'cash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M7 6v12"></path><path d="M17 6v12"></path></svg>',
        'trial' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M4 12h10"></path><path d="M4 17h16"></path><path d="M17 10l3 2-3 2"></path></svg>',
        'spark' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 16 4-5 3 3 5-7"></path><path d="M18 7h3v3"></path></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'chevron' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"></path></svg>',
    ];

    return $icons[$name] ?? $icons['spark'];
};

$chartWidth = 640;
$chartHeight = 260;
$paddingLeft = 22;
$paddingRight = 20;
$paddingTop = 20;
$paddingBottom = 38;
$plotWidth = max($chartWidth - $paddingLeft - $paddingRight, 1);
$plotHeight = max($chartHeight - $paddingTop - $paddingBottom, 1);
$pointCount = max(count($trendSeries), 1);
$stepX = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : 0;
$gridLevels = 4;
$revenuePoints = [];
$expensePoints = [];

foreach ($trendSeries as $index => $point) {
    $x = $paddingLeft + ($stepX * $index);
    $revenueY = $paddingTop + ($plotHeight - ((((float) ($point['total_revenue'] ?? 0)) / $trendMax) * $plotHeight));
    $expenseY = $paddingTop + ($plotHeight - ((((float) ($point['total_expense'] ?? 0)) / $trendMax) * $plotHeight));
    $revenuePoints[] = number_format($x, 2, '.', '') . ',' . number_format($revenueY, 2, '.', '');
    $expensePoints[] = number_format($x, 2, '.', '') . ',' . number_format($expenseY, 2, '.', '');
}
?>
<div class="dashboard-shell dashboard-shell--executive">
    <section class="dashboard-welcome">
        <div>
            <div class="dashboard-welcome__eyebrow">Ringkasan Eksekutif</div>
            <h1 class="dashboard-welcome__title"><?= e($greeting) ?>, <?= e($displayName) ?>!</h1>
            <p class="dashboard-welcome__text">Berikut ringkasan keuangan <?= e($profile['bumdes_name'] ?: 'BUMDes') ?> untuk <?= e($dateContext) ?>. Tampilan ini difokuskan agar cepat dipindai, nyaman dibaca, dan tidak melelahkan saat dipakai harian.</p>
        </div>
        <div class="dashboard-welcome__actions">
            <a href="#dashboardFilters" class="btn btn-outline-light">
                <span class="dashboard-inline-icon"><?= $icon('settings') ?></span>
                Atur Dashboard
            </a>
            <?php if ((string) ($user['role_code'] ?? '') === 'admin'): ?>
                <form method="post" action="<?= e(base_url('/backups/create')) ?>" class="m-0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="redirect_to" value="/dashboard">
                    <input type="hidden" name="backup_reason" value="dashboard_quick_safety_backup">
                    <button type="submit" class="btn btn-outline-light">Amankan Data</button>
                </form>
            <?php endif; ?>
            <a href="<?= e(base_url('/journals/quick')) ?>" class="btn btn-primary">Transaksi Cepat</a>
        </div>
    </section>

    <section class="dashboard-filter-panel" id="dashboardFilters">
        <div class="dashboard-filter-panel__head">
            <div>
                <h2 class="dashboard-panel__title">Filter Dashboard</h2>
                <p class="dashboard-panel__meta">Pilih periode, unit usaha, dan rentang tanggal agar ringkasan dashboard sesuai kebutuhan Anda.</p>
            </div>
            <div class="dashboard-filter-panel__actions">
                <span class="dashboard-filter-panel__hint"><?= e(current_accounting_period_label()) ?> · <?= e($selectedUnitLabel) ?></span>
                <a href="<?= e(base_url('/dashboard')) ?>" class="btn btn-outline-light btn-sm">Reset Filter</a>
            </div>
        </div>
        <form method="get" action="<?= e(base_url('/dashboard')) ?>" class="row g-3 align-items-end">
            <div class="col-12 col-xl-3">
                <label for="period_id" class="form-label">Periode Default</label>
                <select class="form-select" id="period_id" name="period_id">
                    <option value="0">Gunakan periode aktif / default</option>
                    <?php foreach (($periods ?? []) as $period): ?>
                        <option value="<?= e((string) $period['id']) ?>" <?= (int) ($filters['period_id'] ?? 0) === (int) $period['id'] ? 'selected' : '' ?>>
                            <?= e(($period['period_code'] ?? '') . ' - ' . ($period['period_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($unitFeatureEnabled): ?>
                <div class="col-12 col-xl-3">
                    <label for="unit_id" class="form-label">Unit Usaha</label>
                    <select class="form-select" id="unit_id" name="unit_id">
                        <option value="0">Semua Unit</option>
                        <?php foreach (($units ?? []) as $unit): ?>
                            <option value="<?= e((string) $unit['id']) ?>" <?= (int) ($filters['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>><?= e(($unit['unit_code'] ?? '') . ' - ' . ($unit['unit_name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="date_from" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="date_to" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 col-xl-2 d-grid">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
            </div>
        </form>
        <?php if (!$unitFeatureEnabled): ?>
            <div class="alert alert-secondary mt-3 mb-0 small">Fitur unit usaha belum aktif penuh. Dashboard tetap berjalan dalam mode <strong>Semua Unit</strong>.</div>
        <?php endif; ?>
        <?php if ($filterErrors !== []): ?>
            <div class="alert alert-warning mt-3 mb-0 small">
                <div class="fw-semibold mb-1">Perhatian filter</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($filterErrors as $filterError): ?>
                        <li><?= e($filterError) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>

    <section class="dashboard-kpi-grid">
        <?php foreach ($kpis as $kpi): ?>
            <article class="dashboard-kpi-card dashboard-kpi-card--<?= e((string) $kpi['tone']) ?>">
                <span class="dashboard-kpi-card__icon"><?= $icon((string) $kpi['icon']) ?></span>
                <div class="dashboard-kpi-card__content">
                    <div class="dashboard-kpi-card__label"><?= e((string) $kpi['title']) ?></div>
                    <div class="dashboard-kpi-card__value"><?= e((string) $kpi['value']) ?></div>
                    <div class="dashboard-kpi-card__meta dashboard-kpi-card__meta--<?= e((string) ($kpi['meta']['direction'] ?? 'neutral')) ?>">
                        <span><?= e((string) ($kpi['meta']['direction'] === 'up' ? 'Naik' : (($kpi['meta']['direction'] === 'down') ? 'Turun' : 'Stabil'))) ?></span>
                        <span><?= e((string) ($kpi['meta']['value'] ?? '-')) ?></span>
                        <span class="dashboard-kpi-card__note"><?= e((string) $kpi['note']) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-main-grid">
        <article class="dashboard-panel dashboard-panel--chart">
            <div class="dashboard-panel__head">
                <div>
                    <h2 class="dashboard-panel__title">Tren Pendapatan & Beban</h2>
                    <p class="dashboard-panel__meta">Enam bulan terakhir dari jurnal yang tersimpan di sistem.</p>
                </div>
                <div class="dashboard-legend">
                    <span class="dashboard-legend__item"><span class="dashboard-legend__dot dashboard-legend__dot--revenue"></span>Pendapatan</span>
                    <span class="dashboard-legend__item"><span class="dashboard-legend__dot dashboard-legend__dot--expense"></span>Beban</span>
                </div>
            </div>
            <div class="dashboard-line-chart">
                <?php if ($trendSeries !== []): ?>
                    <svg viewBox="0 0 <?= e((string) $chartWidth) ?> <?= e((string) $chartHeight) ?>" class="dashboard-line-chart__svg" aria-hidden="true">
                        <?php for ($level = 0; $level <= $gridLevels; $level++): ?>
                            <?php
                            $y = $paddingTop + (($plotHeight / $gridLevels) * $level);
                            $valueLabel = dashboard_compact_currency((($gridLevels - $level) / $gridLevels) * $trendMax);
                            ?>
                            <line x1="<?= e((string) $paddingLeft) ?>" y1="<?= e((string) $y) ?>" x2="<?= e((string) ($chartWidth - $paddingRight)) ?>" y2="<?= e((string) $y) ?>" class="dashboard-line-chart__grid"></line>
                            <text x="0" y="<?= e((string) ($y + 4)) ?>" class="dashboard-line-chart__axis"><?= e($valueLabel) ?></text>
                        <?php endfor; ?>
                        <polyline points="<?= e(implode(' ', $expensePoints)) ?>" class="dashboard-line-chart__path dashboard-line-chart__path--expense"></polyline>
                        <polyline points="<?= e(implode(' ', $revenuePoints)) ?>" class="dashboard-line-chart__path dashboard-line-chart__path--revenue"></polyline>
                        <?php foreach ($trendSeries as $index => $point): ?>
                            <?php
                            $x = $paddingLeft + ($stepX * $index);
                            $revenueY = $paddingTop + ($plotHeight - ((((float) ($point['total_revenue'] ?? 0)) / $trendMax) * $plotHeight));
                            $expenseY = $paddingTop + ($plotHeight - ((((float) ($point['total_expense'] ?? 0)) / $trendMax) * $plotHeight));
                            ?>
                            <circle cx="<?= e((string) $x) ?>" cy="<?= e((string) $expenseY) ?>" r="4" class="dashboard-line-chart__point dashboard-line-chart__point--expense"></circle>
                            <circle cx="<?= e((string) $x) ?>" cy="<?= e((string) $revenueY) ?>" r="4" class="dashboard-line-chart__point dashboard-line-chart__point--revenue"></circle>
                        <?php endforeach; ?>
                    </svg>
                    <div class="dashboard-line-chart__labels">
                        <?php foreach ($trendSeries as $point): ?>
                            <span><?= e(dashboard_month_label((string) ($point['month_key'] ?? ''))) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-panel empty-state-panel--compact">
                        <div class="empty-state-panel__title">Belum ada tren yang bisa digambar</div>
                        <div class="empty-state-panel__text">Tambahkan jurnal pada periode aktif agar grafik pendapatan dan beban muncul di dashboard.</div>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <aside class="dashboard-panel dashboard-panel--summary">
            <div class="dashboard-panel__head">
                <div>
                    <h2 class="dashboard-panel__title">Posisi Keuangan</h2>
                    <p class="dashboard-panel__meta">Ringkasan saldo utama pada periode yang sedang dipakai.</p>
                </div>
            </div>
            <div class="dashboard-summary-list">
                <?php foreach ($summaryRows as $row): ?>
                    <div class="dashboard-summary-list__row<?= !empty($row['strong']) ? ' is-strong' : '' ?>">
                        <span><?= e((string) $row['label']) ?></span>
                        <strong><?= e((string) $row['value']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="dashboard-summary-note">
                <span class="dashboard-summary-note__icon"><?= $icon('spark') ?></span>
                <div>
                    <strong>Unit aktif</strong>
                    <span><?= e($selectedUnitLabel) ?></span>
                </div>
            </div>
        </aside>
    </section>

    <section class="dashboard-lower-grid">
        <article class="dashboard-panel dashboard-panel--journals">
            <div class="dashboard-panel__head">
                <div>
                    <h2 class="dashboard-panel__title">Jurnal Terakhir</h2>
                    <p class="dashboard-panel__meta">Transaksi terbaru dalam rentang dashboard yang sedang aktif.</p>
                </div>
                <a href="<?= e(base_url('/journals')) ?>" class="dashboard-inline-link">Lihat Semua</a>
            </div>
            <div class="table-responsive dashboard-table-shell">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Keterangan</th>
                        <?php if ($unitFeatureEnabled): ?><th>Unit</th><?php endif; ?>
                        <th class="text-end">Nominal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentJournals !== []): ?>
                        <?php foreach ($recentJournals as $journal): ?>
                            <tr>
                                <td><?= e((string) ($journal['journal_date'] ?? '-')) ?></td>
                                <td><a class="dashboard-inline-link" href="<?= e(base_url('/journals/detail?id=' . (int) ($journal['id'] ?? 0))) ?>"><?= e((string) ($journal['journal_no'] ?? '-')) ?></a></td>
                                <td><?= e((string) ($journal['description'] ?? '-')) ?></td>
                                <?php if ($unitFeatureEnabled): ?>
                                    <td><?= e(trim((string) ($journal['unit_code'] ?? '')) !== '' ? ((string) $journal['unit_code'] . ' - ' . (string) ($journal['unit_name'] ?? '')) : 'Pusat / umum') ?></td>
                                <?php endif; ?>
                                <td class="text-end fw-semibold"><?= e(dashboard_currency_whole((float) ($journal['total_debit'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $unitFeatureEnabled ? '5' : '4' ?>" class="text-center py-4">Belum ada jurnal pada rentang dashboard ini.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="dashboard-panel__foot">
                <span>Menampilkan <?= e((string) count($recentJournals)) ?> jurnal terbaru</span>
                <a href="<?= e(base_url('/journals')) ?>" class="dashboard-inline-link">Buka modul jurnal</a>
            </div>
        </article>

        <div class="dashboard-side-stack">
            <article class="dashboard-panel dashboard-panel--reports">
                <div class="dashboard-panel__head">
                    <div>
                        <h2 class="dashboard-panel__title">Laporan Populer</h2>
                        <p class="dashboard-panel__meta">Akses cepat ke laporan yang paling sering dipakai.</p>
                    </div>
                    <a href="<?= e(base_url('/profit-loss')) ?>" class="dashboard-inline-link">Lihat Semua</a>
                </div>
                <div class="dashboard-report-list">
                    <?php foreach ($reportLinks as $report): ?>
                        <a href="<?= e(base_url((string) $report['path'])) ?>" class="dashboard-report-list__item">
                            <span class="dashboard-report-list__icon dashboard-report-list__icon--<?= e((string) ($report['tone'] ?? 'blue')) ?>"><?= $icon((string) $report['icon']) ?></span>
                            <span class="dashboard-report-list__copy">
                                <strong><?= e((string) $report['title']) ?></strong>
                                <span><?= e((string) $report['meta']) ?></span>
                            </span>
                            <span class="dashboard-report-list__chevron"><?= $icon('chevron') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="dashboard-panel dashboard-panel--tasks">
                <div class="dashboard-panel__head">
                    <div>
                        <h2 class="dashboard-panel__title">Task Center Operasional</h2>
                        <p class="dashboard-panel__meta">Pekerjaan prioritas agar pengguna tidak perlu menebak langkah berikutnya.</p>
                    </div>
                    <a href="<?= e(base_url('/periods')) ?>" class="dashboard-inline-link">Review</a>
                </div>
                <div class="dashboard-task-list">
                    <?php foreach (array_slice($taskCenter, 0, 4) as $task): ?>
                        <a href="<?= e(base_url((string) ($task['url'] ?? '/dashboard'))) ?>" class="dashboard-task-list__item">
                            <span class="dashboard-task-list__copy">
                                <strong><?= e((string) ($task['title'] ?? '-')) ?></strong>
                                <span><?= e((string) ($task['note'] ?? '')) ?></span>
                            </span>
                            <span class="dashboard-task-list__meta">
                                <span class="dashboard-task-list__badge status-<?= e((string) ($task['status'] ?? 'warning')) ?>"><?= e((string) ($task['value'] ?? '-')) ?></span>
                                <span class="dashboard-task-list__chevron"><?= $icon('chevron') ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>

    <section class="dashboard-utility-grid">
        <?php foreach ($utilityColumns as $column): ?>
            <article class="dashboard-panel dashboard-panel--utility">
                <div class="dashboard-panel__head">
                    <h2 class="dashboard-panel__title"><?= e((string) $column['title']) ?></h2>
                </div>
                <?php if ($column['items'] === []): ?>
                    <div class="dashboard-utility-empty"><?= e((string) $column['empty']) ?></div>
                <?php else: ?>
                    <div class="dashboard-utility-list">
                        <?php foreach ($column['items'] as $item): ?>
                            <a href="<?= e(base_url((string) $item['path'])) ?>" class="dashboard-utility-list__item"><?= e((string) $item['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</div>
