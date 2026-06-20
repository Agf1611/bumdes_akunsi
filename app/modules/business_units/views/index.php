<?php declare(strict_types=1); ?>
<?php $rows = is_array($rows ?? null) ? $rows : []; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<?php
$search = trim((string) ($search ?? ''));
$totalUnits = count($rows);
$activeUnits = count(array_filter($rows, static fn (array $unit): bool => (int) ($unit['is_active'] ?? 0) === 1));
$totalJournals = array_sum(array_map(static fn (array $unit): int => (int) ($unit['journal_count'] ?? 0), $rows));
?>
<div class="business-unit-page module-page">
    <section class="unit-hero mb-4">
        <div class="unit-hero__copy">
            <div class="module-hero__eyebrow">Kelola Usaha</div>
            <h1 class="module-hero__title">Unit Usaha BUMDes</h1>
            <p class="module-hero__text mb-0">Atur profil setiap unit usaha, NIB, kontak, dan status agar laporan per unit lebih rapi.</p>
        </div>
        <div class="unit-hero__stats">
            <div class="unit-stat">
                <span>Total Unit</span>
                <strong><?= e((string) $totalUnits) ?></strong>
            </div>
            <div class="unit-stat">
                <span>Aktif</span>
                <strong><?= e((string) $activeUnits) ?></strong>
            </div>
            <div class="unit-stat">
                <span>Dipakai Jurnal</span>
                <strong><?= e(number_format($totalJournals, 0, ',', '.')) ?></strong>
            </div>
        </div>
    </section>

    <div class="unit-toolbar card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="<?= e(base_url('/business-units')) ?>" class="unit-toolbar__form">
                <div class="unit-search-field">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari kode, nama unit, NIB, atau deskripsi..." aria-label="Cari unit usaha">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span>Cari</span>
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-light">Reset</a>
                <?php endif; ?>
            </form>
            <a href="<?= e(base_url('/business-units/create')) ?>" class="btn btn-primary unit-toolbar__create">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span>Tambah Unit Usaha</span>
            </a>
        </div>
    </div>

    <?php if ($search !== ''): ?>
        <div class="unit-filter-summary mb-3">
            <span>Hasil pencarian</span>
            <strong><?= e($search) ?></strong>
            <span><?= e((string) count($rows)) ?> unit ditemukan</span>
        </div>
    <?php endif; ?>

    <?php if ($rows === []): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="h5 mb-2">Belum ada unit usaha yang cocok</div>
                <p class="text-secondary mb-3">Tambahkan unit usaha seperti WIFI, Ketapang, atau unit lain agar jurnal dan laporan bisa terpisah per usaha.</p>
                <a href="<?= e(base_url('/business-units/create')) ?>" class="btn btn-primary">Tambah Unit Usaha</a>
            </div>
        </div>
    <?php else: ?>
        <div class="unit-card-grid">
            <?php foreach ($rows as $unit): ?>
                <?php
                    $isActive = (int) ($unit['is_active'] ?? 0) === 1;
                    $legalName = trim((string) ($unit['legal_name'] ?? ''));
                    $nib = trim((string) ($unit['nib'] ?? ''));
                    $phone = trim((string) ($unit['phone'] ?? ''));
                    $email = trim((string) ($unit['email'] ?? ''));
                    $address = trim((string) ($unit['address'] ?? ''));
                ?>
                <article class="unit-card">
                    <div class="unit-card__head">
                        <div class="unit-card__mark"><?= e(substr((string) ($unit['unit_code'] ?? 'U'), 0, 2)) ?></div>
                        <div class="unit-card__title">
                            <div class="unit-card__eyebrow"><?= e((string) $unit['unit_code']) ?></div>
                            <h2><?= e((string) $unit['unit_name']) ?></h2>
                            <?php if ($legalName !== ''): ?>
                                <p><?= e($legalName) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge rounded-pill <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span>
                    </div>

                    <div class="unit-card__body">
                        <div class="unit-info-row">
                            <span>NIB</span>
                            <strong><?= e($nib !== '' ? $nib : 'Belum diisi') ?></strong>
                        </div>
                        <div class="unit-info-row">
                            <span>Kontak</span>
                            <strong><?= e(trim($phone . ($phone !== '' && $email !== '' ? ' / ' : '') . $email) ?: 'Belum diisi') ?></strong>
                        </div>
                        <div class="unit-info-row">
                            <span>Alamat</span>
                            <strong><?= e($address !== '' ? $address : 'Belum diisi') ?></strong>
                        </div>
                        <?php if (trim((string) ($unit['description'] ?? '')) !== ''): ?>
                            <div class="unit-card__note"><?= e((string) $unit['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="unit-card__footer">
                        <div>
                            <span>Dipakai jurnal</span>
                            <strong><?= e(number_format((int) ($unit['journal_count'] ?? 0), 0, ',', '.')) ?></strong>
                        </div>
                        <div class="table-action-menu">
                            <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">
                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                <span>Aksi</span>
                            </button>
                            <div class="table-action-panel">
                                <a href="<?= e(base_url('/business-units/edit?id=' . (int) $unit['id'])) ?>">Edit profil unit</a>
                                <form method="post" action="<?= e(base_url('/business-units/toggle-active?id=' . (int) $unit['id'])) ?>" class="m-0">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit"><?= $isActive ? 'Nonaktifkan unit usaha' : 'Aktifkan unit usaha' ?></button>
                                </form>
                                <form method="post" action="<?= e(base_url('/business-units/delete?id=' . (int) $unit['id'])) ?>" onsubmit="return confirm('Hapus unit usaha ini? Unit hanya bisa dihapus jika belum dipakai jurnal.');" class="m-0">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="table-action-danger">Hapus unit usaha</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
