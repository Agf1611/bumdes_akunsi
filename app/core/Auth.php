<?php
declare(strict_types=1);
final class Auth {
    private const KEY = 'auth_user';
    public static function login(array $user): void {
        Session::regenerate();
        Session::put(self::KEY, [
            'id'=>(int)$user['id'], 'full_name'=>$user['full_name'], 'username'=>$user['username'],
            'role_code'=>$user['role_code'], 'role_name'=>$user['role_name'], 'logged_in_at'=>date('Y-m-d H:i:s')
        ]);
    }
    public static function logout(): void { Session::forget(self::KEY); Session::regenerate(); }
    public static function check(): bool { return is_array(Session::get(self::KEY)); }
    public static function user(): ?array { $u = Session::get(self::KEY); return is_array($u)?$u:null; }
    public static function hasRole(array|string $roles): bool { return self::check() && in_array(self::user()['role_code'], (array)$roles, true); }
}
