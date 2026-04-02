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
            $this->view('backups/views/index', [
                'title' => 'Backup Database',
                'files' => $files,
                'summary' => [
                    'count' => count($files),
                    'total_size' => array_sum(array_map(static fn (array $file): int => (int) ($file['size'] ?? 0), $files)),
                    'latest' => $files[0]['modified_at'] ?? '',
                    'db_connected' => Database::isConnected(db_config()),
                    'directory' => BackupService::directory(),
                    'directory_writable' => is_dir(BackupService::directory()) && is_writable(BackupService::directory()),
                ],
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
            ]);

            audit_log('backup_database', 'create', 'Membuat backup database manual.', [
                'severity' => 'info',
                'entity_type' => 'backup_file',
                'entity_id' => (string) ($result['file_name'] ?? ''),
                'after' => $result,
            ]);

            flash('success', 'Backup database berhasil dibuat: ' . (string) ($result['file_name'] ?? '-'));
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Backup database gagal dibuat. ' . $e->getMessage());
        }

        $this->redirect('/backups');
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

            $mode = trim((string) post('restore_mode', 'server'));
            if ($mode === 'upload') {
                $file = $_FILES['restore_file'] ?? null;
                if (!is_array($file)) {
                    throw new RuntimeException('File restore belum dipilih.');
                }
                BackupService::validateUpload($file);
                $result = $this->service()->restoreFromFile((string) $file['tmp_name']);
                $result['restore_mode'] = 'upload';
            } else {
                $fileName = (string) post('file', '');
                $path = BackupService::filePath($fileName);
                if ($path === null) {
                    throw new RuntimeException('File backup server tidak ditemukan atau nama file tidak valid.');
                }
                $result = $this->service()->restoreFromFile($path);
                $result['restore_mode'] = 'server';
            }

            audit_log('backup_database', 'restore', 'Melakukan restore database dari file backup.', [
                'severity' => 'warning',
                'entity_type' => 'backup_file',
                'entity_id' => (string) ($result['file_name'] ?? ''),
                'after' => $result,
            ]);

            flash('success', 'Restore database berhasil dijalankan dari file: ' . (string) ($result['file_name'] ?? '-') . '. Statement dieksekusi: ' . (string) ($result['executed_statements'] ?? 0));
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Restore database gagal. ' . $e->getMessage());
        }

        $this->redirect('/backups');
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
}
