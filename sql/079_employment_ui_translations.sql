DROP TEMPORARY TABLE IF EXISTS employment_translation_seed;

CREATE TEMPORARY TABLE employment_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO employment_translation_seed (english_value, estonian_value, russian_value) VALUES
('New department', 'Uus osakond', 'Новый отдел'),
('New workload', 'Uus koormus', 'Новая нагрузка'),
('Job title name', 'Ametinimetuse nimi', 'Название должности'),
('Department name', 'Osakonna nimi', 'Название отдела'),
('Workload name', 'Koormuse nimi', 'Название нагрузки'),
('Hide', 'Peida', 'Скрыть'),
('Activate', 'Aktiveeri', 'Активировать'),
('Edit contract', 'Muuda lepingut', 'Изменить договор'),
('Open-ended', 'Tähtajatu', 'Бессрочный'),
('Restore', 'Taasta', 'Восстановить');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui'
FROM employment_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN s.english_value WHEN 'et' THEN s.estonian_value WHEN 'ru' THEN s.russian_value END,
       'active'
FROM employment_translation_seed s
INNER JOIN system_translation_keys k ON k.translation_key = CONCAT('ui.', SHA2(s.english_value, 256))
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1;

DROP TEMPORARY TABLE IF EXISTS employment_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.92', 'Completed missing employment catalogue, contract form and action label translations in Estonian and Russian.', 'employment, translations', 'Translation seeds and version registration only.', NOW());
