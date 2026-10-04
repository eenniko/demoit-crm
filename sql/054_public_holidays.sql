CREATE TABLE IF NOT EXISTS system_public_holidays (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    kind VARCHAR(100) NOT NULL,
    kind_id TINYINT UNSIGNED NOT NULL,
    source_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_system_public_holidays_source (source_key),
    KEY idx_system_public_holidays_day_off (holiday_date, kind_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.67', 'Import Estonian public holidays and treat statutory holidays as schedule non-working days.', NOW());