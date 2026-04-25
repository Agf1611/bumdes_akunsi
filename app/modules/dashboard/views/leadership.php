<?php declare(strict_types=1);
$user = Auth::user();
$profile = app_profile();
$summary = $summary ?? [];
$cashSummary = $cashSummary ?? [];
$recentJournals = $recentJournals ?? [];
$topRevenueAccounts = $topRevenueAccounts ?? [];
$topExpenseAccounts = $topExpenseAccounts ?? [];
$unitSummaries = $unitSummaries ?? [];
$filterErrors = $filterErrors ?? [];
$closingChecklist = is_array($closingChecklist ?? null) ? $closingChecklist : null;
$unitFeatureEnabled = (bool) ($unitFeatureEnabled ?? false);
$selectedUnitLabel = $unitFeatureEnabled ? business_unit_label($selectedUnit ?? null) : 'Semua Unit';
$readiness = $closingChecklist['is_ready_to_close'] ?? false;
$criticalFailures = (int) ($closingChecklist['critical_failures'] ?? 0);
$warnings = (int) ($closingChecklist['warnings'] ?? 0);
$checks = $closingChecklist['checks'] ?? [];
$topChecks = array_slice($checks, 0, 4);
$taskCenter = is_array($taskCenter ?? null) ? $taskCenter : [];
$workspaceRecentItems = is_array($workspaceRecentItems ?? null) ? $workspaceRecentItems : [];
$workspaceFavoritePages = is_array($workspaceFavoritePages ?? null) ? $workspaceFavoritePages : [];
?>
<div class="dashboard-shell">
    <section class="dashboard-hero mb-4">
        <div class="dashboard-hero__content dashboard-hero__content--single">
            <div>
                <div class="dashboard-hero__eyebrow">Ringkasan Pimpinan</div>
                <h1 class="dashboard-hero__title">Dashboard Pimpinan</h1>
                <p class="dashboard-hero__text">Ringkasan cepat untuk pimpinan <?= e($profile['bumdes_name'] ?: 'BUMDes') ?>: laba rugi berjalan, posisi kas, indikator kesiapan tutup buku, dan titik perhatian utama tanpa harus membuka semua laporan satu per satu.</p>
                <div class="dashboard-hero__badges">
                    <span class="dashboard-badge"><?= e(($filters['range_mode'] ?? 'period_default') === 'manual' ? 'Rentang Manual' : current_accounting_period_label()) ?></span>
                    <span class="dashboard-badge dashboard-badge--soft">Rentang: <?= e($filters['range_label'] ?? '-') ?></span>
                    <span class="dashboard-badge dashboard-badge--soft">Unit: <?= e($selectedUnitLabel) ?></span>
                    <span class="dashboard-badge <?= $readiness ? 'text-bg-success' : ($criticalFailures > 0 ? 'text-bg-danger' : 'text-bg-warning') ?>">Closing <?= $readiness ? 'Siap' : 'Perlu Review' ?></span>
                </div>
            </div>
        </div>
    </section>

    <section class="card dashboard-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <div>
                    <h2 class="h5 mb-1">Task Center Pimpinan</h2>
                    <div class="text-secondary small">Ringkasan tindakan yang paling relevan untuk pengambilan keputusan dan closing.</div>
                </div>
                <a href="<?= e(base_url('/periods')) ?>" class="btn btn-outline-light btn-sm">Buka Periode</a>
            </div>
            <div class="row g-3">
                <?php foreach ($taskCenter as $task): ?>
                    <?php $statusClass = (string) ($task['status'] ?? 'warning') === 'success' ? 'text-bg-success' : ((string) ($task['status'] ?? 'warning') === 'danger' ? 'text-bg-danger' : 'text-bg-warning'); ?>
                    <div class="col-md-4">
                        <a href="<?= e(base_url((string) ($task['url'] ?? '/dashboard/pimpinan'))) ?>" class="text-decoration-none">
                            <div class="border rounded-4 p-3 h-100 bg-body-tertiary">
                                <div class="d-flex justify-content-between gap-3 mb-2">
                                    <div class="fw-semibold text-dark"><?= e((string) ($task['title'] ?? '-')) ?></div>
                                    <span class="badge <?= e($statusClass) ?>"><?= e((string) ($task['value'] ?? '-')) ?></span>
                                </div>
                                <div class="small text-secondary"><?= e((string) ($task['note'] ?? '')) ?></div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($workspaceFavoritePages !== [] || $workspaceRecentItems !== []): ?>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="fw-semibold mb-2">Favorit</div>
                            <?php foreach (array_slice($workspaceFavoritePages, 0, 3) as $item): ?>
                                <div><a href="<?= e(base_url((string) ($item['path'] ?? '/dashboard/pimpinan'))) ?>" class="small text-decoration-none"><?= e((string) ($item['title'] ?? 'Favorit')) ?></a></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="fw-semibold mb-2">Terakhir Dibuka</div>
                            <?php foreach (array_slice($workspaceRecentItems, 0, 3) as $item): ?>
                                <div><a href="<?= e(base_url((string) ($item['path'] ?? '/dashboard/pimpinan'))) ?>" class="small text-decoration-none"><?= e((string) ($item['title'] ?? 'Halaman')) ?></a></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="dashboard-filter card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                <div>
                    <h2 class="h5 mb-1">Filter Dashboard Pimpinan</h2>
                    <p class="text-secondary mb-0">Atur periode default, unit usaha, dan rentang tanggal manual. Anda bisa melihat ringkasan lintas bulan, misalnya 1 Januari sampai 31 Maret.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= e(base_url('/dashboard/pimpinan')) ?>" class="btn btn-outline-light">Reset Filter</a>
                    <?php if (is_array($filters['period'] ?? null) && isset($filters['period']['id'])): ?>
                        <a href="<?= e(base_url('/periods/checklist?id=' . (int) $filters['period']['id'])) ?>" class="btn btn-primary">Buka Checklist Tutup Buku</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($filterErrors !== []): ?>
                <div class="alert alert-warning mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($filterErrors as $error): ?>
                            <li><?= e((string) $error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="alert alert-secondary mb-4 small">
                <div class="fw-semibold mb-1">Cara kerja filter</div>
                <div>Periode tetap berguna untuk checklist tutup buku, tetapi nilai dashboard akan mengikuti tanggal manual jika Anda mengisinya. Jadi total 1 Januari sampai 31 Maret tetap bisa ditampilkan.</div>
            </div>
            <form method="get" action="<?= e(base_url('/dashboard/pimpinan')) ?>" class="row g-3 align-items-end">
                <div class="col-12 col-xl-3">
                    <label class="form-label">Periode Default</label>
                    <select name="period_id" class="form-select">
                        <option value="0">Periode aktif / default</option>
                        <?php foreach (($periods ?? []) as $period): ?>
                            <option value="<?= e((string) ($period['id'] ?? 0)) ?>" <?= (int) ($filters['period_id'] ?? 0) === (int) ($period['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($period['period_name'] ?? $period['period_code'] ?? 'Periode')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-xl-2">
                    <label class="form-label">Tanggal Mulai Manual</label>
                    <input type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>" class="form-control">
                </div>
                <div class="col-12 col-xl-2">
                    <label class="form-label">Tanggal Akhir Manual</label>
                    <input type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>" class="form-control">
                </div>
                <?php if ($unitFeatureEnabled): ?>
                    <div class="col-12 col-xl-3">
                        <label class="form-label">Unit Usaha</label>
                        <select name="unit_id" class="form-select">
                            <option value="0">Semua Unit</option>
                            <?php foreach (($units ?? []) as $unit): ?>
                                <option value="<?= e((string) ($unit['id'] ?? 0)) ?>" <?= (int) ($filters['unit_id'] ?? 0) === (int) ($unit['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($unit['label'] ?? $unit['unit_name'] ?? 'Unit')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-12 col-xl-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <section class="metric-grid mb-4">
        <article class="metric-card metric-card--teal">
            <div class="metric-card__label">Laba / Rugi Berjalan</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($summary['net_profit'] ?? 0))) ?></div>
            <div class="metric-card__meta">Pendapatan <?= e(dashboard_compact_currency((float) ($summary['total_revenue'] ?? 0))) ?> · Beban <?= e(dashboard_compact_currency((float) ($summary['total_expense'] ?? 0))) ?></div>
        </article>
        <article class="metric-card metric-card--blue">
            <div class="metric-card__label">Kas / Bank Bersih</div>
            <div class="metric-card__value"><?= e(dashboard_compact_currency((float) ($cashSummary['cash_balance'] ?? 0))) ?></div>
            <div class="metric-card__meta">Masuk <?= e(dashboard_compact_currency((float) ($cashSummary['cash_inflow'] ?? 0))) ?> · Keluar <?= e(dashboard_compact_currency((float) ($cashSummary['cash_outflow'] ?? 0))) ?></div>
        </article>
        <article class="metric-card metric-card--orange">
            <div class="metric-card__label">Kesiapan Tutup Buku</div>
            <div class="metric-card__value"><?= e($readiness ? 'Siap' : 'Review') ?></div>
            <div class="metric-card__meta">Kritis <?= e(number_format($criticalFailures, 0, ',', '.')) ?> · Peringatan <?= e(number_format($warnings, 0, ',', '.')) ?></div>
        </article>
        <article class="metric-card metric-card--violet">
            <div class="metric-card__label">Jurnal pada Rentang</div>
            <div class="metric-card__value"><?= e(number_format((int) ($summary['journal_count'] ?? 0), 0, ',', '.')) ?></div>
            <div class="metric-card__meta">Akun aktif <?= e(number_format((int) ($summary['active_detail_accounts'] ?? 0), 0, ',', '.')) ?> akun detail</div>
        </article>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1">Titik Perhatian Utama</h2>
                            <div class="text-secondary small">Ringkasan cepat hasil checklist tutup buku pada periode yang dipilih.</div>
                        </div>
                        <?php if (is_array($filters['period'] ?? null) && isset($filters['period']['id'])): ?>
                            <a href="<?= e(base_url('/periods/checklist?id=' . (int) $filters['period']['id'])) ?>" class="btn btn-sm btn-outline-light">Lihat Lengkap</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($topChecks !== []): ?>
                        <div class="dashboard-list-stack">
                            <?php foreach ($topChecks as $check): ?>
                                <?php $badgeClass = $check['status'] === 'pass' ? 'text-bg-success' : ($check['status'] === 'danger' ? 'text-bg-danger' : 'text-bg-warning'); ?>
                                <div class="dashboard-list-item align-items-start">
                                    <div>
                                        <div class="dashboard-list-item__title"><?= e((string) ($check['label'] ?? '-')) ?></div>
                                        <div class="dashboard-list-item__meta"><?= e((string) ($check['message'] ?? '-')) ?></div>
                                    </div>
                                    <span class="badge <?= e($badgeClass) ?>"><?= e(strtoupper((string) ($check['status'] ?? 'warning'))) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">Checklist periode belum tersedia untuk rentang ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1">Jurnal Terbaru</h2>
                            <div class="text-secondary small">Transaksi terakhir yang perlu diketahui pimpinan pada rentang yang dipilih.</div>
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
                                <th class="text-end">Nominal</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($recentJournals !== []): ?>
                                <?php foreach ($recentJournals as $journal): ?>
                                    <tr>
                                        <td><?= e((string) ($journal['journal_date'] ?? '-')) ?></td>
                                        <td><a class="dashboard-link" href="<?= e(base_url('/journals/detail?id=' . (int) ($journal['id'] ?? 0))) ?>"><?= e((string) ($journal['journal_no'] ?? '-')) ?></a></td>
                                        <td><?= e((string) ($journal['description'] ?? '-')) ?></td>
                                        <td class="text-end fw-semibold"><?= e(dashboard_currency((float) ($journal['total_debit'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada jurnal pada rentang ini.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Akun Pendapatan Teratas</h2>
                        <a href="<?= e(base_url('/profit-loss')) ?>" class="btn btn-sm btn-outline-light">Lihat Laba Rugi</a>
                    </div>
                    <div class="dashboard-list-stack">
                        <?php if ($topRevenueAccounts !== []): ?>
                            <?php foreach ($topRevenueAccounts as $row): ?>
                                <div class="dashboard-list-item">
                                    <div>
                                        <div class="dashboard-list-item__title"><?= e((string) ($row['account_code'] ?? '-')) ?> · <?= e((string) ($row['account_name'] ?? '-')) ?></div>
                                        <div class="dashboard-list-item__meta">Dipakai di <?= e(number_format((int) ($row['journal_count'] ?? 0), 0, ',', '.')) ?> jurnal</div>
                                    </div>
                                    <div class="dashboard-list-item__value"><?= e(dashboard_currency((float) ($row['total_amount'] ?? 0))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">Belum ada akun pendapatan pada rentang ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Akun Beban Teratas</h2>
                        <a href="<?= e(base_url('/profit-loss')) ?>" class="btn btn-sm btn-outline-light">Analisis Beban</a>
                    </div>
                    <div class="dashboard-list-stack">
                        <?php if ($topExpenseAccounts !== []): ?>
                            <?php foreach ($topExpenseAccounts as $row): ?>
                                <div class="dashboard-list-item">
                                    <div>
                                        <div class="dashboard-list-item__title"><?= e((string) ($row['account_code'] ?? '-')) ?> · <?= e((string) ($row['account_name'] ?? '-')) ?></div>
                                        <div class="dashboard-list-item__meta">Dipakai di <?= e(number_format((int) ($row['journal_count'] ?? 0), 0, ',', '.')) ?> jurnal</div>
                                    </div>
                                    <div class="dashboard-list-item__value"><?= e(dashboard_currency((float) ($row['total_amount'] ?? 0))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">Belum ada akun beban pada rentang ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($unitFeatureEnabled): ?>
        <div class="card dashboard-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-1">Ringkasan per Unit Usaha</h2>
                        <div class="text-secondary small">Membantu pimpinan melihat unit yang paling aktif, paling tinggi pendapatannya, dan paling besar bebannya.</div>
                    </div>
                    <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-sm btn-outline-light">Kelola Unit</a>
                </div>
                <div class="table-responsive coa-table-wrapper">
                    <table class="table table-dark table-hover align-middle mb-0 coa-table dashboard-table">
                        <thead>
                        <tr>
                            <th>Unit</th>
                            <th class="text-end">Jurnal</th>
                            <th class="text-end">Pendapatan</th>
                            <th class="text-end">Beban</th>
                            <th class="text-end">Laba / Rugi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($unitSummaries !== []): ?>
                            <?php foreach ($unitSummaries as $unitRow): ?>
                                <?php $unitProfit = (float) ($unitRow['total_revenue'] ?? 0) - (float) ($unitRow['total_expense'] ?? 0); ?>
                                <tr>
                                    <td><?= e((string) ($unitRow['unit_code'] ?? '-')) ?> · <?= e((string) ($unitRow['unit_name'] ?? '-')) ?></td>
                                    <td class="text-end"><?= e(number_format((int) ($unitRow['journal_count'] ?? 0), 0, ',', '.')) ?></td>
                                    <td class="text-end"><?= e(dashboard_currency((float) ($unitRow['total_revenue'] ?? 0))) ?></td>
                                    <td class="text-end"><?= e(dashboard_currency((float) ($unitRow['total_expense'] ?? 0))) ?></td>
                                    <td class="text-end"><span class="badge <?= e(dashboard_balance_badge_class($unitProfit)) ?>"><?= e(dashboard_currency($unitProfit)) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada ringkasan unit usaha.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
