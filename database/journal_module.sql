CREATE TABLE IF NOT EXISTS journal_headers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_no VARCHAR(50) NOT NULL,
    journal_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    period_id INT UNSIGNED NOT NULL,
    total_debit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_credit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_journal_headers_no UNIQUE (journal_no),
    CONSTRAINT fk_journal_headers_period FOREIGN KEY (period_id) REFERENCES accounting_periods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_journal_headers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_journal_headers_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_journal_headers_date (journal_date),
    INDEX idx_journal_headers_period (period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_lines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_id INT UNSIGNED NOT NULL,
    line_no INT UNSIGNED NOT NULL,
    coa_id INT UNSIGNED NOT NULL,
    line_description VARCHAR(255) NOT NULL DEFAULT '',
    debit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    credit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    cashflow_component_id INT UNSIGNED NULL,
    entry_tag VARCHAR(30) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_lines_header FOREIGN KEY (journal_id) REFERENCES journal_headers(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_journal_lines_coa FOREIGN KEY (coa_id) REFERENCES coa_accounts(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_journal_lines_line UNIQUE (journal_id, line_no),
    INDEX idx_journal_lines_coa (coa_id),
    INDEX idx_journal_lines_journal (journal_id),
    INDEX idx_journal_lines_cashflow_component (cashflow_component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashflow_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    component_code VARCHAR(40) NOT NULL,
    component_name VARCHAR(150) NOT NULL,
    cashflow_group ENUM('OPERATING','INVESTING','FINANCING') NOT NULL DEFAULT 'OPERATING',
    direction ENUM('IN','OUT') NOT NULL DEFAULT 'IN',
    display_order INT NOT NULL DEFAULT 0,
    description VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cashflow_components_code (component_code),
    KEY idx_cashflow_components_group (cashflow_group),
    KEY idx_cashflow_components_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
