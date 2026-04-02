<?php

declare(strict_types=1);

final class ReceivableLedgerModel
{
    private ?array $statusCache = null;
    /** @var int[]|null */
    private ?array $receivableAccountIdsCache = null;

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
            'entry_tag_column' => $this->columnExists('journal_lines', 'entry_tag'),
            'line_description_column' => $this->columnExists('journal_lines', 'line_description'),
            'business_unit_column' => $this->columnExists('journal_headers', 'business_unit_id'),
            'account_category_column' => $this->columnExists('coa_accounts', 'account_category'),
            'account_type_column' => $this->columnExists('coa_accounts', 'account_type'),
            'account_code_column' => $this->columnExists('coa_accounts', 'account_code'),
            'partner_code_column' => $this->columnExists('reference_partners', 'partner_code'),
            'partner_name_column' => $this->columnExists('reference_partners', 'partner_name'),
            'partner_type_column' => $this->columnExists('reference_partners', 'partner_type'),
            'partner_active_column' => $this->columnExists('reference_partners', 'is_active'),
            'unit_code_column' => $this->columnExists('business_units', 'unit_code'),
            'unit_name_column' => $this->columnExists('business_units', 'unit_name'),
            'auxiliary_type_column' => $this->columnExists('coa_accounts', 'auxiliary_type'),
            'is_control_account_column' => $this->columnExists('coa_accounts', 'is_control_account'),
        ];

        return $this->statusCache;
    }

    public function isReady(): bool
    {
        $status = $this->getFeatureStatus();
        return $status['journal_headers_table'] && $status['journal_lines_table'] && $status['coa_accounts_table'];
    }

    public function getPeriods(): array
    {
        if (!$this->tableExists('accounting_periods')) {
            return [];
        }
        $sql = 'SELECT id, period_code, period_name, start_date, end_date, status, is_active
                FROM accounting_periods ORDER BY start_date DESC, id DESC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPeriodById(int $id): ?array
    {
        if (!$this->tableExists('accounting_periods')) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
        $clauses = [];
        if ($status['partner_active_column']) {
            $clauses[] = 'is_active = 1';
        }
        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $orderBy = $status['partner_name_column'] ? 'partner_name ASC, id ASC' : 'id ASC';
        $sql .= ' ORDER BY ' . $orderBy;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

    public function getReceivableAccountOptions(): array
    {
        $ids = $this->getReceivableAccountIds();
        if ($ids === []) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $selectCode = $status['account_code_column'] ? 'account_code' : 'CAST(id AS CHAR)';
        $sql = 'SELECT id, ' . $selectCode . ' AS account_code, account_name'
            . ($status['account_type_column'] ? ', account_type' : ', "" AS account_type')
            . ($status['account_category_column'] ? ', account_category' : ', "" AS account_category')
            . ' FROM coa_accounts WHERE id IN (' . implode(',', $ids) . ') ORDER BY ' . $selectCode . ' ASC, id ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAccountById(int $id): ?array
    {
        $ids = $this->getReceivableAccountIds();
        if ($ids === [] || !in_array($id, $ids, true)) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM coa_accounts WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getOpeningBalance(?int $partnerId = null, ?string $dateFrom = null, int $unitId = 0, int $accountId = 0): float
    {
        if ($dateFrom === null || $dateFrom === '' || !$this->isReady()) {
            return 0.0;
        }

        $ids = $this->resolveRequestedReceivableIds($accountId);
        if ($ids === []) {
            return 0.0;
        }

        $status = $this->getFeatureStatus();
        $sql = 'SELECT COALESCE(SUM(l.debit), 0) AS total_debit, COALESCE(SUM(l.credit), 0) AS total_credit
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE h.journal_date < :date_from
                  AND l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [':date_from' => $dateFrom];
        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':unit_id']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_debit' => 0, 'total_credit' => 0];
        return ((float) $row['total_debit']) - ((float) $row['total_credit']);
    }

    public function getMovementSummary(?int $partnerId = null, ?int $accountId = null, ?string $dateFrom = null, ?string $dateTo = null, int $unitId = 0): array
    {
        if (!$this->isReady()) {
            return [
                'debit_total' => 0.0,
                'credit_total' => 0.0,
                'journal_count' => 0,
                'partner_count' => 0,
                'last_transaction_date' => null,
            ];
        }

        $ids = $this->resolveRequestedReceivableIds((int) ($accountId ?? 0));
        if ($ids === []) {
            return [
                'debit_total' => 0.0,
                'credit_total' => 0.0,
                'journal_count' => 0,
                'partner_count' => 0,
                'last_transaction_date' => null,
            ];
        }

        $status = $this->getFeatureStatus();
        $partnerCountExpr = $status['partner_id_column']
            ? 'COUNT(DISTINCT CASE WHEN l.partner_id IS NOT NULL AND l.partner_id > 0 THEN l.partner_id END)'
            : '0';
        $sql = 'SELECT COALESCE(SUM(l.debit), 0) AS debit_total,
                       COALESCE(SUM(l.credit), 0) AS credit_total,
                       COUNT(DISTINCT h.id) AS journal_count,
                       ' . $partnerCountExpr . ' AS partner_count,
                       MAX(h.journal_date) AS last_transaction_date
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [];
        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND h.journal_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND h.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':unit_id']);
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
        if (!$this->isReady()) {
            return [];
        }

        $ids = $this->resolveRequestedReceivableIds((int) ($accountId ?? 0));
        if ($ids === []) {
            return [
                ['key' => 'current', 'label' => '0-30 hari', 'amount' => 0.0],
                ['key' => '31_60', 'label' => '31-60 hari', 'amount' => 0.0],
                ['key' => '61_90', 'label' => '61-90 hari', 'amount' => 0.0],
                ['key' => '91_plus', 'label' => '> 90 hari', 'amount' => 0.0],
            ];
        }

        $status = $this->getFeatureStatus();
        $asOfDate = ($asOfDate !== null && $asOfDate !== '') ? $asOfDate : date('Y-m-d');
        $sql = 'SELECT
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, h.journal_date) <= 30 THEN (l.debit - l.credit) ELSE 0 END), 0) AS bucket_current,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, h.journal_date) BETWEEN 31 AND 60 THEN (l.debit - l.credit) ELSE 0 END), 0) AS bucket_31_60,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, h.journal_date) BETWEEN 61 AND 90 THEN (l.debit - l.credit) ELSE 0 END), 0) AS bucket_61_90,
                    COALESCE(SUM(CASE WHEN DATEDIFF(?, h.journal_date) >= 91 THEN (l.debit - l.credit) ELSE 0 END), 0) AS bucket_91_plus
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE h.journal_date <= ?
                  AND l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [$asOfDate, $asOfDate, $asOfDate, $asOfDate, $asOfDate];

        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = ?';
            $params[] = $partnerId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = ?';
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
        if (!$this->isReady()) {
            return [];
        }

        $ids = $this->resolveRequestedReceivableIds((int) ($accountId ?? 0));
        if ($ids === []) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $asOfDate = ($asOfDate !== null && $asOfDate !== '') ? $asOfDate : date('Y-m-d');

        $partnerIdExpr = $status['partner_id_column'] ? 'COALESCE(l.partner_id, 0)' : '0';
        $partnerCodeExpr = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_code_column']) ? 'COALESCE(rp.partner_code, "")' : '""';
        $partnerNameExpr = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_name_column']) ? 'COALESCE(rp.partner_name, "Tanpa Mitra")' : '"Tanpa Mitra"';

        $sql = 'SELECT ' . $partnerIdExpr . ' AS partner_id,
                       ' . $partnerCodeExpr . ' AS partner_code,
                       ' . $partnerNameExpr . ' AS partner_name,
                       COUNT(DISTINCT h.id) AS journal_count,
                       COALESCE(SUM(l.debit), 0) AS debit_total,
                       COALESCE(SUM(l.credit), 0) AS credit_total,
                       COALESCE(SUM(l.debit - l.credit), 0) AS balance
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id';
        if ($status['partners_table'] && $status['partner_id_column']) {
            $sql .= ' LEFT JOIN reference_partners rp ON rp.id = l.partner_id';
        }
        $sql .= ' WHERE h.journal_date <= :as_of_date
                  AND l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [':as_of_date' => $asOfDate];

        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $sql .= ' GROUP BY ' . $partnerIdExpr . ', ' . $partnerCodeExpr . ', ' . $partnerNameExpr . '
                  HAVING ABS(COALESCE(SUM(l.debit - l.credit), 0)) > 0.00001
                  ORDER BY balance DESC, partner_name ASC
                  LIMIT ' . max(1, $limit);

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':unit_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getMutations(?int $partnerId = null, ?string $dateFrom = null, ?string $dateTo = null, int $unitId = 0, int $accountId = 0): array
    {
        if (!$this->isReady()) {
            return [];
        }

        $ids = $this->resolveRequestedReceivableIds($accountId);
        if ($ids === []) {
            return [];
        }

        $status = $this->getFeatureStatus();
        $unitSelect = $status['business_unit_column'] ? 'h.business_unit_id' : '0 AS business_unit_id';
        $lineDescription = $status['line_description_column'] ? 'l.line_description' : '"" AS line_description';
        $entryTag = $status['entry_tag_column'] ? 'l.entry_tag' : '"" AS entry_tag';
        $accountCode = $status['account_code_column'] ? 'a.account_code' : 'CAST(a.id AS CHAR) AS account_code';
        $partnerCode = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_code_column']) ? 'COALESCE(rp.partner_code, "")' : '"" AS partner_code';
        $partnerName = ($status['partners_table'] && $status['partner_id_column'] && $status['partner_name_column']) ? 'COALESCE(rp.partner_name, "Tanpa Mitra")' : '"Tanpa Mitra" AS partner_name';
        $unitCode = ($status['business_units_table'] && $status['business_unit_column'] && $status['unit_code_column']) ? 'COALESCE(bu.unit_code, "")' : '"" AS unit_code';
        $unitName = ($status['business_units_table'] && $status['business_unit_column'] && $status['unit_name_column']) ? 'COALESCE(bu.unit_name, "")' : '"" AS unit_name';

        $sql = 'SELECT h.id AS journal_id,
                       h.journal_date,
                       h.journal_no,
                       h.description AS journal_description,
                       ' . $unitSelect . ',
                       ' . $unitCode . ',
                       ' . $unitName . ',
                       l.id AS journal_line_id,
                       l.line_no,
                       ' . $lineDescription . ',
                       l.debit,
                       l.credit,
                       ' . $entryTag . ',
                       ' . $accountCode . ',
                       a.account_name,
                       ' . $partnerCode . ',
                       ' . $partnerName . '
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                INNER JOIN coa_accounts a ON a.id = l.coa_id';
        if ($status['partners_table'] && $status['partner_id_column']) {
            $sql .= ' LEFT JOIN reference_partners rp ON rp.id = l.partner_id';
        }
        if ($status['business_units_table'] && $status['business_unit_column']) {
            $sql .= ' LEFT JOIN business_units bu ON bu.id = h.business_unit_id';
        }
        $sql .= ' WHERE l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [];
        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND h.journal_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND h.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $sql .= ' ORDER BY h.journal_date ASC, h.journal_no ASC, l.line_no ASC, l.id ASC';
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':unit_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getControlClosingBalance(?int $partnerId = null, ?int $accountId = null, ?string $dateTo = null, int $unitId = 0): float
    {
        if (!$this->isReady()) {
            return 0.0;
        }

        $ids = $this->resolveRequestedReceivableIds((int) ($accountId ?? 0));
        if ($ids === []) {
            return 0.0;
        }

        $status = $this->getFeatureStatus();
        $sql = 'SELECT COALESCE(SUM(l.debit), 0) - COALESCE(SUM(l.credit), 0) AS balance
                FROM journal_lines l
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE l.coa_id IN (' . implode(',', $ids) . ')';
        $params = [];
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND h.journal_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        if ($partnerId !== null && $partnerId > 0 && $status['partner_id_column']) {
            $sql .= ' AND l.partner_id = :partner_id';
            $params[':partner_id'] = $partnerId;
        }
        if ($unitId > 0 && $status['business_unit_column']) {
            $sql .= ' AND h.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params, [':partner_id', ':unit_id']);
        $stmt->execute();
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    /** @return int[] */
    private function getReceivableAccountIds(): array
    {
        if ($this->receivableAccountIdsCache !== null) {
            return $this->receivableAccountIdsCache;
        }

        $status = $this->getFeatureStatus();
        if (!$status['coa_accounts_table']) {
            $this->receivableAccountIdsCache = [];
            return [];
        }

        $excludeKeywords = [
            'kas', 'bank', 'persediaan', 'uang muka', 'dibayar dimuka', 'dibayar di muka',
            'modem', 'router', 'access point', 'instalasi', 'kabel', 'tiang', 'aset', 'inventaris',
            'peralatan', 'akumulasi', 'penyusutan', 'beban', 'pendapatan', 'laba ditahan', 'modal',
        ];

        if ($status['auxiliary_type_column']) {
            $sql = 'SELECT id FROM coa_accounts WHERE is_active = 1 AND is_header = 0 AND auxiliary_type = "RECEIVABLE" ORDER BY id ASC';
            $ids = array_map('intval', $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $this->receivableAccountIdsCache = $ids;
            return $ids;
        }

        $conditions = [
            'is_active = 1',
            'is_header = 0',
            "LOWER(account_name) LIKE '%piutang%'",
        ];
        if ($status['account_type_column']) {
            $conditions[] = "account_type = 'ASSET'";
        }
        if ($status['account_category_column']) {
            $conditions[] = "account_category IN ('CURRENT_ASSET', 'RECEIVABLE')";
        }
        foreach ($excludeKeywords as $keyword) {
            $conditions[] = "LOWER(account_name) NOT LIKE " . $this->db->quote('%' . $keyword . '%');
        }

        $sql = 'SELECT id FROM coa_accounts WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id ASC';
        $ids = array_map('intval', $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: []);
        $this->receivableAccountIdsCache = $ids;
        return $ids;
    }

    /** @return int[] */
    private function resolveRequestedReceivableIds(int $accountId = 0): array
    {
        $ids = $this->getReceivableAccountIds();
        if ($accountId > 0) {
            return in_array($accountId, $ids, true) ? [$accountId] : [];
        }
        return $ids;
    }

    private function bindParams(PDOStatement $stmt, array $params, array $intKeys = []): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, in_array($key, $intKeys, true) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
