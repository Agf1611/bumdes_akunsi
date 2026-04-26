<?php declare(strict_types=1);

$user = Auth::user();
$profile = app_profile();
$pageTitle = (string) ($title ?? 'Aplikasi');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$sectionLabel = 'Workspace';

if (str_contains($currentPath, '/assets')) {
    $sectionLabel = 'Manajemen Aset';
} elseif (str_contains($currentPath, '/journals')) {
    $sectionLabel = 'Manajemen Keuangan';
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
} elseif (
    str_contains($currentPath, '/coa')
    || str_contains($currentPath, '/periods')
    || str_contains($currentPath, '/business-units')
    || str_contains($currentPath, '/reference-masters')
) {
    $sectionLabel = 'Data Inti';
} elseif (str_contains($currentPath, '/bank-reconciliations') || str_contains($currentPath, '/backups') || str_contains($currentPath, '/updates')) {
    $sectionLabel = 'Sistem';
} elseif (str_contains($currentPath, '/settings') || str_contains($currentPath, '/user-accounts') || str_contains($currentPath, '/audit-logs')) {
    $sectionLabel = 'Pengaturan';
}

$icon = static function (string $name): string {
    $icons = [
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path><path d="M10 17a2 2 0 0 0 4 0"></path></svg>',
        'star' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.8 5.6 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20l1.1-6.2L3 9.5l6.2-.9L12 3z"></path></svg>',
        'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"></path><path d="M7 12h10"></path><path d="M10 18h4"></path></svg>',
        'chevron' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"></path></svg>',
        'sun' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>',
        'moon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>',
    ];

    return $icons[$name] ?? '';
};

$workspacePalette = workspace_command_palette_bootstrap();
$favoritePages = workspace_favorite_pages();
$currentRequestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$currentFavorite = false;
foreach ($favoritePages as $favoritePage) {
    if ((string) ($favoritePage['path'] ?? '') === $currentRequestUri) {
        $currentFavorite = true;
        break;
    }
}
$filterTargets = workspace_filter_targets();
$canSaveFilter = array_key_exists($currentPath, $filterTargets) && ((string) parse_url($currentRequestUri, PHP_URL_QUERY) !== '');
?>
<header class="app-topbar">
    <div class="app-topbar__inner">
        <div class="app-topbar__left">
            <div class="app-topbar__search-row">
                <button type="button" class="command-search" id="commandPaletteToggle" title="Cari menu, jurnal, akun, laporan, dan data master">
                    <span class="command-search__icon"><?= $icon('search') ?></span>
                    <span class="command-search__text">Cari menu, jurnal, laporan, atau akun...</span>
                    <span class="command-search__shortcut">Ctrl + K</span>
                </button>
            </div>

            <div class="app-topbar__title-row">
                <button type="button" class="app-topbar__menu" id="sidebarToggle" aria-label="Tampilkan atau sembunyikan menu" aria-expanded="true" title="Menu navigasi">
                    <span></span><span></span><span></span>
                </button>
                <div class="page-meta">
                    <div class="page-meta__eyebrow"><?= e($sectionLabel) ?></div>
                    <div class="page-meta__title"><?= e($pageTitle) ?></div>
                    <div class="page-meta__subtitle"><?= e($profile['bumdes_name'] ?: 'Sistem Akuntansi BUMDes') ?></div>
                </div>
            </div>
        </div>

        <div class="app-topbar__utility">
            <div class="app-topbar__meta-row">
                <div class="app-topbar__right">
                <div class="topbar-badges d-none d-md-flex">
                    <a href="<?= e(base_url('/periods')) ?>" class="topbar-badge-chip topbar-badge-chip--link" title="Buka menu periode akuntansi">
                        <span class="topbar-badge-chip__label">Periode</span>
                        <strong class="topbar-badge-chip__value"><?= e(current_accounting_period_label()) ?></strong>
                    </a>
                    <a href="<?= e(base_url('/periods/select-working')) ?>" class="topbar-badge-chip topbar-badge-chip--link" title="Buka menu pilih tahun kerja">
                        <span class="topbar-badge-chip__label">Tahun Kerja</span>
                        <strong class="topbar-badge-chip__value"><?= e((string) current_working_year()) ?></strong>
                    </a>
                </div>

                <div class="topbar-actions">
                    <button type="button" class="topbar-theme-chip d-none d-lg-inline-flex" data-theme-toggle aria-pressed="false" title="Ganti mode terang atau gelap">
                        <span class="theme-toggle-icon" data-theme-icon><?= $icon('sun') ?></span>
                        <span class="theme-toggle-text" data-theme-text>Light</span>
                    </button>

                    <form method="post" action="<?= e(base_url('/workspace/toggle-favorite')) ?>" class="m-0 d-none d-lg-block" id="workspaceFavoriteForm">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="title" value="<?= e($pageTitle) ?>">
                        <input type="hidden" name="path" value="<?= e($currentRequestUri) ?>">
                        <button type="submit" class="topbar-icon-button <?= $currentFavorite ? 'is-favorited' : '' ?>" id="workspaceFavoriteButton" title="Simpan halaman ini ke favorit">
                            <?= $icon('star') ?>
                        </button>
                    </form>

                    <?php if ($canSaveFilter): ?>
                        <button type="button" class="topbar-icon-button d-none d-lg-inline-flex" id="saveCurrentFilterButton" title="Simpan filter halaman ini">
                            <?= $icon('filter') ?>
                        </button>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="topbar-icon-button" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Notifikasi dan shortcut sistem">
                            <?= $icon('bell') ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-dropdown topbar-dropdown--notify">
                            <div class="topbar-dropdown__title">Notifikasi Sistem</div>
                            <div class="topbar-dropdown__copy">Belum ada notifikasi real-time. Gunakan shortcut berikut untuk pekerjaan yang paling sering dibuka.</div>
                            <a class="dropdown-item" href="<?= e(base_url('/journals/quick')) ?>">Transaksi Cepat</a>
                            <a class="dropdown-item" href="<?= e(base_url('/periods')) ?>">Checklist Periode</a>
                            <?php if (Auth::hasRole('admin')): ?>
                                <a class="dropdown-item" href="<?= e(base_url('/backups')) ?>">Backup Database</a>
                                <a class="dropdown-item" href="<?= e(base_url('/updates')) ?>">Update Aplikasi</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="topbar-profile" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <span class="topbar-profile__avatar"><?= e(strtoupper(substr((string) ($user['username'] ?? 'U'), 0, 1))) ?></span>
                            <span class="topbar-profile__meta d-none d-md-flex">
                                <span class="topbar-profile__name"><?= e($user['full_name'] ?? '-') ?></span>
                                <span class="topbar-profile__role"><?= e($user['role_name'] ?? '-') ?></span>
                            </span>
                            <span class="topbar-profile__chevron"><?= $icon('chevron') ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-dropdown">
                            <div class="topbar-dropdown__title"><?= e($user['full_name'] ?? '-') ?></div>
                            <div class="topbar-dropdown__copy"><?= e($user['username'] ?? '-') ?> &middot; <?= e($user['role_name'] ?? '-') ?></div>
                            <a class="dropdown-item" href="<?= e(base_url('/periods/select-working')) ?>">Ganti Tahun Kerja</a>
                            <button type="button" class="dropdown-item topbar-theme-action" data-theme-toggle aria-pressed="false">
                                <span class="theme-toggle-icon" data-theme-icon><?= $icon('sun') ?></span>
                                <span class="theme-toggle-text" data-theme-text>Light</span>
                            </button>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= e(base_url('/logout')) ?>" class="m-0">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="dropdown-item text-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</header>
<div class="workspace-palette" id="workspacePalette" hidden
     data-search-url="<?= e((string) ($workspacePalette['search_url'] ?? '')) ?>"
     data-app-base-url="<?= e(base_url()) ?>"
     data-save-filter-url="<?= e((string) ($workspacePalette['save_filter_url'] ?? '')) ?>"
     data-csrf="<?= e(csrf_token()) ?>"
     data-page-title="<?= e($pageTitle) ?>"
     data-page-path="<?= e($currentRequestUri) ?>"
     data-page-label="<?= e((string) ($filterTargets[$currentPath] ?? $pageTitle)) ?>"
     data-can-save-filter="<?= $canSaveFilter ? '1' : '0' ?>"
     data-bootstrap='<?= e(json_encode($workspacePalette, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?>'>
    <div class="workspace-palette__backdrop" data-close-palette></div>
    <div class="workspace-palette__panel">
        <div class="workspace-palette__head">
            <div>
                <div class="workspace-palette__eyebrow">Quick Search</div>
                <div class="workspace-palette__title">Cari menu, jurnal, akun, laporan, dan data master</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-light" data-close-palette>Tutup</button>
        </div>
        <div class="workspace-palette__body">
            <input type="text" class="form-control workspace-palette__input" id="workspacePaletteInput" placeholder="Ketik nama menu, akun COA, nomor jurnal, periode, atau user...">
            <div class="workspace-palette__hint">Gunakan Ctrl+K untuk membuka pencarian cepat dari halaman mana pun.</div>
            <div class="workspace-palette__sections" id="workspacePaletteResults"></div>
        </div>
    </div>
</div>
