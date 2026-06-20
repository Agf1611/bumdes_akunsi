<?php declare(strict_types=1); ?>
<?php
require APP_PATH . '/views/partials/table_action_menu.php';

$importErrors = Session::pull('import_errors', []);
$importSuccess = Session::pull('import_success', '');
$importResult = Session::pull('import_result', []);

$filters = is_array($filters ?? null) ? $filters : ['search' => '', 'type' => ''];
$searchTerm = trim((string) ($filters['search'] ?? ''));
$selectedType = trim((string) ($filters['type'] ?? ''));
$allAccounts = is_array($accounts ?? null) ? $accounts : [];
$types = is_array($types ?? null) ? $types : [];

$typeOrder = array_keys($types);
$groupedAccounts = [];
$typeCounts = [];

foreach ($typeOrder as $typeKey) {
    $groupedAccounts[$typeKey] = [];
    $typeCounts[$typeKey] = 0;
}

foreach ($allAccounts as $row) {
    $type = (string) ($row['account_type'] ?? '');
    if ($type === '') {
        $type = 'OTHER';
    }
    if (!array_key_exists($type, $groupedAccounts)) {
        $groupedAccounts[$type] = [];
        $typeCounts[$type] = 0;
        $types[$type] = coa_type_label($type);
    }
    $groupedAccounts[$type][] = $row;
    $typeCounts[$type]++;
}

$visibleGroupCount = count(array_filter($groupedAccounts, static fn (array $rows): bool => $rows !== []));
$openGroups = $searchTerm !== '' || $selectedType !== '';
?>
<div class="coa-page module-page coa-accordion-page">
    <section class="coa-compact-hero mb-4">
        <div class="coa-compact-hero__copy">
            <div class="module-hero__eyebrow">Chart Of Accounts</div>
            <h1 class="module-hero__title">Struktur Akun BUMDes</h1>
            <p class="module-hero__text mb-0">Kelola akun per grup agar pencarian dan pengecekan struktur lebih cepat.</p>
        </div>
        <div class="coa-compact-hero__meta">
            <div class="coa-stat-pill">
                <span>Total Akun</span>
                <strong><?= e((string) $totalAccounts) ?></strong>
            </div>
            <div class="coa-stat-pill">
                <span>Grup Aktif</span>
                <strong><?= e((string) $visibleGroupCount) ?></strong>
            </div>
        </div>
    </section>

    <?php if ($importSuccess !== ''): ?>
        <div class="alert alert-success"><?= e($importSuccess) ?></div>
    <?php endif; ?>
    <?php if ($importErrors !== []): ?>
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">Import COA dibatalkan karena ditemukan masalah:</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($importErrors as $message): ?>
                    <li><?= e((string) $message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (($importResult['type'] ?? '') === 'COA' && (int) ($importResult['imported'] ?? 0) > 0): ?>
        <div class="alert alert-success">Total akun yang ditambahkan melalui import terakhir: <strong><?= e((string) (int) $importResult['imported']) ?></strong>.</div>
    <?php endif; ?>

    <div class="coa-toolbar card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="<?= e(base_url('/coa')) ?>" class="coa-toolbar__form">
                <div class="coa-search-field">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" name="search" value="<?= e($searchTerm) ?>" placeholder="Cari kode atau nama akun..." aria-label="Cari kode atau nama akun">
                </div>
                <select class="form-select coa-type-select" name="type" aria-label="Filter tipe akun">
                    <option value="">Semua jenis akun</option>
                    <?php foreach ($types as $value => $label): ?>
                        <option value="<?= e((string) $value) ?>" <?= $selectedType === (string) $value ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span>Cari</span>
                </button>
                <?php if ($searchTerm !== '' || $selectedType !== ''): ?>
                    <a href="<?= e(base_url('/coa')) ?>" class="btn btn-outline-light">Reset</a>
                <?php endif; ?>
            </form>
            <div class="coa-toolbar__actions">
                <?php if (Auth::hasRole('admin')): ?>
                    <a href="<?= e(base_url('/coa/create')) ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i>
                        <span>Tambah Akun</span>
                    </a>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-tools" aria-hidden="true"></i>
                        <span>Tools</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end coa-tools-menu">
                        <a href="<?= e(base_url('/imports/template?type=coa')) ?>" class="dropdown-item">
                            <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                            Unduh Template COA
                        </a>
                        <a href="<?= e(base_url('/coa/export?' . http_build_query($filters))) ?>" class="dropdown-item">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            Export COA
                        </a>
                        <?php if (Auth::hasRole('admin')): ?>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= e(base_url('/coa/seed-global')) ?>" class="px-3 py-2" onsubmit="return confirm('Tambahkan paket COA standar KepmenDesa 136/2022? Akun yang sudah ada tidak akan ditimpa.');">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="btn btn-outline-success w-100">
                                    <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                    Tambah COA KepmenDesa
                                </button>
                                <div class="form-text mt-2">Paket tersedia: <?= e((string) ($globalCoaCount ?? 0)) ?> akun standar.</div>
                            </form>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= e(base_url('/imports/coa')) ?>" enctype="multipart/form-data" class="px-3 py-2">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="redirect_to" value="/coa">
                                <label class="form-label small fw-semibold">Import COA (.xlsx)</label>
                                <input type="file" class="form-control form-control-sm" name="coa_file" accept=".xlsx" required>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="coa_overwrite" name="coa_overwrite">
                                    <label class="form-check-label small" for="coa_overwrite">Timpa kode yang sama</label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    Import COA
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($searchTerm !== '' || $selectedType !== ''): ?>
        <div class="coa-filter-summary mb-3">
            <span>Hasil filter</span>
            <?php if ($searchTerm !== ''): ?><strong><?= e($searchTerm) ?></strong><?php endif; ?>
            <?php if ($selectedType !== ''): ?><strong><?= e(coa_type_label($selectedType)) ?></strong><?php endif; ?>
            <span><?= e((string) count($allAccounts)) ?> akun ditemukan</span>
        </div>
    <?php endif; ?>

    <div class="coa-accordion-list">
        <?php if ($allAccounts === []): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-secondary">Belum ada data akun yang cocok. Silakan ubah pencarian atau tambah akun baru.</div>
            </div>
        <?php else: ?>
            <?php foreach ($groupedAccounts as $type => $rows): ?>
                <?php
                    if ($rows === []) {
                        continue;
                    }
                    $typeLabel = (string) ($types[$type] ?? coa_type_label((string) $type));
                    $isOpen = $openGroups || $selectedType === (string) $type;
                    $detailCount = count(array_filter($rows, static fn (array $row): bool => (int) ($row['is_header'] ?? 0) !== 1));
                    $inactiveCount = count(array_filter($rows, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) !== 1));
                ?>
                <details class="coa-account-group" <?= $isOpen ? 'open' : '' ?>>
                    <summary class="coa-account-group__summary">
                        <span class="coa-account-group__icon"><?= e(substr($typeLabel, 0, 1)) ?></span>
                        <span class="coa-account-group__copy">
                            <span class="coa-account-group__title"><?= e($typeLabel) ?></span>
                            <span class="coa-account-group__meta"><?= e((string) $detailCount) ?> akun detail<?= $inactiveCount > 0 ? ' · ' . e((string) $inactiveCount) . ' nonaktif' : '' ?></span>
                        </span>
                        <span class="coa-account-group__badge"><?= e((string) count($rows)) ?></span>
                        <i class="bi bi-chevron-down coa-account-group__chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="coa-account-group__body">
                        <div class="table-responsive coa-table-wrapper">
                            <table class="table table-hover align-middle coa-table coa-group-table mb-0">
                                <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Akun</th>
                                    <th>Kategori</th>
                                    <th>Parent</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th class="text-end table-action-col">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr class="<?= (int) ($row['is_header'] ?? 0) === 1 ? 'coa-row-header' : '' ?>">
                                        <td class="fw-semibold text-nowrap"><?= e($row['account_code']) ?></td>
                                        <td>
                                            <div class="fw-medium"><?= e($row['account_name']) ?></div>
                                        </td>
                                        <td><?= e(coa_category_label($row['account_type'], $row['account_category'])) ?></td>
                                        <td>
                                            <?php if (!empty($row['parent_code'])): ?>
                                                <div class="small fw-medium"><?= e($row['parent_code']) ?></div>
                                                <div class="text-secondary small"><?= e($row['parent_name']) ?></div>
                                            <?php else: ?>
                                                <span class="text-secondary">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= (int) $row['is_header'] === 1 ? 'text-bg-info' : 'text-bg-primary' ?>">
                                                <?= (int) $row['is_header'] === 1 ? 'Header' : 'Detail' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= e(coa_status_badge_class((int) $row['is_active'] === 1)) ?>">
                                                <?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </td>
                                        <td class="text-end table-action-col">
                                            <?php if (Auth::hasRole('admin')): ?>
                                                <div class="table-action-menu">
                                                    <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">
                                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                                        <span>Aksi</span>
                                                    </button>
                                                    <div class="table-action-panel">
                                                        <a href="<?= e(base_url('/coa/edit?id=' . (int) $row['id'])) ?>">Edit akun</a>
                                                        <form method="post" action="<?= e(base_url('/coa/toggle-active?id=' . (int) $row['id'])) ?>" class="m-0">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <button type="submit"><?= (int) $row['is_active'] === 1 ? 'Nonaktifkan akun' : 'Aktifkan akun' ?></button>
                                                        </form>
                                                        <form method="post" action="<?= e(base_url('/coa/delete?id=' . (int) $row['id'])) ?>" class="m-0" onsubmit="return confirm('Hapus akun ini? Akun hanya bisa dihapus jika belum dipakai jurnal dan tidak punya akun turunan.');">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <button type="submit" class="table-action-danger">Hapus akun</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-secondary small">Lihat saja</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
