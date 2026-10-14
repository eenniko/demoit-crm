CREATE TABLE IF NOT EXISTS system_support_reads (
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, user_id),
    CONSTRAINT fk_support_reads_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_reads_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Unopened active support tickets', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code
           WHEN 'en' THEN 'Unopened active support tickets'
           WHEN 'et' THEN 'Avamata aktiivsed toe päringud'
           WHEN 'ru' THEN 'Неоткрытые активные обращения в поддержку'
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key = CONCAT('ui.', SHA2('Unopened active support tickets', 256));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.87', 'Count active support tickets never opened by the recipient or participant, treating creators as already read.', 'support, translations', 'Per-user first-open support receipts and badge translations.', NOW());
