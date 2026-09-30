# DemoIT CRM – muudatuste logi

## Versioon 1.23 – 30. september 2026

- GitHubi avalehele (`README.md`) lisatud kõigi seniste versioonide kakskeelne lühikokkuvõte ja link täielikule muudatuste logile.
- Taastatud versiooni 1.1 dokumentatsioonimuudatuste kirje Giti ajaloo põhjal.
- Mõjutatud failid: `README.md`, `CHANGELOG.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `sql/010_readme_version_history.sql` ja `src/Services/SchemaInstaller.php`. Rakenduse moodulite käitumine ei muutu.
- Andmebaasimuudatus: uus idempotentne migratsioon registreerib versiooni 1.23 tabelis `system_version_logs`; skeemimuudatusi ei ole.
- Kontrollitud: README versioonide loend kattub muudatuste logi versioonidega; PHP süntaksi kontroll ja migratsiooni registreeringu kontroll.
- Järgmiseks versiooniks pärast 1.23 on `1.24`.

## Versioon 1.22 – 30. september 2026

- Lisatud `docs/06-tootajate-mooduli-kasutusjuhend.md`: süsteemiadministraatori ja kliendiadministraatori käsiraamat mooduli aktiveerimise, töötajate halduse, konto aktiveerimise, paroolide, vana SQL-dumpi impordi, auditlogi ja tõrkeotsingu kohta.
- Lisatud `docs/07-tootajate-mooduli-ai-arendusjuhend.md`: normatiivne AI/arendaja juhend tenant-isolatsiooni, transaktsioonilise provisioneerimise, andmemudeli, teenuste vastutuse, turvareeglite, migratsioonide ja kohustusliku testimaatriksiga.
- Uuendatud AI põhiprompt süsteemiversioonile 1.22, lisatud viited uutele moodulijuhenditele ning täpsustatud ühise MySQL andmebaasi `client_id`-põhine isolatsioon ja projekti versioonipoliitika.
- Lisatud `sql/009_employee_module_documentation.sql`, mis registreerib dokumentatsioonipaketi versiooni olemasolevates paigaldustes.
- Järgmiseks versiooniks pärast 1.22 on `1.23`.

## Versioon 1.21 – 30. september 2026

- Töötajate funktsionaalsus registreeritud aktiveeritava `employees` moodulina; C-paneli töötajate menüü ja kõik `/panel/users*` marsruudid on kättesaadavad ainult ettevõttele aktiveeritud mooduli korral.
- Lisatud ettevõttepõhine moodulite provisioneerimine: `system_client_module_provisions` salvestab iga ettevõtte ja mooduli andmeruumi skeemiversiooni ning valmisoleku.
- Lisatud `employee_module_settings`, mis luuakse ettevõttele töötajate mooduli aktiveerimisel ja määrab selle ettevõtte töötajate vaikimisi F-taseme rolli.
- Mooduli aktiveerimine, kliendi-mooduli seos ja vajalike ettevõtte andmete loomine toimuvad ühe andmebaasitransaktsioonina. Provisioneerimise vea korral aktiveerimine tühistatakse täielikult.
- Mooduli deaktiveerimine ei kustuta ettevõtte andmeid; uuesti aktiveerimisel kontrollitakse ja uuendatakse provisioneerimise olekut idempotentselt.
- Lahendus kasutab projekti kinnitatud ühist MySQL andmebaasi ja `client_id`-põhist ettevõtete isolatsiooni, mitte hostingu tasemel dünaamilisi MySQL kasutajaid või eraldi andmebaase.
- Järgmiseks versiooniks pärast 1.21 on `1.22`.

## Versioon 1.20 – 30. september 2026

- Töötajate haldusse lisatud eraldi vana andmebaasi SQL-dumpi üleslaadimine (`/panel/users/import`), ligipääs ainult kliendiadministraatorile.
- Import loeb ainult vana `db_user_data` tabeli `INSERT` read ega käivita üleslaaditud SQL-i andmebaasis.
- Vana `db_users_id` kantakse üle töötaja tunnuseks ja kasutajatunnuseks; nimi, kehtiv e-post, telefon ning korrektsed loomise/muutmise ajad säilitatakse.
- Imporditud kontod luuakse mitteaktiivsena ja F-taseme õigustega. Paroole ei impordita ning olemasoleva sama kasutajatunnusega kirjed jäetakse vahele.
- Üleslaadimisel kehtib `.sql` failitüübi ja 5 MB suuruse kontroll; toiming salvestatakse auditlogisse.
- Järgmiseks versiooniks pärast 1.20 on `1.21`.

## Versioon 1.19 – 30. september 2026

- Kasutajate haldus laiendatud töötajate mooduliks: töötaja nimi, töötaja kood/kasutajatunnus, e-post, telefon, ligipääsutase ja organisatsiooniüksus.
- Uus töötaja luuakse vaikimisi mitteaktiivsena ja F-taseme õigustega; administraator saab konto pärast andmete kontrollimist aktiveerida.
- Lisatud töötaja muutmisvaade ning viimase aktiivse kliendiadministraatori rolli eemaldamise kaitse.
- Konto aktiveerimine ja parooli lähtestamine genereerivad ajutise parooli ning saadavad kasutajale sisselogimisandmed e-postiga. Saatmise ebaõnnestumisel kuvatakse ajutine parool administraatorile.
- Lisatud automaatmigratsioon `sql/006_employee_users.sql`, mis lisab `system_users.phone` välja olemasolevatele paigaldustele.
- Järgmiseks versiooniks pärast 1.19 on `1.20`.

## Versioon 1.18 – 20. september 2026

- "Organisation" ja "Substitutes" teisendatud pärisalikuks aktiveeritavaks mooduliks (`organisation`), samal põhimõttel nagu "Patients": `sql/005_module_organisation.sql` registreerib mooduli kataloogi (tabelid `system_org_units`/`system_substitutes` olid juba olemas).
- `ModuleService::isActiveForClient()` lisatud üldise abimeetodina; `PatientService` refaktoreeritud seda kasutama.
- `/panel/org*` ja `/panel/substitutes*` marsruudid nõuavad nüüd mooduli aktiveerimist kliendile (`require_organisation_module()` / `require_organisation_manager()`), 403 kui pole aktiveeritud.
- C-paneli menüü näitab "Organisation" ja "Substitutes" linke ainult siis, kui admin on mooduli kliendile aktiveerinud (`/admin/clients/view`).
- `SchemaInstaller` täiendatud: toetab nüüd ka "rowCheck" tüüpi migratsioone (andmete registreerimine olemasolevasse tabelisse), mitte ainult uute tabelite loomist.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.18 on `1.19`.

## Versioon 1.17 – 20. september 2026

- Eemaldatud "Organisation" ja "Substitutes" lingid C-paneli menüüüst. Marsruudid (`/panel/org`, `/panel/substitutes`) ja teenused (`OrgUnitService`, `SubstituteService`) jäävad muutumatuks – need viiakse hiljem ümber eraldi aktiveeritavaks mooduliks (samal põhimõttel nagu "Patients").
- Järgmiseks versiooniks pärast 1.17 on `1.18`.

## Versioon 1.16 – 20. september 2026

- Lisatud `UserService::resetPassword()`: peaadmin saab lähtestada kliendi kasutaja parooli kliendi detailvaates (`/admin/clients/view` → Users, nupp "Reset password"), samuti kliendi enda `client_admin` saab lähtestada teiste kliendi kasutajate paroole `/panel/users` lehel. Uus ajutine parool kuvatakse ühekordselt ja `must_change_password` sunnib vahetama.
- Lisatud `UserService::changeOwnPassword()` ja uus leht `/panel/account` – iga sisseloginud kasutaja saab ise oma parooli muuta (nõuab praeguse parooli kinnitust, min 8 märki). Link lisatud C-paneli menüüsse ("My account") ja "Users & permissions" lehele ("Change my password").
- Kõik parooli muudatused lähevad auditlogisse (`user.password_reset`, `user.password_changed_self`).
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.16 on `1.17`.

## Versioon 1.15 – 20. september 2026

- Lisatud `/admin/roles` leht: rollide kuvatavate nimetuste muutmine (nt "Client Administrator" → muu nimi), ilma tehnilist `role_key`-d muutmata. Link lisatud menüüüsse ("Users & permissions → Roles") ja "System settings" lehele.
- `RoleService::listAll()` ja `RoleService::updateName()` lisatud, muudatused lähevad auditlogisse (`role.renamed`).
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.15 on `1.16`.

## Versioon 1.14 – 20. september 2026

- Viga parandatud: `ADMIN_SYSTEM_ROLES` konstant oli failis `public/index.php` deklareeritud pärast marsruutimisloogikat, mistõttu `/admin` (ja kõik sellest sõltuvad lehed) andsid 500 viga ("Undefined constant"). Konstant on nüüd deklareeritud faili alguses, enne päringu töötlemist.
- Testitud reaalses keskkonnas (`APP_DEBUG=1` ajutiselt): `/admin` dashboard, kliendinimekiri, moodulid, süsteemi logid – kõik töötavad korrektselt. `APP_DEBUG` on tagasi `0` peale testimist.
- Järgmiseks versiooniks pärast 1.14 on `1.15`.

## Versioon 1.13 – 20. september 2026

- Lisatud esimene päris äri-moodul: **Patients** (patsiendid) – `sql/004_module_patients.sql` loob `patients` tabeli ja registreerib mooduli kataloogi (`system_modules`).
- Loodud `PatientService`: kliendipõhine patsientide loomine, nimekiri, aktiveerimine/deaktiveerimine (soft-delete, doc 01 §9).
- `/panel/patients`, `/panel/patients/create`, `/panel/patients/toggle` – nähtavad ja kasutatavad ainult siis, kui admin on mooduli kliendile aktiveerinud (`/admin/clients/view` kaudu).
- C-paneli vasak menüü kuvab nüüd dünaamiliselt ainult kliendile aktiveeritud mooduleid (doc 01 §4 "vasak menüü kuvab ainult kliendile aktiveeritud ... mooduleid") – varem oli menüü staatiline.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Avatud küsimus: praegu piisab mooduli nägemiseks lihtsalt sisselogimisest + mooduli aktiveerimisest kliendile; granulaarne õiguste (vaatamine/lisamine/muutmine) kontroll mooduli sees tuleb lisada järgmisena, kui moodul kasvab.
- Järgmiseks versiooniks pärast 1.13 on `1.14`.

## Versioon 1.12 – 20. september 2026

- Lisatud `sql/003_notifications_support.sql`: `system_notifications` ja `system_support_tickets` tabelid. `SchemaInstaller` uuendatud nii, et iga migratsioonifail kontrollitakse eraldi (oma marker-tabeli järgi), mitte ainult üks kord üldiselt – seega rakendub see ja tulevased migratsioonid automaatselt ka juba paigaldatud kesk pkondades.
- Loodud `NotificationService`: admin saab saata teavituse kas ühele kasutajale või kogu kliendile (`/admin/notifications`); kasutajad näevad ja saavad märkida enda teavitusi loetuks (`/panel/notifications`).
- Loodud `ReportService`: süsteemi- ja kliendipõhised baasstatistikad (`/panel/reports`) – aktiivsed kasutajad/moodulid/org-üksused/avatud toepiletid.
- Loodud `SupportService`: kliendi kasutajad saavad esitada toepileteid (`/panel/support`, `/panel/support/create`), admin näeb ja haldab kõiki avatud pileteid (`/admin/support`).
- Menuüd uuendatud (Teavitused, Tugi, Raportid ja statistika pole enam platseholderid).
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Märkus: "Notifications" link C-paneli menüüüs ja "Support" admin menüüüs on lisatud väljaspool doc 01 §4-5 täpset loendit, kuna need on funktsionaalselt vajalikud, et teavituste/toe vood täielikult töötaksid.
- Järgmiseks versiooniks pärast 1.12 on `1.13`.

## Versioon 1.11 – 20. september 2026

- Loodud `SettingsService` ja admin "Süsteemi seaded" leht (`/admin/settings`) `system_settings` tabeli haldamiseks (võti/väärtus).
- Loodud C-paneli "Kliendi haldus" leht (`/panel/client`) – kliendi enda andmete (aadress, telefon, e-post, esindaja) vaatamine kõigile kasutajatele, muutmine ainult `client_admin` rolliga (`ClientService::updateOwnDetails`).
- Menuü uuendatud: "Süsteemi seaded" ja "Kliendi haldus" pole enam platseholderid.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.11 on `1.12`.

## Versioon 1.10 – 20. september 2026

- Kinnitatud reaalses keskkonnas: esileht, algseadistus ja automaatne SQL-skeemi paigaldus töötavad root `index.php` + `.htaccess` lahendusega.
- Lisatud `AuditLogService::listRecent()` filtritega (toiming, kliendikood, kasutajanimi).
- Loodud admin "Süsteemi logid" leht (`/admin/logs`) doc 01 §5 ja doc 02 §10 järgi – varem oli menuüis ainult platseholder.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.10 on `1.11`.

## Versioon 1.9 – 20. september 2026

- Lisatud juurkataloogi `index.php`, mis suunab päringu `public/index.php` front controllerisse – vajalik, kuna hostimiskeskkond eeldab reaalset `index.php` faili veebijuure kataloogis (mitte ainult `.htaccess` ümbersuunamist).
- Uuendatud `.htaccess`: `DirectoryIndex index.php`, kõik marsruudid (v.a `public/assets` staatilised failid ja olemasolevad failid/kataloogid) suunatakse nüüd juure `index.php`-le.
- Lisatud `SchemaInstaller`: kontrollib esimese päringu ajal, kas `system_initial_setup` tabel on olemas; kui ei ole, käivitab automaatselt `sql/001_core_schema.sql` ja `sql/002_client_modules.sql` – käsitsi phpMyAdminis käivitamist enam vaja ei ole.
- Lisatud üldine veakäsitlus front controlleris (try/catch) koos `views/errors/500.php` lehega, et vigade korral ei kuvataks tühja 500-lehte; `APP_DEBUG=1` `.env`-is näitab tehnilisi detaile (vaikimisi väljas).
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.9 on `1.10`.

## Versioon 1.8 – 20. september 2026

- Loodud `OrgUnitService`: org-üksuste (A–F hierarhia, osakonnad/grupid/rühmad) loomine ja nimekiri kliendi kontekstis (`/panel/org`, `/panel/org/create`), sh vanemüksus ja juht (doc 02 §8, doc 03 §2).
- Kasutaja loomisel (`/panel/users/create`) saab nüüd valida ka org-üksuse; see salvestatakse `system_user_roles.org_unit_id` väljale.
- Loodud `SubstituteService`: ajutise asendaja taotlemine (`/panel/substitutes/create`) koos sobivate asendajate automaatse arvutamisega (sama üksus/tase, alluv või otsene ülemus, doc 03 §5), kinnitamine/tagasilükkamine `client_admin` rolliga (`/panel/substitutes/approve`, `/reject`, doc 03 §6).
- Delegeeritud õigused salvestatakse JSON-ina; kõik taotlused/otsused lähevad auditlogisse.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Avatud küsimus: asendaja automaatne aktiveerimine/lõpetamine periood javahemiku järgi (staatuse `active`/`ended` üleminek) vajab hiljem cron-tüüpi taustaprotsessi – praegu on olekud ainult `pending`/`approved`/`rejected`.
- Järgmiseks versiooniks pärast 1.8 on `1.9`.

## Versioon 1.7 – 20. september 2026

- Laiendatud `UserService`: kliendi kasutajate loomine (`/panel/users/create`) koos rolli valikuga (client_admin, A–F, viewer, temp_substitute) ja automaatse ühekordse ajutise parooliga; kasutaja aktiveerimine/deaktiveerimine (`/panel/users/toggle`).
- Lisatud serveripoolne reegel: klienti ei saa jätta ilma aktiivse peaadministraatorita – viimase aktiivse `client_admin` deaktiveerimine on blokeeritud (doc 03 §4).
- Kasutajahaldus (lisamine/deaktiveerimine) piiratud `client_admin` rolliga; nimekirja vaatamine on lubatud kõigile kliendi kasutajatele.
- Lisatud `RoleService::listAssignableClientRoles()`.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Avatud küsimus: ajutine asendaja voog (doc 03 §5) ja organisatsiooniyksuste (A–F hierarhia) UI on veel loomata – praegu saab rolli määrata, aga org-üksuse (osakond/grupp/rühm) sidumist kasutajaliideses veel pole.
- Järgmiseks versiooniks pärast 1.7 on `1.8`.

## Versioon 1.6 – 20. september 2026

- Lisatud `sql/002_client_modules.sql`: `system_client_modules` tabel kliendipõhise mooduli aktiveerimise jaoks.
- Admin: kliendi detailvaade `/admin/clients/view?id=` – kliendi andmed, kasutajate nimekiri, moodulite aktiveerimine/deaktiveerimine kliendi kohta (`ModuleService::toggleClientActivation`).
- Loodud C-paneli baasstruktuur kliendi kasutajatele (doc 01 §4, §7): `/panel` dashboard, `/panel/modules` aktiivsed moodulid, `/panel/users` kasutajad ja rollid (loetavas vaates), ühine päis/jalus + vasak menüü.
- Lisatud `UserService` kliendi kasutajate ja rollide loetavaks päringuks.
- Avaleht suunab süsteemirolliga kasutajad `/admin`-i ja tavakasutajad `/panel`-isse.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Avatud küsimus: kasutajate/rollide **muutmine** (rollide määramine, deaktiveerimine, asendajad, organisatsiooniyksused) on järgmise etapi teema – praegu on `/panel/users` ainult loetav.
- Järgmiseks versiooniks pärast 1.6 on `1.7`.

## Versioon 1.5 – 20. september 2026

- Loodud moodulite kataloog (`ModuleService`): `/admin/modules` nimekiri, `/admin/modules/create` uue mooduli loomine (võti, nimi, kirjeldus, demo saadavus), aktiveerimine/deaktiveerimine ilma andmeid kustutamata (doc 01 §5, doc 02 §7).
- Loodud keelemoodul (`LanguageService`, `TranslationService`): `/admin/languages` keelte haldus (inglise keelt ei saa deaktiveerida), `/admin/translations` tõlkevõtmete/väärtuste haldus keele kaupa koos ingliskeelse baasväärtusega, `/admin/translations/missing` puuduvate tõlgete raport (doc 01 §5, doc 02 §6).
- Kõik loomised/muudatused lähevad auditlogisse.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.5 on `1.6`.

## Versioon 1.4 – 20. september 2026

- Loodud peaadministraatori keskkonna skelett: rollipõhine ligipääsukontroll (`RoleService`) marsruutidele `/admin/*`, ühine päis/jalus + vasak menüü + tööruum (doc 01 §3-5).
- Loodud kliendihalduse esimene vertikaalne lõik (`ClientService`, doc 01 §6): kliendinimekiri (`/admin/clients`) ja uue kliendi loomine (`/admin/clients/create`), mis loob kliendi, esimese kasutaja (kliendi peaadministraatori rolliga) ja näitab ühekordset ajutist parooli.
- Kliendikoodi `13666` ei saa määrata tavakliendile (valideeritud serveris); kliendikoodi unikaalsus kontrollitakse enne loomist.
- Kõik uued toimingud (kliendi ja esimese kasutaja loomine) lähevad auditlogisse.
- Testitud: kõik PHP failid `php -l` abil vigadeta.
- Järgmiseks versiooniks pärast 1.4 on `1.5`.

## Versioon 1.3 – 20. september 2026

- Loodud esmane andmebaasiskeem `sql/001_core_schema.sql` (18 `system_*` tabelit: kliendid, kasutajad, rollid, õigused, org-üksused, asendajad, moodulid, keeled, tõlked, auditlogi, versioonilogi, algseadistus), koos alglaengu andmetega (inglise keel, reserveeritud klient `13666`, baasrollid ja -õigused). Fail on korduskäivitatav ja mõeldud otse phpMyAdminis käivitamiseks.
- Loodud vanilla PHP + PDO rakenduse skelett: `public/index.php` front controller, `config/env.php` ja `config/database.php`, teenused `src/Services/InitialSetupService.php`, `src/Services/AuthService.php`, `src/Services/AuditLogService.php`, tugiklassid `src/Support/ClientContext.php` ja `src/Support/Csrf.php`.
- Loodud ühekordne algseadistuse voog (`/setup`) esimese peaadministraatori loomiseks ja C-paneli sisselogimisvoog (`/login`, `/logout`) kliendikoodi + kasutajatunnuse + parooliga, koos avaliku kodulehe skeletiga (`/`), mis kasutab Bootstrap 5 ja ühist päis/jalus kujundust (100% laius, 75 px kõrgus, doc 01 §3).
- Lisatud `.htaccess` reeglid, mis blokeerivad otsepääsu `config/`, `src/`, `sql/`, `views/`, `docs/` ja `.env` failidele ning suunavad kõik päringud front controllerisse.
- Turvameetmed: paroolid `password_hash()`/`password_verify()`, PDO prepared statement'id, sessioonipõhine CSRF-kaitse vormidel, lihtne rate limiting sisselogimisel, auditlogi kõigi oluliste toimingute kohta.
- Testitud: kõik PHP failid `php -l` abil vigadeta; DB-ühendust ei õnnestunud kohapeal valideerida (kaug-MySQL ligipääs polnud lubatud) – skeem tuleb kasutajal ise phpMyAdminis käivitada.
- Avatud küsimus: doc 02 §5 kirjeldatud isik/konto/liikmesuse (mitme kliendi) mudel on esialgu lihtsustatud üks-kasutaja-üks-klient mudeliks; laiendamine järgmises etapis, kui vajalik.
- Järgmiseks versiooniks pärast 1.3 on `1.4`.

## Versioon 1.2 – 20. september 2026

- Lõplikult kinnitatud, et Laravelit ei kasutata; backend on vanilla PHP + PDO/MySQLi, ilma raamistikuta.
- Uuendatud SQL-juhend (`docs/02-sql-andmebaasi-loogika.md`) Laraveli viideteta.
- Fail `docs/04-laravel-arhitektuur-ja-mudelid.md` eemaldatud ja asendatud dokumendiga `docs/04-php-arhitektuur-ja-mudelid.md`, mis kirjeldab vanilla PHP + PDO arhitektuuri (front controller, teenused, PDO prepared statement'id, sessioonipõhine autentimine).
- Uuendatud AI-prompt (`docs/05-ai-eeskirjad-ja-prompt.md`), et see eeldaks vanilla PHP + PDO/MySQLi + Bootstrap stacki, mitte Laravelit.
- Säilitatud MySQL/SQL, kliendikontekst, moodulid, rollid, keelehaldus, algkasutajad, demo ja auditlogid.
- Järgmiseks versiooniks pärast 1.2 on `1.3`.

## Versioon 1.1 – 20. september 2026

- Lisatud projekti nõuete ja disaini, SQL-andmebaasi, kasutajate ja rollide, arhitektuuri ning AI arendusreeglite esmased dokumendid (`docs/01`–`docs/05`). Arhitektuuridokument kirjeldas sel ajal Laravelit; versioonis 1.2 mindi üle vanilla PHP ja PDO peale.
- Mõjutatud moodulid: dokumentatsioon ja arendusjuhised. Andmebaasi skeemi ega rakenduskoodi selles versioonis ei lisatud.
- Testitulemusi algses versiooni 1.1 commit'is ei dokumenteeritud.
- Järgmiseks versiooniks pärast 1.1 on `1.2`.

## Versioonireegel

Iga täiendav funktsionaalne, tehniline või dokumenteeritud uuendus suurendab viimast versiooninumbrit ühe võrra. Iga versioonikirje peab sisaldama kuupäeva, muudatuse kirjeldust, mõjutatud faile või mooduleid, andmebaasimuudatusi ja testimistulemust.
