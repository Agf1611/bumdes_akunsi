<?php declare(strict_types=1);
$flashError = get_flash('error');
$flashSuccess = get_flash('success');
$pageTitle = (string) ($title ?? app_config('name'));
$assetVersion = static function (string $relativePath): string {
    $file = public_path('assets/' . ltrim($relativePath, '/'));
    return is_file($file) ? (string) filemtime($file) : '1';
};
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
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
    <link href="<?= e(asset_url('css/app.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/soft-dashboard-v2.css')) ?>?v=<?= e($assetVersion('css/soft-dashboard-v2.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-final-layer.css')) ?>?v=<?= e($assetVersion('css/ui-final-layer.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-sidebar-sync.css')) ?>?v=<?= e($assetVersion('css/ui-sidebar-sync.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('css/ui-shell-fix.css')) ?>?v=<?= e($assetVersion('css/ui-shell-fix.css')) ?>" rel="stylesheet">
</head>
<body class="app-shell ui-ready">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/theme.js')) ?>"></script>
</body>
</html>
