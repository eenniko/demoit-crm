# Tehniline taastamisspetsifikatsioon

**Sihtversioon: 1.98.** HTTP marsruutide ja avalike teenuseliideste täielik register asub [inventuuris](16-tehniline-inventuur.md); SQL väljade tüübid, NULL/default väärtused, ENUM-id, indeksid ja seosed [SQL-taastepaketis](15-andmebaasi-taastepakett.sql).

## 1. Käivitus ja failivastutused

- Juur-`index.php` kaasab `public/index.php`; Apache suunab puhtad URL-id juurkäivitusse.
- `config/env.php` loeb juure `.env`: kommentaarid/tühjad read jäetakse vahele, jaotus esimesel `=` märgil, olemasolevaid keskkonnamuutujaid ei asendata. Jutumärkide eemaldamist ega muutujate laiendamist pole.
- `db()` annab ühe PDO ühenduse päringu kohta; ERRMODE_EXCEPTION, FETCH_ASSOC, native prepared statements, `mysql:host=...;dbname=...;charset=...`.
- Alates 1.85 seab database bootstrap PHP ajavööndiks `Europe/Tallinn`. `ApplicationTime::mysqlOffset()` annab hetke Tallinn offset'i, millega PDO sessioonile täidetakse seotud parameetriga `SET SESSION time_zone = :time_zone` enne ühenduse avaldamist või SQL-i kasutamist. Nimetatud MySQL ajavöönditabelite olemasolu ei nõuta. Jagatud hosti globaalset ajavööndit ei muudeta. Cache kontrollib iga `db()` kutsega offset'i ja uuendab seda DST üleminekul, sh pika CLI protsessi korral. Seadistuse erind kandub edasi, vale ajaga ühendust ei tagastata. SQL NOW/CURRENT_TIMESTAMP/CURDATE ja PHP date kasutavad nüüd sama Eesti aega; graafiku kestuse UTC intervalliarvutused säilivad.
- Autoload, Composer, ORM, eraldi controller/repository/entity klassid, CLI-käsud ja frontend build puuduvad. Teenused kaasatakse `require_once` abil; handler'id on front controller'is ning teenustes on nii äriloogika kui SQL.
- Iga päring: sessiooni käivitamine → skeemi kontroll/migratsioonid → URI path'i lugemine ja lõpus `/` eemaldamine → keelevahetus → algseadistuse värav → route switch → handler.
- Üldine erind annab 500 vaate; ainult `APP_DEBUG=1` lisab veateate ja stack trace'i. See ei tähenda, et kõik teenuste erindid logitakse.
- `views/render.php` annab `e()` (`htmlspecialchars`, ENT_QUOTES, UTF-8), `t()` ja avaliku renderduse. Admin/panel renderdavad tööruumi output buffer'isse ning lisavad ühise päise, menüü ja jaluse. `extract(..., EXTR_SKIP)` ei asenda juba olemasolevaid muutujaid.
- Ühised tugiklassid: `ClientContext` on sessiooni klient/kasutaja/nimi; `Csrf` genereerib 32 juhuslikust baidist hex-tokeni ja võrdleb `hash_equals` abil.

Sessiooniküpsis kasutab HttpOnly, SameSite=Lax ning Secure'i, kui PHP `HTTPS` serverimuutuja näitab HTTPS-i. Sisselogimine/väljalogimine muudab sessiooni ID-d. Tagurpidi proksi korral peab hostingu konfiguratsioon HTTPS-i õigesti edastama; rakendus ei tõlgenda iseseisvalt kõiki proxy päiseid.

## 2. Andmemudeli kaart

| Tabel või tabelirühm | Vastutus ja olulised seosed |
|---|---|
| `system_settings` | Globaalne unikaalne setting_key ja väärtus |
| `system_clients` | Unikaalne kliendikood; `13666` süsteemikeskkonnale |
| `system_users` | Üks klient konto kohta; unikaalne `(client_id, username)`; telefon lisandub migratsiooniga |
| `system_roles`, `system_user_roles` | Globaalne rollikataloog; kliendi/kasutaja/valikulise üksuse aktiivsed rolliseosed |
| `system_permissions`, `system_role_permissions` | Õiguste kataloog ja skeemitaseme seosed; pole üldine käitusaegne policy-mootor |
| `system_modules`, `system_module_versions` | Kataloog ja võimalik mooduliversioonide ajalugu |
| `system_client_modules` | Unikaalne `(client_id, module_id)`, aktiivsus ja aktiveerimisaeg |
| `system_client_module_provisions` | Sama paar, schema_version, ready/inactive ja provisioneerimisaeg |
| `employee_module_settings` | Üks rida kliendi kohta, uue töötaja vaikimisi roll |
| `employee_list_settings` | Kliendi FK/primaarvõti, page_size 1–100; puuduv rida tähendab 10, esmane salvestus loob rea |
| `system_languages`, `system_translation_keys`, `system_translation_values` | Globaalne kataloog; unikaalne võti ja võtme/keele väärtus |
| `system_org_units`, `system_substitutes` | A–F üksusepuu ja asendajate taotlused |
| `system_notifications`, `system_notification_reads` | Kliendi/kasutaja teavitused ja kasutajapõhised lugemiskinnitused |
| `system_support_tickets`, `system_support_replies`, `system_support_participants`, `system_support_reads`, `system_support_reply_reads` | Privaatsed toe vestlused, vastused, osalejad, esmaavamised ja nähtud vastuse ID piirid |
| `system_client_domain_embeds` | Kliendi unikaalne domeen ja 256-bitine embed-token, üks seadistus kliendi kohta; unikaalsed domeen/token ja kliendi FK |
| `patients` | Nimi, isiku- ja kontaktandmed, märkused, olek ja lisaja |
| `property_nodes` | Kliendi puu, parent_id, building/wing/floor/room ja sort_order |
| `employment_job_titles`, `employment_departments` | Kliendipõhised unikaalse nime ja aktiivsusega kataloogid |
| `employment_workloads` | Nimi, DECIMAL protsent ja olek; lepingu protsent on eraldi hetkekoopia |
| `employment_contracts` | Töötaja, asukoht, amet, koormus, osakond, juht, primary/temporary, kuupäevad, archived_at |
| `employment_contract_list_settings` | Kliendi FK/primaarvõti ja page_size 1–100; puuduva rea korral 10, eraldi töötajate loendi seadest |
| `employment_contract_monthly_workloads` | Unikaalne klient/leping/kuu; kuu esimesel päeval workload_id ja protsendikoopia |
| `employee_schedule_templates` | Tüüp, kood, nimi, TIME algus, minutite kestus, värv ja aktiivsus |
| `employee_schedule_entries` | Üks kanne `(client_id, contract_id, schedule_date)` kohta, töötaja ja mall ning auditajad/muutjad |
| `system_public_holidays` | Globaalne kuupäev/title/notes/kind/kind_id kataloog ja unikaalne source_key |
| `system_audit_logs`, `system_version_logs`, `system_initial_setup` | Tegevuslogi, unikaalsed väljalasked ja algseadistuse lukustus |

Kliendieraldus põhineb teenuse filtril ja uuemate moodulite liit-välisvõtmetel `(client_id, id)`. Vanemates süsteemitabelites ei kinnita iga üksik ID-välisvõti iseenesest sama klienti. Kõigi päringute/pakkujate usalduspiir tuleb taastamisel testida, mitte eeldada FK olemasolust.

Ära ühenda `system_org_units`, `employment_departments` ja `property_nodes` üheks puuks: need täidavad eri rolle. Töötaja tähendab `system_users` kontot; leping ja patsiendikirje on eraldi objektid.

## 3. Migratsioonid ja olekukontroll

`SchemaInstaller::MIGRATIONS` on järjestatud register, mitte failide dünaamiline skannimine. Alates 001-st kontrollitakse kas marker-tabelit või SQL `rowCheck` olemasolu. Hilisemad migratsioonid kasutavad sageli versioonilogi koos skeemi/tõlke kontrolliga. Ainult failinimi ei tähenda migratsiooni rakendamist.

PHP erihandler'id parandavad vana skeemi enne vastavat SQL-faili:

1. 027: puuduvad `employee_schedule_entries.created_by` ja `updated_by`, nullable unsigned int.
2. 031: puuduv mallivärv `color_hex CHAR(7) NOT NULL DEFAULT '#64748B'`; värviallowlisti migratsioon korrastab väärtused.
3. 042: vajadusel töötaja-päeva indeks, vana unikaalse töötaja-päeva võtme eemaldamine ja lepingu-päeva unikaalsus.
4. 046: puuduv `employment_contracts.archived_at DATETIME NULL`.
5. 071: puuduvad `system_support_tickets.recipient_type ENUM('system','client_admin','manager') NOT NULL DEFAULT 'system'`, `closed_at DATETIME NULL` koos indeksiga `(closed_at,id)` ja `closed_by_user_id INT UNSIGNED NULL` koos kasutaja FK-ga `ON DELETE SET NULL`. Iga veerg kontrollitakse eraldi, et osaliselt rakendatud migratsiooni kordus ei tekitaks duplicate-column viga.
6. 081: loob `system_client_domain_embeds` tabeli ning lisab embed-mooduli kataloogi ja en/et/ru tõlkevõtmed. `SchemaInstaller` kontrollib versioonikirjet koos tabeli ja mooduli olemasoluga.
7. 082: lisab kliendiadministraatorite admin-vaate en/et/ru tõlkevõtmed; äriskeemi ega kasutaja/rolli tabeleid ei muuda.
8. 083: loob `employee_list_settings` (client_id INT UNSIGNED PRIMARY KEY, page_size SMALLINT UNSIGNED DEFAULT 10, kliendi FK CASCADE), lisab 12 en/et/ru tõlkevõtit. Installer kontrollib versiooni 1.96 ja tabeli olemasolu.
9. 084: loob `employment_contract_list_settings` (client_id INT UNSIGNED PRIMARY KEY, page_size SMALLINT UNSIGNED DEFAULT 10, kliendi FK CASCADE), viis en/et/ru tõlkevõtit. Installer kontrollib versiooni 1.97 ja tabeli olemasolu.
10. 085: lisab ainult versiooni 1.98 kirje, kinnistu harude sulgemine ei vaja skeemimuudatust.

SQL-taastepakett sisaldab nende samaväärseid kontrollitud DDL samme, mistõttu puhas skeem on taastatav ka PHP-installerita. SQL-i käitamine ei ole kogu ulatuses transaktsiooniline: MySQL DDL võib teha implicit commit'i. Installer eemaldab tervete `--` kommentaariridade sisu ja jagab laused semikoolonilt; see ei ole üldine SQL parser, stored procedure'ide või semikooloniga stringide lisamine nõuab eraldi otsust.

Versioon 1.76 lisab ainult versioonikirje, mitte uut äriskeemi. Registri failide täpne järjestus ja originaal-SQL on taastepaketis.

## 4. Vormid ja HTTP käitumine

### Kinnistu struktuuri harude avamine/sulgemine (1.98)

`views/panel/property/index.php` kasutab teenuse olemasolevat depth-first järjestatud täielikku loendit. Iga rida saab `property-node-{id}` ID ja `data-parent`; mittejuurread renderdatakse HTML `hidden` atribuudiga. Ainult lastega real on `type=button` avamisnupp, `aria-expanded=false`, `aria-controls` otseste laste rea-ID-dega ja asukoha nimega aria-label. +/− märgid on tõlkimisest välistatud. Native button annab Tab/Enter/Space toe.

Vaate lokaalne JavaScript kuulab ainult avamisnupu klikke ning uuendab ridu vanematest lasteni: laps on peidetud, kui vanem on peidetud või suletud. Vanema sulgemine peidab kogu alampuu, kuid ei lähtesta alamtaseme nupuolekuid; lehelaadimine lähtestab kõik suletuks. Olekut ei salvestata DB-sse/sessionisse/localStorage'isse. JavaScript on avamiseks vajalik. Muutmis-/lisamis-/järjestamisnupud ei käivita haru ümberlülitamist. Õigused ja täielikku puud kasutavad lepingud/graafik ei muutu.

### Töölepingute nimeotsing ja leheküljed (1.97)

- GET `/panel/employment/contracts` nõuab employment moodulit. `search`: trimmitud töötaja nimeosa, string kuni 191 märki. `page`: positiivne täisarv, vaikimisi 1. Vigane sisend 400; viimasest suurem number piiratakse viimasele lehele.
- `EmploymentContractService::listPage()` annab `contracts,total,page,pages,pageSize`. COUNT ja andmepäring kasutavad ühiseid `listJoins()` / `listFilter()` osi, sama kliendi/Level F enda konto filtrit ja `employee.full_name LIKE` seotud parameetrit. `%`, `_`, `!` on literaalid. Järjestus start_date DESC, full_name, id DESC. LIMIT/OFFSET on valideeritud täisarvud.
- `listPageSize()` / `saveListPageSize()` loevad/salvestavad kliendi seadistuse. Puuduv rida tähendab 10, vigane salvestatud suurus annab erindi. `listForClient()` ilma lisaparameetriteta jääb täielikuks. Lepingu päringu senised seosed, arhiivi-/graafikutunnused ja asukohateed säilivad.
- POST `/panel/employment/contracts/list-settings`: employment + client_admin + CSRF. `page_size` täisarv 1–100, vale väärtus suunatakse nähtava veateatega loendisse ilma kirjutuseta. Muu meetod 405 + Allow: POST, puuduv õigus/moodul/CSRF 403. Kliendi ID tuleb sessioonist, audit `employment.contracts.page_size_updated`.
- Otsing ja tavaloend kasutavad sama kliendiseadet, mis on töötajate nimekirja seadest sõltumatu. Ühine `views/panel/pagination.php` renderdab kõik leheküljenumbrid täpselt üks kord tabeli all, mitte foreach'i sees. Otsing säilib linkides ja uus otsing algab lehelt 1.

### Töötajate nimeotsing ja leheküljed (1.96)

- GET `/panel/users`: mooduli värav ja senine rollinähtavus. `search` peab olema string, trimmitakse ja piiratakse 191 märgiga; `page` positiivne täisarv, vaikimisi 1. Vigane sisend annab HTTP 400. Üle viimase lehe number piiratakse viimasele lehele.
- Teenus kasutab COUNT(*) ja loendipäringus samu kliendi/enda konto/nime filtreid. Nimeosa LIKE on seotud parameeter, `%`, `_` ja `!` escape'itakse `!` märgiga. Järjestus username,id ja valideeritud täisarvuline LIMIT/OFFSET. Teenus tagastab `users,total,page,pages,pageSize`; tühjal loendil total=0, page=pages=1.
- Page size tuleb kliendipõhisest tabelist, puuduv rida tähendab 10. Kehtib nii otsingule kui tavaloendile. `listForClient()` lisaparameetriteta jääb teiste vormide jaoks täielikuks.
- POST `/panel/users/list-settings` nõuab aktiivset employees moodulit, client_admin rolli ja CSRF-i. `page_size` peab olema täisarv 1–100; vigane väärtus suunab nähtava veateatega loendisse ilma muudatuseta. Muu HTTP meetod annab 405 + Allow: POST, õiguse/CSRF-i puudumine 403. Õige väärtus upsert'itakse ainult sessiooni kliendile ja audititakse `employees.page_size_updated`.
- Vaade sisaldab nimeotsingut, kliendiadministraatori seadistusvormi, tulemuste koguarvu ja kõigi lehtede numbreid. Lehelink säilitab otsingu, uus otsing algab esimeselt lehelt. Tühi tulemus annab selge teate; kasutaja sisend escape'itakse HTML-is.

Põhiliides on HTML, URL encoded või multipart POST ja tavalised ümbersuunamised, mitte REST API. Enamik haldusvorme kasutab sama GET/POST marsruuti; lülitid on POST. `id` on kirje ID, `client_id` tuleb klienditoimingutes sessioonist; admini kontrollitud kliendihalduses saab valida teise kliendi.

Iga muutmisvorm peab kandma `csrf_token` välja. Olemasolev teostus ei anna igale ebaõige meetodi/CSRF juhule ühtset 405/403 vastust: osa handler'eid lihtsalt ei muuda andmeid ja suunab tagasi. Ühtlustamist ei tohi eeldada taastatud algkäitumisena.

Sisselogimata kaitstud päring suunatakse `/login`; puuduva rolli/mooduli korral kasutatakse 403 HTML-vaadet. Tundmatu route või puuduva objekti vorm võib anda 404. Vigane graafikukuu annab 400. Vigane vorm kuvab vea ja säilitab mittesalajase sisendi; parooli ei kanta tagasi vormi.

### Kliendi domeeni iframe (1.94)

- Admin aktiveerib kliendi jaoks kataloogimooduli `client_domain_embed`. Kliendi `/panel/domain-embed` ja `/panel/domain-embed/download` nõuavad sisselogimist, aktiivset kliendi mooduliseost ning aktiivset `client_admin` rolli; klient tuleb alati sessioonist, mitte vormist.
- `POST /panel/domain-embed` väljad: `customer_domain`, `csrf_token`. Domeen normaliseeritakse väiketähtedeks, lubatud on DNS hostinimi, milles on punkt, ilma IP, skeemi, tee või pordita. Ühel kliendil on üks seadistus; domeen on globaalelt unikaalne. Iga edukas salvestus vahetab 64 hex-märgiga tokeni, nii et eelmine allalaaditud fail muutub kehtetuks.
- `GET /panel/domain-embed/download` saadab allalaaditava HTML-faili. See kuvab DemoIT CRM-i nime koos kliendi nimega ning iframe’i `https://crm.demoit.eu/embed?token=...` URL-i. Kliendiserverisse paigaldatakse staatiline fail (näiteks `index.html`); see ei ole koopia PHP-rakendusest ega andmetest.
- `GET/POST /embed?token=...`: token peab olema 64 väikest hex-märki ja vastama aktiivsele kliendile, kliendimoodulile ning kataloogile. Vastuse `Content-Security-Policy: frame-ancestors` lubab ainult `self` ja seadistatud `https://<domeen>`; lisaks väljastatakse `Referrer-Policy: no-referrer`. Token ei anna iseenesest kontot ega kliendiandmeid: POST autentib tavapärase kliendikoodi, kasutajanime ja parooliga läbi `AuthService::attemptLogin()`. Aktiivse teise kliendi sessioon saab 403.
- Embed-login säilitab CSRF vormi ja tokeni query-stringis. Sessiooniküpsis jääb `SameSite=Lax`; brauser, mis blokeerib kolmanda osapoole küpsised, ei pruugi iframe-sisselogimist säilitada. Kasutaja fallback avab tavapärase `/login?client_code=...` vahekaardi, kus kood on eeltäidetud, kuid kasutaja peab ise oma mandaadid sisestama. Embed-sessiooni logout suunab tokeniga embed-loginile tagasi.
- HTML-i kliendi nimi ja atribuudid escape'itakse; staatiline fail ei sisalda parooli, DB-saladust ega PHP-koodi. Embed-token on kliendi domeenile suunatud URL-i osa ja tuleb käsitleda avaliku, uuendatava embed-viitena. CSP kaitseb teistelt veebisaitidelt embedimist, kuid token ei ole ligipääsu- ega autoriseerimispiir kliendi töötajakontode asemel.
- Domeeni omandi DNS-/failikinnitust automaatselt ei kontrollita. Enne seadistamist peab kliendi administraator veenduma, et domeen on kliendi kontrolli all; vale domeeni usaldamine lisaks selle päritolu CSP lubatud embed-omanikuks.

### Kliendi administraatorite haldus süsteemiadministraatori vaates (1.95)

- `GET /admin/clients/view?id=<client_id>` hangib kasutajate ploki jaoks ainult valitud kliendi `system_user_roles` aktiivse `client_admin` seosega kontod. Konto enda `system_users.status` võib olla active/inactive ning kuvatakse eraldi. Tavakasutajaid ei lisata selle ploki loendisse.
- `GET/POST /admin/clients/users/create`: nõuab `require_system_role()`. `client_id` võetakse vormist/URL-ist ja klient kinnitatakse `ClientService::find()` abil. POST nõuab CSRF-i ja `username`, `full_name`, korrektset `email`, valikulist `phone`; rollivõtit kliendilt ei loeta. Kasutajanime maksimum on 100, täisnime/e-posti 191 ja telefoni 50 märki. Teenus loob ühe transaktsiooniga aktiivse kasutaja, `must_change_password=1` ning `client_admin` rolliseose. Ajutine parool on random genereeritud ning ainult sessiooni flash-is; see kuvatakse üks kord kliendi detailvaates, URL-i ei panda. Kasutajatunnus on kliendipõhiselt unikaalne.
- `GET/POST /admin/clients/users/edit`: `client_id` + `user_id` peab viitama aktiivse `client_admin` rolliseosega kasutajale. POST-i teostatakse sama kontroll teenuses uuesti, lisaks CSRF. Muudetakse ainult täisnime, e-posti ja telefoni; kasutajanime ega rolle ei muudeta. Vigane sisu renderdatakse escape'itult.
- `POST /admin/clients/users/reset-password` kontrollib samuti, et kasutaja oleks valitud kliendi administraator. Ajutine parool kuvatakse sessiooni kaudu ühekordselt, mitte URL query-stringis. Kõik haldusmarsruudid keelduvad admini rollita kasutajast.
- Migratsioon 082 lisab tõlkevõtmed ja versiooni 1.95, uusi tabeleid/veerge/indekseid ei ole. Kasutajate ja rollide andmed jäävad olemasolevasse mudelisse.

### Teavituste tehniline leping (1.86)

- `GET /panel/notifications`: tavavaade, `archive=1` vanemate arhiiv, `page` täisarv 1–100000 (vale väärtus 400). `listForUser(clientId,userId,archive=false,page=1)` loeb 51 rida, handler kuvab 50 ja tuvastab järgmise lehe olemasolu. Sama kliendi `user_id=session user OR NULL`, tulevikuajad välistatud. Tavavaate piir `created_at >= PHP now -30 calendar days`, arhiiv `<` sama piir. Lehtede järjekord `created_at DESC,id DESC`.
- `unreadCount(clientId,userId)` rakendab sama saajafiltrit ja kaasavat 24-tunni alumist piiri (`DateInterval PT24H`), ülemine piir now. Isiklik legacy is_read=1 või kasutaja lugemiskinnitus välistab loendamise. Broadcast ei pärine ühe teise kasutaja is_read väärtusest. `render_panel_page()` arvutab arvu igal paneeli renderdusel ja shell lisab positiivse arvu lingi paremale.
- `POST /panel/notifications/read`: `id`, `csrf_token`, `archive`, `page`. Vale meetod 405, CSRF 403, vale page 400, võõras/puuduv teade 404. `markRead(id,userId,clientId): bool` kontrollib kliendi/saaja filtrit ning lisab idempotentse `(notification_id,user_id,read_at)` kirje. Edu suunab samasse arhiivi/lehte. FK-d eemaldavad lugemisread vastava teate/kasutaja kustutamisel.
- Migratsioon 073 loob `system_notification_reads` PK `(notification_id,user_id)` ja kaks CASCADE FK-d, lisab en/et/ru tõlked ning versiooni 1.86. Olemasolevaid teateid ega legacy is_read ei muudeta. Uued isiklikud ja broadcast lugemised on kasutajapõhised. Lugemisoleku muutmine ei lisa auditit, nagu senises teostuses. Teenuse liiga väike/suur page annab erindi, mitte vaikiva normaliseerimise.
- Teated säilivad arhiivis tähtajatult. Admini saatmisloend jääb seniseks viimase 50 teate ülevaateks, kliendiarhiiv ei muuda saatmise õigusi ega transporti. Lehe avamine ei märgi automaatselt loetuks. Teema/sõnum HTML-escape'itakse ning jäetakse DOM-tõlkest välja.

### Toe vestluste tehniline leping (1.84)

**1.89 loendi väljund:** `listVisible()` lisab `is_unread` ja `has_unread_reply` 0/1 väljad. Private `unreadCondition()`/`unreadReplyCondition()` koostavad nii loendi kui menüü arvu ühisreegli. Loendi mõlemas CASE-is kasutatavate samade konto-ID-de PDO placeholder nimed on erinevad (native prepares). Closed annab mõlemaks 0. Jagatud `views/support/index.php` lisab `tr.fw-bold` vaid is_unread=1 korral ning teema kõrvale tõlgitava **New reply** või **Unread** badge'i, eelistades uut vastust. Kasutaja teema link jääb `data-no-translate` all, badge väljaspool, et nimed/sisu ei tõlgitaks aga tähis tõlgitaks. Detaili avamisel kaob märgistus ilma täiendava salvestusloogikata. Migratsioon 076 on kolm tõlke/versiooni lauset, skeem ei muutu.

**Kehtiv 1.88 täiendus, asendab allpool ajaloolise 1.87 ainult esmaavamise reegli:** `system_support_reply_reads` PK `(ticket_id,user_id)`, `last_reply_id INT UNSIGNED DEFAULT 0` ning ticket/user CASCADE FK-d. `unreadCount()` loendab lubatud sulgemata teema, kui mitte-looja esmaavamiskinnitus puudub **või** leidub teise konto vastus ID-ga üle selle kasutaja salvestatud piiri. Nii saavad ka loojad uutest vastustest numbri ja ühes ticketis mitu vastust annavad ühe ühiku.

`markOpened(...,lastReplyId=0)` säilitab esmaavamise aja ja tõstab vastuste piiri `GREATEST` upsert'iga. Detaili GET handler annab juba laaditud vastuste maksimaalse ID, mitte päringu lõpus värskelt lisandunud vastuse ID: lehe laadimise ajal juurde tulnud nägemata vastus jääb lugemata. Aegade sekunditäpsus/DST ei mõjuta vastuste lugemist. Hilinenud vana tab ei saa piiri tagasi viia. Vastuse saatmise transaktsioon salvestab saatjale esmaavamise ning uue vastuse ID piiri koos reply/auditiga; rollback taastab ka lugemisoleku. Teenuse markOpened kutsel peab nähtud vastuste ID olema usaldatud serveripoolse laadimise tulemus, mitte POST/GET sisend.

Migratsioon 075 loob piiritabeli ja tõlgib tooltip'i „Lugemata aktiivsed toe päringud”, versioon 1.88. Vanad esmaavamise kinnitused säilivad, vastuste lugemist ei oletata timestamp'ide alusel: vanad teiste saadetud vastused võivad kuni avamiseni lugemata olla. Uuenduse käigus ei nihutata ajatemplite väärtusi ega muudeta osaliste õigusi.

Alates **1.87** `SupportService::unreadCount(clientId,userId,systemWorkspace=false)` kasutab sama `accessCondition()` autoriseerimisfiltrit kui teema loend/detail. Loendab `status != closed`, `created_by_user_id != current user` ja puuduva `system_support_reads` kirje. SQL native-prepare parameetrite nimed creator/recipient/participant/author/reader on eri nimedega ka sama kasutaja ID puhul. Aktiivse konto ning süsteemivaates aktiivse süsteemirolli nõue kehtib ka loenduris.

`markOpened(ticketId,clientId,userId,systemWorkspace=false): bool` kontrollib teema ligipääsu ja teeb idempotentse avamiskinnituse, säilitades esimese `opened_at`. Lubamatu ID tagastab false, DB-vead kanduvad edasi. `handle_support_ticket()` märgib loetuks ainult autoriseeritud detaili **GET** päringul pärast sisu laadimist ning enne renderdust. Loend, invalid CSRF ja muud toimingud avamiskinnitusi ei kirjuta. Uusi vastuseid/kutseid lisav loogika vanu kinnitusi ei kustuta. Looja loetud olek on ID-põhine reegel ega nõua eraldi backfill'i.

Kliendi/admini renderdused arvutavad `unreadSupportCount` enne kesta renderdust, seega detaili avamisel kajastab sama lehe menüü juba vähendatud arvu. Shell näitab ainult positiivset arvu paremal ja tõlgitud tooltip'i, number ei läbi DOM-tõlget. Migratsioon 074 loob `system_support_reads` PK `(ticket_id,user_id)`, `opened_at DATETIME` ja ticket/user CASCADE FK-d, lisab en/et/ru tooltip'i ning versiooni 1.87. Varasemaid avamisi ei backfill'ita, sest need polnud salvestatud. Teemade õigusi, olekut ega vestlust ei muudeta ning 24h piir ei kehti toe loendurile.

- `GET /panel/support` ja `/admin/support`: `SupportService::listVisible(clientId,userId,systemWorkspace)` tagastab `open` ja `closed` loendid. Avatud `created_at DESC,id DESC`, suletud `closed_at DESC,id DESC LIMIT 10`. Paneel säilitab sessiooni kliendifiltri; admini vaade nõuab aktiivset süsteemirolli ja rakendab endiselt ticketi osaliste/süsteemi suuna filtrit.
- `GET/POST /panel/support/create`: `recipient_type=system|client_admin|manager`, `recipient_user_id` (ainult nimelise vastaja puhul), `subject`, `message`, `csrf_token`. `create()` valideerib aktiivse looja/sama kliendi, 1–191 märki teema, mittetühja kuni 65535-baidi sõnumi ning teenuse arvutatud vastajavaliku. Süsteemi suunal ignoreeritakse esitatud recipient ID-d. Edu tuple `[true,message,id]`, viga `[false,message]`; edu suunab uue teema juurde.
- `GET /panel/support/view?id=...` või `/admin/support/view?id=...`: autoriseeritud algsõnum, vastused ja kutsutud osalejad. `ticket()` annab osalise teema või `null`, lisab `can_manage`; kõrvalisele või puuduvale ID-le sama 404. `replies()` ja `participants()` kontrollivad ligipääsu ka teenuse tasemel.
- Mõlemas keskkonnas `POST .../support/reply|invite|close`: ID ja CSRF, reply puhul `message`, invite puhul `user_id`. Vale meetod 405, CSRF 403, ligipääsmatu ID 404, vigane/keelatud toiming 400 koos nähtava veaga. Ainult `act()` teenus kirjutab vestlusse, võtab teema `FOR UPDATE` luku ja kontrollib live õigusi/sulgemist. Nii ei saa sulgemisega konkureeriv reply/invite kirjutada juba suletud teemasse. Kõik kirjutused ja audit on samas transaktsioonis, DB/auditi erind teeb rollback'i ja kandub edasi.
- `recipientOptions()` pakub ainult aktiivseid sama kliendi administraatoreid ja täna kaasavate algus/lõppkuupäevadega arhiveerimata primary/temporary lepingute juhte. Olemasoleva nimelise vastaja ligipääs ei sõltu hilisemast rolli/lepingu kehtivusest. `inviteOptions()` pakub ainult ticketi kliendi aktiivseid kontosid, välistades looja/vastaja/juba kutsutud. SystemWorkspace pole kasutaja sisend, vaid route'i fikseeritud väärtus.
- Ticketi `message` jääb algsõnumiks, vastused on eraldi append-only tabelis: `ticket_id`, `user_id`, `message`, `created_at`; järjekord `created_at,id`. Osalejal on PK `(ticket_id,user_id)`, kutsuja ja aeg. Isikute nimed kasutavad `COALESCE(NULLIF(TRIM(full_name),''),username)`. Kuva HTML-escape'ib kõik sisendid ning jätab teemad, nimed ja sõnumid DOM-tõlkest välja (`data-no-translate`).
- `close` salvestab `status=closed`, PHP serveri `closed_at`, `closed_by_user_id`, `updated_at`; suletu ei ole taasavatav. Kutsutud osaleja saab lugeda/vastata, kuid mitte kutsuda/sulgeda. Audit: `support_ticket.created|reply|invite|close`, invite logib lisatud konto ID, sõnumi sisu auditisse ei kopeerita.
- Migratsioon 071 loob kaks tabelit, täidab vanade suletute puuduva sulgemisaja `updated_at`-ist, lisab en/et/ru tõlked ja versiooni 1.84. Puuduvad veerud lisatakse enne SQL-i PHP-handler'is ning SQL-only taastepaketis on sama DDL. Olemasolevaid teemasid/algtekste ei muudeta. Vana `/admin/support/status` lubab ainult turvatud sulgemist, mitte üldist staatusvahetust.
- Kliendi/süsteemi raportite sulgemata piletite koondarv säilitab senise statistikakäitumise; see ei anna koondarvu kaudu teema sisu vaatamisõigust. E-posti, manuseid, reopen'i ega kutsutute eemaldamist pole.

### Graafiku ühe lahtri salvestus

`POST /panel/schedule`, päis `X-Requested-With: XMLHttpRequest`, keha:

```text
csrf_token=<sessiooni token>
month=2026-10
assignments[<contract_id>][2026-10-14]=<template_id>
repeat_days=7
```

Tühi string või string `"0"` eemaldab selle lahtri kande. Puuduvad lahtrid **säilivad**: sisend on osaline muudatus, mitte terve kuu asendus. Tavaline automaatsalvestus saadab ainult valitud lahtri. Valikulise `repeat_days` (vaikimisi 1, lubatud 1–366) korral aktsepteeritakse ainult üht contract/date/template kirjet; ankur peab olema valitud kuus ja mall peab olema selle kliendi `exception`-tüüpi mall. Teenus laiendab selle järjestikusteks kuupäevadeks kaasa arvatud ankurpäev. Iga kuust väljuva päeva jaoks laaditakse sama lepingu autoriseeritud skoop eraldi kuus uuesti; kuutaseme ligipääsu puudumisel lükatakse kogu transaktsioon tagasi. Lepinguperioodi kuupäevad kontrollitakse iga päeva kohta; konfliktipäring katab laiendatud vahemiku ning ühe päeva kummaltki poolt, et tuvastada üleöö ulatuvaid vahetusi. Teise kliendi/mitteaktiivse malli kontroll ning atomic rollback säilivad. POST vastuse vorm ei muutu; kuu tunnikokkuvõte kajastab avatud kuu osa.

Edu 200:

```json
{
  "success": true,
  "message": "Schedule saved.",
  "hourSummary": {
    "123": {
      "plannedHours": 88,
      "temporaryPlannedHours": 8,
      "requiredHours": 80,
      "monthlyBalance": 8,
      "trimesterBalance": 12,
      "monthlyWorkloadId": null
    }
  }
}
```

Võti on lepingurida, mitte kasutaja ID. Tunnid on numbrid; ajutise lepingu `requiredHours`, `monthlyBalance`, `trimesterBalance` on NULL. `monthlyWorkloadId` on ID või NULL. PHP järjestikuste numbriliste võtmete serialiseerimine võib anda JSON massiivi ning tühi koond on `[]`; kliendi `Object.entries` peab toetama mõlemat. Näide ei luba alati objektikuju garanteerida.

Viga on `{"success":false,"message":"..."}`: vigane kuu 400; POST-i tokeni või teenuse valideerimisviga 422. Rolli/mooduli värav võib tagastada HTML-i ka AJAX päringule. Tavaline mitte-AJAX POST kasutab õnnestumisel ümbersuunamist sama kuu juurde.

### Kuukoormuse salvestus

`POST /panel/schedule/workload`, sama AJAX päis:

```text
csrf_token=<token>
month=2026-10
contract_id=123
workload_id=<aktiivse koormuse ID või tühi string>
```

Lubatud ainult nähtavale põhilepingule. Tühi workload eemaldab kuu erandi. Salvestub protsendi hetkekoopia; lepingu põhikoormus ja päevakanded ei muutu. Edu vastab samale `success/message/hourSummary` lepingule. Vale meetod/AJAX päis 405, CSRF või mittelubatud leping 403, vigane sisend 400, teenuse tõrge 422.

### Mallide ja kataloogide vormid

Mallivorm: `id`, `action`, `template_type`, `code`, `name`, `start_time`, `duration_hours`, `color_hex`. Tüübid `shift`, `exception`, `block`; kood 1–20 Unicode tähte/numbrit või `_`/`-`; nimi kuni 100 märki, tühi nimi asendatakse koodiga. Algus `HH:MM`, kestus 0,25–24 tundi, kuni kaks kohta, ümardatud minutiteks. Sama klient+tüüp+kood peab olema unikaalne. Värvid: `#FF0000`, `#FFA500`, `#FFFF00`, `#008000`, `#0000FF`, `#4B0082`, `#EE82EE`, `#000000`, `#FFFFFF`. Kasutuses olevat malli ei kustutata; peidetud senise kande säilitamine on lubatud.

Lepingute kataloogivormid kasutavad `titles`, `departments`, `workloads` valikut, kirje ID-d, nime ja action'it; koormusel protsent `0.01–100.00`, kuni kaks kohta. Tabelinimi valitakse teenuse whitelist'ist, mitte kasutaja antud SQL-identifikaatorist.

## 5. Lepingud ja graafiku arvutus

Lepingureeglid on [09](09-toolepingute-moodul.md); taastamisel säilita töötaja rea lukustus, kaasavad kuupäevad, muutmisel töötaja ja tüübi muutmatus ning hetkekoopiana protsent.

Kuugraafik valib aktiivsete kontode arhiveerimata primary/temporary lepingud, mis lõikuvad kuuga. Admin näeb kõiki; muu kasutaja näeb lepinguid, mille `manager_user_id` on tema ID. **Filter käib iga lepingu, mitte ainult töötaja põhilepingu järgi.** Erineva juhiga ajutine rida võib jääda teisele juhile nähtamatuks ja saldod on selle nähtava lepinguskoobi põhised.

Versioonis 1.82 lisab ühine privaatne `appendManagerScope()` teise haru: lepingu employee ID on sessiooni kasutaja ID JA samas kliendis leidub valitud kuuga lõikuv arhiveerimata primary/temporary leping, kus manager ID on sessiooni ID, töötaja on keegi teine ning tema konto aktiivne. Tavakasutaja ilma sellise alluvata enda õigust ei saa. Harud on sulgudes OR, kliendi/kehtivuse filtrite sees. PDO kohatäitjad on eri nimedega ka sama ID jaoks (native prepares ei luba nime kordamist). Sama filter rakendub loendile (ka FOR UPDATE salvestamisel), planned minutitele ja Tri-OT primary normile. Ajalooliste osakuude enda Tri-OT õiguse jaoks kasutatakse valitud kuu manager-kvalifikatsiooni; ajaloolisi alluvate lepinguid piirab endiselt vastava lepingu manager ID. HTTP F väravad jäävad enne teenuse kutset, admin möödub manager-filtrist. Koormuse handler kasutab sama nähtava loendi õigust enne teenuse salvestust.

Versioon 1.80 erand: `RoleService::isSelfServiceUser()` (aktiivne F ilma client_admin'ita) ei saa muuta kuugraafikut ega koormust, vaid ainult printida. Handler annab `monthData()` ja `listScheduleContracts()` valikulisele `locationEmployeeId` parameetrile sessiooni kasutaja ID. SQL `EXISTS` nõuab samas kliendis enda arhiveerimata primary/temporary lepingut, mis lõikub sama kuuga ja vastab rea property ID-le ning osakonnale (COALESCE NULL -> 0). Juhi filtrit selles prindiskoobis ei kasutata. Kanded küsitakse ainult tagastatud lepingu-ID-dele, mitte kogu kliendile. Lubatud grupi kaastöötajad on nähtavad. Arvutused arvestavad nende töötajate eri juhtidega lepinguid samamoodi kui administraatori prindis, kuid teisi asukoha lepinguridu/kandeid HTML-i ei saadeta. Põhirea kuu kogutunnid võivad senise koondloogika järgi hõlmata töötaja teistes töökohtades tehtud tunde; ajutine jaotus pärineb nähtavatest ajutistest ridadest.

F `/panel/schedule` GET -> `/panel/schedule/print?month=...`; POST -> 403 HTML või AJAX JSON `{success:false,message:"Forbidden"}`. `/panel/schedule/workload` -> 403 JSON ka enne CSRF/andmete kontrolli. Seaded/pühad nõuavad endiselt client_admin rolli. Print ei muuda andmeid ning PDF on olemasolev brauseri `window.print()`, mitte serveripoolne failigeneraator.

Lepingurea tööpäevad = kuu ja lepinguperioodi ühisosa E–R päevad, välja arvatud pühaliigid 1 ja 2. Nõutud tunnid = tööpäevad × 8 × protsent / 100, põhilepingul. Kuu erand asendab vastava põhilepingu kuu protsendi. Ajutise lepingu real nõutud tunde ei arvutata.

Planeeritud minutid arvutatakse mallide **praegusest** tüübist, algusest ja kestusest, mitte kande hetkel salvestatud koopiast. Malli muutmine võib muuta varasemate kuude saldosid. Shift loeb igal päeval; exception loeb ainult kande alguskuupäeva tööpäeval; block ei loe kunagi. Öise exception'i puhul otsustab alguspäev ka järgmisse päeva ulatuva osa loendamise.

UTC ajavahemikud väldivad suve-/talveaja vahetuse mõju kestusele; algus on kuupäev + TIME, lõpp algus + duration_minutes. Tavalisel kuu piiril arvestatakse kuu ajavahemikuga ühisosa. Eelmise kuu viimase päeva üleöö kanne tuleb järgmise kuu summasse, v.a kui eelmine kuu on aprill, august või detsember. Nendes perioodilõpukuudes jääb kuu sees alanud üleöö vahetus täies pikkuses lõppevasse perioodi.

Oluline tehniline erinevus: `shiftMinutesInMonth()` üksi arvutab järgmise kuu ajalise ühisosa ka perioodilõpu vahetusele. Ülekande välistab `plannedWorkMinutes()` päringu alguskuupäev, mis ei kaasa eelmist päeva pärast aprilli/augustit/detsembrit. Selle ärireegli test peab kontrollima kogu teenuse/query tulemust, mitte ainult minutite helper'it.

Põhilepingu Planned/Sum h koondab sama töötaja nähtavad põhi- ja ajutise lepingu minutid. Ajutine rida näitab ainult oma lepingu minuteid. Ajutine jaotus on positiivse mahu korral põhirea all. OT = Planned − Required. Tri-OT = valitud kuu lõpuni summeeritud kuu OT perioodides jaanuar–aprill, mai–august, september–detsember. Nimi ei tähenda kolmekuulist kvartalit. Tunnid ja saldod ümardatakse kahe komakohani; täisarv kuvatakse komakohtadeta, positiivsel saldol `+`.

Salvestus lukustab nähtavad lepingud, olemasolevad kanded ja mallid; muudab ainult sisendis antud päevi; kontrollib sama töötaja ajavahemikke ka eelmise/järgmise päeva ulatuses ning commit'ib alles pärast kattuvuskontrolli. Sama töötaja kattuvad planeeritud shift/exception intervallid keelatakse, täpselt kõrvuti vahemikud lubatakse. Mittetööpäeva exception ei ole planeeritud töö ja ei tekita teiste töövahetustega samasugust tööaja konflikti; **block** kontrollib ka sellise exception'i kattuvust. Kontroll ei piirdu sama lepingu või sama asukohaga. Tõrke puhul rollback; andmebaasi salvestusviga läheb PHP `error_log`-i, adminile võib lisanduda SQLSTATE ja andmebaasi number.

Kui töötajal on ühe kuu sees mitu järjestikust põhilepingut, saavad põhilepinguread töötaja kuu Planned kogusumma; see on olemasoleva algoritmi eripära, mitte dokumenteerimata põhilepingupõhine tunni jaotus.

### Graafikuasenduse tehniline leping (1.83)

`GET/POST /panel/schedule/substitutes` nõuab graafiku mooduleid, mitte organisation moodulit. Manageri kvalifikatsioon: mitte print-only F, samas kliendis teise aktiivse töötaja arhiveerimata põhi/ajutine leping, mis pole lõppenud; admin lubatud. Vorm: adminil original_user_id, kõigil substitute_user_id, starts_at/ends_at rangelt `YYYY-MM-DDTHH:MM`, reason; CSRF. Manageri POST original_user_id eiratakse ja kasutatakse sessiooni ID-d. Mõlemad kontod peavad aktiivsed ja samas kliendis, IDs erinevad, lõpp tulevikus ning pärast algust, manageri alluva leping peab lõikuma asendusperioodiga. Sama manager+asendaja kattuv periood lükatakse tagasi. Töölepingut asendajale ei nõuta.

Salvestus olemasolevasse system_substitutes: substitute_type=`schedule_manager`, status=`approved`, approved_by/at määramise tegija/aeg, delegated_permissions JSON `["schedule.shifts"]`, org_unit_id NULL. See JSON on info, mitte üldine permission-mootor. Vana create keeldub reserveeritud tüübist ka teise registriga. action=`end`, id -> ainult original manager/admin saab status ended. Audit: schedule.substitute_assigned, schedule.substitute_ended ja tavaline schedule.month_saved aktuaalse asendaja ID-ga.

`activeScheduleManagers()` loeb ainult sama kliendi approved schedule_manager ridu, starts_at <= rakenduse nüüd, ends_at > rakenduse nüüd, mõlemad kontod aktiivsed; print-only F original välistatud. Vahemikku hinnatakse igal päringul ilma cronita. Vorm näitab PHP ajavööndit; sama PHP date('Y-m-d H:i:s') väärtus antakse SQL-võrdlustele eri nimedega PDO parameetritena, vältides DB NOW ajavööndi erinevust ja native prepares parameetrinime kordamist. ScheduleService ühises SQL-skoobis OR delegation EXISTS nõuab uuesti samu aja/oleku/kliendi tingimusi ning manageri ID-d, mille jaoks asenduse õigused tegelikult kehtivad. Nähtav on algse manageri juhina määratud rida või tema enda rida sama kuuga kvalifitseeriva alluvuse EXISTS alusel. Edasidelegeerimist pole.

Delegatsiooni OR haru lisatakse ainult `valitud Y-m >= tegeliku serveriaja Y-m`; F-l ei lisata oma managerina põhiharusid. Tavalise manageri/admini iseseisvat varasemat õigust ei piirata. List, entries, saveMonth FOR UPDATE scope ja saldod kasutavad sama nähtavust; ajaloolised Tri-OT osakuud kasutavad valitud kuu autoriseerimisskoopi ainult arvutuseks, mitte ajalooliste päevade muutmise lubamiseks. HTTP lubab aktiivsel F asendajal kuuvaate; expiry järel taastub F print/POST403.

Kuukoormuse handler küsib loendi `includeDelegation=false` ja F keeld säilib. UI can_manage_workload vastab ainult algsele, mitte delegeeritud skoobile; puhta asendaja jaoks ei loeta koormuskataloogi. Vahetuste seadete/pühade route ja nav nõuavad jätkuvalt olemasolevat client_admin õigust. Graafikuasendaja määramine eeldab enda manageri rolli lepingus, mitte tema päritud delegatsiooni. F aktiivses prindis ühendatakse manageri read ja tema senise oma asukoha prindiskoobi read ID-de järgi, ilma dubleerimiseta.

## 6. Riigipühade XML-leping

`multipart POST /panel/schedule/holidays/import`, väli `public_holidays_xml`, CSRF; ainult graafiku administraator. `.xml`, kuni 2 MiB, tegelik HTTP-upload. Juurelement `xml`, 1–10 000 `row` elementi:

```xml
<xml>
  <row>
    <date>2026-12-25</date>
    <title>Esimene jõulupüha</title>
    <notes></notes>
    <kind>Riigipüha</kind>
    <kind_id>1</kind_id>
  </row>
</xml>
```

Kuupäev täpne `YYYY-MM-DD`, title 1–255 märki, kind 1–100 märki, notes kuni 65535 baiti, kind_id täisarv 1–255. DOCTYPE/ENTITY on keelatud, SimpleXML kasutab LIBXML_NONET. Kõik read valideeritakse enne transaktsiooni. Võti SHA-256: `date + NUL + title + NUL + kind_id`. INSERT IGNORE lisab ainult uusi kirjeid; sama võtmega olemasolevat notes/kind väärtust ei muudeta. Tulemuses added/existing, handler tõlgib kokkuvõtte ja auditeerib.

Pühad on **globaalsed**, kuigi importija on kliendiadministraator. Liigid 1 ja 2 on töövabad; 3 ja 4 ei vähenda normi, lühendatud tööpäeva tunde eraldi ei vähendata. Tühi paigaldus ei sisalda pühade kalendrit.

## 7. UI ja JavaScripti taastamise nõuded

Bootstrap 5.3.3 CSS/JS tuleb CDN-ist; lokaalset app.css-i ei ehitata. Päis/jalus on 75 px, kere flex-column, sisu kasvab; tööruumis menüü `col-12 col-md-3 col-lg-2`, sisu `col-12 col-md-9 col-lg-10`. Päise keelevalik POST-ib automaatselt, sisselogimisel näidatakse nime ja logout'i. Vormivigadel alert, loendites Bootstrap tabelid, action'id eraldi CSRF vormides.

Graafik: kuuvalik, asukoha/osakonna grupiread, kokku-lahti nupud, töötaja/koormuse rida, kuu päevad ja saldod. Grupeerimisvõti ekraanil tuleneb asukohateest + osakonnast; print kasutab asukoha ID + osakonna ID paari. Ruumi kuvamise piirang ei tähenda, et andmemudel keelaks ruumi valimise lepingule. Tabel on fixed layout ja kohaneb laiusega; päevavärv ja mallivärv eristuvad. Mittetööpäeva taust on roosa 50% läbipaistvusega.

Päevalahtri nupp avab Bootstrap modaali. Valikus Off ja ainult aktiivsed mallid; olemasolev peidetud mall on kandes endiselt nähtav. Valikul suletakse modaal ja saadetakse ühe lahtri automaatsalvestus; UI uuendab serveri saldosid. Salvestuse ebaõnnestumisel taastatakse eelmine lahtriseis ja kuvatakse veateade, mitte edukas näiv tulemus. Kuukoormuse modaal toimib analoogselt, lubab lepingu vaikeväärtusele naasmist. Poolelioleva salvestuse ajal peab UI vältima sama valiku vastuolulisi paralleelmuudatusi; grupi peitmine ei kustuta kandeid.

Print: gruppide mitmikvalik, kõik vaikimisi valitud, Select all/Clear selection ja `window.print()`. Valimata grupid ei prinditu. A4 landscape, 6 mm marginaal, iga grupp algab uuelt lehelt, pikk grupp võib jätkuda. Peidetakse CRM päis/jalus/menüü/vahelehed/valikukontrollid; säilivad kuu, grupi pealkiri, tänane kuupäev, võrdsed töötajaread, mallivärvid, üks Sum h veerg ja ajutine jaotus. Brauseri prindisätted (taustad, skaleerimine ja browser headers/footers) mõjutavad lõpptulemust; serveris PDF-i ei genereerita.

Tõlgitud kuunimi ja päevanumbri taustamärgistus prindilahtris kuuluvad kontrollitud tööpuusse, kuid olid auditi alguses kasutaja pooleliolev muudatus. Selle versiooni dokumentatsioon ei väida selle varasema väljalaske osa olevat; bitt-täpseks taastamiseks säilita vastav lähtekood koos lõplikult kinnitatud väljalaskega.
