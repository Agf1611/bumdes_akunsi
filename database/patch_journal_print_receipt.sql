SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'journal_headers' AND COLUMN_NAME = 'print_template'
    ),
    'SELECT 1',
    "ALTER TABLE journal_headers ADD COLUMN print_template VARCHAR(20) NOT NULL DEFAULT 'standard' AFTER business_unit_id"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE journal_headers
SET print_template = 'standard'
WHERE COALESCE(print_template, '') = '';

CREATE TABLE IF NOT EXISTS journal_receipts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_id INT UNSIGNED NOT NULL,
    party_title VARCHAR(30) NOT NULL DEFAULT 'Dibayar kepada',
    party_name VARCHAR(150) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    amount_in_words VARCHAR(255) NOT NULL DEFAULT '',
    payment_method VARCHAR(50) NOT NULL DEFAULT '',
    reference_no VARCHAR(100) NOT NULL DEFAULT '',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_journal_receipts_journal UNIQUE (journal_id),
    CONSTRAINT fk_journal_receipts_journal FOREIGN KEY (journal_id) REFERENCES journal_headers(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_journal_receipts_reference_no (reference_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
