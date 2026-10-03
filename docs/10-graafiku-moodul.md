# Töögraafiku moodul / Work schedule module

**Süsteemi arendusversioon:** 1.36

## Eesti keeles

### Aktiveerimine ja ligipääs

Süsteemiadministraator aktiveerib kliendile **Schedule** mooduli. Graafik vajab samal kliendil ka **Employment** ja **Property structure** moodulite aktiveerimist. Menüülink asub kliendipaneeli **Modules** all.

Kliendiadministraator näeb kõiki kliendi aktiivsete põhilepingutega töötajaid ja saab nende graafikuid hallata. Manager näeb ja muudab töötajaid, kelle kehtival põhilepingul on tema määratud juhiks. Graafik koondab töötajad kinnistu hoone/korpuse/korruse ning lepingu osakonna järgi. Töötaja ruumiüksust graafikus ei kuvata.

### Kuugraafik

Vali kuu; iga aktiivne põhilepinguga töötaja kuvatakse reana ja kuu päevad eraldi veergudena. Kui töötaja leping kehtib ainult osa kuust, on valitavad ainult lepinguperioodi päevad. Ühe töötaja ja päeva kohta saab salvestada ühe kirje. Tühi **Off** valik tähendab vaba päeva.

Päevaseks valikuks saab määrata vahetuse või erandi. Malli algusaeg ja kestus määravad tööaja; vahetus võib kesta kuni 24 tundi ja jätkuda järgmise päeva hommikusse. Näiteks `12Ö` algab 20:00 ja kestab 12 tundi; `24H` algab 08:00 ja kestab 24 tundi. Erandid kuvatakse eraldi ning nende kestust ei liideta planeeritud töötundide hulka.

### Vahetuste seaded ja tundide kokkuvõte

Kliendiadministraator haldab vahelehel **Shift settings** vahetuste ja erandite koode, algusaegu ning kestusi. Vaikimisi vahetused on `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) ja `8P` (08:00, 8h). Vaikimisi erandid on `HP`, `K`, `LHP`, `LIP`, `P`, `TV` ja `X` (08:00, 8h). Kasutuses olevat malli ei kustutata: selle saab peita; olemasolevad graafikukirjed säilivad.

Graafik näitab töötaja lepingu koormust, kuu normtunde ja planeeritud vahetusetunde. Esimese versiooni norm on lepingu koormus × kuu esmaspäevast reedeni tööpäevade arv × 8 tundi. Näiteks 50% × 20 tööpäeva × 8 tundi = 80 normtundi. Riigipühade automaatset mahaarvamist selles versioonis ei ole. Osalise lepinguperioodi norm arvutatakse ainult selle perioodi tööpäevadest.

Andmed on kliendipõhistes tabelites `employee_schedule_templates` ja `employee_schedule_entries`. Leping, töötaja, vahetuse mall ja muutja valideeritakse sama kliendi piires ning muutmistehing nõuab CSRF-kaitset. Iga salvestus läheb auditlogisse. Skeem ja vaikimisi mallid paigaldab `sql/023_employee_scheduling.sql`.

## In English

### Activation and access

A system administrator activates the **Schedule** module for a client. The same client must also have **Employment** and **Property structure** active. The link appears under **Modules** in the client panel.

A client administrator can see and manage all employees with active primary contracts. A manager can see and edit employees whose valid primary contract names that user as manager. The roster groups employees by property building/wing/floor and contract department. Room-level units are not shown in this version.

### Monthly roster

Choose a month; each employee with an active primary contract appears as a row, with each date in its own column. If the contract is valid for only part of the month, only dates inside that contract period are editable. One entry can be saved per employee per day. An empty **Off** choice means a day off.

Each day can have a shift or an exception. A template's start time and duration determine its span; shifts may last up to 24 hours and continue into the next morning. For example, `12Ö` starts at 20:00 and lasts 12 hours; `24H` starts at 08:00 and lasts 24 hours. Exceptions are listed separately and are not counted as planned working hours.

### Shift settings and hour summary

A client administrator manages shift and exception codes, start times and durations on **Shift settings**. Default shifts are `12Ö` (20:00, 12h), `12P` (08:00, 12h), `24H` (08:00, 24h) and `8P` (08:00, 8h). Default exceptions are `HP`, `K`, `LHP`, `LIP`, `P`, `TV` and `X` (08:00, 8h). Referenced templates are retained and can be hidden; existing schedule entries remain intact.

The roster shows contract workload, required monthly hours and planned shift hours. The initial norm is contract workload × Monday-to-Friday workdays in the month × 8 hours. For example, 50% × 20 workdays × 8 hours = 80 required hours. Public holidays are not automatically deducted in this version. For contracts covering only part of a month, the norm uses only weekdays within that contract period.

Data is stored in the client-scoped `employee_schedule_templates` and `employee_schedule_entries` tables. Contract, employee, shift template and editor are validated within the same client; writes require CSRF protection and are audited. `sql/023_employee_scheduling.sql` installs the schema and default templates.
