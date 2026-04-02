<?php declare(strict_types=1);
$user = Auth::user();
$profile = app_profile();
$pageTitle = (string) ($title ?? 'Aplikasi');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$sectionLabel = 'Workspace';

if (str_contains($currentPath, '/assets')) {
    $sectionLabel = 'Aset';
} elseif (str_contains($currentPath, '/journals')) {
    $sectionLabel = 'Transaksi';
} elseif (
    str_contains($currentPath, '/ledger')
    || str_contains($currentPath, '/trial-balance')
    || str_contains($currentPath, '/profit-loss')
    || str_contains($currentPath, '/balance-sheet')
    || str_contains($currentPath, '/cash-flow')
    || str_contains($currentPath, '/equity-changes')
    || str_contains($currentPath, '/financial-notes')
    || str_contains($currentPath, '/lpj')
) {
    $sectionLabel = 'Laporan';
} elseif (str_contains($currentPath, '/coa') || str_contains($currentPath, '/periods') || str_contains($currentPath, '/business-units') || str_contains($currentPath, '/reference-masters')) {
    $sectionLabel = 'Master Data';
} elseif (str_contains($currentPath, '/imports') || str_contains($currentPath, '/bank-reconciliations')) {
    $sectionLabel = 'Utilitas';
} elseif (str_contains($currentPath, '/backups')) {
    $sectionLabel = 'Utilitas';
} elseif (str_contains($currentPath, '/settings') || str_contains($currentPath, '/user-accounts') || str_contains($currentPath, '/updates')) {
    $sectionLabel = 'Pengaturan';
}

$todayLabel = function_exists('format_id_long_date') ? format_id_long_date(date('Y-m-d')) : date('d/m/Y');
?>
<header class="app-topbar">
    <div class="app-topbar__inner">
        <div class="app-topbar__left">
            <button type="button" class="app-topbar__menu" id="sidebarToggle" aria-label="Tampilkan atau sembunyikan menu" aria-expanded="true" title="Tampilkan / sembunyikan menu">
                <span></span><span></span><span></span>
            </button>

            <div class="page-meta">
                <div class="page-meta__eyebrow"><?= e($sectionLabel) ?></div>
                <div class="page-meta__title"><?= e($pageTitle) ?></div>
                <div class="page-meta__subtitle"><?= e($profile['bumdes_name'] ?: 'Sistem Pelaporan Keuangan BUMDes') ?></div>
            </div>
        </div>

        <div class="app-topbar__right">
            <div class="topbar-pill topbar-pill--muted d-none d-md-inline-flex">
                <span class="topbar-pill__label">Hari ini</span>
                <strong class="topbar-pill__value"><?= e($todayLabel) ?></strong>
            </div>

            <div class="topbar-pill topbar-pill--period d-none d-sm-inline-flex">
                <span class="topbar-pill__label">Periode</span>
                <strong class="topbar-pill__value"><?= e(current_accounting_period_label()) ?></strong>
            </div>


            <a href="<?= e(base_url('/periods/select-working')) ?>" class="topbar-pill topbar-pill--muted d-none d-md-inline-flex text-decoration-none">
                <span class="topbar-pill__label">Tahun Kerja</span>
                <strong class="topbar-pill__value"><?= e((string) current_working_year()) ?></strong>
            </a>

            <button type="button" class="topbar-pill topbar-pill--theme" id="themeToggle" aria-label="Ubah tema" title="Ubah tema">
                <span class="theme-toggle-icon" id="themeToggleIcon">☀️</span>
                <span class="theme-toggle-text" id="themeToggleText">Light</span>
            </button>

            <div class="topbar-user">
                <div class="topbar-user__avatar"><?= e(strtoupper(substr((string) ($user['username'] ?? 'U'), 0, 1))) ?></div>
                <div class="topbar-user__meta d-none d-md-block">
                    <div class="topbar-user__name"><?= e($user['full_name'] ?? '-') ?></div>
                    <div class="topbar-user__role"><?= e($user['role_name'] ?? '-') ?></div>
                </div>
            </div>


            <a href="<?= e(base_url('/periods/select-working')) ?>" class="btn btn-outline-light app-logout-btn text-decoration-none">Ganti Tahun Kerja</a>

            <form method="post" action="<?= e(base_url('/logout')) ?>" class="m-0">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn btn-outline-light app-logout-btn">Logout</button>
            </form>
        </div>
    </div>
</header>
