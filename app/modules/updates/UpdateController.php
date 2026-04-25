<?php

declare(strict_types=1);

final class UpdateController extends Controller
{
    private function service(): GitHubAppUpdaterService
    {
        return new GitHubAppUpdaterService();
    }

    public function index(): void
    {
        try {
            GitHubAppUpdaterService::ensureDirectories();
            BackupService::ensureDirectory();
            $files = BackupService::listFiles();
            $latestBackup = $files[0] ?? null;
            $this->view('updates/views/index', [
                'title' => 'Update Aplikasi',
                'repo_url' => $this->service()->repoUrl(),
                'branch' => $this->service()->branch(),
                'current_version' => $this->service()->currentVersion(),
                'current_manifest' => $this->service()->currentManifest(),
                'pending_migrations' => $this->service()->pendingMigrations(),
                'state' => $this->service()->state(),
                'last_check' => $this->service()->lastCheck(),
                'latest_backup' => $latestBackup,
                'report_files' => GitHubAppUpdaterService::latestReports(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman update aplikasi belum dapat dibuka.', $e);
        }
    }

    public function check(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        try {
            $result = $this->service()->checkForUpdates();
            audit_log('app_update', 'check', 'Memeriksa update aplikasi dari GitHub.', [
                'severity' => 'info',
                'entity_type' => 'app_update',
                'entity_id' => (string) (($result['remote']['commit_short'] ?? '') ?: '-'),
                'after' => [
                    'summary' => $result['summary'] ?? [],
                    'remote' => $result['remote'] ?? [],
                ],
            ]);
            $summary = (array) ($result['summary'] ?? []);
            if ((bool) ($summary['update_available'] ?? false)) {
                flash('success', 'Cek update selesai. Ditemukan ' . (string) ((int) ($summary['changed_count'] ?? 0) + (int) ($summary['new_count'] ?? 0)) . ' file yang perlu diperbarui.');
            } else {
                flash('success', 'Cek update selesai. Aplikasi sudah menggunakan versi terbaru dari GitHub.');
            }
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Cek update gagal. ' . $e->getMessage());
        }

        $this->redirect('/updates');
    }

    public function apply(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        if ((string) post('confirm_backup', '') !== '1') {
            flash('error', 'Sebelum update, centang pernyataan bahwa sistem akan membuat backup database otomatis.');
            $this->redirect('/updates');
        }

        try {
            $result = $this->service()->applyUpdates();
            audit_log('app_update', 'apply', 'Menjalankan update aplikasi dari GitHub.', [
                'severity' => 'warning',
                'entity_type' => 'app_update',
                'entity_id' => (string) (($result['remote']['commit_short'] ?? '') ?: '-'),
                'after' => [
                    'summary' => $result['summary'] ?? [],
                    'database_backup' => $result['database_backup'] ?? [],
                    'report_file' => $result['report_file'] ?? '',
                ],
            ]);
            flash('success', 'Update aplikasi selesai. Backup database dibuat otomatis: ' . (string) (($result['database_backup']['file_name'] ?? 'tidak terdeteksi')) . '. Laporan: ' . (string) ($result['report_file'] ?? '-'));
        } catch (Throwable $e) {
            log_error($e);
            audit_log('app_update', 'apply_failed', 'Update aplikasi gagal dijalankan.', [
                'severity' => 'danger',
                'entity_type' => 'app_update',
                'context' => [
                    'message' => $e->getMessage(),
                ],
            ]);
            flash('error', 'Update aplikasi gagal. ' . $e->getMessage());
        }

        $this->redirect('/updates');
    }

    public function report(): void
    {
        $fileName = (string) get_query('file', '');
        $path = GitHubAppUpdaterService::safeReportPath($fileName);
        if ($path === null) {
            http_response_code(404);
            render_error_page(404, 'File laporan update tidak ditemukan.');
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) ((int) (@filesize($path) ?: 0)));
        readfile($path);
        exit;
    }
}
