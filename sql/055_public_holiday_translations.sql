CREATE TEMPORARY TABLE public_holiday_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO public_holiday_translation_seed (english_value, estonian_value, russian_value) VALUES
('Public holidays XML', 'Riigipühade XML', 'XML государственных праздников'),
('Import new entries', 'Impordi uued kirjed', 'Импортировать новые записи'),
('Only missing entries are added. Existing records are never changed.', 'Lisatakse ainult puuduvad kirjed. Olemasolevaid kirjeid ei muudeta.', 'Добавляются только отсутствующие записи. Существующие записи не изменяются.'),
('Added %d new entries, %d were already present.', 'Lisati %d uut kirjet, %d olid juba olemas.', 'Добавлено новых записей: %d, уже существовало: %d.'),
('The XML upload failed.', 'XML-faili üleslaadimine ebaõnnestus.', 'Не удалось загрузить XML-файл.'),
('Select a valid public-holiday XML file.', 'Vali kehtiv riigipühade XML-fail.', 'Выберите корректный XML-файл государственных праздников.'),
('The uploaded file must have an .xml extension.', 'Üleslaaditava faili laiend peab olema .xml.', 'Расширение загружаемого файла должно быть .xml.'),
('The XML file must be no larger than 2 MB.', 'XML-faili suurus ei tohi ületada 2 MB.', 'Размер XML-файла не должен превышать 2 МБ.'),
('The uploaded XML file could not be read.', 'Üleslaaditud XML-faili ei õnnestunud lugeda.', 'Не удалось прочитать загруженный XML-файл.'),
('The XML file is empty, too large, or contains a forbidden declaration.', 'XML-fail on tühi, liiga suur või sisaldab keelatud deklaratsiooni.', 'XML-файл пуст, слишком велик или содержит запрещённое объявление.'),
('The file is not a valid public-holiday XML document.', 'Fail ei ole korrektne riigipühade XML-dokument.', 'Файл не является корректным XML-документом государственных праздников.'),
('The XML file must contain between 1 and 10,000 entries.', 'XML-fail peab sisaldama 1 kuni 10 000 kirjet.', 'XML-файл должен содержать от 1 до 10 000 записей.'),
('The XML contains an entry with invalid date, title, kind, or kind ID.', 'XML-fail sisaldab vigase kuupäeva, nimetuse, liigi või liigi ID-ga kirjet.', 'XML-файл содержит запись с неверной датой, названием, типом или идентификатором типа.'),
('Could not import the public holidays.', 'Riigipühi ei õnnestunud importida.', 'Не удалось импортировать государственные праздники.'),
('Invalid session token, please try again.', 'Vigane seansitunnus. Proovi uuesti.', 'Недействительный токен сеанса. Повторите попытку.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM public_holiday_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM public_holiday_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE public_holiday_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.68', 'Translate public-holiday XML import controls and messages.', NOW());