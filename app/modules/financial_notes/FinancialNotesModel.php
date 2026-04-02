<?php

declare(strict_types=1);

final class FinancialNotesModel
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

    public function getRowsByType(string $dateTo, string $accountType, int $unitId = 0): array
    {
        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.account_category,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS closing_total_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS closing_total_credit
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type = ?";

        $params = [$dateTo, $dateTo, $accountType];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= ' GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                  ORDER BY a.account_code ASC, a.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['amount'] = $this->balanceAmount((string) $row['account_type'], (float) $row['closing_total_debit'], (float) $row['closing_total_credit']);
        }
        unset($row);

        return $rows;
    }

    public function getNamedAssetRows(string $dateTo, array $keywords, int $unitId = 0): array
    {
        $rows = $this->getRowsByType($dateTo, 'ASSET', $unitId);
        $filtered = [];
        foreach ($rows as $row) {
            $haystack = mb_strtolower((string) ($row['account_name'] ?? ''));
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    $filtered[] = $row;
                    break;
                }
            }
        }

        return $filtered;
    }

    public function getProfitLossRows(string $dateFrom, string $dateTo, string $accountType, int $unitId = 0): array
    {
        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.account_category,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.debit ELSE 0 END), 0) AS period_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date >= ? AND h.journal_date <= ? THEN l.credit ELSE 0 END), 0) AS period_credit
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type = ?";

        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo, $accountType];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= ' GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                  HAVING period_debit <> 0 OR period_credit <> 0
                  ORDER BY a.account_code ASC, a.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['amount'] = $accountType === 'REVENUE'
                ? ((float) $row['period_credit'] - (float) $row['period_debit'])
                : ((float) $row['period_debit'] - (float) $row['period_credit']);
        }
        unset($row);

        return $rows;
    }

    public function getNetIncome(string $dateFrom, string $dateTo, int $unitId = 0): float
    {
        $revenues = $this->getProfitLossRows($dateFrom, $dateTo, 'REVENUE', $unitId);
        $expenses = $this->getProfitLossRows($dateFrom, $dateTo, 'EXPENSE', $unitId);
        return financial_notes_table_total($revenues) - financial_notes_table_total($expenses);
    }

    private function balanceAmount(string $accountType, float $debit, float $credit): float
    {
        return in_array($accountType, ['ASSET', 'EXPENSE'], true)
            ? $debit - $credit
            : $credit - $debit;
    }
}
