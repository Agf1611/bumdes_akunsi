<?php
declare(strict_types=1);
final class Auth {
    public const KEY = 'auth_user';
    private const LAST_ACTIVITY_KEY = '_auth_last_activity';
    public static function login(array $user): void {
        Session::regenerate();
        Session::put(self::KEY, [
            'id'=>(int)$user['id'], 'full_name'=>$user['full_name'], 'username'=>$user['username'],
            'role_code'=>$user['role_code'], 'role_name'=>$user['role_name'], 'logged_in_at'=>date('Y-m-d H:i:s')
        ]);
        Session::touch(self::LAST_ACTIVITY_KEY);
    }
    public static function logout(): void {
        Session::forget(self::KEY);
        Session::forget(self::LAST_ACTIVITY_KEY);
        Session::regenerate();
    }
    public static function check(): bool {
        $u = Session::get(self::KEY);
        if (!is_array($u)) {
            return false;
        }
        if (self::hasTimedOut()) {
            self::expireByInactivity();
            return false;
        }
        Session::touch(self::LAST_ACTIVITY_KEY);
        return true;
    }
    public static function user(): ?array {
        $u = Session::get(self::KEY);
        return is_array($u)?$u:null;
    }
    public static function hasRole(array|string $roles): bool { return self::check() && in_array(self::user()['role_code'], (array)$roles, true); }
    private static function hasTimedOut(): bool
    {
        $timeout = (int) (app_config('session_idle_timeout') ?? 0);
        if ($timeout <= 0) {
            return false;
        }
        $lastActivity = (int) Session::get(self::LAST_ACTIVITY_KEY, 0);
        return $lastActivity > 0 && (time() - $lastActivity) > $timeout;
    }
    private static function expireByInactivity(): void
    {
        Session::forget(self::KEY);
        Session::forget(self::LAST_ACTIVITY_KEY);
        flash('error', 'Sesi login berakhir karena tidak ada aktivitas terlalu lama. Silakan login kembali.');
        Session::regenerate();
    }
}
