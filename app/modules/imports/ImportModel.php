<?php

declare(strict_types=1);

final class ImportModel
{
    public function __construct(private PDO $db)
    {
    }

    public function findCoaByCode(string $accountCode): ?array
    {
        $normalized = coa_normalize_account_code($accountCode);
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, account_code, account_name, account_type, account_category, parent_id, is_header, is_active
             FROM coa_accounts
             WHERE account_code = :account_code
             ORDER BY is_active DESC, id ASC
             LIMIT 1'
        );
        $stmt->bindValue(':account_code', $normalized, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }

        return $this->findCoaByNormalizedCode($normalized);
    }

    public function findCoaByNormalizedCode(string $accountCode): ?array
    {
        $normalized = coa_normalize_account_code($accountCode);
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, account_code, account_name, account_type, account_category, parent_id, is_header, is_active
             FROM coa_accounts
             WHERE REPLACE(UPPER(TRIM(account_code)), " ", "") = :normalized
             ORDER BY is_active DESC, id ASC
             LIMIT 1'
        );
        $stmt->bindValue(':normalized', $normalized, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insertCoa(array $data): int
    {
        $sql = 'INSERT INTO coa_accounts (
                    account_code, account_name, account_type, account_category, parent_id, is_header, is_active, created_at, updated_at
                ) VALUES (
                    :account_code, :account_name, :account_type, :account_category, :parent_id, :is_header, :is_active, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':account_code', coa_normalize_account_code((string) $data['account_code']), PDO::PARAM_STR);
        $stmt->bindValue(':account_name', $data['account_name'], PDO::PARAM_STR);
        $stmt->bindValue(':account_type', $data['account_type'], PDO::PARAM_STR);
        $stmt->bindValue(':account_category', $data['account_category'], PDO::PARAM_STR);
        if ($data['parent_id'] === null) {
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':parent_id', (int) $data['parent_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':is_header', (int) $data['is_header'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', (int) $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateCoa(int $id, array $data): void
    {
        $sql = 'UPDATE coa_accounts
                SET account_code = :account_code,
                    account_name = :account_name,
                    account_type = :account_type,
                    account_category = :account_category,
                    parent_id = :parent_id,
                    is_header = :is_header,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':account_code', coa_normalize_account_code((string) $data['account_code']), PDO::PARAM_STR);
        $stmt->bindValue(':account_name', $data['account_name'], PDO::PARAM_STR);
        $stmt->bindValue(':account_type', $data['account_type'], PDO::PARAM_STR);
        $stmt->bindValue(':account_category', $data['account_category'], PDO::PARAM_STR);
        if ($data['parent_id'] === null) {
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':parent_id', (int) $data['parent_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':is_header', (int) $data['is_header'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', (int) $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();
    }

    public function findPeriodByCode(string $periodCode): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE period_code = :period_code LIMIT 1');
        $stmt->bindValue(':period_code', strtoupper(trim($periodCode)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findJournalAccountByCode(string $accountCode): ?array
    {
        $lookupCodes = function_exists('coa_journal_account_lookup_codes')
            ? coa_journal_account_lookup_codes($accountCode)
            : [coa_normalize_account_code($accountCode)];

        $lookupCodes = array_values(array_unique(array_filter($lookupCodes, static fn ($value): bool => (string) $value !== '')));
        if ($lookupCodes === []) {
            return null;
        }

        $placeholders = [];
        foreach ($lookupCodes as $index => $_) {
            $placeholders[] = ':code_' . $index;
        }

        $sql = 'SELECT id, account_code, account_name, account_type, is_header, is_active
                FROM coa_accounts
                WHERE account_code IN (' . implode(', ', $placeholders) . ')
                ORDER BY is_active DESC, is_header ASC, id ASC
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        foreach ($lookupCodes as $index => $code) {
            $stmt->bindValue(':code_' . $index, $code, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }

        $compact = function_exists('coa_compact_account_code')
            ? coa_compact_account_code($accountCode)
            : preg_replace('/[^A-Z0-9]/', '', coa_normalize_account_code($accountCode));
        if ($compact === null || $compact === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, account_code, account_name, account_type, is_header, is_active
             FROM coa_accounts
             WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(account_code)), ".", ""), "-", ""), " ", "") = :compact
             ORDER BY is_active DESC, is_header ASC, id ASC
             LIMIT 1'
        );
        $stmt->bindValue(':compact', $compact, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
