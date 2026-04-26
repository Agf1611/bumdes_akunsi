<?php declare(strict_types=1); ?>

<div class="module-page report-analytics-page">
    <section class="module-hero">
        <div class="module-hero__content">
            <div>
                <div class="module-hero__eyebrow">Kontrol Saldo</div>
                <h1 class="module-hero__title">Neraca Saldo</h1>
                <p class="module-hero__text">Ringkasan saldo seluruh akun aktif yang kembali fokus pada posisi saldo utama dan akses drill-down ke jurnal sumber.</p>
            </div>
            <?php if (($filters['date_to'] ?? '') !== ''): ?>
                <div class="module-hero__actions">
                    <a href="<?= e(base_url('/trial-balance/print?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Print</a>
                    <a href="<?= e(base_url('/trial-balance/pdf?' . report_filters_query($filters))) ?>" target="_blank" class="btn btn-outline-light">Export PDF</a>
                    <a href="<?= e(base_url('/trial-balance/xlsx?' . report_filters_query($filters))) ?>" class="btn btn-primary">Export XLSX</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="get" action="<?= e(base_url('/trial-balance')) ?>" class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label">Periode</label>
                    <select name="period_id" class="form-select">
                        <option value="">Semua periode / manual tanggal</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= e((string) $period['id']) ?>" <?= (string) $filters['period_id'] === (string) $period['id'] ? 'selected' : '' ?>><?= e($period['period_name'] . ' (' . $period['period_code'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Tahun</label>
                    <select name="fiscal_year" class="form-select">
                        <option value="">Semua tahun</option>
                        <?php foreach (($reportYears ?? []) as $year): ?>
                            <option value="<?= e((string) $year) ?>" <?= (string) ($filters['fiscal_year'] ?? '') === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Unit Usaha</label>
                    <select name="unit_id" class="form-select">
                        <option value="">Semua Unit</option>
                        <?php foreach (($units ?? []) as $unit): ?>
                            <option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Mulai</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Akhir</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>">
                </div>
                <div class="col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Tampil</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <section class="report-kpi-grid">
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Rentang Data</div>
                <div class="report-kpi-card__value report-kpi-card__value--sm"><?= e(format_id_date((string) $filters['date_from'])) ?> - <?= e(format_id_date((string) $filters['date_to'])) ?></div>
                <div class="report-kpi-card__meta"><?= e($selectedPeriod['period_name'] ?? 'Filter tanggal manual') ?></div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Unit Usaha</div>
                <div class="report-kpi-card__value report-kpi-card__value--sm"><?= e(business_unit_label($selectedUnit)) ?></div>
                <div class="report-kpi-card__meta">Ruang lingkup laporan</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Jumlah Akun</div>
                <div class="report-kpi-card__value"><?= e((string) $summary['account_count']) ?></div>
                <div class="report-kpi-card__meta">Akun aktif yang terbaca</div>
            </article>
            <article class="report-kpi-card">
                <div class="report-kpi-card__label">Total Saldo</div>
                <div class="report-kpi-card__value report-kpi-card__value--sm"><?= e(ledger_currency((float) $summary['ending_balance_total'])) ?></div>
                <div class="report-kpi-card__meta">Posisi saldo akhir periode</div>
            </article>
        </section>

        <section class="card shadow-sm report-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 report-analytics-table">
                        <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Tipe</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Kredit</th>
                            <th class="text-end">Saldo Akhir</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-5">Tidak ada data neraca saldo untuk filter yang dipilih.</td></tr>
                        <?php else: foreach ($rows as $row): ?>
                            <?php $currentUrl = report_drilldown_url((int) ($row['account_id'] ?? 0), $filters, 'trial_balance'); ?>
                            <tr>
                                <td class="fw-semibold"><?= e((string) $row['account_code']) ?></td>
                                <td><?= e((string) $row['account_name']) ?></td>
                                <td><span class="badge text-bg-secondary"><?= e((string) $row['account_type']) ?></span></td>
                                <td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['period_debit'])) ?></td>
                                <td class="text-end fw-semibold"><?= e(ledger_currency((float) $row['period_credit'])) ?></td>
                                <td class="text-end fw-semibold"><a href="<?= e($currentUrl) ?>" class="report-value-link"><?= e(ledger_currency((float) $row['closing_balance'])) ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-dark">Tampil</strong> untuk melihat neraca saldo.</div></div>
    <?php endif; ?>
</div>
