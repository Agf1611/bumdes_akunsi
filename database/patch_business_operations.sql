CREATE TABLE IF NOT EXISTS business_employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_unit_id INT UNSIGNED NULL,
    employee_name VARCHAR(150) NOT NULL,
    position_title VARCHAR(120) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL DEFAULT '',
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    notes VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_business_employees_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_business_employees_unit (business_unit_id),
    INDEX idx_business_employees_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_unit_id INT UNSIGNED NULL,
    activity_name VARCHAR(160) NOT NULL,
    activity_type VARCHAR(80) NOT NULL DEFAULT '',
    target_period VARCHAR(30) NOT NULL DEFAULT '',
    target_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    status ENUM('PLANNED','RUNNING','DONE','PAUSED') NOT NULL DEFAULT 'RUNNING',
    notes VARCHAR(700) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_business_activities_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_business_activities_unit (business_unit_id),
    INDEX idx_business_activities_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_budgets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_unit_id INT UNSIGNED NULL,
    budget_year SMALLINT UNSIGNED NOT NULL,
    budget_month TINYINT UNSIGNED NULL,
    budget_type ENUM('INCOME','EXPENSE','ASSET','CAPITAL') NOT NULL DEFAULT 'EXPENSE',
    category VARCHAR(120) NOT NULL,
    account_id INT UNSIGNED NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    status ENUM('DRAFT','ACTIVE','CLOSED') NOT NULL DEFAULT 'ACTIVE',
    notes VARCHAR(700) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_business_budgets_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_business_budgets_account FOREIGN KEY (account_id) REFERENCES coa_accounts(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_business_budgets_unit_year (business_unit_id, budget_year, budget_month),
    INDEX idx_business_budgets_type (budget_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS budget_rabs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_unit_id INT UNSIGNED NULL,
    plan_no VARCHAR(40) NOT NULL,
    plan_date DATE NOT NULL,
    plan_title VARCHAR(180) NOT NULL,
    activity_name VARCHAR(160) NOT NULL DEFAULT '',
    status ENUM('DRAFT','APPROVED','REALIZED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
    notes VARCHAR(700) NOT NULL DEFAULT '',
    total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_budget_rabs_plan_no UNIQUE (plan_no),
    CONSTRAINT fk_budget_rabs_unit FOREIGN KEY (business_unit_id) REFERENCES business_units(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_budget_rabs_unit_date (business_unit_id, plan_date),
    INDEX idx_budget_rabs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS budget_rab_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_rab_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    quantity DECIMAL(18,4) NOT NULL DEFAULT 1,
    unit_name VARCHAR(30) NOT NULL DEFAULT 'unit',
    unit_price DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    notes VARCHAR(300) NOT NULL DEFAULT '',
    CONSTRAINT fk_budget_rab_items_plan FOREIGN KEY (budget_rab_id) REFERENCES budget_rabs(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_budget_rab_items_plan (budget_rab_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
