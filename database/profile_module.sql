CREATE TABLE IF NOT EXISTS app_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bumdes_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    village_name VARCHAR(120) NOT NULL DEFAULT '',
    district_name VARCHAR(120) NOT NULL DEFAULT '',
    regency_name VARCHAR(120) NOT NULL DEFAULT '',
    province_name VARCHAR(120) NOT NULL DEFAULT '',
    legal_entity_no VARCHAR(120) NOT NULL DEFAULT '',
    nib VARCHAR(50) NOT NULL DEFAULT '',
    npwp VARCHAR(50) NOT NULL DEFAULT '',
    phone VARCHAR(30) NOT NULL DEFAULT '',
    email VARCHAR(100) NOT NULL DEFAULT '',
    logo_path VARCHAR(255) NOT NULL DEFAULT '',
    leader_name VARCHAR(120) NOT NULL,
    active_period_start DATE NOT NULL,
    active_period_end DATE NOT NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_profiles_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_profiles (
    bumdes_name,
    address,
    village_name,
    district_name,
    regency_name,
    province_name,
    legal_entity_no,
    nib,
    npwp,
    phone,
    email,
    logo_path,
    leader_name,
    active_period_start,
    active_period_end,
    updated_by
)
SELECT
    'BUMDes Maju Bersama',
    'Jl. Contoh Desa No. 1',
    'Contoh',
    'Contoh',
    'Contoh',
    'Contoh',
    '',
    '',
    '',
    '081234567890',
    'info@bumdes.local',
    '',
    'Nama Pimpinan',
    '2026-01-01',
    '2026-12-31',
    NULL
WHERE NOT EXISTS (SELECT 1 FROM app_profiles);
