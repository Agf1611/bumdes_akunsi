<?php declare(strict_types=1);
$user = Auth::user();
$profile = app_profile();
$logoPath = (string) ($profile['logo_path'] ?? '');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<aside class="sidebar bg-sidebar border-end border-secondary-subtle p-3">
    <div class="mb-4 d-flex align-items-center gap-3">
        <div class="sidebar-logo d-flex align-items-center justify-content-center">
            <?php if ($logoPath !== ''): ?>
                <img src="<?= e(storage_url($logoPath)) ?>" alt="Logo BUMDes" class="img-fluid rounded-circle logo-thumb">
            <?php else: ?>
                <span class="fw-bold text-light">B</span>
            <?php endif; ?>
        </div>
        <div>
            <div class="fw-bold fs-6 lh-sm"><?= e($profile['bumdes_name'] ?: 'BUMDes Keuangan') ?></div>
            <div class="text-secondary small"><?= e(current_accounting_period_label()) ?></div>
        </div>
    </div>

    <nav class="nav flex-column gap-1">
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/dashboard') || str_contains($currentPath, '/eis') || $currentPath === '/' ? ' active' : '' ?>" href="<?= e(base_url('/dashboard')) ?>">Dashboard EIS</a>

        <div class="sidebar-section-label mt-3 mb-2 text-secondary small text-uppercase">Master Data</div>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/coa') ? ' active' : '' ?>" href="<?= e(base_url('/coa')) ?>">Chart of Accounts</a>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/periods') ? ' active' : '' ?>" href="<?= e(base_url('/periods')) ?>">Periode Akuntansi</a>

        <div class="sidebar-section-label mt-3 mb-2 text-secondary small text-uppercase">Transaksi</div>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/journals') ? ' active' : '' ?>" href="<?= e(base_url('/journals')) ?>">Jurnal Umum</a>

        <div class="sidebar-section-label mt-3 mb-2 text-secondary small text-uppercase">Laporan</div>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/ledger') ? ' active' : '' ?>" href="<?= e(base_url('/ledger')) ?>">Buku Besar</a>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/trial-balance') ? ' active' : '' ?>" href="<?= e(base_url('/trial-balance')) ?>">Neraca Saldo</a>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/profit-loss') ? ' active' : '' ?>" href="<?= e(base_url('/profit-loss')) ?>">Laba Rugi</a>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/balance-sheet') ? ' active' : '' ?>" href="<?= e(base_url('/balance-sheet')) ?>">Neraca</a>
        <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/cash-flow') ? ' active' : '' ?>" href="<?= e(base_url('/cash-flow')) ?>">Arus Kas</a>

        <?php if (Auth::hasRole('admin')): ?>
            <div class="sidebar-section-label mt-3 mb-2 text-secondary small text-uppercase">Pengaturan</div>
            <a class="nav-link text-light sidebar-link<?= str_contains($currentPath, '/settings/profile') ? ' active' : '' ?>" href="<?= e(base_url('/settings/profile')) ?>">Profil BUMDes</a>
        <?php endif; ?>
    </nav>

    <div class="mt-auto pt-4 small text-secondary border-top border-secondary-subtle">
        <div class="text-light fw-medium"><?= e($user['full_name'] ?? '-') ?></div>
        <div><?= e($user['role_name'] ?? '-') ?></div>
    </div>
</aside>
