<?php
declare(strict_types=1);
final class AuthModel {
    private ?bool $hasMfaEnabledColumn = null;
    private ?bool $hasMfaSecretColumn = null;
    public function __construct(private PDO $db) {}
    public function findByUsername(string $username): ?array {
        $select = [
            'u.id',
            'u.full_name',
            'u.username',
            'u.password_hash',
            'u.is_active',
            $this->hasMfaEnabledColumn() ? 'u.mfa_enabled' : '0 AS mfa_enabled',
            $this->hasMfaSecretColumn() ? 'u.mfa_secret' : "'' AS mfa_secret",
            'r.code AS role_code',
            'r.name AS role_name',
        ];
        $sql = 'SELECT ' . implode(', ', $select) . '
                FROM users u INNER JOIN roles r ON r.id = u.role_id
                WHERE u.username = :username LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function updateLastLogin(int $id): void {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id',$id,PDO::PARAM_INT);
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
