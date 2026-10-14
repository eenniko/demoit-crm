INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('New job title', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN 'New job title' WHEN 'et' THEN 'Uus ametinimetus' WHEN 'ru' THEN 'Новая должность' END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key = CONCAT('ui.', SHA2('New job title', 256));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.91', 'Added Estonian and Russian translations for the New job title form label.', 'employment, translations', 'Translation seeds and version registration only.', NOW());
