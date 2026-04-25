<?php

declare(strict_types=1);


if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        unset($encoding);
        return strlen($string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        unset($encoding);
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        unset($encoding);
        return strtolower($string);
    }
}

function app_config(?string $key = null): mixed
{
    global $config;
    return $key === null ? ($config['app'] ?? []) : ($config['app'][$key] ?? null);
}

function db_config(?string $key = null): mixed
{
    global $config;
    return $key === null ? ($config['database'] ?? []) : ($config['database'][$key] ?? null);
}

function auth_config(?string $key = null): mixed
{
    global $config;
    return $key === null ? ($config['auth'] ?? []) : ($config['auth'][$key] ?? null);
}

function app_url_base(): string
{
    $configured = trim((string) app_config('url'));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $baseDir = rtrim(dirname($scriptName), '/');
    if ($baseDir === '/public') {
        $baseDir = '';
    }
    if ($baseDir === '/' || $baseDir === '.') {
        $baseDir = '';
    }

    return $scheme . '://' . $host . $baseDir;
}

function base_url(string $path = ''): string
{
    $base = app_url_base();
    $normalized = '/' . ltrim($path, '/');
    return $normalized === '/' ? $base : $base . $normalized;
}

function is_serving_from_public_document_root(): bool
{
    $publicDir = realpath(ROOT_PATH . '/public');
    $publicIndex = realpath(ROOT_PATH . '/public/index.php');
    $publicInstall = realpath(ROOT_PATH . '/public/install.php');
    $scriptFilename = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if ($scriptFilename !== false && ($scriptFilename === $publicIndex || $scriptFilename === $publicInstall)) {
        return true;
    }

    return $publicDir !== false && $documentRoot !== false && $documentRoot === $publicDir;
}

function public_url(string $path = ''): string
{
    $rootPublic = is_file(ROOT_PATH . '/public/index.php');
    $uri = '/' . ltrim($path, '/');
    $basePath = parse_url(base_url(), PHP_URL_PATH) ?? '';

    if ($rootPublic && !is_serving_from_public_document_root() && !str_contains($basePath, '/public')) {
        return base_url('/public' . ($uri === '/' ? '' : $uri));
    }

    return base_url($uri);
}

function asset_url(string $path): string
{
    return public_url('/assets/' . ltrim($path, '/'));
}

function upload_url(?string $relativePath): string
{
    $relativePath = trim((string) $relativePath);
    if ($relativePath === '') {
        return '';
    }

    return public_url('/' . ltrim($relativePath, '/'));
}

function public_path(?string $relativePath): string
{
    $relativePath = trim((string) $relativePath);
    if ($relativePath === '') {
        return '';
    }

    return ROOT_PATH . '/public/' . ltrim($relativePath, '/');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function e(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, string $message): void
{
    Session::put('_flash_' . $key, $message);
}

function get_flash(string $key): ?string
{
    $sessionKey = '_flash_' . $key;
    $value = Session::get($sessionKey);
    Session::forget($sessionKey);
    return is_string($value) ? $value : null;
}

function old(string $key, string $default = ''): string
{
    $old = Session::get('_old_input', []);
    return isset($old[$key]) ? (string) $old[$key] : $default;
}

function old_input(string $key, mixed $default = null): mixed
{
    $old = Session::get('_old_input', []);
    return $old[$key] ?? $default;
}

function with_old_input(array $input): void
{
    Session::put('_old_input', $input);
}

function clear_old_input(): void
{
    Session::forget('_old_input');
}

function csrf_token(): string
{
    $token = Session::get('_csrf_token');
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        Session::put('_csrf_token', $token);
    }

    return $token;
}

function verify_csrf(?string $token): bool
{
    $stored = Session::get('_csrf_token');
    return is_string($stored) && is_string($token) && hash_equals($stored, $token);
}

function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function get_query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function render_view(string $view, array $data = [], string $layout = 'main'): void
{
    $viewFile = APP_PATH . '/modules/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('View tidak ditemukan: ' . $view);
    }

    extract($data, EXTR_SKIP);
    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();

    $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';
    if (!is_file($layoutFile)) {
        throw new RuntimeException('Layout tidak ditemukan: ' . $layout);
    }

    require $layoutFile;
}

function render_error_page(int $statusCode, string $message, ?Throwable $exception = null): void
{
    $debug = (bool) app_config('debug');
    $errorView = APP_PATH . '/views/errors/' . $statusCode . '.php';

    try {
        if (!is_file($errorView)) {
            throw new RuntimeException('View error tidak ditemukan.');
        }
        require $errorView;
    } catch (Throwable $viewError) {
        http_response_code($statusCode);
        echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Error</title>';
        echo '<style>body{font-family:Arial,sans-serif;background:#0f172a;color:#e5e7eb;padding:32px} .box{max-width:720px;margin:40px auto;background:#111827;padding:24px;border-radius:12px;border:1px solid #334155} pre{white-space:pre-wrap;background:#0b1220;padding:12px;border-radius:8px;overflow:auto}</style>';
        echo '</head><body><div class="box">';
        echo '<h1>' . e((string) $statusCode) . '</h1>';
        echo '<p>' . e($message) . '</p>';
        if ($debug) {
            if ($exception) {
                echo '<pre>' . e($exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine()) . '</pre>';
            }
            echo '<pre>' . e($viewError->getMessage() . "\n" . $viewError->getFile() . ':' . $viewError->getLine()) . '</pre>';
        }
        echo '</div></body></html>';
    }
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function log_error(Throwable $e): void
{
    $dir = ROOT_PATH . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $file = $dir . '/app-' . date('Y-m-d') . '.log';
    $line = sprintf(
        "[%s] %s in %s:%d\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($file, $line, FILE_APPEND);
}

function format_id_date(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }

    try {
        return (new DateTimeImmutable($date))->format('d-m-Y');
    } catch (Throwable) {
        return $date;
    }
}

function app_today_city(): string
{
    return format_id_date(date('Y-m-d'));
}


if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd-m-Y'): string
    {
        $date = trim((string) $date);
        if ($date == '') {
            return '-';
        }

        try {
            return (new DateTimeImmutable($date))->format($format);
        } catch (Throwable) {
            return $date;
        }
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|int|string|null $amount, string $prefix = '', int $decimals = 2): string
    {
        $number = (float) ($amount ?? 0);
        $negative = $number < 0;
        $formatted = number_format(abs($number), $decimals, ',', '.');
        $value = $prefix !== '' ? trim($prefix) . ' ' . $formatted : $formatted;
        return $negative ? '-' . $value : $value;
    }
}


function format_id_long_date(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    try {
        $dt = new DateTimeImmutable($date);
        $month = $months[(int) $dt->format('n')] ?? $dt->format('F');
        return $dt->format('d') . ' ' . $month . ' ' . $dt->format('Y');
    } catch (Throwable) {
        return $date;
    }
}

function format_id_month_year(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }

    try {
        $dt = new DateTimeImmutable($date);
        return format_id_long_date($dt->format('Y-m-01'));
    } catch (Throwable) {
        return $date;
    }
}

function user_can_manage(array|string $roles = ['admin']): bool
{
    return Auth::hasRole($roles);
}

function is_read_only_user(): bool
{
    return Auth::hasRole('pimpinan');
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    $units = ['KB', 'MB', 'GB', 'TB'];
    $value = $bytes / 1024;
    foreach ($units as $index => $unit) {
        if ($value < 1024 || $index === array_key_last($units)) {
            return number_format($value, $value >= 100 ? 0 : 2, ',', '.') . ' ' . $unit;
        }
        $value /= 1024;
    }

    return number_format($value, 2, ',', '.') . ' TB';
}
