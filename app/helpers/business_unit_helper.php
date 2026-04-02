<?php

declare(strict_types=1);

function business_unit_options(bool $includeInactive = false): array
{
    static $cache = [];
    $cacheKey = $includeInactive ? 'all' : 'active';
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    try {
        if (!Database::isConnected(db_config())) {
            return [];
        }
        $pdo = Database::getInstance(db_config());
        $sql = 'SELECT id, unit_code, unit_name, description, is_active FROM business_units';
        if (!$includeInactive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY unit_code ASC, id ASC';
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $cache[$cacheKey] = $rows;
        return $rows;
    } catch (Throwable) {
        return [];
    }
}

function find_business_unit(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    try {
        $pdo = Database::getInstance(db_config());
        $stmt = $pdo->prepare('SELECT id, unit_code, unit_name, description, is_active FROM business_units WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable) {
        return null;
    }
}

function business_unit_label(?array $unit, bool $fallbackAll = true): string
{
    if (!$unit) {
        return $fallbackAll ? 'Semua Unit' : '-';
    }

    $code = trim((string) ($unit['unit_code'] ?? ''));
    $name = trim((string) ($unit['unit_name'] ?? ''));
    if ($code !== '' && $name !== '') {
        return $code . ' - ' . $name;
    }
    return $name !== '' ? $name : ($fallbackAll ? 'Semua Unit' : '-');
}

function selected_unit_from_filters(array $filters): ?array
{
    $unitId = (int) ($filters['unit_id'] ?? 0);
    return $unitId > 0 ? find_business_unit($unitId) : null;
}
