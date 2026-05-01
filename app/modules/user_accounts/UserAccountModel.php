<?php

declare(strict_types=1);

final class UserAccountModel
{
    private ?bool $hasMfaEnabledColumn = null;
    private ?bool $hasMfaSecretColumn = null;
    public function __construct(private PDO $db)
    {
    }

    public function getList(string $search = '', string $roleCode = ''): array
    {
        $allowedRoles = $this->manageableRoleCodes();
        $quotedRoles = "'" . implode("','", array_map(static fn (string $role): string => str_replace("'", "''", $role), $allowedRoles)) . "'";
        $sql = 'SELECT u.id, u.full_name, u.username, u.is_active, u.last_login_at, u.created_at, '
                . ($this->hasMfaEnabledColumn() ? 'u.mfa_enabled' : '0 AS mfa_enabled') . ',
                       r.code AS role_code, r.name AS role_name,
                       (SELECT COUNT(*) FROM journal_headers j WHERE j.created_by = u.id) AS journal_count
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE r.code IN (' . $quotedRoles . ')';

        $params = [];
        if ($search !== '') {
            $sql .= ' AND (u.full_name LIKE :search OR u.username LIKE :search OR r.name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if ($roleCode !== '' && in_array($roleCode, $allowedRoles, true)) {
            $sql .= ' AND r.code = :role_code';
            $params[':role_code'] = $roleCode;
        }

        $sql .= ' ORDER BY FIELD(r.code, \'admin\', \'bendahara\', \'pimpinan\'), u.full_name ASC, u.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRoleOptions(): array
    {
        $stmt = $this->db->query("SELECT id, code, name FROM roles WHERE code IN ('admin', 'bendahara', 'pimpinan') ORDER BY FIELD(code, 'admin', 'bendahara', 'pimpinan')");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSummary(): array
    {
        $quotedRoles = "'" . implode("','", array_map(static fn (string $role): string => str_replace("'", "''", $role), $this->manageableRoleCodes())) . "'";
        $sql = 'SELECT
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active_users,
                    SUM(CASE WHEN u.is_active = 0 THEN 1 ELSE 0 END) AS inactive_users,
                    SUM(CASE WHEN r.code = \'admin\' THEN 1 ELSE 0 END) AS admin_users,
                    SUM(CASE WHEN r.code = \'bendahara\' THEN 1 ELSE 0 END) AS bendahara_users,
                    SUM(CASE WHEN r.code = \'pimpinan\' THEN 1 ELSE 0 END) AS pimpinan_users,
                    SUM(CASE WHEN ' . ($this->hasMfaEnabledColumn() ? 'u.mfa_enabled = 1' : '0 = 1') . ' THEN 1 ELSE 0 END) AS mfa_users
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE r.code IN (' . $quotedRoles . ')';
        $stmt = $this->db->query($sql);
        $row = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        return [
            'total_users' => (int) ($row['total_users'] ?? 0),
            'active_users' => (int) ($row['active_users'] ?? 0),
            'inactive_users' => (int) ($row['inactive_users'] ?? 0),
            'admin_users' => (int) ($row['admin_users'] ?? 0),
            'bendahara_users' => (int) ($row['bendahara_users'] ?? 0),
            'pimpinan_users' => (int) ($row['pimpinan_users'] ?? 0),
            'mfa_users' => (int) ($row['mfa_users'] ?? 0),
        ];
    }

    public function findById(int $id): ?array
    {
        $quotedRoles = "'" . implode("','", array_map(static fn (string $role): string => str_replace("'", "''", $role), $this->manageableRoleCodes())) . "'";
        $stmt = $this->db->prepare('SELECT u.*, r.code AS role_code, r.name AS role_name
                                    FROM users u
                                    INNER JOIN roles r ON r.id = u.role_id
                                    WHERE u.id = :id AND r.code IN (' . $quotedRoles . ')
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
        $stmt = $this->db->prepare("SELECT id, code, name FROM roles WHERE code = :code AND code IN ('admin', 'bendahara', 'pimpinan') LIMIT 1");
        $stmt->bindValue(':code', $roleCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function manageableRoleCodes(): array
    {
        return ['admin', 'bendahara', 'pimpinan'];
    }

    public function create(array $data): int
    {
        if ($this->hasMfaEnabledColumn() && $this->hasMfaSecretColumn()) {
            $sql = 'INSERT INTO users (role_id, full_name, username, password_hash, is_active, mfa_enabled, mfa_secret, created_at, updated_at)
                    VALUES (:role_id, :full_name, :username, :password_hash, :is_active, :mfa_enabled, :mfa_secret, NOW(), NOW())';
        } else {
            $sql = 'INSERT INTO users (role_id, full_name, username, password_hash, is_active, created_at, updated_at)
                    VALUES (:role_id, :full_name, :username, :password_hash, :is_active, NOW(), NOW())';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $data['password_hash'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        if ($this->hasMfaEnabledColumn() && $this->hasMfaSecretColumn()) {
            $stmt->bindValue(':mfa_enabled', !empty($data['mfa_enabled']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':mfa_secret', (string) ($data['mfa_secret'] ?? ''), PDO::PARAM_STR);
        }
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
        if ($this->hasMfaEnabledColumn()) {
            $sql .= ', mfa_enabled = :mfa_enabled';
        }
        if ($this->hasMfaSecretColumn()) {
            $sql .= ', mfa_secret = :mfa_secret';
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
        if ($this->hasMfaEnabledColumn()) {
            $stmt->bindValue(':mfa_enabled', !empty($data['mfa_enabled']) ? 1 : 0, PDO::PARAM_INT);
        }
        if ($this->hasMfaSecretColumn()) {
            $stmt->bindValue(':mfa_secret', (string) ($data['mfa_secret'] ?? ''), PDO::PARAM_STR);
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

    public function resetPassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function hasMfaEnabledColumn(): bool
    {
        if ($this->hasMfaEnabledColumn !== null) {
            return $this->hasMfaEnabledColumn;
        }
        $this->hasMfaEnabledColumn = $this->columnExists('users', 'mfa_enabled');
        return $this->hasMfaEnabledColumn;
    }

    private function hasMfaSecretColumn(): bool
    {
        if ($this->hasMfaSecretColumn !== null) {
            return $this->hasMfaSecretColumn;
        }
        $this->hasMfaSecretColumn = $this->columnExists('users', 'mfa_secret');
        return $this->hasMfaSecretColumn;
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name
                 LIMIT 1'
            );
            $stmt->execute([
                ':table_name' => $table,
                ':column_name' => $column,
            ]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
