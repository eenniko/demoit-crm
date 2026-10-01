# DemoIT CRM – muudatuste logi

## Versioon 1.28 – 1. oktoober 2026

- Lisatud eraldi aktiveeritav `property` moodul koos kliendipõhise `property_nodes` tabeli ja mooduli kataloogikirjega (`sql/015_property_structure.sql`). See ei kasuta teiste moodulite andmetabeleid.
- Kliendipaneeli struktuurivaade ning loomise ja nime muutmise vormid võimaldavad lisada hoone, selle alla korpuse või korruse, korpuse alla korruse ning korruse alla ruumi ja nende nimesid muuta (`views/panel/property/`, `public/index.php`, `views/panel/shell.php`). Lugemine nõuab aktiivset moodulit; loomine ja muutmine kliendiadministraatori õigust ja CSRF-kontrolli.
- `PropertyService` kontrollib vanema kuulumist samale kliendile, lubatud tasemete järjekorda ja nime ning salvestab loomise ja nime muutmise auditlogisse. Andmebaasi sama kliendi välisvõti takistab klientidevahelisi vanemseoseid.
- Lisatud kakskeelne kasutusjuhend `docs/08-kinnistu-struktuuri-moodul.md` ja uuendatud `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `CHANGELOG.md`, `src/Services/SchemaInstaller.php` ning `src/Services/ModuleProvisioningService.php`.
- Andmebaasimuudatus: uus `property_nodes` tabel; migratsioon registreerib versiooni 1.28 tabelis `system_version_logs`. Olemasolevate moodulite andmeskeeme ei muudeta.
- Kontrollitud: PHP süntaks, lubatud vanem-laps tüübid ja UTF-8 nimepiirang; live-veebis struktuurivaade, vanemakohased loomise vormid ja nime muutmise vorm. Olematu kirje annab 404; olemasoleva nime uuesti salvestamine õnnestus nime muutmata. Uusi kirjeid ega eraldi klientidevahelist andmebaasitesti ei tehtud.
- Järgmiseks versiooniks pärast 1.28 on `1.29`.

## Versioon 1.27 – 30. september 2026

- Lisatud kõigi versioonide 1.1–1.26 täielik ingliskeelne tõlge faili `CHANGELOG.md`, säilitades eestikeelsed kirjed ja teadaolevad piirangud. Muudatuste logi on nüüd kakskeelne.
- Mõjutatud failid: `CHANGELOG.md`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/014_bilingual_changelog.sql`.
- Andmebaasimuudatus: skeem ei muutu; idempotentne migratsioon registreerib versiooni 1.27 tabelis `system_version_logs`.
- Kontrollitud: mõlemas keeles versioonide arv ja järjekord kattuvad, README versioonitabelid ja PHP süntaks kontrollitud. Rakenduse käitumine ei muutu.
- Järgmiseks versiooniks pärast 1.27 on `1.28`.

## Versioon 1.26 – 30. september 2026

- Töötaja loomise vormist (`views/panel/users/create.php`) eemaldatud organisatsiooniüksuse valik. `UserService::createForClient()` käsitleb puuduva `org_unit_id` väärtusena `null`, seega loomise loogika jääb muutmata.
- Loomise marsruut (`public/index.php`) ei päri enam vormi jaoks organisatsiooniüksuste loendit. Töötaja muutmisvorm jääb samaks.
- Mõjutatud failid: `views/panel/users/create.php`, `public/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/013_employee_create_org_unit.sql`.
- Andmebaasimuudatus: skeem ei muutu; idempotentne migratsioon registreerib versiooni 1.26 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja töötaja loomise vorm live-veebis; organisatsiooniüksuse valikut ei kuvata. Uut töötajat testi käigus ei loodud.
- Järgmiseks versiooniks pärast 1.26 on `1.27`.

## Versioon 1.25 – 30. september 2026

- Töötaja muutmisvormist (`views/panel/users/edit.php`) eemaldatud organisatsiooniüksuse valik. Töötaja loomise vorm jääb muutmata.
- Muudetud `UserService::updateForClient()` nii, et puuduva `org_unit_id` korral säilib olemasolev seos; muutmisvaade (`public/index.php`) ei päri enam vormi jaoks üksuste loendit.
- Mõjutatud failid: `views/panel/users/edit.php`, `public/index.php`, `src/Services/UserService.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/012_employee_edit_org_unit.sql`.
- Andmebaasimuudatus: skeem ei muutu; idempotentne migratsioon registreerib versiooni 1.25 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja töötaja muutmisvorm live-veebis; organisatsiooniüksuse valikut ei kuvata. Salvestamise mõju olemasolevale seosele vajab andmebaasiga kontrolli.
- Järgmiseks versiooniks pärast 1.25 on `1.26`.

## Versioon 1.24 – 30. september 2026

- Sisselogimisel salvestatakse `system_users.full_name` sessiooni; päis, avaleht ning süsteemi- ja kliendipaneeli töölauad kuvavad nüüd inimese nime. Puuduva nime korral jääb varuvariandiks kasutajatunnus.
- Mõjutatud failid: `src/Services/AuthService.php`, `src/Support/ClientContext.php`, `views/layout/header.php`, `views/home.php`, `views/admin/dashboard.php`, `views/panel/dashboard.php`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/011_signed_in_display_name.sql`.
- Andmebaasimuudatus: skeem ei muutu; uus idempotentne migratsioon registreerib versiooni 1.24 tabelis `system_version_logs`.
- Kontrollitud: muudetud PHP failide süntaks; päris kontoga uuesti sisselogimine ning nime kuvamine avalehel ja kliendipaneeli töölaual. Tühja nime korral kasutatakse kasutajatunnust.
- Järgmiseks versiooniks pärast 1.24 on `1.25`.

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

# DemoIT CRM - Changelog (English)

## Version 1.28 - October 1, 2026

- Added a separate optional `property` module with a client-scoped `property_nodes` table and module catalog entry (`sql/015_property_structure.sql`). It does not use other modules' data tables.
- The client panel tree, creation and rename forms support buildings, optional wings or direct floors, floors under wings, rooms under floors, and editing their names (`views/panel/property/`, `public/index.php`, `views/panel/shell.php`). Reading requires an active module; creation and renaming require the client administrator role and CSRF protection.
- `PropertyService` validates same-client parents, allowed level transitions and names, and audits creation and renaming. A same-client database foreign key prevents cross-client parent references.
- Added a bilingual guide at `docs/08-kinnistu-struktuuri-moodul.md` and updated `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `CHANGELOG.md`, `src/Services/SchemaInstaller.php`, and `src/Services/ModuleProvisioningService.php`.
- Database: new `property_nodes` table; the migration records version 1.28 in `system_version_logs`. Schemas owned by existing modules are unchanged.
- Verified: PHP syntax, allowed parent/child types and UTF-8 name length; the live tree, context-aware creation forms and rename form. A nonexistent entry returns 404; resubmitting an existing name succeeded without changing the name. No records were created and no separate cross-client database test was run.
- The next version after 1.28 is `1.29`.

## Version 1.27 - September 30, 2026

- Added a full English translation of versions 1.1-1.26 to `CHANGELOG.md`, retaining the Estonian entries and documented limitations. The changelog is now bilingual.
- Affected files: `CHANGELOG.md`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php`, and `sql/014_bilingual_changelog.sql`.
- Database: no schema changes; an idempotent migration records version 1.27 in `system_version_logs`.
- Verified: version counts and order match in both languages; README version tables and PHP syntax checked. Application behavior is unchanged.
- The next version after 1.27 is `1.28`.

## Version 1.26 - September 30, 2026

- Removed the organisation unit selector from the employee creation form (`views/panel/users/create.php`). `UserService::createForClient()` already treats a missing `org_unit_id` as `null`, so creation logic is unchanged.
- The creation route (`public/index.php`) no longer queries organisation units for this form. The employee edit form is unchanged.
- Affected files: `views/panel/users/create.php`, `public/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php`, and `sql/013_employee_create_org_unit.sql`.
- Database: no schema changes; an idempotent migration records version 1.26 in `system_version_logs`.
- Verified: PHP syntax and the live employee creation form; the organisation unit selector is absent. No employee was created during testing.
- The next version after 1.26 is `1.27`.

## Version 1.25 - September 30, 2026

- Removed the organisation unit selector from the employee edit form (`views/panel/users/edit.php`). The creation form remains unchanged.
- Updated `UserService::updateForClient()` to retain the existing assignment when `org_unit_id` is omitted; the edit route (`public/index.php`) no longer queries organisation units for this form.
- Affected files: `views/panel/users/edit.php`, `public/index.php`, `src/Services/UserService.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php`, and `sql/012_employee_edit_org_unit.sql`.
- Database: no schema changes; an idempotent migration records version 1.25 in `system_version_logs`.
- Verified: PHP syntax and the live employee edit form; the organisation unit selector is absent. Retention of an existing assignment on save still needs database-backed verification.
- The next version after 1.25 is `1.26`.

## Version 1.24 - September 30, 2026

- Login now stores `system_users.full_name` in the session. The header, home page, and system and client dashboards display the person's name, falling back to the username if the name is missing.
- Affected files: `src/Services/AuthService.php`, `src/Support/ClientContext.php`, `views/layout/header.php`, `views/home.php`, `views/admin/dashboard.php`, `views/panel/dashboard.php`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php`, and `sql/011_signed_in_display_name.sql`.
- Database: no schema changes; a new idempotent migration records version 1.24 in `system_version_logs`.
- Verified: PHP syntax, logging back in with a real account, and the name displayed on the home page and client dashboard. The username is used if the name is blank.
- The next version after 1.24 is `1.25`.

## Version 1.23 - September 30, 2026

- Added a bilingual summary of every existing version and a link to the full changelog on GitHub's landing page (`README.md`).
- Restored the version 1.1 documentation entry from Git history.
- Affected files: `README.md`, `CHANGELOG.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `sql/010_readme_version_history.sql`, and `src/Services/SchemaInstaller.php`. Application module behavior is unchanged.
- Database: a new idempotent migration records version 1.23 in `system_version_logs`; no schema changes.
- Verified: README version lists match the changelog; PHP syntax and migration registration checks.
- The next version after 1.23 is `1.24`.

## Version 1.22 - September 30, 2026

- Added `docs/06-tootajate-mooduli-kasutusjuhend.md`, a manual for system and client administrators covering module activation, employee management, account activation, passwords, legacy SQL dump import, audit logs, and troubleshooting.
- Added `docs/07-tootajate-mooduli-ai-arendusjuhend.md`, a normative AI/developer guide covering tenant isolation, transactional provisioning, the data model, service responsibilities, security rules, migrations, and the required test matrix.
- Updated the main AI prompt to system version 1.22, linked the new module guides, and clarified shared MySQL database isolation by `client_id` and the project version policy.
- Added `sql/009_employee_module_documentation.sql` to register the documentation release in existing installations.
- The next version after 1.22 is `1.23`.

## Version 1.21 - September 30, 2026

- Registered employee functionality as the optional `employees` module. The C-panel employee menu and all `/panel/users*` routes are available only when the module is activated for the company.
- Added per-company module provisioning: `system_client_module_provisions` tracks the schema version and readiness of each company/module data space.
- Added `employee_module_settings`, created when a company activates the employees module, to define that company's default level F employee role.
- Module activation, the client/module link, and the required company data are created in one database transaction. Provisioning failure rolls back the entire activation.
- Deactivation does not delete company data; reactivation checks and updates provisioning idempotently.
- Uses the approved shared MySQL database with `client_id`-based tenant isolation, not dynamic hosting-level MySQL users or separate databases.
- The next version after 1.21 is `1.22`.

## Version 1.20 - September 30, 2026

- Added upload of a legacy database SQL dump under employee management (`/panel/users/import`), accessible only to the client administrator.
- Import reads only `INSERT` rows for the legacy `db_user_data` table; it never executes the uploaded SQL against the database.
- The legacy `db_users_id` becomes the employee code and username. Names, valid email addresses, phone numbers, and valid creation/update timestamps are preserved.
- Imported accounts are inactive and have level F access. Passwords are not imported, and records with existing matching usernames are skipped.
- Uploads are limited to `.sql` files of at most 5 MB; the action is recorded in the audit log.
- The next version after 1.20 is `1.21`.

## Version 1.19 - September 30, 2026

- Expanded user management into an employees module with employee name, code/username, email, phone, access level, and organisation unit.
- New employees are inactive with level F access by default; an administrator can activate the account after reviewing its details.
- Added an employee edit view and protection against removing the last active client administrator role.
- Account activation and password resets generate a temporary password and email the login details. If sending fails, the temporary password is shown to the administrator.
- Added the automatic migration `sql/006_employee_users.sql` to add `system_users.phone` to existing installations.
- The next version after 1.19 is `1.20`.

## Version 1.18 - September 20, 2026

- Converted Organisation and Substitutes into a real optional `organisation` module, following the Patients model: `sql/005_module_organisation.sql` registers it in the catalog. The `system_org_units` and `system_substitutes` tables already existed.
- Added `ModuleService::isActiveForClient()` as a general helper and refactored `PatientService` to use it.
- `/panel/org*` and `/panel/substitutes*` now require the client's module activation (`require_organisation_module()` / `require_organisation_manager()`), returning 403 otherwise.
- The C-panel menu shows Organisation and Substitutes links only when an administrator has activated the module for the client via `/admin/clients/view`.
- Extended `SchemaInstaller` to support row-check migrations that register rows in existing tables, in addition to migrations creating new tables.
- Verified: all PHP files passed `php -l`.
- The next version after 1.18 is `1.19`.

## Version 1.17 - September 20, 2026

- Removed Organisation and Substitutes links from the C-panel menu. The `/panel/org` and `/panel/substitutes` routes and the `OrgUnitService` and `SubstituteService` remain unchanged; they were planned to become a separate optional module like Patients.
- The next version after 1.17 is `1.18`.

## Version 1.16 - September 20, 2026

- Added `UserService::resetPassword()`: the system administrator can reset a client user's password on `/admin/clients/view` (Users, Reset password); a `client_admin` can reset other users' passwords on `/panel/users`. The one-time temporary password is displayed, and `must_change_password` forces a change.
- Added `UserService::changeOwnPassword()` and `/panel/account` so any signed-in user can change their own password, confirming the current password and supplying at least eight characters. Added My account and Change my password links to the C-panel.
- All password changes are audited (`user.password_reset`, `user.password_changed_self`).
- Verified: all PHP files passed `php -l`.
- The next version after 1.16 is `1.17`.

## Version 1.15 - September 20, 2026

- Added `/admin/roles` to rename role display labels without changing technical `role_key` values, with links from Users & permissions and System settings.
- Added `RoleService::listAll()` and `RoleService::updateName()`; renames are audited as `role.renamed`.
- Verified: all PHP files passed `php -l`.
- The next version after 1.15 is `1.16`.

## Version 1.14 - September 20, 2026

- Fixed a 500 error on `/admin` and dependent pages: `ADMIN_SYSTEM_ROLES` was declared in `public/index.php` after the routing logic. It is now declared near the start of the file, before requests are handled.
- Verified on the live site with `APP_DEBUG=1` temporarily enabled: the `/admin` dashboard, client list, modules, and system logs worked. `APP_DEBUG` was returned to `0` afterward.
- The next version after 1.14 is `1.15`.

## Version 1.13 - September 20, 2026

- Added the first real business module, Patients: `sql/004_module_patients.sql` creates the `patients` table and registers the module in `system_modules`.
- Created `PatientService` for client-scoped patient creation, listing, activation, and deactivation (soft delete; doc 01 section 9).
- `/panel/patients`, `/panel/patients/create`, and `/panel/patients/toggle` are available only after an administrator activates the module for the client via `/admin/clients/view`.
- The C-panel menu dynamically shows only modules activated for the client (doc 01 section 4); previously the menu was static.
- Verified: all PHP files passed `php -l`.
- Open question: module activation plus login is currently sufficient to access Patients. Fine-grained view/create/edit permissions within the module are still needed as it grows.
- The next version after 1.13 is `1.14`.

## Version 1.12 - September 20, 2026

- Added `sql/003_notifications_support.sql` with `system_notifications` and `system_support_tickets`. Updated `SchemaInstaller` to check each migration separately against its own marker table, so this and future migrations also apply automatically to existing installations.
- Created `NotificationService`: administrators can notify an individual user or an entire client (`/admin/notifications`); users can view and mark their own notifications as read (`/panel/notifications`).
- Created `ReportService` for basic system- and client-scoped statistics on active users, modules, organisation units, and open support tickets (`/panel/reports`).
- Created `SupportService`: client users can submit tickets (`/panel/support`, `/panel/support/create`); administrators can view and manage all open tickets (`/admin/support`).
- Updated the menu: Notifications, Support, and Reports & statistics are no longer placeholders.
- Verified: all PHP files passed `php -l`.
- Note: the C-panel Notifications and admin Support links were added beyond the exact menus in doc 01 sections 4-5 to make those workflows usable end to end.
- The next version after 1.12 is `1.13`.

## Version 1.11 - September 20, 2026

- Created `SettingsService` and the System settings admin page (`/admin/settings`) for managing the `system_settings` key/value table.
- Created the Client management page (`/panel/client`): all client users can view their client's address, phone, email, and representative; only `client_admin` can edit them via `ClientService::updateOwnDetails()`.
- Updated the menu: System settings and Client management are no longer placeholders.
- Verified: all PHP files passed `php -l`.
- The next version after 1.11 is `1.12`.

## Version 1.10 - September 20, 2026

- Confirmed on the live site that the landing page, initial setup, and automatic SQL schema installation work with the root `index.php` and `.htaccess` configuration.
- Added `AuditLogService::listRecent()` with action, client code, and username filters.
- Created the admin System logs page (`/admin/logs`) per docs 01 section 5 and 02 section 10; it was previously just a menu placeholder.
- Verified: all PHP files passed `php -l`.
- The next version after 1.10 is `1.11`.

## Version 1.9 - September 20, 2026

- Added root-level `index.php` to forward requests to `public/index.php`; the hosting environment requires a real `index.php` in the web root rather than only a `.htaccess` rewrite.
- Updated `.htaccess` with `DirectoryIndex index.php`; all routes except static `public/assets` files and existing files/directories now forward to the root entry point.
- Added `SchemaInstaller`: on the first request it checks for `system_initial_setup` and, if absent, automatically runs `sql/001_core_schema.sql` and `sql/002_client_modules.sql`. Manual phpMyAdmin setup is no longer required.
- Added front-controller exception handling and `views/errors/500.php` to avoid a blank 500 page. Setting `APP_DEBUG=1` in `.env` shows technical details; it is off by default.
- Verified: all PHP files passed `php -l`.
- The next version after 1.9 is `1.10`.

## Version 1.8 - September 20, 2026

- Created `OrgUnitService` for listing and creating client-scoped A-F hierarchy units (departments, groups, teams) at `/panel/org` and `/panel/org/create`, including parent units and managers (docs 02 section 8 and 03 section 2).
- Employee creation at `/panel/users/create` gained an organisation unit selector, stored in `system_user_roles.org_unit_id`.
- Created `SubstituteService`: requests for temporary substitutes (`/panel/substitutes/create`), automatic eligibility calculations (same unit/level, subordinate, or direct superior; doc 03 section 5), and client-admin approval or rejection (`/panel/substitutes/approve`, `/reject`; doc 03 section 6).
- Delegated permissions are stored as JSON; every request and decision is audited.
- Verified: all PHP files passed `php -l`.
- Open question: automatic activation and ending of a substitute assignment based on its date range needs a cron-like background job. Current states are limited to `pending`, `approved`, and `rejected`.
- The next version after 1.8 is `1.9`.

## Version 1.7 - September 20, 2026

- Expanded `UserService` for client user creation (`/panel/users/create`) with role selection (`client_admin`, A-F, `viewer`, `temp_substitute`) and an automatically generated one-time temporary password; added activation/deactivation (`/panel/users/toggle`).
- Added a server-side rule preventing a client from losing its last active administrator: deactivating the final active `client_admin` is blocked (doc 03 section 4).
- Adding/deactivating users requires the `client_admin` role; all client users can view the list.
- Added `RoleService::listAssignableClientRoles()`.
- Verified: all PHP files passed `php -l`.
- Open question: the temporary substitute workflow (doc 03 section 5) and A-F organisation unit UI were still missing. Roles could be assigned, but unit selection was not yet available in the UI.
- The next version after 1.7 is `1.8`.

## Version 1.6 - September 20, 2026

- Added `sql/002_client_modules.sql`, creating `system_client_modules` for per-client module activation.
- Added the admin client detail page (`/admin/clients/view?id=`) with client information, user list, and module activation/deactivation via `ModuleService::toggleClientActivation()`.
- Built the C-panel foundation (doc 01 sections 4 and 7): dashboard at `/panel`, active modules at `/panel/modules`, a read-only users and roles view at `/panel/users`, and shared header/footer and side menu.
- Added `UserService` queries for client users and roles.
- The home page now directs users with system roles to `/admin` and regular users to `/panel`.
- Verified: all PHP files passed `php -l`.
- Open question: editing users and roles, role assignment, deactivation, substitutes, and organisation units were planned for the next stage; `/panel/users` was read-only.
- The next version after 1.6 is `1.7`.

## Version 1.5 - September 20, 2026

- Created the module catalog (`ModuleService`): listing at `/admin/modules`, creation at `/admin/modules/create` (key, name, description, demo availability), and activation/deactivation without deleting data (docs 01 section 5 and 02 section 7).
- Created language management (`LanguageService`, `TranslationService`): manage languages at `/admin/languages` (English cannot be disabled), translations and their English base values at `/admin/translations`, and a missing-translations report at `/admin/translations/missing` (docs 01 section 5 and 02 section 6).
- All creations and changes are recorded in the audit log.
- Verified: all PHP files passed `php -l`.
- The next version after 1.5 is `1.6`.

## Version 1.4 - September 20, 2026

- Built the system administrator foundation with role-based access control (`RoleService`) on `/admin/*`, a shared header/footer, left menu, and workspace (doc 01 sections 3-5).
- Built the first client management workflow (`ClientService`, doc 01 section 6): list clients at `/admin/clients` and create a client at `/admin/clients/create`, including its first user with the client administrator role and a one-time temporary password.
- Client code `13666` is reserved and cannot be assigned to a regular client; client code uniqueness is validated before creation.
- New actions (creating clients and their first users) are written to the audit log.
- Verified: all PHP files passed `php -l`.
- The next version after 1.4 is `1.5`.

## Version 1.3 - September 20, 2026

- Added the initial database schema in `sql/001_core_schema.sql`: 18 `system_*` tables for clients, users, roles, permissions, organisation units, substitutes, modules, languages, translations, audit logs, version logs, and initial setup, with seed data for English, reserved client `13666`, and base roles/permissions. The file can be rerun and was designed for direct phpMyAdmin execution.
- Built the plain PHP and PDO application skeleton: `public/index.php` front controller; `config/env.php` and `config/database.php`; `InitialSetupService`, `AuthService`, and `AuditLogService`; and the `ClientContext` and `Csrf` helpers.
- Built one-time initial setup (`/setup`) to create the first system administrator, and C-panel login (`/login`, `/logout`) using client code, username, and password. Added a Bootstrap 5 home page and shared full-width, 75 px header/footer (doc 01 section 3).
- Added `.htaccess` rules blocking direct access to `config/`, `src/`, `sql/`, `views/`, `docs/`, and `.env`, while routing requests through the front controller.
- Security measures: `password_hash()` / `password_verify()`, PDO prepared statements, session-based form CSRF protection, basic login rate limiting, and audit logging for important actions.
- Verified: all PHP files passed `php -l`. A local database connection could not be checked because remote MySQL access was blocked; at that stage the schema had to be run manually in phpMyAdmin.
- Open question: the person/account/membership model spanning multiple clients (doc 02 section 5) was initially simplified to one user per client, to be extended if needed.
- The next version after 1.3 is `1.4`.

## Version 1.2 - September 20, 2026

- Confirmed Laravel is not used: the backend is plain PHP with PDO/MySQLi and no framework.
- Removed Laravel references from `docs/02-sql-andmebaasi-loogika.md`.
- Removed `docs/04-laravel-arhitektuur-ja-mudelid.md` and replaced it with `docs/04-php-arhitektuur-ja-mudelid.md`, describing the plain PHP and PDO architecture (front controller, services, PDO prepared statements, session authentication).
- Updated `docs/05-ai-eeskirjad-ja-prompt.md` to assume plain PHP with PDO/MySQLi and Bootstrap rather than Laravel.
- Retained MySQL/SQL, client context, modules, roles, language management, initial users, demo functionality, and audit logs.
- The next version after 1.2 is `1.3`.

## Version 1.1 - September 20, 2026

- Added initial documentation covering project requirements and design, SQL database logic, users and roles, architecture, and AI development rules (`docs/01`-`docs/05`). The architecture document described Laravel at the time; version 1.2 switched to plain PHP and PDO.
- Affected modules: documentation and development guidance. No database schema or application code was added in this version.
- Test results were not documented in the original version 1.1 commit.
- The next version after 1.1 is `1.2`.

## Versioning rule

Every additional functional, technical, or documented change increments the latest version number by one. Each release entry must include its date, a description of the change, affected files or modules, database changes, and test results.
