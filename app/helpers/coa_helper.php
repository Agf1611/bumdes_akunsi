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
            'INVESTMENT_ASSET' => 'Investasi',
            'FIXED_ASSET' => 'Aset Tetap',
            'INTANGIBLE_ASSET' => 'Aset Takberwujud',
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
            'COST_OF_GOODS_SOLD' => 'Harga Pokok Produksi dan Penjualan',
            'OPERATING_EXPENSE' => 'Beban Operasional',
            'ADMIN_EXPENSE' => 'Beban Administrasi',
            'MARKETING_EXPENSE' => 'Beban Pemasaran',
            'NON_OPERATING_EXPENSE' => 'Beban Non Usaha',
            'TAX_EXPENSE' => 'Beban Pajak',
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
    return array_merge(coa_kepmendes_136_accounts(), [
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
    ]);
}

function coa_kepmendes_136_accounts(): array
{
    $rows = [
        ['1.0.00.00', 'ASET', 'ASSET', 'OTHER_ASSET', null, true],
        ['1.1.00.00', 'Aset Lancar', 'ASSET', 'CURRENT_ASSET', '1.0.00.00', true],
        ['1.1.01.00', 'Kas', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.01.01', 'Kas Tunai', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.01.02', 'Kas di Bank BSI', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.01.03', 'Kas di Bank Mandiri', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.01.04', 'Kas di Bank BRI', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.01.05', 'Kas di Bank BPD', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.01.98', 'Kas Kecil (Petty Cash)', 'ASSET', 'CURRENT_ASSET', '1.1.01.00', false],
        ['1.1.02.00', 'Setara Kas', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.02.01', 'Deposito <= 3 bulan', 'ASSET', 'CURRENT_ASSET', '1.1.02.00', false],
        ['1.1.02.99', 'Setara Kas Lainnya', 'ASSET', 'CURRENT_ASSET', '1.1.02.00', false],
        ['1.1.03.00', 'Piutang', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.03.01', 'Piutang Usaha', 'ASSET', 'CURRENT_ASSET', '1.1.03.00', false],
        ['1.1.03.02', 'Piutang kepada Pegawai', 'ASSET', 'CURRENT_ASSET', '1.1.03.00', false],
        ['1.1.03.99', 'Piutang Lainnya', 'ASSET', 'CURRENT_ASSET', '1.1.03.00', false],
        ['1.1.04.00', 'Penyisihan Piutang', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.04.01', 'Penyisihan Piutang Usaha Tak Tertagih', 'ASSET', 'CURRENT_ASSET', '1.1.04.00', false],
        ['1.1.04.02', 'Penyisihan Piutang kepada Pegawai Tak Tertagih', 'ASSET', 'CURRENT_ASSET', '1.1.04.00', false],
        ['1.1.04.99', 'Penyisihan Piutang Lainnya Tak Tertagih', 'ASSET', 'CURRENT_ASSET', '1.1.04.00', false],
        ['1.1.05.00', 'Persediaan', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.05.01', 'Persediaan Barang Dagangan', 'ASSET', 'CURRENT_ASSET', '1.1.05.00', false],
        ['1.1.05.02', 'Persediaan Bahan Baku', 'ASSET', 'CURRENT_ASSET', '1.1.05.00', false],
        ['1.1.05.03', 'Persediaan Barang Dalam Proses', 'ASSET', 'CURRENT_ASSET', '1.1.05.00', false],
        ['1.1.05.04', 'Persediaan Barang Jadi', 'ASSET', 'CURRENT_ASSET', '1.1.05.00', false],
        ['1.1.06.00', 'Perlengkapan', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.06.01', 'Alat Tulis Kantor (ATK)', 'ASSET', 'CURRENT_ASSET', '1.1.06.00', false],
        ['1.1.07.00', 'Pembayaran Dimuka', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.07.01', 'Sewa Dibayar Dimuka', 'ASSET', 'CURRENT_ASSET', '1.1.07.00', false],
        ['1.1.07.02', 'Asuransi Dibayar Dimuka', 'ASSET', 'CURRENT_ASSET', '1.1.07.00', false],
        ['1.1.07.03', 'PPh 25', 'ASSET', 'CURRENT_ASSET', '1.1.07.00', false],
        ['1.1.07.04', 'PPN Masukan', 'ASSET', 'CURRENT_ASSET', '1.1.07.00', false],
        ['1.1.98.00', 'Aset Lancar Lainnya', 'ASSET', 'CURRENT_ASSET', '1.1.00.00', true],
        ['1.1.98.99', 'Aset Lancar Lainnya', 'ASSET', 'CURRENT_ASSET', '1.1.98.00', false],
        ['1.2.00.00', 'Investasi', 'ASSET', 'INVESTMENT_ASSET', '1.0.00.00', true],
        ['1.2.01.00', 'Investasi', 'ASSET', 'INVESTMENT_ASSET', '1.2.00.00', true],
        ['1.2.01.01', 'Deposito > 3 bulan', 'ASSET', 'INVESTMENT_ASSET', '1.2.01.00', false],
        ['1.2.01.99', 'Investasi Lainnya', 'ASSET', 'INVESTMENT_ASSET', '1.2.01.00', false],
        ['1.3.00.00', 'Aset Tetap', 'ASSET', 'FIXED_ASSET', '1.0.00.00', true],
        ['1.3.01.00', 'Tanah', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.01.01', 'Tanah', 'ASSET', 'FIXED_ASSET', '1.3.01.00', false],
        ['1.3.02.00', 'Kendaraan', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.02.01', 'Kendaraan', 'ASSET', 'FIXED_ASSET', '1.3.02.00', false],
        ['1.3.03.00', 'Peralatan dan Mesin', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.03.01', 'Peralatan dan Mesin', 'ASSET', 'FIXED_ASSET', '1.3.03.00', false],
        ['1.3.04.00', 'Meubelair', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.04.01', 'Meubelair', 'ASSET', 'FIXED_ASSET', '1.3.04.00', false],
        ['1.3.05.00', 'Gedung dan Bangunan', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.05.01', 'Gedung dan Bangunan', 'ASSET', 'FIXED_ASSET', '1.3.05.00', false],
        ['1.3.06.00', 'Konstruksi Dalam Pengerjaan', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.06.01', 'Konstruksi Dalam Pengerjaan', 'ASSET', 'FIXED_ASSET', '1.3.06.00', false],
        ['1.3.07.00', 'Akumulasi Penyusutan Aset Tetap', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.07.01', 'Akumulasi Penyusutan Kendaraan', 'ASSET', 'FIXED_ASSET', '1.3.07.00', false],
        ['1.3.07.02', 'Akumulasi Penyusutan Peralatan dan Mesin', 'ASSET', 'FIXED_ASSET', '1.3.07.00', false],
        ['1.3.07.03', 'Akumulasi Penyusutan Meubelair', 'ASSET', 'FIXED_ASSET', '1.3.07.00', false],
        ['1.3.07.04', 'Akumulasi Penyusutan Gedung dan Bangunan', 'ASSET', 'FIXED_ASSET', '1.3.07.00', false],
        ['1.3.99.00', 'Aset Tetap Lainnya', 'ASSET', 'FIXED_ASSET', '1.3.00.00', true],
        ['1.3.99.99', 'Aset Tetap Lainnya', 'ASSET', 'FIXED_ASSET', '1.3.99.00', false],
        ['1.4.00.00', 'Aset Takberwujud', 'ASSET', 'INTANGIBLE_ASSET', '1.0.00.00', true],
        ['1.4.01.00', 'Aset Takberwujud', 'ASSET', 'INTANGIBLE_ASSET', '1.4.00.00', true],
        ['1.4.01.01', 'Software', 'ASSET', 'INTANGIBLE_ASSET', '1.4.01.00', false],
        ['1.4.01.02', 'Patent', 'ASSET', 'INTANGIBLE_ASSET', '1.4.01.00', false],
        ['1.4.01.03', 'Trademark', 'ASSET', 'INTANGIBLE_ASSET', '1.4.01.00', false],
        ['1.4.02.00', 'Amortisasi Aset Takberwujud', 'ASSET', 'INTANGIBLE_ASSET', '1.4.00.00', true],
        ['1.4.02.01', 'Amortisasi Aset Takberwujud', 'ASSET', 'INTANGIBLE_ASSET', '1.4.02.00', false],
        ['1.9.00.00', 'Aset Lain-lain', 'ASSET', 'OTHER_ASSET', '1.0.00.00', true],
        ['1.9.01.00', 'Aset Lain-lain', 'ASSET', 'OTHER_ASSET', '1.9.00.00', true],
        ['1.9.01.01', 'Aset Lain-lain', 'ASSET', 'OTHER_ASSET', '1.9.01.00', false],
        ['1.9.02.00', 'Akumulasi Penyusutan Aset Lain-lain', 'ASSET', 'OTHER_ASSET', '1.9.00.00', true],
        ['1.9.02.01', 'Akumulasi Penyusutan Aset Lain-lain', 'ASSET', 'OTHER_ASSET', '1.9.02.00', false],
        ['2.0.00.00', 'KEWAJIBAN', 'LIABILITY', 'OTHER_LIABILITY', null, true],
        ['2.1.00.00', 'Kewajiban Jangka Pendek', 'LIABILITY', 'CURRENT_LIABILITY', '2.0.00.00', true],
        ['2.1.01.00', 'Utang Usaha', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.01.01', 'Utang Usaha', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.01.00', false],
        ['2.1.02.00', 'Utang Pajak', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.02.01', 'PPN Keluaran', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.02.00', false],
        ['2.1.02.02', 'PPh 21', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.02.00', false],
        ['2.1.02.03', 'PPh 23', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.02.00', false],
        ['2.1.02.04', 'PPh 29', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.02.00', false],
        ['2.1.03.00', 'Utang Gaji/Upah dan Tunjangan', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.03.01', 'Utang Gaji dan Tunjangan', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.03.00', false],
        ['2.1.03.02', 'Utang Gaji/Upah Karyawan', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.03.00', false],
        ['2.1.04.00', 'Utang Utilitas', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.04.01', 'Utang Listrik', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.04.00', false],
        ['2.1.04.02', 'Utang Telepon/Internet', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.04.00', false],
        ['2.1.04.99', 'Utang Utilitas Lainnya', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.04.00', false],
        ['2.1.05.00', 'Utang kepada Pihak Ketiga Jangka Pendek', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.05.01', 'Utang kepada Pihak Ketiga Jangka Pendek', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.05.00', false],
        ['2.1.05.99', 'Utang kepada Pihak Ketiga Jangka Pendek Lainnya', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.05.00', false],
        ['2.1.99.00', 'Utang Jangka Pendek Lainnya', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.00.00', true],
        ['2.1.99.99', 'Utang Jangka Pendek Lainnya', 'LIABILITY', 'CURRENT_LIABILITY', '2.1.99.00', false],
        ['2.2.00.00', 'Kewajiban Jangka Panjang', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.0.00.00', true],
        ['2.2.01.00', 'Utang Ke Bank', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.00.00', true],
        ['2.2.01.01', 'Utang Ke Bank', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.01.00', false],
        ['2.2.02.00', 'Utang kepada Pihak Ketiga Jangka Panjang', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.00.00', true],
        ['2.2.02.01', 'Utang kepada Pihak Ketiga Jangka Panjang', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.02.00', false],
        ['2.2.99.00', 'Utang Jangka Panjang Lainnya', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.00.00', true],
        ['2.2.99.99', 'Utang Jangka Panjang Lainnya', 'LIABILITY', 'LONG_TERM_LIABILITY', '2.2.99.00', false],
        ['3.0.00.00', 'EKUITAS', 'EQUITY', 'OWNER_EQUITY', null, true],
        ['3.1.00.00', 'Modal Pemilik', 'EQUITY', 'OWNER_EQUITY', '3.0.00.00', true],
        ['3.1.01.00', 'Penyertaan Modal Desa', 'EQUITY', 'OWNER_EQUITY', '3.1.00.00', true],
        ['3.1.01.01', 'Penyertaan Modal Desa', 'EQUITY', 'OWNER_EQUITY', '3.1.01.00', false],
        ['3.1.02.00', 'Penyertaan Modal Masyarakat', 'EQUITY', 'OWNER_EQUITY', '3.1.00.00', true],
        ['3.1.02.01', 'Penyertaan Modal Masyarakat A', 'EQUITY', 'OWNER_EQUITY', '3.1.02.00', false],
        ['3.2.00.00', 'Pengambilan oleh Pemilik', 'EQUITY', 'OTHER_EQUITY', '3.0.00.00', true],
        ['3.2.01.00', 'Bagi Hasil Penyertaan Modal Desa', 'EQUITY', 'OTHER_EQUITY', '3.2.00.00', true],
        ['3.2.01.01', 'Bagi Hasil Penyertaan Modal Desa', 'EQUITY', 'OTHER_EQUITY', '3.2.01.00', false],
        ['3.2.02.00', 'Bagi Hasil Penyertaan Modal Masyarakat', 'EQUITY', 'OTHER_EQUITY', '3.2.00.00', true],
        ['3.2.02.01', 'Bagi Hasil Penyertaan Modal Masyarakat', 'EQUITY', 'OTHER_EQUITY', '3.2.02.00', false],
        ['3.3.00.00', 'Saldo Laba', 'EQUITY', 'RETAINED_EARNINGS', '3.0.00.00', true],
        ['3.3.01.00', 'Saldo Laba', 'EQUITY', 'RETAINED_EARNINGS', '3.3.00.00', true],
        ['3.3.01.01', 'Saldo Laba Tidak Dicadangkan', 'EQUITY', 'RETAINED_EARNINGS', '3.3.01.00', false],
        ['3.3.01.02', 'Saldo Laba Dicadangkan untuk Pembelian Aset Tetap', 'EQUITY', 'RETAINED_EARNINGS', '3.3.01.00', false],
        ['3.3.01.03', 'Saldo Laba Dicadangkan untuk Pembayaran Utang Jangka Panjang', 'EQUITY', 'RETAINED_EARNINGS', '3.3.01.00', false],
        ['3.4.00.00', 'Modal Donasi/Sumbangan', 'EQUITY', 'OTHER_EQUITY', '3.0.00.00', true],
        ['3.4.01.00', 'Modal Donasi/Sumbangan', 'EQUITY', 'OTHER_EQUITY', '3.4.00.00', true],
        ['3.4.01.01', 'Modal Donasi/Sumbangan', 'EQUITY', 'OTHER_EQUITY', '3.4.01.00', false],
        ['3.8.00.00', 'RK Pusat', 'EQUITY', 'OTHER_EQUITY', '3.0.00.00', true],
        ['3.8.01.01', 'RK Pusat', 'EQUITY', 'OTHER_EQUITY', '3.8.00.00', false],
        ['3.9.00.00', 'Ikhtisar Laba Rugi', 'EQUITY', 'RETAINED_EARNINGS', '3.0.00.00', true],
        ['3.9.01.00', 'Ikhtisar Laba Rugi', 'EQUITY', 'RETAINED_EARNINGS', '3.9.00.00', true],
        ['3.9.01.01', 'Ikhtisar Laba Rugi', 'EQUITY', 'RETAINED_EARNINGS', '3.9.01.00', false],
        ['4.0.00.00', 'PENDAPATAN USAHA', 'REVENUE', 'OPERATING_REVENUE', null, true],
        ['4.1.00.00', 'Pendapatan Jasa', 'REVENUE', 'OPERATING_REVENUE', '4.0.00.00', true],
        ['4.1.01.00', 'Pendapatan Wisata', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.01.01', 'Pendapatan Tiket', 'REVENUE', 'OPERATING_REVENUE', '4.1.01.00', false],
        ['4.1.01.02', 'Pendapatan Wahana', 'REVENUE', 'OPERATING_REVENUE', '4.1.01.00', false],
        ['4.1.01.03', 'Pendapatan Paket Wisata', 'REVENUE', 'OPERATING_REVENUE', '4.1.01.00', false],
        ['4.1.02.00', 'Pendapatan Pengelolaan Air Bersih', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.02.01', 'Pendapatan Pengelolaan Air Bersih', 'REVENUE', 'OPERATING_REVENUE', '4.1.02.00', false],
        ['4.1.03.00', 'Pendapatan Pengelolaan Sampah', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.03.01', 'Pendapatan Pengelolaan Sampah', 'REVENUE', 'OPERATING_REVENUE', '4.1.03.00', false],
        ['4.1.04.00', 'Pendapatan Sewa', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.04.01', 'Pendapatan Sewa Tempat Outbound', 'REVENUE', 'OPERATING_REVENUE', '4.1.04.00', false],
        ['4.1.04.02', 'Pendapatan Sewa Tempat untuk Toko/Kios', 'REVENUE', 'OPERATING_REVENUE', '4.1.04.00', false],
        ['4.1.04.03', 'Pendapatan Sewa Gedung', 'REVENUE', 'OPERATING_REVENUE', '4.1.04.00', false],
        ['4.1.04.04', 'Pendapatan Sewa Mobil', 'REVENUE', 'OPERATING_REVENUE', '4.1.04.00', false],
        ['4.1.04.99', 'Pendapatan Sewa Lainnya', 'REVENUE', 'OPERATING_REVENUE', '4.1.04.00', false],
        ['4.1.05.00', 'Pendapatan Jasa Pelayanan', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.05.01', 'Pendapatan Jasa Pembayaran Listrik', 'REVENUE', 'OPERATING_REVENUE', '4.1.05.00', false],
        ['4.1.05.99', 'Pendapatan Jasa Pelayanan Lainnya', 'REVENUE', 'OPERATING_REVENUE', '4.1.05.00', false],
        ['4.1.06.00', 'Pendapatan Transportasi', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.06.01', 'Pendapatan Transportasi', 'REVENUE', 'OPERATING_REVENUE', '4.1.06.00', false],
        ['4.1.07.00', 'Pendapatan Parkir', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.07.01', 'Pendapatan Parkir Mobil', 'REVENUE', 'OPERATING_REVENUE', '4.1.07.00', false],
        ['4.1.07.02', 'Pendapatan Parkir Motor', 'REVENUE', 'OPERATING_REVENUE', '4.1.07.00', false],
        ['4.1.08.00', 'Pendapatan Simpan Pinjam', 'REVENUE', 'OPERATING_REVENUE', '4.1.00.00', true],
        ['4.1.08.01', 'Pendapatan Simpan Pinjam', 'REVENUE', 'OPERATING_REVENUE', '4.1.08.00', false],
        ['4.2.00.00', 'Pendapatan Penjualan Barang Dagangan', 'REVENUE', 'OPERATING_REVENUE', '4.0.00.00', true],
        ['4.2.01.00', 'Pendapatan Penjualan Barang Dagangan', 'REVENUE', 'OPERATING_REVENUE', '4.2.00.00', true],
        ['4.2.01.01', 'Pendapatan Penjualan Makanan/Minuman', 'REVENUE', 'OPERATING_REVENUE', '4.2.01.00', false],
        ['4.2.01.99', 'Diskon Penjualan Barang Dagangan', 'REVENUE', 'OPERATING_REVENUE', '4.2.01.00', false],
        ['4.3.00.00', 'Pendapatan Penjualan Barang Jadi', 'REVENUE', 'OPERATING_REVENUE', '4.0.00.00', true],
        ['4.3.01.00', 'Pendapatan Penjualan Barang Jadi', 'REVENUE', 'OPERATING_REVENUE', '4.3.00.00', true],
        ['4.3.01.01', 'Pendapatan Katering', 'REVENUE', 'OPERATING_REVENUE', '4.3.01.00', false],
        ['4.3.01.02', 'Pendapatan Restoran', 'REVENUE', 'OPERATING_REVENUE', '4.3.01.00', false],
        ['5.0.00.00', 'HARGA POKOK PRODUKSI DAN PENJUALAN', 'EXPENSE', 'COST_OF_GOODS_SOLD', null, true],
        ['5.1.00.00', 'Harga Pokok Penjualan Barang Dagangan', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.0.00.00', true],
        ['5.1.01.00', 'Harga Pokok Penjualan Barang Dagangan', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.1.00.00', true],
        ['5.1.01.01', 'Harga Pokok Penjualan Barang Dagangan', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.1.01.00', false],
        ['5.2.00.00', 'Harga Pokok Penjualan Barang Jadi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.0.00.00', true],
        ['5.2.01.00', 'Harga Pokok Penjualan Barang Jadi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.2.00.00', true],
        ['5.2.01.01', 'Harga Pokok Penjualan Barang Jadi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.2.01.00', false],
        ['5.3.00.00', 'Harga Pokok Produksi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.0.00.00', true],
        ['5.3.01.00', 'Harga Pokok Produksi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.3.00.00', true],
        ['5.3.01.01', 'Harga Pokok Produksi', 'EXPENSE', 'COST_OF_GOODS_SOLD', '5.3.01.00', false],
        ['6.0.00.00', 'BEBAN-BEBAN USAHA', 'EXPENSE', 'OPERATING_EXPENSE', null, true],
        ['6.1.00.00', 'Beban Administrasi dan Umum', 'EXPENSE', 'ADMIN_EXPENSE', '6.0.00.00', true],
        ['6.1.01.00', 'Beban Pegawai Bagian Administrasi Umum', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.01.01', 'Beban Gaji dan Tunjangan Bag. Adum', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.01.00', false],
        ['6.1.01.02', 'Beban Honor Lembur Bag. Adum', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.01.00', false],
        ['6.1.01.03', 'Beban Honor Narasumber', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.01.00', false],
        ['6.1.02.00', 'Beban Perlengkapan', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.02.01', 'Beban Alat Tulis Kantor (ATK)', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.02.00', false],
        ['6.1.02.02', 'Beban Foto Copy', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.02.00', false],
        ['6.1.02.03', 'Beban Konsumsi Rapat', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.02.00', false],
        ['6.1.03.00', 'Beban Pemeliharaan dan Perbaikan', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.03.01', 'Beban Pemeliharaan dan Perbaikan', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.03.00', false],
        ['6.1.04.00', 'Beban Utilitas', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.04.01', 'Beban Listrik', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.04.00', false],
        ['6.1.04.02', 'Beban Telepon/Internet', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.04.00', false],
        ['6.1.05.00', 'Beban Sewa dan Asuransi', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.05.01', 'Beban Sewa', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.05.00', false],
        ['6.1.05.02', 'Beban Asuransi', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.05.00', false],
        ['6.1.07.00', 'Beban Penyisihan dan Penyusutan/Amortisasi', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.07.01', 'Beban Penyisihan Piutang Tak Tertagih', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.07.02', 'Beban Penyusutan Kendaraan', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.07.03', 'Beban Penyusutan Peralatan dan Mesin', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.07.04', 'Beban Penyusutan Meubelair', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.07.05', 'Beban Penyusutan Gedung dan Bangunan', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.07.06', 'Beban Amortisasi Aset Takberwujud', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.07.00', false],
        ['6.1.99.00', 'Beban Administrasi dan Umum Lainnya', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.00.00', true],
        ['6.1.99.99', 'Beban Administrasi dan Umum Lainnya', 'EXPENSE', 'ADMIN_EXPENSE', '6.1.99.00', false],
        ['6.2.00.00', 'Beban Operasional', 'EXPENSE', 'OPERATING_EXPENSE', '6.0.00.00', true],
        ['6.2.01.00', 'Beban Pegawai Bagian Operasional', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.00.00', true],
        ['6.2.01.01', 'Beban Gaji/Upah Bag. Operasional', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.01.00', false],
        ['6.2.01.02', 'Beban Uang Makan Bag. Operasional', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.01.00', false],
        ['6.2.02.00', 'Beban Pemeliharaan dan Perbaikan', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.00.00', true],
        ['6.2.02.01', 'Beban Pemeliharaan Wahana', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.02.00', false],
        ['6.2.02.02', 'Beban Perbaikan dan Renovasi', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.02.00', false],
        ['6.2.99.00', 'Beban Operasional Lainnya', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.00.00', true],
        ['6.2.99.99', 'Beban Operasional Lainnya', 'EXPENSE', 'OPERATING_EXPENSE', '6.2.99.00', false],
        ['6.3.00.00', 'Beban Pemasaran', 'EXPENSE', 'MARKETING_EXPENSE', '6.0.00.00', true],
        ['6.3.01.00', 'Beban Pegawai Bagian Pemasaran', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.00.00', true],
        ['6.3.01.01', 'Beban Gaji/Upah Bag. Pemasaran', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.01.00', false],
        ['6.3.02.00', 'Beban Iklan dan Promosi', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.00.00', true],
        ['6.3.02.01', 'Beban Iklan', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.02.00', false],
        ['6.3.99.00', 'Beban Pemasaran Lainnya', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.00.00', true],
        ['6.3.99.99', 'Beban Pemasaran Lainnya', 'EXPENSE', 'MARKETING_EXPENSE', '6.3.99.00', false],
        ['7.0.00.00', 'PENDAPATAN DAN BEBAN LAIN-LAIN', 'REVENUE', 'OTHER_REVENUE', null, true],
        ['7.1.00.00', 'Pendapatan Lain-lain', 'REVENUE', 'OTHER_REVENUE', '7.0.00.00', true],
        ['7.1.01.00', 'Pendapatan dari Bank', 'REVENUE', 'NON_OPERATING_REVENUE', '7.1.00.00', true],
        ['7.1.01.01', 'Pendapatan Bunga Bank', 'REVENUE', 'NON_OPERATING_REVENUE', '7.1.01.00', false],
        ['7.1.01.02', 'Pendapatan Fee Agen BNI 46', 'REVENUE', 'NON_OPERATING_REVENUE', '7.1.01.00', false],
        ['7.1.02.00', 'Pendapatan Dividen', 'REVENUE', 'NON_OPERATING_REVENUE', '7.1.00.00', true],
        ['7.1.02.01', 'Pendapatan Dividen', 'REVENUE', 'NON_OPERATING_REVENUE', '7.1.02.00', false],
        ['7.1.03.00', 'Pendapatan Denda', 'REVENUE', 'OTHER_REVENUE', '7.1.00.00', true],
        ['7.1.03.01', 'Pendapatan Denda', 'REVENUE', 'OTHER_REVENUE', '7.1.03.00', false],
        ['7.1.05.00', 'Pendapatan Penjualan Aset Tetap', 'REVENUE', 'OTHER_REVENUE', '7.1.00.00', true],
        ['7.1.05.01', 'Keuntungan Penjualan Aset Tetap', 'REVENUE', 'OTHER_REVENUE', '7.1.05.00', false],
        ['7.1.99.00', 'Pendapatan Lain-lain Lainnya', 'REVENUE', 'OTHER_REVENUE', '7.1.00.00', true],
        ['7.1.99.99', 'Pendapatan Lain-lain Lainnya', 'REVENUE', 'OTHER_REVENUE', '7.1.99.00', false],
        ['7.2.00.00', 'Beban Lain-lain', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.0.00.00', true],
        ['7.2.01.00', 'Beban Bank', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.00.00', true],
        ['7.2.01.01', 'Beban Administrasi Bank', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.01.00', false],
        ['7.2.02.00', 'Beban Bunga', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.00.00', true],
        ['7.2.02.01', 'Beban Bunga', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.02.00', false],
        ['7.2.03.00', 'Beban Denda', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.00.00', true],
        ['7.2.03.01', 'Beban Denda', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.03.00', false],
        ['7.2.04.00', 'Beban Penjualan Aset Tetap', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.00.00', true],
        ['7.2.04.01', 'Kerugian Penjualan Aset Tetap', 'EXPENSE', 'NON_OPERATING_EXPENSE', '7.2.04.00', false],
        ['7.2.99.00', 'Beban Lain-lain Lainnya', 'EXPENSE', 'OTHER_EXPENSE', '7.2.00.00', true],
        ['7.2.99.99', 'Beban Lain-lain Lainnya', 'EXPENSE', 'OTHER_EXPENSE', '7.2.99.00', false],
        ['7.3.00.00', 'Beban Pajak', 'EXPENSE', 'TAX_EXPENSE', '7.0.00.00', true],
        ['7.3.01.00', 'Beban Pajak', 'EXPENSE', 'TAX_EXPENSE', '7.3.00.00', true],
        ['7.3.01.01', 'Beban Pajak Air Permukaan', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.02', 'Beban Pajak Bunga Bank', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.03', 'Beban Pajak Daerah', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.06', 'Beban PPh 21', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.07', 'Beban PPh 23', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.08', 'Beban PPh 25', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.09', 'Beban PPh 29', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.10', 'Beban PPh Final', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
        ['7.3.01.99', 'Beban Pajak Lainnya', 'EXPENSE', 'TAX_EXPENSE', '7.3.01.00', false],
    ];

    return array_map(static fn (array $row): array => [
        'account_code' => $row[0],
        'account_name' => $row[1],
        'account_type' => $row[2],
        'account_category' => $row[3],
        'parent_code' => $row[4],
        'is_header' => $row[5],
        'is_active' => true,
    ], $rows);
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
