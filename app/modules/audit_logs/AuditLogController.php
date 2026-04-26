<?php

declare(strict_types=1);

final class AuditLogController extends Controller
{
    private function model(): AuditLogModel
    {
        return new AuditLogModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            if (!audit_logs_table_exists()) {
                flash('error', 'Tabel audit trail belum tersedia. Import file database/patch_audit_logs.sql terlebih dahulu.');
                $this->redirect('/dashboard');
            }

            $filters = [
                'module_name' => trim((string) get_query('module_name', '')),
                'action_name' => trim((string) get_query('action_name', '')),
                'severity_level' => trim((string) get_query('severity_level', '')),
                'username' => trim((string) get_query('username', '')),
                'date_from' => trim((string) get_query('date_from', '')),
                'date_to' => trim((string) get_query('date_to', '')),
            ];

            $rows = $this->model()->getList($filters);
            foreach ($rows as &$row) {
                $row['before_payload'] = audit_decode_json((string) ($row['before_data'] ?? ''));
                $row['after_payload'] = audit_decode_json((string) ($row['after_data'] ?? ''));
                $row['context_payload'] = audit_decode_json((string) ($row['context_data'] ?? ''));
            }
            unset($row);

            $this->view('audit_logs/views/index', [
                'title' => 'Audit Trail',
                'rows' => $rows,
                'filters' => $filters,
                'moduleOptions' => $this->model()->getModuleOptions(),
                'actionOptions' => $this->model()->getActionOptions(),
                'summary' => $this->model()->getSummary($filters),
                'maxRows' => $this->model()->maxRows(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman audit trail belum dapat dibuka. Pastikan patch audit log sudah diimpor dengan benar.', $e);
        }
    }
}
