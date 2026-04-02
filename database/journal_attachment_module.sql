CREATE TABLE IF NOT EXISTS journal_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_id INT UNSIGNED NOT NULL,
    attachment_title VARCHAR(150) NOT NULL DEFAULT '',
    attachment_notes VARCHAR(255) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    stored_file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL DEFAULT '',
    file_ext VARCHAR(20) NOT NULL DEFAULT '',
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_attachments_journal FOREIGN KEY (journal_id) REFERENCES journal_headers(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_journal_attachments_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_journal_attachments_journal (journal_id),
    INDEX idx_journal_attachments_created_at (created_at),
    INDEX idx_journal_attachments_stored_name (stored_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
