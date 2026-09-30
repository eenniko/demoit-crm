# DemoIT CRM - töötajate mooduli AI ja arendusjuhend

**Juhendi versioon:** 1.0  
**Süsteemi arendusversioon:** 1.22  
**Staatus:** normatiivne tehniline juhend

## 1. AI roll ja eesmärk

Seda juhendit kasutav AI tegutseb DemoIT CRM-i vanilla PHP, PDO/MySQL ja Bootstrap arendajana. AI peab säilitama töötajate mooduli ettevõttepõhise isolatsiooni, autentimise tervikluse, transaktsioonilise provisioneerimise ja turvalise vana andmebaasi impordi.

Enne töötajate mooduli muutmist loe vähemalt:

1. `docs/01-projektinouded-ja-disaininouded.md`;
2. `docs/02-sql-andmebaasi-loogika.md`;
3. `docs/03-kasutajad-rollid-ja-oigused.md`;
4. `docs/04-php-arhitektuur-ja-mudelid.md`;
5. `docs/05-ai-eeskirjad-ja-prompt.md`;
6. `docs/06-tootajate-mooduli-kasutusjuhend.md`;
7. käesolev dokument.

## 2. Tehniline alus

- Backend: vanilla PHP, ilma Laravelita ja Symfonyta.
- Andmebaas: MySQL/MariaDB, InnoDB ja `utf8mb4`.
- Andmepääs: PDO native prepared statement'idega (`PDO::ATTR_EMULATE_PREPARES = false`).
- Autentimine: PHP sessioonid, `password_hash()` ja `password_verify()`.
- UI: Bootstrap ja serveris renderdatavad PHP vaated.
- Router: `public/index.php` front controller.
- Migratsioonid: järjestatud `sql/*.sql` failid ja `SchemaInstaller`.

## 3. Arhitektuuri kohustuslikud invariandid

### 3.1 Ettevõtte isolatsioon

Süsteem kasutab ühist MySQL andmebaasi. Ettevõtte andmed eraldatakse `client_id` abil.

AI ei tohi:

- luua dünaamiliselt MySQL kasutajaid;
- luua iga ettevõtte jaoks uut füüsilist andmebaasi;
- moodustada tabelinimesid kasutaja sisendist või kliendikoodist;
- teha töötajate päringut ilma usaldatud serveripoolse `client_id` filtrita;
- võtta aktiivset klienti URL-ist või vormi peidetud väljast.

Aktiivne ettevõte tuleb `ClientContext` sessioonikontekstist. Süsteemiadministraatori ettevõttevaates võib kliendi ID tulla kontrollitud admini marsruudist, kuid teenus peab ikkagi piirama päringu selle ID-ga.

### 3.2 Mooduli aktiveerimine

Töötajate mooduli tehniline võti on `employees`.

Mooduli aktiveerimine peab jääma transaktsiooniliseks:

1. lukusta mooduli ja kliendi aktivatsiooni olek;
2. loo või uuenda `system_client_modules` seos;
3. provisioneeri ettevõtte vajalikud andmed;
4. kirjuta `system_client_module_provisions` olekuks `ready`;
5. commit;
6. alles seejärel kirjuta auditlogi.

Provisioneerimisvea korral peab toimuma rollback. Moodul ei tohi jääda aktiivseks ilma ettevõtte seadistuseta.

Deaktiveerimine ei kustuta töötajaid ega seadeid. `system_client_module_provisions.status` muutub väärtuseks `inactive`. Uuesti aktiveerimine peab olema idempotentne.

### 3.3 Serveripoolne ligipääsukontroll

Kõik `/panel/users*` marsruudid peavad läbima `require_employee_module()` kontrolli. Haldustoimingud peavad lisaks läbima `require_panel_manager()` kontrolli ja nõudma aktiivset `client_admin` rolli.

Menüü peitmine ei asenda serveripoolset kontrolli. Iga POST-vorm peab kasutama `Csrf::field()` ja handler peab valideerima tokeni `Csrf::validate()` abil.

### 3.4 Kasutajakonto reeglid

- `system_users.username` on töötaja tunnus ja sisselogimisnimi.
- Kasutajatunnus on unikaalne ühe ettevõtte piires.
- Uus töötaja luuakse olekuga `inactive`.
- Uue töötaja vaikimisi roll tuleb `employee_module_settings.default_role_id` väärtusest.
- Esmasel provisioneerimisel on vaikimisi roll `level_f`.
- Parool salvestatakse ainult räsina.
- Aktiveerimisel ja resetil luuakse uus juhuslik ajutine parool.
- `must_change_password` peab ajutise parooli korral olema `1`.
- Viimast aktiivset kliendiadministraatorit ei tohi deaktiveerida ega tema rolli eemaldada.
- Füüsilist kustutamist ei kasutata.

### 3.5 E-post

`EmailService` saadab lihttekstilise konto e-kirja PHP `mail()` kaudu. Saatmise ebaõnnestumine ei tohi jätta parooli teadmata olekusse: administraatorile tuleb ajutine parool kuvada üks kord.

AI ei tohi:

- logida ajutist või püsivat parooli;
- lisada parooli URL-i, auditlogisse või püsivasse teavitustabelisse;
- saata parooli kasutajale, kelle e-post ei läbinud valideerimist;
- eeldada, et `mail()` tagastus `true` tõendab kirja lõplikku kohaletoimetamist.

SMTP-le üleminekul tuleb kasutada eraldi transpordikihti ja keskkonnamuutujaid; saladusi ei tohi lähtekoodi lisada.

## 4. Andmemudel

### 4.1 `system_users`

Töötaja ja autentimiskonto põhitabel. Mooduli jaoks olulised väljad:

- `client_id` - ettevõtte omanik;
- `username` - töötaja tunnus ja kasutajatunnus;
- `full_name` - töötaja nimi;
- `email` - konto teadete aadress;
- `phone` - telefon;
- `password_hash` - räsitud parool;
- `status` - `active` või `inactive`;
- `must_change_password` - kohustusliku paroolivahetuse tunnus.

Ära loo eraldi autentimistabelit töötajatele. Töötaja ja kasutaja on selles moodulis sama konto.

### 4.2 `system_user_roles`

Seob kasutaja rolli, ettevõtte ja valikulise organisatsiooniüksusega. Kõik rollipäringud peavad arvestama `client_id` ja aktiivset olekut.

### 4.3 `system_client_modules`

Määrab, kas kataloogimoodul on ettevõttele aktiivne. Ainult aktiivne kataloogimoodul ja aktiivne kliendi seos annavad moodulile ligipääsu.

### 4.4 `system_client_module_provisions`

Salvestab ettevõttepõhise mooduli andmeruumi valmisoleku:

- `client_id`;
- `module_id`;
- `schema_version`;
- `status` (`ready` või `inactive`);
- provisioneerimise ja muutmise ajad.

Unikaalne võti on `(client_id, module_id)`.

### 4.5 `employee_module_settings`

Ettevõtte töötajate mooduli seadistus:

- `client_id` on primaarvõti;
- `default_role_id` viitab ettevõtte uute ja imporditud töötajate vaikimisi rollile.

Seadistuse puudumine aktiivse mooduli korral on vigane olek. Teenus peab sellise olukorra korral toimingu katkestama, mitte vaikimisi globaalse rolli valima.

## 5. Teenuste vastutus

### `ModuleService`

- moodulikataloogi ja kliendi aktivatsioonide lugemine;
- aktiveerimise/deaktiveerimise transaktsioon;
- `ModuleProvisioningService` kutsumine;
- aktivatsiooni auditlogi.

### `ModuleProvisioningService`

- moodulipõhiste ettevõtteandmete loomine;
- skeemiversiooni ja valmisoleku salvestamine;
- Employees mooduli vaikimisi F-rolli seadistus;
- idempotentne uuesti provisioneerimine.

Uue mooduli provisioneerimisel lisa selle võti `SCHEMA_VERSIONS` kaarti ja moodulispetsiifiline meetod ainult siis, kui moodul vajab oma seadeid või algandmeid.

### `UserService`

- ettevõtte töötajate nimekiri;
- töötaja loomine ja muutmine;
- rolli määramine;
- aktiveerimine/deaktiveerimine;
- parooli lähtestamine ja muutmine;
- viimase kliendiadministraatori kaitse;
- töötajate auditlogi.

### `LegacyEmployeeImportService`

- vana SQL-dumpi piiratud parser;
- `db_user_data` väljade teisendus;
- duplikaatide vahelejätmine;
- impordi transaktsioon;
- ettevõtte provisioneeritud vaikimisi rolli kasutamine;
- koondauditlogi.

### `EmailService`

- konto ajutise parooli lihttekstiline e-kiri;
- e-posti formaadi kontroll;
- hostingu mailitranspordi kasutamine.

## 6. Marsruudid ja vaated

| Marsruut | Meetod | Otstarve | Nõutud kontroll |
|---|---|---|---|
| `/panel/users` | GET | töötajate nimekiri | login + aktiivne Employees moodul |
| `/panel/users/create` | GET/POST | töötaja loomine | aktiivne moodul + `client_admin` + POST-il CSRF |
| `/panel/users/edit` | GET/POST | töötaja muutmine | aktiivne moodul + `client_admin` + POST-il CSRF |
| `/panel/users/import` | GET/POST | vana dumpi import | aktiivne moodul + `client_admin` + POST-il CSRF |
| `/panel/users/toggle` | POST | konto aktiveerimine/deaktiveerimine | aktiivne moodul + `client_admin` + CSRF |
| `/panel/users/reset-password` | POST | ajutise parooli loomine | aktiivne moodul + `client_admin` + CSRF |

Vaated asuvad `views/panel/users/` kataloogis. Vaade ei tohi teha andmebaasipäringuid ega otsustada autoriseerimist.

## 7. Vana SQL-dumpi impordi reeglid

Lubatud allikas on ainult tabel `db_user_data`. Üleslaaditud SQL-i ei tohi kunagi PDO `exec()` ega muu andmebaasiühenduse kaudu käivitada.

Parser peab:

- leidma ainult `INSERT INTO db_user_data (...) VALUES (...)` read;
- toetama mitut väärtuste tuple'it;
- käsitlema SQL-i jutumärke, escape'e ja `NULL` väärtust;
- kontrollima veergude ja väärtuste arvu;
- ignoreerima DDL-i ja teiste tabelite käske;
- katkestama vigase lõpetamata väärtusloendi korral.

HTTP-kiht peab kontrollima:

- PHP upload-veakoodi;
- `.sql` laiendit;
- 5 MB suuruse piiri;
- `is_uploaded_file()` tulemust;
- CSRF-tokenit.

Andmete teisenduse invariandid:

- `db_users_id` -> `username`;
- `cn` -> `full_name`;
- vigane e-post -> `NULL`;
- tühi telefon -> `NULL`;
- `0000-00-00 00:00:00` või vigane kuupäev -> impordi hetke aeg;
- konto -> `inactive`;
- roll -> ettevõtte `default_role_id`;
- vana parool -> ei impordita;
- olemasolev või sama faili korduv kasutajatunnus -> jäetakse vahele.

Kogu ühe faili import peab toimuma transaktsioonis. Ühe rea andmebaasivea korral tuleb kogu selle faili kirjutus tagasi pöörata.

## 8. Migratsioonireeglid

Asjakohased migratsioonid:

- `006_employee_users.sql` - telefoni väli;
- `007_legacy_employee_import.sql` - impordifunktsiooni versioonikirje;
- `008_module_tenant_provisioning.sql` - provisioneerimise tabelid ja Employees mooduli registreerimine.

Uue andmebaasimuudatuse korral:

1. loo järgmise järjekorranumbriga SQL-fail;
2. kasuta `IF NOT EXISTS`, `INSERT IGNORE` või kontrollitud idempotentset konstruktsiooni;
3. registreeri migratsioon `SchemaInstaller::MIGRATIONS` loendis;
4. vali marker või `rowCheck`, mis tõendab kogu migratsiooni, mitte ainult selle esimest osa;
5. lisa `system_version_logs` kirje;
6. uuenda `CHANGELOG.md` ja süsteemi arendusversiooni;
7. ära muuda vana juba rakendatud migratsiooni tähendust.

## 9. Auditlogi toimingud

Praegused olulised võtmed:

- `user.created`;
- `user.updated`;
- `user.status_changed`;
- `user.password_reset`;
- `user.password_changed_self`;
- `user.legacy_imported`;
- `client_module.status_changed`.

Uus kirjutav toiming peab saama selge ja stabiilse auditvõtme. Auditlogisse ei tohi kirjutada parooli, parooliräsi, sessioonitokenit ega e-posti transpordi saladusi.

## 10. Kohustuslik testimaatriks

Iga töötajate moodulit mõjutava muudatuse järel kontrolli vähemalt:

1. kõik muudetud PHP-failid läbivad `php -l`;
2. võimalusel kõik projekti PHP-failid läbivad `php -l`;
3. aktiivse moodulita ettevõte saab `/panel/users*` teel 403;
4. aktiivse mooduliga ettevõtte menüüs kuvatakse Employees;
5. aktiveerimine loob `system_client_module_provisions` ja `employee_module_settings` kirje;
6. provisioneerimisviga jätab `system_client_modules` oleku muutmata;
7. deaktiveerimine säilitab töötajad ja seadistuse;
8. uus töötaja saab õige `client_id`, mitteaktiivse oleku ja vaikimisi rolli;
9. teise ettevõtte kasutajat ei saa lugeda ega muuta;
10. viimane aktiivne kliendiadministraator on kaitstud;
11. aktiveerimine ja reset seavad `must_change_password = 1`;
12. vana dump parser leiab oodatud read, säilitab Unicode'i ning käsitleb `NULL` ja escape'e;
13. sama dumpi kordusimport ei loo duplikaate;
14. vigane või üle 5 MB fail lükatakse tagasi;
15. CSRF-ta POST lükatakse tagasi;
16. auditlogi tekib, kuid ei sisalda paroole.

Päris kliendiandmebaasis ei tohi destruktiivset testi käivitada ilma selge loata. Kasuta testklienti või transaktsiooniga kontrollitavat testandmebaasi.

## 11. Muudatuse töövoog AI-le

1. Sõnasta üks lokaalne hüpotees ja seda ümber lükkav odav kontroll.
2. Leia käitumist omav teenus; ära pane äriloogikat vaatesse.
3. Kontrolli kliendikonteksti, mooduliguardi, rolli ja CSRF mõju.
4. Tee väikseim kooskõlaline muudatus.
5. Käivita kohe kitsas PHP lint või käitumistest.
6. Andmemuudatuse korral lisa idempotentne migratsioon ja provisioning.
7. Kontrolli tenant-isolatsiooni ning rollback'i.
8. Uuenda dokumentatsiooni ja versioonilogi.
9. Lõpeta kogu PHP lindi, staatilise veakontrolli ja `git diff --check` kontrolliga.

## 12. Keelatud lahendused

- Üleslaaditud SQL-i otsene käivitamine.
- `client_id` filtri puudumine kliendipõhises päringus.
- Kliendi ID usaldamine tavakasutaja vormiväljast.
- Autoriseerimine ainult menüü või nupu peitmisega.
- Parooli salvestamine või logimine lihttekstina.
- Töötaja füüsiline kustutamine.
- Viimase aktiivse kliendiadministraatori eemaldamine.
- Mooduli aktiivseks märkimine enne edukat provisioneerimist.
- Provisioneerimise või impordi osaline commit.
- Sama nimetatud PDO parameetri korduv kasutamine ühes päringus native prepared statement'ide korral.
- Raamistiku või ORM-i lisamine ainult selle mooduli jaoks.

## 13. Laiendamise kontrollküsimused

Enne uue töötajate funktsiooni loomist vasta:

- Kellele andmed kuuluvad ja kus kontrollitakse `client_id`?
- Kas Employees mooduli aktiivsus on kontrollitud serveris?
- Milline roll võib toimingu teha?
- Kas kirjutav toiming vajab CSRF-i ja transaktsiooni?
- Mis juhtub poole toimingu ebaõnnestumisel?
- Kas tegevus peab minema auditlogisse?
- Kas andmed säilivad deaktiveerimisel?
- Kas muudatus vajab uut skeemiversiooni või provisioneerimise täiendust?
- Kas kasutaja saab tundliku väärtuse näha rohkem kui ühe korra?
- Milline test tõendab teise ettevõtte andmete kättesaamatust?