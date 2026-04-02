CREATE TABLE IF NOT EXISTS business_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(30) NOT NULL,
    unit_name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_business_units_code UNIQUE (unit_code),
    INDEX idx_business_units_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO business_units (unit_code, unit_name, description, is_active)
SELECT 'PUSAT', 'Kantor Pusat', 'Unit default / kantor pusat BUMDes', 1
WHERE NOT EXISTS (SELECT 1 FROM business_units WHERE unit_code = 'PUSAT');

SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'director_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN director_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER leader_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'director_position'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN director_position VARCHAR(100) NOT NULL DEFAULT ''Direktur'' AFTER director_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'signature_city'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN signature_city VARCHAR(100) NOT NULL DEFAULT '''' AFTER director_position'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'signature_path'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN signature_path VARCHAR(255) NOT NULL DEFAULT '''' AFTER signature_city'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE app_profiles
SET director_name = leader_name
WHERE COALESCE(director_name, '') = '' AND COALESCE(leader_name, '') <> '';

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'business_unit_id'
    ),
    'SELECT 1',
    'ALTER TABLE journal_headers ADD COLUMN business_unit_id INT UNSIGNED NULL AFTER period_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND INDEX_NAME = 'idx_journal_headers_business_unit'
    ),
    'SELECT 1',
    'ALTER TABLE journal_headers ADD INDEX idx_journal_headers_business_unit (business_unit_id)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = @db_name
          AND TABLE_NAME = 'journal_headers'
          AND CONSTRAINT_NAME = 'fk_journal_headers_business_unit'
    ),
    'SELECT 1',
    'ALTER TABLE journal_headers ADD CONSTRAINT fk_journal_headers_business_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id) ON UPDATE CASCADE ON DELETE SET NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
