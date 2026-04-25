<?php
declare(strict_types=1);
final class Session {
    public static function start(array $app): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name($app['session_name']);
        session_set_cookie_params([
            'lifetime' => $app['session_lifetime'],
            'path' => '/', 'domain' => '', 'secure' => self::isHttps(), 'httponly' => true, 'samesite' => 'Lax'
        ]);
        session_start();
        if (!isset($_SESSION['_regenerated'])) { session_regenerate_id(true); $_SESSION['_regenerated']=time(); }
    }
    public static function get(string $key, mixed $default=null): mixed { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function set(string $key, mixed $value): void { self::put($key, $value); }
    public static function pull(string $key, mixed $default=null): mixed {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function regenerate(): void { if (session_status()===PHP_SESSION_ACTIVE) session_regenerate_id(true); }
    public static function touch(string $key): void { $_SESSION[$key] = time(); }
    public static function destroy(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
    private static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    }
}
