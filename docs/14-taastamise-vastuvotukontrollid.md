# Taastamise vastuvõtukontrollid

**Sihtversioon: 1.76.** See on testimise spetsifikatsioon, mitte väide, et kõik testid on jooksutatud. Testi eraldatud keskkonnas sünteetiliste isikuandmetega.

## Testandmed ja tulemuste registreerimine

Loo kaks tavaklienti A/B, mõlemale aktiivne `client_admin`, tavakasutaja ja juht, eri ID-dega asukohad, ametid, osakonnad, koormused, mallid ja patsiendid. A töötaja põhileping on 2026-01-01 kuni 2026-12-31, 50%, juht M1; ajutine töökoht teisel korrusel. Ära kasuta tootmis-DB-d.

Iga katse kohta säilita keskkond/versioon, eeltingimus, sisend, HTTP staatus/vastuse kuju, oodatud ja tegelik DB/UI tulemus ning PASS/FAIL. Kirjuta kõrvalekalded teostuse ja sihtnõude vahel eraldi; nõuetes mainitud teostamata võime ei ole automaatselt olemasoleva versiooni regressioon.

## 1. Paigaldus ja põhifunktsioonid

| ID | Katse | Oodatud tulemus |
|---|---|---|
| I01 | Puhas DB + kõik migratsioonid | Kõik inventuuri tabelid/indeksid, versioon 1.76 ja lõpetamata seadistus |
| I02 | Sama koodiga teine HTTP päring | Ei teki korduvaid seemneid, uut skeemi ega uut kasutajat |
| I03 | Ainult dokumendi 15 SQL uude DB-sse | Samaväärne skeem, sh archive/color/audit/index parandused ja tõlked |
| I04 | Kehtetu setup ja siis korrektne | Vigase korral kasutajat/lukustust pole; korrektne üks aktiivne admin kliendil 13666 |
| I05 | `/setup` pärast lõpetamist | Tagasisuunamine `/`; lukustus säilib |
| I06 | `.env*`, config/src/sql/views/docs/.git/.vscode HTTP ligipääs | Saladusi ega sisefaile ei serveerita; piirangud toimivad hostis |
| I07 | Avaleht ja `/assets/css/app.css` | HTML/CSS laadivad juurpaigaldusega, CDN Bootstrap töötab |
| A01 | Õige/vale kliendikood, kasutajanimi, parool | Edu või üldine login-viga; kliendi A konto ei logi B alla |
| A02 | Mitteaktiivne konto/klient | Sisselogimine keelatud ja vastav audit |
| A03 | 5 valet katset samas sessioonis, 6. katse | 300 s piirang; erinev sessioon ei ole dokumenteeritud globaalse piirangu test |
| A04 | Sessiooni ID enne/pärast login/logout | ID muutub; logout eemaldab kliendikonteksti |
| A05 | Paroolivahetus | Praeguse vale parooli ja erineva kinnituse korral viga; edu korral uus räsi ja must_change_password=0 |
| A06 | Ajutise parooliga login | Tunnus salvestub; automaatset kogu süsteemi sundväravat praegu ei eeldata |
| A07 | POST ilma/võltsitud CSRF-ga | Muutust ei tehta; konkreetne vastus vastab handler'ile, mitte üldisele oletatud staatusele |
| A08 | Tundmatu URL / puuduva rolliga admin URL | 404 / 403; sisselogimata kaitstud URL suunab login'i |

## 2. Kliendid, moodulid ja töötajad

| ID | Katse | Oodatud tulemus |
|---|---|---|
| C01 | Klient koodiga 13666 või korduva koodiga | Loomine keelatud, kasutaja/rolli poolikut tulemust pole |
| C02 | Tavaline kliendi loomine | Klient + aktiivne client_admin ühe transaktsiooniga; parool ainult räsina DB-s |
| C03 | Kliendi detaili muutmine admini/tavakasutajana | Ainult client_admin saab paneelis muuta lubatud välju |
| C04 | Level F või viewer kliendihalduse GET/POST, versioon 1.78 | GET: nähtavad andmed, 0 vormi/sisestusvälja/salvestusnuppu; POST: 403, 0 teenuse kirjutust |
| M01 | Moodul aktiivne kataloogis, aga kliendil puudub | Menüü puudub ja otsene mooduli route annab 403 |
| M02 | Aktiveerimine ja uuesti aktiveerimine | Kliendiseos + ready provisioon; employees vaikimisi roll level_f, kordusteta |
| M03 | Provisioneerimise kontrollitud tõrge | Aktivatsioon rollback; ei jää aktiivset poolikut seadistust |
| M04 | Kataloogi või kliendi mooduli sulgemine | Ligipääs kaob, olemasolevad andmed säilivad |
| M05 | Schedule ilma employment/property moodulita | Otsene ligipääs keelatud, sõltuvus ei teki automaatselt |
| M06 | Klient loodud pärast vaikemallide migratsiooni | Ei eeldata automaatseid malle ega 100% koormust; UI-s lisamine töötab |
| U01 | Töötaja loomine, e-post vigane/kood korduv | Viga; korrektne konto inactive + provisioneeritud roll |
| U02 | Muutmine | Username säilib, lubatud kontaktid/roll muutuvad, vana org seos säilib |
| U03 | Viimase aktiivse admini deaktiveerimine/rolli eemaldamine | Mõlemad keelatud; teise aktiivse admini olemasolul lubatud |
| U04 | Aktiveerimine, reset ja mailitranspordi tõrge | Uus ajutine räsi, paroolivahetuse tunnus; tõrge nähtav, parool ei ilmu auditisse |
| U05 | SQL-import 5 MiB piiri ümber või vale laiend | Piiri ületus/vale fail lükatakse tagasi; sobiv fail läbib parseri |
| U06 | `db_user_data` kaks korrektset + duplikaat + tühi nimi | found=4, imported=2, skipped=2; uued inactive, vana parool ei imporditu |
| U07 | Dump sisaldab DROP/DELETE või muud tabelit | Üleslaaditud SQL-i ei täideta; ainult lubatud INSERT-ridadest saab töötaja |

## 3. Isolatsioon ja tugifunktsioonid

Muuda kliendi A sessioonis URL-i/POST-i ID-d B objekti ID-ks iga mooduli loomise/muutmise/oleku toimingus. Edukaks isolatsiooniks peab B andmestik jääma muutumatuks ja selle sisu ei tohi tagastada. Vanemate süsteemitabelite üksik FK ei tõenda sama klienti.

| ID | Katse | Oodatud tulemus |
|---|---|---|
| T01 | Kõigi kliendiloendite A/B võrdlus | Ainult vastava kliendi kontod/patsiendid/lepingud/kanded/piletid |
| T02 | Foreign klient employee/property/template/workload/manager ID | Loomine/muutmine keelatud; sama kliendi kontroll teenuses või FK-s |
| T03 | Organisatsiooni vanem/juht ja teavituse adressaat teisest kliendist | Nõutud eraldus kontrollitakse; leitud puudus registreeritakse, mitte ei nimetata olemasolevaks garantiiks |
| P01 | Patsiendi lisamine tavakasutajana + oleku lülitus | Aktiivse patients mooduli korral lubatud; staatus muutub, andmed säilivad |
| O01 | Organisatsiooni loend/loomine | Tasand A–F ja nimi; lisab ainult client_admin |
| O02 | Asendaja samast üksusest, otsesest alamüksusest, vanema juhiks | Ainult teenuse kandidaatide hulgas aktiivsed sama kliendi kontod |
| O03 | Pending kinnitamine/tagasilükkamine ja korduv otsus | Approved/rejected ja kinnitaja; teist otsust ei rakendata |
| O04 | Approved perioodi algus/lõpp | Ei eeldata automaatset õiguse aktiveerimist ega ended üleminekut |
| L01 | en/et/ru ja deaktiveeritud keel | Valik töötab; en ei deaktiveeritu; sobimatu keel langeb tagasi aktiivsele default'ile |
| L02 | Puuduv/tühi tõlge, aktiivne English | English fallback; täielikult puuduval võtmel antud fallback |
| L03 | Uus dünaamiline modaalitekst | DOM tõlkemehhanism töötab, koodiplokke ja andmevälju ei tõlgita |
| L04 | Väline/CRLF `return_to` | Ei suunata välisele hostile ega vigasesse päisesse |
| N01 | Oma, teise kasutaja ja kliendi ühisteade | Oma + ühine nähtavad; teise sihtteade pole; ainult oma sihtteade saab loetuks |
| N02 | Ühisteate lugemine | Ei eeldata individuaalse read oleku salvestamist |
| R01 | Kontrollandmetega klient-statistika | Täpsed active_users/active_modules/org_units/open_support_tickets arvud |
| H01 | Toe päringu vastajavalik, avamine/vastamine/sulgemine | system järjekord või üks client_admin/kehtiva lepingu manager, open → in_progress → closed, kogu vestlus osalistele nähtav |
| H02 | Võõras sama/teise kliendi kasutaja ja nimetu teine admin/manager | Teema puudub loendist ning otselink/POST 404, süsteemi roll ei ava nimelist privaatset klienditeemat |
| H03 | Looja/vastaja kutsuvad sama kliendi aktiivse töötajakonto | Kutsutu loeb ja vastab, kuid ei kutsu ega sulge; võõra kliendi/mitteaktiivset/korduvat kontot ei lisata |
| H04 | Sulgemine, hiline reply/invite, vähemalt 12 suletud teemat | Suletu ainult loetav, alumises segmendis täpselt 10 closed_at DESC,id DESC; vanem otselink jätkuvalt lubatud |
| H05 | Vanad system päringud ja suletud teema algtekst | Säilivad teema/algsõnum, closed_at backfill senisest updated_at-ist, looja full_name mitte ID |
| G01 | Audit ja filtrid | Õige kasutaja/klient/objekt/aeg, mitte parool; kuni 100 viimast |

## 4. Kinnistu ja lepingud

| ID | Katse | Oodatud tulemus |
|---|---|---|
| K01 | building → wing → floor → room ja building → floor | Mõlemad lubatud; room alla ja floor alla building keelatud |
| K02 | Nimi ja Up/Down | Sama vanema/tüübi järjestus säilib reload'il; vale äär annab vea |
| E01 | 0,01%, 100%, 0%, 100,01% | Esimesed kaks lubatud, viimased kaks keelatud |
| E02 | Kataloogi peitmine | Uues lepingus ei pakuta; olemasolev säilitab ID ja protsendikoopia |
| E03 | Primary 01.01–31.01 ja uus 31.01–28.02 | Kattuvus keelatud; uus alates 01.02 lubatud |
| E04 | Temporary väljaspool primary perioodi või samal asukohal | Keelatud |
| E05 | Kaks temporary lepingut samal korrusel sama kalendrikuu eri päevadel | Keelatud ka ilma päevavahemike kattuvuseta |
| E06 | Temporary eri korrustel kattuva perioodiga | Lepingud lubatud, graafiku tööaja kattuvust kontrollitakse eraldi |
| E07 | Primary muutmine, mis jätab temporary välja | Rollback/valideerimisviga |
| E08 | Arhiveeri temporary graafikuga / graafikuta / primary | Ainult graafikuta temporary saab arhiveerida; restore säilitab ID |
| E09 | Muuda lepingut | Töötaja ja liik ei muutu; varasem koormuse snapshot säilib sama workload ID korral |

## 5. Graafiku mõõdetavad arvutusnäited

Arvutuskatsetes kasuta kas eraldatud pühadeta andmebaasi või täpselt nimetatud kalendrit. Lisatud pühad mõjutavad kõiki kliente.

| ID | Sisend | Oodatud tulemus |
|---|---|---|
| S01 | Kuu `2026-02` / `2026-13` / `26-02` | 28 päeva / vigane / vigane |
| S02 | 50%, 20 tööpäeva | Required = 80h |
| S03 | Primary 12.10.2026–16.10.2026, 50%, pühadeta | 5 tööpäeva, Required = 20h |
| S04 | Nädalavahetuse shift 8h / exception 8h / block 24h | Planned = 8h / 0h / 0h |
| S05 | Tööpäeva exception 8h, sellele kuupäevale kind_id 1 või 2 | Pärast importi exception = 0h ja selle tööpäeva norm väheneb 8h × koormus |
| S06 | Kind 3 või 4 samal tööpäeval | Required ei vähene; ei rakendata 3h lühendust |
| S07 | 31.01.2026 20:00, shift 12h | Jaanuar 4h, veebruar 8h |
| S08 | 30.04.2026 20:00, shift 12h | Aprill 12h, mai 0h; ei topeltarvestata |
| S09 | 31.08 ja 31.12 sama 12h shift | Vastavas perioodilõpukuus 12h, järgmises kuus 0h |
| S10 | Primary 80h + temporary 8h; norm 80h | Primary Sum=88h, temp jaotus=8h, OT=+8h; temporary Sum=8h, muud saldod NULL |
| S11 | Perioodi kuude OT: jaan +4, veebr −2, märts +6, apr +1 | Tri-OT: +4, +2, +8, +9; mai algab uuesti |
| S12 | Kuukoormus 50% → 100%, 20 tööpäeva, Planned 88h | Required 80→160, OT +8→−72; päevakanded ja baasleping ei muutu |
| S13 | Eemalda kuu erand | Baasprotsent taastub; eranditabeli rida eemaldatakse |
| S14 | Tööpäeva exception algab 20:00 ja kestab 12h | Loendamist otsustab alguspäev, mitte iga jätkupäeva tööpäevasus |
| S15 | Juht M1 primary, M2 temporary | M1 ei näe automaatselt M2 temporary rida; ulatus vastab lepingufiltrile |

### Salvestus, kattuvus ja UI

| ID | Katse | Oodatud tulemus |
|---|---|---|
| S16 | Muuda ühte lahtrit, teises on senine kanne | Ainult valitud kanne muutub; teine säilib |
| S17 | Off olemasolevas/tühjas lahtris | Olemasolev eemaldub; tühja ei tekitata uut kirjet |
| S18 | Shift 08:00–16:00 ja 16:00–20:00 samale töötajale eri lepingutel | Lubatud |
| S19 | Shift 08:00–16:00 ja 15:59–20:00 | Keelatud, kõik selle tehingu muudatused rollback |
| S20 | Eelmise päeva 20:00–08:00 ja järgmise 07:00 | Keelatud ka kuu piiril |
| S21 | Block 00:00–24:00 + kattuv shift või nädalavahetuse exception | Keelatud, block'i Planned jääb 0 |
| S22 | Peidetud mall uues lahtris / senise väärtuse säilitamine | Uus keelatud / senine lubatud |
| S23 | Malli kestuse muutmine pärast kande loomist | Varasema kande saldo kasutab uut mallikestust; snapshot'i ei eeldata |
| S24 | Väljaspool perioodi päev, B leping või manager'i teine leping | Viga, ühtegi osalist salvestust pole |
| S25 | AJAX success/error vastus | success boolean, message string, edu koond kõikide lubatud lepinguridade kohta, temporary NULL saldod |
| S26 | Võrguviga / HTML 403 AJAX asemel | UI taastab eelmise valiku ja näitab viga; ei näita salvestatud edu |
| S27 | Grupi peitmine/avamine | Päevakanded ja saldod ei muutu; aria-expanded vastab nähtavusele |
| S28 | Kuukoormuse endpoint vale meetodi/tokeni/sisendiga | Vastavalt 405/403/400; teenuse valideerimisviga 422 |

## 6. XML ja print

| ID | Katse | Oodatud tulemus |
|---|---|---|
| X01 | 1 korrektne rida ja sama XML uuesti | added=1/existing=0, seejärel added=0/existing=1 |
| X02 | Muuda ainult sama võtme notes/kind teksti | Olemasolev kirje ei kirjutata üle |
| X03 | Vigane kuupäev/juur, DOCTYPE/ENTITY, tühi, >2 MiB, >10000 rida | Tagasilükkamine ilma osalise impordita |
| X04 | Pühade loend teisel kliendil | Globaalne sama kalender; mõjutab ka teise kliendi arvestust |
| V01 | Printimisgrupid kõik/tühi/üks/mitu | Valik peegeldub prindis, grupid eri landscape-lehtedel |
| V02 | 31 päeva, pikk nimi, ajutine rida, palju töötajaid | Kõik veerud mahuvad landscape'ile; pikk grupp jätkub, read võrdsed |
| V03 | Prinditavad elemendid | Kuupäev, kuu, grupp, värvid ja Sum h; CRM kest, Min h/OT/Tri-OT puuduvad |
| V04 | En/et/ru päis ja 320/768/1440 px ekraan | Menüüd ja vormid kasutatavad; graafiku tegelik loetavus kontrollitakse eraldi |

Prindivaate tööpuus oleva poolelioleva kuunime/päevanumbri muudatuse vastuvõtt otsustatakse selle funktsiooni eraldi väljalaskes; seda ei loeta automaatselt selle dokumentatsiooni muudatusega kinnitatuks.

## Lõpetamise tingimused

Taastamine on vastu võetud alles siis, kui puhas paigaldus ja SQL-only skeem, konto põhivood, kliendieraldus, kõik kasutatavad moodulid, mõõdetavad graafiku piirijuhtumid, tõlked, print ja varukoopiast taastamine on kontrollitud ning puudused/taotletud erinevused kirjalikult kinnitatud.

Repositooriumis puudub testiraamistik ja automaatne CI testisuite. Käesoleva dokumentatsiooni täiendamisel tehtavad staatilised lingi-/katvuskontrollid ja PHP lint ei asenda DB/browser vastuvõtuteste. Päris serveri andmeid ei kasutata dokumentatsiooni kontrollimisel.

## Selle ülevaatuse kontrollitulemused (14.10.2026)

- PASS: dokumentide kohalikud Markdown-lingid; kõik 71 marsruuti ja 32 tabelit inventuuris; kõik 63 registreeritud migratsiooni täpse tekstina SQL-paketis; 157 inventeeritud lähtefaili SHA-256 vastavus tööpuule.
- PASS: 34 DB-ühenduseta kontrolli PHP 8.3.22-ga: kuu valideerimine, osalise perioodi tööpäevad, pühadest sõltuv exception, tundide norm, kuu/perioodilõpu minutite helper, kattuvuse ja kõrvaloleku reeglid, lepingu kuupäevad, kinnistu lapsetüübid, XML/SQL parseri piiratud näited ning CSRF.
- PASS: muudetud installeri `php -l`, IDE veakontroll ja `git diff --check`.
- **Tegemata:** SQL-paketi import päris MySQL-i, täielik saldode DB-query test (sh perioodilõpu ülekande välistamine), rolli-/kliendieralduse HTTP testid, meilitransport, brauseri print ja varukoopiast taastamine. Selle ülevaatuse käigus puudus kohalik MySQL kliendi/serveri tööriist ning tootmisandmebaasi ei kasutatud.

Minutite helper'i test üksi ei tõenda perioodilõpu järgmise kuu nullsummat: selle tagab eelmist päeva välistav andmebaasipäring. Seda eristust kajastab tehniline spetsifikatsioon ning kogu teenuse vastuvõtukatse on jätkuvalt vajalik.

### Versiooni 1.77 käivituse regressioonikontroll

Migratsiooni tekst tuleb kontrollida installeri tegeliku `splitStatements()` meetodiga, mitte ainult faili sisaldumise või PHP süntaksi järgi. Vana 063 tekst lõigati kirjelduses oleva semikooloni tõttu kaheks. Parandatud 063 ja uus 064 peavad mõlemad andma täpselt ühe mittetühja INSERT-lause, mille tekst on täielik algne SQL ilma lõputerminaatorita. See lisab viis kontrolli varasemale 34-le; DB-ühenduseta kontrollide koguarv on 39.

PASS: kõik 39 kontrolli ning uuendatud 64 migratsiooni/158 faili katvus. Live'is kontrollitud `/`, `/panel` ja `/panel/schedule`: HTTP 200, käivituse veavaade puudub. See ei asenda ülejäänud tabelites kirjeldatud täielikku vastuvõtu- ega varukoopiast taastamise katset.

## Kinnistu tüüpide tõlked (1.90)

- Versioon 1.92 laiendab testi migratsiooni 079 seitsme lause, korduskäivitamise ja kümne uue fraasi kõigi keelte kontrollidega. Tegelik kataloogivaade renderdatakse kolme kataloogi ja kolme keelega: aria-label on tõlgitud ning salvestatud kasutajanimi säilib. Kokku 129 kontrolli. Live GET-kontrollid läbisid titles/departments/workloads/contracts/contracts-create lehed eesti/vene keeles; vorme ei saadetud.
- Versioon 1.91 laiendab sama testi migratsiooni 078 korduskäivitamise, en/et/ru „New job title” kataloogiväärtuste ja UI textMap kontrollidega. Oodatud vormisildid: New job title / Uus ametinimetus / Новая должность.
- `php -d extension=pdo_sqlite tests\property-translations.php`: kontrollib tegeliku migratsiooni kolme SQL-lauset, korduskäivitamise järel nelja võtit ja 12 aktiivset tõlget ning TranslationService kataloogi/textMap väljundit kõigis kolmes keeles.
- Kinnistu loendi ja lisamisvormi tüübid: Building/Hoone/Здание, Wing/Korpus/Корпус, Floor/Korrus/Этаж, Room/Ruum/Помещение. Keelevahetus ei tohi muuta salvestatud node_type koode ega kasutaja asukohanimesid.

## Toe avamata aktiivsete teemade regressioon (1.87)

**1.89 rea märgistus:** toe testis 174 kontrolli, sh looja algne loetud rida, saaja avamata Lugemata/bold, looja uus vastus/bold, saatja tavaline rida, rea is_unread summa võrdsus menüü arvuga, detaili avamisel märgi/bold kadumine, suletu märgistuseta olek, süsteemi loendi sama käitumine ja migratsiooni 076 kolm terviklikku lauset. Live loendi GET-is kuvatakse olemasoleval teemal „Uus vastus”; computed font-weight kontroll ei tohi teema linki avada ega lugemisolekut muuta.

**1.88 muudab vastuste käitumist:** samas testis on nüüd 158 kontrolli. Lisaks esmaavamisele kontrollitakse teiste uue vastuse lugemata arvu ka loojale, saatja loetud olekut, kutsutu vastust, mitme vastuse ühte ticketiühikut, detaili laadimise ajal lisandunud vastuse säilimist lugemata, vana tabi piiri tagasiliikumise keeldu, ID-põhist lugemist sama sekundi vastustel, saatja lugemispiiri rollback'i auditiveal ja suletute puudumist. Migratsioon 075 peab andma neli täielikku lauset. Varasema 1.87 katse „vastus ei lähtesta” on asendatud „uus vastus teavitab teisi” katsega.

PASS 1.88: 158 toe, 29 teavituse ja varasemad F/graafikuasenduse regressioonid, PHP lint/IDE ning whitespace. Live Toe loendi GET näitab algse looja kontole olemasoleva avatud vastustega teema eest Tugi 1. Teemat kontrolliks ei avatud ega tootmisvastuseid lisatud. Taastepakett/inventuur: 80 route'i, 37 tabelit, 75 täpset migratsiooni ja 183 lähtefaili kontrollsummad.

`php -d extension=pdo_sqlite tests\support-tickets.php` sisaldab lisaks varasemale 120 kontrollile 24 toe esimese avamise/loenduri kontrolli. Looja arvu puudumine kohe pärast loomist, saaja/kutsutu avamata arv, võõras admin/manager/klient, avamiskinnituse teenuse autoriseerimine, loendi ja invalid-CSRF mõju puudumine, detaili GET vähendab ainult selle konto arvu, esimene avamisaeg säilib, uus vastus ei taasta lugemata olekut, in_progress kuulub loendurisse, closed ei kuulu, sõltumatud süsteemi vastajad ja aktiivse rolli eemaldamine. Paneeli/admini badge'id ja nulli puhul peitmine ning migratsiooni neli täielikku lauset kontrollitakse samuti. SQL test kohandab ainult FOR UPDATE ja idempotentse MySQL upsert'i. Live loendi laadimist kontrollitakse ilma tootmisteemasid avamata ega nende lugemisolekut muutmata.

## Teavituste arhiivi ja loenduri regressioon (1.86)

Käivita `php -d extension=pdo_sqlite tests\notifications.php`. Tegelik teenuse SQL, handler'id ja paneeli vaated kasutavad eraldatud SQLite andmeid; ainult MySQL idempotentne upsert kohandatakse SQLite süntaksile. Kontrollib 30 päeva kaasavat tavavaate/ranget arhiivi piiri, 24 tunni lugemata piiri, tulevikuaja välistamist, kliendi/saaja eraldust, ühisteate kasutajapõhist lugemist, legacy is_read, korduslugemise idempotentsust, üle 100 vana teate 50-kaupa kättesaadavust/stabiilset ID-järjestust, HTML-escape, arhiveeritud teate lugemist, vale lehte, CSRF/method/foreign-ID keeldu ning positiivset/puuduvat menüübadge'i. Migratsioon peab installeri tegeliku jagajaga andma seitse täielikku lauset. Live kontroll on ainult lugemiseks: 30 päeva ja Arhiivi lingid, tõlked ning vormid, tootmisteateid testimiseks loetuks ei märgita.

PASS: 29 teavituste integratsioonikontrolli, 120 toe ja 16 ajavööndi regressiooni ning varasemad F/schedule-substitutes menüü/õiguse kontrollid. PHP lint/IDE vead puuduvad. Live tavavaade ja tühi arhiiv avanevad tõlgitud vahelehtedega, konto null-loenduri märki ei näidata. Positiivne badge ja ühisteate lugemine kontrolliti eraldatud andmetel, tootmises lugemisolekut ei muudetud. Taastepakett/inventuur: 80 route'i, 35 tabelit, 73 migratsiooni ja 181 lähtefaili kontrollsummad ning docs-lingid.

## Eesti ajavööndi regressioon (1.85)

`php tests\application-time.php` kontrollib Eesti talve +02:00 ja suve +03:00, 2026 märtsi/oktoobri DST üleminekuid enne/täpselt pärast piiri, hosti ajavööndist sõltumatust, PDO seotud sessiooniseadistust, seadistuse vea edasikandumist, PHP bootstrap'i Europe/Tallinn väärtust ning migratsiooni tegelikku SQL-jagajat. SQL sessioon seatakse enne ühenduse tagastamist; pika protsessi cache peab jälgima offset'i muutust. Uue teavituse, ticketi loomise, sulgemise ning sama sulgemise auditilogikirje ajad peavad live'is näitama sama kohalikku aega. Suve/talve ülemineku test ei tähenda olemasolevate ajavööndita DATETIME kirjete teisendamist.

Vanade kirjete parandamine on blokeeritud kuni nende päritolu ja parandatava hulga eraldi kontrollini. Kohalik lugemisühenduse proov andis SQLSTATE 2002, mistõttu tootmisandmete algset sessiooni ega vanade auditi/sulgemise paaride vahet ei saanud DB-st kinnitada. Teavituste/toe live GET toimimine kinnitab rakenduse käivitust, mitte ajalooliste ajatemplite päritolu. Ainult vaatamiseks brauseris ei looda uut teavitust/ticketit ega muudeta rolle.

## Toe vestluste integratsioonitest (1.84)

Käivita `php -d extension=pdo_sqlite tests\support-tickets.php`. Tegelik SupportService/RoleService, detaili/listi handler'id ja jagatud kliendi/admini vaated töötavad eraldatud mälusisesel SQLite baasil. Ainult MySQL `FOR UPDATE` eemaldatakse testis; päris MySQL-i lukk/native prepares ja DDL vajavad eraldi kontrolli.

Kontrollib kõiki kolme sihtliiki, mitut manageri/administraatorit, tänase päeva kaasavaid lepingupiire, lõppenud/tulevase/arhiveeritud lepingu välistamist, töölepinguta kontot, aktiivseid kontosid/rolle, sama/teise kliendi kõrvaliste keeldu, süsteemi järjekorda, rolli eemaldamist, nimelise vastaja ligipääsu säilimist lepingu muutumisel, looja/vastaja kutseid ja kutsutu lugemist/vastamist ilma haldusõiguseta. Lisaks tühjad/ülepikad sisendid, võltsitud ID-d, XSS-escape, kronoloogia, transaktsiooni rollback auditiveal, sulgemise püsiv aeg/kasutaja, kõik suletu muutmiskeelud, täpselt viimased 10 sulgemisaja (mitte loomise) järgi, vanema lubatud teema otselink, GET 405, CSRF 403 ning lubamatu detail/POST 404.

Live-kontrollid on ainult lugemiseks: loendi eraldi segmendid, looja nimi, varasema suletud päringu täistekst, uue päringu vastajavaliku vorm. Testimiseks ei looda ega muudeta päris päringuid, vastuseid, kutseid ega konto rolle. Installeri tegelik SQL-jagaja peab andma migratsioonist 071 täpselt üheksa täielikku lauset, viimane versiooni INSERT.

PASS: 120 toe integratsioonikontrolli, sh täpsed 191 Unicode-märgi/65535-baidi piirid, võrdse sulgemisaja ID-järjestus ja vana status-endpoint'i reopen-keeld. Varasemad client-management, F employee/property/modules/print, manageri ja graafikuasenduse regressioonid läbivad. PASS: 53 DB-ühenduseta migratsiooni/helper'i kontrolli, PHP lint/IDE ja diff whitespace. Taastepakett/inventuur: 80 switch-route'i, 34 tabelit, 71 täpset migratsiooni, 176 lähtefaili kontrollsummad ja docs-lingid. Live kliendipaneelis töötavad tõlgitud loend, vana suletud teema algtekst ja loomise vastajavalik koos puuduva manageri teatega. Uute päringute/vastuste/kutsete/sulgemiste tootmiskatseid ning süsteemi vastajakonto live-katset ei tehtud.

## Graafikuasenduse integratsioonitest (1.83)

Käivita `php -d extension=pdo_sqlite tests\schedule-substitutes.php`. Test kasutab manageri SQLite baasi ja tegelikku SubstituteService/ScheduleService ning handler'eid/vaateid. Kontrollid: manager enda asenduse kohene määramine, võltsitud original ID eiramine, aktiivne F ilma töölepinguta, jooksva/tulevase kuu persistentsed salvestused, möödunud kuu/teise juhi/teise kliendi keeld, koormuse/seadete/pühade/edasidelegeerimise keeld, F muu menüü säilimine, vahetuse nupud ilma workload nuppudeta, algus/lõpp/future/pending/rejected/ended, konto deaktiveerimine, original F rollimuutus, varane lõpetamine ja admini määramine/lõpetamine. Kontrollib rangeid kuupäevi, kattuvat määrangut, puuduvaid/mitteaktiivseid kontosid, reserveeritud legacy tüüpi ja CSRF. Tootmisandmeid ei muudeta. Päris MySQL-i native prepares/lukustust ning päris asendajakontoga live-muutmist tuleb eraldi kontrollida.

PASS: asenduse SQLite integratsioon koos inclusive start/exclusive end piiridega, manageri baasregressioon, F print/employee/property/modules ja client-management kontrollid, PHP lint/IDE diagnostika, 51 migratsiooni/helper'i kontrolli. Live administraatori kuu-, print- ja Graafikuasendajad lehed tagastavad 200, määramisvorm ning uued en/et/ru tõlked on olemas. Taastepaketi/inventuuri kontroll hõlmab 72 route'i, 32 tabelit, 70 migratsiooni ja 170 lähtefaili. Päris manageri/asendaja kontoga salvestust ega tootmismäärangu muutmist assistendi kontrollis ei tehtud.

## Manageri enda ja alluvate graafiku integratsioonitest (1.82)

Käivita `php -d extension=pdo_sqlite tests\manager-schedule.php`. Tegelik teenus ning AJAX kuu/koormuse handler'id käivitatakse eraldatud mälusisesel SQLite DB-l. Test kohandab ainult MySQL FOR UPDATE/upsert süntaksi ja FIELD-funktsiooni; SELECT-i autoriseerimistingimused käivitatakse muutmata. Kontrollib enda/alluvate põhi- ja ajutisi lepinguid, teise juhiga sama töötaja lepingu keeldu, teist klienti, alluvateta/tulevase/arhiveeritud/mitteaktiivse alluvaga kontot, kuu kehtivust, salvestatud ridu ja koormuse reset'i, mixed-päringu rollback'i, kattuvusi, AJAX vastuseid, F keeldu ja manager-määrangu eemaldamist. Own Tri-OT näide: 2026 september+oktoober 44 tööpäeva x 8 h, kummalgi kuul 8 h planeeritud => -336 h; manager-määrang algab alles oktoobris, kuid valitud kuu õigusega on mõlema kuu enda planeeritud tunnid arvestatud. Päris MySQL-i tehingulukke ega live manager-kontot selle testiga ei kontrollita.

PASS: manageri tegeliku SQL-i/salvestuste integratsioon, varasemad F print/employee/property/modules ja client-management regressioonid, CSRF keeld, 49 migratsiooni/helper'i kontrolli, PHP lint/IDE diagnostika. Live avatud administraatori kuugraafik tagastab 200 ning säilitab muutmisvormi ja printimislingi; tootmisandmeid ei muudetud. Live manager-konto kontrolli ei tehtud.

## Level F peidetud mooduli- ja kinnistulehed (1.81)

Käivita `php tests\level-f-scope.php` ning `php -d extension=pdo_sqlite tests\schedule-print-scope.php --sqlite`. Laiendatud testid kontrollivad F/F+viewer keelatud `/panel/modules` (enne mooduliloendi lugemist), kinnistu vaatamise väravat/lehte ja create/edit/reorder GET/POST alamtegevusi. Viewer/admin/F+admin säilitavad senise vaatamisõiguse. Paneeli HTML-is pole F-l moodulite ega kinnistu linke, kuid enda töötaja/lepingute ja graafiku printimise lingid säilivad. Graafik sõltub endiselt aktiivsest property moodulist, mitte kinnistulehe vaatamisõigusest. Live Level F kontot ei kontrollitud.

## Level F graafiku prindiskoobi regressioonitest (1.80)

Käivita `php tests\schedule-print-scope.php`. Tegelik teenus, handler'id ja prindivaade töötavad PDO/rolli/sessiooni testtopeltidega ilma MySQL-ühenduseta. Kontrollid: enda põhi- ja ajutise lepingu grupid koos kaastöötajatega; sama asukoha teine osakond keelatud; muu kliendi, samanimelise konto, arhiveeritud, tulevase/lõppenud ja mitteaktiivse konto mõju; kuu vahetamine; lepinguta konto; URL-i ID-de eiramine; kanded ainult lubatud gruppides; kuu kogutunnid; F ja F+viewer; admin/F+admin ning viewer senine ulatus; ainult print-nav, PDF-nupp, kuugraafiku GET suunamine, HTML/AJAX POST 403, kuukoormuse ja seadete/pühade keelud enne graafikupäringuid. Test ei täida SQL-i päris MySQL-is ega loo PDF-faili; need vajavad eraldi live/taastekatset.

Kui PHP-l on PDO SQLite laiendus, käivita lisaks `php -d extension=pdo_sqlite tests\schedule-print-scope.php --sqlite`. See täidab teenuse tegeliku, muutmata grupi-SELECT-i eraldatud mälusisese SQLite andmebaasi peal ning kontrollib põhi/ajutist gruppi, osakondi, kliendieraldust, kuuvahetust ja lepinguta kontot. Tootmisandmebaasi ega PHP konfiguratsiooni ei muudeta. MySQL-i eraldi integratsioonikatset see ei asenda.

PASS: prindiskoobi PHP regressioon koos tegeliku SQL-i SQLite katsega, varasemad kliendihalduse ja F töötaja/lepingutestid, 45 DB-ühenduseta migratsiooni/helper'i kontrolli, PHP lint/IDE diagnostika, dokumentatsioonilingid ja 67 migratsiooni/164 faili katvus. Live'i administraatori print tagastab 200; PDF-nupu klõps kutsub printimisfunktsiooni, valikuta on nupp keelatud ja A4 landscape säilib. Printimisdialoogi avamise asemel kasutati brauseris ajutist kontrollfunktsiooni, mis taastati; PDF-faili ei loodud. Live Level F kontot ei kontrollitud.

## Level F töötajate ja lepingute regressioonitest (1.79)

Käivita projektijuures `php tests\level-f-scope.php`. Eraldatud test käivitab tegelikud rolli-, töötaja- ja lepinguteenused ning handler'id ilma MySQL-ühenduseta, kasutades PDO testtopeltit. Kontrollib SQL-i konto/kliendifiltreid, samanimelise teise konto välistamist, teise kliendi välistamist, URL-i ID-de eiramist, kõigi kolme kataloogi GET/POST 403 enne lugemist, peidetud menüüd ning muutmisväravaid. Kontrollitakse F, F+viewer, viewer, admin ja F+admin kombinatsioone. Kontode rolle ei muudeta. Level F live-kontrolli ei ole tehtud.

PASS: Level F regressioonitest, varasem kliendihalduse test, 43 DB-ühenduseta kontrolli (sh migratsioon 066 installeri tegeliku SQL-jagajaga), muudetud PHP lint ja IDE diagnostika.

## Kliendihalduse regressioonitest (1.78)

Käivita projektijuures `php tests\client-management.php`. Test kasutab tegelikku `handle_panel_client()` funktsiooni ja PHP-vaadet koos eraldatud teenuse-/sessiooniasendustega, ilma DB-ühenduseta. Level F ja viewer saavad GET-il andmed ainult tekstina, 0 vormi, 0 sisestusvälja ja 0 salvestusnuppu. Nende POST ka korrektse tokeniga annab 403 ning salvestusteenust ei kutsuta. Client Administrator saab senise vormi ja salvestada; vigase CSRF korral ei kirjutata. Kontrollitakse HTML-erimärkide kodeerimist ja NULL kontaktandmete kuvamist.

Test ei muuda päris kontosid ega kliendiandmeid. `tests/` on HTTP kaudu keelatud. Repositooriumis pole testiraamistikku; see on otse PHP CLI-ga käivitatav test.

PASS: kliendihalduse regressioonitest, muudetud PHP lint/IDE diagnostika, 41 DB-ühenduseta kontrolli (sh migratsioon 065 installeri jagajaga), lingid ja 65 migratsiooni/160 faili inventuur. Live'i administraatorivaade annab 200 ja säilitab ühe muutmisvormi/salvestusnupu. Level F live-kontroll jäeti kasutaja valikul praegu tegemata; kontode rolle ega andmeid ei muudetud.
