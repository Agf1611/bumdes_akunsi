<?php

declare(strict_types=1);

final class ProfileModel
{
    private ?array $columnMap = null;

    public function __construct(private PDO $db)
    {
    }

    public function findFirst(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM app_profiles ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function save(array $data): int
    {
        $existing = $this->findFirst();
        $columns = $this->availableColumns();

        $fieldOrder = [
            'bumdes_name',
            'address',
            'village_name',
            'district_name',
            'regency_name',
            'province_name',
            'legal_entity_no',
            'nib',
            'npwp',
            'phone',
            'email',
            'logo_path',
            'leader_name',
            'director_name',
            'director_position',
            'signature_city',
            'signature_path',
            'treasurer_name',
            'treasurer_position',
            'treasurer_signature_path',
            'receipt_signature_mode',
            'receipt_require_recipient_cash',
            'receipt_require_recipient_transfer',
            'director_sign_threshold',
            'show_stamp',
            'active_period_start',
            'active_period_end',
            'updated_by',
        ];

        $payload = [];
        foreach ($fieldOrder as $field) {
            if (isset($columns[$field])) {
                $payload[$field] = $data[$field] ?? null;
            }
        }

        if ($existing) {
            $sets = [];
            foreach (array_keys($payload) as $field) {
                $sets[] = $field . ' = :' . $field;
            }
            $sql = 'UPDATE app_profiles SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
        } else {
            $insertColumns = array_keys($payload);
            $placeholders = array_map(static fn (string $field): string => ':' . $field, $insertColumns);
            $sql = 'INSERT INTO app_profiles (' . implode(', ', $insertColumns) . ', created_at, updated_at) VALUES (' . implode(', ', $placeholders) . ', NOW(), NOW())';
            $stmt = $this->db->prepare($sql);
        }

        foreach ($payload as $field => $value) {
            if (in_array($field, ['updated_by', 'receipt_require_recipient_cash', 'receipt_require_recipient_transfer', 'show_stamp'], true)) {
                $stmt->bindValue(':' . $field, (int) $value, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue(':' . $field, $value === null ? '' : (string) $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $existing ? (int) $existing['id'] : (int) $this->db->lastInsertId();
    }

    private function availableColumns(): array
    {
        if (is_array($this->columnMap)) {
            return $this->columnMap;
        }

        $stmt = $this->db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
        $stmt->bindValue(':table_name', 'app_profiles', PDO::PARAM_STR);
        $stmt->execute();

        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
            $columns[(string) $column] = true;
        }
        $this->columnMap = $columns;

        return $this->columnMap;
    }
}
