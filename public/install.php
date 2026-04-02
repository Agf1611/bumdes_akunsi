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
    <title>Aplikasi Sudah Terinstal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --bg:#f3f7ff;
            --bg-accent:#e8f0ff;
            --surface:#ffffff;
            --surface-soft:#f8fbff;
            --border:#d9e3f4;
            --text:#10213a;
            --muted:#5c6f91;
            --primary:#2563eb;
            --primary-dark:#1d4ed8;
            --shadow:0 28px 60px rgba(15, 23, 42, .10);
        }
        body{min-height:100vh;background:radial-gradient(circle at top left,var(--bg-accent),transparent 32%),linear-gradient(180deg,#f8fbff 0%,var(--bg) 100%);color:var(--text);display:flex;align-items:center}
        .install-card{max-width:860px;background:rgba(255,255,255,.92);border:1px solid rgba(217,227,244,.9);border-radius:28px;box-shadow:var(--shadow);backdrop-filter:blur(10px)}
        .hero-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem .9rem;border-radius:999px;background:#eef4ff;color:var(--primary);font-weight:700;font-size:.82rem;letter-spacing:.02em}
        .hero-icon{width:52px;height:52px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg,#dbeafe,#eff6ff);color:var(--primary);box-shadow:inset 0 1px 0 rgba(255,255,255,.85)}
        .info-card{background:var(--surface-soft);border:1px solid var(--border);border-radius:20px;padding:1rem 1.1rem}
        .muted{color:var(--muted)}
        code{color:var(--primary-dark);background:#eef4ff;padding:.12rem .35rem;border-radius:.45rem}
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card install-card mx-auto border-0">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row align-items-start gap-3 mb-4">
                <div class="hero-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6a.5.5 0 0 0 .708.708L3 7.207V14.5a.5.5 0 0 0 .5.5h3.5v-4.5a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1V15h3.5a.5.5 0 0 0 .5-.5V7.207l.646.647a.5.5 0 0 0 .708-.708z"/></svg>
                </div>
                <div class="flex-grow-1">
                    <span class="hero-badge mb-3">Instalasi telah diamankan</span>
                    <h1 class="h3 mb-2">Aplikasi sudah terinstal</h1>
                    <p class="muted mb-0">Installer mendeteksi file lock instalasi. Demi keamanan, proses pemasangan ulang tidak bisa dijalankan langsung dari browser.</p>
                </div>
            </div>
            <div class="info-card mb-4">
                <div class="fw-semibold mb-2">Yang bisa Anda lakukan sekarang</div>
                <ul class="mb-0 muted ps-3">
                    <li>Buka halaman login dan gunakan akun admin yang sudah dibuat.</li>
                    <li>Simpan file cadangan database sebelum melakukan perubahan besar.</li>
                    <li>Gunakan menu update aplikasi untuk pembaruan bertahap jika fitur itu sudah aktif.</li>
                </ul>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="<?= installer_e($loginUrl) ?>" class="btn btn-primary px-4">Buka Halaman Login</a>
            </div>
            <div class="small muted">Jika Anda memang perlu mengulang instalasi, hapus file <code>storage/installed.lock</code> dan <code>app/config/generated.php</code> secara manual, lalu kosongkan database sebelum menjalankan installer kembali.</div>
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
$passedChecks = 0;
foreach ($checks as $check) {
    if (!empty($check['ok'])) {
        $passedChecks++;
    }
}
$totalChecks = count($checks);
$allChecksPassed = $passedChecks === $totalChecks;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installer Aplikasi BUMDes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --bg:#f3f7ff;
            --bg-accent:#e7efff;
            --surface:#ffffff;
            --surface-soft:#f8fbff;
            --border:#d9e3f4;
            --border-strong:#c5d3ea;
            --text:#10213a;
            --muted:#5c6f91;
            --heading:#0f1c33;
            --primary:#2563eb;
            --primary-dark:#1d4ed8;
            --primary-soft:#eff6ff;
            --success:#0f9f6e;
            --danger:#dc2626;
            --warning:#d97706;
            --shadow:0 28px 70px rgba(15, 23, 42, .10);
            --shadow-soft:0 18px 40px rgba(37, 99, 235, .08);
        }
        *{box-sizing:border-box}
        body{min-height:100vh;background:radial-gradient(circle at top left,var(--bg-accent),transparent 28%),radial-gradient(circle at top right,#eef4ff,transparent 26%),linear-gradient(180deg,#f9fbff 0%,var(--bg) 100%);color:var(--text)}
        .install-shell{padding:34px 0 64px}
        .glass-card{background:rgba(255,255,255,.92);border:1px solid rgba(217,227,244,.92);border-radius:28px;box-shadow:var(--shadow);backdrop-filter:blur(12px)}
        .hero-card{overflow:hidden;position:relative}
        .hero-card:before{content:"";position:absolute;inset:0 auto auto 0;width:260px;height:260px;background:radial-gradient(circle,rgba(37,99,235,.14),transparent 68%);transform:translate(-32%, -42%)}
        .hero-card:after{content:"";position:absolute;inset:auto 0 -80px auto;width:240px;height:240px;background:radial-gradient(circle,rgba(59,130,246,.12),transparent 72%)}
        .hero-badge{display:inline-flex;align-items:center;gap:.55rem;padding:.56rem .95rem;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-weight:700;font-size:.82rem;letter-spacing:.02em}
        .hero-mark{width:58px;height:58px;border-radius:20px;display:grid;place-items:center;background:linear-gradient(145deg,#dbeafe,#eff6ff);color:var(--primary);box-shadow:inset 0 1px 0 rgba(255,255,255,.9),0 10px 24px rgba(37,99,235,.12)}
        .hero-title{color:var(--heading);font-weight:800;letter-spacing:-.02em}
        .hero-desc{max-width:720px;color:var(--muted);font-size:1.02rem;line-height:1.65}
        .hero-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:24px}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:16px 18px;box-shadow:var(--shadow-soft)}
        .stat-label{display:block;font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
        .stat-value{font-weight:800;color:var(--heading);font-size:1.2rem}
        .section-card{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:22px;box-shadow:0 18px 40px rgba(148,163,184,.08)}
        .section-title{display:flex;align-items:center;gap:.85rem;font-size:1.06rem;font-weight:800;color:var(--heading);margin-bottom:1rem}
        .section-icon{width:40px;height:40px;border-radius:14px;display:grid;place-items:center;background:var(--primary-soft);color:var(--primary);flex:0 0 auto}
        .section-subtitle{color:var(--muted);font-size:.93rem;line-height:1.6;margin-bottom:1rem}
        .status-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-radius:18px;background:linear-gradient(135deg,#f8fbff,#eef4ff);border:1px solid var(--border);margin-bottom:1rem}
        .status-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .8rem;border-radius:999px;font-size:.82rem;font-weight:700}
        .status-pill.ok{background:#ecfdf3;color:#0f9f6e}
        .status-pill.warn{background:#fff7ed;color:var(--warning)}
        .check-list{display:grid;gap:12px}
        .check-item{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px 18px;border:1px solid var(--border);border-radius:18px;background:var(--surface-soft)}
        .check-item strong{display:block;color:var(--heading);margin-bottom:4px}
        .check-meta{font-size:.88rem;color:var(--muted);line-height:1.5}
        .check-badge{display:inline-flex;align-items:center;justify-content:center;min-width:84px;padding:.55rem .8rem;border-radius:999px;font-size:.82rem;font-weight:800;letter-spacing:.03em}
        .check-badge.ok{background:#ecfdf3;color:var(--success)}
        .check-badge.bad{background:#fef2f2;color:var(--danger)}
        .callout{display:flex;gap:.8rem;align-items:flex-start;background:#fffdf4;border:1px solid #fde9b2;border-radius:18px;padding:15px 16px;color:#7c5b13;font-size:.92rem;line-height:1.6}
        .callout svg{flex:0 0 auto;margin-top:2px}
        .form-panel{padding:24px}
        .form-grid{display:grid;gap:20px}
        .field-group{background:var(--surface-soft);border:1px solid var(--border);border-radius:20px;padding:20px}
        .field-group h3{font-size:1rem;font-weight:800;color:var(--heading);margin-bottom:6px}
        .field-group p{font-size:.9rem;color:var(--muted);margin-bottom:16px}
        .form-label{font-weight:700;color:#243655;margin-bottom:.55rem}
        .form-control,.form-select{border-radius:14px;border:1px solid var(--border-strong);background:#fff;color:var(--heading);padding:.78rem .95rem;min-height:50px}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 .22rem rgba(37,99,235,.12)}
        .form-text{color:var(--muted);font-size:.84rem}
        .required{color:var(--danger);font-weight:700}
        .alert{border:0;border-radius:18px;padding:16px 18px;box-shadow:0 12px 26px rgba(15,23,42,.08)}
        .alert-success{background:#ecfdf3;color:#0f5132}
        .alert-danger{background:#fef2f2;color:#991b1b}
        .btn{border-radius:14px;font-weight:700;padding:.78rem 1.2rem}
        .btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;box-shadow:0 14px 30px rgba(37,99,235,.24)}
        .btn-primary:hover{background:linear-gradient(135deg,var(--primary-dark),#1e40af)}
        .btn-outline-secondary{border-color:var(--border-strong);color:#28406a;background:#fff}
        .btn-outline-secondary:hover{background:#f8fbff;border-color:var(--primary);color:var(--primary)}
        .page-note{color:var(--muted);font-size:.9rem;line-height:1.7}
        code{color:var(--primary-dark);background:#eef4ff;padding:.14rem .38rem;border-radius:.45rem}
        @media (min-width: 992px){
            .sticky-panel{position:sticky;top:22px}
        }
        @media (max-width: 991.98px){
            .install-shell{padding-top:22px}
            .glass-card,.section-card,.field-group{border-radius:22px}
            .hero-title{font-size:1.75rem}
        }
    </style>
</head>
<body>
<div class="container install-shell">
    <div class="glass-card hero-card mb-4 border-0">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-start gap-3 mb-3">
                <div class="hero-mark">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 0a1 1 0 0 1 1 1v1.07a7.001 7.001 0 0 1 5.93 5.93H16a1 1 0 1 1 0 2h-1.07A7.001 7.001 0 0 1 9 14.93V16a1 1 0 1 1-2 0v-1.07A7.001 7.001 0 0 1 1.07 10H0a1 1 0 1 1 0-2h1.07A7.001 7.001 0 0 1 7 2.07V1a1 1 0 0 1 1-1Zm0 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                </div>
                <div>
                    <span class="hero-badge mb-3">Installer resmi aplikasi BUMDes</span>
                    <h1 class="hero-title h2 mb-2">Instalasi lebih rapi, terang, dan siap dipakai</h1>
                    <p class="hero-desc mb-0">Gunakan halaman ini untuk memeriksa kebutuhan server, menghubungkan database, dan membuat akun admin pertama. Tampilan installer diperbarui agar lebih profesional, mudah dibaca, dan lebih nyaman digunakan di hosting maupun localhost.</p>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-label">Status pengecekan</span>
                    <div class="stat-value"><?= installer_e((string) $passedChecks) ?>/<?= installer_e((string) $totalChecks) ?> siap</div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">URL aplikasi</span>
                    <div class="stat-value text-break"><?= installer_e($appUrlDefault) ?></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Mode instalasi</span>
                    <div class="stat-value">Browser tanpa CLI</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success !== []): ?>
        <div class="alert alert-success mb-4">
            <div class="fw-semibold mb-2">Instalasi berhasil diproses</div>
            <?php foreach ($success as $message): ?>
                <div><?= installer_e($message) ?></div>
            <?php endforeach; ?>
            <hr>
            <a href="<?= installer_e($loginUrl) ?>" class="btn btn-success btn-sm px-3">Buka Halaman Login</a>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger mb-4">
            <div class="fw-semibold mb-2">Installer belum bisa dilanjutkan</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $message): ?>
                    <li><?= installer_e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-xl-4">
            <div class="sticky-panel">
                <div class="section-card mb-4">
                    <div class="section-title">
                        <span class="section-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 0c.69 0 1.25.56 1.25 1.25v.518a6.502 6.502 0 0 1 5.232 5.232H15c.69 0 1.25.56 1.25 1.25S15.69 9.5 15 9.5h-.518a6.502 6.502 0 0 1-5.232 5.232v.518c0 .69-.56 1.25-1.25 1.25s-1.25-.56-1.25-1.25v-.518A6.502 6.502 0 0 1 1.518 9.5H1c-.69 0-1.25-.56-1.25-1.25S.31 7 1 7h.518A6.502 6.502 0 0 1 6.75 1.768V1.25C6.75.56 7.31 0 8 0Zm0 3a5 5 0 1 0 0 10A5 5 0 0 0 8 3Z"/></svg>
                        </span>
                        <span>1. Pengecekan sistem</span>
                    </div>
                    <div class="section-subtitle">Pastikan semua kebutuhan dasar server sudah siap sebelum database diimpor dan akun admin dibuat.</div>
                    <div class="status-summary">
                        <div>
                            <div class="fw-semibold">Kesiapan server</div>
                            <div class="small text-muted"><?= installer_e((string) $passedChecks) ?> dari <?= installer_e((string) $totalChecks) ?> pemeriksaan lolos</div>
                        </div>
                        <span class="status-pill <?= $allChecksPassed ? 'ok' : 'warn' ?>">
                            <?= $allChecksPassed ? 'Siap instalasi' : 'Perlu diperiksa' ?>
                        </span>
                    </div>
                    <div class="check-list mb-3">
                        <?php foreach ($checks as $check): ?>
                            <div class="check-item">
                                <div>
                                    <strong><?= installer_e($check['label']) ?></strong>
                                    <div class="check-meta">Saat ini: <?= installer_e((string) $check['current']) ?> · Wajib: <?= installer_e((string) $check['required']) ?></div>
                                </div>
                                <span class="check-badge <?= $check['ok'] ? 'ok' : 'bad' ?>">
                                    <?= $check['ok'] ? 'OK' : 'GAGAL' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="callout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M7.001 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm.1-6.995a.905.905 0 1 1 1.8 0l-.35 4.2a.55.55 0 0 1-1.1 0l-.35-4.2ZM8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16Z"/></svg>
                        <div>Kalau ada status gagal, perbaiki dulu agar proses instalasi tidak berhenti di tengah jalan atau menyebabkan error setelah login.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="section-card form-panel">
                <div class="section-title mb-2">
                    <span class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 13.5 1h-11ZM2 2.5a.5.5 0 0 1 .5-.5H6v12H2.5a.5.5 0 0 1-.5-.5v-11Zm5 11.5V2h6.5a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H7Z"/></svg>
                    </span>
                    <span>2. Konfigurasi instalasi</span>
                </div>
                <div class="section-subtitle">Isi informasi database dengan benar, lalu buat akun admin pertama untuk masuk ke aplikasi setelah instalasi selesai.</div>
                <form method="post" action="install.php" novalidate>
                    <input type="hidden" name="_token" value="<?= installer_e($csrfToken) ?>">
                    <div class="form-grid">
                        <section class="field-group">
                            <h3>Koneksi database</h3>
                            <p>Database harus sudah dibuat lebih dulu dari panel hosting, phpMyAdmin, atau localhost MySQL Anda.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="db_host">Host Database <span class="required">*</span></label>
                                    <input class="form-control" id="db_host" name="db_host" value="<?= installer_e(installer_old('db_host', '127.0.0.1')) ?>" maxlength="150" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_port">Port Database <span class="required">*</span></label>
                                    <input class="form-control" id="db_port" name="db_port" value="<?= installer_e(installer_old('db_port', '3306')) ?>" maxlength="5" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_name">Nama Database <span class="required">*</span></label>
                                    <input class="form-control" id="db_name" name="db_name" value="<?= installer_e(installer_old('db_name')) ?>" maxlength="150" required>
                                    <div class="form-text">Contoh: <code>bumdes_db</code></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_user">User Database <span class="required">*</span></label>
                                    <input class="form-control" id="db_user" name="db_user" value="<?= installer_e(installer_old('db_user')) ?>" maxlength="150" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="db_pass">Password Database</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?= installer_e(installer_old('db_pass')) ?>" autocomplete="new-password">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="app_url">URL Aplikasi <span class="required">*</span></label>
                                    <input class="form-control" id="app_url" name="app_url" value="<?= installer_e(installer_old('app_url', $appUrlDefault)) ?>" required>
                                    <div class="form-text">Contoh URL aktif: <code><?= installer_e($appUrlDefault) ?></code></div>
                                </div>
                            </div>
                        </section>

                        <section class="field-group">
                            <h3>Akun admin pertama</h3>
                            <p>Akun ini dipakai untuk login pertama kali. Setelah berhasil masuk, Anda bisa menambah pengguna lain dari dalam aplikasi.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_name">Nama Admin <span class="required">*</span></label>
                                    <input class="form-control" id="admin_name" name="admin_name" value="<?= installer_e(installer_old('admin_name', 'Administrator BUMDes')) ?>" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_username">Username Admin <span class="required">*</span></label>
                                    <input class="form-control" id="admin_username" name="admin_username" value="<?= installer_e(installer_old('admin_username', 'admin')) ?>" maxlength="50" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_password">Password Admin <span class="required">*</span></label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" autocomplete="new-password" required>
                                    <div class="form-text">Minimal 8 karakter.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="admin_password_confirm">Konfirmasi Password <span class="required">*</span></label>
                                    <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" autocomplete="new-password" required>
                                </div>
                            </div>
                        </section>

                        <section class="field-group">
                            <h3>Ringkasan tindakan installer</h3>
                            <p>Setelah tombol instalasi dijalankan, sistem akan menyiapkan file konfigurasi dan mengunci installer otomatis demi keamanan.</p>
                            <div class="page-note">
                                Installer akan menulis file <code>app/config/generated.php</code>, mengimpor struktur database utama, membuat akun admin pertama, lalu membuat file lock <code>storage/installed.lock</code> agar halaman instalasi tidak dipakai ulang secara tidak sengaja.
                            </div>
                        </section>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-3 mt-4">
                        <button type="submit" name="action" value="test-db" class="btn btn-outline-secondary px-4">Tes Koneksi Database</button>
                        <button type="submit" name="action" value="install" class="btn btn-primary px-4">Mulai Instalasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
