# Töölepingute moodul / Employment module

**Süsteemi arendusversioon:** 1.97

Täielik andmeskeem asub [SQL-taastepaketis](15-andmebaasi-taastepakett.sql), tehnilised piirijuhud ja testid [taastamisspetsifikatsioonis](12-tehniline-taastamisspetsifikatsioon.md) ning [vastuvõtukontrollides](14-taastamise-vastuvotukontrollid.md).

## Eesti keeles

### Aktiveerimine ja ligipääs

Süsteemiadministraator aktiveerib kliendile eraldi **Employment** mooduli kliendi detailvaates (`/admin/clients/view`). Lepingute lisamiseks ja muutmiseks peab kliendile olema aktiveeritud ka **Property structure**, sest töökoht seotakse selle mooduli asukohaga. Kliendikasutajad saavad lepinguid vaadata; ametite, osakondade ja lepingute haldamine on kliendiadministraatori õigus.

### Ametid ja osakonnad

Versioon 1.91 lisab migratsiooniga `sql/078_new_job_title_translation.sql` täieliku dünaamilise vormisildi „New job title” tõlke: eesti keeles „Uus ametinimetus”, vene keeles „Новая должность”. Võti on `ui.` + ingliskeelse fraasi SHA-256; olemasolevad kohandatud väärtused säilivad. Kasutaja sisestatud ametinimetusi ei tõlgita ega muudeta.

Versioon 1.92 lisab `sql/079_employment_ui_translations.sql` kaudu järgmised puudunud fraasid. SQL kasutab ajutist seed-tabelit, lisab võtmed/väärtused `INSERT IGNORE` abil ja eemaldab ajutise tabeli; püsiskeem ei muutu. Kataloogi väljade aria-label ja Employment navigatsiooni aria-label kasutavad `TranslationService::translate('ui.' . hash('sha256', $english), $english)` serveris. Ülejäänud nähtavat teksti tõlgib olemasolev jagatud UI textMap.

| English | Eesti | Русский |
| --- | --- | --- |
| New department | Uus osakond | Новый отдел |
| New workload | Uus koormus | Новая нагрузка |
| Job title name | Ametinimetuse nimi | Название должности |
| Department name | Osakonna nimi | Название отдела |
| Workload name | Koormuse nimi | Название нагрузки |
| Hide | Peida | Скрыть |
| Activate | Aktiveeri | Активировать |
| Edit contract | Muuda lepingut | Изменить договор |
| Open-ended | Tähtajatu | Бессрочный |
| Restore | Taasta | Восстановить |

Alates versioonist 1.79 näeb aktiivse Level F rolliga kasutaja ainult enda konto ID-ga seotud põhi- ja ajutisi lepinguid, sealhulgas loendi olemasolevaid lõppenud/arhiveeritud kirjeid. Teise töötaja või kliendi ID saatmine URL-is ei muuda nähtavust. **Job titles**, **Departments** ja **Workloads** vahelehed on peidetud ning otse kataloogi URL-i GET/POST annab 403. Enda lepingu amet, osakond ja koormus jäävad lepingu real nähtavaks; kataloogide loendeid ei näidata. Muutmine ja arhiveerimine jäävad kliendiadministraatorile. Täiendav aktiivne `client_admin` roll säilitab kogu kliendi ligipääsu; teised rollid jäävad muutmata.

Mooduli vahelehtedel **Job titles**, **Departments** ja **Workloads** saab lisada ning muuta vastavaid valikuid. Koormuse valikul on nimi ja protsent vahemikus 0,01–100,00%. Kasutuses olevat kirjet ei kustutata: selle saab peita ning vajadusel uuesti aktiveerida. Peidetud kirjeid ei pakuta uutele lepingutele, kuid olemasolev leping säilitab oma viite ja koormuse protsendi koopia.

### Lepingud

Alates 1.97 on loendi kohal töötaja nimeosa otsing ja **Töölepingute loendi seaded**. Otsing hõlmab kõiki nähtavaid lepinguid; uus otsing alustab esimeselt lehelt. Kliendiadministraator saab määrata 1–100 lepingut lehel, vaikimisi 10. See on ühine sama kliendi kasutajatele ja otsingutulemustele, kuid sõltumatu töötajate nimekirja seadest. Leheküljenumbrid kuvatakse täpselt üks kord kogu tabeli all ning lingid säilitavad otsingu. Level F näeb endiselt ainult enda lepinguid, sh lõppenud/arhiveeritud kirjeid.

Leping seob sama kliendi aktiivse töötaja, ametinimetuse, kinnistu asukoha, valikulise osakonna ja valikulise juhi. Töötaja ise juhiks määrata ei saa. Töölepingul on kohustuslik alguskuupäev; lõppkuupäeva võib tühjaks jätta, mis tähendab tähtajatut lepingut. Lepingu lõppkuupäeva hiljem lisamine sulgeb kehtivusperioodi.

Iga leping on kas **Primary workplace** või **Temporary workplace**. Lepingul on koormuse valik ja selle lepingu hetke protsendikoopia. Lepingu vorm ei arvuta kuunormi. Olemasolev graafikumoodul arvutab kohustuslikud tunnid põhilepingu kuu ja kehtivusperioodi ühisosa tööpäevade järgi: `koormusprotsent / 100 × tööpäevad × 8`; näiteks 50% × 20 tööpäeva × 8 tundi = 80 tundi. Asukoht kuvatakse lepingu nimekirjas täieliku teena hoonest valitud korruse või ruumini. Lepingu liiki pärast loomist muuta ei saa. Sama töötaja põhilepingute kuupäevavahemikud on kaasavad ning ei tohi kattuda. Tähtajatu põhileping katab kõik järgnevad kuupäevad kuni sellele lõppkuupäeva lisamiseni. Ajutine töökoht peab täielikult mahtuma põhilepingu perioodi ja kasutama põhikohast erinevat kinnistu asukohta. Põhilepingu muutmisel kontrollitakse, et seotud ajutised töökohad jääksid endiselt kehtivaks. Erinevate ajutiste töökohtade perioodid võivad omavahel kattuda.

### Ajutise töökoha lisareeglid ja arhiveerimine

Sama töötaja ajutisi lepinguid ei saa luua samale korrusele samas kalendrikuus isegi siis, kui nende päevavahemikud ei kattu. Ruumi puhul kasutatakse selle korrust; korruseta asukoha puhul võrreldakse asukoha ID-d. Muutmisel töötaja ja lepingu liik säilivad.

Ainult graafikukanneteta ajutise lepingu saab arhiveerida (`archived_at`); põhilepingut ega ühegi graafikukandega ajutist lepingut arhiveerida ei saa. Taastamine eemaldab arhiveerimisaja, säilitades lepingu ID ja andmed. Graafik ei kuva arhiveeritud lepinguid. Arhiveerimisel andmeid füüsiliselt ei kustutata.

Põhilepingu ajutine kuukoormus salvestub graafikust tabelisse `employment_contract_monthly_workloads`; põhilepingut ei poolitata. See on eraldi lepingu koormuse hetkekoopiast. Skeemi vastavad täiendused on migratsioonides 046 ja 050 ning installeri parandustes.

### Andmed ja turvakontrollid

Andmed asuvad kliendipõhistes tabelites `employment_job_titles`, `employment_departments`, `employment_workloads` ja `employment_contracts`. Lepingute võõrvõtmed piiravad töötaja, juhi, ameti, koormuse, osakonna ja kinnistuüksuse samale kliendile. Põhilepingu kattuvuskontroll toimub andmebaasitransaktsioonis töötaja rea lukustamisega. Loomine ja muutmine logitakse auditlogisse. Põhiskeemi lisab `sql/017_employment_module.sql`; koormuste tabeli ja olemasolevate lepingute 100% tagasitäitmise lisab `sql/018_employment_workloads.sql`.

## In English

### Activation and access

A system administrator activates the separate **Employment** module for a client from `/admin/clients/view`. **Property structure** must also be active for contract creation and editing because each workplace is linked to a location in that module. Client users may view contracts; managing job titles, departments and contracts is restricted to the client administrator.

### Job titles and departments

Since version 1.79, active Level F users see only primary/temporary contracts linked to their signed-in account ID, including existing ended/archived list entries. URL-supplied employee/client IDs do not widen access. **Job titles**, **Departments** and **Workloads** tabs are hidden and direct catalog GET/POST requests return 403. Their own contract's title, department and workload remain visible in the contract row. Editing/archiving remains administrator-only. An additional active `client_admin` role retains full client access; other roles remain unchanged.

The **Job titles**, **Departments** and **Workloads** tabs allow administrators to add and edit choices. A workload has a name and a percentage from 0.01% to 100.00%. Referenced entries are retained rather than deleted: they can be hidden and reactivated later. Hidden entries are not offered for new contracts, while existing contracts retain their references and workload percentage snapshot.

### Contracts

Since 1.97, the list supports employee-name substring search and numbered pages. Only client administrators can save a client-wide page size of 1–100 contracts (default 10), independent of the employee-list setting. The same size applies to search results. Page numbers appear once below the entire table and retain the search. Existing client isolation, self-service scope and archived/ended entries remain unchanged.

A contract links an active employee, job title, property location, optional department and optional manager from the same client. An employee cannot be their own manager. A start date is required; the end date may be blank, making the contract open-ended. Adding an end date later closes its validity period.

Each contract is either a **Primary workplace** or a **Temporary workplace**, and stores a workload choice plus a percentage snapshot. The contract form does not calculate monthly required hours. The implemented scheduling module calculates them for the primary contract's valid working days within the month as `workload percentage / 100 × working days × 8 hours`; for example, 50% × 20 days × 8 hours = 80 hours. Contract list locations display the full path from building to selected floor or room. The contract type cannot be changed after creation. Date ranges are inclusive, and one employee cannot have overlapping primary contracts. An open-ended primary contract covers all later dates until an end date is added. A temporary workplace must fit entirely within a primary contract period and use a different property location. Editing a primary contract revalidates its linked temporary workplaces. Different temporary assignments may overlap each other.

### Temporary assignment restrictions and archiving

An employee cannot have two temporary contracts on the same floor in the same calendar month, even if the day ranges do not overlap. Room locations resolve to their floor; locations without a floor use the location ID. The employee and contract type remain immutable when editing.

Only temporary contracts without any schedule entries can be archived with `archived_at`. Primary contracts and temporary contracts with entries cannot be archived. Restoring clears the timestamp while retaining the ID and data. Archived contracts are excluded from schedules. Monthly primary-workload overrides are stored separately in `employment_contract_monthly_workloads` without splitting the contract. Migrations 046/050 and installer repairs supply these additions.

### Data and safeguards

Records are stored in separate client-scoped `employment_job_titles`, `employment_departments`, `employment_workloads` and `employment_contracts` tables. Contract foreign keys keep the employee, manager, title, workload, department and property location within the same client. Primary overlap validation runs in a database transaction while locking the employee row. Creation and updates are audited. `sql/017_employment_module.sql` installs the core schema; `sql/018_employment_workloads.sql` adds workload choices and backfills existing contracts to 100%.