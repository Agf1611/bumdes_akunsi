<?php

declare(strict_types=1);

final class TrialBalanceModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getPeriods(): array
    {
        $sql = 'SELECT id, period_code, period_name, start_date, end_date, status, is_active FROM accounting_periods ORDER BY start_date DESC, id DESC';
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

    public function getRows(string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.account_category,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS period_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS period_credit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS closing_total_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS closing_total_credit
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1 AND a.is_header = 0";

        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateTo, $dateTo];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= ' GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                  HAVING period_debit <> 0 OR period_credit <> 0 OR closing_total_debit <> closing_total_credit
                  ORDER BY a.account_code ASC, a.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
