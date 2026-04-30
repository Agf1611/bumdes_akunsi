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

ALTER TABLE cashflow_components
    ADD COLUMN IF NOT EXISTS cashflow_group ENUM('OPERATING','INVESTING','FINANCING') NOT NULL DEFAULT 'OPERATING' AFTER component_name,
    ADD COLUMN IF NOT EXISTS direction ENUM('IN','OUT') NOT NULL DEFAULT 'IN' AFTER cashflow_group,
    ADD COLUMN IF NOT EXISTS display_order INT NOT NULL DEFAULT 0 AFTER direction,
    ADD COLUMN IF NOT EXISTS description VARCHAR(255) NOT NULL DEFAULT '' AFTER display_order;

ALTER TABLE journal_lines
    ADD COLUMN IF NOT EXISTS cashflow_component_id INT UNSIGNED NULL AFTER credit,
    ADD COLUMN IF NOT EXISTS entry_tag VARCHAR(30) NOT NULL DEFAULT '' AFTER cashflow_component_id;

ALTER TABLE journal_lines
    ADD INDEX IF NOT EXISTS idx_journal_lines_cashflow_component (cashflow_component_id);

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'OP-IN-SALES', 'Penerimaan dari penjualan / jasa', 'OPERATING', 'IN', 10, 'Penerimaan kas dari penjualan barang, jasa, atau pendapatan usaha.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-IN-SALES');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'OP-IN-OTHER', 'Penerimaan operasional lainnya', 'OPERATING', 'IN', 15, 'Penerimaan kas dari aktivitas operasi selain penjualan utama.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-IN-OTHER');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'OP-OUT-OPEX', 'Pembayaran beban operasional', 'OPERATING', 'OUT', 20, 'Pembayaran kas untuk beban rutin, supplier, honor, listrik, dan biaya operasional.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-OUT-OPEX');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'OP-OUT-TAX', 'Pembayaran pajak / kewajiban operasional', 'OPERATING', 'OUT', 25, 'Pembayaran kas untuk pajak, retribusi, atau kewajiban operasional.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'OP-OUT-TAX');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'INV-IN-ASSET', 'Penerimaan penjualan aset / investasi', 'INVESTING', 'IN', 40, 'Penerimaan kas dari pelepasan aset tetap atau investasi.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'INV-IN-ASSET');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'INV-OUT-ASSET', 'Pembelian aset tetap / investasi', 'INVESTING', 'OUT', 45, 'Pengeluaran kas untuk pembelian aset tetap, investasi, peralatan, atau pembangunan.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'INV-OUT-ASSET');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'FIN-IN-CAPITAL', 'Penerimaan penyertaan modal / pinjaman', 'FINANCING', 'IN', 60, 'Penerimaan kas dari penyertaan modal, pinjaman, atau pendanaan.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'FIN-IN-CAPITAL');

INSERT INTO cashflow_components (component_code, component_name, cashflow_group, direction, display_order, description, is_active)
SELECT 'FIN-OUT-LOAN', 'Pembayaran pinjaman / pembagian hasil', 'FINANCING', 'OUT', 70, 'Pengeluaran kas untuk angsuran pinjaman, pengembalian modal, atau pembagian hasil.', 1
WHERE NOT EXISTS (SELECT 1 FROM cashflow_components WHERE component_code = 'FIN-OUT-LOAN');
