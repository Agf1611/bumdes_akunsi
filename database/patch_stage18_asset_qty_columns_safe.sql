SET @db_name := DATABASE();

SET @has_quantity := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'asset_items'
      AND COLUMN_NAME = 'quantity'
);
SET @sql_quantity := IF(
    @has_quantity = 0,
    'ALTER TABLE asset_items ADD COLUMN quantity DECIMAL(18,2) NOT NULL DEFAULT 1.00 AFTER business_unit_id',
    'SELECT 1'
);
PREPARE stmt_quantity FROM @sql_quantity;
EXECUTE stmt_quantity;
DEALLOCATE PREPARE stmt_quantity;

SET @has_unit_name := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'asset_items'
      AND COLUMN_NAME = 'unit_name'
);
SET @sql_unit_name := IF(
    @has_unit_name = 0,
    "ALTER TABLE asset_items ADD COLUMN unit_name VARCHAR(30) NOT NULL DEFAULT 'unit' AFTER quantity",
    'SELECT 1'
);
PREPARE stmt_unit_name FROM @sql_unit_name;
EXECUTE stmt_unit_name;
DEALLOCATE PREPARE stmt_unit_name;
