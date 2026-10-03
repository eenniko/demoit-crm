# DemoIT CRM – muudatuste logi

## Versioon 1.58 – 3. oktoober 2026

- Muudatuse järel saadab automaatsalvestus ainult valitud lepingu ja päeva. Vanast brauseritabulist ei saadeta enam kogu kuu aegunud väärtusi, mis võisid teised vahetused üle kirjutada.
- Weekday-Exceptionid on nüüd ka kattuvuskontrollis tööajavahemikud; nädalavahetuse exceptionid jäävad nii Planned- kui kattuvusarvestusest välja.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/045_schedule_single_cell_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.58 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, ühe assignment-väljaga AJAX-payload ning exceptionide kattuvuse piiritestid.
- Järgmiseks versiooniks pärast 1.58 on `1.59`.

## Versioon 1.57 – 3. oktoober 2026

- Planned arvestab Exception-malli tunde esmaspäevast reedeni, nädalavahetuse exception-tunde ei liideta. Reegel rakendub kuu- ja neljakuulisele Tri-OT saldole.
- Uuendatud graafikumooduli kasutusjuhend, et kirjeldada ajutisi lepinguid, kattuvust ja tunniarvestust.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `docs/10-graafiku-moodul.md`, `src/Services/SchemaInstaller.php`, `sql/044_schedule_weekday_exception_hours.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.57 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja viis exception-/nädalapäevade arvestusjuhtu.
- Järgmiseks versiooniks pärast 1.57 on `1.58`.

## Versioon 1.56 – 3. oktoober 2026

- Eemaldatud ajutiste tundide sulgudes kuvatavalt jaotuselt sõna `temp`; näiteks `(+24h)`.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/043_schedule_temp_label.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.56 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja kuvatava teksti muutus.
- Järgmiseks versiooniks pärast 1.56 on `1.57`.

## Versioon 1.55 – 3. oktoober 2026

- Graafikusse kaasatakse töötaja ajutised lepingud nende asukoha/osakonna all. Ajutise rea Planned näitab selle asukoha tunde; selle workload’i põhjal ei arvutata Required, OT ega Tri-OT. Põhirea Planned koondab põhi- ja ajutised tunnid ning näitab ajutise osa sulgudes.
- Lubatud on mitu lepingukirjet töötaja ja kuupäeva kohta ainult siis, kui vahetuste ajavahemikud ei kattu; täpselt kõrvuti lõppev/alustav vahetus on lubatud. Kontroll hõlmab üle südaöö vahetusi.
- Mõjutatud failid: `sql/023_employee_scheduling.sql`, `sql/042_schedule_contract_day_index.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `views/panel/schedule/index.php`, `public/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: päevakirjete unikaalsus liigub töötaja/kuupäeva pealt lepingu/kuupäeva peale; FK-d toetav töötajaindeks lisatakse enne vana unikaalindeksi eemaldamist.
- Kontrollitud: PHP süntaks ning kattuvuse 5 piirjuhtu, sh üle südaöö ja täpselt kõrvuti vahetused.
- Järgmiseks versiooniks pärast 1.55 on `1.56`.

## Versioon 1.54 – 3. oktoober 2026

- Jaotatakse öövahetuse tunnid alguskuu ja järgmise kuu vahel tegelike kuupiiride järgi; aprilli, augusti ja detsembri lõpus jääb kogu üle piiri ulatuv vahetus perioodi viimasesse kuusse.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/040_schedule_cross_month_hours.sql`, `sql/041_schedule_month_boundary_balances.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.54 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, kuus kuu piiri ja perioodilõpu vahetustest ning live-vaate saldomärgendid.
- Järgmiseks versiooniks pärast 1.54 on `1.55`.

## Versioon 1.53 – 3. oktoober 2026

- Jaotatud kuu piiri ületava vahetuse tunnid kuude vahel. Tri-OT perioodi viimasel kuul (aprill, august, detsember) jääb üle piiri ulatuv vahetus tervikuna sellesse kuusse ning uude perioodi üle ei kandu.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/040_schedule_cross_month_hours.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.53 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja live-vaate saldode kuvamine; uusi graafikukirjeid ei loodud.
- Järgmiseks versiooniks pärast 1.53 on `1.54`.

## Versioon 1.52 – 3. oktoober 2026

- Muudetud `Tri-OT` saldo neljakuuliseks kumulatsiooniks: jaanuar–aprill, mai–august ja september–detsember. Iga perioodi alguses saldo nullitakse.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/039_schedule_four_month_balance.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.52 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja kõigi 12 kuu perioodi alguskuu arvutus.
- Järgmiseks versiooniks pärast 1.52 on `1.53`.

## Versioon 1.51 – 3. oktoober 2026

- Täiendatud automaatsalvestuse vastust nii, et pärast vahetuse lisamist või eemaldamist uuenevad kohe Planned, Required, OT ja Tri-OT veerud koos saldode värviga.
- Mõjutatud failid: `public/index.php`, `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/038_schedule_balance_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.51 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja kõigi nelja kokkuvõttevälja AJAX-teekond staatiliselt.
- Järgmiseks versiooniks pärast 1.51 on `1.52`.

## Versioon 1.50 – 3. oktoober 2026

- Teisaldatud töötundide kokkuvõte päevaveergude järele ja jaotatud neljaks: planeeritud tunnid, nõutud tunnid, kuu OT (+ üle-, − alatunnid) ning trimestri algusest kumulatiivne Tri-OT.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/037_schedule_hour_balances.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.50 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, kvartalipõhise saldo live-arvutus ja tabeli laius (`clientWidth` = `scrollWidth` = 1415px).
- Järgmiseks versiooniks pärast 1.50 on `1.51`.

## Versioon 1.49 – 3. oktoober 2026

- Eemaldatud eraldi **Workload** veerg ja töötaja kasutajatunnuse rida; koormuse protsent kuvatakse töötaja nime all.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/036_schedule_workload_under_employee.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.49 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja live-vaate veerud; laiuse ülevoolu ei ole (`clientWidth` = `scrollWidth` = 1415px).
- Järgmiseks versiooniks pärast 1.49 on `1.50`.

## Versioon 1.48 – 3. oktoober 2026

- Eemaldatud graafikutabeli fikseeritud 1900px miinimumlaius. Päevaveerud jagavad saadaoleva ekraanilaiuse ning töötaja, asukoha ja tundide veerud kasutavad kompaktseid laiusi.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/035_schedule_viewport_width.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.48 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, live-ekraanipilt ja tabeli mõõtmed (`clientWidth` = `scrollWidth` = 1415px).
- Järgmiseks versiooniks pärast 1.48 on `1.49`.

## Versioon 1.47 – 3. oktoober 2026

- Lisatud töötajate kinnistu/osakonna gruppidele avamis-sulgemisnupud, et peita või kuvada grupi read eraldi. Peitmine ei eemalda välju automaatsalvestuse vormist.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/034_schedule_collapsible_groups.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.47 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja live-brauseris sõltumatu grupi sulgemine/avamine; graafikukirjeid ei muudetud.
- Järgmiseks versiooniks pärast 1.47 on `1.48`.

## Versioon 1.46 – 3. oktoober 2026

- Vahetusevaliku modaal kuvab ainult aktiivseid vahetuse- ja erandimalle. Inaktiivsed mallid jäävad olemasolevate graafikukirjete kuvamiseks alles, kuid neid ei saa uue valikuna määrata.
- Mõjutatud failid: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/033_schedule_modal_active_templates.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.46 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja live-modali valikud; inaktiivseid malle loendis ei kuvata.
- Järgmiseks versiooniks pärast 1.46 on `1.47`.

## Versioon 1.45 – 3. oktoober 2026

- Asendatud vaba värvivalik üheksa nimelise värvi rippmenüüga: vikerkaare värvid, must ja valge. Malli värvivalik valideeritakse samas loendis; varasemad muud toonid teisendatakse lubatud põhivärvideks.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `views/panel/schedule/templates.php`, `sql/023_employee_scheduling.sql`, `sql/032_schedule_color_allowlist.sql`, `src/Services/SchemaInstaller.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: olemasolevate mallide värvid normaliseeritakse üheksast lubatud väärtusest koosnevasse loendisse; uute paigalduste vaikevärvid on samuti loendist.
- Kontrollitud: PHP süntaks, värvide serveripoolne lubaloend ja staatiline migratsioonikontroll.
- Järgmiseks versiooniks pärast 1.45 on `1.46`.

## Versioon 1.44 – 3. oktoober 2026

- Lisatud vahetuse- ja erandimallidele määratav värv. Valitud värv kuvatakse modaali valikul ning plaanitud päeva lahtri taustas; olemasolevad graafikukirjed kasutavad malli värvi.
- Mõjutatud failid: `sql/023_employee_scheduling.sql`, `sql/031_schedule_template_colors.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `views/panel/schedule/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: lisatud mallipõhine `color_hex`; olemasolevatele tabelitele lisatakse veerg idempotentselt.
- Kontrollitud: PHP süntaks, hex-värvi valideerimine ja migratsiooni staatiline kontroll. Värvi live-salvestust ei tehtud.
- Järgmiseks versiooniks pärast 1.44 on `1.45`.

## Versioon 1.43 – 3. oktoober 2026

- Vahetuse või erandi valimine salvestab kuugraafiku automaatselt pärast modaali sulgumist; käsitsi salvestamise nupp eemaldatud. Planeeritud tundide kokkuvõte uueneb vastuse põhjal ilma lehte laadimata.
- Mõjutatud failid: `views/panel/schedule/index.php`, `public/index.php`, `src/Services/SchemaInstaller.php`, `sql/030_schedule_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.43 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, diagnostika ja CSRF-vormi AJAX-teekond staatiliselt. Automaatset salvestust live-andmetel ei testitud.
- Järgmiseks versiooniks pärast 1.43 on `1.44`.

## Versioon 1.42 – 3. oktoober 2026

- Asendatud kuugraafiku rippmenüüd täisruuduliste nuppudega, mis avavad ühise vahetuse- ja erandimodaali. Vaba päeva nupp kuvab pika kriipsu; valitud lahter kuvab ainult koodi.
- Mõjutatud failid: `views/panel/schedule/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.42 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks, live-graafiku GET ja modaalis vahetuste/erandite kuvamine. Graafikut ei salvestatud.
- Järgmiseks versiooniks pärast 1.42 on `1.43`.

## Versioon 1.41 – 3. oktoober 2026

- Teisaldatud olemasoleva graafikutabeli auditveergude kontroll ja lisamine PHP-põhiseks, et vältida hosti MySQL-i dünaamilise DDL-i ühilduvusest tingitud rakenduse 500 viga.
- Mõjutatud failid: `src/Services/SchemaInstaller.php`, `sql/027_schedule_audit_columns.sql`, `sql/028_schedule_repair_compatibility.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: enne v1.40 registreerimist lisatakse vajadusel `created_by` ja `updated_by` nullitavad veerud; skeemimuudatust ei tehta, kui need juba olemas on.
- Kontrollitud: PHP süntaks ja live-graafiku GET pärast parandust; olemasolevaid graafikukirjeid ei muudetud.
- Järgmiseks versiooniks pärast 1.41 on `1.42`.

## Versioon 1.40 – 3. oktoober 2026

- Parandatud olemasoleva ajakava tabeli migreerimine: puuduvaid `created_by` ja `updated_by` veerge lisatakse idempotentselt. See väldib `SQLSTATE 42S22` viga vanadel või osaliselt uuendatud paigaldustel.
- Tugevdatud versiooni 1.36 rakendamise kontrolli, et ajakavamigratsiooni loetaks täielikuks ainult auditveergude olemasolul.
- Mõjutatud failid: `sql/027_schedule_audit_columns.sql`, `src/Services/SchemaInstaller.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: olemasolevale `employee_schedule_entries` tabelile lisatakse vajadusel kaks nullitavat auditveergu; versioon 1.40 registreeritakse `system_version_logs` tabelis.
- Kontrollitud: staatiline migreerimisloogika; dünaamiline DDL põhjustas hostis rakenduse käivitamisel 500 vea ning asendati versioonis 1.41 PHP-põhise kontrolliga.
- Järgmiseks versiooniks pärast 1.40 on `1.41`.

## Versioon 1.39 – 3. oktoober 2026

- Kuugraafiku salvestuse ebaõnnestumisel näeb kliendiadministraator üldise teate kõrval SQLSTATE'i ja andmebaasi veanumbrit; tundlikku SQL-i veateksti ei kuvata.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/026_schedule_error_code_feedback.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.39 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja diagnostika piiramine kliendiadministraatorile. Live-salvestust ei korratud.
- Järgmiseks versiooniks pärast 1.39 on `1.40`.

## Versioon 1.38 – 3. oktoober 2026

- Kuugraafiku salvestuse ebaõnnestumisel logitakse serveri PHP-logisse erandi tüüp, veakood ja põhjus; kasutajale kuvatakse endiselt üldine veateade.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/025_schedule_save_error_logging.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.38 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks. Tegeliku andmebaasierandi tuvastamiseks on vaja pärast juurutust uut salvestuskatset.
- Järgmiseks versiooniks pärast 1.38 on `1.39`.

## Versioon 1.37 – 3. oktoober 2026

- Parandatud kuugraafiku salvestuse tõhusust: tühjade `Off` lahtrite jaoks ei käivitata enam kustutuspäringuid, kui vastava päeva kirjet pole.
- Mõjutatud failid: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/024_schedule_save_optimization.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md` ja `docs/05-ai-eeskirjad-ja-prompt.md`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.37 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja muudatuse loogika staatiliselt. Live-salvestust ei korratud, et vältida päris töötajate graafikukirjete muutmist.
- Järgmiseks versiooniks pärast 1.37 on `1.38`.

## Versioon 1.36 – 3. oktoober 2026

- Lisatud eraldi aktiveeritav `schedule` moodul kuupõhise töötajate graafiku ja vahetusemallide jaoks. Graafik kuvab töötaja põhilepingu järgi hoone/korpuse/korruse ja osakonna kaupa; manager näeb ainult töötajaid, kelle kehtival põhilepingul on tema määratud juhiks. Kliendiadministraator näeb kõiki.
- Iga töötaja ja päeva kohta saab valida ühe vahetuse või erandi; tühi `Off` lahter tähendab vaba päeva. Mallid sisaldavad algusaega ja kestust kuni 24 tundi; graafikusse saab lisada vahetusi üle südaöö.
- Vaikimisi mallid: `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h), `8P` (08:00, 8h) ning erandid `HP`, `K`, `LHP`, `LIP`, `P`, `TV`, `X` (08:00, 8h). Kliendiadministraator saab malle eraldi **Shift settings** vaates lisada, muuta ja peita.
- Kuu kokkuvõte näitab koormuse põhjal normtunde (E–R tööpäevad × 8 tundi) ja planeeritud vahetustunde; erandeid planeeritud töötundide hulka ei arvestata. Riigipühade mahaarvamist ei rakendata.
- Mõjutatud failid: `sql/023_employee_scheduling.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `src/Services/ModuleProvisioningService.php`, `public/index.php`, `views/panel/shell.php`, `views/panel/schedule/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md` ja `docs/10-graafiku-moodul.md`.
- Andmebaasimuudatus: lisatud kliendipõhised vahetusemallide ja päevagraafiku tabelid; migratsioon registreerib mooduli ning versiooni 1.36.
- Kontrollitud: PHP süntaks, 2026. aasta oktoobri 22 tööpäeva, vaikimisi 12/24-tunni mallide vorming, manageri lepingu-põhine ligipääs, päevagraafiku vaate renderdus ja kliendipõhised võõrvõtmed. Live-keskkonnas ega päris töötajatele graafikukirjeid ei salvestatud.
- Järgmiseks versiooniks pärast 1.36 on `1.37`.

## Versioon 1.35 – 3. oktoober 2026

- Töötajate nimekirja lisatud **Active contract** veerg, mis näitab töötaja tänase kuupäeva järgi kehtivaid põhilepinguid ja/või ajutisi töökohti. Tähtajatu leping jääb kehtivaks kuni lõppkuupäev lisatakse.
- Päring on piiratud sama kliendi ID-ga ja koondab lepingutüübid ühe töötaja reale, vältides nimekirja korduvaid töötajakirjeid.
- Mõjutatud failid: `src/Services/UserService.php`, `views/panel/users/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md` ja `sql/022_employee_active_contract_column.sql`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.35 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ja kuupäevapiirangutega kliendipõhine alampäring; live-töötajate loendis kuvati kehtiva lepingu korral `Primary` ning muul juhul `No active contract`.
- Järgmiseks versiooniks pärast 1.35 on `1.36`.

## Versioon 1.34 – 3. oktoober 2026

- Kinnistu struktuuris saab sama vanema all sama tüüpi üksusi üles/alla liigutada: hoone korpusi, korpuse korruseid või korruse ruume.
- Järjestus salvestatakse `property_nodes.sort_order` väljale. Olemasolevad kirjed alustavad ID-põhises järjekorras; uued üksused lisatakse oma õdede-vendade loendi lõppu. Muutmise õigus on kliendiadministraatoril ning tegevus logitakse auditlogisse.
- Mõjutatud failid: `sql/021_property_sibling_order.sql`, `src/Services/PropertyService.php`, `src/Services/SchemaInstaller.php`, `public/index.php`, `views/panel/property/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md` ja `docs/08-kinnistu-struktuuri-moodul.md`.
- Andmebaasimuudatus: lisatud `property_nodes.sort_order`; idempotentne migratsioon algväärtustab vanade kirjete järjekorra nende ID-de põhjal ning registreerib versiooni 1.34.
- Kontrollitud: PHP süntaks, järjestamise marsruudi ja migratsiooni registreering ning eraldi korpuse/korruse õdede-vendade järjestuse puhas test.
- Järgmiseks versiooniks pärast 1.34 on `1.35`.

## Versioon 1.33 – 3. oktoober 2026

- Töölepingute loendi **Location** veerg kuvab nüüd kinnistu täieliku nimeahela hoonest valitud tasandini, näiteks `Peamaja - B korpus - 2. korrus - 205`, mitte ainult ruumi/korruse nime.
- Mõjutatud failid: `src/Services/EmploymentContractService.php`, `views/panel/employment/contracts.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md` ja `sql/020_contract_property_path.sql`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.33 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ning kliendipõhise kinnistupuu põhjal nimeahela koostamine `Peamaja - B korpus - 2. korrus - 205`.
- Järgmiseks versiooniks pärast 1.33 on `1.34`.

## Versioon 1.32 – 3. oktoober 2026

- Eemaldatud kuupõhine normtundide kalkulaator lepingu lisamise ja muutmise vormist. Koormuse valik ja lepingule salvestatud protsendikoopia jäävad alles.
- Kuu normtundide arvutus (koormus × jooksva kuu aktiivsed tööpäevad × päevatunnid) on tulevase graafikumooduli vastutus, mitte lepingu koostamise osa.
- Kinnistu asukoha valik näitab nüüd tervet hierarhiat, näiteks `Peamaja - B korpus - 2. korrus`, mitte ainult kriipsudega taanet.
- Mõjutatud failid: `views/panel/employment/contract-form.php`, `src/Services/PropertyService.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `docs/09-toolepingute-moodul.md`, `src/Services/SchemaInstaller.php` ja `sql/019_contract_workload_schedule_scope.sql`.
- Andmebaasimuudatus: skeem ei muutu; migratsioon registreerib versiooni 1.32 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks; lepinguvormis on koormuse valik alles ning kuupõhise arvutuse väljad eemaldatud. Kinnistu asukohasilt moodustatakse hoonest kuni valitud tasandini. Graafikumooduli arvutust siin ei rakendata.
- Järgmiseks versiooniks pärast 1.32 on `1.33`.

## Versioon 1.31 – 3. oktoober 2026

- Lisatud töölepingute moodulisse eraldi **Workloads** kataloog, kus kliendiadministraator saab koormuse nime ja protsenti (0,01–100,00%) lisada, muuta, peita ja taasaktiveerida.
- Leping seob koormuse valiku ning salvestab lepingu hetke protsendiväärtuse eraldi koopiana, et kataloogimuudatus ei muudaks olemasolevat lepingut tagasiulatuvalt. Varasemad lepingud saavad migratsiooniga 100% koormuse.
- Lepingu vorm lisab kuupõhise normtundide kalkulaatori: tööpäevade arv × päevatunnid × koormuse protsent. Näide: 20 × 8 × 50% = 80 tundi. Tööpäevade arvu saab iga kuu jaoks muuta.
- Mõjutatud failid: `sql/018_employment_workloads.sql`, `src/Services/EmploymentCatalogService.php`, `src/Services/EmploymentContractService.php`, `src/Services/SchemaInstaller.php`, `src/Services/ModuleProvisioningService.php`, `public/index.php`, `views/panel/employment/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md` ja `docs/09-toolepingute-moodul.md`.
- Andmebaasimuudatus: lisatud `employment_workloads` tabel ning lepingutele `workload_id` ja `workload_percent`; olemasolevad lepingud tagasitäidetakse 100%-ga enne kliendipõhise võõrvõtme kehtestamist. Migratsioon registreerib versiooni 1.31.
- Kontrollitud: PHP süntaks, 0,01–100,00% sisendipiirangud ja kuu normtundide valem; live-kalkulaator arvutas 20 × 8 × 50% = 80 tundi ning Workloads kataloog ja migratsioon laadisid. Olemasoleva lepingu vormi valikut testiti salvestamata; lepingut ega koormuse kataloogikirjet ei muudetud.
- Järgmiseks versiooniks pärast 1.31 on `1.32`.

## Versioon 1.30 – 1. oktoober 2026

- Lisatud eraldi aktiveeritav `employment` moodul ametinimetuste, osakondade ning töötajate põhilepingute ja ajutiste töökohtade haldamiseks.
- Leping seob sama kliendi töötaja, ametinimetuse, valikulise osakonna, juhi ja `property_nodes` asukoha. Alguskuupäev on kohustuslik; tühi lõppkuupäev tähendab tähtajatut lepingut. Kliendiadministraator saab lepinguid ja katalooge lisada/muuta ning kataloogikirjeid peita.
- Põhilepingute kaasavad kuupäevavahemikud ei tohi sama töötaja puhul kattuda. Ajutise lepingu periood peab täielikult mahtuma põhilepingu sisse ja kasutama teist kinnistu asukohta; põhilepingu muutmine ei tohi muuta seotud ajutist kohta kehtetuks.
- Lisatud kliendipõhised tabelid `employment_job_titles`, `employment_departments` ja `employment_contracts` ning migratsioon `sql/017_employment_module.sql`. Lepingute välisvõtmed piiravad töötaja, juhi, ameti, osakonna ja kinnistuüksuse samale kliendile.
- Mõjutatud failid: `public/index.php`, `src/Services/EmploymentCatalogService.php`, `src/Services/EmploymentContractService.php`, `src/Services/ModuleProvisioningService.php`, `src/Services/SchemaInstaller.php`, `views/panel/shell.php`, `views/panel/employment/`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `docs/09-toolepingute-moodul.md` ja `CHANGELOG.md`.
- Andmebaasimuudatus: lisatud ameti, osakonna ja lepingu tabelid; idempotentne migratsioon lisab vajadusel töötaja kliendipõhise koondindeksi ja registreerib versiooni 1.30. Lepingu vormid eeldavad kliendil aktiivset kinnistu struktuuri moodulit.
- Kontrollitud: PHP süntaks, perioodipiiride ja tähtajatu lepingu juhud, kliendipiirangud, migratsiooni struktuur ning vormi õiguskontrollid. Lepingu tüüpi ei saa pärast loomist muuta. Päris lepinguid ei loodud; kattuvuste MySQL-käitumine vajab live-keskkonnas kliendiadministraatoriga kontrollimist.
- Järgmiseks versiooniks pärast 1.30 on `1.31`.

## Versioon 1.29 – 1. oktoober 2026

- Kliendipaneeli külgmenüüs koondatud **Active modules** ja kõik kliendile aktiveeritud moodulilingid ühe **Modules** pealkirja alla; korduvad pealkirjad eemaldatud.
- Mõjutatud failid: `views/panel/shell.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/016_group_panel_modules.sql`.
- Andmebaasimuudatus: skeem ei muutu; idempotentne migratsioon registreerib versiooni 1.29 tabelis `system_version_logs`.
- Kontrollitud: PHP süntaks ning live-kliendipaneeli menüü struktuur ja moodulite aktiivse oleku järgi kuvamine.
- Järgmiseks versiooniks pärast 1.29 on `1.30`.

## Versioon 1.28 – 1. oktoober 2026


- Lisatud eraldi aktiveeritav `property` moodul koos kliendipõhise `property_nodes` tabeli ja mooduli kataloogikirjega (`sql/015_property_structure.sql`). See ei kasuta teiste moodulite andmetabeleid.
- Kliendipaneeli struktuurivaade ning loomise ja nime muutmise vormid võimaldavad lisada hoone, selle alla korpuse või korruse, korpuse alla korruse ning korruse alla ruumi ja nende nimesid muuta (`views/panel/property/`, `public/index.php`, `views/panel/shell.php`). Lugemine nõuab aktiivset moodulit; loomine ja muutmine kliendiadministraatori õigust ja CSRF-kontrolli.
- `PropertyService` kontrollib vanema kuulumist samale kliendile, lubatud tasemete järjekorda ja nime ning salvestab loomise ja nime muutmise auditlogisse. Andmebaasi sama kliendi välisvõti takistab klientidevahelisi vanemseoseid.
- Lisatud kakskeelne kasutusjuhend `docs/08-kinnistu-struktuuri-moodul.md` ja uuendatud `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `CHANGELOG.md`, `src/Services/SchemaInstaller.php` ning `src/Services/ModuleProvisioningService.php`.
- Andmebaasimuudatus: uus `property_nodes` tabel; migratsioon registreerib versiooni 1.28 tabelis `system_version_logs`. Olemasolevate moodulite andmeskeeme ei muudeta.
- Kontrollitud: PHP süntaks, lubatud vanem-laps tüübid ja UTF-8 nimepiirang; live-veebis struktuurivaade, vanemakohased loomise vormid ja nime muutmise vorm. Olematu kirje annab 404; olemasoleva nime uuesti salvestamine õnnestus nime muutmata. Uusi kirjeid ega eraldi klientidevahelist andmebaasitesti ei tehtud.
- Järgmiseks versiooniks pärast 1.28 on `1.29`.

## Versioon 1.27 – 30. september 2026

- Lisatud täielik versioonide 1.1–1.26 ingliskeelne tõlge faili `CHANGELOG.md`, säilitades eestikeelsed kirjed ja dokumenteeritud piirangud. Muudatuste logi on nüüd kakskeelne.
- Mõjutatud failid: `CHANGELOG.md`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php` ja `sql/014_bilingual_changelog.sql`.
- Andmebaasimuudatus: skeem ei muutu; idempotentne migratsioon registreerib versiooni 1.27 tabelis `system_version_logs`.
- Kontrollitud: versioonide arv ja järjestus mõlemas keeles, README versioonitabelid ja PHP süntaks. Rakenduse käitumine ei muutu.
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

## Version 1.58 - October 3, 2026

- Autosave now submits only the selected contract/day assignment. Stale browser tabs no longer resend the whole month's old values and overwrite other shifts.
- Weekday exceptions are now included in interval-overlap validation because they count as Planned hours; weekend exceptions remain excluded from both.
- Affected files: `views/panel/schedule/index.php`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/045_schedule_single_cell_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.58 in `system_version_logs`.
- Verified: PHP syntax, a single-assignment AJAX payload and exception-overlap boundary cases.
- The next version after 1.58 is `1.59`.

## Version 1.57 - October 3, 2026

- Planned hours now include exception-template duration Monday-Friday only; weekend exception hours are excluded. The rule applies to monthly OT and four-month Tri-OT balances.
- Updated the schedule module guide for temporary contracts, shift overlap and hour accounting.
- Affected files: `src/Services/ScheduleService.php`, `docs/10-graafiku-moodul.md`, `src/Services/SchemaInstaller.php`, `sql/044_schedule_weekday_exception_hours.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.57 in `system_version_logs`.
- Verified: PHP syntax and five exception/weekday accounting cases.
- The next version after 1.57 is `1.58`.

## Version 1.56 - October 3, 2026

- Removed the word `temp` from the temporary-hours breakdown, e.g. `(+24h)`.
- Affected files: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/043_schedule_temp_label.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.56 in `system_version_logs`.
- Verified: PHP syntax and rendered-label change.
- The next version after 1.56 is `1.57`.

## Version 1.55 - October 3, 2026

- Temporary contracts now appear under their own location/department. Their Planned hours show only that location's shifts; no workload-based Required, OT or Tri-OT is calculated for the temporary row. The primary row shows combined primary-plus-temporary Planned hours with the temporary portion in parentheses.
- Multiple contract entries for one employee/day are allowed only when shift intervals do not overlap; shifts that meet exactly at an endpoint are allowed. The check includes overnight shifts.
- Affected files: `sql/023_employee_scheduling.sql`, `sql/042_schedule_contract_day_index.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `views/panel/schedule/index.php`, `public/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: daily-entry uniqueness changes from employee/date to contract/date; the supporting employee foreign-key index is preserved before replacing the old unique index.
- Verified: PHP syntax and five overlap boundary cases, including overnight and touching shifts.
- The next version after 1.55 is `1.56`.

## Version 1.54 - October 3, 2026

- Overnight shift minutes are allocated across month boundaries; at the end of April, August and December, a crossing shift stays entirely in the final month of that four-month balance period.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/040_schedule_cross_month_hours.sql`, `sql/041_schedule_month_boundary_balances.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.54 in `system_version_logs`.
- Verified: PHP syntax, six month-boundary/period-end cases and live summary labels.
- The next version after 1.54 is `1.55`.

## Version 1.53 - October 3, 2026

- Split shifts that cross a month boundary between the two months. In the final month of a Tri-OT period (April, August, December), the full crossing shift stays in that month and is not carried into the next period.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/040_schedule_cross_month_hours.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.53 in `system_version_logs`.
- Verified: PHP syntax and live balance rendering; no schedule entries were created.
- The next version after 1.53 is `1.54`.

## Version 1.52 - October 3, 2026

- Changed `Tri-OT` to four-month cumulative periods: January-April, May-August and September-December. The balance resets at the start of each period.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/039_schedule_four_month_balance.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.52 in `system_version_logs`.
- Verified: PHP syntax and period-start mapping for all 12 months.
- The next version after 1.52 is `1.53`.

## Version 1.51 - October 3, 2026

- Extended autosave responses so Planned, Required, OT and Tri-OT values and balance colors update immediately after a shift is added or removed.
- Affected files: `public/index.php`, `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/038_schedule_balance_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.51 in `system_version_logs`.
- Verified: PHP syntax and static review of all four AJAX summary fields.
- The next version after 1.51 is `1.52`.

## Version 1.50 - October 3, 2026

- Moved hour summaries after the day columns and split them into planned hours, required hours, monthly OT (+ overtime, − undertime), and trimester-to-date cumulative Tri-OT.
- Affected files: `src/Services/ScheduleService.php`, `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/037_schedule_hour_balances.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.50 in `system_version_logs`.
- Verified: PHP syntax, live quarter-to-date balance calculation and table width (`clientWidth` = `scrollWidth` = 1415px).
- The next version after 1.50 is `1.51`.

## Version 1.49 - October 3, 2026

- Removed the separate **Workload** column and employee username/ID line; workload percentage now appears beneath the employee name.
- Affected files: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/036_schedule_workload_under_employee.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.49 in `system_version_logs`.
- Verified: PHP syntax and live view columns; no horizontal overflow (`clientWidth` = `scrollWidth` = 1415px).
- The next version after 1.49 is `1.50`.

## Version 1.48 - October 3, 2026

- Removed the schedule table's fixed 1900px minimum width. Day columns now share the available viewport width while employee, location and hours columns use compact widths.
- Affected files: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/035_schedule_viewport_width.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.48 in `system_version_logs`.
- Verified: PHP syntax, live screenshot and table dimensions (`clientWidth` = `scrollWidth` = 1415px).
- The next version after 1.48 is `1.49`.

## Version 1.47 - October 3, 2026

- Added independent collapse/expand buttons to employee property/department groups. Collapsed rows remain in the autosave form.
- Affected files: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/034_schedule_collapsible_groups.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.47 in `system_version_logs`.
- Verified: PHP syntax and independent group collapse/expand in the live browser; no schedule entries were changed.
- The next version after 1.47 is `1.48`.

## Version 1.46 - October 3, 2026

- The shift-picker modal now lists only active shift and exception templates. Inactive templates remain visible on existing schedule entries but cannot be selected for new assignments.
- Affected files: `views/panel/schedule/index.php`, `src/Services/SchemaInstaller.php`, `sql/033_schedule_modal_active_templates.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.46 in `system_version_logs`.
- Verified: PHP syntax and live modal choices; inactive templates are absent from the list.
- The next version after 1.46 is `1.47`.

## Version 1.45 - October 3, 2026

- Replaced free color selection with a named dropdown limited to the seven rainbow colors plus black and white. Template colors use the same server-side allowlist; previously saved colors outside it are normalized to allowed colors.
- Affected files: `src/Services/ScheduleService.php`, `views/panel/schedule/templates.php`, `sql/023_employee_scheduling.sql`, `sql/032_schedule_color_allowlist.sql`, `src/Services/SchemaInstaller.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: existing template colors are normalized to the allowed list; new-install defaults also use only those colors.
- Verified: PHP syntax, server-side color allowlist and static migration review.
- The next version after 1.45 is `1.46`.

## Version 1.44 - October 3, 2026

- Added configurable color to shift and exception templates. The chosen color appears in modal choices and as the background of scheduled day cells; existing schedule entries inherit the template color.
- Affected files: `sql/023_employee_scheduling.sql`, `sql/031_schedule_template_colors.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `views/panel/schedule/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: added template-scoped `color_hex`; existing template tables receive the column idempotently.
- Verified: PHP syntax, hex-color validation and static migration review. Live color changes were not saved.
- The next version after 1.44 is `1.45`.

## Version 1.43 - October 3, 2026

- Choosing a shift or exception now autosaves the monthly schedule when the modal closes; the manual save button is removed. Planned-hours totals update from the response without reloading the page.
- Affected files: `views/panel/schedule/index.php`, `public/index.php`, `src/Services/SchemaInstaller.php`, `sql/030_schedule_autosave.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; the migration records version 1.43 in `system_version_logs`.
- Verified: PHP syntax, diagnostics and static review of the CSRF-protected AJAX route. Live autosave was not tested with production data.
- The next version after 1.43 is `1.44`.

## Version 1.42 - October 3, 2026

- Replaced monthly schedule dropdowns with full-cell buttons opening a shared shift/exception modal. Off days show a long dash; selected cells show only the template code.
- Affected files: `views/panel/schedule/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; a migration records version 1.42 in `system_version_logs`.
- Verified: PHP syntax, live schedule GET and shift/exception choices in the modal. The schedule was not submitted.
- The next version after 1.42 is `1.43`.

## Version 1.41 - October 3, 2026

- Moved conditional repair of schedule audit columns to PHP so it does not rely on the hosting MySQL server's dynamic-DDL support, which caused application startup to return HTTP 500.
- Affected files: `src/Services/SchemaInstaller.php`, `sql/027_schedule_audit_columns.sql`, `sql/028_schedule_repair_compatibility.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: adds nullable `created_by` and `updated_by` columns before registering v1.40 only when they are missing; no schema change when already present.
- Verified: PHP syntax and live schedule GET after the repair; no schedule entries were modified.
- The next version after 1.41 is `1.42`.

## Version 1.40 - October 3, 2026

- Fixed upgrades of existing schedule tables: missing `created_by` and `updated_by` columns are added idempotently, preventing `SQLSTATE 42S22` on stale or partially upgraded installs.
- Strengthened the v1.36 migration check so scheduling is considered installed only when both audit columns exist.
- Affected files: `sql/027_schedule_audit_columns.sql`, `src/Services/SchemaInstaller.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: conditionally adds two nullable audit columns to `employee_schedule_entries`; records version 1.40 in `system_version_logs`.
- Verified: static migration review; the dynamic-DDL approach caused an HTTP 500 on the host and was replaced in v1.41.
- The next version after 1.40 is `1.41`.

## Version 1.39 - October 3, 2026

- Client administrators now see the SQLSTATE and database error number alongside the generic monthly schedule save error; sensitive SQL error text is not exposed.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/026_schedule_error_code_feedback.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; the migration records version 1.39 in `system_version_logs`.
- Verified: PHP syntax and that diagnostic codes are restricted to client administrators. Live save was not retried.
- The next version after 1.39 is `1.40`.

## Version 1.38 - October 3, 2026

- Failed monthly schedule saves now log the exception type, code and reason to the server PHP log; the user-facing error remains generic.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/025_schedule_save_error_logging.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; the migration records version 1.38 in `system_version_logs`.
- Verified: PHP syntax. A new save attempt after deployment is required to identify the actual database exception.
- The next version after 1.38 is `1.39`.

## Version 1.37 - October 3, 2026

- Improved monthly schedule saves: empty `Off` cells no longer issue DELETE queries when no entry exists for that day.
- Affected files: `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `sql/024_schedule_save_optimization.sql`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, and `docs/05-ai-eeskirjad-ja-prompt.md`.
- Database: no schema changes; the migration records version 1.37 in `system_version_logs`.
- Verified: PHP syntax and static control-flow review. Live save was not retried to avoid changing real employee schedules.
- The next version after 1.37 is `1.38`.

## Version 1.36 - October 3, 2026

- Added a separately activated `schedule` module for monthly employee rosters and shift templates. The roster groups primary-contract employees by building/wing/floor and department; managers see only employees whose valid primary contract names them as manager. Client administrators can see all employees.
- One shift or exception can be assigned per employee per day; an empty `Off` cell means a day off. Templates have a start time and duration up to 24 hours, so shifts can continue past midnight.
- Default templates: `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h), `8P` (08:00, 8h), and exceptions `HP`, `K`, `LHP`, `LIP`, `P`, `TV`, `X` (08:00, 8h). Client administrators can add, edit and hide templates under **Shift settings**.
- The monthly summary shows workload-based required hours (Monday-Friday workdays × 8 hours) and planned shift hours; exceptions are not counted as planned working hours. Public holidays are not deducted.
- Affected files: `sql/023_employee_scheduling.sql`, `src/Services/ScheduleService.php`, `src/Services/SchemaInstaller.php`, `src/Services/ModuleProvisioningService.php`, `public/index.php`, `views/panel/shell.php`, `views/panel/schedule/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, and `docs/10-graafiku-moodul.md`.
- Database: added client-scoped shift-template and daily-schedule tables; the migration registers the module and version 1.36.
- Verified: PHP syntax, 22 workdays in October 2026, default 12/24-hour template formatting, manager contract-scoped access, monthly view rendering and client-scoped foreign keys. No live schedule entries were saved.
- The next version after 1.36 is `1.37`.

## Version 1.35 - October 3, 2026

- Added an **Active contract** column to the employee list, showing primary and/or temporary contracts valid today. An open-ended contract remains valid until an end date is added.
- The query is scoped to the same client and aggregates contract types per employee, avoiding duplicate employee rows.
- Affected files: `src/Services/UserService.php`, `views/panel/users/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, and `sql/022_employee_active_contract_column.sql`.
- Database: no schema changes; a migration records version 1.35 in `system_version_logs`.
- Verified: PHP syntax and the client-scoped date-validity subquery; the live employee list showed `Primary` for employees with a currently valid contract and `No active contract` otherwise.
- The next version after 1.35 is `1.36`.

## Version 1.34 - October 3, 2026

- Property structure entries of the same type can be moved up or down under the same parent: wings within a building, floors within a wing, and rooms within a floor.
- Order is stored in `property_nodes.sort_order`. Existing entries retain ID order as their initial order; new entries are appended to their sibling list. Reordering is restricted to client administrators and audited.
- Affected files: `sql/021_property_sibling_order.sql`, `src/Services/PropertyService.php`, `src/Services/SchemaInstaller.php`, `public/index.php`, `views/panel/property/index.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, and `docs/08-kinnistu-struktuuri-moodul.md`.
- Database: added `property_nodes.sort_order`; an idempotent migration initializes existing rows by ID and records version 1.34.
- Verified: PHP syntax, reorder route and migration registration, and a pure test of independent wing/floor sibling ordering.
- The next version after 1.34 is `1.35`.

## Version 1.33 - October 3, 2026

- The employment contracts **Location** column now shows the complete property path from building to selected level, for example `Main building - Wing B - Floor 2 - Room 205`, instead of only the room/floor name.
- Affected files: `src/Services/EmploymentContractService.php`, `views/panel/employment/contracts.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, and `sql/020_contract_property_path.sql`.
- Database: no schema changes; a migration records version 1.33 in `system_version_logs`.
- Verified: PHP syntax and full path formatting from the client-scoped property tree: `Peamaja - B korpus - 2. korrus - 205`.
- The next version after 1.33 is `1.34`.

## Version 1.32 - October 3, 2026

- Removed the monthly required-hours calculator from contract create/edit forms. Workload selection and the percentage snapshot stored on each contract remain.
- Monthly required hours (workload × current month's active workdays × daily hours) belong to the future scheduling module, not contract creation.
- Property location options now show the complete hierarchy, for example `Main building - Wing B - Floor 2`, instead of indentation marks alone.
- Affected files: `views/panel/employment/contract-form.php`, `src/Services/PropertyService.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `docs/09-toolepingute-moodul.md`, `src/Services/SchemaInstaller.php`, and `sql/019_contract_workload_schedule_scope.sql`.
- Database: no schema changes; a migration records version 1.32 in `system_version_logs`.
- Verified: PHP syntax; workload selection remains, monthly calculation controls are removed, and property labels use the full hierarchy. Scheduling calculations are not implemented here.
- The next version after 1.32 is `1.33`.

## Version 1.31 - October 3, 2026

- Added a separate **Workloads** catalog to the Employment module. Client administrators can add, rename, hide and reactivate workload choices from 0.01% to 100.00%.
- Contracts link to a workload choice and store a snapshot of its percentage, so later catalog edits do not retroactively change existing contracts. Existing contracts are backfilled to 100% by the migration.
- Added a monthly required-hours calculator to the contract form: working days × hours per workday × workload percentage. Example: 20 × 8 × 50% = 80 hours. Working days can be adjusted for each month.
- Affected files: `sql/018_employment_workloads.sql`, `src/Services/EmploymentCatalogService.php`, `src/Services/EmploymentContractService.php`, `src/Services/SchemaInstaller.php`, `src/Services/ModuleProvisioningService.php`, `public/index.php`, `views/panel/employment/`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, and `docs/09-toolepingute-moodul.md`.
- Database: added `employment_workloads` and contract columns `workload_id` and `workload_percent`; existing contracts are backfilled to 100% before adding the tenant-scoped foreign key. The migration records version 1.31.
- Verified: PHP syntax, the 0.01–100.00% input range and monthly-hours formula; the live calculator returned 80 hours for 20 × 8 × 50%, and the Workloads catalog and migration loaded. The existing contract form was tested without submission; no contract or workload catalog entry was changed.
- The next version after 1.31 is `1.32`.



## Version 1.30 - October 1, 2026

- Added a separately activated `employment` module for job titles, departments, primary contracts and temporary workplace assignments.
- Each contract links a same-client employee, job title, optional department, manager and `property_nodes` location. A start date is required; a blank end date means open-ended. Client administrators can add or edit contracts and catalogs, and hide catalog entries.
- Inclusive primary contract periods cannot overlap for the same employee. A temporary contract must fit entirely within a primary contract at a different property location; editing a primary contract cannot invalidate linked temporary assignments.
- Added client-scoped `employment_job_titles`, `employment_departments`, and `employment_contracts` tables in `sql/017_employment_module.sql`. Contract foreign keys keep employee, manager, title, department and property references within the same client.
- Affected files: `public/index.php`, `src/Services/EmploymentCatalogService.php`, `src/Services/EmploymentContractService.php`, `src/Services/ModuleProvisioningService.php`, `src/Services/SchemaInstaller.php`, `views/panel/shell.php`, `views/panel/employment/`, `README.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `docs/09-toolepingute-moodul.md`, and `CHANGELOG.md`.
- Database: new job title, department and contract tables; an idempotent migration adds the employee composite client index if needed and records version 1.30. Contract forms require the property structure module to be active for the client.
- Verified: PHP syntax, date interval boundary and open-end cases, tenant constraints, migration structure, and form permission checks. Contract type is immutable after creation. No real contracts were created; MySQL-backed overlap behavior still needs live verification with the module activated for a client administrator.
- The next version after 1.30 is `1.31`.

## Version 1.29 - October 1, 2026

- Grouped Active modules and all module links activated for the client under one Modules heading in the client panel sidebar; removed duplicate headings.
- Affected files: `views/panel/shell.php`, `README.md`, `CHANGELOG.md`, `docs/01-projektinouded-ja-disaininouded.md`, `docs/05-ai-eeskirjad-ja-prompt.md`, `src/Services/SchemaInstaller.php`, and `sql/016_group_panel_modules.sql`.
- Database: no schema changes; an idempotent migration records version 1.29 in `system_version_logs`.
- Verified: PHP syntax and the live client panel menu structure and visibility based on module activation.
- The next version after 1.29 is `1.30`.

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
