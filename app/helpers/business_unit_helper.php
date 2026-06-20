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
        $sql = 'SELECT id, unit_code, unit_name, legal_name, nib, phone, email, address, description, is_active FROM business_units';
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
        $stmt = $pdo->prepare('SELECT id, unit_code, unit_name, legal_name, nib, phone, email, address, description, is_active FROM business_units WHERE id = :id LIMIT 1');
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

function current_business_unit_id(): int
{
    if (!class_exists('Auth') || !Auth::check()) {
        return 0;
    }

    $sessionUnitId = (int) Session::get('active_business_unit_id', -1);
    $unitId = $sessionUnitId >= 0
        ? $sessionUnitId
        : (int) UserPreferenceStore::instance()->get((int) (Auth::user()['id'] ?? 0), 'active_business_unit_id', 0);

    if ($unitId <= 0) {
        Session::put('active_business_unit_id', 0);
        return 0;
    }

    $unit = find_business_unit($unitId);
    if (!$unit || (int) ($unit['is_active'] ?? 0) !== 1) {
        switch_business_unit_context(0);
        return 0;
    }

    Session::put('active_business_unit_id', $unitId);
    return $unitId;
}

function current_business_unit(): ?array
{
    $unitId = current_business_unit_id();
    return $unitId > 0 ? find_business_unit($unitId) : null;
}

function current_business_unit_label(bool $fallbackAll = true): string
{
    return business_unit_label(current_business_unit(), $fallbackAll);
}

function switch_business_unit_context(int $unitId): bool
{
    if ($unitId > 0) {
        $unit = find_business_unit($unitId);
        if (!$unit || (int) ($unit['is_active'] ?? 0) !== 1) {
            return false;
        }
    } else {
        $unitId = 0;
    }

    Session::put('active_business_unit_id', $unitId);
    if (class_exists('Auth') && Auth::check()) {
        UserPreferenceStore::instance()->put((int) (Auth::user()['id'] ?? 0), 'active_business_unit_id', $unitId);
    }

    return true;
}

function resolve_business_unit_filter(?int $queryUnitId = null): int
{
    return current_business_unit_id();
}

function apply_global_business_unit_filter(array $filters): array
{
    if (array_key_exists('unit_id', $filters)) {
        $filters['unit_id'] = resolve_business_unit_filter(isset($filters['unit_id']) ? (int) $filters['unit_id'] : null);
    }

    return $filters;
}
