# Töölepingute moodul / Employment module

**Süsteemi arendusversioon:** 1.31

## Eesti keeles

### Aktiveerimine ja ligipääs

Süsteemiadministraator aktiveerib kliendile eraldi **Employment** mooduli kliendi detailvaates (`/admin/clients/view`). Lepingute lisamiseks ja muutmiseks peab kliendile olema aktiveeritud ka **Property structure**, sest töökoht seotakse selle mooduli asukohaga. Kliendikasutajad saavad lepinguid vaadata; ametite, osakondade ja lepingute haldamine on kliendiadministraatori õigus.

### Ametid ja osakonnad

Mooduli vahelehtedel **Job titles**, **Departments** ja **Workloads** saab lisada ning muuta vastavaid valikuid. Koormuse valikul on nimi ja protsent vahemikus 0,01–100,00%. Kasutuses olevat kirjet ei kustutata: selle saab peita ning vajadusel uuesti aktiveerida. Peidetud kirjeid ei pakuta uutele lepingutele, kuid olemasolev leping säilitab oma viite ja koormuse protsendi koopia.

### Lepingud

Leping seob sama kliendi aktiivse töötaja, ametinimetuse, kinnistu asukoha, valikulise osakonna ja valikulise juhi. Töötaja ise juhiks määrata ei saa. Töölepingul on kohustuslik alguskuupäev; lõppkuupäeva võib tühjaks jätta, mis tähendab tähtajatut lepingut. Lepingu lõppkuupäeva hiljem lisamine sulgeb kehtivusperioodi.

Iga leping on kas **Primary workplace** või **Temporary workplace**. Lepingul on ka koormuse valik. Vorm arvutab kuu normtunnid valemiga `tööpäevad × päevatunnid × koormusprotsent`; näiteks 20 tööpäeva × 8 tundi × 50% = 80 tundi. Tööpäevade arv tuleb kalkulaatorisse sisestada iga kuu tegeliku normi järgi. Lepingu liiki pärast loomist muuta ei saa. Sama töötaja põhilepingute kuupäevavahemikud on kaasavad ning ei tohi kattuda. Tähtajatu põhileping katab kõik järgnevad kuupäevad kuni sellele lõppkuupäeva lisamiseni. Ajutine töökoht peab täielikult mahtuma põhilepingu perioodi ja kasutama põhikohast erinevat kinnistu asukohta. Põhilepingu muutmisel kontrollitakse, et seotud ajutised töökohad jääksid endiselt kehtivaks. Erinevate ajutiste töökohtade perioodid võivad omavahel kattuda.

### Andmed ja turvakontrollid

Andmed asuvad kliendipõhistes tabelites `employment_job_titles`, `employment_departments`, `employment_workloads` ja `employment_contracts`. Lepingute võõrvõtmed piiravad töötaja, juhi, ameti, koormuse, osakonna ja kinnistuüksuse samale kliendile. Põhilepingu kattuvuskontroll toimub andmebaasitransaktsioonis töötaja rea lukustamisega. Loomine ja muutmine logitakse auditlogisse. Põhiskeemi lisab `sql/017_employment_module.sql`; koormuste tabeli ja olemasolevate lepingute 100% tagasitäitmise lisab `sql/018_employment_workloads.sql`.

## In English

### Activation and access

A system administrator activates the separate **Employment** module for a client from `/admin/clients/view`. **Property structure** must also be active for contract creation and editing because each workplace is linked to a location in that module. Client users may view contracts; managing job titles, departments and contracts is restricted to the client administrator.

### Job titles and departments

The **Job titles**, **Departments** and **Workloads** tabs allow administrators to add and edit choices. A workload has a name and a percentage from 0.01% to 100.00%. Referenced entries are retained rather than deleted: they can be hidden and reactivated later. Hidden entries are not offered for new contracts, while existing contracts retain their references and workload percentage snapshot.

### Contracts

A contract links an active employee, job title, property location, optional department and optional manager from the same client. An employee cannot be their own manager. A start date is required; the end date may be blank, making the contract open-ended. Adding an end date later closes its validity period.

Each contract is either a **Primary workplace** or a **Temporary workplace**, and has a workload choice. The form estimates monthly required hours as `working days × required hours per workday × workload percentage`; for example, 20 days × 8 hours × 50% = 80 hours. Enter the actual monthly working-day norm in the calculator. The contract type cannot be changed after creation. Date ranges are inclusive, and one employee cannot have overlapping primary contracts. An open-ended primary contract covers all later dates until an end date is added. A temporary workplace must fit entirely within a primary contract period and use a different property location. Editing a primary contract revalidates its linked temporary workplaces. Different temporary assignments may overlap each other.

### Data and safeguards

Records are stored in separate client-scoped `employment_job_titles`, `employment_departments`, `employment_workloads` and `employment_contracts` tables. Contract foreign keys keep the employee, manager, title, workload, department and property location within the same client. Primary overlap validation runs in a database transaction while locking the employee row. Creation and updates are audited. `sql/017_employment_module.sql` installs the core schema; `sql/018_employment_workloads.sql` adds workload choices and backfills existing contracts to 100%.