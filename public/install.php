<?php

declare(strict_types=1);

const ROOT_PATH = __DIR__ . '/..';
const APP_PATH = ROOT_PATH . '/app';

require APP_PATH . '/install/Installer.php';

$appConfig = require APP_PATH . '/config/app.php';
date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Asia/Jakarta'));

session_name((string) ($appConfig['session_name'] ?? 'BUMDESINSTALLSESSID'));
session_set_cookie_params([
    'lifetime' => (int) ($appConfig['session_lifetime'] ?? 7200),
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$installer = new Installer(ROOT_PATH, APP_PATH, ROOT_PATH . '/storage');
$csrfToken = $_SESSION['_installer_csrf'] ?? '';
if (!is_string($csrfToken) || $csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['_installer_csrf'] = $csrfToken;
}

function installer_e(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function installer_old(string $key, string $default = ''): string
{
    $old = $_SESSION['_installer_old'] ?? [];
    return isset($old[$key]) ? (string) $old[$key] : $default;
}

function installer_set_old(array $input): void
{
    $_SESSION['_installer_old'] = $input;
}

function installer_clear_old(): void
{
    unset($_SESSION['_installer_old']);
}

function installer_flash_set(string $key, array $messages): void
{
    $_SESSION['_installer_flash_' . $key] = $messages;
}

function installer_flash_get(string $key): array
{
    $sessionKey = '_installer_flash_' . $key;
    $messages = $_SESSION[$sessionKey] ?? [];
    unset($_SESSION[$sessionKey]);
    return is_array($messages) ? $messages : [];
}

function installer_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    }
    return $scheme . '://' . $host . $basePath;
}

if ($installer->isInstalled()) {
    $loginUrl = installer_base_url() . '/index.php';
    http_response_code(200);
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi Sudah Terinstall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#0f172a;color:#e5e7eb;min-height:100vh;display:flex;align-items:center}
        .install-card{max-width:760px;background:#111827;border:1px solid #334155;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.25)}
        .muted{color:#94a3b8}
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card install-card mx-auto">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 mb-3">Aplikasi sudah terinstall</h1>
            <p class="muted mb-4">Installer mendeteksi file lock instalasi. Demi keamanan, installer tidak bisa dijalankan ulang melalui browser.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= installer_e($loginUrl) ?>" class="btn btn-primary">Buka Halaman Login</a>
            </div>
            <hr class="border-secondary-subtle my-4">
            <p class="small muted mb-0">Jika Anda memang perlu mengulang instalasi, hapus file <code>storage/installed.lock</code> dan <code>app/config/generated.php</code> secara manual, lalu kosongkan database sebelum menjalankan installer lagi.</p>
        </div>
    </div>
</div>
</body>
</html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['_token'] ?? '');
    if (!hash_equals($csrfToken, $postedToken)) {
        installer_flash_set('error', ['Token keamanan tidak valid. Silakan muat ulang halaman installer lalu coba lagi.']);
        header('Location: install.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? 'install');
    $input = [
        'db_host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')),
        'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
        'db_name' => trim((string) ($_POST['db_name'] ?? '')),
        'db_user' => trim((string) ($_POST['db_user'] ?? '')),
        'db_pass' => (string) ($_POST['db_pass'] ?? ''),
        'app_url' => trim((string) ($_POST['app_url'] ?? $installer->getDefaultAppUrl())),
        'admin_name' => trim((string) ($_POST['admin_name'] ?? '')),
        'admin_username' => trim((string) ($_POST['admin_username'] ?? 'admin')),
        'admin_password' => (string) ($_POST['admin_password'] ?? ''),
        'admin_password_confirm' => (string) ($_POST['admin_password_confirm'] ?? ''),
    ];
    installer_set_old($input);

    if ($action === 'test-db') {
        [$ok, $message] = $installer->testDatabaseConnection($input);
        installer_flash_set($ok ? 'success' : 'error', [$message]);
        header('Location: install.php');
        exit;
    }

    [$ok, $messages] = $installer->install($input);
    if ($ok) {
        installer_clear_old();
        installer_flash_set('success', $messages);
    } else {
        installer_flash_set('error', $messages);
    }

    header('Location: install.php');
    exit;
}

$errors = installer_flash_get('error');
$success = installer_flash_get('success');
$checks = $installer->getEnvironmentChecks();
$appUrlDefault = $installer->getDefaultAppUrl();
$loginUrl = installer_base_url() . '/index.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installer Aplikasi BUMDes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:linear-gradient(180deg,#0b1220,#0f172a);color:#e5e7eb;min-height:100vh}
        .install-wrapper{padding:32px 0 64px}
        .install-card{background:#111827;border:1px solid #334155;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.25)}
        .install-card .card-header{background:#131c2e;border-bottom:1px solid #334155;border-radius:20px 20px 0 0}
        .muted{color:#94a3b8}
        .form-control,.form-select{background:#0f172a;border-color:#334155;color:#e5e7eb}
        .form-control:focus,.form-select:focus{background:#0f172a;color:#e5e7eb;border-color:#3b82f6;box-shadow:0 0 0 .2rem rgba(59,130,246,.15)}
        .form-text{color:#94a3b8}
        .table-dark-custom{--bs-table-bg:#0f172a;--bs-table-striped-bg:#111827;--bs-table-color:#e5e7eb;--bs-table-border-color:#334155}
        .check-ok{color:#22c55e;font-weight:600}
        .check-bad{color:#ef4444;font-weight:600}
        code{color:#93c5fd}
    </style>
</head>
<body>
<div class="container install-wrapper">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card install-card mb-4">
                <div class="card-header p-4">
                    <h1 class="h3 mb-1">Installer Aplikasi BUMDes</h1>
                    <p class="muted mb-0">Pasang aplikasi melalui browser tanpa CLI atau SSH. Installer ini akan mengecek server, menyimpan konfigurasi database, mengimpor struktur database, membuat akun admin pertama, dan menandai aplikasi sebagai sudah terinstall.</p>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <?php if ($success !== []): ?>
                        <div class="alert alert-success">
                            <?php foreach ($success as $message): ?>
                                <div><?= installer_e($message) ?></div>
                            <?php endforeach; ?>
                            <hr>
                            <a href="<?= installer_e($loginUrl) ?>" class="btn btn-success btn-sm">Buka Halaman Login</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger">
                            <div class="fw-semibold mb-2">Installer belum bisa dilanjutkan:</div>
                            <ul class="mb-0">
                                <?php foreach ($errors as $message): ?>
                                    <li><?= installer_e($message) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-12 col-lg-5">
                            <h2 class="h5 mb-3">1. Pengecekan sistem</h2>
                            <div class="table-responsive">
                                <table class="table table-dark-custom table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Pengecekan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($checks as $check): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium"><?= installer_e($check['label']) ?></div>
                                                    <div class="small muted">Saat ini: <?= installer_e((string) $check['current']) ?> &middot; Wajib: <?= installer_e((string) $check['required']) ?></div>
                                                </td>
                                                <td class="text-center <?= $check['ok'] ? 'check-ok' : 'check-bad' ?>">
                                                    <?= $check['ok'] ? 'OK' : 'Gagal' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-text mt-3">Jika ada yang berstatus gagal, perbaiki dulu sebelum menjalankan instalasi agar aplikasi tidak error saat dipakai.</div>
                        </div>

                        <div class="col-12 col-lg-7">
                            <h2 class="h5 mb-3">2. Konfigurasi instalasi</h2>
                            <form method="post" action="install.php" novalidate>
                                <input type="hidden" name="_token" value="<?= installer_e($csrfToken) ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_host">Host Database <span class="text-danger">*</span></label>
                                        <input class="form-control" id="db_host" name="db_host" value="<?= installer_e(installer_old('db_host', '127.0.0.1')) ?>" maxlength="150" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_port">Port Database <span class="text-danger">*</span></label>
                                        <input class="form-control" id="db_port" name="db_port" value="<?= installer_e(installer_old('db_port', '3306')) ?>" maxlength="5" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_name">Nama Database <span class="text-danger">*</span></label>
                                        <input class="form-control" id="db_name" name="db_name" value="<?= installer_e(installer_old('db_name')) ?>" maxlength="150" required>
                                        <div class="form-text">Database harus sudah dibuat terlebih dahulu dari panel hosting Anda.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_user">User Database <span class="text-danger">*</span></label>
                                        <input class="form-control" id="db_user" name="db_user" value="<?= installer_e(installer_old('db_user')) ?>" maxlength="150" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="db_pass">Password Database</label>
                                        <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?= installer_e(installer_old('db_pass')) ?>" autocomplete="new-password">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="app_url">URL Aplikasi <span class="text-danger">*</span></label>
                                        <input class="form-control" id="app_url" name="app_url" value="<?= installer_e(installer_old('app_url', $appUrlDefault)) ?>" required>
                                        <div class="form-text">Contoh: <code><?= installer_e($appUrlDefault) ?></code></div>
                                    </div>
                                </div>

                                <hr class="border-secondary-subtle my-4">

                                <h2 class="h5 mb-3">3. Akun admin pertama</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="admin_name">Nama Admin <span class="text-danger">*</span></label>
                                        <input class="form-control" id="admin_name" name="admin_name" value="<?= installer_e(installer_old('admin_name', 'Administrator BUMDes')) ?>" maxlength="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="admin_username">Username Admin <span class="text-danger">*</span></label>
                                        <input class="form-control" id="admin_username" name="admin_username" value="<?= installer_e(installer_old('admin_username', 'admin')) ?>" maxlength="50" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="admin_password">Password Admin <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" autocomplete="new-password" required>
                                        <div class="form-text">Minimal 8 karakter.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="admin_password_confirm">Konfirmasi Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" autocomplete="new-password" required>
                                    </div>
                                </div>

                                <div class="d-flex flex-column flex-md-row gap-2 mt-4">
                                    <button type="submit" name="action" value="test-db" class="btn btn-outline-light">Tes Koneksi Database</button>
                                    <button type="submit" name="action" value="install" class="btn btn-primary">Mulai Instalasi</button>
                                </div>
                                <div class="form-text mt-3">Installer akan menulis file <code>app/config/generated.php</code> dan membuat file lock <code>storage/installed.lock</code>. Setelah berhasil, installer akan dikunci otomatis demi keamanan.</div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
