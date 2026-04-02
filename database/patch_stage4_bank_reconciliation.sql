CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    bank_account_coa_id INT UNSIGNED NOT NULL,
    period_id INT UNSIGNED NULL,
    business_unit_id INT UNSIGNED NULL,
    statement_no VARCHAR(100) NULL,
    statement_start_date DATE NOT NULL,
    statement_end_date DATE NOT NULL,
    opening_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    closing_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_statement_rows INT UNSIGNED NOT NULL DEFAULT 0,
    total_statement_in DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_statement_out DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_statement_net DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_matched_rows INT UNSIGNED NOT NULL DEFAULT 0,
    total_unmatched_rows INT UNSIGNED NOT NULL DEFAULT 0,
    matched_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    unmatched_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    last_matched_at DATETIME NULL,
    imported_file_name VARCHAR(255) NULL,
    stored_file_path VARCHAR(255) NULL,
    notes TEXT NULL,
    auto_match_tolerance_days TINYINT UNSIGNED NOT NULL DEFAULT 3,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_reconciliations_account FOREIGN KEY (bank_account_coa_id) REFERENCES coa_accounts(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bank_reconciliations_period FOREIGN KEY (period_id) REFERENCES accounting_periods(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bank_reconciliations_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_bank_reconciliations_created_by FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bank_reconciliations_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_bank_reconciliations_period (period_id),
    INDEX idx_bank_reconciliations_date (statement_start_date, statement_end_date),
    INDEX idx_bank_reconciliations_account (bank_account_coa_id),
    INDEX idx_bank_reconciliations_unit (business_unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_reconciliation_lines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reconciliation_id INT UNSIGNED NOT NULL,
    line_no INT UNSIGNED NOT NULL,
    transaction_date DATE NOT NULL,
    value_date DATE NULL,
    description VARCHAR(255) NOT NULL,
    reference_no VARCHAR(100) NULL,
    amount_in DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    amount_out DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    running_balance DECIMAL(18,2) NULL,
    raw_payload LONGTEXT NULL,
    match_status ENUM('UNMATCHED','AUTO','MANUAL','IGNORED') NOT NULL DEFAULT 'UNMATCHED',
    matched_journal_id INT UNSIGNED NULL,
    matched_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    matched_reason VARCHAR(255) NULL,
    matched_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_reconciliation_lines_header FOREIGN KEY (reconciliation_id) REFERENCES bank_reconciliations(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bank_reconciliation_lines_journal FOREIGN KEY (matched_journal_id) REFERENCES journal_headers(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT uq_bank_reconciliation_lines_unique UNIQUE (reconciliation_id, line_no),
    INDEX idx_bank_reconciliation_lines_date (transaction_date),
    INDEX idx_bank_reconciliation_lines_status (match_status),
    INDEX idx_bank_reconciliation_lines_journal (matched_journal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
