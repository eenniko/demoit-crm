INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('New reply', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Unread', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE
           WHEN k.translation_key = CONCAT('ui.', SHA2('New reply', 256)) THEN
               CASE l.language_code WHEN 'en' THEN 'New reply' WHEN 'et' THEN 'Uus vastus' WHEN 'ru' THEN 'Новый ответ' END
           ELSE
               CASE l.language_code WHEN 'en' THEN 'Unread' WHEN 'et' THEN 'Lugemata' WHEN 'ru' THEN 'Не прочитано' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (CONCAT('ui.', SHA2('New reply', 256)), CONCAT('ui.', SHA2('Unread', 256)));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.89', 'Highlight unread support ticket rows in bold with new reply or unread labels using the menu badge read rules.', 'support, translations', 'Translation seeds and version registration only.', NOW());
