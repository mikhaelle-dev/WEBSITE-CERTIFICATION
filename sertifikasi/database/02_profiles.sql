USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS profil_user (
    user_id       INT PRIMARY KEY,
    bahasa        VARCHAR(20) NOT NULL DEFAULT 'Indonesia',
    negara        VARCHAR(100) NOT NULL DEFAULT 'Indonesia',
    provinsi      VARCHAR(100) NOT NULL DEFAULT '',
    tujuan_karier VARCHAR(100) NOT NULL DEFAULT '',
    foto_profil   VARCHAR(255) NULL,
    CONSTRAINT fk_profil_user_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

SET @foto_profil_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'profil_user'
      AND COLUMN_NAME = 'foto_profil'
);
SET @foto_profil_migration = IF(
    @foto_profil_exists = 0,
    'ALTER TABLE profil_user ADD COLUMN foto_profil VARCHAR(255) NULL',
    'SELECT 1'
);
PREPARE foto_profil_statement FROM @foto_profil_migration;
EXECUTE foto_profil_statement;
DEALLOCATE PREPARE foto_profil_statement;
