SET @db_name = DATABASE();

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NOT NULL DEFAULT '',
    full_name VARCHAR(100) NOT NULL DEFAULT '',
    module_name VARCHAR(60) NOT NULL,
    action_name VARCHAR(60) NOT NULL,
    entity_type VARCHAR(60) NOT NULL DEFAULT '',
    entity_id VARCHAR(80) NOT NULL DEFAULT '',
    severity_level ENUM('info','warning','danger') NOT NULL DEFAULT 'info',
    description VARCHAR(255) NOT NULL,
    before_data LONGTEXT NULL,
    after_data LONGTEXT NULL,
    context_data LONGTEXT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_logs_created_at (created_at),
    INDEX idx_audit_logs_module_action (module_name, action_name),
    INDEX idx_audit_logs_user (user_id, username),
    INDEX idx_audit_logs_severity (severity_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
