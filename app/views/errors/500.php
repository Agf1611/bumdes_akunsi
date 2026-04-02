<?php declare(strict_types=1);
$debug = isset($debug) ? (bool) $debug : false;
$message = isset($message) && is_string($message) && $message !== ''
    ? $message
    : 'Sistem sedang mengalami kendala. Silakan coba lagi atau hubungi administrator.';
$exception = isset($exception) && $exception instanceof Throwable ? $exception : null;
?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Terjadi Kesalahan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="bg-body-dark text-light d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark-subtle border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="display-6 mb-3">500</div>
                        <h1 class="h3 mb-3">Maaf, terjadi kesalahan pada aplikasi</h1>
                        <p class="text-secondary mb-0"><?= e($message) ?></p>
                    </div>

                    <?php if ($debug && $exception instanceof Throwable): ?>
                        <div class="alert alert-warning text-break small">
                            <strong>Debug:</strong><br>
                            <?= e($exception->getMessage()) ?><br>
                            <?= e($exception->getFile()) ?>:<?= e((string) $exception->getLine()) ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="<?= e(base_url('/login')) ?>" class="btn btn-primary">Kembali ke Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
