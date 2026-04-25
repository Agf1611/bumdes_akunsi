<?php

declare(strict_types=1);

final class UserPreferenceStore
{
    private static ?self $instance = null;
    private array $memoryCache = [];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get(int $userId, string $key, mixed $default = null): mixed
    {
        if ($userId <= 0) {
            return $default;
        }

        $preferences = $this->all($userId);
        return $preferences[$key] ?? $default;
    }

    public function put(int $userId, string $key, mixed $value): void
    {
        if ($userId <= 0) {
            return;
        }

        $preferences = $this->all($userId);
        $preferences[$key] = $value;
        $this->save($userId, $preferences);
    }

    public function forget(int $userId, string $key): void
    {
        if ($userId <= 0) {
            return;
        }

        $preferences = $this->all($userId);
        unset($preferences[$key]);
        $this->save($userId, $preferences);
    }

    public function all(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        if (isset($this->memoryCache[$userId])) {
            return $this->memoryCache[$userId];
        }

        $preferences = $this->tableExists()
            ? $this->loadFromDatabase($userId)
            : $this->loadFromFile($userId);

        $this->memoryCache[$userId] = $preferences;
        return $preferences;
    }

    private function save(int $userId, array $preferences): void
    {
        if ($this->tableExists()) {
            $this->saveToDatabase($userId, $preferences);
        } else {
            $this->saveToFile($userId, $preferences);
        }

        $this->memoryCache[$userId] = $preferences;
    }

    private function tableExists(): bool
    {
        static $tableExists = null;
        if ($tableExists !== null) {
            return $tableExists;
        }

        try {
            $stmt = Database::getInstance(db_config())->prepare(
                'SELECT 1
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                 LIMIT 1'
            );
            $stmt->execute([':table_name' => 'user_preferences']);
            $tableExists = (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            $tableExists = false;
        }

        return $tableExists;
    }

    private function loadFromDatabase(int $userId): array
    {
        try {
            $stmt = Database::getInstance(db_config())->prepare(
                'SELECT preference_key, preference_value
                 FROM user_preferences
                 WHERE user_id = :user_id'
            );
            $stmt->execute([':user_id' => $userId]);

            $preferences = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $key = (string) ($row['preference_key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $preferences[$key] = $this->decodeValue((string) ($row['preference_value'] ?? 'null'));
            }

            if ($preferences === []) {
                $legacyPreferences = $this->loadFromFile($userId);
                if ($legacyPreferences !== []) {
                    $this->saveToDatabase($userId, $legacyPreferences);
                    return $legacyPreferences;
                }
            }

            return $preferences;
        } catch (Throwable) {
            return [];
        }
    }

    private function saveToDatabase(int $userId, array $preferences): void
    {
        $db = Database::getInstance(db_config());
        $db->beginTransaction();

        try {
            $delete = $db->prepare('DELETE FROM user_preferences WHERE user_id = :user_id');
            $delete->execute([':user_id' => $userId]);

            $insert = $db->prepare(
                'INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
                 VALUES (:user_id, :preference_key, :preference_value, NOW())'
            );

            foreach ($preferences as $key => $value) {
                $insert->execute([
                    ':user_id' => $userId,
                    ':preference_key' => (string) $key,
                    ':preference_value' => $this->encodeValue($value),
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private function loadFromFile(int $userId): array
    {
        $path = $this->filePath($userId);
        if (!is_file($path)) {
            return [];
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            return is_array($payload) ? $payload : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function saveToFile(int $userId, array $preferences): void
    {
        $dir = dirname($this->filePath($userId));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $this->filePath($userId),
            json_encode($preferences, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function filePath(int $userId): string
    {
        return ROOT_PATH . '/storage/user_preferences/user-' . $userId . '.json';
    }

    private function encodeValue(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    private function decodeValue(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }
}
