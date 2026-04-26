<?php

declare(strict_types=1);

final class ReportDrilldownController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance(db_config());
        $period = current_accounting_period();
        $filters = [
            'period_id' => (int) get_query('period_id', (int) ($period['id'] ?? 0)),
            'account_id' => (int) get_query('account_id', 0),
            'unit_id' => (int) get_query('unit_id', 0),
            'date_from' => trim((string) get_query('date_from', (string) ($period['start_date'] ?? ''))),
            'date_to' => trim((string) get_query('date_to', (string) ($period['end_date'] ?? ''))),
            'source_report' => trim((string) get_query('source_report', 'laporan')),
        ];

        [$sql, $params] = $this->buildQuery($filters, $this->columnExists($db, 'journal_headers', 'workflow_status'));
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $this->view('reports/views/drilldown', [
            'title' => 'Drill-down Jurnal Sumber',
            'filters' => $filters,
            'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'periods' => $this->periodOptions($db),
            'accounts' => $this->accountOptions($db),
            'unitOptions' => business_unit_options(),
        ]);
    }

    private function buildQuery(array $filters, bool $hasWorkflowStatus): array
    {
        $workflowSelect = $hasWorkflowStatus ? "COALESCE(j.workflow_status, 'POSTED') AS workflow_status" : "'POSTED' AS workflow_status";
        $sql = "SELECT j.id AS journal_id,
                       j.journal_no,
                       j.journal_date,
                       j.description,
                       {$workflowSelect},
                       l.line_description,
                       l.debit,
                       l.credit,
                       a.account_code,
                       a.account_name,
                       p.period_name,
                       bu.unit_code,
                       bu.unit_name
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                INNER JOIN accounting_periods p ON p.id = j.period_id
                LEFT JOIN business_units bu ON bu.id = j.business_unit_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['period_id'])) {
            $sql .= ' AND j.period_id = :period_id';
            $params[':period_id'] = (int) $filters['period_id'];
        }
        if (!empty($filters['account_id'])) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = (int) $filters['account_id'];
        }
        if (!empty($filters['unit_id'])) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = (int) $filters['unit_id'];
        }
        if ((string) ($filters['date_from'] ?? '') !== '') {
            $sql .= ' AND j.journal_date >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }
        if ((string) ($filters['date_to'] ?? '') !== '') {
            $sql .= ' AND j.journal_date <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'];
        }

        $sql .= ' ORDER BY j.journal_date DESC, j.id DESC, l.line_no ASC LIMIT 500';
        return [$sql, $params];
    }

    private function periodOptions(PDO $db): array
    {
        return $db->query('SELECT id, period_code, period_name FROM accounting_periods ORDER BY start_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function accountOptions(PDO $db): array
    {
        return $db->query('SELECT id, account_code, account_name FROM coa_accounts WHERE is_active = 1 AND is_header = 0 ORDER BY account_code ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function columnExists(PDO $db, string $table, string $column): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
