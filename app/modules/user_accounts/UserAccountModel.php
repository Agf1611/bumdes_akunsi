<?php

declare(strict_types=1);

final class UserAccountModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getList(string $search = '', string $roleCode = ''): array
    {
        $sql = 'SELECT u.id, u.full_name, u.username, u.is_active, u.last_login_at, u.created_at,
                       r.code AS role_code, r.name AS role_name,
                       (SELECT COUNT(*) FROM journal_headers j WHERE j.created_by = u.id) AS journal_count
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE r.code IN (\'bendahara\', \'pimpinan\')';

        $params = [];
        if ($search !== '') {
            $sql .= ' AND (u.full_name LIKE :search OR u.username LIKE :search OR r.name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if ($roleCode !== '' && in_array($roleCode, ['bendahara', 'pimpinan'], true)) {
            $sql .= ' AND r.code = :role_code';
            $params[':role_code'] = $roleCode;
        }

        $sql .= ' ORDER BY FIELD(r.code, \'bendahara\', \'pimpinan\'), u.full_name ASC, u.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRoleOptions(): array
    {
        $stmt = $this->db->query("SELECT id, code, name FROM roles WHERE code IN ('bendahara', 'pimpinan') ORDER BY FIELD(code, 'bendahara', 'pimpinan')");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, r.code AS role_code, r.name AS role_name
                                    FROM users u
                                    INNER JOIN roles r ON r.id = u.role_id
                                    WHERE u.id = :id AND r.code IN (\'bendahara\', \'pimpinan\')
                                    LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByUsername(string $username, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT id, username FROM users WHERE username = :username';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findRoleByCode(string $roleCode): ?array
    {
        $stmt = $this->db->prepare("SELECT id, code, name FROM roles WHERE code = :code AND code IN ('bendahara', 'pimpinan') LIMIT 1");
        $stmt->bindValue(':code', $roleCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (role_id, full_name, username, password_hash, is_active, created_at, updated_at)
                VALUES (:role_id, :full_name, :username, :password_hash, :is_active, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $data['password_hash'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users
                SET role_id = :role_id,
                    full_name = :full_name,
                    username = :username,
                    is_active = :is_active,
                    updated_at = NOW()';
        if (!empty($data['password_hash'])) {
            $sql .= ', password_hash = :password_hash';
        }
        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        if (!empty($data['password_hash'])) {
            $stmt->bindValue(':password_hash', $data['password_hash'], PDO::PARAM_STR);
        }
        $stmt->execute();
    }

    public function toggleActive(int $id, bool $isActive): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $isActive ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
    }
}
