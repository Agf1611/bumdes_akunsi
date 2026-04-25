<?php

declare(strict_types=1);

final class AuthRateLimiter
{
    public static function directory(): string
    {
        return ROOT_PATH . '/storage/security/login_attempts';
    }

    public static function ensureDirectory(): void
    {
        if (!is_dir(self::directory()) && !@mkdir(self::directory(), 0775, true) && !is_dir(self::directory())) {
            throw new RuntimeException('Folder throttle login tidak dapat dibuat.');
        }
    }

    public static function assertCanAttempt(string $username, string $ipAddress): void
    {
        if (!(bool) (auth_config('throttle')['enabled'] ?? false)) {
            return;
        }

        $state = self::readState($username, $ipAddress);
        $lockedUntil = (int) ($state['lockout_until'] ?? 0);
        if ($lockedUntil > time()) {
            $seconds = max(1, $lockedUntil - time());
            throw new RuntimeException('Login dikunci sementara. Coba lagi dalam ' . self::formatSeconds($seconds) . '.');
        }
    }

    public static function recordFailure(string $username, string $ipAddress): array
    {
        self::ensureDirectory();
        $state = self::readState($username, $ipAddress);
        $windowSeconds = max(60, (int) (auth_config('throttle')['window_seconds'] ?? 900));
        $maxAttempts = max(1, (int) (auth_config('throttle')['max_attempts'] ?? 5));
        $now = time();

        $failures = array_values(array_filter(
            array_map('intval', (array) ($state['failures'] ?? [])),
            static fn (int $timestamp): bool => $timestamp >= ($now - $windowSeconds)
        ));
        $failures[] = $now;

        $progressive = auth_config('throttle')['progressive_lockouts'] ?? [];
        $lockoutSeconds = 0;
        foreach ((array) $progressive as $attempts => $seconds) {
            if (count($failures) >= (int) $attempts) {
                $lockoutSeconds = max($lockoutSeconds, (int) $seconds);
            }
        }
        if ($lockoutSeconds <= 0 && count($failures) >= $maxAttempts) {
            $lockoutSeconds = $windowSeconds;
        }

        $state = [
            'username' => strtolower(trim($username)),
            'ip_address' => $ipAddress,
            'failures' => $failures,
            'lockout_until' => $lockoutSeconds > 0 ? ($now + $lockoutSeconds) : 0,
            'updated_at' => date('c'),
        ];
        self::writeState($username, $ipAddress, $state);

        return [
            'failed_attempts' => count($failures),
            'lockout_seconds' => $lockoutSeconds,
            'locked_until' => $state['lockout_until'],
        ];
    }

    public static function clear(string $username, string $ipAddress): void
    {
        $path = self::statePath($username, $ipAddress);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function statePath(string $username, string $ipAddress): string
    {
        $fingerprint = hash('sha256', strtolower(trim($username)) . '|' . trim($ipAddress));
        return self::directory() . '/' . $fingerprint . '.json';
    }

    private static function readState(string $username, string $ipAddress): array
    {
        $path = self::statePath($username, $ipAddress);
        if (!is_file($path)) {
            return [];
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (Throwable) {
            return [];
        }
    }

    private static function writeState(string $username, string $ipAddress, array $state): void
    {
        @file_put_contents(
            self::statePath($username, $ipAddress),
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' detik';
        }
        $minutes = (int) ceil($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' menit';
        }
        $hours = (int) ceil($minutes / 60);
        return $hours . ' jam';
    }
}
