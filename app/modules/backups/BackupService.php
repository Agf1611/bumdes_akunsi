<?php

declare(strict_types=1);

final class BackupService
{
    private const MAX_UPLOAD_SIZE = 31457280;

    public function __construct(private PDO $db)
    {
    }

    public static function directory(): string
    {
        return ROOT_PATH . '/storage/backups';
    }

    public static function ensureDirectory(): void
    {
        foreach ([self::directory(), self::stagingDirectory()] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    public static function stagingDirectory(): string
    {
        return self::directory() . '/restore_staging';
    }

    public static function safeFileName(string $fileName): ?string
    {
        $fileName = basename(trim($fileName));
        if ($fileName === '') {
            return null;
        }

        if (!preg_match('/\A[a-zA-Z0-9._-]+\.sql\z/', $fileName)) {
            return null;
        }

        return $fileName;
    }

    public static function filePath(string $fileName): ?string
    {
        $safe = self::safeFileName($fileName);
        if ($safe === null) {
            return null;
        }

        $path = self::directory() . '/' . $safe;
        return is_file($path) ? $path : null;
    }

    public static function listFiles(): array
    {
        self::ensureDirectory();
        $files = [];
        foreach (glob(self::directory() . '/*.sql') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $files[] = [
                'name' => basename($path),
                'path' => $path,
                'size' => (int) (@filesize($path) ?: 0),
                'modified_at' => date('Y-m-d H:i:s', (int) (@filemtime($path) ?: time())),
                'sha1' => sha1_file($path) ?: '',
            ];
        }

        usort($files, static fn (array $a, array $b): int => strcmp((string) $b['modified_at'], (string) $a['modified_at']));
        return $files;
    }

    public static function stageUploadedRestoreFile(array $file): array
    {
        self::ensureDirectory();
        self::validateUpload($file);

        $originalName = basename((string) ($file['name'] ?? 'restore.sql'));
        $stagedName = 'restore-review-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.sql';
        $stagedPath = self::stagingDirectory() . '/' . $stagedName;

        if (!@copy((string) $file['tmp_name'], $stagedPath)) {
            throw new RuntimeException('File upload tidak dapat dipindahkan ke area staging restore.');
        }

        return [
            'staged_name' => $stagedName,
            'path' => $stagedPath,
            'original_name' => $originalName,
            'size' => (int) (@filesize($stagedPath) ?: 0),
            'sha1' => sha1_file($stagedPath) ?: '',
        ];
    }

    public static function stagedFilePath(string $fileName): ?string
    {
        $safe = basename(trim($fileName));
        if ($safe === '' || !preg_match('/\A[a-zA-Z0-9._-]+\.sql\z/', $safe)) {
            return null;
        }

        $path = self::stagingDirectory() . '/' . $safe;
        return is_file($path) ? $path : null;
    }

    public static function deleteStagedFile(?string $fileName): void
    {
        $path = $fileName !== null ? self::stagedFilePath($fileName) : null;
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }


    public function inspectSqlFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('File backup tidak ditemukan atau tidak dapat dibaca.');
        }

        $sql = (string) file_get_contents($path);
        if (trim($sql) === '') {
            throw new RuntimeException('File backup kosong.');
        }

        $statements = $this->splitSqlStatements($sql);
        if ($statements === []) {
            throw new RuntimeException('Isi file backup tidak berisi statement SQL yang dapat dianalisis.');
        }

        $summary = [
            'file_name' => basename($path),
            'size' => (int) (@filesize($path) ?: 0),
            'sha1' => sha1_file($path) ?: '',
            'statement_count' => count($statements),
            'transaction_control_count' => 0,
            'insert_count' => 0,
            'drop_count' => 0,
            'create_count' => 0,
            'alter_count' => 0,
            'other_count' => 0,
            'table_names' => [],
            'contains_ddl' => false,
        ];

        $tables = [];
        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }

            if ($this->isTransactionControlStatement($trimmed)) {
                $summary['transaction_control_count']++;
                continue;
            }

            $upper = strtoupper($trimmed);
            if (str_starts_with($upper, 'INSERT ')) {
                $summary['insert_count']++;
                $table = $this->extractTableName($trimmed, '/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i');
                if ($table !== null) {
                    $tables[$table] = true;
                }
                continue;
            }
            if (str_starts_with($upper, 'DROP TABLE')) {
                $summary['drop_count']++;
                $summary['contains_ddl'] = true;
                $table = $this->extractTableName($trimmed, '/DROP\s+TABLE(?:\s+IF\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i');
                if ($table !== null) {
                    $tables[$table] = true;
                }
                continue;
            }
            if (str_starts_with($upper, 'CREATE TABLE')) {
                $summary['create_count']++;
                $summary['contains_ddl'] = true;
                $table = $this->extractTableName($trimmed, '/CREATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i');
                if ($table !== null) {
                    $tables[$table] = true;
                }
                continue;
            }
            if (str_starts_with($upper, 'ALTER TABLE')) {
                $summary['alter_count']++;
                $summary['contains_ddl'] = true;
                $table = $this->extractTableName($trimmed, '/ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i');
                if ($table !== null) {
                    $tables[$table] = true;
                }
                continue;
            }

            $summary['other_count']++;
        }

        $summary['table_names'] = array_values(array_keys($tables));
        $summary['table_count'] = count($summary['table_names']);

        return $summary;
    }

    public function restoreFromFile(string $path): array
    {
        $analysis = $this->inspectSqlFile($path);
        $sql = (string) file_get_contents($path);
        $statements = $this->splitSqlStatements($sql);

        $executed = 0;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }
            $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') {
                    continue;
                }
                if ($this->isTransactionControlStatement($trimmed)) {
                    continue;
                }
                $this->db->exec($trimmed);
                $executed++;
            }
            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
            $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            try {
                $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
            }
            throw new RuntimeException('Restore database gagal pada statement ke-' . ($executed + 1) . ': ' . $e->getMessage(), 0, $e);
        }

        return $analysis + [
            'executed_statements' => $executed,
        ];
    }

    public static function validateUpload(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file restore gagal. Pastikan file SQL dipilih dan ukuran file tidak melebihi batas server.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('File restore kosong.');
        }
        if ($size > self::MAX_UPLOAD_SIZE) {
            throw new RuntimeException('Ukuran file restore melebihi batas 30 MB.');
        }

        $name = (string) ($file['name'] ?? '');
        if (!preg_match('/\.sql\z/i', $name)) {
            throw new RuntimeException('File restore harus berformat .sql');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('File upload restore tidak valid.');
        }
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
                    $buffer .= $char;
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
                $buffer .= $char;
                continue;
            }
            if ($char === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
                $inDouble = !$inDouble;
                $buffer .= $char;
                continue;
            }
            if ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
                $buffer .= $char;
                continue;
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

    private function isTransactionControlStatement(string $statement): bool
    {
        $statement = strtoupper(trim($statement));
        foreach ([
            'START TRANSACTION',
            'BEGIN',
            'COMMIT',
            'ROLLBACK',
            'SET AUTOCOMMIT',
        ] as $prefix) {
            if (str_starts_with($statement, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractTableName(string $statement, string $pattern): ?string
    {
        if (preg_match($pattern, $statement, $matches) === 1) {
            return (string) ($matches[1] ?? '');
        }

        return null;
    }

    public function createBackup(array $meta = []): array
    {
        self::ensureDirectory();
        $timestamp = date('Ymd-His');
        $databaseName = trim((string) db_config('database'));
        $databaseSlug = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $databaseName) ?: 'database';
        $fileName = 'backup-' . $databaseSlug . '-' . $timestamp . '.sql';
        $filePath = self::directory() . '/' . $fileName;

        $handle = @fopen($filePath, 'wb');
        if (!is_resource($handle)) {
            throw new RuntimeException('Folder storage/backups tidak dapat ditulis.');
        }

        try {
            $tables = $this->getTables();
            $this->writeHeader($handle, $meta, count($tables));
            foreach ($tables as $table) {
                $this->writeTableDump($handle, $table);
            }
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($handle, "COMMIT;\n");
        } catch (Throwable $e) {
            fclose($handle);
            @unlink($filePath);
            throw $e;
        }

        fclose($handle);

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'size' => (int) (@filesize($filePath) ?: 0),
            'sha1' => sha1_file($filePath) ?: '',
            'table_count' => count($tables),
        ];
    }

    private function writeHeader($handle, array $meta, int $tableCount): void
    {
        $lines = [
            '-- Backup Database Sistem Akuntansi BUMDes',
            '-- Dibuat pada: ' . date('Y-m-d H:i:s'),
            '-- Database: ' . (string) db_config('database'),
            '-- Host: ' . (string) db_config('host') . ':' . (string) db_config('port'),
            '-- Jumlah tabel: ' . $tableCount,
        ];

        $appName = trim((string) ($meta['app_name'] ?? ''));
        if ($appName !== '') {
            $lines[] = '-- Aplikasi: ' . $appName;
        }

        $bumdesName = trim((string) ($meta['bumdes_name'] ?? ''));
        if ($bumdesName !== '') {
            $lines[] = '-- BUMDes: ' . $bumdesName;
        }

        $lines[] = '-- ------------------------------------------------------------';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'START TRANSACTION;';
        $lines[] = '';

        fwrite($handle, implode("\n", $lines) . "\n");
    }

    private function getTables(): array
    {
        $stmt = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM) ?: [];
        $tables = [];
        foreach ($rows as $row) {
            $tableName = trim((string) ($row[0] ?? ''));
            if ($tableName !== '') {
                $tables[] = $tableName;
            }
        }

        return $tables;
    }

    private function writeTableDump($handle, string $table): void
    {
        $safeTable = str_replace('`', '``', $table);
        fwrite($handle, "\n-- ------------------------------------------------------------\n");
        fwrite($handle, '-- Struktur tabel `' . $table . "`\n");
        fwrite($handle, 'DROP TABLE IF EXISTS `' . $safeTable . "`;\n");

        $stmt = $this->db->query('SHOW CREATE TABLE `' . $safeTable . '`');
        $createRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $createSql = (string) ($createRow['Create Table'] ?? '');
        if ($createSql === '') {
            throw new RuntimeException('Gagal membaca struktur tabel: ' . $table);
        }
        fwrite($handle, $createSql . ";\n\n");

        fwrite($handle, '-- Data tabel `' . $table . "`\n");
        $stmt = $this->db->query('SELECT * FROM `' . $safeTable . '`');
        $rowCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rowCount++;
            $columns = array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', array_keys($row));
            $values = [];
            foreach ($row as $value) {
                $values[] = $this->exportValue($value);
            }
            fwrite($handle, 'INSERT INTO `' . $safeTable . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
        }

        if ($rowCount === 0) {
            fwrite($handle, '-- Tidak ada data pada tabel ini.\n');
        }
    }

    private function exportValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->db->quote((string) $value);
    }
}
