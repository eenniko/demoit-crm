# DemoIT CRM: dokumentatsioon ja AI abil taastamine

**Taastamise sihtversioon: 1.93. Kontrollitud lähtekoodi seis: 24.10.2026.**

Käivituse parandusversioon **1.77** eemaldab migratsiooni 063 kirjeldusest SQL-jagajaga vastuolus olnud semikooloni. Taastepakett ja inventuur sisaldavad ka migratsiooni 064. Funktsionaalse taastamise kirjeldus jääb versiooni 1.76 käitumisega samaks.

Versioonis **1.78** on mitteadministraatori kliendihaldus ainult tekstiline vaatamisvaade. Administraatori vorm säilib ja mitteadministraatori POST on endiselt keelatud. Taastepakett/inventuur sisaldavad migratsiooni 065 ja eraldatud regressioonitesti.

Versioonis **1.79** näeb Level F ainult enda töötajakontot ja lepinguid, konto ID ja kliendi ID järgi. Töölepingute kataloogid on F-le keelatud ka otse URL-iga. Täiendav aktiivne `client_admin` säilitab administraatori õigused. Taastepakett/inventuur sisaldavad migratsiooni 066 ja testi `tests/level-f-scope.php`.

Versioonis **1.80** on Level F graafik ainult prinditav: nähtavad on valitud kuul tema lepinguid sisaldavad asukoha/osakonna grupid koos kaastöötajate ridadega ja brauseri PDF-printimisega. Muutmispäringud on keelatud. Täpsed reeglid on graafikujuhendis ja tehnilises spetsifikatsioonis; migratsioon 067 ning `tests/schedule-print-scope.php` kuuluvad inventuuri.

Versioonis **1.81** on Level F jaoks peidetud ja otse avamisel keelatud Active modules ning Property structure. Kinnistumooduli andmeid ega aktiivsust ei muudeta, seega lubatud graafikute printimine säilib. Piirang kasutab sama F ilma aktiivse client_admin'ita poliitikat; migratsioon 068 ning laiendatud olemasolevad regressioonitestid kuuluvad taastepaketti/inventuuri.

Versioonis **1.82** saab valitud kuul teise aktiivse töötaja kehtival lepingul manageriks määratud mitte-F kasutaja näha/muuta ka enda graafikut, mitte ainult talle juhina määratud lepinguridu. Level F print-only säilib. Migratsioon 069 ja `tests/manager-schedule.php` kuuluvad taastamispaketti.

Versioon **1.83** lisab eraldi graafikuasenduse: manager määrab ise aktiivse sama kliendi töötajakonto algus/lõppajaga, ilma asendaja töölepingu või rollimuutuseta. Jooksva/tulevase kuu vahetuste muutmisõigus kestab ainult asendusaja jooksul; F saab erandi ainult graafikus. Tehniline leping ja test `tests/schedule-substitutes.php` on dokumentides 10/12/14, tõlked ning versioon migratsioonis 070. Vana organisatsiooni asendajate taotlus ei anna seda õigust.

Versioon **1.84** muudab toe privaatseks vestlussüsteemiks: süsteemi toe ühine järjekord või üks valitud kliendiadministraator/töötaja kehtiva lepingu manager. Looja/vastaja saavad kutsuda sama kliendi aktiivseid töötajakontosid, kes loevad ja vastavad. Sulgemine jätab kogu vestluse ainult loetavaks ning loendi alumises osas kuvatakse viimased 10 sulgemisaja järgi. Skeem, API/õigused ja testid on dokumentides 11/12/14, migratsioonis 071 ja [integratsioonitestis](../tests/support-tickets.php). Vanad piletid jäävad süsteemi toe suunaga, suletute sulgemisaja algväärtus on senine `updated_at`.

See on kogu lahenduse dokumentatsiooni sisenemispunkt. Pakett kirjeldab olemasoleva rakenduse funktsionaalset taastamist, mitte ainult algseid soove. Dokumendid ei ole tootmisandmete, paroolide, serveri konfiguratsiooni ega lähtekoodi varukoopia.

Versioon **1.85** määrab PHP ajavööndiks Europe/Tallinn ja iga rakenduse MySQL sessiooni offset'iks sama hetke Eesti +02:00/+03:00. Nii on SQL NOW/CURRENT_TIMESTAMP/CURDATE ja PHP loodud ajatemplid ühtsed ka suve-/talveaja korral. Vanu ajavööndita DATETIME väärtusi automaatselt ei muudeta; nende päritolu kontroll ja ohutu paranduse eeldused on [paigaldusjuhendis](13-paigaldus-varundus-ja-taastamine.md).

## Lugemisjärjekord ja dokumentide vastutus

Versioon **1.86** lisab teavituste viimase 30 päeva tavavaate, vanemate arhiivi koos lehekülgedega ning menüü paremale viimase 24 tunni lugemata arvu. Isiklikud ja kliendi ühisteated märgitakse loetuks kasutajapõhiselt. Skeem ja API on dokumentides 11/12, test [notifications.php](../tests/notifications.php) ja migratsioon 073 kuuluvad taastamispaketti.

Versioon **1.87** lisab kliendi ja süsteemi Tugi menüüle avamata aktiivsete ticketite arvu. Looja on alati kohe loetud, teised osalised esimesel detailvaate avamisel. Vastused ei lähtesta lugemisolekut, suletud teemasid ei loendata. Migratsioon 074 lisab kasutajapõhised esimese avamise kinnitused; õigused ja testid on dokumentides 11/12/14.

Versioon **1.88** asendab 1.87 vastuste reegli: teiste osaliste uus vastus teeb avatud ticketi taas lugemata olekusse, ka algsele loojale. Saatja on loetud, detaili avamine salvestab nähtud vastuste ID piiri. Arv loendab ticketeid, mitte vastuseid, suletuid ei loendata. Migratsioon 075 lisab vastuste lugemise piiri tabeli. Täpne kehtiv reegel on dokumentides 11/12/14.

Versioon **1.89** märgistab toe loendis kasutaja lugemata aktiivse ticketi kogu rea paksu tekstiga ning „Uus vastus” või „Lugemata” tähisega. Rea olek ja menüü arv kasutavad sama SQL-reeglit. Kliendi/süsteemi vaated on ühtsed, suletud/read teemad tähist ei saa. Migratsioon 076 lisab ainult tõlked ja versiooni.

Versioon **1.90** lisab migratsiooniga 077 kinnistu nelja tüübi inglise/eesti/vene tõlked andmebaasi. Olemasolev UI tõlkekataloog rakendab neid loendis ja lisamisvormis, skeem ning salvestatud tüübid ei muutu. Tõlketabel ja kontrollid on dokumentides 08/14.

Versioon **1.91** lisab migratsiooniga 078 „New job title” vormisildi eesti- ja venekeelsed tõlked. Täielik dünaamiliselt koostatud fraas peab olema UI kataloogis; andmeskeem ja õigused ei muutu.

Versioon **1.92** lisab migratsiooniga 079 ülejäänud puuduvad töölepingute mooduli UI fraasid en/et/ru keeles. Sisestusväljade ja navigatsiooni ligipääsetavad nimetused tõlgitakse serveris samast kataloogist. Tõlketabel ning kontrollid on dokumentides 09/14.

Versioon **1.93** lubab erandi valikul rakendada sama malli 1–366 järjestikusele kalendripäevale, sh üle kuu piiri. Salvestus on atomaarne ja kontrollib iga kuu õigusi, lepingu kehtivust ja kattuvusi. Migratsioon 080 lisab uued tõlked; tehnilised piirid ja API on dokumentides 10/12/14.

| Dokument | Mida selle alusel taastada |
|---|---|
| [01: projekti- ja disaininõuded](01-projektinouded-ja-disaininouded.md) | Algne eesmärk, üldine kujundus ja kavandatud võimalused |
| [02: SQL-andmebaasi loogika](02-sql-andmebaasi-loogika.md) | Andmebaasi põhimõtted; tegelik skeem on taastepaketis |
| [03: kasutajad, rollid ja õigused](03-kasutajad-rollid-ja-oigused.md) | Rollide eesmärk ja algsed õigusenõuded |
| [04: PHP arhitektuur](04-php-arhitektuur-ja-mudelid.md) | Tehniline alus ja arenduspõhimõtted |
| [05: AI tööjuhend](05-ai-eeskirjad-ja-prompt.md) | AI tööviis ja versioonireegel |
| [06: töötajate kasutusjuhend](06-tootajate-mooduli-kasutusjuhend.md) | Töötaja lisamine, muutmine, aktiveerimine ja import |
| [07: töötajate tehniline juhend](07-tootajate-mooduli-ai-arendusjuhend.md) | Töötajate andmemudel, parser ja provisioneerimine |
| [08: kinnistustruktuur](08-kinnistu-struktuuri-moodul.md) | Asukohtade puu ja järjestamine |
| [09: töölepingud](09-toolepingute-moodul.md) | Kataloogid, põhi- ja ajutised lepingud, arhiveerimine |
| [10: graafiku kasutusjuhend](10-graafiku-moodul.md) | Kuugraafik, mallid, koormus, pühad ja printimine |
| [11: baasfunktsioonid ja tegelikud piirangud](11-baasfunktsioonid-ja-teostuse-piirid.md) | Autentimine, admin, patsiendid, organisatsioon, tõlked, teavitused, raportid ja tugi |
| [12: tehniline taastamisspetsifikatsioon](12-tehniline-taastamisspetsifikatsioon.md) | Käivitus, andmemudel, HTTP/JSON lepingud, graafiku algoritmid ja UI |
| [13: paigaldamine ja taastamine](13-paigaldus-varundus-ja-taastamine.md) | Keskkond, deploy, migratsioonid, varundus, tõrkeotsing ja taastamise sammud |
| [14: vastuvõtukontrollid](14-taastamise-vastuvotukontrollid.md) | Mõõdetavad testjuhtumid ja taastamise lõpetamise tingimused |
| [15: SQL-taastepakett](15-andmebaasi-taastepakett.sql) | Kõik tabelid, väljad, indeksid, välisvõtmed, algandmed ja tõlked ilma `sql/` kaustata |
| [16: tehniline inventuur](16-tehniline-inventuur.md) | Kõik marsruudid, handler'id, teenuste avalikud liidesed, vormiväljad ja failide kontrollsummad |

Vastuolu korral kirjeldavad 11–16 kontrollitud teostust; 01–04 sisaldavad ka sihtnõudeid. Turva- ja kvaliteedinõuet ei tohi lugeda teostatud funktsiooniks pelgalt selle olemasolu tõttu nõuete dokumendis. Algse nõude ja tegeliku teostuse vahe säilita nähtava otsusena, mitte ära paranda seda taastamise käigus vaikimisi.

## Katvuse kontroll

Ülevaatus hõlmas juurkäivitust, Apache reegleid, turvalist konfiguratsiooninäidist, `config/`, kõiki `src/Services/` ja `src/Support/` komponente, front controller'i marsruute, `views/` vorme ja menüüsid, graafiku JavaScripti/CSS-i ning SQL-migratsioonide registrit. Inventuur ja SQL-pakett annavad kontrollitava loendi ka väikestest tugifunktsioonidest.

| Valdkond | Põhiallikas |
|---|---|
| Avaleht, algseadistus, autentimine ja konto | 11, 12, 13, 16 |
| Kliendid, moodulite kataloog ja aktiveerimine | 07, 11, 12, 15, 16 |
| Rollid, keel, tõlked, seaded ja audit | 03, 11, 12, 15, 16 |
| Töötajad ja vana SQL-import | 06, 07, 11, 14 |
| Organisatsioon ja asendajate taotlused | 03, 11, 14, 16 |
| Patsiendid | 11, 14, 15, 16 |
| Kinnistu ja lepingud | 08, 09, 12, 14 |
| Graafik, saldod, pühad, koormus ja print | 10, 12, 14 |
| Teavitused, statistika ja kasutajatugi | 11, 14, 15, 16 |
| Paigaldus, uuendamine, varundus ja tõrked | 13, 14, 15 |

## Taastamisprompt

> Taasta DemoIT CRM-i dokumenteeritud versiooni 1.93 funktsionaalne käitumine. Loe kõigepealt dokumente 00, 11, 12, 13 ja 14, seejärel kõiki moodulijuhendeid, SQL-paketti 15 ja inventuuri 16. Baittäpse taastamise jaoks hangi kinnitatud 1.93 lähtekoodiväljalase; dokumentatsioon üksi ei sisalda kogu lähtekoodi, tootmisandmeid, saladusi ega hostingu seadistust. Kui täpset lähtekoodi pole, rekonstrueeri käitumine dokumentide järgi ja märgi see funktsionaalseks, mitte baittäpseks taastamiseks. Kasuta vanilla PHP 8+, PDO/MySQL, PHP sessioone ja Bootstrap 5.3.3. Ühine andmebaas eraldab kliendid `client_id` järgi; klienti ei võeta vormist. Ära eelda, et algsetes nõuetes nimetatud demo, õiguste pärandumine, asendaja automaatne volitamine või paroolivahetuse sundsuunamine on juba teostatud. Taasta esmalt tühi skeem SQL-paketi järgi või impordi andmebaasivarukoopia koos sobiva koodiväljalaskega; seejärel kontrolli installerit ja migratsioonide registrit, seadista saladused turvaliselt ning taasta konto/admin, baasfunktsioonid ja moodulid sõltuvusjärjekorras. Säilita marsruudid, väljade nimed, JSON-vastused, kuupäeva- ja tundide reeglid. Lisa vastuvõtutestid dokumendi 14 järgi. Kirjelda iga kõrvalekalle ja küsi otsust enne käitumise muutmist. Ära kasuta päris isikuandmeid ega saladusi. Ära kuuluta taastamist lõpetatuks enne puhta paigalduse, kahe kliendi isolatsiooni, graafiku arvutuste ja varukoopiast taastamise kontrolli.

## Mida tuleb eraldi säilitada

- Git-repositooriumi või väljalaske lähtekood: vajalik baittäpseks taastamiseks, sh kujundus ja JavaScript.
- Enne lähtekoodi kasutamist kontrolli, et valitud Git viide sisaldaks dokumenteeritud sihtversiooni; käesolev dokumentatsioon ja inventuur ei tõenda, et sama versioon oleks juba kaug-repositooriumisse salvestatud.
- Täielik andmebaasivarukoopia: dokumentatsioon ei taasta töötajaid, patsiente, graafikukandeid, kohandatud tõlkeid ega auditajalugu.
- Saladused ja keskkonna/hostingu seaded: turvalises saladuste hoidlas, mitte `docs/` all.
- Domeeni/DNS-i, TLS-i, meilitranspordi, ajavööndi, PHP/MySQL versiooni ja õiguste tegelikud väärtused.
- Riigipühade impordi algfail ja õiguslikult sobivad varundus-/säilitustingimused.

Kontroll oli lähtekoodi ja dokumentatsiooni staatiline võrdlus. See ei ole tootmiskeskkonna test, turvaaudit ega tühja MySQL-i peal tehtud taastamiskatse. Dokument 14 kirjeldab eraldi veel vajalikku käivitusaegset tõendamist.

## Hooldusreegel

Iga uue funktsiooni korral uuenda vastavat moodulijuhendit, tegeliku käitumise spetsifikatsiooni ja vastuvõtuteste. Marsruudi, vormi, teenuseliidese või skeemi muutmisel värskenda ka inventuuri ja SQL-taastepaketti. Lisa versioonilogi migratsioon ning uuenda README ja muudatuste logi. Varasemate väljalasete kuupäevi ja kontrollitulemusi ei kirjutata tagantjärele ümber.
