-- Laravel 12 framework support tables not present in database/schema.sql.
-- Loaded by docker/entrypoint.sh after schema.sql on first boot.

CREATE TABLE IF NOT EXISTS cache (
    `key`       VARCHAR(255) NOT NULL PRIMARY KEY,
    value       MEDIUMTEXT NOT NULL,
    expiration  INT NOT NULL
);

CREATE TABLE IF NOT EXISTS cache_locks (
    `key`      VARCHAR(255) NOT NULL PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);

CREATE TABLE IF NOT EXISTS jobs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      LONGTEXT NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL,
    reserved_at  INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at   INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
);

CREATE TABLE IF NOT EXISTS job_batches (
    id              VARCHAR(255) NOT NULL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    total_jobs      INT NOT NULL,
    pending_jobs    INT NOT NULL,
    failed_jobs     INT NOT NULL,
    failed_job_ids  LONGTEXT NOT NULL,
    options         MEDIUMTEXT NULL,
    cancelled_at    INT NULL,
    created_at      INT NOT NULL,
    finished_at     INT NULL
);

CREATE TABLE IF NOT EXISTS failed_jobs (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue      TEXT NOT NULL,
    payload    LONGTEXT NOT NULL,
    exception  LONGTEXT NOT NULL,
    failed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      VARCHAR(255) NOT NULL PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
