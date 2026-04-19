CREATE TABLE IF NOT EXISTS coa_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(30) NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    account_type ENUM('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
    account_category VARCHAR(50) NOT NULL,
    parent_id INT UNSIGNED NULL,
    is_header TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_coa_account_code UNIQUE (account_code),
    CONSTRAINT fk_coa_parent FOREIGN KEY (parent_id) REFERENCES coa_accounts(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_coa_type (account_type),
    INDEX idx_coa_active (is_active),
    INDEX idx_coa_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.000', 'Aset', 'ASSET', 'OTHER_ASSET', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.000');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.101', 'Kas', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.101');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.000', 'Liabilitas', 'LIABILITY', 'OTHER_LIABILITY', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.000');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.000', 'Ekuitas', 'EQUITY', 'OWNER_EQUITY', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.000');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.000', 'Pendapatan', 'REVENUE', 'OPERATING_REVENUE', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.000');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.000', 'Beban', 'EXPENSE', 'OPERATING_EXPENSE', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.000');

INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.102', 'Bank', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.102');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.103', 'Kas Kecil', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.103');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.104', 'Setara Kas', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.104');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.110', 'Piutang Usaha', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.110');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.111', 'Piutang Lain-lain', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.111');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.120', 'Persediaan Barang Dagang', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.120');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.121', 'Persediaan Bahan / Perlengkapan', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.121');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.130', 'Uang Muka Pembelian', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.130');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.131', 'Beban Dibayar Dimuka', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.131');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.140', 'Pajak Dibayar Dimuka', 'ASSET', 'CURRENT_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.140');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.201', 'Tanah', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.201');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.202', 'Bangunan', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.202');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.203', 'Peralatan dan Mesin', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.203');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.204', 'Kendaraan', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.204');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.205', 'Inventaris Kantor', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.205');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.291', 'Akumulasi Penyusutan Bangunan', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.291');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.292', 'Akumulasi Penyusutan Peralatan dan Mesin', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.292');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.293', 'Akumulasi Penyusutan Kendaraan', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.293');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '1.294', 'Akumulasi Penyusutan Inventaris Kantor', 'ASSET', 'FIXED_ASSET', (SELECT id FROM coa_accounts WHERE account_code = '1.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '1.294');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.101', 'Utang Usaha', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.101');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.102', 'Utang Lain-lain', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.102');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.103', 'Utang Gaji dan Honor', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.103');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.104', 'Utang Pajak', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.104');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.105', 'Biaya Masih Harus Dibayar', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.105');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.106', 'Pendapatan Diterima Dimuka', 'LIABILITY', 'CURRENT_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.106');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '2.201', 'Utang Bank / Pinjaman Jangka Panjang', 'LIABILITY', 'LONG_TERM_LIABILITY', (SELECT id FROM coa_accounts WHERE account_code = '2.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '2.201');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.101', 'Penyertaan Modal Desa', 'EQUITY', 'OWNER_EQUITY', (SELECT id FROM coa_accounts WHERE account_code = '3.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.101');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.102', 'Penyertaan Modal Masyarakat / Pihak Ketiga', 'EQUITY', 'OWNER_EQUITY', (SELECT id FROM coa_accounts WHERE account_code = '3.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.102');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.103', 'Cadangan Umum', 'EQUITY', 'OWNER_EQUITY', (SELECT id FROM coa_accounts WHERE account_code = '3.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.103');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.104', 'Laba Ditahan', 'EQUITY', 'RETAINED_EARNINGS', (SELECT id FROM coa_accounts WHERE account_code = '3.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.104');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '3.105', 'Saldo Laba Tahun Berjalan', 'EQUITY', 'RETAINED_EARNINGS', (SELECT id FROM coa_accounts WHERE account_code = '3.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '3.105');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.101', 'Pendapatan Penjualan', 'REVENUE', 'OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.101');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.102', 'Pendapatan Jasa', 'REVENUE', 'OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.102');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.103', 'Pendapatan Administrasi', 'REVENUE', 'OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.103');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.104', 'Pendapatan Sewa', 'REVENUE', 'OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.104');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.105', 'Pendapatan Komisi / Fee', 'REVENUE', 'OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.105');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.201', 'Pendapatan Bunga / Jasa Giro', 'REVENUE', 'NON_OPERATING_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.201');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '4.202', 'Pendapatan Lain-lain', 'REVENUE', 'OTHER_REVENUE', (SELECT id FROM coa_accounts WHERE account_code = '4.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '4.202');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.101', 'Harga Pokok Penjualan', 'EXPENSE', 'OPERATING_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.101');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.102', 'Beban Gaji dan Honor', 'EXPENSE', 'ADMIN_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.102');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.103', 'Beban Listrik dan Air', 'EXPENSE', 'ADMIN_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.103');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.104', 'Beban Internet dan Telepon', 'EXPENSE', 'ADMIN_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.104');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.105', 'Beban ATK dan Administrasi', 'EXPENSE', 'ADMIN_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.105');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.106', 'Beban Transportasi', 'EXPENSE', 'OPERATING_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.106');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.107', 'Beban Perawatan', 'EXPENSE', 'OPERATING_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.107');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.108', 'Beban Sewa', 'EXPENSE', 'OPERATING_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.108');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.109', 'Beban Penyusutan', 'EXPENSE', 'OTHER_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.109');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.110', 'Beban Pajak dan Retribusi', 'EXPENSE', 'OTHER_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.110');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.111', 'Beban Bunga', 'EXPENSE', 'OTHER_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.111');
INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active)
SELECT '5.112', 'Beban Lain-lain', 'EXPENSE', 'OTHER_EXPENSE', (SELECT id FROM coa_accounts WHERE account_code = '5.000' LIMIT 1), 0, 1
WHERE NOT EXISTS (SELECT 1 FROM coa_accounts WHERE account_code = '5.112');
