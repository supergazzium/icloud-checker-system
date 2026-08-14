-- ============================================================
-- iCloud Checker System - Database Schema
-- Laravel 11 | MySQL 8+
-- ============================================================
CREATE TABLE users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password          VARCHAR(255) NOT NULL,
    role              ENUM('admin','reseller','user') DEFAULT 'user',
    balance           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    locale            ENUM('th','en') DEFAULT 'th',
    is_active         TINYINT(1) DEFAULT 1,
    provider          VARCHAR(20) NULL,
    provider_id       VARCHAR(100) NULL,
    two_factor_secret       VARCHAR(255) NULL,
    two_factor_enabled      TINYINT(1) DEFAULT 0,
    two_factor_recovery_codes TEXT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    remember_token    VARCHAR(100) NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE services (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_th              VARCHAR(200) NOT NULL,
    name_en              VARCHAR(200) NOT NULL,
    description_th       TEXT NULL,
    description_en       TEXT NULL,
    provider_service_id  INT NOT NULL,
    provider_service_id_2 INT NULL,
    device_type          ENUM('iphone','ipad','macbook','all') DEFAULT 'iphone',
    cost_price           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sell_price           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    processing_time      VARCHAR(50) DEFAULT 'Instant',
    supports_serial      TINYINT(1) DEFAULT 1,
    active               TINYINT(1) DEFAULT 1,
    sort_order           INT DEFAULT 0,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    service_id          BIGINT UNSIGNED NOT NULL,
    imei_serial         VARCHAR(100) NOT NULL,
    cost_price          DECIMAL(10,2) NOT NULL,
    sell_price          DECIMAL(10,2) NOT NULL,
    profit              DECIMAL(10,2) GENERATED ALWAYS AS (sell_price - cost_price) STORED,
    status              ENUM('pending','processing','success','error') DEFAULT 'pending',
    result_model        VARCHAR(200) NULL,
    result_serial       VARCHAR(100) NULL,
    result_imei         VARCHAR(100) NULL,
    result_color        VARCHAR(100) NULL,
    result_storage      VARCHAR(50)  NULL,
    result_region       VARCHAR(100) NULL,
    result_fmi          VARCHAR(50)  NULL,
    result_activation   VARCHAR(50)  NULL,
    result_blacklist    VARCHAR(50)  NULL,
    result_simlock      VARCHAR(50)  NULL,
    result_mdm          VARCHAR(50)  NULL,
    result_warranty     VARCHAR(100) NULL,
    result_purchase_date VARCHAR(100) NULL,
    result_replaced     VARCHAR(50)  NULL,
    response_text       TEXT NULL,
    raw_response        LONGTEXT NULL,
    error_message       VARCHAR(500) NULL,
    processed_at        TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE credit_transactions (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        BIGINT UNSIGNED NOT NULL,
    type           ENUM('topup','deduct','refund','adjustment') NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after  DECIMAL(10,2) NOT NULL,
    description    VARCHAR(300) NULL,
    order_id       BIGINT UNSIGNED NULL,
    admin_id       BIGINT UNSIGNED NULL,
    admin_ip       VARCHAR(45) NULL,
    reference      VARCHAR(100) NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE api_logs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     BIGINT UNSIGNED NULL,
    service_id   INT NOT NULL,
    imei_serial  VARCHAR(100) NOT NULL,
    http_code    INT NULL,
    success      TINYINT(1) NULL,
    error_msg    VARCHAR(500) NULL,
    duration_ms  INT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT UNSIGNED NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX (user_id),
    INDEX (last_activity)
);

-- Admin user is provisioned at container start by `php artisan admin:ensure`
-- (see docker/entrypoint.sh). That command reads ADMIN_EMAIL / ADMIN_NAME /
-- ADMIN_PASSWORD from the environment. When ADMIN_PASSWORD is empty a strong
-- random password is generated and printed ONCE to stdout — copy it from the
-- deploy logs. The admin row is flagged must_change_password=1 so first login
-- forces the operator to set a new password.

-- Seed: Default Services
INSERT INTO services (name_th, name_en, description_th, description_en, provider_service_id, device_type, cost_price, sell_price, processing_time, supports_serial, active, sort_order) VALUES
('ตรวจสอบ iPhone/iPad ครบชุด (Pro)','iPhone/iPad All-in-One (Pro)',
 'ตรวจ Model, FMI, Activation Lock, Blacklist, SIM Lock, MDM, Warranty',
 'Model, FMI, Activation Lock, Blacklist, SIM Lock, MDM, Warranty',
 242, 'iphone', 15.00, 35.00, 'Instant', 1, 1, 1),
('ตรวจสอบ iPhone/iPad ครบชุด (Ultimate)','iPhone/iPad All-in-One (Ultimate)',
 'ตรวจครบทุกรายการ รวม Carrier, Country, Purchase Date',
 'Full check: Carrier, Country, Purchase Date included',
 244, 'iphone', 25.00, 59.00, 'Instant', 1, 1, 2),
('ตรวจสอบ MacBook ครบชุด','MacBook All-in-One Check',
 'ตรวจ Model, FMI, MDM, iCloud Clean/Lost สำหรับ MacBook',
 'Model, FMI, MDM, iCloud Clean/Lost for MacBook',
 316, 'macbook', 20.00, 49.00, 'Instant', 1, 1, 3),
('ตรวจสอบ iPad ครบชุด','iPad All-in-One Check',
 'ตรวจ Model, FMI, Activation Lock, Blacklist สำหรับ iPad',
 'Model, FMI, Activation Lock, Blacklist for iPad',
 246, 'ipad', 15.00, 35.00, 'Instant', 1, 1, 4);