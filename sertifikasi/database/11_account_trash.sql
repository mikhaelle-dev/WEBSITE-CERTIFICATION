ALTER TABLE user
    ADD COLUMN masuk_sampah_pada TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN status_sebelum_sampah TINYINT(1) NULL DEFAULT NULL,
    ADD KEY idx_user_sampah_pada (masuk_sampah_pada);