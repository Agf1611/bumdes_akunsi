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
