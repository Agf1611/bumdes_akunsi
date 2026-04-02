<?php
declare(strict_types=1);
final class Database {
    private static ?PDO $pdo = null;
    public static function getInstance(array $config): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;
        $dsn = sprintf('%s:host=%s;port=%d;dbname=%s;charset=%s', $config['driver'], $config['host'], (int)$config['port'], $config['database'], $config['charset']);
        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        return self::$pdo;
    }
    public static function isConnected(array $config): bool {
        try { self::getInstance($config)->query('SELECT 1'); return true; } catch (Throwable) { return false; }
    }
}
