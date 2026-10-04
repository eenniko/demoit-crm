CREATE TEMPORARY TABLE schedule_print_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schedule_print_translation_seed (english_value, estonian_value, russian_value) VALUES
('Print schedule', 'Prindi graafik', 'Печать графика'),
('Choose locations to print', 'Vali prinditavad asukohad', 'Выберите места для печати'),
('Select all', 'Vali kõik', 'Выбрать все'),
('Clear selection', 'Tühista valik', 'Снять выделение'),
('Print / Save as PDF', 'Prindi / salvesta PDF-ina', 'Печать / сохранить в PDF'),
('No locations available for this month.', 'Selles kuus pole prinditavaid asukohti.', 'В этом месяце нет мест для печати.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_print_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM schedule_print_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_print_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.71', 'Add monthly schedule printing with location selection.', NOW());