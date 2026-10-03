# DemoIT CRM

Kliendipõhine moodulitega CRM, mis kasutab PHP-d, PDO-d ja MySQL-i. Projekt sisaldab peaadministraatori keskkonda, kliendipaneeli ning eraldi aktiveeritavaid patsiendi-, organisatsiooni-, töötajate, kinnistustruktuuri ja töölepingute mooduleid.

A client-based modular CRM built with PHP, PDO and MySQL. It includes a system administrator workspace, a client panel, and independently activated patients, organisation, employees, property structure and employment modules.

Kinnistustruktuuri eraldi mooduli kasutus: [juhend](docs/08-kinnistu-struktuuri-moodul.md).
Property structure module: [usage guide](docs/08-kinnistu-struktuuri-moodul.md).
Töölepingute moodul: [kasutusjuhend](docs/09-toolepingute-moodul.md).
Employment module: [usage guide](docs/09-toolepingute-moodul.md).

## Versioonide ajalugu / Version history

### Eesti keeles

| Versioon | Kuupäev | Olulisemad muudatused |
| --- | --- | --- |
| 1.44 | 03.10.2026 | Vahetuse- ja erandimallidele saab määrata graafikus kuvatava värvi. |
| 1.43 | 03.10.2026 | Vahetuse või erandi valik salvestub automaatselt. |
| 1.42 | 03.10.2026 | Päevanupud avavad vahetuse või erandi valimiseks modaali. |
| 1.41 | 03.10.2026 | Graafiku auditveergude parandav migratsioon töötab hosti MySQL-iga ühilduvalt. |
| 1.40 | 03.10.2026 | Parandatud vanade ajakavatabelite puuduvaid auditveerge lisav migratsioon. |
| 1.39 | 03.10.2026 | Kliendiadministraator näeb salvestusvea SQLSTATE'i ja andmebaasi veanumbrit. |
| 1.38 | 03.10.2026 | Kuugraafiku salvestuse vea täpsem serveripoolne logimine. |
| 1.37 | 03.10.2026 | Välditud tarbetud kustutuspäringud tühjade graafikulahtrite salvestamisel. |
| 1.36 | 03.10.2026 | Eraldi kuugraafiku moodul manageri vahetuste ja erandite haldamiseks. |
| 1.35 | 03.10.2026 | Töötajate loendis näidatakse tänase seisuga aktiivset põhilepingut või ajutist töökohta. |
| 1.34 | 03.10.2026 | Kinnistu struktuuri korpusi, korruseid ja ruume saab käsitsi järjestada. |
| 1.33 | 03.10.2026 | Lepingute nimekirja kinnistu asukoht kuvab nüüd kogu hoone-korpuse-korruse-ruumi tee. |
| 1.32 | 03.10.2026 | Koormus jääb lepingule, kalkulaator liigub graafikumoodulisse ja asukohavalik näitab tervet nimeahelat. |
| 1.31 | 03.10.2026 | Lepingute muudetavad koormuse valikud ja kuupõhine normtundide arvutus. |
| 1.30 | 01.10.2026 | Ametite, osakondade ja tähtajatu/tähtajalise põhi- ning ajutise töökohaga lepingute moodul. |
| 1.29 | 01.10.2026 | Kliendipaneeli moodulilingid koondatud ühe „Modules” pealkirja alla. |
| 1.28 | 01.10.2026 | Eraldi kinnistustruktuuri moodul: hooned, korpused, korrused, ruumid ja nende nimede muutmine. |
| 1.27 | 30.09.2026 | Kõigi versioonide täielik ingliskeelne tõlge lisatud kakskeelsesse muudatuste logisse. |
| 1.26 | 30.09.2026 | Töötaja loomise vormist eemaldatud organisatsiooniüksuse valik. |
| 1.25 | 30.09.2026 | Töötaja muutmisvormist eemaldatud organisatsiooniüksuse valik; salvestamisel säilib varasem seos. |
| 1.24 | 30.09.2026 | Sisselogitud kasutaja nime näidatakse kasutajatunnuse asemel päises ja töölaudadel. |
| 1.23 | 30.09.2026 | Kakskeelne GitHubi README versiooniülevaade ja versioonilogi registrikanne. |
| 1.22 | 30.09.2026 | Töötajate mooduli kasutus- ja AI arendusjuhend ning dokumentatsioonipaketi migratsioon. |
| 1.21 | 30.09.2026 | Aktiveeritav töötajate moodul, ettevõttepõhine provisioneerimine ja mooduli seaded. |
| 1.20 | 30.09.2026 | Vana SQL-dumpi töötajate import koos failikontrolli ja auditlogiga. |
| 1.19 | 30.09.2026 | Töötajate andmed ja muutmisvaade, konto aktiveerimine ning ajutised paroolid. |
| 1.18 | 20.09.2026 | Organisatsioon ja asendajad aktiveeritava moodulina koos marsruutide ligipääsukontrolliga. |
| 1.17 | 20.09.2026 | Organisatsiooni ja asendajate lingid peideti kliendipaneelis kuni mooduliks muutmiseni. |
| 1.16 | 20.09.2026 | Paroolide lähtestamine, oma parooli muutmine ja auditlogi. |
| 1.15 | 20.09.2026 | Rollide kuvatavate nimede haldus peaadministraatori keskkonnas. |
| 1.14 | 20.09.2026 | Parandatud peaadministraatori marsruutide 500 viga. |
| 1.13 | 20.09.2026 | Patsiendimoodul ning kliendi aktiivsetest moodulitest sõltuv menüü. |
| 1.12 | 20.09.2026 | Teavitused, raportid, tugi ja olemasolevate paigalduste automigratsioonid. |
| 1.11 | 20.09.2026 | Süsteemi seaded, kliendi andmete haldus ja kontaktandmete muutmine. |
| 1.10 | 20.09.2026 | Administraatori auditlogi vaade ja kinnitatud paigaldusvoog. |
| 1.9 | 20.09.2026 | Juurkataloogi marsruutimine, automaatne SQL-skeemi paigaldus ja 500 vea vaade. |
| 1.8 | 20.09.2026 | Organisatsiooniüksused ja ajutiste asendajate taotlused. |
| 1.7 | 20.09.2026 | Kliendi kasutajate loomine ja viimase aktiivse administraatori kaitse. |
| 1.6 | 20.09.2026 | Kliendipaneel ning kliendipõhine moodulite aktiveerimine. |
| 1.5 | 20.09.2026 | Moodulite, keelte ja tõlgete haldus. |
| 1.4 | 20.09.2026 | Peaadministraatori keskkond ja kliendi loomine. |
| 1.3 | 20.09.2026 | Esmane andmebaas, PHP rakendus, sisselogimine ja algseadistus. |
| 1.2 | 20.09.2026 | Laraveli asemel vanilla PHP ja PDO arhitektuur. |
| 1.1 | 20.09.2026 | Projekti nõuete, andmebaasi, rollide, arhitektuuri ja AI arendusjuhendi esmane dokumentatsioon. |

Kõigi versioonide muudatused, mõjutatud moodulid, andmebaasimuudatused ja testimismärkused on kirjas [täielikus muudatuste logis](CHANGELOG.md).

### In English

| Version | Date | Highlights |
| --- | --- | --- |
| 1.44 | 03.10.2026 | Shift and exception templates have configurable schedule colors. |
| 1.43 | 03.10.2026 | Shift and exception selections autosave automatically. |
| 1.42 | 03.10.2026 | Day buttons open a modal for shift or exception selection. |
| 1.41 | 03.10.2026 | Made the schedule audit-column repair compatible with the host MySQL server. |
| 1.40 | 03.10.2026 | Repair migration adds missing audit columns to existing schedule tables. |
| 1.39 | 03.10.2026 | Client administrators see safe SQLSTATE and database error diagnostics. |
| 1.38 | 03.10.2026 | Server-side diagnostics for monthly schedule save failures. |
| 1.37 | 03.10.2026 | Avoided unnecessary deletes for empty schedule cells when saving a roster. |
| 1.36 | 03.10.2026 | Separate monthly schedule module for manager-managed shifts and exceptions. |
| 1.35 | 03.10.2026 | Employee list shows currently valid primary and temporary contracts. |
| 1.34 | 03.10.2026 | Manually reorder property wings, floors and rooms within their parent. |
| 1.33 | 03.10.2026 | Contract list locations now show the full building-wing-floor-room path. |
| 1.32 | 03.10.2026 | Kept workload on contracts, deferred hours calculation to scheduling and clarified property paths. |
| 1.31 | 03.10.2026 | Editable contract workload options and monthly required-hours calculation. |
| 1.30 | 01.10.2026 | Employment module for job titles, departments, and open-ended primary and temporary workplace contracts. |
| 1.29 | 01.10.2026 | Grouped client panel module links under a single “Modules” heading. |
| 1.28 | 01.10.2026 | Independent property structure module for buildings, wings, floors, rooms and renaming. |
| 1.27 | 30.09.2026 | Full English translation of all releases added to the bilingual changelog. |
| 1.26 | 30.09.2026 | Removed the organisation unit selector from the Add employee form. |
| 1.25 | 30.09.2026 | Removed the organisation unit selector from employee editing while retaining existing assignments. |
| 1.24 | 30.09.2026 | Signed-in person's name replaces the username in the header and dashboards. |
| 1.23 | 30.09.2026 | Bilingual GitHub README version overview and version log registration. |
| 1.22 | 30.09.2026 | Employees module user and AI development guides, plus documentation migration. |
| 1.21 | 30.09.2026 | Optional employees module, per-client provisioning and module settings. |
| 1.20 | 30.09.2026 | Legacy SQL dump employee import with file validation and audit logging. |
| 1.19 | 30.09.2026 | Employee records and editing, account activation and temporary passwords. |
| 1.18 | 20.09.2026 | Organisation and substitutes as an optional module with route access control. |
| 1.17 | 20.09.2026 | Organisation and substitutes links hidden pending module activation support. |
| 1.16 | 20.09.2026 | Password resets, self-service password changes and audit logging. |
| 1.15 | 20.09.2026 | System administrator management of display names for roles. |
| 1.14 | 20.09.2026 | Fixed a 500 error affecting system administrator routes. |
| 1.13 | 20.09.2026 | Patients module and a menu driven by the client's active modules. |
| 1.12 | 20.09.2026 | Notifications, reports, support and automatic migrations for existing installs. |
| 1.11 | 20.09.2026 | System settings and client contact detail management. |
| 1.10 | 20.09.2026 | System administrator audit log view and verified installation flow. |
| 1.9 | 20.09.2026 | Root routing, automatic SQL schema installation and a 500 error page. |
| 1.8 | 20.09.2026 | Organisation units and temporary substitute requests. |
| 1.7 | 20.09.2026 | Client user creation and protection of the last active administrator. |
| 1.6 | 20.09.2026 | Client panel and per-client module activation. |
| 1.5 | 20.09.2026 | Module, language and translation management. |
| 1.4 | 20.09.2026 | System administrator workspace and client creation. |
| 1.3 | 20.09.2026 | Initial database, PHP application, login and setup. |
| 1.2 | 20.09.2026 | Switched from Laravel to plain PHP and PDO. |
| 1.1 | 20.09.2026 | Initial requirements, database, roles, architecture and AI development documentation. |

For detailed release notes, affected modules, database changes and test results, see the [full bilingual changelog](CHANGELOG.md).