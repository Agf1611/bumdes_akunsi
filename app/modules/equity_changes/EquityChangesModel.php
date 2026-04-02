<?php

declare(strict_types=1);

final class EquityChangesModel
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

    public function getEquityRows(string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(CASE WHEN h.journal_date < ? THEN l.debit ELSE 0 END), 0) AS opening_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date < ? THEN l.credit ELSE 0 END), 0) AS opening_credit,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS movement_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS movement_credit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS closing_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS closing_credit
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type = 'EQUITY'";

        $params = [$dateFrom, $dateFrom, $dateFrom, $dateTo, $dateFrom, $dateTo, $dateTo, $dateTo];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= ' GROUP BY a.id, a.account_code, a.account_name
                  HAVING opening_debit <> opening_credit OR movement_debit <> movement_credit OR closing_debit <> closing_credit
                  ORDER BY a.account_code ASC, a.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getNetIncome(string $dateFrom, string $dateTo, int $unitId = 0): float
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE' THEN (l.credit - l.debit) ELSE 0 END), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN a.account_type = 'EXPENSE' THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_expense
                FROM coa_accounts a
                INNER JOIN journal_lines l ON l.coa_id = a.id
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type IN ('REVENUE', 'EXPENSE')
                  AND h.journal_date >= ?
                  AND h.journal_date <= ?";
        $params = [$dateFrom, $dateTo];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_revenue' => 0, 'total_expense' => 0];

        return ((float) $row['total_revenue']) - ((float) $row['total_expense']);
    }
}
