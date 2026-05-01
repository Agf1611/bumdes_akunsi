<?php declare(strict_types=1);
$flashError = get_flash('error');
$flashSuccess = get_flash('success');
$pageTitle = (string) ($title ?? app_config('name'));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routeSlug = trim(str_replace('/', '-', $currentPath), '-');
$routeClass = 'route-' . ($routeSlug !== '' ? preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($routeSlug)) : 'dashboard');
if (Auth::check() && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
    $currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    workspace_track_recent_page($pageTitle, $currentUri);
}
$assetVersion = static function (string $relativePath): string {
    $file = public_path('assets/' . ltrim($relativePath, '/'));
    return is_file($file) ? (string) filemtime($file) : '1';
};
$isMobileNavActive = static function (array $needles) use ($currentPath): string {
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($currentPath, $needle)) {
            return ' is-active';
        }
    }

    return '';
};
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('bumdes_theme');
                var theme = (saved === 'dark' || saved === 'light') ? saved : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/app.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/soft-dashboard-v2.css')) ?>?v=<?= e($assetVersion('css/soft-dashboard-v2.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-final-layer.css')) ?>?v=<?= e($assetVersion('css/ui-final-layer.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-sidebar-sync.css')) ?>?v=<?= e($assetVersion('css/ui-sidebar-sync.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-shell-fix.css')) ?>?v=<?= e($assetVersion('css/ui-shell-fix.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/workspace-tools.css')) ?>?v=<?= e($assetVersion('css/workspace-tools.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-redesign.css')) ?>?v=<?= e($assetVersion('css/ui-redesign.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-module-pages.css')) ?>?v=<?= e($assetVersion('css/ui-module-pages.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-dashboard-reference.css')) ?>?v=<?= e($assetVersion('css/ui-dashboard-reference.css')) ?>" rel="stylesheet">
</head>
<body class="app-shell ui-ready <?= e($routeClass) ?>">
<div class="app-frame">
    <?php require APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main" id="appMain">
        <?php require APP_PATH . '/views/layouts/topbar.php'; ?>

        <main class="app-content">
            <div class="content-wrap container-fluid">
                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success alert-dismissible fade show ui-alert" role="alert">
                        <div class="ui-alert-title">Berhasil</div>
                        <div><?= e($flashSuccess) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert alert-danger alert-dismissible fade show ui-alert" role="alert">
                        <div class="ui-alert-title">Perlu perhatian</div>
                        <div><?= e($flashError) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </main>

        <?php require APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>
<nav class="mobile-bottom-nav" aria-label="Navigasi utama mobile">
    <a class="mobile-bottom-nav__item<?= e($isMobileNavActive(['/dashboard'])) ?>" href="<?= e(base_url('/dashboard')) ?>">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.8 12 4l8 6.8V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Dashboard</span>
    </a>
    <a class="mobile-bottom-nav__item<?= e($isMobileNavActive(['/journals/quick'])) ?>" href="<?= e(base_url('/journals/quick')) ?>">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M12 5v14M6 20h12a2 2 0 0 0 2-2V8.5L15.5 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Transaksi</span>
    </a>
    <a class="mobile-bottom-nav__item<?= e($isMobileNavActive(['/profit-loss', '/balance-sheet', '/cash-flow', '/trial-balance', '/ledger'])) ?>" href="<?= e(base_url('/profit-loss')) ?>">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 20V9m6 11V4m6 16v-7M4 20h16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Laporan</span>
    </a>
    <a class="mobile-bottom-nav__item<?= e(str_contains($currentPath, '/journals') && !str_contains($currentPath, '/journals/quick') ? ' is-active' : '') ?>" href="<?= e(base_url('/journals')) ?>">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h12a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm3 5h6M9 13h6M9 17h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Jurnal</span>
    </a>
    <button class="mobile-bottom-nav__item" type="button" data-mobile-menu-toggle>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Menu</span>
    </button>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/theme.js')) ?>?v=<?= e($assetVersion('js/theme.js')) ?>"></script>
<script src="<?= e(asset_url('js/workspace-tools.js')) ?>?v=<?= e($assetVersion('js/workspace-tools.js')) ?>"></script>
<script src="<?= e(asset_url('js/report-filters.js')) ?>?v=<?= e($assetVersion('js/report-filters.js')) ?>"></script>
<script>
    (function () {
        var storagePrefix = 'bumdes_scroll_restore:';
        var storagePathPrefix = 'bumdes_scroll_restore_path:';
        var restoreTtlMs = 10 * 60 * 1000;

        function normalizeUrl(input) {
            try {
                var url = new URL(input, window.location.origin);
                url.hash = '';
                return url.toString();
            } catch (error) {
                return '';
            }
        }

        function currentPageKey() {
            return storagePrefix + normalizeUrl(window.location.href);
        }

        function currentPathKey() {
            try {
                var url = new URL(window.location.href, window.location.origin);
                return storagePathPrefix + url.origin + url.pathname;
            } catch (error) {
                return storagePathPrefix + window.location.pathname;
            }
        }

        function saveScrollPosition() {
            try {
                var payload = JSON.stringify({
                    y: window.scrollY || window.pageYOffset || 0,
                    savedAt: Date.now()
                });
                sessionStorage.setItem(currentPageKey(), payload);
                sessionStorage.setItem(currentPathKey(), payload);
            } catch (error) {
                return;
            }
        }

        function restoreScrollPosition() {
            if (window.location.hash) {
                return;
            }

            try {
                var exactKey = currentPageKey();
                var pathKey = currentPathKey();
                var raw = sessionStorage.getItem(exactKey) || sessionStorage.getItem(pathKey);
                if (!raw) {
                    return;
                }

                var payload = JSON.parse(raw);
                var y = Number(payload && payload.y ? payload.y : 0);
                var savedAt = Number(payload && payload.savedAt ? payload.savedAt : 0);
                if (!Number.isFinite(y) || y < 0) {
                    sessionStorage.removeItem(exactKey);
                    sessionStorage.removeItem(pathKey);
                    return;
                }
                if (!Number.isFinite(savedAt) || (Date.now() - savedAt) > restoreTtlMs) {
                    sessionStorage.removeItem(exactKey);
                    sessionStorage.removeItem(pathKey);
                    return;
                }

                var applyScroll = function () {
                    window.scrollTo(0, y);
                };

                requestAnimationFrame(applyScroll);
                window.addEventListener('load', function () {
                    setTimeout(applyScroll, 40);
                }, { once: true });
                sessionStorage.removeItem(exactKey);
                sessionStorage.removeItem(pathKey);
            } catch (error) {
                try {
                    sessionStorage.removeItem(currentPageKey());
                    sessionStorage.removeItem(currentPathKey());
                } catch (storageError) {
                    return;
                }
            }
        }

        document.addEventListener('submit', function (event) {
            var form = event.target instanceof HTMLFormElement ? event.target : null;
            if (!form) {
                return;
            }

            var method = String(form.method || 'get').toUpperCase();
            if (method === 'GET') {
                return;
            }

            saveScrollPosition();
        }, true);

        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            if (link.target && link.target !== '_self') {
                return;
            }
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }

            var href = normalizeUrl(link.href || '');
            var current = normalizeUrl(window.location.href);
            if (href === '' || current === '' || href === current) {
                return;
            }

            saveScrollPosition();
        }, true);

        window.addEventListener('pagehide', saveScrollPosition);
        restoreScrollPosition();
    })();

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-mobile-menu-toggle]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        var sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.click();
            return;
        }

        document.body.classList.add('sidebar-open');
        document.body.style.overflow = 'hidden';
    });
</script>
</body>
</html>
