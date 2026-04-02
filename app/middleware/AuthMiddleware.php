<?php
declare(strict_types=1);
final class AuthMiddleware {
    public function handle(): void {
        if (!Auth::check()) { flash('error','Silakan login terlebih dahulu untuk mengakses halaman tersebut.'); redirect('/login'); }
    }
}
