CREATE TABLE IF NOT EXISTS employee_list_settings (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    page_size SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    CONSTRAINT fk_employee_list_settings_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('employees.search_name', 'employees'),
       ('employees.search', 'employees'),
       ('employees.clear_search', 'employees'),
       ('employees.list_settings', 'employees'),
       ('employees.per_page', 'employees'),
       ('employees.save_settings', 'employees'),
       ('employees.shared_settings', 'employees'),
       ('employees.results', 'employees'),
       ('employees.no_results', 'employees'),
       ('employees.pagination', 'employees'),
       ('employees.page_size_invalid', 'employees'),
       ('employees.page_size_saved', 'employees');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'employees.search_name' THEN CASE l.language_code WHEN 'et' THEN 'Otsi nime järgi' WHEN 'ru' THEN 'Поиск по имени' ELSE 'Search by name' END
           WHEN 'employees.search' THEN CASE l.language_code WHEN 'et' THEN 'Otsi' WHEN 'ru' THEN 'Найти' ELSE 'Search' END
           WHEN 'employees.clear_search' THEN CASE l.language_code WHEN 'et' THEN 'Tühjenda otsing' WHEN 'ru' THEN 'Сбросить поиск' ELSE 'Clear search' END
           WHEN 'employees.list_settings' THEN CASE l.language_code WHEN 'et' THEN 'Töötajate loendi seaded' WHEN 'ru' THEN 'Настройки списка сотрудников' ELSE 'Employee list settings' END
           WHEN 'employees.per_page' THEN CASE l.language_code WHEN 'et' THEN 'Töötajaid lehel' WHEN 'ru' THEN 'Сотрудников на странице' ELSE 'Employees per page' END
           WHEN 'employees.save_settings' THEN CASE l.language_code WHEN 'et' THEN 'Salvesta seaded' WHEN 'ru' THEN 'Сохранить настройки' ELSE 'Save settings' END
           WHEN 'employees.shared_settings' THEN CASE l.language_code WHEN 'et' THEN 'Kehtib kõigile selle kliendi kasutajatele, ka otsingutulemustele.' WHEN 'ru' THEN 'Применяется ко всем пользователям клиента, включая результаты поиска.' ELSE 'Applies to all users of this client, including search results.' END
           WHEN 'employees.results' THEN CASE l.language_code WHEN 'et' THEN 'Tulemusi' WHEN 'ru' THEN 'Результатов' ELSE 'Results' END
           WHEN 'employees.no_results' THEN CASE l.language_code WHEN 'et' THEN 'Töötajaid ei leitud.' WHEN 'ru' THEN 'Сотрудники не найдены.' ELSE 'No employees found.' END
           WHEN 'employees.pagination' THEN CASE l.language_code WHEN 'et' THEN 'Töötajate loendi leheküljed' WHEN 'ru' THEN 'Страницы списка сотрудников' ELSE 'Employee list pages' END
           WHEN 'employees.page_size_invalid' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta lehe suurus vahemikus 1 kuni 100.' WHEN 'ru' THEN 'Укажите размер страницы от 1 до 100.' ELSE 'Enter a page size between 1 and 100.' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Töötajate loendi seaded on kliendile salvestatud.' WHEN 'ru' THEN 'Настройки списка сотрудников сохранены для клиента.' ELSE 'Employee list settings saved for this client.' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.module_context = 'employees' AND k.translation_key IN (
    'employees.search_name', 'employees.search', 'employees.clear_search', 'employees.list_settings',
    'employees.per_page', 'employees.save_settings', 'employees.shared_settings', 'employees.results',
    'employees.no_results', 'employees.pagination', 'employees.page_size_invalid', 'employees.page_size_saved'
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.96', 'Added employee name search and numbered pagination with client administrator-managed shared page size.', 'employees, translations', 'Added employee_list_settings and English, Estonian and Russian translations.', NOW());
