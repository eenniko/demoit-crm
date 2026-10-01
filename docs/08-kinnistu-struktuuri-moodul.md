# Kinnistustruktuuri moodul / Property structure module

## Eesti keeles

Süsteemiadministraator aktiveerib kliendile eraldiseisva **Property structure** mooduli kliendi detailvaates (`/admin/clients/view`). See ei sõltu organisatsiooni-, patsiendi- ega töötajamoodulist. Aktiveeritud moodul on kliendipaneelis aadressil `/panel/property`.

Kliendi kasutajad saavad struktuuri vaadata. Kliendiadministraator saab lisada hooneid ning olemasoleva asukoha juures nupuga **Add inside** selle allüksusi. Hoone alla saab lisada korpuse või kohe korruse, korpuse alla korruse ja korruse alla ruumi. Ruumi alla uusi tasemeid ei lisata. **Edit name** võimaldab kliendiadministraatoril muuta lisatud hoone, korpuse, korruse või ruumi nime. Tüüpi ja vanemat muuta ega kirjeid kustutada selles versioonis ei saa.

Andmed asuvad eraldi tabelis `property_nodes`; `client_id` piirab päringud kliendiga ja sama kliendi välisvõti keelab teise kliendi asukoha kasutamise vanemana. Loomisel kontrollitakse serveris lubatud vanema ja lapse tüüpi, administraatori õigust ning CSRF-märgendit. Nime muutmine uuendab vaid sama kliendi kirje nime. Loomine ja nime muutmine lähevad auditlogisse. Tabel ja mooduli kataloogikirje paigaldatakse migratsiooniga `sql/015_property_structure.sql`.

## In English

A system administrator activates the separate **Property structure** module for a client on `/admin/clients/view`. It does not depend on the organisation, patients or employees modules. Once activated, it appears at `/panel/property`.

Client users can view the hierarchy. A client administrator can add buildings and use **Add inside** on an existing location to create a child. A building can contain wings or floors directly, a wing can contain floors, and a floor can contain rooms. Rooms cannot contain further levels. **Edit name** lets a client administrator rename any building, wing, floor or room. Changing a location's type or parent, and deleting locations, are not available in this version.

Records are stored separately in `property_nodes`. Queries are scoped by `client_id`, and a same-client foreign key prevents a location belonging to another client from being used as a parent. Creation validates the allowed parent/child types, administrator permissions and CSRF token on the server. Renaming only changes a location's name within the same client. Both operations write audit entries. The table and module catalogue entry are installed by `sql/015_property_structure.sql`.