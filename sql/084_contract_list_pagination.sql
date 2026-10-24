CREATE TABLE IF NOT EXISTS employment_contract_list_settings (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    page_size SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    CONSTRAINT fk_contract_list_settings_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('employment.list_settings', 'employment'),
       ('employment.per_page', 'employment'),
       ('employment.no_results', 'employment'),
       ('employment.pagination', 'employment'),
       ('employment.page_size_saved', 'employment');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'employment.list_settings' THEN CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi seaded' WHEN 'ru' THEN 'Настройки списка трудовых договоров' ELSE 'Contract list settings' END
           WHEN 'employment.per_page' THEN CASE l.language_code WHEN 'et' THEN 'Töölepinguid lehel' WHEN 'ru' THEN 'Договоров на странице' ELSE 'Contracts per page' END
           WHEN 'employment.no_results' THEN CASE l.language_code WHEN 'et' THEN 'Töölepinguid ei leitud.' WHEN 'ru' THEN 'Трудовые договоры не найдены.' ELSE 'No contracts found.' END
           WHEN 'employment.pagination' THEN CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi leheküljed' WHEN 'ru' THEN 'Страницы списка трудовых договоров' ELSE 'Contract list pages' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi seaded on kliendile salvestatud.' WHEN 'ru' THEN 'Настройки списка трудовых договоров сохранены для клиента.' ELSE 'Contract list settings saved for this client.' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    'employment.list_settings', 'employment.per_page', 'employment.no_results',
    'employment.pagination', 'employment.page_size_saved'
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.97', 'Added contract employee-name search and numbered pagination with client administrator-managed shared page size.', 'employment, translations', 'Added employment_contract_list_settings and English, Estonian and Russian translations.', NOW());
