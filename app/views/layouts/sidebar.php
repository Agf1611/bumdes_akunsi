<?php declare(strict_types=1);

$user = Auth::user();
$profile = app_profile();
$logoPath = (string) ($profile['logo_path'] ?? '');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$active = static function (array $needles) use ($currentPath): string {
    foreach ($needles as $needle) {
        if ($needle === '/' && $currentPath === '/') {
            return ' is-active';
        }
        if ($needle === '/dashboard' && $currentPath === '/dashboard') {
            return ' is-active';
        }
        if ($needle === '/dashboard/pimpinan' && $currentPath === '/dashboard/pimpinan') {
            return ' is-active';
        }
        if ($needle === '/journals' && str_starts_with($currentPath, '/journals') && !str_starts_with($currentPath, '/journals/quick')) {
            return ' is-active';
        }
        if ($needle !== '/' && $needle !== '/dashboard' && $needle !== '/dashboard/pimpinan' && $needle !== '/journals' && str_contains($currentPath, $needle)) {
            return ' is-active';
        }
    }
    return '';
};

$icon = static function (string $name): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="8" rx="2"></rect><rect x="14" y="3" width="7" height="5" rx="2"></rect><rect x="14" y="12" width="7" height="9" rx="2"></rect><rect x="3" y="15" width="7" height="6" rx="2"></rect></svg>',
        'units' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M9 14h.01"></path><path d="M15 14h.01"></path></svg>',
        'coa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11"></path><path d="M9 12h11"></path><path d="M9 18h11"></path><path d="M4 6h.01"></path><path d="M4 12h.01"></path><path d="M4 18h.01"></path></svg>',
        'assets' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.3 7l8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
        'periods' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        'journals' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
        'ledger' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path><path d="M8 5v14"></path></svg>',
        'trial' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M4 12h10"></path><path d="M4 17h16"></path><path d="M17 10l3 2-3 2"></path></svg>',
        'profit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 7-8"></path><path d="M14 7h6v6"></path></svg>',
        'balance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"></path><path d="M5 7h5"></path><path d="M14 7h5"></path><path d="M3 7a2 2 0 0 0 4 0"></path><path d="M17 7a2 2 0 0 0 4 0"></path><path d="M5 7l-2 7"></path><path d="M19 7l2 7"></path><path d="M1 14h6"></path><path d="M17 14h6"></path></svg>',
        'cash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M7 6v12"></path><path d="M17 6v12"></path></svg>',
        'equity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"></path><path d="M7 8l5-5 5 5"></path><path d="M17 16l-5 5-5-5"></path></svg>',
        'notes' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path><path d="M8 9h3"></path></svg>',
        'lpj' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6"></path><path d="M8 12h8"></path><path d="M8 16h8"></path><path d="M8 8h3"></path></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'audit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>',
        'backup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'update' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v10"></path><path d="M8 9l4 4 4-4"></path><path d="M5 21h14"></path><path d="M5 16a7 7 0 0 0 14 0"></path></svg>',
        'health' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"></path><path d="M3.5 12h4l1.5-3 3 6 1.5-3h7"></path></svg>',
    ];

    return $icons[$name] ?? $icons['dashboard'];
};

$sections = [
    [
        'label' => 'Dashboard',
        'items' => [
            ['title' => 'Dashboard Utama', 'note' => 'Ringkasan operasional harian', 'path' => '/dashboard', 'icon' => 'dashboard', 'needles' => ['/dashboard', '/']],
            ['title' => 'Dashboard Pimpinan', 'note' => 'Fokus keputusan dan closing', 'path' => '/dashboard/pimpinan', 'icon' => 'dashboard', 'needles' => ['/dashboard/pimpinan']],
        ],
    ],
];

if (Auth::hasRole(['admin', 'bendahara'])) {
    $sections[] = [
        'label' => 'Manajemen Keuangan',
        'items' => array_values(array_filter([
            ['title' => 'Transaksi Cepat', 'note' => 'Kas masuk, keluar, dan setoran', 'path' => '/journals/quick', 'icon' => 'cash', 'needles' => ['/journals/quick']],
            ['title' => 'Jurnal Umum', 'note' => 'Double entry dan bukti transaksi', 'path' => '/journals', 'icon' => 'journals', 'needles' => ['/journals']],
            ['title' => 'Chart of Accounts', 'note' => 'Struktur akun keuangan', 'path' => '/coa', 'icon' => 'coa', 'needles' => ['/coa']],
            Auth::hasRole('admin') ? ['title' => 'Unit Usaha', 'note' => 'Cabang dan layanan usaha', 'path' => '/business-units', 'icon' => 'units', 'needles' => ['/business-units']] : null,
            ['title' => 'Periode Akuntansi', 'note' => 'Buka, tutup, dan periode aktif', 'path' => '/periods', 'icon' => 'periods', 'needles' => ['/periods']],
            ['title' => 'Aset', 'note' => 'Master aset dan penyusutan', 'path' => '/assets', 'icon' => 'assets', 'needles' => ['/assets']],
            ['title' => 'Rekonsiliasi Bank', 'note' => 'Mutasi bank vs jurnal', 'path' => '/bank-reconciliations', 'icon' => 'cash', 'needles' => ['/bank-reconciliations']],
            class_exists('ReferenceMasterController') ? ['title' => 'Referensi Jurnal', 'note' => 'Mitra dan referensi transaksi', 'path' => '/reference-masters', 'icon' => 'coa', 'needles' => ['/reference-masters']] : null,
        ])),
    ];
}

$reportItems = [];
if (class_exists('ReceivableLedgerController')) {
    $reportItems[] = ['title' => 'BP Piutang', 'note' => 'Mutasi piutang per mitra', 'path' => '/receivable-ledgers', 'icon' => 'ledger', 'needles' => ['/receivable-ledgers']];
}
if (class_exists('PayableLedgerController')) {
    $reportItems[] = ['title' => 'BP Utang', 'note' => 'Mutasi utang per kreditur', 'path' => '/payable-ledgers', 'icon' => 'ledger', 'needles' => ['/payable-ledgers']];
}
$reportItems = array_merge($reportItems, [
    ['title' => 'Buku Besar', 'note' => 'Mutasi akun dan saldo', 'path' => '/ledger', 'icon' => 'ledger', 'needles' => ['/ledger']],
    ['title' => 'Neraca Saldo', 'note' => 'Ringkasan debit dan kredit', 'path' => '/trial-balance', 'icon' => 'trial', 'needles' => ['/trial-balance']],
    ['title' => 'Laba Rugi', 'note' => 'Pendapatan dan beban', 'path' => '/profit-loss', 'icon' => 'profit', 'needles' => ['/profit-loss']],
    ['title' => 'Neraca', 'note' => 'Aset, liabilitas, ekuitas', 'path' => '/balance-sheet', 'icon' => 'balance', 'needles' => ['/balance-sheet']],
    ['title' => 'Arus Kas', 'note' => 'Pergerakan kas dan bank', 'path' => '/cash-flow', 'icon' => 'cash', 'needles' => ['/cash-flow']],
    ['title' => 'Perubahan Ekuitas', 'note' => 'Mutasi modal dan saldo akhir', 'path' => '/equity-changes', 'icon' => 'equity', 'needles' => ['/equity-changes']],
    ['title' => 'CaLK', 'note' => 'Catatan atas laporan keuangan', 'path' => '/financial-notes', 'icon' => 'notes', 'needles' => ['/financial-notes']],
    ['title' => 'Drill-down Laporan', 'note' => 'Telusuri angka ke jurnal sumber', 'path' => '/reports/drilldown', 'icon' => 'journals', 'needles' => ['/reports/drilldown']],
    ['title' => 'Paket Tutup Bulan', 'note' => 'Checklist dan bundel laporan closing', 'path' => '/closing-pack', 'icon' => 'lpj', 'needles' => ['/closing-pack']],
    ['title' => 'Paket LPJ', 'note' => 'Bundel laporan pertanggungjawaban', 'path' => '/lpj', 'icon' => 'lpj', 'needles' => ['/lpj']],
]);
$sections[] = [
    'label' => 'Laporan',
    'items' => $reportItems,
];

if (Auth::hasRole('admin')) {
    $sections[] = [
        'label' => 'Pengguna',
        'items' => [
            ['title' => 'Data User', 'note' => 'Akun pengguna aplikasi', 'path' => '/user-accounts', 'icon' => 'users', 'needles' => ['/user-accounts']],
            ['title' => 'Hak Akses & Audit', 'note' => 'Role dan riwayat aktivitas', 'path' => '/audit-logs', 'icon' => 'audit', 'needles' => ['/audit-logs']],
        ],
    ];
    $sections[] = [
        'label' => 'Pengaturan',
        'items' => [
            ['title' => 'Backup Database', 'note' => 'Cadangan data dan restore', 'path' => '/backups', 'icon' => 'backup', 'needles' => ['/backups']],
            ['title' => 'Update Aplikasi', 'note' => 'Patch dan release terbaru', 'path' => '/updates', 'icon' => 'update', 'needles' => ['/updates']],
            ['title' => 'Health Check', 'note' => 'Migration, backup, dan folder sistem', 'path' => '/settings/health', 'icon' => 'health', 'needles' => ['/settings/health']],
            ['title' => 'Profil BUMDes', 'note' => 'Identitas lembaga dan tanda tangan', 'path' => '/settings/profile', 'icon' => 'settings', 'needles' => ['/settings/profile']],
        ],
    ];
}

$categoryIcons = [
    'Dashboard' => 'dashboard',
    'Manajemen Keuangan' => 'cash',
    'Laporan' => 'ledger',
    'Pengguna' => 'users',
    'Pengaturan' => 'settings',
];

$hasActiveItem = static function (array $section) use ($active): bool {
    foreach (($section['items'] ?? []) as $item) {
        if ($active((array) ($item['needles'] ?? [])) !== '') {
            return true;
        }
    }

    return false;
};
?>
<aside class="sidebar app-sidebar" id="appSidebar" aria-label="Sidebar navigasi">
    <div class="app-sidebar__inner">
        <div class="app-sidebar__brand">
            <a href="<?= e(base_url('/dashboard')) ?>" class="app-sidebar__brand-link text-decoration-none">
                <div class="brand-mark">
                    <?php if ($logoPath !== ''): ?>
                        <img src="<?= e(upload_url($logoPath)) ?>" alt="Logo BUMDes" class="brand-mark__image">
                    <?php else: ?>
                        <span class="brand-mark__fallback">B</span>
                    <?php endif; ?>
                </div>
                <div class="brand-copy min-w-0">
                    <div class="brand-copy__label">BUMDes Finance Suite</div>
                    <div class="brand-copy__title"><?= e($profile['bumdes_name'] ?: 'BUMDes') ?></div>
                    <div class="brand-copy__meta"><?= e(current_accounting_period_label()) ?></div>
                </div>
            </a>
            <button type="button" class="app-sidebar__close d-lg-none" id="sidebarClose" aria-label="Tutup menu">&times;</button>
        </div>

        <nav class="app-nav" aria-label="Menu utama">
            <?php foreach ($sections as $section): ?>
                <?php
                    $label = (string) ($section['label'] ?? 'Kategori');
                    $items = (array) ($section['items'] ?? []);
                    $groupKey = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $label));
                    $groupIcon = (string) ($categoryIcons[$label] ?? ($items[0]['icon'] ?? 'dashboard'));
                    $groupActive = $hasActiveItem($section);
                ?>
                <div class="app-nav__group<?= $groupActive ? ' is-current' : '' ?>" data-sidebar-group="<?= e($groupKey) ?>">
                    <button
                        type="button"
                        class="app-nav__section-trigger"
                        data-sidebar-trigger
                        data-sidebar-group="<?= e($groupKey) ?>"
                        aria-expanded="false"
                    >
                        <span class="app-nav__section-marker" aria-hidden="true"></span>
                        <span class="app-nav__icon"><?= $icon($groupIcon) ?></span>
                        <span class="app-nav__section-copy">
                            <span class="app-nav__caption"><?= e($label) ?></span>
                        </span>
                        <span class="app-nav__section-badge"><?= count($items) ?></span>
                        <span class="app-nav__section-chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"></path></svg>
                        </span>
                    </button>
                    <div
                        class="app-nav__section-panel"
                        data-sidebar-panel
                        data-sidebar-group="<?= e($groupKey) ?>"
                        hidden
                    >
                        <div class="app-nav__section-rail" aria-hidden="true"></div>
                        <div class="app-nav__section-links">
                            <?php foreach ($items as $item): ?>
                                <a class="app-nav__link<?= $active((array) ($item['needles'] ?? [])) ?>" href="<?= e(base_url((string) ($item['path'] ?? '/dashboard'))) ?>">
                                    <span class="app-nav__icon"><?= $icon((string) ($item['icon'] ?? 'dashboard')) ?></span>
                                    <span class="app-nav__text">
                                        <span class="app-nav__title"><?= e((string) ($item['title'] ?? '-')) ?></span>
                                        <span class="app-nav__note"><?= e((string) ($item['note'] ?? '')) ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="app-sidebar__footer">
            <div class="sidebar-user-card">
                <div class="sidebar-user-card__eyebrow">Masuk sebagai</div>
                <div class="sidebar-user-card__name"><?= e($user['full_name'] ?? '-') ?></div>
                <div class="sidebar-user-card__meta"><?= e($user['role_name'] ?? '-') ?> &middot; <?= e($user['username'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</aside>
