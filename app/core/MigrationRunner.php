<?php

declare(strict_types=1);

final class MigrationRunner
{
    public function __construct(
        private PDO $db,
        private string $migrationsPath = ROOT_PATH . '/database/migrations',
    ) {
    }

    public function ensureSchemaTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS schema_versions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(190) NOT NULL UNIQUE,
                migration_hash CHAR(64) NOT NULL DEFAULT '',
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function discoverMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = array_values(array_filter(
            glob(rtrim($this->migrationsPath, '/\\') . '/*.sql') ?: [],
            static fn (string $path): bool => is_file($path)
        ));
        sort($files);
        return $files;
    }

    public function appliedMigrationNames(): array
    {
        $this->ensureSchemaTable();
        $stmt = $this->db->query('SELECT migration_name FROM schema_versions ORDER BY migration_name ASC');
        return array_map(
            static fn (mixed $value): string => (string) $value,
            $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []
        );
    }

    public function pendingMigrations(): array
    {
        $applied = array_flip($this->appliedMigrationNames());
        return array_values(array_filter(
            $this->discoverMigrations(),
            static fn (string $file): bool => !isset($applied[basename($file)])
        ));
    }

    public function migrate(): array
    {
        $this->ensureSchemaTable();
        $applied = [];
        foreach ($this->pendingMigrations() as $file) {
            $this->applyMigrationFile($file);
            $applied[] = basename($file);
        }
        return [
            'applied' => $applied,
        ];
    }

    private function applyMigrationFile(string $file): void
    {
        $sql = (string) file_get_contents($file);
        $statements = $this->splitSqlStatements($sql);
        $ownTransaction = !$this->db->inTransaction();

        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '' || $this->isTransactionControlStatement($trimmed)) {
                    continue;
                }
                $this->db->exec($trimmed);
            }

            $stmt = $this->db->prepare(
                'INSERT INTO schema_versions (migration_name, migration_hash, executed_at)
                 VALUES (:migration_name, :migration_hash, NOW())'
            );
            $stmt->execute([
                ':migration_name' => basename($file),
                ':migration_hash' => hash_file('sha256', $file) ?: '',
            ]);

            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException('Migrasi gagal pada ' . basename($file) . ': ' . $e->getMessage(), 0, $e);
        }
    }

    private function isTransactionControlStatement(string $statement): bool
    {
        $statement = strtoupper(trim($statement));
        foreach (['START TRANSACTION', 'COMMIT', 'ROLLBACK'] as $keyword) {
            if (str_starts_with($statement, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '-' && $next === '-' && (($i + 2 < $length && ctype_space($sql[$i + 2])) || $i + 2 >= $length)) {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$inDouble && !$inBacktick && $prev !== '\\') {
                $inSingle = !$inSingle;
            } elseif ($char === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
                $inDouble = !$inDouble;
            } elseif ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            }

            if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
