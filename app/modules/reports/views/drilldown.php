<?php declare(strict_types=1); ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-semibold small mb-2">Source trace</div>
        <h1 class="h3 mb-1">Drill-down Jurnal Sumber</h1>
        <p class="text-secondary mb-0">Telusuri angka laporan sampai ke baris jurnal sumber dengan filter periode, unit, akun, dan tanggal yang sama.</p>
    </div>
    <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-primary">Buka Modul Jurnal</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= e(base_url('/reports/drilldown')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="source_report" value="<?= e((string) ($filters['source_report'] ?? 'laporan')) ?>">
            <input type="hidden" name="filter_scope" value="<?= e(report_filter_scope($filters)) ?>">
            <div class="col-lg-2">
                <label class="form-label">Periode Awal</label>
                <select name="period_id" class="form-select">
                    <?= report_period_select_options($periods ?? [], (int) ($filters['period_id'] ?? 0), 'Manual tanggal') ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Sampai Periode</label>
                <select name="period_to_id" class="form-select">
                    <?= report_period_select_options($periods ?? [], (int) ($filters['period_to_id'] ?? 0), 'Sama dengan periode awal') ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Akun</label>
                <select name="account_id" class="form-select">
                    <option value="">Semua Akun</option>
                    <?php foreach (($accounts ?? []) as $account): ?>
                        <option value="<?= e((string) $account['id']) ?>" <?= (string) ($filters['account_id'] ?? '') === (string) $account['id'] ? 'selected' : '' ?>>
                            <?= e((string) $account['account_code'] . ' - ' . (string) $account['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Unit Usaha</label>
                <select name="unit_id" class="form-select">
                    <option value="">Semua Unit</option>
                    <?php foreach (($unitOptions ?? []) as $unit): ?>
                        <option value="<?= e((string) $unit['id']) ?>" <?= (string) ($filters['unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>>
                            <?= e((string) $unit['unit_code'] . ' - ' . (string) $unit['unit_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="date_from" class="form-control" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="date_to" class="form-control" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Jurnal</th>
                    <th>Akun</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($rows ?? []) as $row): ?>
                    <tr>
                        <td><?= e(format_id_date((string) $row['journal_date'])) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string) $row['journal_no']) ?></div>
                            <div class="small text-secondary"><?= e(journal_workflow_label((string) ($row['workflow_status'] ?? 'POSTED'))) ?></div>
                        </td>
                        <td><?= e((string) $row['account_code'] . ' - ' . (string) $row['account_name']) ?></td>
                        <td>
                            <div><?= e((string) $row['description']) ?></div>
                            <div class="small text-secondary"><?= e((string) ($row['line_description'] ?? '')) ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= e(number_format((float) $row['debit'], 0, ',', '.')) ?></td>
                        <td class="text-end fw-semibold"><?= e(number_format((float) $row['credit'], 0, ',', '.')) ?></td>
                        <td class="text-end"><a href="<?= e(base_url('/journals/detail?id=' . (int) $row['journal_id'])) ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($rows ?? []) === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            Belum ada baris jurnal sesuai filter. Coba ubah periode, akun, atau rentang tanggal.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent text-secondary small">Menampilkan maksimal 500 baris agar halaman tetap ringan.</div>
</div>
