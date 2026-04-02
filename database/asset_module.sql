CREATE TABLE IF NOT EXISTS asset_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(30) NOT NULL,
    category_name VARCHAR(120) NOT NULL,
    asset_group ENUM('FIXED','BIOLOGICAL','OTHER') NOT NULL DEFAULT 'FIXED',
    default_useful_life_months INT UNSIGNED NULL,
    default_depreciation_method ENUM('STRAIGHT_LINE') NOT NULL DEFAULT 'STRAIGHT_LINE',
    depreciation_allowed TINYINT(1) NOT NULL DEFAULT 1,
    asset_coa_id INT UNSIGNED NULL,
    accumulated_depreciation_coa_id INT UNSIGNED NULL,
    depreciation_expense_coa_id INT UNSIGNED NULL,
    disposal_gain_coa_id INT UNSIGNED NULL,
    disposal_loss_coa_id INT UNSIGNED NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_asset_categories_code UNIQUE (category_code),
    INDEX idx_asset_categories_active (is_active),
    INDEX idx_asset_categories_group (asset_group),
    INDEX idx_asset_categories_asset_coa (asset_coa_id),
    INDEX idx_asset_categories_acc_dep (accumulated_depreciation_coa_id),
    INDEX idx_asset_categories_dep_exp (depreciation_expense_coa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(40) NOT NULL,
    asset_name VARCHAR(160) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    subcategory_name VARCHAR(120) NOT NULL DEFAULT '',
    business_unit_id INT UNSIGNED NULL,
    quantity DECIMAL(18,2) NOT NULL DEFAULT 1.00,
    unit_name VARCHAR(30) NOT NULL DEFAULT 'unit',
    entry_mode ENUM('OPENING','ACQUISITION') NOT NULL DEFAULT 'ACQUISITION',
    acquisition_date DATE NOT NULL,
    acquisition_cost DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    opening_as_of_date DATE NULL,
    opening_accumulated_depreciation DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    residual_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    useful_life_months INT UNSIGNED NULL,
    depreciation_method ENUM('STRAIGHT_LINE') NOT NULL DEFAULT 'STRAIGHT_LINE',
    depreciation_start_date DATE NULL,
    depreciation_allowed TINYINT(1) NOT NULL DEFAULT 1,
    offset_coa_id INT UNSIGNED NULL,
    location VARCHAR(150) NOT NULL DEFAULT '',
    supplier_name VARCHAR(150) NOT NULL DEFAULT '',
    source_of_funds ENUM('DANA_DESA','HASIL_USAHA','HIBAH_BANTUAN','PENYERTAAN_MODAL','PINJAMAN','SWADAYA','LAINNYA') NOT NULL DEFAULT 'HASIL_USAHA',
    funding_source_detail VARCHAR(150) NOT NULL DEFAULT '',
    reference_no VARCHAR(100) NOT NULL DEFAULT '',
    linked_journal_id INT UNSIGNED NULL,
    condition_status ENUM('EXCELLENT','GOOD','FAIR','POOR','DAMAGED') NOT NULL DEFAULT 'GOOD',
    asset_status ENUM('ACTIVE','IDLE','MAINTENANCE','NONACTIVE','SOLD','DAMAGED','DISPOSED') NOT NULL DEFAULT 'ACTIVE',
    acquisition_sync_status ENUM('NONE','READY','POSTED') NOT NULL DEFAULT 'NONE',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    description TEXT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_asset_items_code UNIQUE (asset_code),
    INDEX idx_asset_items_category (category_id),
    INDEX idx_asset_items_business_unit (business_unit_id),
    INDEX idx_asset_items_status (asset_status),
    INDEX idx_asset_items_active (is_active),
    INDEX idx_asset_items_acquisition_date (acquisition_date),
    INDEX idx_asset_items_source_of_funds (source_of_funds),
    INDEX idx_asset_items_offset_coa (offset_coa_id),
    INDEX idx_asset_items_entry_mode (entry_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_mutations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    mutation_date DATE NOT NULL,
    mutation_type ENUM('ACQUISITION','UPDATE','STATUS_CHANGE','TRANSFER_UNIT','TRANSFER_LOCATION','MAINTENANCE','SELL','DAMAGE','DISPOSE') NOT NULL,
    from_business_unit_id INT UNSIGNED NULL,
    to_business_unit_id INT UNSIGNED NULL,
    from_location VARCHAR(150) NOT NULL DEFAULT '',
    to_location VARCHAR(150) NOT NULL DEFAULT '',
    old_status VARCHAR(30) NOT NULL DEFAULT '',
    new_status VARCHAR(30) NOT NULL DEFAULT '',
    reference_no VARCHAR(100) NOT NULL DEFAULT '',
    linked_journal_id INT UNSIGNED NULL,
    amount DECIMAL(18,2) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset_mutations_asset (asset_id),
    INDEX idx_asset_mutations_date (mutation_date),
    INDEX idx_asset_mutations_type (mutation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_depreciations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    period_year SMALLINT UNSIGNED NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL,
    depreciation_date DATE NOT NULL,
    depreciation_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    accumulated_depreciation DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    book_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status ENUM('CALCULATED','POSTED') NOT NULL DEFAULT 'CALCULATED',
    linked_journal_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_asset_depreciations_period UNIQUE (asset_id, period_year, period_month),
    INDEX idx_asset_depreciations_date (depreciation_date),
    INDEX idx_asset_depreciations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_accounting_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    event_type ENUM('OPENING','ACQUISITION','DEPRECIATION','DISPOSAL','SALE','ADJUSTMENT') NOT NULL,
    event_date DATE NOT NULL,
    period_year SMALLINT UNSIGNED NULL,
    period_month TINYINT UNSIGNED NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    journal_id INT UNSIGNED NULL,
    status ENUM('DRAFT','POSTED','CANCELED') NOT NULL DEFAULT 'POSTED',
    description VARCHAR(255) NOT NULL DEFAULT '',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset_accounting_events_asset (asset_id),
    INDEX idx_asset_accounting_events_type (event_type),
    INDEX idx_asset_accounting_events_date (event_date),
    INDEX idx_asset_accounting_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_year_snapshots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    snapshot_year SMALLINT UNSIGNED NOT NULL,
    snapshot_date DATE NOT NULL,
    business_unit_id INT UNSIGNED NULL,
    acquisition_cost DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    accumulated_depreciation DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    book_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    asset_status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_asset_year_snapshots UNIQUE (asset_id, snapshot_year),
    INDEX idx_asset_year_snapshots_year (snapshot_year),
    INDEX idx_asset_year_snapshots_unit (business_unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE asset_categories
    ADD COLUMN IF NOT EXISTS asset_coa_id INT UNSIGNED NULL AFTER depreciation_allowed,
    ADD COLUMN IF NOT EXISTS accumulated_depreciation_coa_id INT UNSIGNED NULL AFTER asset_coa_id,
    ADD COLUMN IF NOT EXISTS depreciation_expense_coa_id INT UNSIGNED NULL AFTER accumulated_depreciation_coa_id,
    ADD COLUMN IF NOT EXISTS disposal_gain_coa_id INT UNSIGNED NULL AFTER depreciation_expense_coa_id,
    ADD COLUMN IF NOT EXISTS disposal_loss_coa_id INT UNSIGNED NULL AFTER disposal_gain_coa_id;

ALTER TABLE asset_items
    ADD COLUMN IF NOT EXISTS quantity DECIMAL(18,2) NOT NULL DEFAULT 1.00 AFTER business_unit_id,
    ADD COLUMN IF NOT EXISTS unit_name VARCHAR(30) NOT NULL DEFAULT 'unit' AFTER quantity,
    ADD COLUMN IF NOT EXISTS entry_mode ENUM('OPENING','ACQUISITION') NOT NULL DEFAULT 'ACQUISITION' AFTER unit_name,
    ADD COLUMN IF NOT EXISTS opening_as_of_date DATE NULL AFTER acquisition_cost,
    ADD COLUMN IF NOT EXISTS opening_accumulated_depreciation DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER opening_as_of_date,
    ADD COLUMN IF NOT EXISTS offset_coa_id INT UNSIGNED NULL AFTER depreciation_allowed,
    ADD COLUMN IF NOT EXISTS acquisition_sync_status ENUM('NONE','READY','POSTED') NOT NULL DEFAULT 'NONE' AFTER asset_status,
    ADD COLUMN IF NOT EXISTS source_of_funds ENUM('DANA_DESA','HASIL_USAHA','HIBAH_BANTUAN','PENYERTAAN_MODAL','PINJAMAN','SWADAYA','LAINNYA') NOT NULL DEFAULT 'HASIL_USAHA' AFTER supplier_name,
    ADD COLUMN IF NOT EXISTS funding_source_detail VARCHAR(150) NOT NULL DEFAULT '' AFTER source_of_funds;

INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'LAND', 'Tanah', 'FIXED', NULL, 'STRAIGHT_LINE', 0, 'Tanah / lahan usaha dan lahan kantor.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'LAND');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'BUILDING', 'Bangunan', 'FIXED', 240, 'STRAIGHT_LINE', 1, 'Bangunan kantor, gudang, kandang, atau bangunan usaha lainnya.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'BUILDING');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'VEHICLE', 'Kendaraan', 'FIXED', 96, 'STRAIGHT_LINE', 1, 'Motor operasional, mobil pickup, kendaraan angkut.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'VEHICLE');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'MACHINE', 'Mesin', 'FIXED', 84, 'STRAIGHT_LINE', 1, 'Mesin produksi, genset, mesin pencacah, pompa.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'MACHINE');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'EQUIPMENT', 'Peralatan', 'FIXED', 60, 'STRAIGHT_LINE', 1, 'Peralatan kerja umum dan peralatan lapangan.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'EQUIPMENT');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'INVENTORY', 'Inventaris', 'FIXED', 48, 'STRAIGHT_LINE', 1, 'Meja, kursi, lemari, inventaris kantor.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'INVENTORY');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'IT', 'Perangkat Teknologi / IT', 'FIXED', 48, 'STRAIGHT_LINE', 1, 'Laptop, PC, mini server, printer, UPS.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'IT');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'NETWORK', 'Peralatan Jaringan', 'FIXED', 36, 'STRAIGHT_LINE', 1, 'Router, access point, switch, ODP, FO, tiang jaringan, alat instalasi.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'NETWORK');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'LIVESTOCK_EQUIP', 'Peralatan Peternakan', 'FIXED', 60, 'STRAIGHT_LINE', 1, 'Timbangan ternak, alat pakan, alat minum, alat kebersihan, mesin pencacah pakan.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'LIVESTOCK_EQUIP');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'BIOLOGICAL', 'Hewan Produktif / Aset Biologis', 'BIOLOGICAL', NULL, 'STRAIGHT_LINE', 0, 'Indukan atau ternak produktif dicatat sebagai aset biologis. Secara desain awal tidak disusutkan otomatis.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'BIOLOGICAL');
INSERT INTO asset_categories (category_code, category_name, asset_group, default_useful_life_months, default_depreciation_method, depreciation_allowed, description, is_active)
SELECT 'OTHER', 'Lainnya', 'OTHER', 36, 'STRAIGHT_LINE', 1, 'Kategori umum untuk aset yang belum masuk kategori lain.', 1
WHERE NOT EXISTS (SELECT 1 FROM asset_categories WHERE category_code = 'OTHER');
