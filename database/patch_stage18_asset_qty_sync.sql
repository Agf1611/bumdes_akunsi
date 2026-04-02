ALTER TABLE asset_items
    ADD COLUMN IF NOT EXISTS quantity DECIMAL(18,2) NOT NULL DEFAULT 1.00 AFTER business_unit_id,
    ADD COLUMN IF NOT EXISTS unit_name VARCHAR(30) NOT NULL DEFAULT 'unit' AFTER quantity;

UPDATE asset_items
SET quantity = 1.00
WHERE quantity IS NULL OR quantity <= 0;

UPDATE asset_items
SET unit_name = 'unit'
WHERE unit_name IS NULL OR TRIM(unit_name) = '';
