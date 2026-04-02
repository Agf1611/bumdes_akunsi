CREATE TABLE IF NOT EXISTS reference_partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_code VARCHAR(40) NOT NULL,
    partner_name VARCHAR(150) NOT NULL,
    partner_type ENUM('CUSTOMER','VENDOR','BOTH','OTHER') NULL,
    phone VARCHAR(50) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reference_partners_code (partner_code),
    KEY idx_reference_partners_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(40) NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    unit_name VARCHAR(30) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_items_code (item_code),
    KEY idx_inventory_items_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS raw_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_code VARCHAR(40) NOT NULL,
    material_name VARCHAR(150) NOT NULL,
    unit_name VARCHAR(30) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_raw_materials_code (material_code),
    KEY idx_raw_materials_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saving_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_no VARCHAR(40) NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    saving_type ENUM('VOLUNTARY','MANDATORY','TIME','OTHER') NULL,
    owner_name VARCHAR(150) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saving_accounts_no (account_no),
    KEY idx_saving_accounts_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashflow_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    component_code VARCHAR(40) NOT NULL,
    component_name VARCHAR(150) NOT NULL,
    component_group ENUM('OPERATING_IN','OPERATING_OUT','INVESTING_IN','INVESTING_OUT','FINANCING_IN','FINANCING_OUT','OTHER') NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cashflow_components_code (component_code),
    KEY idx_cashflow_components_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE journal_lines
    ADD COLUMN IF NOT EXISTS partner_id INT UNSIGNED NULL AFTER credit,
    ADD COLUMN IF NOT EXISTS inventory_item_id INT UNSIGNED NULL AFTER partner_id,
    ADD COLUMN IF NOT EXISTS raw_material_id INT UNSIGNED NULL AFTER inventory_item_id,
    ADD COLUMN IF NOT EXISTS asset_id INT UNSIGNED NULL AFTER raw_material_id,
    ADD COLUMN IF NOT EXISTS saving_account_id INT UNSIGNED NULL AFTER asset_id,
    ADD COLUMN IF NOT EXISTS cashflow_component_id INT UNSIGNED NULL AFTER saving_account_id,
    ADD COLUMN IF NOT EXISTS entry_tag VARCHAR(30) NOT NULL DEFAULT '' AFTER cashflow_component_id;

ALTER TABLE journal_lines
    ADD INDEX IF NOT EXISTS idx_journal_lines_partner (partner_id),
    ADD INDEX IF NOT EXISTS idx_journal_lines_inventory_item (inventory_item_id),
    ADD INDEX IF NOT EXISTS idx_journal_lines_raw_material (raw_material_id),
    ADD INDEX IF NOT EXISTS idx_journal_lines_asset (asset_id),
    ADD INDEX IF NOT EXISTS idx_journal_lines_saving_account (saving_account_id),
    ADD INDEX IF NOT EXISTS idx_journal_lines_cashflow_component (cashflow_component_id);
