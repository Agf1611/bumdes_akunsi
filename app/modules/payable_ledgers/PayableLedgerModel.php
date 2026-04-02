<?php

declare(strict_types=1);

final class PayableLedgerModel
{
    private ?array $statusCache = null;

    public function __construct(private PDO $db)
    {
    }

    public function getFeatureStatus(): array
    {
        if ($this->statusCache !== null) {
            return $this->statusCache;
        }

        $this->statusCache = [
            'journal_headers_table' => $this->tableExists('journal_headers'),
            'journal_lines_table' => $this->tableExists('journal_lines'),
            'coa_accounts_table' => $this->tableExists('coa_accounts'),
            'partners_table' => $this->tableExists('reference_partners'),
            'business_units_table' => $this->tableExists('business_units'),
            'partner_id_column' => $this->columnExists('journal_lines', 'partner_id'),
            'line_description_column' => $this->columnExists('journal_lines', 'line_description'),
            'business_unit_column' => $this->columnExists('journal_headers', 'business_unit_id'),
            'account_type_column' => $this->columnExists('coa_accounts', 'account_type'),
            'account_category_column' => $this->columnExists('coa_accounts', 'account_category'),
            'account_code_column' => $this->columnExists('coa_accounts', 'account_code'),
            'partner_code_column' => $this->columnExists('reference_partners', 'partner_code'),
            'partner_name_column' => $this->columnExists('reference_partners', 'partner_name'),
            'partner_type_column' => $this->columnExists('reference_partners', 'partner_type'),
            'partner_active_column' => $this->columnExists('reference_partners', 'is_active'),
            'unit_code_column' => $this->columnExists('business_units', 'unit_code'),
            'unit_name_column' => $this->columnExists('business_units', 'unit_name'),
        ];

        return $this->statusCache;
    }

    public function hasRequiredTables(): bool
    {
        $status = $this->getFeatureStatus();
        return $status['journal_headers_table'] && $status['journal_lines_table'] && $status['coa_accounts_table'];
    }

    public function getPartnerOptions(): array
    {
        $status = $this->getFeatureStatus();
        if (!$status['partners_table']) {
            return [];
        }

        $code = $status['partner_code_column'] ? 'partner_code' : 'NULL AS partner_code';
        $name = $status['partner_name_column'] ? 'partner_name' : 'CAST(id AS CHAR) AS partner_name';
        $type = $status['partner_type_column'] ? 'partner_type' : '"" AS partner_type';
        $sql = "SELECT id, {$code}, {$name}, {$type} FROM reference_partners";
        if ($status['partner_active_column']) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY ' . ($status['partner_name_column'] ? 'partner_name ASC, id ASC' : 'id ASC');
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAccountOptions(): array
    {
        $status = $this->getFeatureStatus();
        $code = $status['account_code_column'] ? 'a.account_code' : 'CAST(a.id AS CHAR)';
        $sql = "SELECT a.id, {$code} AS account_code, a.account_name FROM coa_accounts a WHERE a.is_active = 1 AND a.is_header = 0 AND " . $this->payableAccountPredicate('a', $status) . " ORDER BY {$code} ASC, a.id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPeriods(): array
    {
        if (!$this->tableExists('accounting_periods')) {
            return [];
        }
        $stmt = $this->db->query('SELECT id, period_code, period_name, start_date, end_date, status, is_active FROM accounting_periods ORDER BY start_date DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAccountById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM coa_accounts WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPartnerById(int $id): ?array
    {
        $status = $this->getFeatureStatus();
        if (!$status['partners_table']) {
            return null;
        }
        $code = $status['partner_code_column'] ? 'partner_code' : 'NULL AS partner_code';
        $name = $status['partner_name_column'] ? 'partner_name' : 'CAST(id AS CHAR) AS partner_name';
        $type = $status['partner_type_column'] ? 'partner_type' : '"" AS partner_type';
        $stmt = $this->db->prepare("SELECT id, {$code}, {$name}, {$type} FROM reference_partners WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPeriodById(int $id): ?array
    {
        if (!$this->tableExists('accounting_periods')) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT id, period_code, period_name, start_date, end_date, status, is_active FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getOpeningBalance(?int $partnerId, ?int $accountId, ?string $dateFrom, int $unitId = 0): float
    {
        if (!$this->hasRequiredTables()) {
            return 0.0;
        }

        $status = $this->getFeatureStatus();
        $sql = "SELECT COALESCE(SUM(l.credit - l.debit), 0) AS opening_balance
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                WHERE " . $this->payableAccountPredicate('a', $status);
        $params = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND j.journal_date < :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = $accountId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':account_id', ':unit_id']);
        $stmt->execute();
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    public function getMovementSummary(?int $partnerId = null, ?int $accountId = null, ?string $dateFrom = null, ?string $dateTo = null, int $unitId = 0): array
    {
        if (!$this->hasRequiredTables()) {
            return ['debit_total' => 0.0, 'credit_total' => 0.0, 'journal_count' => 0, 'partner_count' => 0, 'last_transaction_date' => null];
        }

        $status = $this->getFeatureStatus();
        $partnerCountExpr = $status['partner_id_column'] ? 'COUNT(DISTINCT CASE WHEN l.partner_id IS NOT NULL AND l.partner_id > 0 THEN l.partner_id END)' : '0';
        $sql = "SELECT COALESCE(SUM(l.debit), 0) AS debit_total,
                       COALESCE(SUM(l.credit), 0) AS credit_total,
                       COUNT(DISTINCT j.id) AS journal_count,
                       {$partnerCountExpr} AS partner_count,
                       MAX(j.journal_date) AS last_transaction_date
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                WHERE " . $this->payableAccountPredicate('a', $status);
        $params = [];

        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = $accountId;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND j.journal_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND j.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':account_id', ':unit_id']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'debit_total' => (float) ($row['debit_total'] ?? 0),
            'credit_total' => (float) ($row['credit_total'] ?? 0),
            'journal_count' => (int) ($row['journal_count'] ?? 0),
            'partner_count' => (int) ($row['partner_count'] ?? 0),
            'last_transaction_date' => $row['last_transaction_date'] ?? null,
        ];
    }

    public function getAgingBuckets(?int $partnerId = null, ?int $accountId = null, ?string $asOfDate = null, int $unitId = 0): array
    {
        if (!$this->hasRequiredTables()) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $asOfDate = ($asOfDate !== null && $asOfDate !== '') ? $asOfDate : date('Y-m-d');
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, j.journal_date) <= 30 THEN (l.credit - l.debit) ELSE 0 END), 0) AS bucket_current,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, j.journal_date) BETWEEN 31 AND 60 THEN (l.credit - l.debit) ELSE 0 END), 0) AS bucket_31_60,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, j.journal_date) BETWEEN 61 AND 90 THEN (l.credit - l.debit) ELSE 0 END), 0) AS bucket_61_90,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, j.journal_date) >= 91 THEN (l.credit - l.debit) ELSE 0 END), 0) AS bucket_91_plus
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                WHERE j.journal_date <= ?
                  AND " . $this->payableAccountPredicate('a', $status);
        $params = [$asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate];

        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = ?';
            $params[] = $partnerId;
        }
        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = ?';
            $params[] = $accountId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = ?';
            $params[] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            ['key' => 'current', 'label' => '0-30 hari', 'amount' => (float) ($row['bucket_current'] ?? 0)],
            ['key' => '31_60', 'label' => '31-60 hari', 'amount' => (float) ($row['bucket_31_60'] ?? 0)],
            ['key' => '61_90', 'label' => '61-90 hari', 'amount' => (float) ($row['bucket_61_90'] ?? 0)],
            ['key' => '91_plus', 'label' => '> 90 hari', 'amount' => (float) ($row['bucket_91_plus'] ?? 0)],
        ];
    }

    public function getTopPartners(?int $accountId = null, ?string $asOfDate = null, int $unitId = 0, int $limit = 7): array
    {
        if (!$this->hasRequiredTables()) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $asOfDate = ($asOfDate !== null && $asOfDate !== '') ? $asOfDate : date('Y-m-d');
        $partnerIdExpr = $status['partner_id_column'] ? 'COALESCE(l.partner_id, 0)' : '0';
        $partnerCodeExpr = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_code_column']) ? 'COALESCE(p.partner_code, "")' : '""';
        $partnerNameExpr = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_name_column']) ? 'COALESCE(p.partner_name, "Tanpa Mitra")' : '"Tanpa Mitra"';

        $sql = "SELECT {$partnerIdExpr} AS partner_id,
                       {$partnerCodeExpr} AS partner_code,
                       {$partnerNameExpr} AS partner_name,
                       COUNT(DISTINCT j.id) AS journal_count,
                       COALESCE(SUM(l.debit), 0) AS debit_total,
                       COALESCE(SUM(l.credit), 0) AS credit_total,
                       COALESCE(SUM(l.credit - l.debit), 0) AS balance
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id";
        if ($status['partners_table'] && $status['partner_id_column']) {
            $sql .= ' LEFT JOIN reference_partners p ON p.id = l.partner_id';
        }
        $sql .= " WHERE j.journal_date <= :as_of_date
                  AND " . $this->payableAccountPredicate('a', $status);
        $params = [':as_of_date' => $asOfDate];

        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = $accountId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $sql .= " GROUP BY {$partnerIdExpr}, {$partnerCodeExpr}, {$partnerNameExpr}
                  HAVING ABS(COALESCE(SUM(l.credit - l.debit), 0)) > 0.00001
                  ORDER BY balance DESC, partner_name ASC
                  LIMIT " . max(1, $limit);

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':account_id', ':unit_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public function getControlClosingBalance(?int $partnerId = null, ?int $accountId = null, ?string $dateTo = null, int $unitId = 0): float
    {
        if (!$this->hasRequiredTables()) {
            return 0.0;
        }

        $status = $this->getFeatureStatus();
        $sql = "SELECT COALESCE(SUM(l.credit - l.debit), 0) AS closing_balance
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id
                WHERE " . $this->payableAccountPredicate('a', $status);
        $params = [];

        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = $accountId;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND j.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':account_id', ':unit_id']);
        $stmt->execute();
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    public function getMutations(?int $partnerId, ?int $accountId, ?string $dateFrom, ?string $dateTo, int $unitId = 0): array
    {
        if (!$this->hasRequiredTables()) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $lineDescription = $status['line_description_column'] ? 'l.line_description' : '"" AS line_description';
        $partnerCode = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_code_column']) ? 'COALESCE(p.partner_code, "")' : '"" AS partner_code';
        $partnerName = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_name_column']) ? 'COALESCE(p.partner_name, "Tanpa Mitra")' : '"Tanpa Mitra" AS partner_name';
        $unitCode = ($status['business_units_table'] && $status['business_unit_column'] && $status['unit_code_column']) ? 'COALESCE(bu.unit_code, "")' : '"" AS unit_code';
        $unitName = ($status['business_units_table'] && $status['business_unit_column'] && $status['unit_name_column']) ? 'COALESCE(bu.unit_name, "")' : '"" AS unit_name';
        $accountCode = $status['account_code_column'] ? 'a.account_code' : 'CAST(a.id AS CHAR) AS account_code';

        $sql = "SELECT l.id, l.journal_id, l.line_no, {$lineDescription}, l.debit, l.credit,
                       " . ($status['partner_id_column'] ? 'l.partner_id' : '0 AS partner_id') . ",
                       j.journal_no, j.journal_date, j.description AS journal_description,
                       {$partnerCode}, {$partnerName}, {$unitCode}, {$unitName}, {$accountCode}, a.account_name
                FROM journal_lines l
                INNER JOIN journal_headers j ON j.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id";
        if ($status['partners_table'] && $status['partner_id_column']) {
            $sql .= ' LEFT JOIN reference_partners p ON p.id = l.partner_id';
        }
        if ($status['business_units_table'] && $status['business_unit_column']) {
            $sql .= ' LEFT JOIN business_units bu ON bu.id = j.business_unit_id';
        }
        $sql .= ' WHERE ' . $this->payableAccountPredicate('a', $status);
        $params = [];

        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($accountId !== null && $accountId > 0) {
            $sql .= ' AND l.coa_id = :account_id';
            $params[':account_id'] = $accountId;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND j.journal_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND j.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $sql .= ' ORDER BY j.journal_date ASC, j.id ASC, l.line_no ASC';
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':account_id', ':unit_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function payableAccountPredicate(string $alias, array $status): string
    {
        $parts = ["LOWER({$alias}.account_name) LIKE '%utang%'"];
        if ($status['account_type_column']) {
            $parts[] = "{$alias}.account_type = 'LIABILITY'";
        }
        if ($status['account_category_column']) {
            $parts[] = "{$alias}.account_category IN ('CURRENT_LIABILITY', 'LONG_TERM_LIABILITY', 'PAYABLE')";
        }
        return '(' . implode(' OR ', array_unique($parts)) . ')';
    }

    private function bindParams(PDOStatement $stmt, array $params, array $intKeys = []): void
    {
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, in_array($name, $intKeys, true) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1');
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->bindValue(':column', $column, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }
}
