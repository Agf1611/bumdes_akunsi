CREATE TABLE IF NOT EXISTS reference_partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_code VARCHAR(30) NOT NULL DEFAULT '',
    partner_name VARCHAR(150) NOT NULL,
    partner_type ENUM('CUSTOMER','VENDOR','DEBTOR','CREDITOR','OTHER') NOT NULL DEFAULT 'OTHER',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reference_partners_type (partner_type),
    INDEX idx_reference_partners_active (is_active),
    INDEX idx_reference_partners_name (partner_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(30) NOT NULL DEFAULT '',
    item_name VARCHAR(150) NOT NULL,
    unit_name VARCHAR(40) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inventory_items_active (is_active),
    INDEX idx_inventory_items_name (item_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS raw_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_code VARCHAR(30) NOT NULL DEFAULT '',
    material_name VARCHAR(150) NOT NULL,
    unit_name VARCHAR(40) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_raw_materials_active (is_active),
    INDEX idx_raw_materials_name (material_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saving_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_no VARCHAR(40) NOT NULL DEFAULT '',
    account_name VARCHAR(150) NOT NULL,
    owner_name VARCHAR(150) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_saving_accounts_active (is_active),
    INDEX idx_saving_accounts_name (account_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashflow_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    component_code VARCHAR(30) NOT NULL,
    component_name VARCHAR(150) NOT NULL,
    cashflow_group ENUM('OPERATING','INVESTING','FINANCING') NOT NULL DEFAULT 'OPERATING',
    direction ENUM('IN','OUT') NOT NULL DEFAULT 'IN',
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_cashflow_components_code UNIQUE (component_code),
    INDEX idx_cashflow_components_group (cashflow_group),
    INDEX idx_cashflow_components_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE journal_lines
    ADD COLUMN IF NOT EXISTS partner_id INT UNSIGNED NULL AFTER credit,
    ADD COLUMN IF NOT EXISTS inventory_item_id INT UNSIGNED NULL AFTER partner_id,
    ADD COLUMN IF NOT EXISTS raw_material_id INT UNSIGNED NULL AFTER inventory_item_id,
    ADD COLUMN IF NOT EXISTS asset_id INT UNSIGNED NULL AFTER raw_material_id,
    ADD COLUMN IF NOT EXISTS saving_account_id INT UNSIGNED NULL AFTER asset_id,
    ADD COLUMN IF NOT EXISTS cashflow_component_id INT UNSIGNED NULL AFTER saving_account_id,
    ADD COLUMN IF NOT EXISTS entry_tag VARCHAR(30) NOT NULL DEFAULT 'OPERASIONAL' AFTER cashflow_component_id;

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'OP-IN-SALES', 'Penerimaan dari penjualan / jasa', 'OPERATING', 'IN', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-IN-SALES');
INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'OP-OUT-OPEX', 'Pembayaran beban operasional', 'OPERATING', 'OUT', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-OUT-OPEX');
INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'INV-OUT-ASSET', 'Pembelian aset tetap / investasi', 'INVESTING', 'OUT', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'INV-OUT-ASSET');
INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'INV-IN-ASSET', 'Penerimaan penjualan aset / investasi', 'INVESTING', 'IN', 40, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'INV-IN-ASSET');
INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'FIN-IN-LOAN', 'Penerimaan pinjaman / modal', 'FINANCING', 'IN', 50, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'FIN-IN-LOAN');
INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, is_active)
SELECT 'FIN-OUT-LOAN', 'Pembayaran pinjaman / pembagian hasil', 'FINANCING', 'OUT', 60, 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'FIN-OUT-LOAN');
