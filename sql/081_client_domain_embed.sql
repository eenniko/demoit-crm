CREATE TABLE IF NOT EXISTS system_client_domain_embeds (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    customer_domain VARCHAR(253) NOT NULL,
    embed_token CHAR(64) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_domain_embeds_domain (customer_domain),
    UNIQUE KEY uq_client_domain_embeds_token (embed_token),
    CONSTRAINT fk_client_domain_embeds_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_client_domain_embeds_user FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('client_domain_embed', 'Customer domain embed', 'Download an iframe page that displays this client workspace on its own domain.', 'active', 0);

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Customer domain embed', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Download an iframe page that displays this client workspace on its own domain.', 256)), 'ui');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('embed.company_workspace', 'embed'),
       ('embed.signin_fallback', 'embed'),
       ('embed.open_new_tab', 'embed'),
       ('embed.customer_domain', 'embed'),
       ('embed.setup_title', 'embed'),
       ('embed.setup_help', 'embed'),
       ('embed.domain_label', 'embed'),
       ('embed.domain_hint', 'embed'),
       ('embed.save_domain', 'embed'),
       ('embed.download_page', 'embed'),
       ('embed.download_hint', 'embed'),
       ('embed.not_configured', 'embed'),
       ('embed.domain_saved', 'embed'),
       ('embed.invalid_domain', 'embed'),
       ('embed.domain_in_use', 'embed'),
       ('embed.module_inactive', 'embed');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'embed.company_workspace' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi tööruum' WHEN 'ru' THEN 'Рабочая область клиента' ELSE 'Client workspace' END
           WHEN 'embed.signin_fallback' THEN CASE l.language_code WHEN 'et' THEN 'Kui sisselogimine manustatud aknas ei tööta, ava CRM eraldi vahekaardil.' WHEN 'ru' THEN 'Если вход во встроенном окне не работает, откройте CRM в отдельной вкладке.' ELSE 'If sign-in is blocked in this embedded window, open the CRM in a separate tab.' END
           WHEN 'embed.open_new_tab' THEN CASE l.language_code WHEN 'et' THEN 'Ava uuel vahekaardil' WHEN 'ru' THEN 'Открыть в новой вкладке' ELSE 'Open in a new tab' END
           WHEN 'embed.customer_domain' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeeni leht' WHEN 'ru' THEN 'Страница на домене клиента' ELSE 'Customer domain page' END
           WHEN 'embed.setup_title' THEN CASE l.language_code WHEN 'et' THEN 'CRM-i kuvamine kliendi domeenil' WHEN 'ru' THEN 'Отображение CRM на домене клиента' ELSE 'Display CRM on a customer domain' END
           WHEN 'embed.setup_help' THEN CASE l.language_code WHEN 'et' THEN 'See moodul genereerib staatilise HTML-faili, mis kuvab selle kliendi tööruumi iframe’is. Rakendus ja andmed jäävad DemoIT CRM-i serverisse.' WHEN 'ru' THEN 'Этот модуль создаёт статический HTML-файл, который показывает рабочую область клиента во фрейме. Приложение и данные остаются на сервере DemoIT CRM.' ELSE 'This module generates a static HTML file that displays this client workspace in an iframe. The application and data remain on the DemoIT CRM server.' END
           WHEN 'embed.domain_label' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeen' WHEN 'ru' THEN 'Домен клиента' ELSE 'Customer domain' END
           WHEN 'embed.domain_hint' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta domeeninimi ilma https://, tee või pordita. Kliendi veebileht peab kasutama HTTPS-i.' WHEN 'ru' THEN 'Введите доменное имя без https://, пути или порта. Сайт клиента должен использовать HTTPS.' ELSE 'Enter the domain without https://, a path or port. The customer website must use HTTPS.' END
           WHEN 'embed.save_domain' THEN CASE l.language_code WHEN 'et' THEN 'Salvesta domeen' WHEN 'ru' THEN 'Сохранить домен' ELSE 'Save domain' END
           WHEN 'embed.download_page' THEN CASE l.language_code WHEN 'et' THEN 'Laadi HTML-leht alla' WHEN 'ru' THEN 'Скачать HTML-страницу' ELSE 'Download HTML page' END
           WHEN 'embed.download_hint' THEN CASE l.language_code WHEN 'et' THEN 'Laadi fail alla ja paigalda see kliendi domeeni veebijuure või sobiva alamlehe index.html-failina. Domeeni või tokeni muutmisel laadi uus fail alla.' WHEN 'ru' THEN 'Скачайте файл и разместите его как index.html в корне сайта клиента или в нужном каталоге. После изменения домена или токена скачайте новый файл.' ELSE 'Download the file and place it as index.html in the customer website root or a suitable directory. Download a new file after changing the domain or token.' END
           WHEN 'embed.domain_saved' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeen salvestati. Muudatuse rakendamiseks laadi uus leht alla.' WHEN 'ru' THEN 'Домен клиента сохранён. Для применения изменений скачайте новую страницу.' ELSE 'Customer domain saved. Download a new page to apply the change.' END
           WHEN 'embed.invalid_domain' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta korrektne domeeninimi ilma skeemi, tee või pordita.' WHEN 'ru' THEN 'Введите корректное доменное имя без схемы, пути или порта.' ELSE 'Enter a valid domain name without a scheme, path or port.' END
           WHEN 'embed.domain_in_use' THEN CASE l.language_code WHEN 'et' THEN 'See domeen on juba teise kliendi kasutuses.' WHEN 'ru' THEN 'Этот домен уже настроен для другого клиента.' ELSE 'This domain is already configured for another client.' END
           WHEN 'embed.module_inactive' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeeni moodul ei ole selle kliendi jaoks aktiivne.' WHEN 'ru' THEN 'Модуль домена клиента не активирован для этого клиента.' ELSE 'The customer-domain module is not active for this client.' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Domeen pole veel seadistatud.' WHEN 'ru' THEN 'Домен ещё не настроен.' ELSE 'No domain has been configured yet.' END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key LIKE 'embed.%';

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN CONCAT('ui.', SHA2('Customer domain embed', 256)) THEN
               CASE l.language_code
                   WHEN 'et' THEN 'Kliendi domeeni embed'
                   WHEN 'ru' THEN 'Встраивание на домен клиента'
                   ELSE 'Customer domain embed'
               END
           ELSE
               CASE l.language_code
                   WHEN 'et' THEN 'Laadi alla iframe-leht, mis kuvab selle kliendi tööruumi tema enda domeenil.'
                   WHEN 'ru' THEN 'Скачайте страницу iframe, которая отображает рабочую область этого клиента на его домене.'
                   ELSE 'Download an iframe page that displays this client workspace on its own domain.'
               END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    CONCAT('ui.', SHA2('Customer domain embed', 256)),
    CONCAT('ui.', SHA2('Download an iframe page that displays this client workspace on its own domain.', 256))
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.94', 'Added downloadable customer-domain iframe pages with tenant-pinned login.', 'authentication, client domain embed', 'Added system_client_domain_embeds, module catalogue seed and English/Estonian/Russian translations.', NOW());
