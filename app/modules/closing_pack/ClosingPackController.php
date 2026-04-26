<?php

declare(strict_types=1);

final class ClosingPackController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::getInstance(db_config());
            $period = current_accounting_period();
            if (!$period) {
                throw new RuntimeException('Periode aktif belum tersedia.');
            }

            $periodModel = new PeriodModel($db);
            $checklist = $periodModel->buildClosingChecklist((int) $period['id']);
            $latestBackup = BackupService::listFiles()[0] ?? null;
            $query = report_filters_query([
                'period_id' => (int) $period['id'],
                'date_from' => (string) ($period['start_date'] ?? ''),
                'date_to' => (string) ($period['end_date'] ?? ''),
            ]);

            $this->view('closing_pack/views/index', [
                'title' => 'Paket Tutup Bulan',
                'period' => $period,
                'checklist' => $checklist,
                'latestBackup' => $latestBackup,
                'reportQuery' => $query,
                'profile' => app_profile(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Paket tutup bulan belum dapat dibuka.', $e);
        }
    }
}
