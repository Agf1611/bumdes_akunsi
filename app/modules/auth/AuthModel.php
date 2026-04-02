<?php
declare(strict_types=1);
final class AuthModel {
    public function __construct(private PDO $db) {}
    public function findByUsername(string $username): ?array {
        $sql = 'SELECT u.id, u.full_name, u.username, u.password_hash, u.is_active, r.code AS role_code, r.name AS role_name
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
}
