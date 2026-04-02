<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

class GuestMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            return;
        }

        redirect((string) config('auth.home_route', '/dashboard'));
    }
}
