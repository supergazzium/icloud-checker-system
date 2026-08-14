-- ============================================================
-- Bank-transfer topup flow: bank accounts + widened topups
-- Loaded by docker/entrypoint.sh after schema.sql + topup_migration.sql.
-- Idempotent: safe to run on both fresh and existing databases.
--
-- MySQL 8.0 does NOT support `IF NOT EXISTS` on ADD COLUMN / ADD INDEX / ADD
-- FOREIGN KEY, so every addition is guarded by an information_schema lookup
-- that runs the ALTER only when the target object is missing. Stored
-- procedures are avoided because the app-side importer splits SQL on
-- semicolons and cannot use DELIMITER.
-- ============================================================

CREATE TABLE IF NOT EXISTS bank_accounts (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_name      VARCHAR(100) NOT NULL,
    account_name   VARCHAR(200) NOT NULL,
    account_number VARCHAR(50)  NOT NULL,
    branch         VARCHAR(100) NULL,
    notes          VARCHAR(500) NULL,
    active         TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order     INT          NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (active, sort_order)
);

-- Widen topups.status. MODIFY re-runnable — same target shape → no-op.
ALTER TABLE topups
    MODIFY COLUMN status ENUM(
        'pending','pending_review','approved','rejected',
        'paid','failed','expired'
    ) NOT NULL DEFAULT 'pending';

-- Add each column only if it doesn't already exist.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='bank_account_id');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN bank_account_id BIGINT UNSIGNED NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='slip_path');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN slip_path VARCHAR(500) NULL AFTER charge_id', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='slip_uploaded_at');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN slip_uploaded_at TIMESTAMP NULL AFTER slip_path', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='transfer_reference');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN transfer_reference VARCHAR(100) NULL AFTER slip_uploaded_at', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='transfer_date');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN transfer_date DATE NULL AFTER transfer_reference', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='reviewed_by');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER transfer_date', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='reviewed_at');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND COLUMN_NAME='rejection_reason');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD COLUMN rejection_reason VARCHAR(500) NULL AFTER reviewed_at', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FKs.
SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND CONSTRAINT_NAME='fk_topups_bank_account');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD CONSTRAINT fk_topups_bank_account FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND CONSTRAINT_NAME='fk_topups_reviewer');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD CONSTRAINT fk_topups_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Composite index.
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='topups' AND INDEX_NAME='idx_topups_status_created');
SET @s := IF(@c=0, 'ALTER TABLE topups ADD INDEX idx_topups_status_created (status, created_at)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
