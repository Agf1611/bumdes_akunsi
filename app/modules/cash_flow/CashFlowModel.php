<?php

declare(strict_types=1);

final class CashFlowModel
{
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
        $sql = "SELECT COALESCE(SUM(CASE WHEN l.coa_id IN ($placeholders) THEN (l.debit - l.credit) ELSE 0 END), 0) AS opening_cash
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                WHERE h.journal_date < ?";
        $params = array_map('intval', $cashAccountIds);
        $params[] = $dateFrom;
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
                    bu.unit_code,
                    bu.unit_name
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                INNER JOIN coa_accounts c ON c.id = l.coa_id
                LEFT JOIN business_units bu ON bu.id = h.business_unit_id
                WHERE h.journal_date >= ?
                  AND h.journal_date <= ?";

        $params = [];
        for ($i = 0; $i < 8; $i++) {
            $params = array_merge($params, array_map('intval', $cashAccountIds));
        }
        $params[] = $dateFrom;
        $params[] = $dateTo;
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
}
