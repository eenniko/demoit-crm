INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Building', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Wing', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Floor', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Room', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN labels.en WHEN 'et' THEN labels.et WHEN 'ru' THEN labels.ru END,
       'active'
FROM (
    SELECT 'Building' AS en, 'Hoone' AS et, 'Здание' AS ru
    UNION ALL SELECT 'Wing', 'Korpus', 'Корпус'
    UNION ALL SELECT 'Floor', 'Korrus', 'Этаж'
    UNION ALL SELECT 'Room', 'Ruum', 'Помещение'
) labels
INNER JOIN system_translation_keys k ON k.translation_key = CONCAT('ui.', SHA2(labels.en, 256))
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.90', 'Added Estonian and Russian property type translations for list and creation views.', 'property, translations', 'Translation seeds and version registration only.', NOW());
