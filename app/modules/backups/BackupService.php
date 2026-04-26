<?php

declare(strict_types=1);

final class BackupService
{
    private const MAX_UPLOAD_SIZE = 31457280;
    private const RETENTION_DAYS = 30;
    private const MIN_KEEP_FILES = 7;
    private const BACKUP_STALE_WARNING_SECONDS = 86400;
    private const BACKUP_STALE_CRITICAL_SECONDS = 259200;

    public function __construct(private PDO $db)
    {
    }

    public static function directory(): string
    {
        return ROOT_PATH . '/storage/backups';
    }

    public static function ensureDirectory(): void
    {
        foreach ([self::directory(), self::stagingDirectory(), self::recoveryDirectory(), self::recoveryReportDirectory()] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    public static function stagingDirectory(): string
    {
        return self::directory() . '/restore_staging';
    }

    public static function recoveryDirectory(): string
    {
        return ROOT_PATH . '/storage/recovery';
    }

    public static function recoveryReportDirectory(): string
    {
        return self::recoveryDirectory() . '/reports';
    }

    public static function recoveryStatePath(): string
    {
        return self::recoveryDirectory() . '/recovery-state.json';
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
        $paths = array_values(array_filter(
            glob(self::directory() . '/*.sql') ?: [],
            static fn (string $path): bool => is_file($path)
        ));

        usort($paths, static fn (string $a, string $b): int => ((int) (@filemtime($b) ?: 0)) <=> ((int) (@filemtime($a) ?: 0)));

        $files = [];
        foreach ($paths as $index => $path) {
            $files[] = self::describeFile($path, $index === 0);
        }

        return $files;
    }

    public static function summarizeFiles(array $files): array
    {
        $latest = $files[0] ?? null;
        $stale = self::backupStaleStatus($latest);

        return [
            'count' => count($files),
            'total_size' => array_sum(array_map(static fn (array $file): int => (int) ($file['size'] ?? 0), $files)),
            'latest' => $latest['modified_at'] ?? '',
            'latest_file' => $latest,
            'latest_age_label' => $latest['age_label'] ?? 'Belum ada',
            'latest_stale_level' => $stale['level'],
            'latest_stale_note' => $stale['note'],
            'latest_is_stale_1d' => $stale['is_warning'],
            'latest_is_stale_3d' => $stale['is_critical'],
        ];
    }

    public static function backupStaleStatus(?array $latestFile): array
    {
        if (!is_array($latestFile)) {
            return [
                'level' => 'critical',
                'is_warning' => true,
                'is_critical' => true,
                'note' => 'Belum ada backup database yang tersedia.',
            ];
        }

        $ageSeconds = (int) ($latestFile['age_seconds'] ?? PHP_INT_MAX);
        if ($ageSeconds >= self::BACKUP_STALE_CRITICAL_SECONDS) {
            return [
                'level' => 'critical',
                'is_warning' => true,
                'is_critical' => true,
                'note' => 'Backup terakhir sudah lebih dari 3 hari. Risiko kehilangan data tinggi jika terjadi gangguan.',
            ];
        }
        if ($ageSeconds >= self::BACKUP_STALE_WARNING_SECONDS) {
            return [
                'level' => 'warning',
                'is_warning' => true,
                'is_critical' => false,
                'note' => 'Backup terakhir lebih dari 1 hari. Sebaiknya buat backup baru hari ini.',
            ];
        }

        return [
            'level' => 'ok',
            'is_warning' => false,
            'is_critical' => false,
            'note' => 'Backup terakhir masih segar dan aman untuk baseline recovery.',
        ];
    }

    public static function readRecoveryState(): array
    {
        self::ensureDirectory();
        $path = self::recoveryStatePath();
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
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

    public function createDailySafetyBackupIfMissing(array $meta = []): array
    {
        $today = date('Y-m-d');
        foreach (self::listFiles() as $file) {
            if ((string) ($file['date_key'] ?? '') === $today) {
                return [
                    'created' => false,
                    'skipped' => true,
                    'reason' => 'daily_backup_already_exists',
                    'file' => $file,
                ];
            }
        }

        $result = $this->createBackup($meta + ['reason' => 'admin_daily_safety_backup']);
        return [
            'created' => true,
            'skipped' => false,
            'reason' => 'created_new_daily_backup',
            'file' => $result,
        ];
    }

    public function recordRecoveryEvent(array $payload): array
    {
        self::ensureDirectory();

        $state = [
            'recorded_at' => date('c'),
            'restored_at' => (string) ($payload['restored_at'] ?? date('c')),
            'source_file_name' => (string) ($payload['source_file_name'] ?? ''),
            'source_file_sha1' => (string) ($payload['source_file_sha1'] ?? ''),
            'source_file_size' => (int) ($payload['source_file_size'] ?? 0),
            'source_file_modified_at' => (string) ($payload['source_file_modified_at'] ?? ''),
            'source_backup_age_days' => isset($payload['source_backup_age_days']) ? (float) $payload['source_backup_age_days'] : null,
            'restore_mode' => (string) ($payload['restore_mode'] ?? 'server'),
            'pre_restore_backup' => $payload['pre_restore_backup'] ?? null,
            'restored_by' => $payload['restored_by'] ?? null,
            'app_version' => $this->manifestVersion(),
            'data_audit' => $this->collectDataAuditSummary(),
            'readiness' => $this->buildRecoveryReadiness(),
        ];

        file_put_contents(
            self::recoveryStatePath(),
            (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $reportName = 'recovery-' . date('Ymd-His') . '.txt';
        $reportPath = self::recoveryReportDirectory() . '/' . $reportName;
        $lines = [
            'RECOVERY READINESS REPORT',
            'Recorded at: ' . date('Y-m-d H:i:s'),
            'Restored at: ' . (string) $state['restored_at'],
            'App version: ' . (string) $state['app_version'],
            'Restore mode: ' . (string) $state['restore_mode'],
            'Source file: ' . (string) $state['source_file_name'],
            'Source file modified at: ' . (string) $state['source_file_modified_at'],
            'Source backup age days: ' . (string) ($state['source_backup_age_days'] ?? '-'),
            'Restored by: ' . (string) (($state['restored_by']['full_name'] ?? '') ?: ($state['restored_by']['username'] ?? '-')),
            'Pre-restore backup: ' . (string) (($state['pre_restore_backup']['file_name'] ?? '-') ?: '-'),
            'Backup count now: ' . (string) (($state['readiness']['backup_summary']['count'] ?? 0)),
            'Latest backup now: ' . (string) (($state['readiness']['backup_summary']['latest_file']['name'] ?? '-') ?: '-'),
            'Pending migrations: ' . number_format((int) ($state['readiness']['pending_migrations_count'] ?? 0), 0, ',', '.'),
            'Data audit users: ' . number_format((int) (($state['data_audit']['counts']['users'] ?? 0)), 0, ',', '.'),
            'Data audit roles: ' . number_format((int) (($state['data_audit']['counts']['roles'] ?? 0)), 0, ',', '.'),
            'Data audit periods: ' . number_format((int) (($state['data_audit']['counts']['periods'] ?? 0)), 0, ',', '.'),
            'Data audit journals: ' . number_format((int) (($state['data_audit']['counts']['journals'] ?? 0)), 0, ',', '.'),
        ];
        file_put_contents($reportPath, implode(PHP_EOL, $lines) . PHP_EOL);

        $state['report_file'] = $reportName;
        file_put_contents(
            self::recoveryStatePath(),
            (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $state;
    }

    public function buildRecoveryReadiness(): array
    {
        $files = self::listFiles();
        $summary = self::summarizeFiles($files);
        $stale = self::backupStaleStatus($files[0] ?? null);
        $pendingMigrations = [];

        try {
            $pendingMigrations = array_map('basename', (new MigrationRunner($this->db))->pendingMigrations());
        } catch (Throwable) {
            $pendingMigrations = [];
        }

        return [
            'backup_summary' => $summary,
            'backup_stale' => $stale,
            'directories' => [
                'backup' => [
                    'path' => self::directory(),
                    'writable' => is_dir(self::directory()) && is_writable(self::directory()),
                ],
                'recovery' => [
                    'path' => self::recoveryDirectory(),
                    'writable' => is_dir(self::recoveryDirectory()) && is_writable(self::recoveryDirectory()),
                ],
            ],
            'db_connected' => Database::isConnected(db_config()),
            'manifest_version' => $this->manifestVersion(),
            'pending_migrations' => $pendingMigrations,
            'pending_migrations_count' => count($pendingMigrations),
            'recovery_state' => self::readRecoveryState(),
        ];
    }

    public function collectDataAuditSummary(): array
    {
        $counts = [
            'users' => $this->countRowsIfTableExists('users'),
            'roles' => $this->countRowsIfTableExists('roles'),
            'periods' => $this->countRowsIfTableExists('accounting_periods'),
            'journals' => $this->countRowsIfTableExists('journal_headers'),
            'business_units' => $this->countRowsIfTableExists('business_units'),
            'coa_accounts' => $this->countRowsIfTableExists('coa_accounts'),
            'assets' => $this->countRowsIfTableExists('asset_items'),
        ];

        $activePeriod = current_accounting_period();
        $profileName = $this->scalarIfTableExists("SELECT bumdes_name FROM app_profiles ORDER BY id ASC LIMIT 1", 'app_profiles');
        $adminCount = $this->scalarIfTableExists(
            "SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1 AND r.code = 'admin'",
            'users',
            'roles'
        );

        $checks = [
            [
                'label' => 'User admin aktif tersedia',
                'status' => ((int) $adminCount) > 0 ? 'ok' : 'critical',
                'message' => ((int) $adminCount) > 0 ? 'Minimal satu admin aktif tersedia untuk akses pemulihan.' : 'Tidak ada admin aktif yang terdeteksi.',
            ],
            [
                'label' => 'Role aplikasi tersedia',
                'status' => $counts['roles'] > 0 ? 'ok' : 'critical',
                'message' => $counts['roles'] > 0 ? 'Role dasar aplikasi tersedia.' : 'Tabel role kosong atau belum siap.',
            ],
            [
                'label' => 'Periode aktif tersedia',
                'status' => is_array($activePeriod) ? 'ok' : 'warning',
                'message' => is_array($activePeriod) ? 'Periode aktif: ' . (string) ($activePeriod['period_name'] ?? '-') : 'Periode aktif belum terbaca. Periksa modul periode.',
            ],
            [
                'label' => 'Profil BUMDes terbaca',
                'status' => trim((string) $profileName) !== '' ? 'ok' : 'warning',
                'message' => trim((string) $profileName) !== '' ? 'Profil BUMDes terisi: ' . (string) $profileName : 'Nama BUMDes belum terisi atau profil belum terbaca.',
            ],
        ];

        return [
            'counts' => $counts,
            'checks' => $checks,
            'active_period' => is_array($activePeriod) ? [
                'id' => (int) ($activePeriod['id'] ?? 0),
                'period_name' => (string) ($activePeriod['period_name'] ?? ''),
                'period_code' => (string) ($activePeriod['period_code'] ?? ''),
            ] : null,
            'profile_name' => (string) $profileName,
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
        $cleanup = $this->cleanupRetention();
        $entry = self::describeFile($filePath, true);

        return $entry + [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'table_count' => count($tables),
            'retention_deleted' => $cleanup['deleted_count'],
            'retention_deleted_files' => $cleanup['deleted_files'],
        ];
    }

    private function cleanupRetention(): array
    {
        $files = self::listFiles();
        $deleted = [];
        foreach ($files as $index => $file) {
            if ($index < self::MIN_KEEP_FILES) {
                continue;
            }

            $ageSeconds = (int) ($file['age_seconds'] ?? 0);
            if ($ageSeconds < (self::RETENTION_DAYS * 86400)) {
                continue;
            }

            $path = (string) ($file['path'] ?? '');
            if ($path !== '' && is_file($path) && @unlink($path)) {
                $deleted[] = (string) ($file['name'] ?? basename($path));
            }
        }

        return [
            'deleted_count' => count($deleted),
            'deleted_files' => $deleted,
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

        $reason = trim((string) ($meta['reason'] ?? 'manual_backup'));
        if ($reason !== '') {
            $lines[] = '-- Alasan backup: ' . $reason;
        }

        $actor = trim((string) ($meta['actor_name'] ?? ''));
        if ($actor !== '') {
            $lines[] = '-- Dibuat oleh: ' . $actor;
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
            fwrite($handle, '-- Tidak ada data pada tabel ini.' . "\n");
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

    private static function describeFile(string $path, bool $isLatest): array
    {
        $mtime = (int) (@filemtime($path) ?: time());
        $ageSeconds = max(0, time() - $mtime);
        $stale = self::backupStaleStatus([
            'age_seconds' => $ageSeconds,
        ]);

        return [
            'name' => basename($path),
            'path' => $path,
            'size' => (int) (@filesize($path) ?: 0),
            'modified_at' => date('Y-m-d H:i:s', $mtime),
            'modified_ts' => $mtime,
            'sha1' => sha1_file($path) ?: '',
            'date_key' => date('Y-m-d', $mtime),
            'is_latest' => $isLatest,
            'age_seconds' => $ageSeconds,
            'age_label' => self::humanizeAge($ageSeconds),
            'stale_level' => $stale['level'],
        ];
    }

    private static function humanizeAge(int $seconds): string
    {
        if ($seconds < 60) {
            return 'Baru saja';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . ' menit lalu';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600) . ' jam lalu';
        }
        if ($seconds < 604800) {
            return floor($seconds / 86400) . ' hari lalu';
        }

        return floor($seconds / 604800) . ' minggu lalu';
    }

    private function countRowsIfTableExists(string $table): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        return (int) $this->db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn();
    }

    private function scalarIfTableExists(string $sql, string ...$tables): mixed
    {
        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                return null;
            }
        }

        return $this->db->query($sql)->fetchColumn();
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name
             LIMIT 1'
        );
        $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    private function manifestVersion(): string
    {
        $path = ROOT_PATH . '/release-manifest.json';
        if (!is_file($path)) {
            return '';
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? (string) ($decoded['release_version'] ?? '') : '';
    }
}
