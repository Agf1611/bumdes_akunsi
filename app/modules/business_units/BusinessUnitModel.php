<?php

declare(strict_types=1);

final class BusinessUnitModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getList(string $search = ''): array
    {
        $sql = 'SELECT bu.*, (SELECT COUNT(*) FROM journal_headers j WHERE j.business_unit_id = bu.id) AS journal_count
                FROM business_units bu
                WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (bu.unit_code LIKE :search OR bu.unit_name LIKE :search OR bu.description LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY bu.unit_code ASC, bu.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM business_units WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCode(string $code, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM business_units WHERE unit_code = :unit_code';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':unit_code', $code, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO business_units (unit_code, unit_name, description, is_active, created_at, updated_at)
                VALUES (:unit_code, :unit_name, :description, :is_active, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':unit_code', $data['unit_code'], PDO::PARAM_STR);
        $stmt->bindValue(':unit_name', $data['unit_name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE business_units SET unit_code = :unit_code, unit_name = :unit_name, description = :description, is_active = :is_active, updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':unit_code', $data['unit_code'], PDO::PARAM_STR);
        $stmt->bindValue(':unit_name', $data['unit_name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE business_units SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function canDelete(int $id): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_headers WHERE business_unit_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return [false, 'Unit usaha tidak dapat dihapus karena sudah dipakai pada transaksi / jurnal.'];
        }
        return [true, 'Unit usaha dapat dihapus.'];
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM business_units WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
