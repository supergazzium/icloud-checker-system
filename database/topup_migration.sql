-- Run once: adds credit-card top-up support (Omise/Opn Payments)
CREATE TABLE topups (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    status       ENUM('pending','paid','failed','expired') DEFAULT 'pending',
    link_id      VARCHAR(100) NULL,
    payment_uri  VARCHAR(500) NULL,
    charge_id    VARCHAR(100) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id), INDEX (link_id)
);
