CREATE TABLE IF NOT EXISTS reference_partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_type ENUM('CUSTOMER','VENDOR','DEBTOR','CREDITOR','GENERAL') NOT NULL DEFAULT 'GENERAL',
    partner_code VARCHAR(30) NOT NULL,
    partner_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reference_partners_code (partner_code),
    KEY idx_reference_partners_type (partner_type),
    KEY idx_reference_partners_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_partner_id := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_lines' AND COLUMN_NAME = 'partner_id'
);
SET @sql_partner_id := IF(@has_partner_id = 0,
    'ALTER TABLE journal_lines ADD COLUMN partner_id INT UNSIGNED NULL AFTER coa_id',
    'SELECT 1');
PREPARE stmt_partner_id FROM @sql_partner_id; EXECUTE stmt_partner_id; DEALLOCATE PREPARE stmt_partner_id;

SET @has_entry_tag := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_lines' AND COLUMN_NAME = 'entry_tag'
);
SET @sql_entry_tag := IF(@has_entry_tag = 0,
    "ALTER TABLE journal_lines ADD COLUMN entry_tag VARCHAR(30) NOT NULL DEFAULT '' AFTER credit",
    'SELECT 1');
PREPARE stmt_entry_tag FROM @sql_entry_tag; EXECUTE stmt_entry_tag; DEALLOCATE PREPARE stmt_entry_tag;

SET @has_idx_partner := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_lines' AND INDEX_NAME = 'idx_journal_lines_partner_id'
);
SET @sql_idx_partner := IF(@has_idx_partner = 0,
    'ALTER TABLE journal_lines ADD INDEX idx_journal_lines_partner_id (partner_id)',
    'SELECT 1');
PREPARE stmt_idx_partner FROM @sql_idx_partner; EXECUTE stmt_idx_partner; DEALLOCATE PREPARE stmt_idx_partner;

INSERT INTO reference_partners (partner_type, partner_code, partner_name, is_active)
SELECT 'DEBTOR', 'UMUM-PIUTANG', 'Pelanggan / Debitur Umum', 1
WHERE NOT EXISTS (SELECT 1 FROM reference_partners WHERE partner_code = 'UMUM-PIUTANG');
