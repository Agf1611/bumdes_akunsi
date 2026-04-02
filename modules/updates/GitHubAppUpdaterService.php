<?php

declare(strict_types=1);

final class GitHubAppUpdaterService
{
    private const REPORT_LIMIT = 12;
    private const HTTP_TIMEOUT = 45;

    public static function directory(): string
    {
        return ROOT_PATH . '/storage/update_manager';
    }

    public static function reportDirectory(): string
    {
        return self::directory() . '/reports';
    }

    public static function cacheDirectory(): string
    {
        return self::directory() . '/cache';
    }

    public static function tempDirectory(): string
    {
        return self::directory() . '/temp';
    }

    public static function fileBackupDirectory(): string
    {
        return self::directory() . '/file_backups';
    }

    public static function ensureDirectories(): void
    {
        foreach ([self::directory(), self::reportDirectory(), self::cacheDirectory(), self::tempDirectory(), self::fileBackupDirectory()] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    public static function statePath(): string
    {
        return self::cacheDirectory() . '/state.json';
    }

    public static function lastCheckPath(): string
    {
        return self::cacheDirectory() . '/last_check.json';
    }

    public static function latestReports(): array
    {
        self::ensureDirectories();
        $reports = [];
        foreach (glob(self::reportDirectory() . '/*.txt') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $reports[] = [
                'name' => basename($path),
                'size' => (int) (@filesize($path) ?: 0),
                'modified_at' => date('Y-m-d H:i:s', (int) (@filemtime($path) ?: time())),
            ];
        }

        usort($reports, static fn (array $a, array $b): int => strcmp((string) $b['modified_at'], (string) $a['modified_at']));
        return array_slice($reports, 0, self::REPORT_LIMIT);
    }

    public static function safeReportPath(string $fileName): ?string
    {
        $safe = basename(trim($fileName));
        if ($safe === '' || !preg_match('/\A[a-zA-Z0-9._-]+\.txt\z/', $safe)) {
            return null;
        }

        $path = self::reportDirectory() . '/' . $safe;
        return is_file($path) ? $path : null;
    }

    public static function readJsonFile(string $path): array
    {
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

    public function repoUrl(): string
    {
        $configured = trim((string) app_config('update_repo_url'));
        return $configured !== '' ? $configured : 'https://github.com/Agf1611/bumdes_akunsi.git';
    }

    public function branch(): string
    {
        $branch = trim((string) app_config('update_branch'));
        return $branch !== '' ? $branch : 'main';
    }

    public function currentVersion(): string
    {
        $versionPath = ROOT_PATH . '/VERSION';
        if (is_file($versionPath)) {
            $value = trim((string) file_get_contents($versionPath));
            if ($value !== '') {
                return $value;
            }
        }

        $state = $this->state();
        $version = trim((string) ($state['current_version'] ?? ''));
        if ($version !== '') {
            return $version;
        }

        return 'local-unversioned';
    }

    public function state(): array
    {
        self::ensureDirectories();
        return self::readJsonFile(self::statePath());
    }

    public function lastCheck(): array
    {
        self::ensureDirectories();
        return self::readJsonFile(self::lastCheckPath());
    }

    public function checkForUpdates(): array
    {
        self::ensureDirectories();
        $repo = $this->parseRepo();
        $remoteMeta = $this->fetchBranchMeta($repo['owner'], $repo['repo'], $this->branch());
        $workspace = $this->downloadAndExtract($repo['owner'], $repo['repo'], $this->branch());

        try {
            $remoteRoot = $workspace['root'];
            $remoteFiles = $this->buildFileMap($remoteRoot);
            $localFiles = $this->buildFileMap(ROOT_PATH);

            $changed = [];
            $new = [];
            $unchangedCount = 0;

            foreach ($remoteFiles as $relativePath => $remoteInfo) {
                $localInfo = $localFiles[$relativePath] ?? null;
                if ($localInfo === null) {
                    $new[] = $remoteInfo;
                    continue;
                }

                if ((string) ($localInfo['sha1'] ?? '') !== (string) ($remoteInfo['sha1'] ?? '')) {
                    $changed[] = $remoteInfo + [
                        'local_sha1' => (string) ($localInfo['sha1'] ?? ''),
                        'local_size' => (int) ($localInfo['size'] ?? 0),
                    ];
                    continue;
                }

                $unchangedCount++;
            }

            $obsolete = [];
            foreach ($localFiles as $relativePath => $localInfo) {
                if (!isset($remoteFiles[$relativePath])) {
                    $obsolete[] = $localInfo;
                }
            }

            usort($changed, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));
            usort($new, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));
            usort($obsolete, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

            $remoteVersion = $this->readRemoteVersion($remoteRoot);
            $state = $this->state();
            $updateAvailable = $changed !== [] || $new !== [] || ((string) ($remoteMeta['commit_sha'] ?? '')) !== '' && ((string) ($state['current_commit'] ?? '')) !== (string) ($remoteMeta['commit_sha'] ?? '');

            $report = [
                'status' => 'checked',
                'checked_at' => date('Y-m-d H:i:s'),
                'repo_url' => $this->repoUrl(),
                'branch' => $this->branch(),
                'remote' => $remoteMeta + ['version' => $remoteVersion],
                'local' => [
                    'version' => $this->currentVersion(),
                    'commit' => (string) ($state['current_commit'] ?? ''),
                ],
                'summary' => [
                    'update_available' => $updateAvailable,
                    'changed_count' => count($changed),
                    'new_count' => count($new),
                    'unchanged_count' => $unchangedCount,
                    'obsolete_count' => count($obsolete),
                ],
                'files' => [
                    'changed' => $changed,
                    'new' => $new,
                    'obsolete' => $obsolete,
                ],
            ];

            $this->writeJson(self::lastCheckPath(), $report);
            $reportFile = $this->writeReport('check', $report);
            $report['report_file'] = basename($reportFile);
            $this->writeJson(self::lastCheckPath(), $report);

            return $report;
        } finally {
            $this->cleanupWorkspace($workspace['workspace']);
        }
    }

    public function applyUpdates(): array
    {
        self::ensureDirectories();

        if (!Database::isConnected(db_config())) {
            throw new RuntimeException('Koneksi database tidak tersedia. Update dibatalkan agar backup database tidak gagal.');
        }

        $check = $this->checkForUpdates();
        $summary = (array) ($check['summary'] ?? []);
        $updateAvailable = (bool) ($summary['update_available'] ?? false);

        if (!$updateAvailable && (int) ($summary['changed_count'] ?? 0) === 0 && (int) ($summary['new_count'] ?? 0) === 0) {
            $result = $check;
            $result['status'] = 'up-to-date';
            $result['message'] = 'Aplikasi sudah berada di versi yang sama dengan GitHub. Tidak ada file yang diganti.';
            $reportFile = $this->writeReport('update-noop', $result);
            $result['report_file'] = basename($reportFile);
            return $result;
        }

        $backupService = new BackupService(Database::getInstance(db_config()));
        $profile = app_profile();
        $dbBackup = $backupService->createBackup([
            'app_name' => (string) app_config('name'),
            'bumdes_name' => (string) ($profile['bumdes_name'] ?? ''),
        ]);

        $repo = $this->parseRepo();
        $workspace = $this->downloadAndExtract($repo['owner'], $repo['repo'], $this->branch());
        $fileBackupId = date('Ymd-His');
        $fileBackupRoot = self::fileBackupDirectory() . '/' . $fileBackupId;
        @mkdir($fileBackupRoot, 0775, true);

        $applied = [];
        $created = [];

        try {
            $remoteRoot = $workspace['root'];
            $allToApply = array_merge((array) ($check['files']['changed'] ?? []), (array) ($check['files']['new'] ?? []));
            usort($allToApply, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

            foreach ($allToApply as $entry) {
                $relativePath = (string) ($entry['path'] ?? '');
                if ($relativePath === '') {
                    continue;
                }

                $sourcePath = $remoteRoot . '/' . $relativePath;
                $targetPath = ROOT_PATH . '/' . $relativePath;
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                if (is_file($targetPath)) {
                    $backupPath = $fileBackupRoot . '/' . $relativePath;
                    $backupDir = dirname($backupPath);
                    if (!is_dir($backupDir)) {
                        @mkdir($backupDir, 0775, true);
                    }
                    if (!@copy($targetPath, $backupPath)) {
                        throw new RuntimeException('Gagal membuat backup file lama: ' . $relativePath);
                    }
                } else {
                    $created[] = $relativePath;
                }

                if (!@copy($sourcePath, $targetPath)) {
                    throw new RuntimeException('Gagal menyalin file update: ' . $relativePath);
                }

                @chmod($targetPath, 0664);
                $applied[] = $relativePath;
            }

            $state = $this->state();
            $newState = [
                'repo_url' => $this->repoUrl(),
                'branch' => $this->branch(),
                'current_commit' => (string) (($check['remote']['commit_sha'] ?? '') ?: ($state['current_commit'] ?? '')),
                'current_commit_short' => (string) (($check['remote']['commit_short'] ?? '') ?: ($state['current_commit_short'] ?? '')),
                'current_version' => $this->currentVersion(),
                'last_checked_at' => (string) ($check['checked_at'] ?? date('Y-m-d H:i:s')),
                'last_updated_at' => date('Y-m-d H:i:s'),
                'last_report_file' => '',
                'last_database_backup' => $dbBackup['file_name'] ?? '',
                'last_file_backup_dir' => basename($fileBackupRoot),
            ];

            $result = $check;
            $result['status'] = 'updated';
            $result['updated_at'] = date('Y-m-d H:i:s');
            $result['database_backup'] = $dbBackup;
            $result['file_backup_dir'] = basename($fileBackupRoot);
            $result['applied_files'] = $applied;
            $result['created_files'] = $created;
            $result['message'] = 'Update selesai. Sistem hanya mengganti file yang berubah dari GitHub dan tidak menyentuh upload, storage, atau generated config.';
            $reportFile = $this->writeReport('update-success', $result);
            $result['report_file'] = basename($reportFile);
            $newState['last_report_file'] = basename($reportFile);
            $newState['current_version'] = $this->currentVersion();
            $this->writeJson(self::statePath(), $newState);
            $this->writeJson(self::lastCheckPath(), $result);

            return $result;
        } catch (Throwable $e) {
            foreach (array_reverse($applied) as $relativePath) {
                $targetPath = ROOT_PATH . '/' . $relativePath;
                $backupPath = $fileBackupRoot . '/' . $relativePath;
                if (is_file($backupPath)) {
                    @copy($backupPath, $targetPath);
                } elseif (in_array($relativePath, $created, true) && is_file($targetPath)) {
                    @unlink($targetPath);
                }
            }

            $failure = [
                'status' => 'failed',
                'failed_at' => date('Y-m-d H:i:s'),
                'message' => $e->getMessage(),
                'database_backup' => $dbBackup,
                'rollback_applied' => true,
                'applied_before_error' => $applied,
                'created_before_error' => $created,
                'last_check' => $check,
            ];
            $reportFile = $this->writeReport('update-failed', $failure);
            $failure['report_file'] = basename($reportFile);
            $this->writeJson(self::lastCheckPath(), $failure);
            throw new RuntimeException('Update aplikasi gagal. Rollback file lokal sudah dicoba otomatis. Lihat laporan: ' . basename($reportFile) . '. Detail: ' . $e->getMessage(), 0, $e);
        } finally {
            $this->cleanupWorkspace($workspace['workspace']);
        }
    }

    private function parseRepo(): array
    {
        $url = trim($this->repoUrl());
        if (!preg_match('#github\.com/([^/]+)/([^/]+?)(?:\.git)?/?$#i', $url, $matches)) {
            throw new RuntimeException('URL repository GitHub tidak valid. Gunakan format https://github.com/owner/repo.git');
        }

        return [
            'owner' => $matches[1],
            'repo' => $matches[2],
        ];
    }

    private function fetchBranchMeta(string $owner, string $repo, string $branch): array
    {
        $url = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/branches/' . rawurlencode($branch);
        $response = $this->httpJson($url);
        $commit = (array) ($response['commit'] ?? []);
        $sha = trim((string) ($commit['sha'] ?? ''));
        $authorDate = (string) (((array) ($commit['commit']['author'] ?? []))['date'] ?? '');

        return [
            'commit_sha' => $sha,
            'commit_short' => $sha !== '' ? substr($sha, 0, 7) : '',
            'commit_message' => trim((string) (($commit['commit']['message'] ?? ''))),
            'commit_date' => $authorDate !== '' ? date('Y-m-d H:i:s', strtotime($authorDate) ?: time()) : '',
            'html_url' => (string) ($response['_links']['html'] ?? ''),
        ];
    }

    private function downloadAndExtract(string $owner, string $repo, string $branch): array
    {
        $workspace = self::tempDirectory() . '/update-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        @mkdir($workspace, 0775, true);
        $extractDir = $workspace . '/extract';
        @mkdir($extractDir, 0775, true);

        $used = null;
        $errorMessages = [];

        if (class_exists('PharData')) {
            try {
                $archivePath = $workspace . '/source.tar.gz';
                $downloadUrl = 'https://codeload.github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/tar.gz/refs/heads/' . rawurlencode($branch);
                $this->downloadFile($downloadUrl, $archivePath);
                $tarPath = substr($archivePath, 0, -3);
                if (is_file($tarPath)) {
                    @unlink($tarPath);
                }
                $phar = new PharData($archivePath);
                $phar->decompress();
                $tar = new PharData($tarPath);
                $tar->extractTo($extractDir, null, true);
                $used = 'tar';
            } catch (Throwable $e) {
                $errorMessages[] = 'tar.gz: ' . $e->getMessage();
            }
        }

        if ($used === null && class_exists('ZipArchive')) {
            try {
                $archivePath = $workspace . '/source.zip';
                $downloadUrl = 'https://codeload.github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zip/refs/heads/' . rawurlencode($branch);
                $this->downloadFile($downloadUrl, $archivePath);
                $zip = new ZipArchive();
                if ($zip->open($archivePath) !== true) {
                    throw new RuntimeException('Arsip zip GitHub tidak dapat dibuka.');
                }
                if (!$zip->extractTo($extractDir)) {
                    $zip->close();
                    throw new RuntimeException('Arsip zip GitHub gagal diekstrak.');
                }
                $zip->close();
                $used = 'zip';
            } catch (Throwable $e) {
                $errorMessages[] = 'zip: ' . $e->getMessage();
            }
        }

        if ($used === null) {
            throw new RuntimeException('Server tidak bisa mengekstrak paket update. Aktifkan ekstensi PharData atau ZipArchive. Detail: ' . implode(' | ', $errorMessages));
        }

        $root = $extractDir;
        $entries = array_values(array_filter(glob($extractDir . '/*') ?: [], static fn (string $path): bool => is_dir($path)));
        if (count($entries) === 1) {
            $root = $entries[0];
        }

        if (!is_dir($root . '/app') || !is_dir($root . '/public')) {
            throw new RuntimeException('Struktur source dari GitHub tidak dikenali. Folder app/public tidak ditemukan pada paket update.');
        }

        return [
            'workspace' => $workspace,
            'root' => $root,
        ];
    }

    private function buildFileMap(string $basePath): array
    {
        $basePath = rtrim($basePath, '/');
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $fullPath = str_replace('\\', '/', $fileInfo->getPathname());
            $relativePath = ltrim(str_replace(str_replace('\\', '/', $basePath), '', $fullPath), '/');
            if ($relativePath === '' || !$this->isUpdatablePath($relativePath)) {
                continue;
            }

            $files[$relativePath] = [
                'path' => $relativePath,
                'full_path' => $fullPath,
                'sha1' => sha1_file($fullPath) ?: '',
                'size' => (int) ($fileInfo->getSize() ?: 0),
            ];
        }

        ksort($files);
        return $files;
    }

    private function isUpdatablePath(string $relativePath): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return false;
        }

        $excludedPrefixes = [
            'storage/',
            'public/uploads/',
            '.git/',
            '.github/',
            'docs/',
            'scripts/',
        ];

        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return false;
            }
        }

        $excludedExact = [
            'app/config/generated.php',
            'storage/installed.lock',
        ];
        if (in_array($relativePath, $excludedExact, true)) {
            return false;
        }

        $baseName = basename($relativePath);
        if (preg_match('/\AREADME/i', $baseName)) {
            return false;
        }
        if (preg_match('/\AIMPLEMENTASI_/i', $baseName)) {
            return false;
        }
        if (preg_match('/\Atest_.*\.php\z/i', $baseName)) {
            return false;
        }
        if (preg_match('/\.sql\z/i', $baseName)) {
            return false;
        }
        if (preg_match('/\.tar\.gz\.?\z/i', $baseName)) {
            return false;
        }

        return true;
    }

    private function readRemoteVersion(string $remoteRoot): string
    {
        $versionPath = $remoteRoot . '/VERSION';
        if (is_file($versionPath)) {
            $value = trim((string) file_get_contents($versionPath));
            if ($value !== '') {
                return $value;
            }
        }

        return 'unknown';
    }

    private function httpJson(string $url): array
    {
        $body = $this->httpRequest($url, ['Accept: application/vnd.github+json']);
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Respons GitHub tidak valid: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Respons GitHub kosong atau bukan JSON yang valid.');
        }

        if (isset($data['message']) && is_string($data['message']) && $data['message'] !== '') {
            throw new RuntimeException('GitHub API: ' . $data['message']);
        }

        return $data;
    }

    private function downloadFile(string $url, string $targetPath): void
    {
        $body = $this->httpRequest($url, ['Accept: application/octet-stream']);
        if (@file_put_contents($targetPath, $body) === false) {
            throw new RuntimeException('File update gagal disimpan ke folder sementara server.');
        }
    }

    private function httpRequest(string $url, array $headers = []): string
    {
        $headers[] = 'User-Agent: BUMDes-App-Updater/1.0';
        $headers[] = 'X-GitHub-Api-Version: 2022-11-28';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('cURL tidak dapat diinisialisasi.');
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if (!is_string($body) || $status >= 400) {
                throw new RuntimeException('Permintaan ke GitHub gagal. HTTP ' . $status . ($error !== '' ? ' - ' . $error : ''));
            }

            return $body;
        }

        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL) && strtolower((string) ini_get('allow_url_fopen')) !== '1') {
            throw new RuntimeException('Server tidak mendukung cURL maupun allow_url_fopen untuk mengambil update dari GitHub.');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::HTTP_TIMEOUT,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $statusLine = is_array($responseHeaders) && isset($responseHeaders[0]) ? (string) $responseHeaders[0] : '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int) ($matches[1] ?? 0);

        if (!is_string($body) || $status >= 400) {
            throw new RuntimeException('Permintaan ke GitHub gagal. HTTP ' . $status);
        }

        return $body;
    }

    private function writeJson(string $path, array $payload): void
    {
        @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function writeReport(string $prefix, array $payload): string
    {
        self::ensureDirectories();
        $fileName = $prefix . '-' . date('Ymd-His') . '.txt';
        $path = self::reportDirectory() . '/' . $fileName;

        $lines = [];
        $lines[] = 'LAPORAN UPDATE APLIKASI BUMDES';
        $lines[] = 'Status: ' . (string) ($payload['status'] ?? '-');
        $lines[] = 'Waktu: ' . (string) ($payload['updated_at'] ?? $payload['checked_at'] ?? $payload['failed_at'] ?? date('Y-m-d H:i:s'));
        $lines[] = 'Repo: ' . $this->repoUrl();
        $lines[] = 'Branch: ' . $this->branch();
        $lines[] = str_repeat('-', 72);

        if (isset($payload['message'])) {
            $lines[] = 'Pesan: ' . (string) $payload['message'];
            $lines[] = '';
        }

        $remote = (array) ($payload['remote'] ?? (($payload['last_check']['remote'] ?? []) ?: []));
        if ($remote !== []) {
            $lines[] = 'Remote version: ' . (string) ($remote['version'] ?? '-');
            $lines[] = 'Remote commit: ' . (string) ($remote['commit_sha'] ?? '-');
            $lines[] = 'Remote commit date: ' . (string) ($remote['commit_date'] ?? '-');
            $lines[] = 'Remote commit message: ' . (string) ($remote['commit_message'] ?? '-');
            $lines[] = '';
        }

        $summary = (array) ($payload['summary'] ?? (($payload['last_check']['summary'] ?? []) ?: []));
        if ($summary !== []) {
            $lines[] = 'Ringkasan perubahan:';
            foreach ($summary as $key => $value) {
                $lines[] = '- ' . $key . ': ' . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }
            $lines[] = '';
        }

        if (isset($payload['database_backup']) && is_array($payload['database_backup'])) {
            $lines[] = 'Backup database otomatis:';
            $lines[] = '- file_name: ' . (string) ($payload['database_backup']['file_name'] ?? '-');
            $lines[] = '- size: ' . format_bytes((int) ($payload['database_backup']['size'] ?? 0));
            $lines[] = '';
        }

        if (isset($payload['file_backup_dir'])) {
            $lines[] = 'Backup file lama: ' . (string) $payload['file_backup_dir'];
            $lines[] = '';
        }

        $files = (array) ($payload['files'] ?? (($payload['last_check']['files'] ?? []) ?: []));
        foreach (['changed' => 'File berubah', 'new' => 'File baru', 'obsolete' => 'File lokal yang tidak ada di GitHub (tidak dihapus otomatis)'] as $key => $label) {
            $items = (array) ($files[$key] ?? []);
            $lines[] = $label . ' (' . count($items) . '):';
            if ($items === []) {
                $lines[] = '- tidak ada';
            } else {
                foreach ($items as $item) {
                    $path = is_array($item) ? (string) ($item['path'] ?? '-') : (string) $item;
                    $lines[] = '- ' . $path;
                }
            }
            $lines[] = '';
        }

        if (isset($payload['applied_files']) && is_array($payload['applied_files'])) {
            $lines[] = 'File yang berhasil diterapkan (' . count($payload['applied_files']) . '):';
            foreach ($payload['applied_files'] as $pathRel) {
                $lines[] = '- ' . (string) $pathRel;
            }
            $lines[] = '';
        }

        if (isset($payload['created_before_error']) && is_array($payload['created_before_error'])) {
            $lines[] = 'File baru yang sempat dibuat sebelum error:';
            foreach ($payload['created_before_error'] as $pathRel) {
                $lines[] = '- ' . (string) $pathRel;
            }
            $lines[] = '';
        }

        @file_put_contents($path, implode("\n", $lines));
        return $path;
    }

    private function cleanupWorkspace(string $workspace): void
    {
        if (!is_dir($workspace)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($workspace, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($workspace);
    }
}
