CREATE TABLE IF NOT EXISTS accounting_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period_code VARCHAR(30) NOT NULL,
    period_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_accounting_period_code UNIQUE (period_code),
    CONSTRAINT fk_accounting_periods_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_accounting_period_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_accounting_period_status ON accounting_periods (status);
CREATE INDEX idx_accounting_period_active ON accounting_periods (is_active);
CREATE INDEX idx_accounting_period_dates ON accounting_periods (start_date, end_date);

INSERT INTO accounting_periods (
    period_code,
    period_name,
    start_date,
    end_date,
    status,
    is_active,
    updated_by
)
SELECT
    '2026-01',
    'Januari 2026',
    '2026-01-01',
    '2026-01-31',
    'OPEN',
    1,
    NULL
WHERE NOT EXISTS (SELECT 1 FROM accounting_periods);
