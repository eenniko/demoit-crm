INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('admin.client_admins', 'admin'),
       ('admin.client_admin_add', 'admin'),
       ('admin.client_admin_temp_password', 'admin'),
       ('admin.client_admin_none', 'admin'),
       ('admin.client_admin_username', 'admin'),
       ('admin.client_admin_name', 'admin'),
       ('admin.client_admin_contact', 'admin'),
       ('admin.client_admin_status', 'admin'),
       ('admin.client_admin_edit', 'admin'),
       ('admin.client_admin_reset', 'admin'),
       ('admin.client_admin_back', 'admin'),
       ('admin.client_admin_email', 'admin'),
       ('admin.client_admin_phone', 'admin'),
       ('admin.client_admin_temp_password_note', 'admin'),
       ('admin.client_admin_create', 'admin'),
       ('admin.client_admin_edit_title', 'admin'),
       ('admin.client_admin_save', 'admin');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'admin.client_admins' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi administraatorid' WHEN 'ru' THEN 'Администраторы клиента' ELSE 'Client administrators' END
           WHEN 'admin.client_admin_add' THEN CASE l.language_code WHEN 'et' THEN 'Lisa administraator' WHEN 'ru' THEN 'Добавить администратора' ELSE 'Add administrator' END
           WHEN 'admin.client_admin_temp_password' THEN CASE l.language_code WHEN 'et' THEN 'Ajutine parool (kuvatakse ainult üks kord):' WHEN 'ru' THEN 'Временный пароль (показывается только один раз):' ELSE 'Temporary password (shown only once):' END
           WHEN 'admin.client_admin_none' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi administraatoreid pole veel lisatud.' WHEN 'ru' THEN 'Администраторы клиента ещё не добавлены.' ELSE 'No client administrators yet.' END
           WHEN 'admin.client_admin_username' THEN CASE l.language_code WHEN 'et' THEN 'Kasutajanimi' WHEN 'ru' THEN 'Имя пользователя' ELSE 'Username' END
           WHEN 'admin.client_admin_name' THEN CASE l.language_code WHEN 'et' THEN 'Täisnimi' WHEN 'ru' THEN 'Полное имя' ELSE 'Full name' END
           WHEN 'admin.client_admin_contact' THEN CASE l.language_code WHEN 'et' THEN 'Kontakt' WHEN 'ru' THEN 'Контакт' ELSE 'Contact' END
           WHEN 'admin.client_admin_status' THEN CASE l.language_code WHEN 'et' THEN 'Olek' WHEN 'ru' THEN 'Статус' ELSE 'Status' END
           WHEN 'admin.client_admin_edit' THEN CASE l.language_code WHEN 'et' THEN 'Muuda' WHEN 'ru' THEN 'Изменить' ELSE 'Edit' END
           WHEN 'admin.client_admin_reset' THEN CASE l.language_code WHEN 'et' THEN 'Lähtesta parool' WHEN 'ru' THEN 'Сбросить пароль' ELSE 'Reset password' END
           WHEN 'admin.client_admin_back' THEN CASE l.language_code WHEN 'et' THEN 'Tagasi kliendi juurde' WHEN 'ru' THEN 'Назад к клиенту' ELSE 'Back to client' END
           WHEN 'admin.client_admin_email' THEN CASE l.language_code WHEN 'et' THEN 'E-post' WHEN 'ru' THEN 'Электронная почта' ELSE 'E-mail' END
           WHEN 'admin.client_admin_phone' THEN CASE l.language_code WHEN 'et' THEN 'Telefon' WHEN 'ru' THEN 'Телефон' ELSE 'Phone' END
           WHEN 'admin.client_admin_temp_password_note' THEN CASE l.language_code WHEN 'et' THEN 'Ajutine parool luuakse automaatselt ja kuvatakse pärast loomist ühe korra.' WHEN 'ru' THEN 'Временный пароль будет создан автоматически и показан один раз после создания.' ELSE 'A temporary password will be generated and shown once after creation.' END
           WHEN 'admin.client_admin_create' THEN CASE l.language_code WHEN 'et' THEN 'Loo administraator' WHEN 'ru' THEN 'Создать администратора' ELSE 'Create administrator' END
           WHEN 'admin.client_admin_edit_title' THEN CASE l.language_code WHEN 'et' THEN 'Muuda kliendi administraatorit' WHEN 'ru' THEN 'Изменить администратора клиента' ELSE 'Edit client administrator' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Salvesta muudatused' WHEN 'ru' THEN 'Сохранить изменения' ELSE 'Save changes' END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key LIKE 'admin.client_admin_%';

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.95', 'Added client administrator-only management to client details.', 'clients, users, translations', 'Added English, Estonian and Russian translations for client administrator management. Existing user and role schema is reused.', NOW());
