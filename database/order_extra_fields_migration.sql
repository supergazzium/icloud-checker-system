-- ============================================================
-- Additional Apple GSX response fields on orders.
-- Loaded by docker/entrypoint.sh after services_importer_migration.sql.
-- Idempotent: safe to run on both fresh and existing databases.
--
-- Every ADD COLUMN is guarded by an information_schema lookup because
-- MySQL 8.0 does not support `ADD COLUMN IF NOT EXISTS`.
-- ============================================================

-- Product identity (visible in every successful Apple check).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_model_desc');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_model_desc VARCHAR(300) NULL AFTER result_model', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_part_number');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_part_number VARCHAR(50) NULL AFTER result_model_desc', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_part_country');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_part_country VARCHAR(100) NULL AFTER result_part_number', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_part_type');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_part_type VARCHAR(50) NULL AFTER result_part_country', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchase / country (some services return purchaseCountry distinct from partCountry).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_purchase_country');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_purchase_country VARCHAR(100) NULL AFTER result_purchase_date', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Coverage details.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_coverage_end_date');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_coverage_end_date VARCHAR(100) NULL AFTER result_warranty', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_ac_eligible');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_ac_eligible VARCHAR(20) NULL AFTER result_coverage_end_date', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_technical_support');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_technical_support VARCHAR(20) NULL AFTER result_ac_eligible', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_repair_coverage');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_repair_coverage VARCHAR(20) NULL AFTER result_technical_support', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Extra device-state flags (independent of result_replaced already stored).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_replacement');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_replacement VARCHAR(20) NULL AFTER result_replaced', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_refurbished');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_refurbished VARCHAR(20) NULL AFTER result_replacement', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_demo_unit');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_demo_unit VARCHAR(20) NULL AFTER result_refurbished', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_loaner');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_loaner VARCHAR(20) NULL AFTER result_demo_unit', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cosmetic: Apple product thumbnail URL for the result-page hero.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_thumbnail');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_thumbnail VARCHAR(500) NULL AFTER result_loaner', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Second IMEI (some iPhones return it for dual-SIM).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_imei2');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_imei2 VARCHAR(100) NULL AFTER result_imei', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Carrier (raw). Was previously used as simlock_status fallback only.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='result_carrier');
SET @s := IF(@c=0, 'ALTER TABLE orders ADD COLUMN result_carrier VARCHAR(100) NULL AFTER result_simlock', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
