<?php declare(strict_types=1); ?>
<?php
$page = is_array($page ?? null) ? $page : [];
$units = is_array($units ?? null) ? $units : [];
$activeUnitLabel = (string) ($activeUnitLabel ?? 'Semua Unit');
$items = is_array($page['items'] ?? null) ? $page['items'] : [];
?>
<div class="business-operation-page module-page">
    <section class="operation-hero mb-4">
        <div class="operation-hero__icon">
            <i class="bi <?= e((string) ($page['icon'] ?? 'bi-grid')) ?>" aria-hidden="true"></i>
        </div>
        <div class="operation-hero__copy">
            <div class="module-hero__eyebrow"><?= e((string) ($page['eyebrow'] ?? 'Kelola Usaha')) ?></div>
            <h1 class="module-hero__title"><?= e((string) ($page['title'] ?? $title ?? 'Kelola Usaha')) ?></h1>
            <p class="module-hero__text mb-0"><?= e((string) ($page['description'] ?? 'Kelola data operasional unit usaha.')) ?></p>
        </div>
        <div class="operation-hero__meta">
            <span>Unit aktif</span>
            <strong><?= e($activeUnitLabel) ?></strong>
        </div>
    </section>

    <div class="operation-grid">
        <section class="operation-card operation-card--main">
            <div class="operation-card__head">
                <div>
                    <span class="operation-card__eyebrow">Fokus fitur</span>
                    <h2><?= e((string) ($page['title'] ?? 'Kelola Usaha')) ?></h2>
                </div>
                <button type="button" class="btn btn-primary" disabled>
                    <i class="bi bi-plus-circle" aria-hidden="true"></i>
                    <span><?= e((string) ($page['next_action'] ?? 'Tambah data')) ?></span>
                </button>
            </div>
            <div class="operation-feature-list">
                <?php foreach ($items as $item): ?>
                    <div class="operation-feature">
                        <span><i class="bi bi-check2" aria-hidden="true"></i></span>
                        <p><?= e((string) $item) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="operation-note">
                Modul ini sudah disiapkan di menu agar alur Kelola Usaha lengkap. Tahap berikutnya bisa dibuat CRUD penuh sesuai format data yang BUMDes butuhkan.
            </div>
        </section>

        <aside class="operation-card">
            <div class="operation-card__head operation-card__head--compact">
                <div>
                    <span class="operation-card__eyebrow">Unit usaha</span>
                    <h2>Daftar Unit</h2>
                </div>
                <a href="<?= e(base_url('/business-units')) ?>" class="btn btn-outline-primary btn-sm">Kelola</a>
            </div>
            <div class="operation-unit-list">
                <?php if ($units === []): ?>
                    <div class="operation-empty">Belum ada unit usaha.</div>
                <?php else: ?>
                    <?php foreach ($units as $unit): ?>
                        <div class="operation-unit">
                            <div>
                                <strong><?= e((string) ($unit['unit_code'] ?? '-')) ?></strong>
                                <span><?= e((string) ($unit['unit_name'] ?? '-')) ?></span>
                            </div>
                            <em><?= (int) ($unit['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></em>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
