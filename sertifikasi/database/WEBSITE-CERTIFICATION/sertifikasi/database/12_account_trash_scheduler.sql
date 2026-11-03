USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS account_file_purge_queue (
    path      VARCHAR(255) NOT NULL PRIMARY KEY,
    queued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS queue_deleted_account_photo;

CREATE TRIGGER queue_deleted_account_photo
    BEFORE DELETE ON user
    FOR EACH ROW
    INSERT IGNORE INTO account_file_purge_queue (path)
        SELECT foto_profil
        FROM profil_user
        WHERE user_id = OLD.id
          AND foto_profil IS NOT NULL;

DROP EVENT IF EXISTS purge_expired_account_trash;

CREATE EVENT purge_expired_account_trash
    ON SCHEDULE EVERY 1 SECOND
    ON COMPLETION PRESERVE
    ENABLE
    DO
        DELETE FROM user
        WHERE masuk_sampah_pada IS NOT NULL
          AND masuk_sampah_pada <= CURRENT_TIMESTAMP - INTERVAL 5 MINUTE;
