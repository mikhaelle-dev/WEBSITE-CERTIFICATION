USE db_sertifikasi;

SET @must_change_password_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'must_change_password'
);
SET @migration_sql = IF(
    @must_change_password_exists = 0,
    'ALTER TABLE user ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS password_reset_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    status       ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    processed_by INT NULL,
    KEY idx_password_reset_status_created (status, created_at),
    KEY idx_password_reset_user_status (user_id, status),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
    CONSTRAINT fk_password_reset_admin
        FOREIGN KEY (processed_by) REFERENCES user (id) ON DELETE SET NULL
);
