<?php declare(strict_types=1); ?>
<?php
$type = (string) ($type ?? 'employees');
$page = is_array($page ?? null) ? $page : [];
$rows = is_array($rows ?? null) ? $rows : [];
$units = is_array($units ?? null) ? $units : [];
$filters = is_array($filters ?? null) ? $filters : [];
$route = (string) ($page['route'] ?? '/business-employees');
$isReady = (bool) ($isReady ?? false);

$fmtMoney = static fn (mixed $value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$unitLabel = static function (array $row): string {
    $code = trim((string) ($row['unit_code'] ?? ''));
    $name = trim((string) ($row['unit_name'] ?? ''));
    return $code !== '' ? $code . ' - ' . $name : ($name !== '' ? $name : 'Semua Unit');
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'ACTIVE', 'RUNNING', 'APPROVED', 'REALIZED' => 'bg-success-subtle text-success',
        'DRAFT', 'PLANNED' => 'bg-primary-subtle text-primary',
        'PAUSED', 'CLOSED' => 'bg-warning-subtle text-warning',
        default => 'bg-secondary-subtle text-secondary',
    };
};
?>
<div class="business-operation-page module-page">
    <section class="operation-hero mb-3">
        <div class="operation-hero__icon">
            <i class="bi <?= e((string) ($page['icon'] ?? 'bi-grid')) ?>" aria-hidden="true"></i>
        </div>
        <div class="operation-hero__copy">
            <div class="module-hero__eyebrow">Kelola Usaha</div>
            <h1 class="module-hero__title"><?= e((string) ($page['title'] ?? $title ?? 'Kelola Usaha')) ?></h1>
            <p class="module-hero__text mb-0"><?= e((string) ($page['description'] ?? 'Kelola data operasional unit usaha.')) ?></p>
        </div>
        <div class="operation-hero__actions">
            <?php if ($type === 'budget_plans'): ?>
                <a href="<?= e(base_url('/budget-plan-reports')) ?>" class="btn btn-outline-light">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span>
                </a>
            <?php endif; ?>
            <a href="<?= e(base_url('/business-operations/create?type=' . urlencode($type))) ?>" class="btn btn-light">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span><?= e((string) ($page['create_label'] ?? 'Tambah Data')) ?></span>
            </a>
        </div>
    </section>

    <?php if (!$isReady): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            Tabel Kelola Usaha belum tersedia di database. Jalankan patch <code>database/patch_business_operations.sql</code> pada server/database aktif.
        </div>
    <?php endif; ?>

    <section class="operation-card mb-3">
        <form method="get" action="<?= e(base_url($route)) ?>" class="operation-filter">
            <div class="operation-search-field">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" class="form-control" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Cari nama, kode, kategori, catatan...">
            </div>
            <select name="unit_id" class="form-select">
                <option value="0">Semua Unit</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= (int) $unit['id'] ?>" <?= (int) ($filters['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>>
                        <?= e(business_unit_label($unit, false)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (in_array($type, ['budgets', 'budget_plans'], true)): ?>
                <input type="number" name="year" class="form-control operation-year-input" value="<?= e((string) ($filters['year'] ?? date('Y'))) ?>" min="2000" max="2100" aria-label="Tahun">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel" aria-hidden="true"></i><span>Cari</span>
            </button>
        </form>
    </section>

    <section class="operation-card">
        <div class="operation-card__head">
            <div>
                <span class="operation-card__eyebrow"><?= e((string) count($rows)) ?> data</span>
                <h2><?= e((string) ($page['title'] ?? 'Kelola Usaha')) ?></h2>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <div class="operation-empty-state">
                <i class="bi bi-inboxes" aria-hidden="true"></i>
                <strong>Belum ada data</strong>
                <span>Mulai dari tombol tambah di kanan atas.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive operation-table-wrap">
                <table class="table align-middle operation-table mb-0">
                    <thead>
                    <tr>
                        <?php if ($type === 'employees'): ?>
                            <th>Nama</th><th>Jabatan</th><th>Unit</th><th>Kontak</th><th>Status</th><th class="text-end">Aksi</th>
                        <?php elseif ($type === 'business'): ?>
                            <th>Aktivitas</th><th>Jenis</th><th>Unit</th><th>Target</th><th>Status</th><th class="text-end">Aksi</th>
                        <?php elseif ($type === 'budgets'): ?>
                            <th>Kategori</th><th>Periode</th><th>Unit</th><th>Jenis</th><th class="text-end">Nominal</th><th class="text-end">Aksi</th>
                        <?php else: ?>
                            <th>No RAB</th><th>Judul</th><th>Unit</th><th>Tanggal</th><th class="text-end">Total</th><th class="text-end">Aksi</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php if ($type === 'employees'): ?>
                                <td><strong><?= e((string) $row['employee_name']) ?></strong><div class="text-secondary small"><?= e((string) ($row['notes'] ?? '')) ?></div></td>
                                <td><?= e((string) $row['position_title']) ?></td>
                                <td><?= e($unitLabel($row)) ?></td>
                                <td><div><?= e((string) ($row['phone'] ?? '-')) ?></div><div class="text-secondary small"><?= e((string) ($row['email'] ?? '')) ?></div></td>
                                <td><span class="badge <?= e($statusClass((string) $row['status'])) ?>"><?= e((string) $row['status']) ?></span></td>
                            <?php elseif ($type === 'business'): ?>
                                <td><strong><?= e((string) $row['activity_name']) ?></strong><div class="text-secondary small"><?= e((string) ($row['notes'] ?? '')) ?></div></td>
                                <td><?= e((string) ($row['activity_type'] ?: '-')) ?></td>
                                <td><?= e($unitLabel($row)) ?></td>
                                <td><div><?= e((string) ($row['target_period'] ?: '-')) ?></div><div class="text-secondary small"><?= e($fmtMoney($row['target_value'] ?? 0)) ?></div></td>
                                <td><span class="badge <?= e($statusClass((string) $row['status'])) ?>"><?= e((string) $row['status']) ?></span></td>
                            <?php elseif ($type === 'budgets'): ?>
                                <td><strong><?= e((string) $row['category']) ?></strong><div class="text-secondary small"><?= e((string) (($row['account_code'] ?? '') . ' ' . ($row['account_name'] ?? ''))) ?></div></td>
                                <td><?= e((string) $row['budget_year']) ?><?= (int) ($row['budget_month'] ?? 0) > 0 ? ' / ' . e(str_pad((string) $row['budget_month'], 2, '0', STR_PAD_LEFT)) : '' ?></td>
                                <td><?= e($unitLabel($row)) ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= e((string) $row['budget_type']) ?></span></td>
                                <td class="text-end fw-bold"><?= e($fmtMoney($row['amount'] ?? 0)) ?></td>
                            <?php else: ?>
                                <td><strong><?= e((string) $row['plan_no']) ?></strong><div class="text-secondary small"><?= e((string) $row['status']) ?> · <?= e((string) ($row['item_count'] ?? 0)) ?> item</div></td>
                                <td><?= e((string) $row['plan_title']) ?><div class="text-secondary small"><?= e((string) ($row['activity_name'] ?? '')) ?></div></td>
                                <td><?= e($unitLabel($row)) ?></td>
                                <td><?= e(format_id_date((string) $row['plan_date'])) ?></td>
                                <td class="text-end fw-bold"><?= e($fmtMoney($row['total_amount'] ?? 0)) ?></td>
                            <?php endif; ?>
                            <td class="text-end">
                                <div class="operation-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/business-operations/edit?type=' . urlencode($type) . '&id=' . (int) $row['id'])) ?>" title="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form method="post" action="<?= e(base_url('/business-operations/delete?type=' . urlencode($type) . '&id=' . (int) $row['id'])) ?>" onsubmit="return confirm('Hapus data ini?');">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
