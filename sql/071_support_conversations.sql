CREATE TABLE IF NOT EXISTS system_support_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_support_replies_thread (ticket_id, created_at, id),
    CONSTRAINT fk_support_replies_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_replies_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_support_participants (
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    added_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, user_id),
    KEY idx_support_participant_user (user_id, ticket_id),
    CONSTRAINT fk_support_participants_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_participants_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_participants_added_by FOREIGN KEY (added_by_user_id) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE system_support_tickets SET closed_at = updated_at WHERE status = 'closed' AND closed_at IS NULL;

CREATE TEMPORARY TABLE support_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO support_translation_seed (english_value, estonian_value, russian_value) VALUES
('Recipient', 'Vastaja', 'Получатель'),
('System support', 'Süsteemi administraator', 'Администратор системы'),
('Client administrator', 'Kliendi administraator', 'Администратор клиента'),
('Employee manager', 'Töötaja manager', 'Руководитель сотрудника'),
('Choose recipient', 'Vali vastaja', 'Выберите получателя'),
('No available recipients.', 'Sobivaid vastajaid ei ole.', 'Нет доступных получателей.'),
('Open tickets', 'Avatud päringud', 'Открытые обращения'),
('Recently closed tickets', 'Viimati suletud päringud', 'Недавно закрытые обращения'),
('The 10 most recently closed tickets are shown.', 'Kuvatakse 10 viimati suletud päringut.', 'Показаны 10 последних закрытых обращений.'),
('No open tickets.', 'Avatud päringuid ei ole.', 'Нет открытых обращений.'),
('No closed tickets.', 'Suletud päringuid ei ole.', 'Нет закрытых обращений.'),
('Closed', 'Suletud', 'Закрыто'),
('open', 'avatud', 'открыто'),
('in_progress', 'töös', 'в работе'),
('closed', 'suletud', 'закрыто'),
('Support ticket', 'Toe päring', 'Обращение в поддержку'),
('Conversation', 'Vestlus', 'Переписка'),
('Participants', 'Osalejad', 'Участники'),
('Add participant', 'Lisa osaleja', 'Добавить участника'),
('Choose participant', 'Vali osaleja', 'Выберите участника'),
('Invited participants', 'Kutsutud osalejad', 'Приглашённые участники'),
('Reply', 'Vastus', 'Ответ'),
('Send reply', 'Saada vastus', 'Отправить ответ'),
('Close ticket', 'Sulge päring', 'Закрыть обращение'),
('Back to support', 'Tagasi toe lehele', 'Вернуться к поддержке'),
('Only the creator, recipient and invited participants can see this conversation.', 'Vestlust näevad ainult looja, vastaja ja kutsutud osalejad.', 'Переписку видят только автор, получатель и приглашённые участники.'),
('System support tickets are visible to the system support team.', 'Süsteemi administraatorile saadetud päringuid näeb süsteemi toe meeskond.', 'Обращения администратору системы видит команда системной поддержки.'),
('This ticket is closed and read-only.', 'See päring on suletud ja ainult loetav.', 'Это обращение закрыто и доступно только для чтения.'),
('Reply added.', 'Vastus lisatud.', 'Ответ добавлен.'),
('Participant added.', 'Osaleja lisatud.', 'Участник добавлен.'),
('Support ticket closed.', 'Toe päring suletud.', 'Обращение закрыто.'),
('Choose a ticket recipient.', 'Vali päringu vastaja.', 'Выберите получателя обращения.'),
('Choose an available recipient for this ticket.', 'Vali selle päringu jaoks sobiv vastaja.', 'Выберите доступного получателя для этого обращения.'),
('Subject must not exceed 191 characters.', 'Teema võib olla kuni 191 märki pikk.', 'Тема не должна превышать 191 символ.'),
('Message is required.', 'Sõnum on kohustuslik.', 'Сообщение обязательно.'),
('Message must not exceed 65535 bytes.', 'Sõnum võib olla kuni 65535 baiti pikk.', 'Сообщение не должно превышать 65535 байт.'),
('Support ticket access denied.', 'Toe päringu juurdepääs keelatud.', 'Доступ к обращению запрещён.'),
('Support ticket not found or access denied.', 'Toe päringut ei leitud või juurdepääs on keelatud.', 'Обращение не найдено или доступ запрещён.'),
('Invalid ticket action.', 'Vigane päringu toiming.', 'Недопустимое действие с обращением.'),
('This action requires POST.', 'See toiming nõuab POST-päringut.', 'Для этого действия требуется POST-запрос.'),
('Only the creator or recipient can invite participants or close this ticket.', 'Osalejaid saab kutsuda ja päringut sulgeda ainult looja või vastaja.', 'Только автор или получатель может приглашать участников или закрывать обращение.'),
('Choose an active employee who is not already a participant.', 'Vali aktiivne töötaja, kes ei ole veel osaleja.', 'Выберите активного сотрудника, который ещё не является участником.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM support_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM support_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE support_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.84', 'Private recipient-directed support conversations with replies, invited participants and a read-only latest-ten closed archive.', 'support, translations', 'Ticket recipient and closure columns, reply and participant tables, legacy closed timestamp backfill and translations.', NOW());
