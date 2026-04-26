<?php
declare(strict_types=1);
return [
    'redirect_after_login' => '/dashboard',
    'redirect_after_logout' => '/login',
    'roles' => ['admin', 'bendahara', 'pimpinan'],
    'throttle' => [
        'enabled' => true,
        'window_seconds' => 900,
        'max_attempts' => 5,
        'progressive_lockouts' => [
            5 => 900,
            8 => 1800,
            12 => 3600,
        ],
    ],
    'mfa' => [
        'enabled' => false,
        'issuer' => 'BUMDes',
        'window' => 1,
    ],
    'password_reset' => [
        'temporary_length' => 12,
    ],
];
