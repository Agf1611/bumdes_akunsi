<?php

declare(strict_types=1);

function app_profile(bool $refresh = false): array
{
    static $cached = null;

    if (!$refresh && is_array($cached)) {
        return $cached;
    }

    $default = [
        'id' => null,
        'bumdes_name' => app_config('name') ?: 'Sistem Pelaporan Keuangan BUMDes',
        'address' => '',
        'village_name' => '',
        'district_name' => '',
        'regency_name' => '',
        'province_name' => '',
        'legal_entity_no' => '',
        'nib' => '',
        'npwp' => '',
        'phone' => '',
        'email' => '',
        'logo_path' => '',
        'leader_name' => '',
        'director_name' => '',
        'director_position' => 'Direktur',
        'signature_city' => '',
        'signature_path' => '',
        'treasurer_name' => '',
        'treasurer_position' => 'Bendahara',
        'treasurer_signature_path' => '',
        'receipt_signature_mode' => 'treasurer_recipient_director',
        'receipt_require_recipient_cash' => 1,
        'receipt_require_recipient_transfer' => 0,
        'director_sign_threshold' => '0.00',
        'show_stamp' => 1,
        'active_period_start' => '',
        'active_period_end' => '',
    ];

    try {
        if (!Database::isConnected(db_config())) {
            $cached = $default;
            return $cached;
        }

        $pdo = Database::getInstance(db_config());
        $stmt = $pdo->query('SELECT * FROM app_profiles ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cached = is_array($row) ? array_merge($default, $row) : $default;
        if ($cached['director_name'] === '') {
            $cached['director_name'] = (string) ($cached['leader_name'] ?? '');
        }
        return $cached;
    } catch (Throwable) {
        $cached = $default;
        return $cached;
    }
}

function active_period_label(?string $startDate, ?string $endDate): string
{
    if (!$startDate || !$endDate) {
        return 'Periode belum diatur';
    }

    try {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
    } catch (Throwable) {
        return 'Periode belum valid';
    }

    return $start->format('d M Y') . ' - ' . $end->format('d M Y');
}

function profile_director_name(array $profile): string
{
    $name = trim((string) ($profile['director_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $legacy = trim((string) ($profile['leader_name'] ?? ''));
    return $legacy !== '' ? $legacy : '-';
}

function profile_treasurer_name(array $profile): string
{
    $name = trim((string) ($profile['treasurer_name'] ?? ''));
    return $name !== '' ? $name : '-';
}

function profile_treasurer_position(array $profile): string
{
    $position = trim((string) ($profile['treasurer_position'] ?? ''));
    return $position !== '' ? $position : 'Bendahara';
}
