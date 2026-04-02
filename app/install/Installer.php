<?php

declare(strict_types=1);

final class Installer
{
    public function __construct(
        private string $rootPath,
        private string $appPath,
        private string $storagePath,
    ) {
    }

    public function isInstalled(): bool
    {
        return is_file($this->getLockFilePath());
    }

    public function getLockFilePath(): string
    {
        return $this->storagePath . '/installed.lock';
    }

    public function getGeneratedConfigPath(): string
    {
        return $this->appPath . '/config/generated.php';
    }

    public function getSqlFiles(): array
    {
        return [
            $this->rootPath . '/database/schema.sql',
            $this->rootPath . '/database/profile_module.sql',
            $this->rootPath . '/database/coa_module.sql',
            $this->rootPath . '/database/patch_stage13_smart_coa.sql',
            $this->rootPath . '/database/period_module.sql',
            $this->rootPath . '/database/journal_module.sql',
            $this->rootPath . '/database/journal_attachment_module.sql',
            $this->rootPath . '/database/patch_journal_print_receipt.sql',
            $this->rootPath . '/database/patch_multi_unit_profile_signature.sql',
            $this->rootPath . '/database/patch_profile_treasurer_receipt_settings.sql',
            $this->rootPath . '/database/asset_module.sql',
            $this->rootPath . '/database/bank_reconciliation_module.sql',
            $this->rootPath . '/database/patch_stage4_bank_reconciliation.sql',
            $this->rootPath . '/database/audit_module.sql',
            $this->rootPath . '/database/patch_stage1_audit_calk.sql',
            $this->rootPath . '/database/patch_profile_legal_identity.sql',
            $this->rootPath . '/database/patch_stage14_journal_reference_metadata.sql',
            $this->rootPath . '/database/patch_stage15_reference_masters.sql',
            $this->rootPath . '/database/patch_stage16_receivable_subledger.sql',
            $this->rootPath . '/database/patch_stage17_payable_subledger.sql',
        ];
    }

    public function getEnvironmentChecks(): array
    {
        $checks = [];
        $checks[] = [
            'label' => 'Versi PHP minimal 8.1',
            'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'current' => PHP_VERSION,
            'required' => '8.1 atau lebih baru',
        ];

        $extensions = [
            'pdo',
            'pdo_mysql',
            'mbstring',
            'json',
            'libxml',
            'simplexml',
            'fileinfo',
            'zip',
        ];

        foreach ($extensions as $extension) {
            $checks[] = [
                'label' => 'Ekstensi ' . $extension,
                'ok' => extension_loaded($extension),
                'current' => extension_loaded($extension) ? 'aktif' : 'tidak aktif',
                'required' => 'aktif',
            ];
        }

        foreach ($this->getWritablePaths() as $path) {
            $checks[] = [
                'label' => 'Folder writable: ' . str_replace($this->rootPath . '/', '', $path),
                'ok' => $this->ensureWritableDirectory($path),
                'current' => is_writable($path) ? 'writable' : 'tidak writable',
                'required' => 'writable',
            ];
        }

        return $checks;
    }

    public function canRunInstaller(): bool
    {
        foreach ($this->getEnvironmentChecks() as $check) {
            if (!$check['ok']) {
                return false;
            }
        }

        return true;
    }

    public function validateInput(array $input): array
    {
        $errors = [];

        if ($this->isInstalled()) {
            $errors[] = 'Aplikasi sudah terpasang. Installer tidak dapat dijalankan lagi sebelum file lock dihapus secara manual.';
            return $errors;
        }

        $required = [
            'db_host' => 'Host database wajib diisi.',
            'db_name' => 'Nama database wajib diisi.',
            'db_user' => 'User database wajib diisi.',
            'admin_name' => 'Nama admin wajib diisi.',
            'admin_username' => 'Username admin wajib diisi.',
            'admin_password' => 'Password admin wajib diisi.',
            'admin_password_confirm' => 'Konfirmasi password admin wajib diisi.',
        ];

        foreach ($required as $key => $message) {
            if (trim((string) ($input[$key] ?? '')) === '') {
                $errors[] = $message;
            }
        }

        if (($input['admin_password'] ?? '') !== ($input['admin_password_confirm'] ?? '')) {
            $errors[] = 'Konfirmasi password admin tidak sama.';
        }

        $adminPassword = (string) ($input['admin_password'] ?? '');
        if ($adminPassword !== '' && strlen($adminPassword) < 8) {
            $errors[] = 'Password admin minimal 8 karakter.';
        }

        $dbPort = (string) ($input['db_port'] ?? '3306');
        if ($dbPort === '' || !ctype_digit($dbPort) || (int) $dbPort < 1 || (int) $dbPort > 65535) {
            $errors[] = 'Port database harus berupa angka 1 sampai 65535.';
        }

        $adminUsername = (string) ($input['admin_username'] ?? '');
        if ($adminUsername !== '' && !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $adminUsername)) {
            $errors[] = 'Username admin hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung dengan panjang 3 sampai 50 karakter.';
        }

        $adminName = trim((string) ($input['admin_name'] ?? ''));
        if ($adminName !== '' && mb_strlen($adminName) > 100) {
            $errors[] = 'Nama admin maksimal 100 karakter.';
        }

        $appUrl = trim((string) ($input['app_url'] ?? ''));
        if ($appUrl === '') {
            $errors[] = 'URL aplikasi wajib diisi.';
        } elseif (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL aplikasi tidak valid.';
        }

        foreach (['db_host', 'db_name', 'db_user'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > 150) {
                $errors[] = 'Isian ' . $field . ' terlalu panjang.';
            }
        }

        return $errors;
    }

    public function testDatabaseConnection(array $input): array
    {
        try {
            $pdo = $this->createPdo($input);
            $pdo->query('SELECT 1');

            return [true, 'Koneksi database berhasil.'];
        } catch (Throwable $e) {
            log_error($e);
            return [false, 'Koneksi database gagal. Periksa host, port, nama database, username, dan password yang Anda masukkan.'];
        }
    }

    public function install(array $input): array
    {
        $errors = $this->validateInput($input);
        if ($errors !== []) {
            return [false, $errors];
        }

        if (!$this->canRunInstaller()) {
            return [false, ['Environment server belum memenuhi syarat installer. Periksa daftar pengecekan sistem terlebih dahulu.']];
        }

        try {
            $pdo = $this->createPdo($input);
            $this->importSqlFiles($pdo);
            $this->seedRoles($pdo);
            $this->createFirstAdmin($pdo, $input);
            $this->writeGeneratedConfig($input);
            $this->writeLockFile($input);

            return [true, ['Instalasi berhasil. Silakan masuk menggunakan akun admin yang baru dibuat.']];
        } catch (Throwable $e) {
            log_error($e);
            return [false, ['Instalasi gagal. Periksa pengaturan database, permission folder writable, lalu coba lagi. Detail teknis tersimpan di log aplikasi.']];
        }
    }

    public function getDefaultAppUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '/public') {
            $basePath = '';
        }
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        return $scheme . '://' . $host . $basePath;
    }

    private function createPdo(array $input): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            trim((string) $input['db_host']),
            (int) $input['db_port'],
            trim((string) $input['db_name'])
        );

        return new PDO($dsn, (string) $input['db_user'], (string) ($input['db_pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private function importSqlFiles(PDO $pdo): void
    {
        foreach ($this->getSqlFiles() as $file) {
            if (!is_file($file)) {
                throw new RuntimeException('File SQL tidak ditemukan: ' . basename($file));
            }

            $statements = $this->splitSqlStatements((string) file_get_contents($file));
            foreach ($statements as $statement) {
                if (trim($statement) === '') {
                    continue;
                }
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    $message = $e->getMessage();
                    if (str_contains($message, 'already exists') || str_contains($message, 'Duplicate key name')) {
                        continue;
                    }
                    throw $e;
                }
            }
        }
    }

    private function seedRoles(PDO $pdo): void
    {
        $roles = [
            ['code' => 'admin', 'name' => 'Admin'],
            ['code' => 'bendahara', 'name' => 'Bendahara'],
            ['code' => 'pimpinan', 'name' => 'Pimpinan'],
        ];

        $checkStmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $insertStmt = $pdo->prepare('INSERT INTO roles (code, name) VALUES (:code, :name)');

        foreach ($roles as $role) {
            $checkStmt->execute([':code' => $role['code']]);
            $existing = $checkStmt->fetchColumn();
            if ($existing) {
                continue;
            }
            $insertStmt->execute([
                ':code' => $role['code'],
                ':name' => $role['name'],
            ]);
        }
    }

    private function createFirstAdmin(PDO $pdo, array $input): void
    {
        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $roleStmt->execute([':code' => 'admin']);
        $roleId = $roleStmt->fetchColumn();
        if (!$roleId) {
            throw new RuntimeException('Role admin tidak ditemukan setelah import database.');
        }

        $userCheck = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $userCheck->execute([':username' => trim((string) $input['admin_username'])]);
        if ($userCheck->fetchColumn()) {
            throw new RuntimeException('Username admin sudah ada di database. Gunakan username lain atau kosongkan database sebelum install.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, full_name, username, password_hash, is_active, created_at, updated_at)
             VALUES (:role_id, :full_name, :username, :password_hash, 1, NOW(), NOW())'
        );

        $stmt->execute([
            ':role_id' => (int) $roleId,
            ':full_name' => trim((string) $input['admin_name']),
            ':username' => trim((string) $input['admin_username']),
            ':password_hash' => password_hash((string) $input['admin_password'], PASSWORD_DEFAULT),
        ]);
    }

    private function writeGeneratedConfig(array $input): void
    {
        $target = $this->getGeneratedConfigPath();
        $payload = [
            'app' => [
                'env' => 'production',
                'debug' => false,
                'url' => rtrim((string) $input['app_url'], '/'),
                'installed' => true,
            ],
            'database' => [
                'host' => trim((string) $input['db_host']),
                'port' => (int) $input['db_port'],
                'database' => trim((string) $input['db_name']),
                'username' => trim((string) $input['db_user']),
                'password' => (string) ($input['db_pass'] ?? ''),
            ],
        ];

        $content = "<?php\n";
        $content .= "declare(strict_types=1);\n";
        $content .= 'return ' . var_export($payload, true) . ";\n";

        if (@file_put_contents($target, $content, LOCK_EX) === false) {
            throw new RuntimeException('File konfigurasi tidak dapat ditulis. Pastikan folder app/config dapat ditulis oleh server.');
        }
    }

    private function writeLockFile(array $input): void
    {
        $payload = [
            'installed_at' => date('c'),
            'app_url' => rtrim((string) $input['app_url'], '/'),
            'database' => [
                'host' => trim((string) $input['db_host']),
                'name' => trim((string) $input['db_name']),
            ],
        ];

        if (@file_put_contents($this->getLockFilePath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            throw new RuntimeException('File penanda instalasi tidak dapat dibuat. Pastikan folder storage dapat ditulis oleh server.');
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $lines = preg_split('/\R/', $sql) ?: [];
        $buffer = '';
        $statements = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $buffer .= $line . "\n";
            if (preg_match('/;\s*$/', $trimmed) === 1) {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }

    private function getWritablePaths(): array
    {
        return [
            $this->appPath . '/config',
            $this->storagePath,
            $this->storagePath . '/logs',
            $this->storagePath . '/imports',
            $this->storagePath . '/backups',
            $this->storagePath . '/bank_reconciliations',
            $this->storagePath . '/journal_attachments',
            $this->rootPath . '/public/uploads/profiles',
            $this->rootPath . '/public/uploads/signatures',
        ];
    }

    private function ensureWritableDirectory(string $path): bool
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            return false;
        }

        return is_writable($path);
    }
}
