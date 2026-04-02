<?php declare(strict_types=1);
$user = Auth::user();
$profile = app_profile();
$trendSeries = $trend ?? [];
$trendMax = 0.0;
foreach ($trendSeries as $point) {
    $trendMax = max($trendMax, (float) ($point['total_revenue'] ?? 0), (float) ($point['total_expense'] ?? 0));
}
$summary = $summary ?? [];
$cashSummary = $cashSummary ?? [];
$recentJournals = $recentJournals ?? [];
$unitSummaries = $unitSummaries ?? [];
$filterErrors = $filterErrors ?? [];
$unitFeatureEnabled = (bool) ($unitFeatureEnabled ?? false);
$selectedUnitLabel = $unitFeatureEnabled ? business_unit_label($selectedUnit ?? null) : 'Semua Unit';
?>
<div class="dashboard-shell">
    <section class="dashboard-hero mb-4">
        <div class="dashboard-hero__content dashboard-hero__content--single">
            <div>
                <div class="dashboard-hero__eyebrow">Ringkasan Keuangan</div>
                <h1 class="dashboard-hero__title">Dashboard Eksekutif</h1>
                <p class="dashboard-hero__text">Pantau posisi keuangan <?= e($profile['bumdes_name'] ?: 'BUMDes') ?> dengan tampilan yang lebih rapi, nyaman dibaca, dan siap digunakan untuk operasional harian.</p>
                <div class="dashboard-hero__badges">
                    <span class="dashboard-badge"><?= e(($filters['range_mode'] ?? 'period_default') === 'manual' ? 'Rentang Manual' : current_accounting_period_label()) ?></span>
                    <span class="dashboard-badge dashboard-badge--soft">Rentang: <?= e($filters['range_label'] ?? '-') ?></span>
                    <span class="dashboard-badge dashboard-badge--soft">Unit: <?= e($selectedUnitLabel) ?></span>
                </div>
            </div>
        </div>
    </section>

    <div class="dashboard-filter card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                <div>
                    <h2 class="h5 mb-1">Filter Dashboard</h2>
                    <p class="text-secondary mb-0">Atur periode, unit usaha, dan rentang tanggal manual. Anda bisa melihat total lintas bulan, misalnya 1 Januari sampai 31 Maret, tanpa dibatasi periode terpilih.</p>
                </div>
                <a href="<?= e(base_url('/dashboard')) ?>" class="btn btn-outline-light">Reset Filter</a>
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
                    <label for="date_from" class="form-label">Tanggal Mulai Manual</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="date_to" class="form-label">Tanggal Akhir Manual</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-12 col-xl-2 d-grid">
                    <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                </div>
            </form>
            <?php if (!$unitFeatureEnabled): ?>
                <div class="alert alert-secondary mt-3 mb-0 small">Fitur unit usaha belum aktif penuh. Dashboard tetap berjalan dalam mode <strong>Semua Unit</strong>.</div>
            <?php endif; ?>
            <div class="alert alert-secondary mt-3 mb-0 small">
                <div class="fw-semibold mb-1">Cara kerja filter</div>
                <div>Periode default dipakai sebagai <strong>bulan acuan utama</strong> untuk dashboard. Jika Anda mengisi tanggal manual, dashboard akan menghitung sesuai rentang manual yang Anda tentukan sendiri.</div>
            </div>
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
        </div>
    </div>

    <section class="metric-grid mb-4">
        <article class="metric-card metric-card--blue">
            <div class="metric-card__label">Total Aset</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($summary['total_assets'] ?? 0))) ?></div>
            <div class="metric-card__meta">Saldo aset sampai <?= e((string) ($filters['date_to'] ?? '-')) ?></div>
        </article>
        <article class="metric-card metric-card--violet">
            <div class="metric-card__label">Total Pendapatan</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($summary['total_revenue'] ?? 0))) ?></div>
            <div class="metric-card__meta">Akumulasi pendapatan pada rentang dashboard</div>
        </article>
        <article class="metric-card metric-card--orange">
            <div class="metric-card__label">Total Beban</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($summary['total_expense'] ?? 0))) ?></div>
            <div class="metric-card__meta">Akumulasi beban pada rentang dashboard</div>
        </article>
        <article class="metric-card metric-card--teal">
            <div class="metric-card__label">Laba / Rugi Berjalan</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($summary['net_profit'] ?? 0))) ?></div>
            <div class="metric-card__meta">Pendapatan dikurangi beban</div>
        </article>
    </section>

    <section class="mini-stat-grid mb-4">
        <div class="card mini-stat-card"><div class="card-body p-4"><div class="mini-stat-card__label">Jumlah Jurnal</div><div class="mini-stat-card__value"><?= e(number_format((int) ($summary['journal_count'] ?? 0), 0, ',', '.')) ?></div><div class="mini-stat-card__meta">Jurnal pada rentang dashboard</div></div></div>
        <div class="card mini-stat-card"><div class="card-body p-4"><div class="mini-stat-card__label">Akun Aktif</div><div class="mini-stat-card__value"><?= e(number_format((int) ($summary['active_accounts'] ?? 0), 0, ',', '.')) ?></div><div class="mini-stat-card__meta"><?= e(number_format((int) ($summary['active_detail_accounts'] ?? 0), 0, ',', '.')) ?> akun detail aktif siap dipakai</div></div></div>
        <div class="card mini-stat-card"><div class="card-body p-4"><div class="mini-stat-card__label">Kas / Bank Bersih</div><div class="mini-stat-card__value"><?= e(dashboard_compact_currency((float) ($cashSummary['cash_balance'] ?? 0))) ?></div><div class="mini-stat-card__meta">Masuk <?= e(dashboard_compact_currency((float) ($cashSummary['cash_inflow'] ?? 0))) ?> · Keluar <?= e(dashboard_compact_currency((float) ($cashSummary['cash_outflow'] ?? 0))) ?></div></div></div>
    </section>

    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-12 col-xxl-8">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4 p-xl-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="h5 mb-1">Grafik Pendapatan vs Beban</h2>
                            <p class="text-secondary mb-0">Visual ringan enam bulan terakhir tanpa library chart tambahan.</p>
                        </div>
                        <div class="text-secondary small">Sumber data dari jurnal real di database</div>
                    </div>
                    <div class="dashboard-chart-modern">
                        <?php if ($trendSeries !== []): ?>
                            <?php foreach ($trendSeries as $point): ?>
                                <?php $revenuePercent = dashboard_bar_percent((float) $point['total_revenue'], $trendMax); $expensePercent = dashboard_bar_percent((float) $point['total_expense'], $trendMax); ?>
                                <div class="dashboard-chart-modern__item">
                                    <div class="dashboard-chart-modern__bars">
                                        <div class="dashboard-chart-modern__track"><div class="dashboard-chart-modern__bar dashboard-chart-modern__bar--revenue" style="height: <?= e((string) $revenuePercent) ?>%;"></div></div>
                                        <div class="dashboard-chart-modern__track"><div class="dashboard-chart-modern__bar dashboard-chart-modern__bar--expense" style="height: <?= e((string) $expensePercent) ?>%;"></div></div>
                                    </div>
                                    <div class="dashboard-chart-modern__label"><?= e(dashboard_month_label((string) $point['month_key'])) ?></div>
                                    <div class="dashboard-chart-modern__meta">Jurnal <?= e((string) $point['journal_count']) ?></div>
                                    <div class="dashboard-chart-modern__badge <?= e(dashboard_balance_badge_class((float) $point['net_profit'])) ?>"><?= e(dashboard_compact_currency((float) $point['net_profit'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">Belum ada data jurnal untuk membentuk grafik.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-4">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Ringkasan Kas / Bank</h2>
                        <a href="<?= e(base_url('/cash-flow')) ?>" class="btn btn-sm btn-outline-light">Lihat Arus Kas</a>
                    </div>
                    <?php if (($cashSummary['detected_accounts'] ?? []) !== []): ?>
                        <div class="dashboard-list-stack">
                            <?php foreach (($cashSummary['detected_accounts'] ?? []) as $cashAccount): ?>
                                <div class="dashboard-list-item">
                                    <div>
                                        <div class="dashboard-list-item__title"><?= e((string) $cashAccount['account_code']) ?></div>
                                        <div class="dashboard-list-item__meta"><?= e((string) $cashAccount['account_name']) ?></div>
                                    </div>
                                    <div class="dashboard-list-item__value"><?= e(dashboard_currency((float) ($cashAccount['balance'] ?? 0))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">Belum ada akun kas/bank yang terdeteksi dari COA. Pastikan nama akun mengandung kata <strong>Kas</strong> atau <strong>Bank</strong>.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xxl-7">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1">Jurnal Terbaru</h2>
                            <div class="text-secondary small">Riwayat jurnal dalam rentang dashboard yang sedang aktif.</div>
                        </div>
                        <a href="<?= e(base_url('/journals')) ?>" class="btn btn-sm btn-outline-light">Buka Jurnal</a>
                    </div>
                    <div class="table-responsive coa-table-wrapper">
                        <table class="table table-dark table-hover align-middle mb-0 coa-table dashboard-table">
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
                                        <td><?= e((string) $journal['journal_date']) ?></td>
                                        <td><a class="dashboard-link" href="<?= e(base_url('/journals/detail?id=' . (int) $journal['id'])) ?>"><?= e((string) $journal['journal_no']) ?></a></td>
                                        <td><?= e((string) $journal['description']) ?></td>
                                        <?php if ($unitFeatureEnabled): ?><td><?= e(trim((string) ($journal['unit_code'] ?? '')) !== '' ? ((string) $journal['unit_code'] . ' - ' . (string) ($journal['unit_name'] ?? '')) : 'Pusat / umum') ?></td><?php endif; ?>
                                        <td class="text-end fw-semibold"><?= e(dashboard_currency((float) ($journal['total_debit'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="<?= $unitFeatureEnabled ? '5' : '4' ?>" class="text-center text-secondary py-4">Belum ada jurnal pada rentang dashboard ini.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-5">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <?php if ($unitFeatureEnabled): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Ringkasan per Unit Usaha</h2>
                            <span class="text-secondary small">Mode multi unit</span>
                        </div>
                        <div class="table-responsive coa-table-wrapper">
                            <table class="table table-dark table-hover align-middle mb-0 coa-table dashboard-table">
                                <thead><tr><th>Unit</th><th class="text-end">Jurnal</th><th class="text-end">Laba/Rugi</th></tr></thead>
                                <tbody>
                                <?php if ($unitSummaries !== []): ?>
                                    <?php foreach ($unitSummaries as $unitRow): ?>
                                        <tr>
                                            <td><?= e((string) $unitRow['unit_code'] . ' - ' . (string) $unitRow['unit_name']) ?></td>
                                            <td class="text-end"><?= e(number_format((int) $unitRow['journal_count'], 0, ',', '.')) ?></td>
                                            <td class="text-end"><?= e(dashboard_currency(((float) $unitRow['total_revenue']) - ((float) $unitRow['total_expense']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada unit usaha aktif.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <h2 class="h5 mb-3">Akses Cepat</h2>
                        <div class="quick-links-grid">
                            <a href="<?= e(base_url('/profit-loss')) ?>" class="quick-link-card"><span class="quick-link-card__title">Laporan Laba Rugi</span><span class="quick-link-card__meta">Pantau pendapatan, beban, dan laba/rugi berjalan.</span></a>
                            <a href="<?= e(base_url('/balance-sheet')) ?>" class="quick-link-card"><span class="quick-link-card__title">Laporan Neraca</span><span class="quick-link-card__meta">Lihat total aset, liabilitas, dan ekuitas.</span></a>
                            <a href="<?= e(base_url('/ledger')) ?>" class="quick-link-card"><span class="quick-link-card__title">Buku Besar</span><span class="quick-link-card__meta">Telusuri mutasi akun dengan saldo berjalan.</span></a>
                            <a href="<?= e(base_url('/trial-balance')) ?>" class="quick-link-card"><span class="quick-link-card__title">Neraca Saldo</span><span class="quick-link-card__meta">Ringkasan saldo akhir semua akun yang relevan.</span></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
