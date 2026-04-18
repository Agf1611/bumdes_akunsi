<?php

declare(strict_types=1);

final class GuestMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            return;
        }

        redirect((string) auth_config('redirect_after_login'));
    }
}
