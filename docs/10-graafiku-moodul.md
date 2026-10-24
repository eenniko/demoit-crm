# Töögraafiku moodul / Work schedule module

**Süsteemi arendusversioon:** 1.93

Algoritmide, JSON-liideste, XML-vormingu ja piirijuhtude täpne kirjeldus on [tehnilises taastamisspetsifikatsioonis](12-tehniline-taastamisspetsifikatsioon.md); kontrollnäited [vastuvõtukontrollides](14-taastamise-vastuvotukontrollid.md).

**Algandmete piirang:** allpool nimetatud vaikemallid lisatakse migratsiooni ajal juba olemasolevatele klientidele; hiljem loodud kliendi provisioneerimine neid automaatselt ei loo. Pühade kalender on globaalne, mitte kliendipõhine, ning tuleb eraldi importida. Malli muutmine mõjutab ka olemasolevate kannete tundide arvutust.

## Eesti keeles

### Aktiveerimine ja ligipääs

Süsteemiadministraator aktiveerib kliendile **Schedule** mooduli. Graafik vajab samal kliendil ka **Employment** ja **Property structure** moodulite aktiveerimist. Menüülink asub kliendipaneeli **Modules** all.

Kliendiadministraator näeb kõiki kliendi aktiivsete kontode kuuga lõikuvaid arhiveerimata põhi- ja ajutisi lepinguid. Manager näeb ja muudab lepinguridu, millel on tema määratud juhiks; ajutise lepingu juhti kontrollitakse eraldi, mitte põhilepingu kaudu. Graafik koondab töötajad lepingu kinnistu asukohatee ning osakonna järgi. Asukohatee võib sisaldada ka ruumi, kui leping seoti ruumiga.

Alates 1.82 näeb ja muudab manager lisaks enda põhi- ning ajutisi lepinguridu, kui ta on valitud kuul vähemalt ühe teise aktiivse töötaja kuuga lõikuval arhiveerimata põhi-/ajutisel lepingul samas kliendis manager. Enda lepingul võib juhiks olla keegi teine. Ainult tulevane, lõppenud, arhiveeritud või mitteaktiivse töötaja alluvussuhe seda õigust ei anna. Määrangu eemaldamine kaotab õiguse järgmisel päringul. Manager saab ka lubatud põhilepingute kuu koormust muuta; CSRF/kattuvuste kontroll säilib. Eraldi manageri rolli pole, õigus tuleb lepingute manager ID-st. Level F ilma client_admin'ita jääb alati ainult printima ka siis, kui lepingus on ta manager.

### Graafikuasendaja (1.83)

Manager avab **Töögraafik -> Graafikuasendajad**, valib sama kliendi aktiivse töötajakonto ning algus/lõppaja ja määrab asendaja ise, ilma kinnitamiseta. Kliendiadministraator võib valida asendatava päris manageri. Manager saab määrata ainult enda asenduse; asendaja ei saa pärida teiste asenduste õigusi ega neid edasi volitada. Puuduva konto lisab ja aktiveerib kliendiadministraator **Töötajad ja õigused** all, määrates tavalise Juurdepääsutaseme. Asendajal ei pea olema töölepingut ega organisatsiooniseost.

Õigus toimib ainult tegeliku hetke vahemikus `algus <= nüüd < lõpp`: jooksva ja tulevaste kuude manageri enda ning talle managerina määratud lepinguridade vahetusi saab vaadata/muuta. Varasemad kuud pole asendusega muudetavad. Asendus ei anna kuukoormuse, Vahetuste seadete ega Riigipühade haldamise õigust. Olemasoleva rolli iseseisvad õigused jäävad alles. F kasutaja saab ainult Töögraafikus ajutise muutmisvaate; tema muud F piirangud ja enda tavapärane prindiskoobiga vaatamine säilivad. Manageri enda graafiku pärimine nõuab sama valitud kuu alluvaga manageri reeglit kui algsel manageril.

Manager/admin saab vajutada **Lõpeta asendaja õigused**; muidu kaob ligipääs automaatselt lõppajal. Konto deaktiveerimine või algse manageri muutmine print-only F-ks lõpetab asendusõiguse. Croni pole vaja ja konto rolli ei lisata/eemaldata. Vanad organisatsiooni Asendajad-taotlused seda graafikuõigust ei anna. Aja väljade sildil näidatakse PHP rakenduse ajavööndit; asenduse kehtivuse SQL-võrdlus saab sama rakenduse kellaaega parameetrina, mitte DB serveri eraldi ajavööndi NOW väärtust.

### Kuugraafiku kasutamine

Alates versioonist 1.80 saab aktiivne Level F ilma `client_admin` rollita vaikimisi ainult **Print schedule** vaate; 1.83 aktiivne graafikuasendus annab ülal kirjeldatud ajutise erandi ainult vahetustele. Ilma asenduseta **Work schedule** menüü avab printi; kuugraafiku GET suunab valitud kuuga printi. F kuugraafiku POST (ka AJAX) ilma aktiivse asenduseta ja kuukoormuse muutmine alati annavad 403. Monthly schedule pole tavalises F prindivaates nähtav; Shift settings ja Public holidays ning nende otsesed URL-id jäävad F-le keelatuks ka asenduse ajal.

F näeb kogu asukoha/osakonna graafikut koos kaastöötajatega ainult nendes gruppides, kus tal on valitud kuuga lõikuv arhiveerimata põhi- või ajutine leping. Põhikoht ei nõua 100% koormust. Grupp on täpne `property_node_id` + `department_id` paar, NULL osakond eraldi grupina. Osalise kuu leping lubab kogu selle kuu grupi. Lõppenud/tulevane/arhiveeritud leping ei anna selle kuu ligipääsu; ka juhi määrang mujal ei anna F-le lisagruppe. Konto tuvastatakse sessiooni ID, mitte nime/URL-i järgi ja kliendifilter säilib. Lepinguta kuu näitab tühja vaadet. Lubatud gruppe saab valida ja **Print / Save as PDF** abil printida/salvestada. Täiendav aktiivne `client_admin` säilitab administraatori õigused; teiste rollide käitumine ei muutu.

Vali kuu; iga kehtiv põhi- või ajutine leping kuvatakse oma asukoha/osakonna all eraldi real ja kuu päevad eraldi veergudena. Kui leping kehtib ainult osa kuust, on valitavad ainult lepinguperioodi päevad. Ühel töötajal võib samal päeval olla mitu lepingurida ainult siis, kui vahetuste ajavahemikud ei kattu. Tühi **Off** valik tähendab vaba päeva.

Päevaseks valikuks saab määrata vahetuse või erandi. Erandi valikul saab enne malli klõpsamist määrata **järjestikuste kalendripäevade arvu 1–366**, valitud kuupäev kaasa arvatud; sama erand rakendub iga järgmise päeva lahtrile. Vahemik võib ületada kuu piiri ning järgmise kuu salvestatud kirjed ilmuvad vastava kuu graafikus. Vahetused, vabad päevad ja Block jäävad ühepäevaseks. Kogu vahemik salvestatakse ühe transaktsioonina: iga kuupäev peab jääma lepingu perioodi ning manager/asendaja peab saama seda lepingut muuta igas mõjutatud kuus. Vastasel juhul midagi ei salvestata. Kattuva vahetuse või Block-i korral lükatakse samuti kogu vahemik tagasi; olemasolevad tulevaste päevade väärtused asendatakse sama erandiga ainult siis, kui kogu vahemik valideerub.

Malli algusaeg ja kestus määravad tööaja; vahetus võib kesta kuni 24 tundi ja jätkuda järgmise päeva hommikusse. Näiteks `12Ö` algab 20:00 ja kestab 12 tundi; `24H` algab 08:00 ja kestab 24 tundi. Shift-tunnid lähevad Planned-summasse kõigil päevadel; Exception-tunnid lähevad sinna tööpäevadel, välja arvatud imporditud riigi- ja rahvuspühadel (`kind_id` 1 ja 2).

`Block`-mall blokeerib sama töötaja kattuvad vahetuse ja exception’i ajavahemikud, kuid Block-tunde Planned-summasse ei arvestata. Kogu päeva blokeerimiseks võib näiteks luua `EST`-malli algusega 00:00 ja kestusega 24 tundi. Täpselt kõrvuti lõppev/alustav vahemik on lubatud.

### Printimine

Vahelehel **Print schedule** vali kuu ja märgi prinditavad asukoha/osakonna grupid. Kõik grupid on vaikimisi valitud; **Select all** ja **Clear selection** muudavad valikut. Nupp **Print / Save as PDF** avab brauseri printimisakna. Iga valitud grupp algab eraldi landscape-lehelt; suurem grupp võib jätkuda järgmisel lehel. Printimisel peidetakse CRM-i päis, menüüd, graafiku vahelehed, jalus ja valikunupud; iga leht kuvab paremas ülanurgas tänase kuupäeva. Tundide veergudest kuvatakse ainult **Sum h**; **Min h**, **OT** ja **Tri-OT** jäetakse välja. Kõik töötajaread on sama kõrgusega nagu „Temporary assignment“ rida. Prindivaates säilivad vahetusmallide värvid ning põhilepingu **Sum h** veerus kuvatakse ka ajutiste töötundide jaotus.

### Vahetuste seaded ja tundide kokkuvõte

Kliendiadministraator haldab vahelehel **Shift settings** vahetuste ja erandite koode, algusaegu ning kestusi. Vaikimisi vahetused on `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) ja `8P` (08:00, 8h). Vaikimisi erandid on `HP`, `K`, `LHP`, `LIP`, `P`, `TV` ja `X` (08:00, 8h). Kasutuses olevat malli ei kustutata: selle saab peita; olemasolevad graafikukirjed säilivad.

Graafiku lõpus kuvatakse **Kokku h / Sum h**, Required, OT ja neljakuuline Tri-OT. Kokku h sisaldab planeeritud töötunde. Ajutise lepingu real on ainult selle asukoha tunnid; põhilepingu real liidetakse põhi- ja ajutise koha tunnid ning positiivse ajutise mahu korral kuvatakse eraldi jaotus. Required arvutatakse ainult põhilepingu workload’i põhjal. Kuu OT = Planned − Required. Tri-OT kumuleerub perioodidel jaanuar–aprill, mai–august ja september–detsember. Kuu piiri ületava vahetuse tunnid jagatakse kuude vahel; neljakuulise perioodi viimases kuus jääb vahetus tervikuna perioodi viimasesse kuusse. Required norm on põhilepingu koormus × kuu tööpäevad (E–R, välja arvatud riigi- ja rahvuspühad `kind_id` 1 ja 2) × 8 tundi. Riiklikud tähtpäevad (`kind_id` 3) ja lühendatud tööpäevad (`kind_id` 4) jäävad tööpäevadeks.

Põhilepingu koormus kehtib vaikimisi lepingu kogu perioodil. Kui koormus muutub ajutiselt, vali graafikurea töötajanime all vastava kuu workload. Kuu erand muudab ainult selle kuu Required-, OT- ja Tri-OT arvestust; lepingut ega olemasolevaid päevakandeid ei poolitata. Jäta valik lepingu vaikeväärtusele, et kuu erand eemaldada.

Andmed on kliendipõhistes tabelites `employee_schedule_templates` ja `employee_schedule_entries`; kuukoormuse erandid salvestatakse tabelis `employment_contract_monthly_workloads`. Leping, töötaja, vahetuse mall ja muutja valideeritakse sama kliendi piires ning muutmistehing nõuab CSRF-kaitset. Iga salvestus läheb auditlogisse. Skeemi paigaldavad SQL-migratsioonid `sql/023_employee_scheduling.sql` ja `sql/050_monthly_workload_overrides.sql`.

## In English

### Activation and access

A system administrator activates the **Schedule** module for a client. The same client must also have **Employment** and **Property structure** active. The link appears under **Modules** in the client panel.

A client administrator can manage all unarchived primary and temporary contracts overlapping the month for active accounts. A manager can see and edit contract rows that name that user as manager; the temporary contract's manager is checked separately. The roster groups rows by their property path and department. The path can include a room when the contract references a room.

Since 1.82 managers also see/edit their own primary and temporary contracts when assigned as manager to at least one other active employee's unarchived primary/temporary contract overlapping the selected month in the same client. Their own contract may have a different manager. Future, ended, archived or inactive-subordinate assignments do not qualify. Removing the assignment revokes access on the next request. Permitted primary-contract monthly workloads are editable with the existing CSRF/overlap checks. There is no separate manager role: contract manager IDs determine scope. Level F without client_admin remains print-only even if assigned as a manager.

### Schedule substitutes (1.83)

Managers assign their own active same-client employee-account substitute under **Work schedule -> Schedule substitutes**, with start/end datetime and no approval. Administrators may select a real manager. Missing accounts must first be added and activated by the client administrator under Employees & permissions with their ordinary access level. Substitutes need no employment contract or organisation membership.

Only during `start <= now < end`, the substitute may edit current/future month shifts on the original manager's own and explicitly managed contracts. Past-month editing, workload changes, template/holiday administration and delegation chains are not granted. Independent base-role rights remain; F gains editing only in Schedule and retains its other F restrictions and personal print scope.

Managers/admins may end assignments early, otherwise access expires on the next request at the end timestamp without cron or role mutation. Inactive accounts or an original manager becoming print-only F invalidate access. Existing organisation substitution requests grant no such rights. Datetime labels show the application timezone; SQL expiry comparisons receive that same application clock as parameters rather than the database server's independent NOW clock.

### Using the monthly roster

Since 1.80, active Level F users without `client_admin` have only **Print schedule**. The sidebar opens print directly and monthly GET redirects with the selected month. Monthly POST (including AJAX) and workload writes return 403; monthly editing, shift settings and holiday tabs are unavailable.

Level F sees complete coworker rosters only for exact property/department groups containing its own unarchived primary or temporary contract overlapping the selected month. Primary contracts need not have 100% workload. NULL department is a separate group; partial-month membership grants that month's full group. Expired/future/archived or other-client contracts and manager assignments elsewhere do not grant access. Account and client IDs come from the session, not names or URL parameters. A month without membership shows no groups. Group selection and browser **Print / Save as PDF** remain available. An additional active `client_admin` retains administrator rights; other roles remain unchanged.

Choose a month; each valid primary or temporary contract appears under its own location/department row, with each date in its own column. If the contract is valid for only part of the month, only dates inside that contract period are editable. An employee may have multiple contract rows on one date only when shift intervals do not overlap. An empty **Off** choice means a day off.

Each day can have a shift or an exception. When choosing an exception, enter **1–366 consecutive calendar days**, including the selected date; the same exception is applied to each following date. The range may cross a month boundary, and entries in the next month appear when viewing that month. Shifts, Off and Blocks remain single-day selections. The range saves atomically: every date must be within the contract period and the manager/substitute must be authorized for the contract in every affected month. Otherwise nothing is saved. An overlap with a shift or Block also rejects the whole range; existing future-day values are replaced only if the entire range validates. Migration 080 supplies the day-count and contract-boundary translations.

A template's start time and duration determine its span; shifts may last up to 24 hours and continue into the next morning. For example, `12Ö` starts at 20:00 and lasts 12 hours; `24H` starts at 08:00 and lasts 24 hours. Shift hours count as Planned on every day; exception hours count only on working weekdays, excluding imported holidays with `kind_id` 1 or 2.

A `Block` template prevents overlapping shift and exception intervals for the same employee, but Block hours are excluded from Planned. To block a full day, create a template such as `EST` starting at 00:00 for 24 hours. Exact end-to-start handoffs remain allowed.

### Printing

On **Print schedule**, choose a month and select the location/department groups to print. All groups are selected by default; **Select all** and **Clear selection** change the selection. **Print / Save as PDF** opens the browser print dialog. Each selected group starts on a separate landscape page; a large group may continue onto additional pages. Printing hides the CRM header, navigation, schedule tabs, footer and selection controls; each page shows today's date at the top right. Only **Sum h** is shown among the totals; **Min h**, **OT** and **Tri-OT** are omitted. All employee rows have the same height as the Temporary assignment row. The print view preserves shift-template colors and shows the temporary-hours breakdown under primary-contract **Sum h** totals.

### Shift settings and hour summary

A client administrator manages shift and exception codes, start times and durations on **Shift settings**. Default shifts are `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) and `8P` (08:00, 8h). Default exceptions are `HP`, `K`, `LHP`, `LIP`, `P`, `TV` and `X` (08:00, 8h). Referenced templates are retained and can be hidden; existing schedule entries remain intact.

The roster ends with **Sum h** (planned hours), Required, OT and four-month Tri-OT totals. A temporary row shows only its location's planned hours; the primary row combines primary and temporary planned hours and shows the temporary-hours breakdown when positive, while Required uses only primary-contract workload. Monthly OT = Planned − Required. Tri-OT accumulates over January-April, May-August and September-December. Shift minutes crossing a month boundary are split between months, except in the final month of a four-month period, where the full shift stays in that period. Required hours use primary-contract workload × working weekdays × 8 hours; imported national and public holidays (`kind_id` 1 and 2) are excluded. Weekday exceptions on those holidays are excluded from Planned, matching weekend behavior.

Client administrators can upload an XML holiday list from the dedicated **Public holidays** schedule tab, which also lists every imported date, title, kind and note. The import adds only entries not already stored; existing records are never overwritten. National observances and shortened workdays (`kind_id` 3 and 4) are retained as calendar data but do not turn a weekday into a non-working day.

Primary contract workload applies by default throughout the contract. For a temporary monthly change, choose the workload under the employee name in that month's schedule. The override affects only that month's Required, OT and Tri-OT; the contract and existing daily entries are not split. Select the contract default to remove the monthly override.

Data is stored in the client-scoped `employee_schedule_templates` and `employee_schedule_entries` tables; monthly workload overrides are stored in `employment_contract_monthly_workloads`. Contract, employee, shift template and editor are validated within the same client; writes require CSRF protection and are audited. The schema is installed by `sql/023_employee_scheduling.sql` and `sql/050_monthly_workload_overrides.sql`.
