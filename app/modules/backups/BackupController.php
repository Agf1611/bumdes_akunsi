<?php

declare(strict_types=1);

final class BackupController extends Controller
{
    private function service(): BackupService
    {
        return new BackupService(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            BackupService::ensureDirectory();
            $files = BackupService::listFiles();
            $summary = BackupService::summarizeFiles($files);
            $this->view('backups/views/index', [
                'title' => 'Backup Database',
                'files' => $files,
                'summary' => $summary + [
                    'db_connected' => Database::isConnected(db_config()),
                    'directory' => BackupService::directory(),
                    'directory_writable' => is_dir(BackupService::directory()) && is_writable(BackupService::directory()),
                ],
                'restoreAnalysis' => Session::get('backup_restore_analysis'),
                'restorePayload' => Session::get('backup_restore_payload'),
                'recoveryReadiness' => $this->service()->buildRecoveryReadiness(),
                'dataAudit' => $this->service()->collectDataAuditSummary(),
                'recoveryState' => BackupService::readRecoveryState(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman backup database belum dapat dibuka.', $e);
        }
    }

    public function create(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        try {
            if (!Database::isConnected(db_config())) {
                throw new RuntimeException('Koneksi database belum tersedia. Backup tidak dapat dibuat.');
            }

            $profile = app_profile();
            $result = $this->service()->createBackup([
                'app_name' => (string) app_config('name'),
                'bumdes_name' => (string) ($profile['bumdes_name'] ?? ''),
                'actor_name' => (string) (Auth::user()['full_name'] ?? ''),
                'reason' => trim((string) post('backup_reason', 'manual_backup')),
            ]);

            audit_log('backup_database', 'create', 'Membuat backup database manual.', [
                'severity' => 'info',
                'entity_type' => 'backup_file',
                'entity_id' => (string) ($result['file_name'] ?? ''),
                'after' => $result,
            ]);

            $message = 'Backup database berhasil dibuat: ' . (string) ($result['file_name'] ?? '-');
            if ((int) ($result['retention_deleted'] ?? 0) > 0) {
                $message .= ' Retention otomatis merapikan ' . (string) ($result['retention_deleted'] ?? 0) . ' file lama.';
            }
            flash('success', $message);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Backup database gagal dibuat. ' . $e->getMessage());
        }

        $this->redirect($this->sanitizeRedirect((string) post('redirect_to', '/backups')));
    }

    public function download(): void
    {
        $fileName = (string) get_query('file', '');
        $path = BackupService::filePath($fileName);
        if ($path === null) {
            http_response_code(404);
            render_error_page(404, 'File backup tidak ditemukan.');
            return;
        }

        audit_log('backup_database', 'download', 'Mengunduh file backup database.', [
            'severity' => 'info',
            'entity_type' => 'backup_file',
            'entity_id' => basename($path),
            'context' => [
                'file_name' => basename($path),
                'size' => (int) (@filesize($path) ?: 0),
            ],
        ]);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) ((int) (@filesize($path) ?: 0)));
        readfile($path);
        exit;
    }

    public function restore(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        try {
            if (!Database::isConnected(db_config())) {
                throw new RuntimeException('Koneksi database belum tersedia. Restore tidak dapat dijalankan.');
            }

            $action = trim((string) post('restore_action', 'analyze'));
            if ($action === 'analyze') {
                [$path, $payload] = $this->resolveRestoreSourceForAnalysis();
                $analysis = $this->service()->inspectSqlFile($path);
                $analysis['display_name'] = (string) ($payload['file_name'] ?? ($analysis['file_name'] ?? ''));
                Session::put('backup_restore_analysis', $analysis);
                Session::put('backup_restore_payload', $payload);
                flash('success', 'Analisa restore selesai. Periksa ringkasan file lalu konfirmasi restore jika sudah yakin.');
                $this->redirect('/backups');
            }

            if ((string) post('confirm_restore', '') !== '1') {
                throw new RuntimeException('Konfirmasi restore wajib dicentang sebelum proses dijalankan.');
            }

            [$path, $payload] = $this->resolveRestoreSourceForExecution();
            $analysis = $this->service()->inspectSqlFile($path);

            $profile = app_profile();
            $preRestoreBackup = $this->service()->createBackup([
                'app_name' => (string) app_config('name'),
                'bumdes_name' => (string) ($profile['bumdes_name'] ?? ''),
                'reason' => 'pre_restore_safety_backup',
            ]);

            MaintenanceMode::enable([
                'reason' => 'restore_database',
                'started_at' => date('c'),
                'source_file' => (string) ($analysis['file_name'] ?? ''),
            ]);

            try {
                $result = $this->service()->restoreFromFile($path);
            } catch (Throwable $restoreError) {
                try {
                    $rollback = $this->service()->restoreFromFile((string) ($preRestoreBackup['file_path'] ?? ''));
                } catch (Throwable $rollbackError) {
                    throw new RuntimeException(
                        'Restore utama gagal dan rollback otomatis dari backup pengaman juga gagal. Restore error: '
                        . $restoreError->getMessage()
                        . ' | Rollback error: '
                        . $rollbackError->getMessage(),
                        0,
                        $rollbackError
                    );
                }

                throw new RuntimeException(
                    'Restore utama gagal, tetapi database telah dicoba dipulihkan ulang dari backup pengaman otomatis '
                    . (string) ($preRestoreBackup['file_name'] ?? '-')
                    . ' (' . (string) ($rollback['executed_statements'] ?? 0) . ' statement). Detail awal: '
                    . $restoreError->getMessage(),
                    0,
                    $restoreError
                );
            } finally {
                MaintenanceMode::disable();
                if (($payload['restore_mode'] ?? '') === 'upload') {
                    BackupService::deleteStagedFile((string) ($payload['staged_name'] ?? ''));
                }
            }

            $result['restore_mode'] = (string) ($payload['restore_mode'] ?? 'server');
            $result['pre_restore_backup'] = $preRestoreBackup;
            $sourceModifiedAt = '';
            if (($payload['restore_mode'] ?? '') === 'server' && !empty($payload['file_name'])) {
                $sourcePath = BackupService::filePath((string) $payload['file_name']);
                if ($sourcePath !== null) {
                    $sourceModifiedAt = date('Y-m-d H:i:s', (int) (@filemtime($sourcePath) ?: time()));
                }
            }
            $recoveryRecord = $this->service()->recordRecoveryEvent([
                'restored_at' => date('c'),
                'source_file_name' => (string) ($result['file_name'] ?? ''),
                'source_file_sha1' => (string) ($result['sha1'] ?? ''),
                'source_file_size' => (int) ($result['size'] ?? 0),
                'source_file_modified_at' => $sourceModifiedAt,
                'source_backup_age_days' => $sourceModifiedAt !== '' ? round(max(0, time() - strtotime($sourceModifiedAt)) / 86400, 2) : null,
                'restore_mode' => (string) ($payload['restore_mode'] ?? 'server'),
                'pre_restore_backup' => $preRestoreBackup,
                'restored_by' => [
                    'id' => (int) (Auth::user()['id'] ?? 0),
                    'username' => (string) (Auth::user()['username'] ?? ''),
                    'full_name' => (string) (Auth::user()['full_name'] ?? ''),
                ],
            ]);
            Session::forget('backup_restore_analysis');
            Session::forget('backup_restore_payload');

            audit_log('backup_database', 'restore', 'Melakukan restore database dari file backup.', [
                'severity' => 'warning',
                'entity_type' => 'backup_file',
                'entity_id' => (string) ($result['file_name'] ?? ''),
                'after' => $result + ['recovery_record' => $recoveryRecord],
            ]);

            flash('success', 'Restore database berhasil dijalankan dari file: ' . (string) ($result['file_name'] ?? '-') . '. Statement dieksekusi: ' . (string) ($result['executed_statements'] ?? 0) . '. Backup pengaman dibuat: ' . (string) (($preRestoreBackup['file_name'] ?? '-')) . '. Metadata recovery tersimpan: ' . (string) ($recoveryRecord['report_file'] ?? '-'));
        } catch (Throwable $e) {
            MaintenanceMode::disable();
            log_error($e);
            flash('error', 'Restore database gagal. ' . $e->getMessage());
        }

        $this->redirect('/backups');
    }

    private function resolveRestoreSourceForAnalysis(): array
    {
        $mode = trim((string) post('restore_mode', 'server'));
        if ($mode === 'upload') {
            $file = $_FILES['restore_file'] ?? null;
            if (!is_array($file)) {
                throw new RuntimeException('File restore belum dipilih.');
            }

            $staged = BackupService::stageUploadedRestoreFile($file);
            return [
                (string) $staged['path'],
                [
                    'restore_mode' => 'upload',
                    'staged_name' => (string) $staged['staged_name'],
                    'file_name' => (string) $staged['original_name'],
                ],
            ];
        }

        $fileName = (string) post('file', '');
        $path = BackupService::filePath($fileName);
        if ($path === null) {
            throw new RuntimeException('File backup server tidak ditemukan atau nama file tidak valid.');
        }

        return [
            $path,
            [
                'restore_mode' => 'server',
                'file_name' => basename($path),
            ],
        ];
    }

    private function resolveRestoreSourceForExecution(): array
    {
        $mode = trim((string) post('restore_mode', 'server'));
        if ($mode === 'upload') {
            $stagedName = trim((string) post('staged_name', ''));
            $path = BackupService::stagedFilePath($stagedName);
            if ($path === null) {
                throw new RuntimeException('File staging restore tidak ditemukan. Ulangi analisa file upload terlebih dahulu.');
            }

            return [
                $path,
                [
                    'restore_mode' => 'upload',
                    'staged_name' => $stagedName,
                    'file_name' => basename($path),
                ],
            ];
        }

        $fileName = (string) post('file', '');
        $path = BackupService::filePath($fileName);
        if ($path === null) {
            throw new RuntimeException('File backup server tidak ditemukan atau nama file tidak valid.');
        }

        return [
            $path,
            [
                'restore_mode' => 'server',
                'file_name' => basename($path),
            ],
        ];
    }

    public function delete(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $fileName = (string) post('file', '');
        $path = BackupService::filePath($fileName);
        if ($path === null) {
            flash('error', 'File backup tidak ditemukan atau nama file tidak valid.');
            $this->redirect('/backups');
        }

        $context = [
            'file_name' => basename($path),
            'size' => (int) (@filesize($path) ?: 0),
        ];

        if (!@unlink($path)) {
            flash('error', 'File backup gagal dihapus dari server.');
            $this->redirect('/backups');
        }

        audit_log('backup_database', 'delete', 'Menghapus file backup database dari server.', [
            'severity' => 'warning',
            'entity_type' => 'backup_file',
            'entity_id' => (string) $context['file_name'],
            'before' => $context,
        ]);

        flash('success', 'File backup berhasil dihapus: ' . (string) $context['file_name']);
        $this->redirect('/backups');
    }

    private function sanitizeRedirect(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/backups';
        }
        foreach (['/backups', '/dashboard', '/settings/health'] as $allowedPrefix) {
            if (str_starts_with($path, $allowedPrefix)) {
                return $path;
            }
        }

        return '/backups';
    }
}
