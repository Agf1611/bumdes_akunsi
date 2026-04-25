<?php

declare(strict_types=1);

final class WorkspaceModel
{
    public function __construct(private PDO $db)
    {
    }

    public function searchJournals(string $query, int $limit = 6): array
    {
        if (!$this->tableExists('journal_headers')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, journal_no, journal_date, description
             FROM journal_headers
             WHERE journal_no LIKE :query OR description LIKE :query
             ORDER BY journal_date DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchAccounts(string $query, int $limit = 6): array
    {
        if (!$this->tableExists('coa_accounts')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, account_code, account_name, account_type
             FROM coa_accounts
             WHERE account_code LIKE :query OR account_name LIKE :query
             ORDER BY account_code ASC, id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchPeriods(string $query, int $limit = 4): array
    {
        if (!$this->tableExists('accounting_periods')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, period_code, period_name, status
             FROM accounting_periods
             WHERE period_code LIKE :query OR period_name LIKE :query
             ORDER BY start_date DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchBusinessUnits(string $query, int $limit = 4): array
    {
        if (!$this->tableExists('business_units')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, unit_code, unit_name
             FROM business_units
             WHERE unit_code LIKE :query OR unit_name LIKE :query
             ORDER BY unit_code ASC, id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchUsers(string $query, int $limit = 4): array
    {
        if (!$this->tableExists('users') || !$this->tableExists('roles')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT u.id, u.full_name, u.username, r.name AS role_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.full_name LIKE :query OR u.username LIKE :query
             ORDER BY u.full_name ASC, u.id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT 1
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                 LIMIT 1'
            );
            $stmt->execute([':table_name' => $tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
