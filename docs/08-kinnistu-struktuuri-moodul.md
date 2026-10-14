# Kinnistustruktuuri moodul / Property structure module

## Eesti keeles

Versioon 1.90 lisab migratsiooniga `sql/077_property_type_translations.sql` andmebaasi aktiivsed en/et/ru tüübinimetused. Võti on `ui.` + ingliskeelse nimetuse SHA-256; olemasolevaid tõlkeid `INSERT IGNORE` ei kirjuta üle. Jagatud tõlkekataloog tõlgib loendi Type veeru ning lisamisvormi valikud, salvestatud `node_type` väärtused jäävad ingliskeelseks.

| node_type | English | Eesti | Русский |
| --- | --- | --- | --- |
| building | Building | Hoone | Здание |
| wing | Wing | Korpus | Корпус |
| floor | Floor | Korrus | Этаж |
| room | Room | Ruum | Помещение |

Alates versioonist 1.81 ei näe aktiivne Level F ilma aktiivse `client_admin` rollita **Property structure** ega **Active modules** menüülinke. Otse `/panel/property`, selle alamtegevuste või `/panel/modules` avamine annab 403. Moodul jääb kliendil aktiivseks: enda töölepingute asukohad ja lubatud graafikute prindivaade/PDF säilivad. Teiste rollide ning F+admin konto senine ligipääs ei muutu.

Süsteemiadministraator aktiveerib kliendile eraldiseisva **Property structure** mooduli kliendi detailvaates (`/admin/clients/view`). See ei sõltu organisatsiooni-, patsiendi- ega töötajamoodulist. Aktiveeritud moodul on kliendipaneelis aadressil `/panel/property`.

Kliendi kasutajad saavad struktuuri vaadata. Kliendiadministraator saab lisada hooneid ning olemasoleva asukoha juures nupuga **Add inside** selle allüksusi. Hoone alla saab lisada korpuse või kohe korruse, korpuse alla korruse ja korruse alla ruumi. Sama vanema all sama tüüpi korpusi, korruseid ja ruume saab **Up**/**Down** nuppudega käsitsi järjestada. Ruumi alla uusi tasemeid ei lisata. **Edit name** võimaldab kliendiadministraatoril muuta lisatud hoone, korpuse, korruse või ruumi nime. Tüüpi ja vanemat muuta ega kirjeid kustutada selles versioonis ei saa.

Andmed asuvad eraldi tabelis `property_nodes`; `client_id` piirab päringud kliendiga ja sama kliendi välisvõti keelab teise kliendi asukoha kasutamise vanemana. `sort_order` säilitab õdede-vendade järjestuse. Loomisel ja ümberjärjestamisel kontrollitakse serveris kliendi ning administraatori õigust ja CSRF-märgendit. Loomine, nime muutmine ja järjestamine lähevad auditlogisse. Tabeli ja mooduli lisab `sql/015_property_structure.sql`, järjestusvälja `sql/021_property_sibling_order.sql`.

## In English

Version 1.90 seeds the four type labels in English, Estonian and Russian through `sql/077_property_type_translations.sql` (see the table above). Existing custom values are preserved. Database-backed UI translation applies to the property list and creation options; stored `node_type` codes and permissions remain unchanged.

Since 1.81, active Level F users without active `client_admin` cannot see **Property structure** or **Active modules** sidebar links. Direct `/panel/property`, its subactions and `/panel/modules` return 403. The module stays active for contract locations and permitted schedule print/PDF access. Other roles and F+admin retain their existing access.

A system administrator activates the separate **Property structure** module for a client on `/admin/clients/view`. It does not depend on the organisation, patients or employees modules. Once activated, it appears at `/panel/property`.

Client users can view the hierarchy. A client administrator can add buildings and use **Add inside** on an existing location to create a child. A building can contain wings or floors directly, a wing can contain floors, and a floor can contain rooms. Wings, floors and rooms of the same type under one parent can be manually reordered with **Up**/**Down**. Rooms cannot contain further levels. **Edit name** lets a client administrator rename any building, wing, floor or room. Changing a location's type or parent, and deleting locations, are not available in this version.

Records are stored separately in `property_nodes`. Queries are scoped by `client_id`, and a same-client foreign key prevents a location belonging to another client from being used as a parent. `sort_order` persists sibling order. Creation and reordering validate client scope, administrator permissions and CSRF token on the server. Creation, renaming and reordering are audited. `sql/015_property_structure.sql` installs the table and module; `sql/021_property_sibling_order.sql` adds ordering.