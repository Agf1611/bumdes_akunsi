<?php

declare(strict_types=1);

final class LedgerModel
{
    private array $tableCache = [];
    private array $columnCache = [];

    public function __construct(private PDO $db)
    {
    }

    public function getSchemaStatus(): array
    {
        $status = [
            'business_units_table' => $this->tableExists('business_units'),
            'business_unit_column' => $this->columnExists('journal_headers', 'business_unit_id'),
        ];
        $status['unit_filter_ready'] = $status['business_units_table'] && $status['business_unit_column'];
        return $status;
    }

    public function getAccountOptions(): array
    {
        $sql = 'SELECT id, account_code, account_name, account_type
                FROM coa_accounts
                WHERE is_active = 1 AND is_header = 0
                ORDER BY account_code ASC, id ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAccountById(int $id): ?array
    {
        $sql = 'SELECT id, account_code, account_name, account_type, account_category, is_header, is_active
                FROM coa_accounts WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPeriods(): array
    {
        $sql = 'SELECT id, period_code, period_name, start_date, end_date, status, is_active
                FROM accounting_periods ORDER BY start_date DESC, id DESC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPeriodById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getOpeningBalance(int $accountId, ?string $dateFrom, int $unitId = 0): float
    {
        if ($dateFrom === null || $dateFrom === '') {
            return 0.0;
        }

        $schema = $this->getSchemaStatus();
        $unitFilterReady = $schema['unit_filter_ready'] === true;

        $sql = 'SELECT COALESCE(SUM(l.debit), 0) AS total_debit, COALESCE(SUM(l.credit), 0) AS total_credit
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE l.coa_id = :account_id AND h.journal_date < :date_from';
        if ($unitId > 0 && $unitFilterReady) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        if ($unitId > 0 && $unitFilterReady) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_debit' => 0, 'total_credit' => 0];
        return ((float) $row['total_debit']) - ((float) $row['total_credit']);
    }

    public function getMutations(int $accountId, ?string $dateFrom = null, ?string $dateTo = null, int $unitId = 0): array
    {
        $schema = $this->getSchemaStatus();
        $unitFilterReady = $schema['unit_filter_ready'] === true;

        $selectUnit = $unitFilterReady
            ? 'bu.unit_code, bu.unit_name'
            : "'' AS unit_code, '' AS unit_name";
        $joinUnit = $unitFilterReady
            ? ' LEFT JOIN business_units bu ON bu.id = h.business_unit_id'
            : '';

        $sql = 'SELECT h.id AS journal_id, h.journal_date, h.journal_no, h.description AS journal_description,
                       l.line_description, l.debit, l.credit, l.line_no,
                       ' . $selectUnit . '
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id' . $joinUnit . '
                WHERE l.coa_id = :account_id';
        $params = [':account_id' => $accountId];
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND h.journal_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND h.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $unitFilterReady) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $sql .= ' ORDER BY h.journal_date ASC, h.journal_no ASC, l.line_no ASC, l.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':account_id' || $key === ':unit_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
            $stmt->bindValue(':table', $table, PDO::PARAM_STR);
            $stmt->execute();
            $exists = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $exists = false;
        }

        $this->tableCache[$table] = $exists;
        return $exists;
    }

    private function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column');
            $stmt->bindValue(':table', $table, PDO::PARAM_STR);
            $stmt->bindValue(':column', $column, PDO::PARAM_STR);
            $stmt->execute();
            $exists = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $exists = false;
        }

        $this->columnCache[$key] = $exists;
        return $exists;
    }
}
