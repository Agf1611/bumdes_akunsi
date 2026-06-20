SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'business_units'
          AND COLUMN_NAME = 'legal_name'
    ),
    'SELECT 1',
    'ALTER TABLE business_units ADD COLUMN legal_name VARCHAR(160) NOT NULL DEFAULT '''' AFTER unit_name'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'business_units'
          AND COLUMN_NAME = 'nib'
    ),
    'SELECT 1',
    'ALTER TABLE business_units ADD COLUMN nib VARCHAR(50) NOT NULL DEFAULT '''' AFTER legal_name'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'business_units'
          AND COLUMN_NAME = 'phone'
    ),
    'SELECT 1',
    'ALTER TABLE business_units ADD COLUMN phone VARCHAR(40) NOT NULL DEFAULT '''' AFTER nib'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'business_units'
          AND COLUMN_NAME = 'email'
    ),
    'SELECT 1',
    'ALTER TABLE business_units ADD COLUMN email VARCHAR(120) NOT NULL DEFAULT '''' AFTER phone'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'business_units'
          AND COLUMN_NAME = 'address'
    ),
    'SELECT 1',
    'ALTER TABLE business_units ADD COLUMN address VARCHAR(500) NOT NULL DEFAULT '''' AFTER email'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE business_units
SET legal_name = unit_name
WHERE COALESCE(legal_name, '') = '';
