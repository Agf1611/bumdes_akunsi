<?php

declare(strict_types=1);

final class HealthController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::getInstance(db_config());
            $runner = new MigrationRunner($db);
            $backupService = new BackupService($db);
            $localMigrations = array_map('basename', $runner->discoverMigrations());
            $pendingMigrations = array_map('basename', $runner->pendingMigrations());
            $appliedMigrations = $runner->appliedMigrationNames();
            $manifest = $this->readReleaseManifest();
            $manifestMigrations = array_map('strval', (array) ($manifest['schema_migrations'] ?? []));
            $missingFromManifest = array_values(array_diff($localMigrations, $manifestMigrations));
            $backupFiles = BackupService::listFiles();
            $backupSummary = BackupService::summarizeFiles($backupFiles);
            $latestBackup = $backupSummary['latest_file'] ?? null;
            $backupStale = BackupService::backupStaleStatus($latestBackup);
            $directories = $this->directoryChecks();
            $recoveryState = BackupService::readRecoveryState();
            $recoveryReadiness = $backupService->buildRecoveryReadiness();
            $dataAudit = $backupService->collectDataAuditSummary();

            $checks = [
                [
                    'label' => 'Koneksi database',
                    'status' => Database::isConnected(db_config()) ? 'ok' : 'critical',
                    'message' => Database::isConnected(db_config()) ? 'Database dapat diakses.' : 'Database tidak dapat diakses.',
                ],
                [
                    'label' => 'Migration pending',
                    'status' => $pendingMigrations === [] ? 'ok' : 'warning',
                    'message' => $pendingMigrations === [] ? 'Semua migration lokal sudah tercatat sebagai applied.' : count($pendingMigrations) . ' migration belum dijalankan.',
                ],
                [
                    'label' => 'Manifest rilis',
                    'status' => $missingFromManifest === [] ? 'ok' : 'critical',
                    'message' => $missingFromManifest === [] ? 'Semua migration lokal sudah masuk release manifest.' : count($missingFromManifest) . ' migration belum tercantum di manifest.',
                ],
                [
                    'label' => 'Backup terakhir',
                    'status' => $backupStale['level'] === 'critical' ? 'critical' : ($backupStale['level'] === 'warning' ? 'warning' : 'ok'),
                    'message' => $latestBackup !== null ? 'Backup terakhir: ' . (string) $latestBackup['modified_at'] . ' (' . (string) ($latestBackup['age_label'] ?? '-') . '). ' . $backupStale['note'] : 'Belum ada file backup database.',
                ],
                [
                    'label' => 'Jumlah backup tersedia',
                    'status' => ((int) ($backupSummary['count'] ?? 0)) <= 1 ? 'warning' : 'ok',
                    'message' => 'Saat ini tersedia ' . number_format((int) ($backupSummary['count'] ?? 0), 0, ',', '.') . ' file backup. Backup lokal tunggal belum cukup aman untuk recovery.',
                ],
            ];

            if ($recoveryState !== []) {
                $ageDays = (float) ($recoveryState['source_backup_age_days'] ?? 0);
                $checks[] = [
                    'label' => 'Sumber restore terakhir',
                    'status' => $ageDays >= 3 ? 'warning' : 'ok',
                    'message' => 'Restore terakhir memakai file ' . (string) ($recoveryState['source_file_name'] ?? '-') . ' dengan usia backup sekitar ' . number_format($ageDays, 2, ',', '.') . ' hari saat dipulihkan.',
                ];
            }

            foreach ($directories as $directory) {
                $checks[] = [
                    'label' => 'Folder writable: ' . $directory['label'],
                    'status' => $directory['writable'] ? 'ok' : 'critical',
                    'message' => $directory['writable'] ? $directory['path'] : 'Tidak writable: ' . $directory['path'],
                ];
            }

            $this->view('settings/views/health', [
                'title' => 'Health Check Aplikasi',
                'checks' => $checks,
                'manifest' => $manifest,
                'localMigrations' => $localMigrations,
                'appliedMigrations' => $appliedMigrations,
                'pendingMigrations' => $pendingMigrations,
                'missingFromManifest' => $missingFromManifest,
                'latestBackup' => $latestBackup,
                'directories' => $directories,
                'backupSummary' => $backupSummary,
                'backupStale' => $backupStale,
                'recoveryState' => $recoveryState,
                'recoveryReadiness' => $recoveryReadiness,
                'dataAudit' => $dataAudit,
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Health check belum dapat dibuka.', $e);
        }
    }

    private function readReleaseManifest(): array
    {
        $path = ROOT_PATH . '/release-manifest.json';
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function directoryChecks(): array
    {
        $paths = [
            'storage' => ROOT_PATH . '/storage',
            'backup' => BackupService::directory(),
            'uploads' => ROOT_PATH . '/public/uploads',
            'update workspace' => ROOT_PATH . '/storage/update-workspace',
        ];

        $checks = [];
        foreach ($paths as $label => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $checks[] = [
                'label' => $label,
                'path' => $path,
                'writable' => is_dir($path) && is_writable($path),
            ];
        }

        return $checks;
    }
}
