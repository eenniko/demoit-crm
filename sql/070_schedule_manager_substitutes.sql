CREATE TEMPORARY TABLE schedule_substitute_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO schedule_substitute_translation_seed (english_value, estonian_value, russian_value) VALUES
('Schedule substitutes', 'Graafikuasendajad', 'Заместители руководителя графика'),
('Manager being substituted', 'Asendatav manager', 'Замещаемый руководитель'),
('Assign substitute', 'Määra asendaja', 'Назначить заместителя'),
('End substitute rights', 'Lõpeta asendaja õigused', 'Прекратить права заместителя'),
('Manager', 'Manager', 'Руководитель'),
('Substitute', 'Asendaja', 'Заместитель'),
('Starts at', 'Algusaeg', 'Начало'),
('Ends at', 'Lõppaeg', 'Окончание'),
('Period', 'Periood', 'Период'),
('Reason', 'Põhjus', 'Причина'),
('Status', 'Olek', 'Статус'),
('active', 'aktiivne', 'активно'),
('scheduled', 'planeeritud', 'запланировано'),
('ended', 'lõppenud', 'завершено'),
('Substitutes can edit current and future months only while the assignment is active.', 'Asendajad saavad muuta jooksvat kuud ja tulevasi kuid ainult aktiivse asendusperioodi jooksul.', 'Заместители могут изменять текущий и будущие месяцы только в период активного замещения.'),
('Account roles are unchanged. No employment contract is required for the substitute.', 'Konto rollid ei muutu. Asendajal ei pea olema töölepingut.', 'Роли аккаунта не меняются. Заместителю не требуется трудовой договор.'),
('If the substitute is not listed, ask the client administrator to add and activate the account under Employees & permissions.', 'Kui asendajat pole loendis, palu kliendiadministraatoril lisada ja aktiveerida konto Töötajad ja õigused all.', 'Если заместителя нет в списке, попросите администратора клиента добавить и активировать аккаунт в разделе Сотрудники и права.'),
('Schedule substitute assigned. Existing account roles are unchanged.', 'Graafikuasendaja määratud. Konto olemasolevad rollid ei muutu.', 'Заместитель графика назначен. Существующие роли аккаунта не меняются.'),
('Schedule substitute rights ended.', 'Graafikuasendaja õigused lõpetatud.', 'Права заместителя графика прекращены.'),
('You are not allowed to assign a schedule substitute.', 'Sul pole õigust graafikuasendajat määrata.', 'У вас нет права назначать заместителя графика.'),
('Choose different users and a valid future-ending substitute period.', 'Vali erinevad kasutajad ja korrektne tulevikus lõppev asendusperiood.', 'Выберите разных пользователей и корректный период замещения с окончанием в будущем.'),
('A print-only Level F user cannot delegate manager editing rights.', 'Ainult printimisõigusega F kasutaja ei saa manageri muutmisõigust delegeerida.', 'Пользователь F с правом только печати не может передавать права редактирования руководителя.'),
('Both users must have active accounts in this client. Add the substitute under Employees & permissions first.', 'Mõlemal kasutajal peab olema selles kliendis aktiivne konto. Lisa asendaja esmalt Töötajad ja õigused alla.', 'Оба пользователя должны иметь активные аккаунты у этого клиента. Сначала добавьте заместителя в Сотрудники и права.'),
('The original user must manage another active employee contract during the substitute period.', 'Asendatav kasutaja peab asendusperioodil olema teise aktiivse töötaja lepingul manager.', 'Замещаемый пользователь должен руководить другим активным сотрудником по договору в период замещения.'),
('This substitute already has an overlapping assignment for this manager.', 'Sellel asendajal on juba selle manageri kattuv asendusperiood.', 'У этого заместителя уже есть пересекающееся назначение для данного руководителя.'),
('Schedule substitute assignment not found or not allowed.', 'Graafikuasendust ei leitud või toiming pole lubatud.', 'Назначение заместителя графика не найдено или действие не разрешено.'),
('Use the Schedule substitutes page to assign schedule editing rights.', 'Graafiku muutmisõiguse määramiseks kasuta Graafikuasendajad lehte.', 'Для назначения прав изменения графика используйте страницу заместителей графика.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_substitute_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM schedule_substitute_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_substitute_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.83', 'Add time-limited manager-assigned schedule shift substitution for current and future months without changing base account roles or requiring substitute employment contracts.', 'schedule, substitutes, translations', 'Translation seeds and version registration only - existing system_substitutes stores dedicated schedule assignments.', NOW());
