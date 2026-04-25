<?php

declare(strict_types=1);

function workspace_preferences(): array
{
    $userId = (int) (Auth::user()['id'] ?? 0);
    return UserPreferenceStore::instance()->all($userId);
}

function workspace_preference_get(string $key, mixed $default = null): mixed
{
    $userId = (int) (Auth::user()['id'] ?? 0);
    return UserPreferenceStore::instance()->get($userId, $key, $default);
}

function workspace_preference_put(string $key, mixed $value): void
{
    $userId = (int) (Auth::user()['id'] ?? 0);
    UserPreferenceStore::instance()->put($userId, $key, $value);
}

function workspace_preference_forget(string $key): void
{
    $userId = (int) (Auth::user()['id'] ?? 0);
    UserPreferenceStore::instance()->forget($userId, $key);
}

function workspace_track_recent_page(string $title, string $path, string $kind = 'page'): void
{
    if (!Auth::check()) {
        return;
    }

    $pathOnly = parse_url($path, PHP_URL_PATH) ?: $path;
    if (
        $pathOnly === '/login'
        || $pathOnly === '/logout'
        || str_starts_with($pathOnly, '/search/')
        || str_starts_with($pathOnly, '/workspace/')
    ) {
        return;
    }

    $recent = workspace_preference_get('recent_items', []);
    if (!is_array($recent)) {
        $recent = [];
    }

    $normalizedPath = workspace_normalize_path((string) $path);
    $item = [
        'title' => trim($title) !== '' ? $title : 'Halaman',
        'path' => $normalizedPath,
        'kind' => $kind,
        'visited_at' => date('Y-m-d H:i:s'),
    ];

    $recent = array_values(array_filter($recent, static fn (array $existing): bool => (string) ($existing['path'] ?? '') !== $normalizedPath));
    array_unshift($recent, $item);
    workspace_preference_put('recent_items', array_slice($recent, 0, 8));
}

function workspace_favorite_pages(): array
{
    $favorites = workspace_preference_get('favorite_pages', []);
    if (!is_array($favorites)) {
        return [];
    }

    return array_values(array_map(
        static function (array $item): array {
            $item['path'] = workspace_normalize_path((string) ($item['path'] ?? '/dashboard'));
            return $item;
        },
        $favorites
    ));
}

function workspace_saved_filters(): array
{
    $filters = workspace_preference_get('saved_filters', []);
    if (!is_array($filters)) {
        return [];
    }

    return array_values(array_map(
        static function (array $item): array {
            $item['path'] = workspace_normalize_path((string) ($item['path'] ?? '/dashboard'));
            return $item;
        },
        $filters
    ));
}

function workspace_recent_items(): array
{
    $recent = workspace_preference_get('recent_items', []);
    if (!is_array($recent)) {
        return [];
    }

    return array_values(array_map(
        static function (array $item): array {
            $item['path'] = workspace_normalize_path((string) ($item['path'] ?? '/dashboard'));
            return $item;
        },
        $recent
    ));
}

function workspace_normalize_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '/dashboard';
    }

    $parsedPath = parse_url($path, PHP_URL_PATH);
    $parsedQuery = parse_url($path, PHP_URL_QUERY);
    $normalized = $parsedPath !== false && $parsedPath !== null ? (string) $parsedPath : $path;
    $normalized = '/' . ltrim($normalized, '/');

    $basePath = (string) (parse_url(base_url(), PHP_URL_PATH) ?? '');
    $basePath = '/' . trim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }

    if ($basePath !== '' && str_starts_with($normalized, $basePath . '/')) {
        $normalized = substr($normalized, strlen($basePath));
        $normalized = $normalized === '' ? '/' : $normalized;
    } elseif ($basePath !== '' && $normalized === $basePath) {
        $normalized = '/';
    }

    if ($parsedQuery !== false && $parsedQuery !== null && $parsedQuery !== '') {
        $normalized .= '?' . $parsedQuery;
    }

    return $normalized;
}

function workspace_filter_targets(): array
{
    return [
        '/dashboard' => 'Dashboard',
        '/dashboard/pimpinan' => 'Dashboard Pimpinan',
        '/journals' => 'Jurnal Umum',
        '/ledger' => 'Buku Besar',
        '/trial-balance' => 'Neraca Saldo',
        '/profit-loss' => 'Laba Rugi',
        '/balance-sheet' => 'Neraca',
        '/cash-flow' => 'Arus Kas',
        '/equity-changes' => 'Perubahan Ekuitas',
        '/financial-notes' => 'CaLK',
        '/lpj' => 'Paket LPJ',
    ];
}

function workspace_menu_index(): array
{
    $items = [
        ['title' => 'Dashboard', 'path' => '/dashboard', 'category' => 'Menu'],
        ['title' => 'Dashboard Pimpinan', 'path' => '/dashboard/pimpinan', 'category' => 'Menu'],
        ['title' => 'Jurnal Umum', 'path' => '/journals', 'category' => 'Transaksi'],
        ['title' => 'Transaksi Cepat', 'path' => '/journals/quick', 'category' => 'Transaksi'],
        ['title' => 'Periode Akuntansi', 'path' => '/periods', 'category' => 'Master Data'],
        ['title' => 'Chart of Accounts', 'path' => '/coa', 'category' => 'Master Data'],
        ['title' => 'Aset', 'path' => '/assets', 'category' => 'Master Data'],
        ['title' => 'Buku Besar', 'path' => '/ledger', 'category' => 'Laporan'],
        ['title' => 'Neraca Saldo', 'path' => '/trial-balance', 'category' => 'Laporan'],
        ['title' => 'Laba Rugi', 'path' => '/profit-loss', 'category' => 'Laporan'],
        ['title' => 'Neraca', 'path' => '/balance-sheet', 'category' => 'Laporan'],
        ['title' => 'Arus Kas', 'path' => '/cash-flow', 'category' => 'Laporan'],
        ['title' => 'Perubahan Ekuitas', 'path' => '/equity-changes', 'category' => 'Laporan'],
        ['title' => 'CaLK', 'path' => '/financial-notes', 'category' => 'Laporan'],
        ['title' => 'Paket LPJ', 'path' => '/lpj', 'category' => 'Laporan'],
        ['title' => 'Rekonsiliasi Bank', 'path' => '/bank-reconciliations', 'category' => 'Utilitas'],
        ['title' => 'Backup Database', 'path' => '/backups', 'category' => 'Utilitas'],
        ['title' => 'Update Aplikasi', 'path' => '/updates', 'category' => 'Utilitas'],
        ['title' => 'Akun Pengguna', 'path' => '/user-accounts', 'category' => 'Pengaturan'],
        ['title' => 'Audit Trail', 'path' => '/audit-logs', 'category' => 'Pengaturan'],
        ['title' => 'Profil BUMDes', 'path' => '/settings/profile', 'category' => 'Pengaturan'],
    ];

    if (class_exists('ReceivableLedgerController')) {
        $items[] = ['title' => 'BP Piutang', 'path' => '/receivable-ledgers', 'category' => 'Laporan'];
    }
    if (class_exists('PayableLedgerController')) {
        $items[] = ['title' => 'BP Utang', 'path' => '/payable-ledgers', 'category' => 'Laporan'];
    }

    return $items;
}

function workspace_command_palette_bootstrap(): array
{
    return [
        'favorites' => workspace_favorite_pages(),
        'recent' => workspace_recent_items(),
        'saved_filters' => workspace_saved_filters(),
        'search_url' => base_url('/search/global'),
        'toggle_favorite_url' => base_url('/workspace/toggle-favorite'),
        'save_filter_url' => base_url('/workspace/save-filter'),
    ];
}
