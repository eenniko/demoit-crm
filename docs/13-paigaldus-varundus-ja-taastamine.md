# Paigaldamine, varundamine ja taastamine

**Sihtversioon: 1.85.** Juhend ei sisalda tegelikke ligipääsuandmeid. Taastamine tehakse esmalt eraldatud testikeskkonda.

## 1. Keskkonna eeldused

- PHP vähemalt 8.0: lähtekood kasutab `match`, `mixed`, `str_starts_with`, `str_contains` ja nullsafe operaatorit. Selle dokumentatsioonikontrolli CLI oli PHP 8.3.22; tootmishosti versioon pole siin kinnitatud.
- Laiendused: PDO + pdo_mysql, session, filter, hash, ctype, JSON, PCRE, date, SimpleXML ja libxml. Paroolifunktsioonid/juhuarvud peavad töötama. Legacy impordi ja pühade jaoks HTTP-upload ning kirjutatav PHP ajutine kataloog.
- MySQL-ühilduv server, InnoDB, utf8mb4, foreign keys, `information_schema`, PREPARE/EXECUTE ja SHA2. Täpne MySQL/MariaDB versioon ja sql_mode tuleb taastamiskatsel registreerida; versioonide täielikku ühilduvust pole testitud.
- Üks ette loodud tühi andmebaas ja selle konto. Rakendus ei loo füüsilist andmebaasi ega MySQL kasutajat.
- PDO kontol peab automigratsioonide jaoks olema vajalik DDL/DML õigus (sh CREATE, ALTER, INDEX, SELECT, INSERT, UPDATE, DELETE ja kasutatavate FK-de õigused). Tegelik minimaalse õiguse komplekt kinnitatakse hostis; ainult read/write konto ei piisa esimese käivituse jaoks.
- Apache koos mod_rewrite'iga, `.htaccess` lubatud, HTTPS; muu veebiserver peab andma samaväärse marsruutimise ja ligipääsupiirangud.
- Rakendus eeldab domeeni juurpaigaldust: `/panel`, `/admin`, `/assets` on absoluutsed teed. Alamkataloogi base URL-i seadet pole.
- Bootstrap 5.3.3 CSS/JS laaditakse jsDelivr CDN-ist; internetita taastamisel on vaja teadlikku lokaalse varakoopia lahendust.
- Kaasaegne brauser: fetch, FormData, Bootstrap modaalid, MutationObserver, Object.hasOwn, CSS color-mix ja printimise CSS. See pole kinnitatud kõigi vanade brauserite toena.

## 2. Konfiguratsioon

Loo `.env` turvalise näidisfaili järgi; võtmed:

```dotenv
DB_HOST=localhost
DB_NAME=<andmebaasi nimi>
DB_USER=<andmebaasi konto>
DB_PASS=<saladuste hoidlast>
DB_CHARSET=utf8mb4
APP_DEBUG=0
```

Ära lisa jutumärke automaatselt: laadija neid ei eemalda. Olemasolev protsessi keskkonnamuutuja on faili väärtusest tähtsam. `.env-test` ei ole rakenduse automaatselt loetav konfiguratsioon.

Tootmises `APP_DEBUG=0`; testkeskkonnas võib eraldi ligipääsupiirangu all kasutada `1`. Rakendusel pole DB_PORT, timezone, APP_URL, SMTP ega storage-path muutujat. Ebastandardne DB port või kohalik PDO socket vajab keskkonnaga sobiva ühenduse teadlikku otsust, mitte väljamõeldud seadistusvõtit.

Alates 1.85 määrab `config/database.php` PHP ajavööndiks `Europe/Tallinn` ning seadistab iga PDO MySQL sessiooni samale hetke offset'ile (+02:00 talvel, +03:00 suvel). See katab NOW/CURRENT_TIMESTAMP/CURDATE, vaikimisi created_at/updated_at, teavitused, toe loomise ja auditi/System logs. Offset arvutatakse PHP IANA andmetest, seega MySQL nimega ajavöönditabelid pole vajalikud. Serveri globaalset seadet ei muudeta. PHP timezone-andmed peavad olema ajakohased; otse phpMyAdminist või välisest skriptist kirjutades tuleb ka seal ajavöönd eraldi määrata. Graafiku kestuse UTC arvutused ei muutu.

### Vanade ajatemplite kontroll enne parandamist

DATETIME ei sisalda ajavööndit ja uue ühenduse seadistus ei teisenda olemasolevaid väärtusi. Enne parandust tee varukoopia, kinnita vana MySQL sessiooni ajavöönd ning leia kontrollitud vana/uue salvestusviisi piir (vajadusel audit-ID-d, deploy-logi ja versiooni 1.85 released_at). Ära lisa kõikidele aegadele kolme tundi: talvel on vahe kaks tundi ning osa PHP-aegu on juba kohalikud.

Koodi päritolu järgi kasutasid teavituste `created_at` ja toe `created_at` SQL-i DEFAULT CURRENT_TIMESTAMP ning auditi `created_at` SQL NOW-i. Toe 1.84 replies.created_at/closed_at, graafikuasenduse starts_at/ends_at ja kasutaja sisestatud ajad on PHP/kohalikud: neid ei tohi üldise SQL-ajaparandusega nihutada. `updated_at` on segakasutusega ning SQL-i poolt vaikimisi muudetav, seega seda ei tohi automaatselt käsitleda ühe ajavööndi väljana.

Järgmised päringud on ainult lugemiseks. Käivita tootmishosti phpMyAdminis ning võrdle tegeliku Eesti kellaajaga:

```sql
SELECT NOW() AS sql_now, UTC_TIMESTAMP() AS sql_utc,
       @@session.time_zone AS session_zone, @@global.time_zone AS global_zone;
SELECT version, released_at FROM system_version_logs WHERE version = '1.85';
SELECT t.id, t.created_at, t.closed_at, a.created_at AS audit_closed_at,
       TIMESTAMPDIFF(SECOND, a.created_at, t.closed_at) AS difference_seconds
FROM system_support_tickets t
INNER JOIN system_audit_logs a
    ON a.object_type = 'system_support_tickets'
   AND a.object_id = CAST(t.id AS CHAR)
   AND a.action = 'support_ticket.close'
WHERE t.closed_by_user_id IS NOT NULL
ORDER BY a.id DESC LIMIT 20;
```

phpMyAdmini sessioon ei ole rakenduse PDO sessioon: sealne NOW võib jätkuvalt olla UTC ning see ei tõenda uue rakendusühenduse viga. Sama sulgemise PHP closed_at ja SQL auditi aja vahe annab vana ühenduse kohta tugevama kontrolli: suvine 10800/talvine 7200 sekundit toetab UTC päritolu, 0 toetab juba ühtset kohalikku aega. Teiste väärtuste või puuduvate paaride korral tuleb kontroll jätkata, mitte andmeid muuta. UTC päritolu kinnitamisel teisenda ainult kinnitatud vanad SQL-väljad nende iga kuupäeva Europe/Tallinn reegliga, säilita juba kohaliku ajaga väljad ning piira parandust kontrollitud ID-dega. MySQL CONVERT_TZ nimega ajavöönd nõuab täidetud timezone-tabeleid, vastasel juhul kasuta kontrollitud PHP DateTime teisendust. Parandus peab olema ühekorraline, auditeeritud ja testitav varukoopia peal. Versiooni 1.85 migratsioon ise vanu andmeid ei nihuta.

HTTP failisuuruse piirmäärad: töötaja SQL 5 MiB, pühade XML 2 MiB. PHP `upload_max_filesize` peab lubama nõutud suurust, `post_max_size` faili ja multipart ümbrist. Suurte mitte-AJAX kuuvormide puhul arvesta `max_input_vars`; automaatsalvestus saadab ühe lahtri, mitte kogu tabelit.

## 3. Veebiserveri paigaldus

Praegune paigutus kasutab repositooriumi juurt document root'ina. Juur-`index.php` kaasab front controller'i. `.htaccess`:

- keelab directory listing'u;
- keelab HTTP ligipääsu `config`, `src`, `sql`, `views`, `docs`, `tests`, `.git`, `.vscode` ja `.env*` sisule;
- serveerib `/assets/...` failid `public/assets/...` alt;
- suunab mitteolemasolevad failid/kaustad `index.php` juurde, säilitades query string'i;
- päriselt olemasolevate failide puhul ei tee üldist ümbersuunamist.

Ära eelda, et ainult `public/` document root'iks määramine töötab sama konfiguratsiooniga: seal ei ole eraldi sama rewrite-faili. Kui valid avaliku kausta root'iks, loo sellele vastav serverikonfiguratsioon ja testi kogu route/asset nimekirja.

Upload'iga peab kaasas olema `.htaccess` ja kõik rakenduse/SQL failid. Ära kasuta isiklikku IDE/SFTP konfiguratsiooni dokumenteeritud deploy-protsessi alusena ega avalda selle sisu.

## 4. Uus paigaldus olemasoleva koodiga

1. Piira keskkonna ligipääs enne esimest HTTP päringut; `/setup` omanik peab olema operaator.
2. Loo tühi DB, sobiv konto ja `.env`; laadi kood ning kõik nummerdatud SQL-failid.
3. Tee esimene päring. Installer läbib registreeritud migratsioonid ja suunab lõpetamata süsteemi `/setup` juurde.
4. Loo esimene administraator, sisene kliendikoodiga `13666`, kontrolli `/setup` lukustumist.
5. Loo tavaklient ja anna ajutine parool üle turvalise kanali kaudu; vaheta parool konto lehel. Sundsuunamise puudumine on dokumenteeritud piirang.
6. Aktiveeri vajalikud moodulid: töötajad ja kinnistu; lepingud; graafik. Organisatsioon/patsiendid on sõltumatud.
7. Uuel kliendil lisa vajalikud ametid, osakonnad, koormused ja mallid. Hiljem loodud klient ei pruugi migratsiooniga lisatud vaikemalle/100% kataloogikirjet saada.
8. Loo aktiivsed töötajad ja põhilepingud, sea juhid; alles siis lisa ajutised lepingud ja graafik.
9. Impordi pühad, kui tööpäevade arvestus peab neid kasutama. Import mõjutab kõiki kliente.
10. Käivita [vastuvõtukontrollid](14-taastamise-vastuvotukontrollid.md) ja säilita tulemused.

## 5. Taastamine ainult dokumentatsioonist

1. Kopeeri kogu `docs/` pakett, mitte üks prompt. Kontrolli sihtversiooni, dokumentide linke ja SQL-paketi olemasolu.
2. Loo eraldatud keskkond ja tühi DB. Käivita [15-andmebaasi-taastepakett.sql](15-andmebaasi-taastepakett.sql) SQL-kliendi või phpMyAdmini kaudu **valitud tühjas andmebaasis**. See ei sisalda CREATE DATABASE/USE käsku, päris kasutajaid ega tootmisandmeid.
3. Pakett sisaldab järjestatud algmigratsioone, kogu tõlkeseemne ning PHP-installeris olnud nelja skeemiparanduse SQL-ekvivalenti. Seetõttu ei pea SQL-only taastamisel originaalset `sql/` ega installerit enne olemas olema.
4. Peata SQL vea korral. Ära kasuta `--force`, ära jätka pimesi ega kirjuta puuduva skeemi asemel versioonilogi edumärget.
5. Taasta failivastutused, teenuseliidesed, handler'id, vormiväljad ja käitumine dokumentide 11/12/16 järgi. Kui soovid ka tulevasi automigratsioone, taasta järjestatud register ja parandus-handler'id dokumendi 12 järgi; puhta süsteemi SQL-paketti ei käivitata igal HTTP päringul.
6. Taasta serveri ligipääsupiirangud ja keskkonnasaladused, seejärel käivita algseadistus ning moodulite põhivood.
7. Testi kahe kliendiga ja mõõdetavate graafikunäidetega. Eraldi kinnita ühe kuu sees järjestikuste põhilepingute ja eri juhiga ajutiste ridade käitumine.
8. Funktsionaalne taastamine pole baittäpne originaal. Täpse HTML/CSS/JS ja kõigi tekstipaigutuste taastamiseks kasuta kinnitatud lähtekoodivarukoopiat.

Pakett on mõeldud **tühja skeemi loomiseks**, mitte kontrollimata tootmisuuenduseks. Kuigi paljud alglaused on idempotentsed, võivad seemne UPDATE-id muuta olemasolevaid kohandusi ja osaliselt rakendatud DDL vajab kontrolli.

## 6. Varundamine ja pärisandmete taastamine

Dokumentatsioon taastab struktuuri ja käitumise. Pärisandmete jaoks hoia eraldi krüpteeritud, juurdepääsupiiranguga varukoopia:

- kõik DB tabelid, andmed, indeksid/FK-d ja vajalikud serveri metaandmed;
- kinnitatud lähtekoodi väljalase, samuti teadlikult säilitatavad pooleliolevad muudatused;
- kasutatud PHP/MySQL versioon, charset/collation, sql_mode, ajavööndid ja serveri vhost/rewrite seaded;
- TLS/DNS/meilitranspordi taastamise juhised; saladused eraldi hoidlas;
- pühade algfail ja vajalikud impordiandmed vastavalt isikuandmete säilitamisreeglitele.

Näide operaatori masinas, kui MySQL klienditööriistad on paigaldatud; asenda nurksulgude väärtused. Parooli ära lisa käsureale:

```powershell
mysqldump --host=<host> --user=<konto> --password --single-transaction --default-character-set=utf8mb4 --result-file=C:\Backups\demoit-crm.sql <db>
```

DDL muutused tuleb dump'i ajaks peatada. Varukoopia pole tõendatud enne eraldi DB-sse importimist ning konto-, graafiku- ja logikontrolli. Planeeri sagedus, säilitusaeg, krüpteerimine ning RPO/RTO vastavalt tellija nõuetele; projekt ei määra neid arve.

Pärisandmete taastamisel **ära käivita esmalt tühja skeemi taastepaketti ja siis eelda, et dump on lihtsalt lisatav**. Impordi täielik dump uude tühja DB-sse, ühenda sellega sobiva väljalaske kood ja lase vajadusel ainult puuduvad migratsioonid kontrollitud kujul teha. Säilita kliendi/kasutaja/lepingu/malli ID-d, räsi, audit ja algseadistuse lukustus. Ära ava `/setup` uuesti tootmiskonto taastamise asenduseks.

## 7. Uuendamine ja tagasipööramine

Enne uuendust varunda DB + kood, fikseeri väljalase ja vajadusel sulge ligipääs migratsiooni ajaks. Laadi seotud failid ühtse väljalaskena; suvaline SQL-failide osaline upload ei ole korrektne uuendus. Esimesel päringul võib käivituda DDL, mistõttu pooleli deploy ajal ei lasta paralleelpäringutel installeri vastu võistelda.

Puuduva migratsioonifaili korral võib praegune installer selle vahele jätta; kontrolli faili olemasolu ise. Versioonilogi olemasolu üksi ei tõenda kõigi tabelite/veergude olemasolu.

Üldisi down-migratsioone ei ole. Skeemimuudatuse tagasipööramine toimub sobiva DB varukoopia ja sama väljalaske koodi koos taastamisega. Ainult koodi tagasipööramine pärast DDL-i ei ole garanteeritud. Versiooni 1.76 puhul äriskeem ei muutu; tagasipööramine ei nõua ärikirjete kustutamist, uut versiooniajaloo rida võib säilitada.

## 8. Tõrkeotsing ja käituse kontroll

| Sümptom | Kontroll |
|---|---|
| 500 esimesel päringul | DB konto/ühendus, pdo_mysql, DDL õigused, SQL failide olemasolu, information_schema; piiratud testis debug |
| 500 pärast versiooni 1.76 dokumentatsiooni uuendust | Migreerimise 063 tekstis oli semikoolon SQL-stringi sees. Laadi parandatud 063 ja versiooni 1.77 installer/064. Andmeid ega setup-lukustust ei kustutata |
| `/panel` 404 või puhtad URL-id ei tööta | document root, mod_rewrite, AllowOverride ja `.htaccess` |
| `/assets/css/app.css` ei lae | `/assets` rewrite ja `public/assets` failid |
| Kõik URL-id suunavad setup'i | `system_initial_setup.id=1`, is_completed; kasutaja olemasolu ei asenda seda |
| 403 moodulis | aktiivne kataloog ja kliendiseos; schedule vajab kolme moodulit; roll |
| Graafik tühi | konto aktiivsus, lepingu kuu/periood, archived_at, manager_user_id ja asukoht |
| Vale Required või Tri-OT | pühade liik, koormuse hetkekoopia/kuu erand, lepingu päevad, neljakuulise perioodi piir |
| AJAX unexpected response | sisselogimine, HTML 403/500, õige AJAX päis ja JSON content-type |
| Graafiku DB viga | PHP error_log ja admini SQLSTATE/kood; auditveerud ja lepingu-päeva indeks |
| Kirja ei tulnud | PHP mailitransport/saatjadomeen, spam/DNS, korrektne e-post; edukas mail ei taga kohaletoimetamist |
| Import ei tööta | upload error, post/upload suurus, laiend, tegelik HTTP-upload, SQL/XML formaat |
| Eesti/vene tõlge puudub | aktiivne keel, ingliskeelne aktiivne algväärtus, täpne lähtefraas ja JavaScript |
| Prindivärvid/veerud puuduvad | brauseri landscape, taustade printimine, valitud grupid, skaleerimine |

PHP serveri error_log'i asukoha määrab hosting; `storage/logs/` ignore-reegel ei tähenda, et rakendus kirjutaks sinna. Rakendus ei sisalda scheduler'it, worker'it, healthcheck endpoint'i ega automaatset varundust. `/` HTML laadimine üksi ei tõenda kõigi moodulite tööd.

Enne avalikku avamist kontrolli HTTP kaudu kaitstud kaustade ja `.env*` ligipääsukeeldu, ilma saladuste sisu väljundisse kopeerimata. Algseadistuse kaitse, TLS ja andmekaitsetingimused on operaatori vastutus.
