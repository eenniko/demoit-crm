INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)), 'ui'),
       (CONCAT('ui.', SHA2('The exception cannot extend beyond this contract.', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE
           WHEN k.translation_key = CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)) THEN
               CASE l.language_code
                   WHEN 'en' THEN 'Consecutive days (including selected day)'
                   WHEN 'et' THEN 'Päevade arv (koos valitud päevaga)'
                   WHEN 'ru' THEN 'Количество дней (включая выбранный день)'
               END
           ELSE
               CASE l.language_code
                   WHEN 'en' THEN 'The exception cannot extend beyond this contract.'
                   WHEN 'et' THEN 'Erand ei saa ulatuda töölepingu kehtivusajast kaugemale.'
                   WHEN 'ru' THEN 'Исключение не может выходить за пределы срока договора.'
               END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)),
    CONCAT('ui.', SHA2('The exception cannot extend beyond this contract.', 256))
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.93', 'Added consecutive multi-day exception assignments from a selected schedule date, with cross-month contract and permission validation.', 'schedule, translations', 'Translation seeds and version registration only.', NOW());
