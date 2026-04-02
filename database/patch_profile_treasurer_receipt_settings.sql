SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'treasurer_name'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN treasurer_name VARCHAR(120) NOT NULL DEFAULT '''' AFTER signature_path'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'treasurer_position'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN treasurer_position VARCHAR(100) NOT NULL DEFAULT ''Bendahara'' AFTER treasurer_name'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'treasurer_signature_path'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN treasurer_signature_path VARCHAR(255) NOT NULL DEFAULT '''' AFTER treasurer_position'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'receipt_signature_mode'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN receipt_signature_mode VARCHAR(50) NOT NULL DEFAULT ''treasurer_recipient_director'' AFTER treasurer_signature_path'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'receipt_require_recipient_cash'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN receipt_require_recipient_cash TINYINT(1) NOT NULL DEFAULT 1 AFTER receipt_signature_mode'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'receipt_require_recipient_transfer'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN receipt_require_recipient_transfer TINYINT(1) NOT NULL DEFAULT 0 AFTER receipt_require_recipient_cash'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'director_sign_threshold'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN director_sign_threshold DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER receipt_require_recipient_transfer'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'app_profiles' AND COLUMN_NAME = 'show_stamp'
    ),
    'SELECT 1',
    'ALTER TABLE app_profiles ADD COLUMN show_stamp TINYINT(1) NOT NULL DEFAULT 1 AFTER director_sign_threshold'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE app_profiles
SET treasurer_position = 'Bendahara'
WHERE COALESCE(treasurer_position, '') = '';

UPDATE app_profiles
SET receipt_signature_mode = 'treasurer_recipient_director'
WHERE COALESCE(receipt_signature_mode, '') = '';
