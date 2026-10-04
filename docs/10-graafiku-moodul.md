# Töögraafiku moodul / Work schedule module

**Süsteemi arendusversioon:** 1.71

## Eesti keeles

### Aktiveerimine ja ligipääs

Süsteemiadministraator aktiveerib kliendile **Schedule** mooduli. Graafik vajab samal kliendil ka **Employment** ja **Property structure** moodulite aktiveerimist. Menüülink asub kliendipaneeli **Modules** all.

Kliendiadministraator näeb kõiki kliendi aktiivsete põhilepingutega töötajaid ja saab nende graafikuid hallata. Manager näeb ja muudab töötajaid, kelle kehtival põhilepingul on tema määratud juhiks. Graafik koondab töötajad kinnistu hoone/korpuse/korruse ning lepingu osakonna järgi. Töötaja ruumiüksust graafikus ei kuvata.

### Kuugraafik

Vali kuu; iga kehtiv põhi- või ajutine leping kuvatakse oma asukoha/osakonna all eraldi real ja kuu päevad eraldi veergudena. Kui leping kehtib ainult osa kuust, on valitavad ainult lepinguperioodi päevad. Ühel töötajal võib samal päeval olla mitu lepingurida ainult siis, kui vahetuste ajavahemikud ei kattu. Tühi **Off** valik tähendab vaba päeva.

Päevaseks valikuks saab määrata vahetuse või erandi. Malli algusaeg ja kestus määravad tööaja; vahetus võib kesta kuni 24 tundi ja jätkuda järgmise päeva hommikusse. Näiteks `12Ö` algab 20:00 ja kestab 12 tundi; `24H` algab 08:00 ja kestab 24 tundi. Shift-tunnid lähevad Planned-summasse kõigil päevadel; Exception-tunnid lähevad sinna ainult esmaspäevast reedeni.

`Block`-mall blokeerib sama töötaja kattuvad vahetuse ja exception’i ajavahemikud, kuid Block-tunde Planned-summasse ei arvestata. Kogu päeva blokeerimiseks võib näiteks luua `EST`-malli algusega 00:00 ja kestusega 24 tundi. Täpselt kõrvuti lõppev/alustav vahemik on lubatud.

### Printimine

Vahelehel **Print schedule** vali kuu ja märgi prinditavad asukoha/osakonna grupid. Kõik grupid on vaikimisi valitud; **Select all** ja **Clear selection** muudavad valikut. Nupp **Print / Save as PDF** avab brauseri printimisakna. Iga valitud grupp algab eraldi landscape-lehelt; suurem grupp võib jätkuda järgmisel lehel. Printimisel peidetakse CRM-i päis, menüüd, graafiku vahelehed, jalus ja valikunupud; iga leht kuvab paremas ülanurgas tänase kuupäeva. Tundide veergudest kuvatakse ainult **Sum h**; **Min h**, **OT** ja **Tri-OT** jäetakse välja. Prindivaates säilivad vahetusmallide värvid ning põhilepingu **Sum h** veerus kuvatakse ka ajutiste töötundide jaotus.

### Vahetuste seaded ja tundide kokkuvõte

Kliendiadministraator haldab vahelehel **Shift settings** vahetuste ja erandite koode, algusaegu ning kestusi. Vaikimisi vahetused on `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) ja `8P` (08:00, 8h). Vaikimisi erandid on `HP`, `K`, `LHP`, `LIP`, `P`, `TV` ja `X` (08:00, 8h). Kasutuses olevat malli ei kustutata: selle saab peita; olemasolevad graafikukirjed säilivad.

Graafiku lõpus kuvatakse Planned, Required, OT ja neljakuuline Tri-OT. Ajutise lepingu real on ainult selle asukoha Planned-tunnid; põhilepingu real liidetakse põhi- ja ajutise koha tunnid, kuid Required arvutatakse ainult põhilepingu workload’i põhjal. Kuu OT = Planned − Required. Tri-OT kumuleerub perioodidel jaanuar–aprill, mai–august ja september–detsember. Kuu piiri ületava vahetuse tunnid jagatakse kuude vahel; neljakuulise perioodi viimases kuus jääb vahetus tervikuna perioodi viimasesse kuusse. Required norm on põhilepingu koormus × kuu tööpäevad (E–R) × 8 tundi; riigipühi maha ei arvata.

Põhilepingu koormus kehtib vaikimisi lepingu kogu perioodil. Kui koormus muutub ajutiselt, vali graafikurea töötajanime all vastava kuu workload. Kuu erand muudab ainult selle kuu Required-, OT- ja Tri-OT arvestust; lepingut ega olemasolevaid päevakandeid ei poolitata. Jäta valik lepingu vaikeväärtusele, et kuu erand eemaldada.

Andmed on kliendipõhistes tabelites `employee_schedule_templates` ja `employee_schedule_entries`; kuukoormuse erandid salvestatakse tabelis `employment_contract_monthly_workloads`. Leping, töötaja, vahetuse mall ja muutja valideeritakse sama kliendi piires ning muutmistehing nõuab CSRF-kaitset. Iga salvestus läheb auditlogisse. Skeemi paigaldavad SQL-migratsioonid `sql/023_employee_scheduling.sql` ja `sql/050_monthly_workload_overrides.sql`.

## In English

### Activation and access

A system administrator activates the **Schedule** module for a client. The same client must also have **Employment** and **Property structure** active. The link appears under **Modules** in the client panel.

A client administrator can see and manage all employees with active primary contracts. A manager can see and edit employees whose valid primary contract names that user as manager. The roster groups employees by property building/wing/floor and contract department. Room-level units are not shown in this version.

### Monthly roster

Choose a month; each valid primary or temporary contract appears under its own location/department row, with each date in its own column. If the contract is valid for only part of the month, only dates inside that contract period are editable. An employee may have multiple contract rows on one date only when shift intervals do not overlap. An empty **Off** choice means a day off.

Each day can have a shift or an exception. A template's start time and duration determine its span; shifts may last up to 24 hours and continue into the next morning. For example, `12Ö` starts at 20:00 and lasts 12 hours; `24H` starts at 08:00 and lasts 24 hours. Shift hours count as Planned on every day; exception hours count only Monday-Friday.

A `Block` template prevents overlapping shift and exception intervals for the same employee, but Block hours are excluded from Planned. To block a full day, create a template such as `EST` starting at 00:00 for 24 hours. Exact end-to-start handoffs remain allowed.

### Printing

On **Print schedule**, choose a month and select the location/department groups to print. All groups are selected by default; **Select all** and **Clear selection** change the selection. **Print / Save as PDF** opens the browser print dialog. Each selected group starts on a separate landscape page; a large group may continue onto additional pages. Printing hides the CRM header, navigation, schedule tabs, footer and selection controls; each page shows today's date at the top right. Only **Sum h** is shown among the totals; **Min h**, **OT** and **Tri-OT** are omitted. The print view preserves shift-template colors and shows the temporary-hours breakdown under primary-contract **Sum h** totals.

### Shift settings and hour summary

A client administrator manages shift and exception codes, start times and durations on **Shift settings**. Default shifts are `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) and `8P` (08:00, 8h). Default exceptions are `HP`, `K`, `LHP`, `LIP`, `P`, `TV` and `X` (08:00, 8h). Referenced templates are retained and can be hidden; existing schedule entries remain intact.

The roster ends with Planned, Required, OT and four-month Tri-OT totals. A temporary row shows only its location's Planned hours; the primary row combines primary and temporary planned hours, while Required uses only primary-contract workload. Monthly OT = Planned − Required. Tri-OT accumulates over January-April, May-August and September-December. Shift minutes crossing a month boundary are split between months, except in the final month of a four-month period, where the full shift stays in that period. Required hours use primary-contract workload × working weekdays × 8 hours; imported national and public holidays (`kind_id` 1 and 2) are excluded. Weekday exceptions on those holidays are excluded from Planned, matching weekend behavior.

Client administrators can upload an XML holiday list from the dedicated **Public holidays** schedule tab, which also lists every imported date, title, kind and note. The import adds only entries not already stored; existing records are never overwritten. National observances and shortened workdays (`kind_id` 3 and 4) are retained as calendar data but do not turn a weekday into a non-working day.

Primary contract workload applies by default throughout the contract. For a temporary monthly change, choose the workload under the employee name in that month's schedule. The override affects only that month's Required, OT and Tri-OT; the contract and existing daily entries are not split. Select the contract default to remove the monthly override.

Data is stored in the client-scoped `employee_schedule_templates` and `employee_schedule_entries` tables; monthly workload overrides are stored in `employment_contract_monthly_workloads`. Contract, employee, shift template and editor are validated within the same client; writes require CSRF protection and are audited. The schema is installed by `sql/023_employee_scheduling.sql` and `sql/050_monthly_workload_overrides.sql`.
