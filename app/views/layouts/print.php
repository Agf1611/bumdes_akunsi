<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? app_config('name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/ui-final-layer.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/print-professional.css')) ?>">
</head>
<body class="print-layout ui-print">
    <main class="print-page-wrap container">
        <?= $content ?>
        <div class="print-generated-meta text-end"><?= e(report_print_generated_meta()) ?></div>
    </main>
</body>
</html>
