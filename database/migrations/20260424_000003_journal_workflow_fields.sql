SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'workflow_status'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN workflow_status ENUM('DRAFT','SUBMITTED','APPROVED','POSTED','VOIDED','REVERSED') NOT NULL DEFAULT 'POSTED' AFTER updated_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'workflow_reason'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN workflow_reason VARCHAR(255) NOT NULL DEFAULT '' AFTER workflow_status"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'submitted_at'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN submitted_at DATETIME NULL AFTER workflow_reason"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'submitted_by'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN submitted_by INT UNSIGNED NULL AFTER submitted_at"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'approved_at'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN approved_at DATETIME NULL AFTER submitted_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'approved_by'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN approved_by INT UNSIGNED NULL AFTER approved_at"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'posted_at'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN posted_at DATETIME NULL AFTER approved_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'posted_by'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN posted_by INT UNSIGNED NULL AFTER posted_at"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'voided_at'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN voided_at DATETIME NULL AFTER posted_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'voided_by'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN voided_by INT UNSIGNED NULL AFTER voided_at"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'reversed_at'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN reversed_at DATETIME NULL AFTER voided_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'reversed_by'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN reversed_by INT UNSIGNED NULL AFTER reversed_at"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'reversed_from_journal_id'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN reversed_from_journal_id INT UNSIGNED NULL AFTER reversed_by"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'reversal_journal_id'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN reversal_journal_id INT UNSIGNED NULL AFTER reversed_from_journal_id"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE journal_headers
SET workflow_status = 'POSTED',
    posted_at = COALESCE(posted_at, created_at),
    posted_by = COALESCE(posted_by, created_by)
WHERE COALESCE(workflow_status, '') = '';

CREATE INDEX idx_journal_headers_workflow_status ON journal_headers (workflow_status);
