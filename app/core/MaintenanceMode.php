<?php

declare(strict_types=1);

final class MaintenanceMode
{
    public static function lockPath(): string
    {
        return ROOT_PATH . '/storage/maintenance.lock';
    }

    public static function isActive(): bool
    {
        return is_file(self::lockPath());
    }

    public static function state(): array
    {
        $path = self::lockPath();
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

    public static function enable(array $context = []): void
    {
        $payload = [
            'enabled_at' => date('c'),
            'reason' => (string) ($context['reason'] ?? 'maintenance'),
            'actor' => (string) ($context['actor'] ?? ''),
        ];
        @file_put_contents(
            self::lockPath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    public static function disable(): void
    {
        if (is_file(self::lockPath())) {
            @unlink(self::lockPath());
        }
    }

    public static function canBypass(string $path): bool
    {
        $path = '/' . trim($path, '/');
        if ($path === '/install' || $path === '/login' || $path === '/logout') {
            return true;
        }

        foreach (['/updates', '/backups'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
