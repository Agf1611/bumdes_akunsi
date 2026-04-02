<?php
declare(strict_types=1);

function app_config(?string $key=null): mixed { global $config; return $key===null ? $config['app'] : ($config['app'][$key] ?? null); }
function db_config(?string $key=null): mixed { global $config; return $key===null ? $config['database'] : ($config['database'][$key] ?? null); }
function auth_config(?string $key=null): mixed { global $config; return $key===null ? $config['auth'] : ($config['auth'][$key] ?? null); }

function app_base_url(): string
{
    $configured = trim((string) app_config('url'));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = ($https !== '' && strtolower((string) $https) !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');

    return $scheme . '://' . $host . ($baseDir !== '' ? $baseDir : '');
}

function public_prefix(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return str_contains($scriptName, '/public/') ? '' : '/public';
}

function base_url(string $path=''): string
{
    $base = rtrim(app_base_url(), '/');
    $path = '/' . ltrim($path, '/');
    return $path === '/' ? $base : $base . $path;
}

function asset_url(string $path): string { return base_url(public_prefix() . '/assets/' . ltrim($path,'/')); }
function storage_url(string $path): string { return base_url(public_prefix() . '/' . ltrim($path,'/')); }
function redirect(string $path): never { header('Location: ' . base_url($path)); exit; }
function e(string|null $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function flash(string $key, string $msg): void { Session::put('_flash_'.$key,$msg); }
function get_flash(string $key): ?string { $k='_flash_'.$key; $v=Session::get($k); Session::forget($k); return is_string($v)?$v:null; }
function old(string $key, string $default=''): string { $old=Session::get('_old_input',[]); return isset($old[$key])?(string)$old[$key]:$default; }
function old_input(string $key, mixed $default=null): mixed { $old=Session::get('_old_input',[]); return $old[$key] ?? $default; }
function with_old_input(array $input): void { Session::put('_old_input',$input); }
function clear_old_input(): void { Session::forget('_old_input'); }
function csrf_token(): string { $t=Session::get('_csrf_token'); if(!is_string($t)||$t===''){ $t=bin2hex(random_bytes(32)); Session::put('_csrf_token',$t);} return $t; }
function verify_csrf(?string $token): bool { $s=Session::get('_csrf_token'); return is_string($s)&&is_string($token)&&hash_equals($s,$token); }
function post(string $key, mixed $default=null): mixed { return $_POST[$key] ?? $default; }
function get_query(string $key, mixed $default=null): mixed { return $_GET[$key] ?? $default; }
function render_view(string $view, array $data=[], string $layout='main'): void {
    $viewFile = APP_PATH . '/modules/' . $view . '.php'; if(!is_file($viewFile)) throw new RuntimeException('View tidak ditemukan: '.$view);
    extract($data, EXTR_SKIP); ob_start(); require $viewFile; $content = ob_get_clean();
    $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php'; if(!is_file($layoutFile)) throw new RuntimeException('Layout tidak ditemukan: '.$layout);
    require $layoutFile;
}
function render_error_page(int $statusCode, string $message, ?Throwable $exception=null): void {
    $debug=(bool)app_config('debug'); $errorView=APP_PATH . '/views/errors/' . $statusCode . '.php';
    if(!is_file($errorView)) { echo '<h1>'.e((string)$statusCode).'</h1><p>'.e($message).'</p>'; return; }
    require $errorView;
}
function log_error(Throwable $e): void {
    $dir = ROOT_PATH . '/storage/logs'; if(!is_dir($dir)) @mkdir($dir,0775,true);
    @file_put_contents($dir.'/app-'.date('Y-m-d').'.log', sprintf("[%s] %s in %s:%d
", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine()), FILE_APPEND);
}
