<?php

declare(strict_types=1);

final class DashboardModel
{
    private array $schemaCache = [];

    public function __construct(private PDO $db)
    {
    }

    public function supportsBusinessUnits(): bool
    {
        return $this->tableExists('business_units') && $this->columnExists('journal_headers', 'business_unit_id');
    }

    public function getPeriods(): array
    {
        if (!$this->tableExists('accounting_periods')) {
            return [];
        }

        $sql = 'SELECT id, period_code, period_name, start_date, end_date, status, is_active
                FROM accounting_periods
                ORDER BY start_date DESC, id DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPeriodById(int $id): ?array
    {
        if ($id <= 0 || !$this->tableExists('accounting_periods')) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getSummaryMetrics(string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        if (!$this->hasAccountingTables()) {
            return $this->emptySummaryMetrics();
        }

        $supportsUnits = $this->supportsBusinessUnits();
        $sql = 'SELECT
                    COALESCE(SUM(CASE WHEN a.account_type = :asset_type AND h.journal_date <= :asset_date_to THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_assets,
                    COALESCE(SUM(CASE WHEN a.account_type = :revenue_type AND h.journal_date >= :range_from AND h.journal_date <= :range_to THEN (l.credit - l.debit) ELSE 0 END), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN a.account_type = :expense_type AND h.journal_date >= :expense_from AND h.journal_date <= :expense_to THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_expense
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0';
        if ($supportsUnits && $unitId > 0) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_type', 'ASSET', PDO::PARAM_STR);
        $stmt->bindValue(':asset_date_to', $dateTo, PDO::PARAM_STR);
        $stmt->bindValue(':revenue_type', 'REVENUE', PDO::PARAM_STR);
        $stmt->bindValue(':range_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':range_to', $dateTo, PDO::PARAM_STR);
        $stmt->bindValue(':expense_type', 'EXPENSE', PDO::PARAM_STR);
        $stmt->bindValue(':expense_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':expense_to', $dateTo, PDO::PARAM_STR);
        if ($supportsUnits && $unitId > 0) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $journalSql = 'SELECT COUNT(*) FROM journal_headers WHERE journal_date >= :date_from AND journal_date <= :date_to';
        if ($supportsUnits && $unitId > 0) {
            $journalSql .= ' AND business_unit_id = :unit_id';
        }
        $journalStmt = $this->db->prepare($journalSql);
        $journalStmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        $journalStmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        if ($supportsUnits && $unitId > 0) {
            $journalStmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $journalStmt->execute();

        $accountStmt = $this->db->query('SELECT
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS total_active,
                SUM(CASE WHEN is_active = 1 AND is_header = 0 THEN 1 ELSE 0 END) AS total_active_detail
            FROM coa_accounts');
        $accountCounts = $accountStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_active' => 0, 'total_active_detail' => 0];

        $revenue = (float) ($summary['total_revenue'] ?? 0);
        $expense = (float) ($summary['total_expense'] ?? 0);

        return [
            'total_assets' => (float) ($summary['total_assets'] ?? 0),
            'total_revenue' => $revenue,
            'total_expense' => $expense,
            'net_profit' => $revenue - $expense,
            'journal_count' => (int) $journalStmt->fetchColumn(),
            'active_accounts' => (int) ($accountCounts['total_active'] ?? 0),
            'active_detail_accounts' => (int) ($accountCounts['total_active_detail'] ?? 0),
        ];
    }

    public function getCashBankSummary(string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        if (!$this->hasAccountingTables()) {
            return $this->emptyCashSummary();
        }

        $cashAccounts = $this->getDetectedCashAccounts();
        if ($cashAccounts === []) {
            return $this->emptyCashSummary();
        }

        $supportsUnits = $this->supportsBusinessUnits();
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $cashAccounts);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN (l.debit - l.credit) ELSE 0 END), 0) AS cash_balance,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS cash_inflow,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS cash_outflow
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE l.coa_id IN ($placeholders)";
        $params = array_merge([$dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo], $ids);
        if ($supportsUnits && $unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $balanceSql = "SELECT
                           a.id,
                           a.account_code,
                           a.account_name,
                           COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN (l.debit - l.credit) ELSE 0 END), 0) AS balance
                       FROM coa_accounts a
                       LEFT JOIN journal_lines l ON l.coa_id = a.id
                       LEFT JOIN journal_headers h ON h.id = l.journal_id
                       WHERE a.id IN ($placeholders)";
        $balanceParams = array_merge([$dateTo], $ids);
        if ($supportsUnits && $unitId > 0) {
            $balanceSql .= ' AND h.business_unit_id = ?';
            $balanceParams[] = $unitId;
        }
        $balanceSql .= ' GROUP BY a.id, a.account_code, a.account_name ORDER BY a.account_code ASC';
        $balanceStmt = $this->db->prepare($balanceSql);
        $balanceStmt->execute($balanceParams);

        return [
            'detected_accounts' => $balanceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'cash_balance' => (float) ($totals['cash_balance'] ?? 0),
            'cash_inflow' => (float) ($totals['cash_inflow'] ?? 0),
            'cash_outflow' => (float) ($totals['cash_outflow'] ?? 0),
        ];
    }

    public function getMonthlyTrend(string $dateTo, int $months = 6, int $unitId = 0): array
    {
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo) ?: new DateTimeImmutable('today');
        $start = $end->modify('first day of this month')->modify('-' . max($months - 1, 0) . ' months');

        if (!$this->hasAccountingTables()) {
            return $this->emptyTrendSeries($start, $end);
        }

        $supportsUnits = $this->supportsBusinessUnits();
        $sql = 'SELECT
                    DATE_FORMAT(h.journal_date, "%Y-%m") AS month_key,
                    COALESCE(SUM(CASE WHEN a.account_type = "REVENUE" THEN (l.credit - l.debit) ELSE 0 END), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN a.account_type = "EXPENSE" THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_expense,
                    COUNT(DISTINCT h.id) AS journal_count
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                WHERE h.journal_date >= :date_from
                  AND h.journal_date <= :date_to
                  AND a.is_active = 1
                  AND a.is_header = 0';
        if ($supportsUnits && $unitId > 0) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }
        $sql .= ' GROUP BY DATE_FORMAT(h.journal_date, "%Y-%m")
                  ORDER BY month_key ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $start->format('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $end->format('Y-m-d'), PDO::PARAM_STR);
        if ($supportsUnits && $unitId > 0) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['month_key']] = [
                'month_key' => (string) $row['month_key'],
                'total_revenue' => (float) $row['total_revenue'],
                'total_expense' => (float) $row['total_expense'],
                'net_profit' => (float) $row['total_revenue'] - (float) $row['total_expense'],
                'journal_count' => (int) $row['journal_count'],
            ];
        }

        $series = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $series[] = $indexed[$key] ?? [
                'month_key' => $key,
                'total_revenue' => 0.0,
                'total_expense' => 0.0,
                'net_profit' => 0.0,
                'journal_count' => 0,
            ];
            $cursor = $cursor->modify('+1 month');
        }

        return $series;
    }

    public function getRecentJournals(string $dateFrom, string $dateTo, int $limit = 5, int $unitId = 0): array
    {
        if (!$this->tableExists('journal_headers')) {
            return [];
        }

        $supportsUnits = $this->supportsBusinessUnits();
        $sql = 'SELECT h.id, h.journal_no, h.journal_date, h.description, h.total_debit';
        if ($supportsUnits) {
            $sql .= ', bu.unit_code, bu.unit_name';
        } else {
            $sql .= ', NULL AS unit_code, NULL AS unit_name';
        }
        $sql .= ' FROM journal_headers h';
        if ($supportsUnits) {
            $sql .= ' LEFT JOIN business_units bu ON bu.id = h.business_unit_id';
        }
        $sql .= ' WHERE h.journal_date >= :date_from
                  AND h.journal_date <= :date_to';
        if ($supportsUnits && $unitId > 0) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }
        $sql .= ' ORDER BY h.journal_date DESC, h.id DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        if ($supportsUnits && $unitId > 0) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public function getTopAccounts(string $dateFrom, string $dateTo, string $accountType, int $limit = 5, int $unitId = 0): array
    {
        if (!$this->hasAccountingTables() || !$this->tableExists('coa_accounts')) {
            return [];
        }

        $normalizedType = strtoupper($accountType);
        if (!in_array($normalizedType, ['REVENUE', 'EXPENSE'], true)) {
            return [];
        }

        $supportsUnits = $this->supportsBusinessUnits();
        $amountExpression = $normalizedType === 'REVENUE'
            ? '(l.credit - l.debit)'
            : '(l.debit - l.credit)';

        $sql = 'SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(' . $amountExpression . '), 0) AS total_amount,
                    COUNT(DISTINCT h.id) AS journal_count
                FROM coa_accounts a
                INNER JOIN journal_lines l ON l.coa_id = a.id
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE h.journal_date >= :date_from
                  AND h.journal_date <= :date_to
                  AND a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type = :account_type';
        if ($supportsUnits && $unitId > 0) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }
        $sql .= ' GROUP BY a.id, a.account_code, a.account_name
                  HAVING ABS(total_amount) > 0.009
                  ORDER BY total_amount DESC, a.account_code ASC
                  LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        $stmt->bindValue(':account_type', $normalizedType, PDO::PARAM_STR);
        if ($supportsUnits && $unitId > 0) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUnitSummaries(string $dateFrom, string $dateTo): array
    {
        if (!$this->supportsBusinessUnits()) {
            return [];
        }

        $sql = 'SELECT bu.id, bu.unit_code, bu.unit_name,
                       COUNT(DISTINCT h.id) AS journal_count,
                       COALESCE(SUM(CASE WHEN a.account_type = "REVENUE" THEN (l.credit - l.debit) ELSE 0 END), 0) AS total_revenue,
                       COALESCE(SUM(CASE WHEN a.account_type = "EXPENSE" THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_expense
                FROM business_units bu
                LEFT JOIN journal_headers h ON h.business_unit_id = bu.id AND h.journal_date >= :date_from AND h.journal_date <= :date_to
                LEFT JOIN journal_lines l ON l.journal_id = h.id
                LEFT JOIN coa_accounts a ON a.id = l.coa_id AND a.is_active = 1 AND a.is_header = 0
                WHERE bu.is_active = 1
                GROUP BY bu.id, bu.unit_code, bu.unit_name
                ORDER BY bu.unit_code ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getDetectedCashAccounts(): array
    {
        if (!$this->tableExists('coa_accounts')) {
            return [];
        }

        $select = 'id, account_code, account_name, account_type, account_category';
        if ($this->columnExists('coa_accounts', 'auxiliary_type')) {
            $select .= ', auxiliary_type';
        }
        if ($this->columnExists('coa_accounts', 'is_cash')) {
            $select .= ', is_cash';
        }
        if ($this->columnExists('coa_accounts', 'is_bank')) {
            $select .= ', is_bank';
        }

        $sql = 'SELECT ' . $select . '
                FROM coa_accounts
                WHERE is_active = 1
                  AND is_header = 0
                  AND account_type = :account_type';

        if ($this->columnExists('coa_accounts', 'is_cash') || $this->columnExists('coa_accounts', 'is_bank')) {
            $parts = [];
            if ($this->columnExists('coa_accounts', 'is_cash')) {
                $parts[] = 'is_cash = 1';
            }
            if ($this->columnExists('coa_accounts', 'is_bank')) {
                $parts[] = 'is_bank = 1';
            }
            $sql .= ' AND ((' . implode(' OR ', $parts) . ') OR (account_category = :account_category AND (LOWER(account_name) LIKE :cash_keyword OR LOWER(account_name) LIKE :bank_keyword)))';
        } else {
            $sql .= ' AND account_category = :account_category
                      AND (LOWER(account_name) LIKE :cash_keyword OR LOWER(account_name) LIKE :bank_keyword)';
        }

        $sql .= ' ORDER BY account_code ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':account_type', 'ASSET', PDO::PARAM_STR);
        $stmt->bindValue(':account_category', 'CURRENT_ASSET', PDO::PARAM_STR);
        $stmt->bindValue(':cash_keyword', '%kas%', PDO::PARAM_STR);
        $stmt->bindValue(':bank_keyword', '%bank%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasAccountingTables(): bool
    {
        return $this->tableExists('coa_accounts')
            && $this->tableExists('journal_headers')
            && $this->tableExists('journal_lines');
    }

    private function emptySummaryMetrics(): array
    {
        return [
            'total_assets' => 0.0,
            'total_revenue' => 0.0,
            'total_expense' => 0.0,
            'net_profit' => 0.0,
            'journal_count' => 0,
            'active_accounts' => 0,
            'active_detail_accounts' => 0,
        ];
    }

    private function emptyCashSummary(): array
    {
        return [
            'detected_accounts' => [],
            'cash_balance' => 0.0,
            'cash_inflow' => 0.0,
            'cash_outflow' => 0.0,
        ];
    }

    private function emptyTrendSeries(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $series = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $series[] = [
                'month_key' => $cursor->format('Y-m'),
                'total_revenue' => 0.0,
                'total_expense' => 0.0,
                'net_profit' => 0.0,
                'journal_count' => 0,
            ];
            $cursor = $cursor->modify('+1 month');
        }

        return $series;
    }

    private function tableExists(string $tableName): bool
    {
        $cacheKey = 'table:' . $tableName;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        $sql = 'SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        $exists = (int) $stmt->fetchColumn() > 0;
        $this->schemaCache[$cacheKey] = $exists;
        return $exists;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $cacheKey = 'column:' . $tableName . ':' . $columnName;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        if (!$this->tableExists($tableName)) {
            $this->schemaCache[$cacheKey] = false;
            return false;
        }

        $sql = 'SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                  AND COLUMN_NAME = :column_name';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->bindValue(':column_name', $columnName, PDO::PARAM_STR);
        $stmt->execute();
        $exists = (int) $stmt->fetchColumn() > 0;
        $this->schemaCache[$cacheKey] = $exists;
        return $exists;
    }
}
