<?php

declare(strict_types=1);

final class ReferenceMasterModel
{
    private const CONFIG = [
        'partners' => [
            'table' => 'reference_partners',
            'title' => 'Master Mitra / Debitur / Kreditur',
            'code_field' => 'partner_code',
            'name_field' => 'partner_name',
            'description_field' => 'notes',
            'type_field' => 'partner_type',
            'type_options' => [
                'CUSTOMER' => 'Pelanggan / Debitur',
                'VENDOR' => 'Vendor / Kreditur',
                'BOTH' => 'Keduanya',
                'OTHER' => 'Lainnya',
            ],
            'search_fields' => ['partner_code', 'partner_name', 'partner_type', 'phone', 'address', 'notes'],
            'usage_table' => 'journal_lines',
            'usage_column' => 'partner_id',
        ],
        'inventory' => [
            'table' => 'inventory_items',
            'title' => 'Master Persediaan',
            'code_field' => 'item_code',
            'name_field' => 'item_name',
            'description_field' => 'notes',
            'type_field' => null,
            'type_options' => [],
            'search_fields' => ['item_code', 'item_name', 'unit_name', 'notes'],
            'usage_table' => 'journal_lines',
            'usage_column' => 'inventory_item_id',
        ],
        'raw-materials' => [
            'table' => 'raw_materials',
            'title' => 'Master Bahan Baku',
            'code_field' => 'material_code',
            'name_field' => 'material_name',
            'description_field' => 'notes',
            'type_field' => null,
            'type_options' => [],
            'search_fields' => ['material_code', 'material_name', 'unit_name', 'notes'],
            'usage_table' => 'journal_lines',
            'usage_column' => 'raw_material_id',
        ],
        'savings' => [
            'table' => 'saving_accounts',
            'title' => 'Master Simpanan',
            'code_field' => 'account_no',
            'name_field' => 'account_name',
            'description_field' => 'notes',
            'type_field' => 'saving_type',
            'type_options' => [
                'VOLUNTARY' => 'Simpanan Sukarela',
                'MANDATORY' => 'Simpanan Wajib',
                'TIME' => 'Simpanan Berjangka',
                'OTHER' => 'Lainnya',
            ],
            'search_fields' => ['account_no', 'account_name', 'saving_type', 'owner_name', 'notes'],
            'usage_table' => 'journal_lines',
            'usage_column' => 'saving_account_id',
        ],
        'cashflow-components' => [
            'table' => 'cashflow_components',
            'title' => 'Master Komponen Arus Kas',
            'code_field' => 'component_code',
            'name_field' => 'component_name',
            'description_field' => 'description',
            'type_field' => 'cashflow_group',
            'type_options' => [
                'OPERATING' => 'Aktivitas Operasi',
                'INVESTING' => 'Aktivitas Investasi',
                'FINANCING' => 'Aktivitas Pembiayaan',
            ],
            'search_fields' => ['component_code', 'component_name', 'cashflow_group', 'direction', 'description'],
            'usage_table' => 'journal_lines',
            'usage_column' => 'cashflow_component_id',
        ],
    ];

    public function __construct(private PDO $db)
    {
    }

    public static function configs(): array
    {
        return self::CONFIG;
    }

    public static function config(string $type): ?array
    {
        return self::CONFIG[$type] ?? null;
    }

    public function isReady(string $type): bool
    {
        $config = self::config($type);
        return $config !== null && $this->tableExists($config['table']);
    }

    public function getList(string $type, string $search = ''): array
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);

        $sql = 'SELECT t.*, '
            . $this->usageCountExpression($config)
            . ' AS usage_count FROM ' . $config['table'] . ' t WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $parts = [];
            foreach ($this->existingColumns($config['table'], $config['search_fields']) as $idx => $field) {
                $key = ':search' . $idx;
                $parts[] = 't.' . $field . ' LIKE ' . $key;
                $params[$key] = '%' . $search . '%';
            }
            if ($parts !== []) {
                $sql .= ' AND (' . implode(' OR ', $parts) . ')';
            }
        }
        $sql .= ' ORDER BY t.is_active DESC, t.' . $config['code_field'] . ' ASC, t.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(string $type, int $id): ?array
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        $stmt = $this->db->prepare('SELECT * FROM ' . $config['table'] . ' WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCode(string $type, string $code, ?int $excludeId = null): ?array
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        $sql = 'SELECT * FROM ' . $config['table'] . ' WHERE ' . $config['code_field'] . ' = :code';
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

    public function create(string $type, array $data): int
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        [$columns, $params] = $this->buildWritePayload($type, $data);
        $sql = 'INSERT INTO ' . $config['table'] . ' (' . implode(', ', array_keys($columns)) . ', created_at, updated_at) VALUES ('
            . implode(', ', array_values($columns)) . ', NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => [$value, $pdoType]) {
            $stmt->bindValue($key, $value, $pdoType);
        }
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function update(string $type, int $id, array $data): void
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        [$columns, $params] = $this->buildWritePayload($type, $data);
        $assignments = [];
        foreach (array_keys($columns) as $column) {
            $assignments[] = $column . ' = ' . $columns[$column];
        }
        $sql = 'UPDATE ' . $config['table'] . ' SET ' . implode(', ', $assignments) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => [$value, $pdoType]) {
            $stmt->bindValue($key, $value, $pdoType);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function setActive(string $type, int $id, bool $active): void
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        $stmt = $this->db->prepare('UPDATE ' . $config['table'] . ' SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function canDelete(string $type, int $id): array
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        if (!$this->tableExists($config['usage_table']) || !$this->columnExists($config['usage_table'], $config['usage_column'])) {
            return [true, 'Data dapat dihapus.'];
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ' . $config['usage_table'] . ' WHERE ' . $config['usage_column'] . ' = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return [false, 'Data tidak dapat dihapus karena sudah dipakai pada jurnal.'];
        }
        return [true, 'Data dapat dihapus.'];
    }

    public function delete(string $type, int $id): void
    {
        $config = $this->requireConfig($type);
        $this->ensureTableExists($config['table']);
        $stmt = $this->db->prepare('DELETE FROM ' . $config['table'] . ' WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function buildWritePayload(string $type, array $data): array
    {
        $config = $this->requireConfig($type);
        $columns = [];
        $params = [];

        $columns[$config['code_field']] = ':code';
        $params[':code'] = [$data['code'], PDO::PARAM_STR];
        $columns[$config['name_field']] = ':name';
        $params[':name'] = [$data['name'], PDO::PARAM_STR];

        if ($config['type_field'] !== null && $this->columnExists($config['table'], $config['type_field'])) {
            $columns[$config['type_field']] = ':type_value';
            $params[':type_value'] = [$data['type_value'] !== '' ? $data['type_value'] : null, $data['type_value'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL];
        }

        switch ($type) {
            case 'partners':
                $columns['phone'] = ':phone';
                $params[':phone'] = [$data['phone'], PDO::PARAM_STR];
                $columns['address'] = ':address';
                $params[':address'] = [$data['address'], PDO::PARAM_STR];
                break;
            case 'inventory':
            case 'raw-materials':
                $columns['unit_name'] = ':unit_name';
                $params[':unit_name'] = [$data['unit_name'], PDO::PARAM_STR];
                break;
            case 'savings':
                $columns['owner_name'] = ':owner_name';
                $params[':owner_name'] = [$data['owner_name'], PDO::PARAM_STR];
                break;
        }

        if ($this->columnExists($config['table'], $config['description_field'])) {
            $columns[$config['description_field']] = ':notes';
            $params[':notes'] = [$data['notes'], PDO::PARAM_STR];
        }
        $columns['is_active'] = ':is_active';
        $params[':is_active'] = [$data['is_active'] ? 1 : 0, PDO::PARAM_INT];

        return [$columns, $params];
    }


    private function existingColumns(string $table, array $columns): array
    {
        $available = [];
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                $available[] = $column;
            }
        }
        return $available;
    }

    private function usageCountExpression(array $config): string
    {
        if (!$this->tableExists($config['usage_table']) || !$this->columnExists($config['usage_table'], $config['usage_column'])) {
            return '0';
        }
        return '(SELECT COUNT(*) FROM ' . $config['usage_table'] . ' jl WHERE jl.' . $config['usage_column'] . ' = t.id)';
    }

    private function requireConfig(string $type): array
    {
        $config = self::config($type);
        if ($config === null) {
            throw new RuntimeException('Tipe master referensi tidak dikenali.');
        }
        return $config;
    }

    private function ensureTableExists(string $table): void
    {
        if (!$this->tableExists($table)) {
            throw new RuntimeException('Tabel master referensi belum tersedia. Jalankan patch database tahap referensi jurnal terlebih dahulu.');
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
            $stmt->bindValue(':table', $table, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1');
            $stmt->bindValue(':table', $table, PDO::PARAM_STR);
            $stmt->bindValue(':column', $column, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
