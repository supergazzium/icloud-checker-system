-- ============================================================
-- Services importer + admin audit log
-- Loaded by docker/entrypoint.sh after bank_transfer_migration.sql.
-- Idempotent: safe on fresh and existing databases (MySQL 8.0).
-- ============================================================

-- Provider sync metadata on services. Never touch admin-editable columns
-- (name_th, description_th, sell_price, active) on re-sync.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND COLUMN_NAME='provider_price_usd');
SET @s := IF(@c=0, 'ALTER TABLE services ADD COLUMN provider_price_usd DECIMAL(10,4) NULL AFTER provider_service_id_2', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND COLUMN_NAME='provider_processing_time');
SET @s := IF(@c=0, 'ALTER TABLE services ADD COLUMN provider_processing_time VARCHAR(100) NULL AFTER provider_price_usd', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND COLUMN_NAME='provider_supports_serial');
SET @s := IF(@c=0, 'ALTER TABLE services ADD COLUMN provider_supports_serial TINYINT(1) NULL AFTER provider_processing_time', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND COLUMN_NAME='provider_synced_at');
SET @s := IF(@c=0, 'ALTER TABLE services ADD COLUMN provider_synced_at TIMESTAMP NULL AFTER provider_supports_serial', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND COLUMN_NAME='provider_missing_at');
SET @s := IF(@c=0, 'ALTER TABLE services ADD COLUMN provider_missing_at TIMESTAMP NULL AFTER provider_synced_at', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Unique index on provider_service_id to enforce the upsert key.
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='services' AND INDEX_NAME='uniq_services_provider_id');
SET @s := IF(@c=0, 'ALTER TABLE services ADD UNIQUE INDEX uniq_services_provider_id (provider_service_id)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- Admin audit log — generic activity trail for admin actions.
-- Not credit_transactions (which is a financial ledger).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id     BIGINT UNSIGNED NULL,
    admin_ip     VARCHAR(45) NULL,
    action       VARCHAR(100) NOT NULL,
    subject_type VARCHAR(100) NULL,
    subject_id   VARCHAR(100) NULL,
    meta         JSON NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (admin_id, created_at),
    INDEX (action, created_at),
    INDEX (subject_type, subject_id)
);

-- FK guarded (may already exist if migration re-runs).
SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='admin_audit_log' AND CONSTRAINT_NAME='fk_admin_audit_admin');
SET @s := IF(@c=0, 'ALTER TABLE admin_audit_log ADD CONSTRAINT fk_admin_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
