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


function coa_default_global_accounts(): array
{
    return [
        ['account_code' => '1.000', 'account_name' => 'Aset', 'account_type' => 'ASSET', 'account_category' => 'OTHER_ASSET', 'parent_code' => null, 'is_header' => true, 'is_active' => true],
        ['account_code' => '1.101', 'account_name' => 'Kas', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.102', 'account_name' => 'Bank', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.103', 'account_name' => 'Kas Kecil', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.104', 'account_name' => 'Setara Kas', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.110', 'account_name' => 'Piutang Usaha', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.111', 'account_name' => 'Piutang Lain-lain', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.120', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.121', 'account_name' => 'Persediaan Bahan / Perlengkapan', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.130', 'account_name' => 'Uang Muka Pembelian', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.131', 'account_name' => 'Beban Dibayar Dimuka', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.140', 'account_name' => 'Pajak Dibayar Dimuka', 'account_type' => 'ASSET', 'account_category' => 'CURRENT_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.201', 'account_name' => 'Tanah', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.202', 'account_name' => 'Bangunan', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.203', 'account_name' => 'Peralatan dan Mesin', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.204', 'account_name' => 'Kendaraan', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.205', 'account_name' => 'Inventaris Kantor', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.291', 'account_name' => 'Akumulasi Penyusutan Bangunan', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.292', 'account_name' => 'Akumulasi Penyusutan Peralatan dan Mesin', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.293', 'account_name' => 'Akumulasi Penyusutan Kendaraan', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '1.294', 'account_name' => 'Akumulasi Penyusutan Inventaris Kantor', 'account_type' => 'ASSET', 'account_category' => 'FIXED_ASSET', 'parent_code' => '1.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.000', 'account_name' => 'Liabilitas', 'account_type' => 'LIABILITY', 'account_category' => 'OTHER_LIABILITY', 'parent_code' => null, 'is_header' => true, 'is_active' => true],
        ['account_code' => '2.101', 'account_name' => 'Utang Usaha', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.102', 'account_name' => 'Utang Lain-lain', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.103', 'account_name' => 'Utang Gaji dan Honor', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.104', 'account_name' => 'Utang Pajak', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.105', 'account_name' => 'Biaya Masih Harus Dibayar', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.106', 'account_name' => 'Pendapatan Diterima Dimuka', 'account_type' => 'LIABILITY', 'account_category' => 'CURRENT_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '2.201', 'account_name' => 'Utang Bank / Pinjaman Jangka Panjang', 'account_type' => 'LIABILITY', 'account_category' => 'LONG_TERM_LIABILITY', 'parent_code' => '2.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '3.000', 'account_name' => 'Ekuitas', 'account_type' => 'EQUITY', 'account_category' => 'OWNER_EQUITY', 'parent_code' => null, 'is_header' => true, 'is_active' => true],
        ['account_code' => '3.101', 'account_name' => 'Penyertaan Modal Desa', 'account_type' => 'EQUITY', 'account_category' => 'OWNER_EQUITY', 'parent_code' => '3.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '3.102', 'account_name' => 'Penyertaan Modal Masyarakat / Pihak Ketiga', 'account_type' => 'EQUITY', 'account_category' => 'OWNER_EQUITY', 'parent_code' => '3.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '3.103', 'account_name' => 'Cadangan Umum', 'account_type' => 'EQUITY', 'account_category' => 'OWNER_EQUITY', 'parent_code' => '3.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '3.104', 'account_name' => 'Laba Ditahan', 'account_type' => 'EQUITY', 'account_category' => 'RETAINED_EARNINGS', 'parent_code' => '3.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '3.105', 'account_name' => 'Saldo Laba Tahun Berjalan', 'account_type' => 'EQUITY', 'account_category' => 'RETAINED_EARNINGS', 'parent_code' => '3.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.000', 'account_name' => 'Pendapatan', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => null, 'is_header' => true, 'is_active' => true],
        ['account_code' => '4.101', 'account_name' => 'Pendapatan Penjualan', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.102', 'account_name' => 'Pendapatan Jasa', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.103', 'account_name' => 'Pendapatan Administrasi', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.104', 'account_name' => 'Pendapatan Sewa', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.105', 'account_name' => 'Pendapatan Komisi / Fee', 'account_type' => 'REVENUE', 'account_category' => 'OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.201', 'account_name' => 'Pendapatan Bunga / Jasa Giro', 'account_type' => 'REVENUE', 'account_category' => 'NON_OPERATING_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '4.202', 'account_name' => 'Pendapatan Lain-lain', 'account_type' => 'REVENUE', 'account_category' => 'OTHER_REVENUE', 'parent_code' => '4.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.000', 'account_name' => 'Beban', 'account_type' => 'EXPENSE', 'account_category' => 'OPERATING_EXPENSE', 'parent_code' => null, 'is_header' => true, 'is_active' => true],
        ['account_code' => '5.101', 'account_name' => 'Harga Pokok Penjualan', 'account_type' => 'EXPENSE', 'account_category' => 'OPERATING_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.102', 'account_name' => 'Beban Gaji dan Honor', 'account_type' => 'EXPENSE', 'account_category' => 'ADMIN_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.103', 'account_name' => 'Beban Listrik dan Air', 'account_type' => 'EXPENSE', 'account_category' => 'ADMIN_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.104', 'account_name' => 'Beban Internet dan Telepon', 'account_type' => 'EXPENSE', 'account_category' => 'ADMIN_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.105', 'account_name' => 'Beban ATK dan Administrasi', 'account_type' => 'EXPENSE', 'account_category' => 'ADMIN_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.106', 'account_name' => 'Beban Transportasi', 'account_type' => 'EXPENSE', 'account_category' => 'OPERATING_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.107', 'account_name' => 'Beban Perawatan', 'account_type' => 'EXPENSE', 'account_category' => 'OPERATING_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.108', 'account_name' => 'Beban Sewa', 'account_type' => 'EXPENSE', 'account_category' => 'OPERATING_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.109', 'account_name' => 'Beban Penyusutan', 'account_type' => 'EXPENSE', 'account_category' => 'OTHER_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.110', 'account_name' => 'Beban Pajak dan Retribusi', 'account_type' => 'EXPENSE', 'account_category' => 'OTHER_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.111', 'account_name' => 'Beban Bunga', 'account_type' => 'EXPENSE', 'account_category' => 'OTHER_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
        ['account_code' => '5.112', 'account_name' => 'Beban Lain-lain', 'account_type' => 'EXPENSE', 'account_category' => 'OTHER_EXPENSE', 'parent_code' => '5.000', 'is_header' => false, 'is_active' => true],
    ];
}

function coa_default_global_account_count(): int
{
    return count(coa_default_global_accounts());
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
