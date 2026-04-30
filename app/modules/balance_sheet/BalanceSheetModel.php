<?php

declare(strict_types=1);

final class BalanceSheetModel
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

    public function findPreviousPeriod(string $currentStartDate, int $excludeId = 0): ?array
    {
        $sql = 'SELECT * FROM accounting_periods WHERE end_date < :current_start_date';
        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' ORDER BY end_date DESC, id DESC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':current_start_date', $currentStartDate, PDO::PARAM_STR);
        if ($excludeId > 0) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRows(string $dateTo, int $unitId = 0): array
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
                  AND a.account_type IN ('ASSET', 'LIABILITY', 'EQUITY')";

        $params = [$dateTo, $dateTo];
        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = ?';
            $params[] = $unitId;
        }

        $sql .= " GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                  HAVING closing_total_debit <> closing_total_credit
                  ORDER BY FIELD(a.account_type, 'ASSET', 'LIABILITY', 'EQUITY'), a.account_code ASC, a.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOpeningSnapshotRows(string $dateTo, int $unitId = 0): array
    {
        $year = substr($dateTo, 0, 4);
        $openingStart = preg_match('/^\d{4}$/', $year) === 1 ? $year . '-01-01' : $dateTo;

        $rows = $this->fetchOpeningSnapshotRows($openingStart, $dateTo, $unitId, true);
        if ($rows === [] && $unitId > 0) {
            $rows = $this->fetchOpeningSnapshotRows($openingStart, $dateTo, 0, true);
        }

        return $rows;
    }

    private function fetchOpeningSnapshotRows(string $dateFrom, string $dateTo, int $unitId = 0, bool $useSignals = true): array
    {
        $entryTagExists = $this->hasJournalEntryTagColumn();

        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.account_category,
                    COALESCE(SUM(l.debit), 0) AS opening_total_debit,
                    COALESCE(SUM(l.credit), 0) AS opening_total_credit
                FROM coa_accounts a
                INNER JOIN journal_lines l ON l.coa_id = a.id
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type IN ('ASSET', 'LIABILITY', 'EQUITY')
                  AND h.journal_date >= :date_from
                  AND h.journal_date <= :date_to";

        if ($unitId > 0) {
            $sql .= ' AND h.business_unit_id = :unit_id';
        }

        if ($useSignals) {
            if ($entryTagExists) {
                $sql .= " AND (l.entry_tag = 'SALDO_AWAL' OR h.description LIKE :desc_like OR h.description LIKE :desc_tag_like OR l.line_description LIKE :line_like OR l.line_description LIKE :line_tag_like)";
            } else {
                $sql .= " AND (h.description LIKE :desc_like OR h.description LIKE :desc_tag_like OR l.line_description LIKE :line_like OR l.line_description LIKE :line_tag_like)";
            }
        }

        $sql .= " GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                  HAVING opening_total_debit <> opening_total_credit
                  ORDER BY FIELD(a.account_type, 'ASSET', 'LIABILITY', 'EQUITY'), a.account_code ASC, a.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        if ($unitId > 0) {
            $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
        }
        if ($useSignals) {
            $stmt->bindValue(':desc_like', '%saldo awal%', PDO::PARAM_STR);
            $stmt->bindValue(':desc_tag_like', '%SALDO_AWAL%', PDO::PARAM_STR);
            $stmt->bindValue(':line_like', '%saldo awal%', PDO::PARAM_STR);
            $stmt->bindValue(':line_tag_like', '%SALDO_AWAL%', PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasJournalEntryTagColumn(): bool
    {
        if ($this->entryTagColumnExists !== null) {
            return $this->entryTagColumnExists;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
        $stmt->execute([
            ':table_name' => 'journal_lines',
            ':column_name' => 'entry_tag',
        ]);

        $this->entryTagColumnExists = (int) $stmt->fetchColumn() > 0;
        return $this->entryTagColumnExists;
    }

    public function getCurrentEarnings(string $dateFrom, string $dateTo, int $unitId = 0): float
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
