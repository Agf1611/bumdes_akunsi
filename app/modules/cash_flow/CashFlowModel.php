<?php

declare(strict_types=1);

final class CashFlowModel
{
    private ?bool $entryTagColumnExists = null;

    public function __construct(private PDO $db)
    {
    }

    public function getPeriods(): array
    {
        $stmt = $this->db->query('SELECT id, period_code, period_name, start_date, end_date, status, is_active FROM accounting_periods ORDER BY start_date DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPeriodById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getDetectedCashAccounts(): array
    {
        $sql = 'SELECT id, account_code, account_name, account_type, account_category
                FROM coa_accounts
                WHERE is_active = 1
                  AND is_header = 0
                  AND account_type = :account_type
                  AND account_category = :account_category
                  AND (LOWER(account_name) LIKE :cash_keyword OR LOWER(account_name) LIKE :bank_keyword)
                ORDER BY account_code ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':account_type', 'ASSET', PDO::PARAM_STR);
        $stmt->bindValue(':account_category', 'CURRENT_ASSET', PDO::PARAM_STR);
        $stmt->bindValue(':cash_keyword', '%kas%', PDO::PARAM_STR);
        $stmt->bindValue(':bank_keyword', '%bank%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOpeningCashBalance(array $cashAccountIds, string $dateFrom, int $unitId = 0): float
    {
        if ($cashAccountIds === []) {
            return 0.0;
        }

        $placeholders = implode(', ', array_fill(0, count($cashAccountIds), '?'));
        $openingSignalSql = $this->openingSignalExistsSql();
        $sql = "SELECT COALESCE(SUM(CASE WHEN l.coa_id IN ($placeholders) THEN (l.debit - l.credit) ELSE 0 END), 0) AS opening_cash
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                WHERE (
                    h.journal_date < ?
                    OR (h.journal_date = ? AND $openingSignalSql)
                )";
        $params = array_map('intval', $cashAccountIds);
        $params[] = $dateFrom;
        $params[] = $dateFrom;
        $params = array_merge($params, $this->openingSignalParams());
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    public function getJournalRows(array $cashAccountIds, string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        if ($cashAccountIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($cashAccountIds), '?'));
        $hasCashflowReference = $this->tableExists('cashflow_components')
            && $this->columnExists('journal_lines', 'cashflow_component_id');
        $cashflowJoin = '';
        $cashflowSelect = [
            'NULL AS explicit_cashflow_codes',
            'NULL AS explicit_cashflow_names',
            'NULL AS explicit_cashflow_groups',
            'NULL AS explicit_cashflow_directions',
        ];
        if ($hasCashflowReference) {
            $groupExpr = $this->cashflowGroupExpression();
            $directionExpr = $this->cashflowDirectionExpression();
            $cashflowJoin = 'LEFT JOIN cashflow_components cfc ON cfc.id = l.cashflow_component_id';
            $cashflowSelect = [
                "GROUP_CONCAT(DISTINCT cfc.component_code ORDER BY cfc.component_code SEPARATOR ' | ') AS explicit_cashflow_codes",
                "GROUP_CONCAT(DISTINCT cfc.component_name ORDER BY cfc.component_code SEPARATOR ' | ') AS explicit_cashflow_names",
                "GROUP_CONCAT(DISTINCT $groupExpr ORDER BY cfc.component_code SEPARATOR ' | ') AS explicit_cashflow_groups",
                "GROUP_CONCAT(DISTINCT $directionExpr ORDER BY cfc.component_code SEPARATOR ' | ') AS explicit_cashflow_directions",
            ];
        }

        $sql = "SELECT
                    h.id AS journal_id,
                    h.journal_date,
                    h.journal_no,
                    h.description,
                    COALESCE(SUM(CASE WHEN l.coa_id IN ($placeholders) THEN l.debit ELSE 0 END), 0) AS cash_debit,
                    COALESCE(SUM(CASE WHEN l.coa_id IN ($placeholders) THEN l.credit ELSE 0 END), 0) AS cash_credit,
                    MAX(CASE WHEN l.coa_id NOT IN ($placeholders) AND (
                            c.account_category IN ('FIXED_ASSET', 'OTHER_ASSET')
                            OR LOWER(c.account_name) LIKE '%modem%'
                            OR LOWER(c.account_name) LIKE '%router%'
                            OR LOWER(c.account_name) LIKE '%access point%'
                            OR LOWER(c.account_name) LIKE '%instalasi%'
                            OR LOWER(c.account_name) LIKE '%jaringan%'
                            OR LOWER(c.account_name) LIKE '%kabel%'
                            OR LOWER(c.account_name) LIKE '%tiang%'
                            OR LOWER(c.account_name) LIKE '%peralatan%'
                            OR LOWER(c.account_name) LIKE '%inventaris%'
                        ) THEN 1 ELSE 0 END) AS has_investing,
                    MAX(CASE WHEN l.coa_id NOT IN ($placeholders) AND (c.account_type = 'EQUITY' OR c.account_category = 'LONG_TERM_LIABILITY') THEN 1 ELSE 0 END) AS has_financing,
                    MAX(CASE WHEN l.coa_id NOT IN ($placeholders) AND (c.account_type IN ('REVENUE', 'EXPENSE') OR c.account_category IN ('CURRENT_ASSET', 'CURRENT_LIABILITY', 'OTHER_LIABILITY')) THEN 1 ELSE 0 END) AS has_operating,
                    GROUP_CONCAT(DISTINCT CASE WHEN l.coa_id NOT IN ($placeholders) THEN CONCAT(c.account_code, ' - ', c.account_name) END ORDER BY c.account_code SEPARATOR ' | ') AS counterpart_accounts,
                    GROUP_CONCAT(DISTINCT CASE WHEN l.coa_id NOT IN ($placeholders) THEN c.account_type END ORDER BY c.account_type SEPARATOR ' | ') AS counterpart_types,
                    GROUP_CONCAT(DISTINCT CASE WHEN l.coa_id NOT IN ($placeholders) THEN c.account_category END ORDER BY c.account_category SEPARATOR ' | ') AS counterpart_categories,
                    " . implode(",\n                    ", $cashflowSelect) . ",
                    bu.unit_code,
                    bu.unit_name
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                INNER JOIN coa_accounts c ON c.id = l.coa_id
                $cashflowJoin
                LEFT JOIN business_units bu ON bu.id = h.business_unit_id
                WHERE h.journal_date >= ?
                  AND h.journal_date <= ?";

        $params = [];
        for ($i = 0; $i < 8; $i++) {
            $params = array_merge($params, array_map('intval', $cashAccountIds));
        }
        $params[] = $dateFrom;
        $params[] = $dateTo;
        $sql .= ' AND NOT (' . $this->openingSignalExistsSql() . ')';
        $params = array_merge($params, $this->openingSignalParams());
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= ' GROUP BY h.id, h.journal_date, h.journal_no, h.description, bu.unit_code, bu.unit_name
                  HAVING cash_debit <> 0 OR cash_credit <> 0
                  ORDER BY h.journal_date ASC, h.journal_no ASC, h.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function cashflowGroupExpression(): string
    {
        if ($this->columnExists('cashflow_components', 'cashflow_group')) {
            return 'cfc.cashflow_group';
        }

        if ($this->columnExists('cashflow_components', 'component_group')) {
            return "CASE
                        WHEN cfc.component_group LIKE 'OPERATING%' THEN 'OPERATING'
                        WHEN cfc.component_group LIKE 'INVESTING%' THEN 'INVESTING'
                        WHEN cfc.component_group LIKE 'FINANCING%' THEN 'FINANCING'
                        ELSE 'OPERATING'
                    END";
        }

        return "'OPERATING'";
    }

    private function cashflowDirectionExpression(): string
    {
        if ($this->columnExists('cashflow_components', 'direction')) {
            return 'cfc.direction';
        }

        if ($this->columnExists('cashflow_components', 'component_group')) {
            return "CASE
                        WHEN cfc.component_group LIKE '%_OUT' THEN 'OUT'
                        ELSE 'IN'
                    END";
        }

        return "'IN'";
    }

    private function openingSignalExistsSql(): string
    {
        $lineSignalSql = "LOWER(opening_l.line_description) LIKE ? OR opening_l.line_description LIKE ?";
        if ($this->hasJournalEntryTagColumn()) {
            $lineSignalSql = "opening_l.entry_tag = 'SALDO_AWAL' OR " . $lineSignalSql;
        }

        return "(LOWER(h.description) LIKE ? OR h.description LIKE ? OR EXISTS (
                    SELECT 1
                    FROM journal_lines opening_l
                    WHERE opening_l.journal_id = h.id
                      AND ($lineSignalSql)
                    LIMIT 1
                ))";
    }

    private function openingSignalParams(): array
    {
        return ['%saldo awal%', '%SALDO_AWAL%', '%saldo awal%', '%SALDO_AWAL%'];
    }

    private function hasJournalEntryTagColumn(): bool
    {
        if ($this->entryTagColumnExists !== null) {
            return $this->entryTagColumnExists;
        }

        $this->entryTagColumnExists = $this->columnExists('journal_lines', 'entry_tag');
        return $this->entryTagColumnExists;
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                LIMIT 1');
            $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                  AND COLUMN_NAME = :column_name
                LIMIT 1');
            $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
            $stmt->bindValue(':column_name', $column, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
