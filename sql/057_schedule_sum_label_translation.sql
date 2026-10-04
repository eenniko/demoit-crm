CREATE TEMPORARY TABLE schedule_sum_label_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schedule_sum_label_translation_seed (english_value, estonian_value, russian_value) VALUES
('Sum h', 'Kokku h', 'Всего ч');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_sum_label_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM schedule_sum_label_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_sum_label_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.70', 'Rename the schedule planned-hours column label to Sum.', NOW());