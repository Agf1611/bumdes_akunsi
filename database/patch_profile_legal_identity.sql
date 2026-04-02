SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'village_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN village_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER address'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'district_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN district_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER village_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'regency_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN regency_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER district_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'province_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN province_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER regency_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'legal_entity_no'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN legal_entity_no VARCHAR(120) NOT NULL DEFAULT '''' AFTER province_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'nib'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN nib VARCHAR(50) NOT NULL DEFAULT '''' AFTER legal_entity_no'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'npwp'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN npwp VARCHAR(50) NOT NULL DEFAULT '''' AFTER nib'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
