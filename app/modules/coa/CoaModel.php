<?php

declare(strict_types=1);

final class CoaModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getList(array $filters = []): array
    {
        $sql = 'SELECT a.id, a.account_code, a.account_name, a.account_type, a.account_category,
                       a.parent_id, a.is_header, a.is_active, a.created_at,
                       p.account_code AS parent_code, p.account_name AS parent_name
                FROM coa_accounts a
                LEFT JOIN coa_accounts p ON p.id = a.parent_id
                WHERE 1=1';

        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (a.account_code LIKE :search OR a.account_name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $sql .= ' AND a.account_type = :type';
            $params[':type'] = $type;
        }

        $sql .= ' ORDER BY a.account_code ASC, a.id ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM coa_accounts');
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM coa_accounts WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCode(string $code, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM coa_accounts WHERE account_code = :code';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getParentOptions(?int $excludeId = null): array
    {
        $sql = 'SELECT id, account_code, account_name, account_type, is_header, is_active
                FROM coa_accounts
                WHERE is_header = 1 AND is_active = 1';

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= ' ORDER BY account_code ASC';
        $stmt = $this->db->prepare($sql);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO coa_accounts (
                    account_code, account_name, account_type, account_category,
                    parent_id, is_header, is_active, created_at, updated_at
                ) VALUES (
                    :account_code, :account_name, :account_type, :account_category,
                    :parent_id, :is_header, :is_active, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $this->bindData($stmt, $data);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE coa_accounts SET
                    account_code = :account_code,
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
        $this->bindData($stmt, $data);
        $stmt->execute();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE coa_accounts SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function hasChildren(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM coa_accounts WHERE parent_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function isUsedInJournal(int $id): bool
    {
        if (!$this->tableExists('journal_lines')) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE coa_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM coa_accounts WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function canDelete(int $id): array
    {
        if ($this->hasChildren($id)) {
            return [false, 'Akun tidak dapat dihapus karena masih memiliki akun turunan.'];
        }

        if ($this->isUsedInJournal($id)) {
            return [false, 'Akun tidak dapat dihapus karena sudah dipakai pada jurnal.'];
        }

        return [true, 'Akun dapat dihapus.'];
    }

    public function canSetAsParent(int $parentId, ?int $currentId = null): bool
    {
        if ($currentId === null) {
            return true;
        }

        if ($parentId === $currentId) {
            return false;
        }

        $visited = [];
        $cursor = $parentId;
        while ($cursor !== null) {
            if (in_array($cursor, $visited, true)) {
                return false;
            }
            $visited[] = $cursor;

            $row = $this->findById($cursor);
            if (!$row) {
                return false;
            }

            $next = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            if ($next === $currentId) {
                return false;
            }
            $cursor = $next;
        }

        return true;
    }

    private function tableExists(string $tableName): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    private function bindData(PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':account_code', $data['account_code'], PDO::PARAM_STR);
        $stmt->bindValue(':account_name', $data['account_name'], PDO::PARAM_STR);
        $stmt->bindValue(':account_type', $data['account_type'], PDO::PARAM_STR);
        $stmt->bindValue(':account_category', $data['account_category'], PDO::PARAM_STR);

        if ($data['parent_id'] === null) {
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':parent_id', (int) $data['parent_id'], PDO::PARAM_INT);
        }

        $stmt->bindValue(':is_header', $data['is_header'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
    }
}
