<?php
declare(strict_types=1);
final class RoleMiddleware {
    public function handle(array $roles=[]): void {
        if (!Auth::check()) { flash('error','Silakan login terlebih dahulu untuk mengakses halaman tersebut.'); redirect('/login'); }
        if (!Auth::hasRole($roles)) { http_response_code(403); render_error_page(403,'Anda tidak memiliki izin untuk membuka halaman ini.'); exit; }
    }
}
