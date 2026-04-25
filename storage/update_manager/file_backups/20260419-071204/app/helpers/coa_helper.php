<?php

declare(strict_types=1);

function coa_account_types(): array
{
    return [
        'ASSET' => 'Aset',
        'LIABILITY' => 'Liabilitas',
        'EQUITY' => 'Ekuitas',
        'REVENUE' => 'Pendapatan',
        'EXPENSE' => 'Beban',
    ];
}

function coa_categories_by_type(): array
{
    return [
        'ASSET' => [
            'CURRENT_ASSET' => 'Aset Lancar',
            'FIXED_ASSET' => 'Aset Tetap',
            'OTHER_ASSET' => 'Aset Lainnya',
        ],
        'LIABILITY' => [
            'CURRENT_LIABILITY' => 'Liabilitas Jangka Pendek',
            'LONG_TERM_LIABILITY' => 'Liabilitas Jangka Panjang',
            'OTHER_LIABILITY' => 'Liabilitas Lainnya',
        ],
        'EQUITY' => [
            'OWNER_EQUITY' => 'Modal / Ekuitas',
            'RETAINED_EARNINGS' => 'Laba Ditahan',
            'OTHER_EQUITY' => 'Ekuitas Lainnya',
        ],
        'REVENUE' => [
            'OPERATING_REVENUE' => 'Pendapatan Usaha',
            'NON_OPERATING_REVENUE' => 'Pendapatan Non Usaha',
            'OTHER_REVENUE' => 'Pendapatan Lainnya',
        ],
        'EXPENSE' => [
            'OPERATING_EXPENSE' => 'Beban Operasional',
            'ADMIN_EXPENSE' => 'Beban Administrasi',
            'OTHER_EXPENSE' => 'Beban Lainnya',
        ],
    ];
}

function coa_categories_for_type(string $type): array
{
    $map = coa_categories_by_type();
    return $map[$type] ?? [];
}

function coa_type_label(?string $type): string
{
    $types = coa_account_types();
    return $types[$type ?? ''] ?? '-';
}

function coa_category_label(?string $type, ?string $category): string
{
    $categories = coa_categories_for_type((string) $type);
    return $categories[$category ?? ''] ?? '-';
}

function coa_status_badge_class(bool $isActive): string
{
    return $isActive ? 'text-bg-success' : 'text-bg-secondary';
}

/**
 * Normalisasi kode akun untuk proses import/sinkronisasi.
 * Menjaga titik dan tanda hubung tetap ada agar kode seperti 1.1.01 dan 1.101 tidak dianggap sama.
 */
function coa_normalize_account_code(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return '';
    }

    $code = preg_replace('/\s+/u', '', $code) ?? $code;
    $code = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], '', $code);

    return $code;
}

function coa_codes_match(string $left, string $right): bool
{
    return coa_normalize_account_code($left) !== ''
        && coa_normalize_account_code($left) === coa_normalize_account_code($right);
}


function coa_compact_account_code(string $code): string
{
    $normalized = coa_normalize_account_code($code);
    if ($normalized === '') {
        return '';
    }

    return preg_replace('/[^A-Z0-9]/', '', $normalized) ?? $normalized;
}

/**
 * Alias kode akun lama yang masih dipakai template jurnal bawaan.
 * Disimpan terpusat agar import jurnal tetap kompatibel tanpa mengubah COA aktif di database.
 */
function coa_legacy_journal_account_aliases(): array
{
    return [
        '1101' => '1.101',
        '1102' => '1.102',
        '1201' => '1.201',
        '3101' => '3.101',
        '4101' => '4.101',
        '5102' => '5.102',
    ];
}

/**
 * Kandidat kode akun untuk lookup jurnal.
 * Urutan penting: exact code -> alias resmi template lama.
 */
function coa_journal_account_lookup_codes(string $code): array
{
    $normalized = coa_normalize_account_code($code);
    if ($normalized === '') {
        return [];
    }

    $candidates = [$normalized];
    $aliases = coa_legacy_journal_account_aliases();
    if (isset($aliases[$normalized])) {
        $candidates[] = coa_normalize_account_code($aliases[$normalized]);
    }

    return array_values(array_unique(array_filter($candidates, static fn ($value): bool => (string) $value !== '')));
}
