INSERT INTO system_languages (language_code, name, is_active, is_default)
VALUES ('et', 'Eesti', 1, 0), ('ru', 'Русский', 1, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context) VALUES
    ('layout.language', 'layout'),
    ('layout.signed_in_as', 'layout'),
    ('layout.log_out', 'layout'),
    ('layout.client_login', 'layout'),
    ('admin.dashboard', 'admin'),
    ('admin.client_solutions', 'admin'),
    ('admin.client_list', 'admin'),
    ('admin.add_client', 'admin'),
    ('admin.modules', 'admin'),
    ('admin.all_modules', 'admin'),
    ('admin.add_module', 'admin'),
    ('admin.dev_languages', 'admin'),
    ('admin.languages', 'admin'),
    ('admin.translations', 'admin'),
    ('admin.missing_translations', 'admin'),
    ('admin.users_permissions', 'admin'),
    ('admin.roles', 'admin'),
    ('admin.system_logs', 'admin'),
    ('admin.notifications', 'admin'),
    ('admin.support', 'admin'),
    ('admin.system_settings', 'admin'),
    ('panel.dashboard', 'panel'),
    ('panel.client_management', 'panel'),
    ('panel.modules', 'panel'),
    ('panel.active_modules', 'panel'),
    ('panel.employees_permissions', 'panel'),
    ('panel.patients', 'panel'),
    ('panel.property_structure', 'panel'),
    ('panel.employment', 'panel'),
    ('panel.work_schedule', 'panel'),
    ('panel.organisation', 'panel'),
    ('panel.substitutes', 'panel'),
    ('panel.reports_statistics', 'panel'),
    ('panel.settings', 'panel'),
    ('panel.support', 'panel'),
    ('panel.notifications', 'panel'),
    ('panel.my_account', 'panel');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_key_row.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM (
    SELECT 'layout.language' AS translation_key, 'Language' AS english_value, 'Keel' AS estonian_value, 'Язык' AS russian_value
    UNION ALL SELECT 'layout.signed_in_as', 'Signed in as', 'Sisse logitud:', 'Вы вошли как'
    UNION ALL SELECT 'layout.log_out', 'Log out', 'Logi välja', 'Выйти'
    UNION ALL SELECT 'layout.client_login', 'Client login', 'Kliendi sisselogimine', 'Вход клиента'
    UNION ALL SELECT 'admin.dashboard', 'Dashboard', 'Töölaud', 'Панель управления'
    UNION ALL SELECT 'admin.client_solutions', 'Client solutions', 'Kliendilahendused', 'Клиентские решения'
    UNION ALL SELECT 'admin.client_list', 'Client list', 'Kliendid', 'Список клиентов'
    UNION ALL SELECT 'admin.add_client', 'Add client', 'Lisa klient', 'Добавить клиента'
    UNION ALL SELECT 'admin.modules', 'Modules', 'Moodulid', 'Модули'
    UNION ALL SELECT 'admin.all_modules', 'All modules', 'Kõik moodulid', 'Все модули'
    UNION ALL SELECT 'admin.add_module', 'Add module', 'Lisa moodul', 'Добавить модуль'
    UNION ALL SELECT 'admin.dev_languages', 'Dev module: languages', 'Arendusmoodul: keeled', 'Модуль разработки: языки'
    UNION ALL SELECT 'admin.languages', 'Languages', 'Keeled', 'Языки'
    UNION ALL SELECT 'admin.translations', 'Translations', 'Tõlked', 'Переводы'
    UNION ALL SELECT 'admin.missing_translations', 'Missing translations', 'Puuduvad tõlked', 'Отсутствующие переводы'
    UNION ALL SELECT 'admin.users_permissions', 'Users & permissions', 'Kasutajad ja õigused', 'Пользователи и права'
    UNION ALL SELECT 'admin.roles', 'Roles', 'Rollid', 'Роли'
    UNION ALL SELECT 'admin.system_logs', 'System logs', 'Süsteemi logid', 'Системные журналы'
    UNION ALL SELECT 'admin.notifications', 'Notifications', 'Teavitused', 'Уведомления'
    UNION ALL SELECT 'admin.support', 'Support', 'Tugi', 'Поддержка'
    UNION ALL SELECT 'admin.system_settings', 'System settings', 'Süsteemi seaded', 'Настройки системы'
    UNION ALL SELECT 'panel.dashboard', 'Dashboard', 'Töölaud', 'Панель управления'
    UNION ALL SELECT 'panel.client_management', 'Client management', 'Kliendihaldus', 'Управление клиентом'
    UNION ALL SELECT 'panel.modules', 'Modules', 'Moodulid', 'Модули'
    UNION ALL SELECT 'panel.active_modules', 'Active modules', 'Aktiivsed moodulid', 'Активные модули'
    UNION ALL SELECT 'panel.employees_permissions', 'Employees & permissions', 'Töötajad ja õigused', 'Сотрудники и права'
    UNION ALL SELECT 'panel.patients', 'Patients', 'Patsiendid', 'Пациенты'
    UNION ALL SELECT 'panel.property_structure', 'Property structure', 'Kinnistu struktuur', 'Структура объекта'
    UNION ALL SELECT 'panel.employment', 'Employment', 'Töölepingud', 'Трудовые договоры'
    UNION ALL SELECT 'panel.work_schedule', 'Work schedule', 'Töögraafik', 'Рабочий график'
    UNION ALL SELECT 'panel.organisation', 'Organisation', 'Organisatsioon', 'Организация'
    UNION ALL SELECT 'panel.substitutes', 'Substitutes', 'Asendajad', 'Замещения'
    UNION ALL SELECT 'panel.reports_statistics', 'Reports & statistics', 'Aruanded ja statistika', 'Отчёты и статистика'
    UNION ALL SELECT 'panel.settings', 'Settings', 'Seaded', 'Настройки'
    UNION ALL SELECT 'panel.support', 'Support', 'Tugi', 'Поддержка'
    UNION ALL SELECT 'panel.notifications', 'Notifications', 'Teavitused', 'Уведомления'
    UNION ALL SELECT 'panel.my_account', 'My account', 'Minu konto', 'Моя учётная запись'
) AS seed
INNER JOIN system_translation_keys translation_key_row ON translation_key_row.translation_key = seed.translation_key
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.61', 'Add session language selection and database-backed shared UI translations for English, Estonian and Russian.', NOW());