CREATE TABLE IF NOT EXISTS system_notification_reads (
    notification_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id, user_id),
    CONSTRAINT fk_notification_reads_notification FOREIGN KEY (notification_id) REFERENCES system_notifications (id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TEMPORARY TABLE notification_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO notification_translation_seed (english_value, estonian_value, russian_value) VALUES
('Last 30 days', 'Viimased 30 päeva', 'Последние 30 дней'),
('Archive', 'Arhiiv', 'Архив'),
('Notifications older than 30 days.', 'Üle 30 päeva vanused teavitused.', 'Уведомления старше 30 дней.'),
('Notifications from the last 30 days.', 'Viimase 30 päeva teavitused.', 'Уведомления за последние 30 дней.'),
('Unread notifications from the last 24 hours', 'Viimase 24 tunni lugemata teavitused', 'Непрочитанные уведомления за последние 24 часа'),
('Previous', 'Eelmine', 'Назад'),
('Next', 'Järgmine', 'Далее');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM notification_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM notification_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE notification_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.86', 'Add 30-day notification inbox, paginated older archive and per-user unread badge for the last 24 hours.', 'notifications, translations', 'Per-user notification read receipts and translation seeds.', NOW());
