# Baasfunktsioonid ja tegeliku teostuse piirid

**Sihtversioon: 1.98.** See dokument täiendab moodulijuhendeid ning eristab olemasolevat teostust algsetest sihtnõuetest.

Versioon 1.98: kinnistu struktuuri juurhooned on alguses nähtavad, kõik alamtasemed suletud. Lastega real on +/− nupp. Vanema sulgemine peidab kogu alampuu, taasavamine säilitab samal lehel alamtasemete oleku; lehelaadimine lähtestab kõik suletuks. Muutub ainult loendi kuvamine, mitte teenuse täielik asukohapuu, lepingute valikud, õigused või andmed.

Versioon 1.97: töölepingute loendis on töötaja nimeosa otsing ja leheküljed. Ainult `client_admin` saab salvestada kliendile 1–100 lepingut lehel (vaikimisi 10), sõltumatult töötajate nimekirja seadest. Otsing kasutab sama lehesuurust, loendus järgib samu nähtavusfiltreid ning numbrid renderdatakse üks kord tabeli all. Kliendi ja enda konto piirangud, arhiivikirjed ning haldustoimingud säilivad.

**Versiooni 1.78 täpsustus:** kliendihalduse GET jääb kõigile sisseloginutele nähtavaks. Ilma aktiivse `client_admin` rollita kuvatakse andmed tavalise tekstina, mitte vormina: sisestusväljad ja salvestusnupp puuduvad. Ainult `client_admin` saab kasutada muutmisvormi; mitteadministraatori POST annab 403 enne salvestust. Sama täisnimi võib kuuluda eri kontodele ja rollidele, seega päise nimi ei tõenda konto rolli. Olemasolev autoriseerimine arvestab kõiki aktiivseid rolle, mitte pelgalt F-rolli olemasolu.

## 1. Avalik leht, seadistus ja konto

Avaleht `/` on lühike DemoIT CRM-i tutvustus koos sisselogimise ja sobiva tööruumi linkidega. Tegelik demo-marsruut, demoandmete generaator, avalik moodulikataloog ega kliendikoodi URL-fragmendiga sisselogimine puuduvad.

Esimene päring paigaldab skeemi. Kuni `system_initial_setup.id=1` ei ole lõpetatud, suunatakse päringud `/setup` lehele; keelevahetuse handler töötab enne seda kontrolli. Vorm nõuab nime, korrektset e-posti, kasutajatunnust ja vähemalt 8-baidist parooli. Teenus lukustab reserveeritud kliendi rea, loob aktiivse `system_admin` kasutaja kliendi `13666` alla, räsib parooli, märgib seadistuse lõpetatuks ja kirjutab pärast commit'i auditi. Edaspidi suunatakse `/setup` avalehele. E-posti kinnituskirja ega paigaldustokenit ei ole: esmakäivitus tuleb operaatoril välise ligipääsupiiranguga kaitsta.

`/login` võtab `client_code`, `username`, `password` ja CSRF-tokeni. Kasutajatunnus on unikaalne kliendi piires; sama inimese eri klientide kontod on eraldi `system_users` read, mitte ühine membership-mudel. Kontrollitakse parooli ning kliendi ja konto aktiivsust. Edu uuendab sessiooni ID-d ja salvestab kliendi/kasutaja konteksti; suunamine toimub `/` lehele. Päis näitab täisnime või selle puudumisel kasutajatunnust. `/logout` logib tegevuse, tühjendab sessiooni ja suunab `/` lehele.

Ebaõnnestunud katsete piirang on **sessioonipõhine**, võtmega kliendikood + kasutajatunnus: 5 ebaõnnestumist, 300 sekundit viimasest katsest. See ei ole IP-põhine ega kõiki sessioone ühendav piirang.

`/panel/account` kuvab konto ja paroolivahetuse vormi. Vaja on praegust parooli, uut vähemalt 8-baidist parooli ning sama kinnitust. Muudetakse räsi, `must_change_password=0` ja kirjutatakse audit. Ajutise parooli tunnus salvestatakse sisselogimisel ka sessiooni, kuid keskne sundsuunamine/paroolivahetuse värav **ei ole teostatud**. Nõuete dokumentide tugevam paroolivahetuse reegel on sihtnõue, mitte praeguse käitumise kirjeldus.

## 2. Tegelik autoriseerimine

| Tegevus | Serveripoolne kontroll |
|---|---|
| Kõik `/admin*` handler'id | Sisselogimine ja vähemalt üks rollidest `system_admin`, `developer`, `client_manager`, `client_support` |
| Baaspaneel, konto, teavitused, raportid, tugi | Sisselogimine |
| Aktiivsete moodulite leht | Sisselogimine; Level F ilma `client_admin` rollita 403 ja menüülink peidetud |
| Töötajate loend | Aktiivne `employees` moodul; Level F ilma `client_admin` rollita näeb ainult enda konto ID-ga kirjet |
| Töötajate loomine/muutmine/import/olek/parool | `employees` + `client_admin` |
| Organisatsiooni vaatamine, asendajate vaatamine/taotlemine | `organisation` |
| Organisatsiooni lisamine, asendaja kinnitamine/tagasilükkamine | `organisation` + `client_admin` |
| Patsiendi loend, lisamine ja oleku muutmine | `patients`; lisamiseks ei nõuta eraldi `client_admin` rolli |
| Kinnistu vaatamine | `property`; Level F ilma `client_admin` rollita 403 ja menüülink peidetud |
| Kinnistu loomine/nimi/järjestamine | `property` + `client_admin` |
| Lepingute vaatamine | `employment`; Level F ilma `client_admin` rollita näeb ainult enda `employee_id` lepinguid |
| Kataloogide vaatamine | `employment`; Level F ilma `client_admin` rollita saab GET/POST puhul 403, teistel säilib senine GET |
| Lepingute/kataloogide haldamine | `employment` + `client_admin`; lepinguvorm nõuab ka `property` |
| Graafik ja print, kuukoormuse muutmine | `schedule` + `employment` + `property`; Level F ilma `client_admin` rollita ainult print enda kuu lepingute asukoha/osakonna gruppidest, muudatused 403; teistel senine lepingupõhine ulatus |
| Graafiku mallid ja pühade import/loend | Eelmised moodulid + `client_admin` |
| Graafikuasenduse määramine/lõpetamine | Graafiku moodulid; manager määrab ainult enda asendaja, admin päris manageri asendaja; eraldi leht, mitte organisation taotlus |
| Kliendi domeeni embed-seadistus/allalaadimine | `client_domain_embed` + `client_admin`; kliendi ID võetakse sessioonist |

Versioon 1.82: manageri enda graafiku vaatamise/muutmise õigus lisandub ainult siis, kui tal on valitud kuul samas kliendis vähemalt üks aktiivne teine töötaja kehtiva arhiveerimata põhi-/ajutise lepinguga, millel ta on manager. Alluvate puhul jääb õigus konkreetse lepingu manager ID järgi; F print-only ja administraatori erand säilivad.

Versioon 1.83 erand: aktiivse `schedule_manager` asenduse ajal saab ka F sama manageri jooksva/tulevase kuu lepinguridade vahetusi muuta, kuid mitte koormusi ega seadeid/pühi. Asendajalt ei nõuta töölepingut, ainult aktiivset sama kliendi kontot. Enda kontorollid ja muu keskkonna nähtavus ei muutu. Õigus lõpeb automaatselt lõppajal või ennetähtaegsel lõpetamisel; vaata dokumente 10/12.

Versioon 1.93: erandi lisamisel saab rakendada sama erandimalli 1–366 järjestikusele kalendripäevale, valitud päev kaasa arvatud; vahemik võib ületada kuu piiri. Iga mõjutatud kuu kontrollib eraldi muutmisõigust ning iga kuupäev lepingu kehtivust ja kattuvust. Ühe päeva või kuu kontrolli ebaõnnestumine tühistab kogu salvestuse. Päevavahemikku toetavad erandid; vahetuse- ja Block-valikud jäävad ühepäevaseks. Täielik sisendi- ja HTTP-leping on dokumentides 10/12, regressioonikontrollid dokumendis 14.

Versioon 1.94: `client_domain_embed` moodul aktiveeritakse kliendile süsteemi moodulihaldusest. Ainult aktiivse mooduliga kliendi `client_admin` näeb `/panel/domain-embed` seadistusvaadet ja saab salvestada ühe avaliku hostinime ning laadida alla vastava HTML-i. Domeen peab olema täielik DNS hostinimi ilma skeemi, tee, pordi või IP-aadressita; kliendi domeenile paigaldatud leht peab kasutama HTTPS-i. Allalaaditud failis pole andmebaasi ega rakenduse saladusi, kuid avalik token on seal embed-URL-i osana. Token määrab kliendi, mitte kasutaja õigused: login kontrollib tavalist kliendikoodi, kasutajanime, parooli ja konto aktiivsust. Teenuse CSP lubab manustada ainult seadistatud HTTPS-domeenilt. Kolmanda osapoole sessiooniküpsiste blokeerimisel kasutatakse eraldi vahekaardi fallback'i. Detailne käitumine ja paigaldus on dokumentides 12/13.

Süsteemikasutajate rollidel ei ole praegu üksteisest eristatud admini tegevusõigusi. `system_permissions` ja `system_role_permissions` on skeemis, kuid üldist õiguste mootorit ega A–F õiguste pärandumist handler'ites ei ole. Rollide haldus muudab ainult kuvatavat nime; tehniline `role_key`, scope ja tasand jäävad samaks.

Mooduli värav kontrollib aktiivset kataloogikirjet ja aktiivset kliendi seost. Menüü nähtavus on abistav, mitte autoriseerimine. Baaspaneeli funktsioonid ei ole eraldi aktiveeritavad moodulid. Kliendi üldine seadete menüüelement on keelatud link; domeeni embed-seadistus on eraldi aktiivse mooduli all.

## 3. Süsteemiadministraatori ja kliendi haldus

Admini töölaud on tervitusvaade. Kliendiloend välistab `13666`, kuid teenuse nimele `listActive` vaatamata näitab ka mitteaktiivseid tavakliente. Kliendi detail näitab kliendi andmeid, töötajaid ja mooduleid; algnõuetes mainitud detailvaate eraldi keelte, organisatsiooni ja auditlogi vahelehed pole teostatud.

Kliendi loomise kohustuslikud väljad: `client_code`, `company_name`, esimese konto `username`. Kood peab olema 2–20 märki mustrist `[A-Za-z0-9_-]`, unikaalne ja mitte `13666`. Ülejäänud väljad: registrikood, aadress, telefon, e-post ja esindaja nimi/isikukood/telefon/e-post. Üks transaktsioon loob kliendi, aktiivse esimese kasutaja ja `client_admin` rolliseose. Ajutine parool on 12 juhuslikku kuueteistkümnendmärki + `Aa1!`; salvestatakse ainult räsi ja paroolivahetuse tunnus. Loomise tulemus näitab parooli; automaatset kirja kliendi loomise teenuses ei saadeta. Rolli valikut esimesele kasutajale pole.

Kliendipaneeli `/panel/client` lubab kõigil sisseloginutel andmeid vaadata; ainult `client_admin` saab muuta nime, aadressi, telefoni, e-posti ja esindaja nime/telefoni/e-posti. Kliendikoodi, registrikoodi ja esindaja isikukoodi sellel vormil ei muudeta. Kliendi deaktiveerimiseks ei ole eraldi UI marsruuti.

Admini moodulikataloogis saab lisada võtme (2–100 väiketähte/numbrit/alakriipsu), nime, kirjelduse ja demotunnuse ning muuta aktiivsust. See ei loo automaatselt mooduli PHP-koodi, marsruute ega sõltuvusgraafi.

Admini kliendi detailvaate kasutajate plokk kuvab alates versioonist 1.95 ainult valitud kliendi aktiivse `client_admin` rolliseosega kasutajakontosid (konto võib ise olla active või inactive, selle olek kuvatakse eraldi). Süsteemiadministraator saab luua täiendava aktiivse kliendiadministraatori; ajutine parool genereeritakse ning kuvatakse üks kord pärast loomist. Muutmine võimaldab muuta täisnime, e-posti ja telefoni; kasutajanimi on lukus. Parooli lähtestamine jääb eraldi toiminguks. Otsing/muutmine kontrollib nii kliendi ID-d kui kasutaja aktiivset `client_admin` rolliseost; teiste kasutajate ID ei ole nende marsruutidega muudetav. Migratsioon 082 lisab ainult en/et/ru UI tõlkeid, äriskeemi ei muuda.

Kliendi aktivatsioon toimub transaktsioonis: lukustatud moodulikirje, kliendiseose loomine/uuendamine, provisioneerimine, valmisoleku kirje, commit, audit. Mitteaktiivset kataloogimoodulit aktiveerida ei saa. Sulgemine säilitab kõik andmed ja märgib provisiooni mitteaktiivseks. `employees` provisioneerimine loob vaikimisi F-rolli seadistuse; teiste moodulite puhul registreeritakse valmisolek. Moodulite sõltuvusi kontrollivad kasutusmarsruudid, mitte aktivatsiooni üldteenus.

**Algandmete eripära:** koormuse 100% valik ja graafiku vaikemallid lisatakse migratsiooni käivitamise ajal olemasolevatele klientidele. Hiljem loodud kliendile nende loomist provisioneerimisteenus ei tee. Uue kliendi korral tuleb koormused ja mallid lisada kasutajaliideses; dokumentatsiooni põhjal taastamisel ei tohi lubada automaatset lisamist olemasoleva funktsioonina.

Süsteemiseadete leht on globaalne võtme-väärtuse register. Teenus teeb upsert'i ja auditkirje; üldseade ei muuda rakenduse käitumist, kui ükski komponent seda ei loe. Tööruumide juhtpaneelid ei kasuta automaatselt kõiki statistikateenuse mõõdikuid.

## 4. Töötajad ja e-post

Alates 1.96 kasutab `/panel/users` serveripoolset nimeosa otsingut ja nummerdatud lehekülgi. Vaikimisi on lehel 10 kontot. Ainult aktiivne `client_admin` saab loendi seadetes salvestada 1–100 kontot lehel; seade on kliendiülene ning kehtib filtreerimata ja otsingutulemustele. Koguarv ja päring järgivad samu nähtavusõigusi, sh Level F enda konto piirangut. Tühjale tulemusele kuvatakse teade. Teiste vormide töötajavalikud jäävad täielikuks.

Detailne mudel ja SQL-dumpi parser on dokumentides [06](06-tootajate-mooduli-kasutusjuhend.md) ja [07](07-tootajate-mooduli-ai-arendusjuhend.md).

Loomisel on nimi, töötajakood/kasutajatunnus ja korrektne e-post kohustuslikud, telefon valikuline. Konto luuakse mitteaktiivsena. Loomisvorm ei paku rolli ega organisatsiooniüksust; teenus toetab rollisisendit, vorm kasutab provisioneeritud vaikimisi rolli. Muutmisel saab muuta nime, e-posti, telefoni ja rolli, mitte kasutajatunnust; varasem organisatsiooniüksuse seos säilib, kui seda pole sisendis. Viimase aktiivse kliendiadministraatori deaktiveerimine või rolli eemaldamine keelatakse.

Aktiveerimine genereerib uue ajutise parooli ja proovib kirja saata. Reset muudab parooli ka siis, kui kiri ei jõua kohale. PHP `mail()` saadab ingliskeelse lihttekstkirja, saatja domeen tuletatakse HTTP hostist; SMTP-klienti, järjekorda ega kordussaatmist pole. Edukas `mail()` tagastus ei tõenda kohaletoimetamist.

Taastamisel arvesta teostuse ja juhendi erinevust: admini paroolireset suunab ajutise parooli `temp_password` päringuparameetris ning e-posti tõrke korral võib töötaja aktiveerimise/reset'i teade sisaldada parooli URL-is. Juhendi keeld saladusi URL-i panna on kvaliteedinõue, mitte praeguse teostuse garantii. Need URL-id ei sobi avalikesse logidesse, ekraanipiltidesse ega dokumentatsiooni.

Vana töötajaimport võtab `legacy_sql` faili: `.sql`, tegelik HTTP-upload, kuni 5 MiB. Käivitatakse ainult piiratud tekstiparser, **mitte üleslaaditud SQL**. `db_user_data` väljad teisendatakse: `db_users_id` → `username`, `cn` → `full_name`, `email`, `phone`, `created_at`, `updated_at`. Puuduv tunnus/nimi ja korduv tunnus jäetakse vahele; vigane e-post muutub NULL-iks. Uued kontod on mitteaktiivsed, juhusliku räsitud parooli ja vaikimisi rolliga. Import on transaktsiooniline ja annab found/imported/skipped kokkuvõtte; ei impordi vanu paroole ega rolle.

## 5. Patsiendid

Moodul `patients` annab loendi, lisamisvormi ja aktiivsuse lüliti; muutmis-, detail-, kliinilise raviloo-, manuse-, ekspordi- ja kustutamisvoogu pole.

Väljad: kohustuslik `full_name`; valikulised `personal_code`, `birth_date`, `phone`, `email`, `notes`. Teenus nõuab nime ning tühjad valikulised väärtused salvestab NULL-ina; kuupäeva lõpliku sobivuse määrab ka andmebaas. Konto loomisel on staatus `active`, lisaja ja ajad säilivad. Loend sisaldab nii aktiivseid kui mitteaktiivseid kirjeid, järjestusega nimi. Olekulüliti ei kustuta andmeid. Päringud piiratakse `client_id` järgi, loomine ja olekumuutus auditeeritakse.

## 6. Organisatsioon ja asendajad

`organisation` hõlmab nii üksusi kui asendajate taotlusi. Üksuse loomisel valitakse nimi, A–F tasand, valikuline vanem ja juht; staatus on `active`. Loend näitab tasandit, nime, vanemat ja juhti. Üksuste muutmise/kustutamise UI ning täielik tasandite pärandumis- või tsüklite kontroll puudub. Kinnistu ja lepingute osakondade kataloog on sellest puust eraldi.

Asendaja valik lähtub asendatava esimesest aktiivsest rolliseosest, millel on organisatsiooniüksus. Kandidaadid on aktiivsed sama kliendi kasutajad samast üksusest, vahetutest alamüksustest või vanemüksuse juht; asendatav ise eemaldatakse. Sama üksuse puhul ei võrrelda tegelikult kandidaadi A–F rollitasandit ja alluvusotsing ei ole rekursiivne. Organisatsiooniüksuseta kasutaja kandidaatide loend on tühi.

Taotluse sisend on asendatav, asendaja, tüüp (vaikimisi `temporary`), algus/lõpp, JSON-iks muudetav õiguste loend ja põhjus. Algus peab eelnema lõpule ning asendaja olema arvutatud kandidaatide hulgas. Loomine annab `pending`; administraator võib selle muuta `approved` või `rejected`, kinnitaja ja aeg salvestatakse.

Skeem lubab ka `active` ja `ended`, aga ajastatud aktiveerimis/lõpetamisprotsessi ega kinnitatud taotluse põhjal tegelikku õiguste delegeerimist ei ole. Taotlus ei tee asendajat automaatselt graafiku juhiks. Töötajate uuest vormist eemaldatud üksusevaliku tõttu sõltub kandidaatide töö varasematest rolli-üksuse seostest.

Ülal kirjeldatu käib vana organisatsiooni taotluse kohta. Versiooni 1.83 eraldi graafikuasendused kasutavad samas tabelis reserveeritud `schedule_manager` tüüpi, mida vana taotluse kaudu määrata ei saa. Graafikuleht määrab kohe approved oleku, kuid ligipääsu määrab alati tegelik starts_at/ends_at vahemik ja aktiivsed kontod. Üldist delegated_permissions õiguste mootorit ei rakendata.

## 7. Keeled ja tõlked

Globaalne keelekataloog sisaldab algandmetena `en`, `et`, `ru`; inglise keelt deaktiveerida ei saa. Uus kood on 2–10 väiketähte ja nimi kohustuslik. Keelevahetus `/language` on CSRF-kaitstud POST, salvestab aktiivse keele sessiooni ja suunab kohaliku `return_to` juurde 303-ga; sobimatu tagasitee asendatakse `/`-ga.

Tõlkevõtme loomine nõuab ingliskeelset väärtust ja valikulist moodulikonteksti. Admin saab valitud keele väärtusi upsert'ida ning puuduvaid aktiivseid väärtusi vaadata. Valitud aktiivse keele mittetühi väärtus → aktiivne ingliskeelne väärtus → funktsioonile antud fallback. Võti peab tõlkekataloogi sattumiseks omama aktiivset ingliskeelset väärtust. Tühja aktiivset tõlget ei näidata puuduvate loendis samal viisil kui puuduvat rida.

Osa päise/menüü tekste kasutab serveri `t()` helper'it. Staatilised ingliskeelsed tekstid kasutavad võtmeid `ui.` + ingliskeelse lähtefraasi SHA-256. Jaluse JavaScript tõlgib tekste ja `aria-label`, `placeholder`, `title`, `alt` atribuute ning jälgib lisatud DOM-sõlmi. `script`, `style`, `noscript`, `textarea`, `input`, `code`, `pre` ja `[data-no-translate]` alad on tekstisõlmede tõlkest välistatud. Kasutaja sisestatud andmed ei ole tõlkekataloogi alus.

Kliendipõhiseid tõlkeväärtusi, kliendi vaikekeele tabelit ega kasutajaprofiili püsivat keele-eelistust pole. Tõlked on ühised kõigile klientidele. SQL-taastepakett säilitab algse kataloogi täpsed väärtused; admini hilisemad muudatused vajavad andmebaasivarukoopiat.

## 8. Teavitused, raportid, tugi ja audit

**Teavitused (1.86):** admin valib kliendi, valikulise kasutaja, pealkirja ja sõnumi. Kasutaja puudumine tähendab kliendile ühist teadet. Paneeli tavavaade sisaldab viimase 30 päeva oma või kliendi ühisteateid; vanemad säilivad **Arhiivis**, neid ei kustutata. Mõlemad vaated on 50-kirjeliste lehtedega, uusim `created_at DESC,id DESC`, mistõttu ka rohkem kui 50 vana teadet on kättesaadav. Piirikuupäev kuulub tavavaatesse, arhiiv on sellest rangelt vanem; tulevikuajaga teateid ei kuvata.

Teavitused menüülingi parempoolne number loendab ainult selle kasutaja viimase 24 tunni **lugemata** teateid, sh kliendi ühisteateid. Nulli korral märki ei näidata. Lehe avamine ei märgi automaatselt loetuks: iga lugemata teate juures on **Märgi loetuks** nupp. Loetuks saab märkida nii isikliku kui ühisteate. Lugemisolek on kasutajapõhine (`system_notification_reads`), seega ühe inimese tegevus ei muuda teise inimese teadet ega loendurit. Vanad isiklikud `is_read=1` väärtused säilivad loetuna. Loomine auditeeritakse, lugemisoleku märkimine mitte. Loendur arvutatakse iga paneelilehe laadimisel; taustal automaatset polling'ut pole. 30 päeva/24 tunni filtreerimine kasutab salvestatud aja väärtust, vanade UTC ajatemplite eraldi parandamise piirang 1.85-st säilib.

**Raportid:** kliendile aktiivsete kontode, aktiivsete kliendimooduliseoste, aktiivsete organisatsiooniüksuste ja sulgemata tugipiletite arv. Mooduliarv ei arvesta kataloogi deaktiveerimist sama filtriga nagu menüü. Süsteemistatistika teenus annab aktiivsed tavakliendid, kataloogimoodulid, kontod, tänased edukad sisselogimised ja sulgemata piletid; kõik need ei ole admini töölaual nähtavad. CSV/PDF-raporti eksporti ei ole; graafiku PDF on brauseri printimine.

**Tugi (1.84):** aktiivne sisseloginud kasutaja, sh F ja töölepinguta konto, saab teha kohustusliku teema ja sõnumiga päringu. Vastaja valik:

- **Süsteemi administraator:** ühine süsteemi toe järjekord; olemasolevad aktiivsed `system_admin`, `developer`, `client_manager`, `client_support` rollid võivad lugeda, vastata, kutsuda ja sulgeda.
- **Kliendi administraator:** valitakse üks sama kliendi aktiivne konto aktiivse `client_admin` rolliga.
- **Töötaja manager:** valitakse üks sama kliendi aktiivne juht töötaja tänase seisuga kehtivalt arhiveerimata primary/temporary lepingult. Mitme juhi korral tehakse nimeline valik. Töölepinguta kontol seda vastajavalikut pole, kuid süsteemi/kliendi administraatorile saab kirjutada. Graafikuasendus ei lisa automaatselt toe manageri õigusi.

Loend sisaldab ainult kasutaja loodud, talle suunatud või kutse kaudu nähtavaid päringuid ning süsteemi vastajale süsteemi järjekorda. Kliendiadministraatori roll ei anna teiste privaatsete teemade vaatamist; süsteemi roll ei anna automaatselt kliendiadministraatorile/managerile suunatud teemade vaatamist. Õiguse alus on konto ID, mitte nimi. Näidatakse teema linki, looja nime (`full_name`, tühja korral username), olekut ja loomisaega. Süsteemi loend lisab kliendikoodi.

Teema avamisel kuvatakse algsõnum ja kronoloogiline vestlus koos autorinimede/ajaga. Looja, valitud vastaja ja kutsutud osalejad saavad vastata kuni sulgemiseni. Esimene vastus muudab `open` oleku `in_progress`-iks. Ainult looja/vastaja saavad lisada sama kliendi aktiivseid töötajakontosid (tööleping pole nõutud) või sulgeda. Süsteemi päringus on vastajaks toe meeskond. Kutsutud osalejal pole kutsumise/sulgemise õigust. Valitud vastaja jääb olemasoleva vestluse osaliseks ka rolli või lepingu hilisemal muutumisel; konto deaktiveerimine katkestab ligipääsu ning süsteemi järjekorra õigus sõltub endiselt aktiivsest süsteemirollist.

Sulgemine salvestab aja/kasutaja ja jätab kogu vestluse osalistele ainult loetavaks. Uuesti avamist, vastamist ega uute osalejate lisamist suletud teemasse pole. Avatud teemade all on eraldi **Viimati suletud päringud**: kuni 10 kasutajale lubatud teemat `closed_at DESC, id DESC` järgi. Vanema lubatud suletud teema otselink töötab endiselt. Vanad teemad jäävad süsteemi suunaga, algsõnum säilib ja suletute `closed_at` täidetakse senisest `updated_at`-ist; varem mittesalvestatud vastuseid ei saa taastada. Manuseid, e-posti/automaatteavitusi ega eraldi otsinguarhiivi pole. Tehniline leping ja test on dokumentides 12/14.

**Audit:** keskne teenus salvestab kasutaja, kliendi, tegevuse, objekti, valikulise vana/uue väärtuse, IP, kuni 255 märki user-agent'i ja aja. Mitte iga toiming ei salvesta täielikku vana/uue objekti koopiat. Admini loend näitab kuni 100 viimast kirjet, filtreerib tegevuse ja kasutajatunnuse osalist vastet ning täpset kliendikoodi. Auditi `approved_by` on skeemis, kuid keskse logifunktsiooni sisend ei täida seda. Audit on rakenduse kaudu lisamispõhine, mitte andmebaasitaseme muutmatu logi.

**Toe loendur (1.88, asendab 1.87 vastuste reegli):** kliendi ja süsteemi Tugi menüülingi paremal on kasutajale lubatud lugemata aktiivsete teemade arv. Aktiivne tähendab `open` või `in_progress`; suletud ei kuulu loendurisse ja ajalist 24 tunni piirangut ei ole. Algne looja loetakse loomisel kohe loetuks. Nimelise vastaja/kutsutu jaoks on teema lugemata kuni esimese avamiseni. **Uus vastus teeb teema teiste osaliste jaoks taas lugemata olekusse, ka algse looja jaoks.** Vastuse saatjale on teema loetud. Detaili avamine märgib selles vaates nähtud vastused loetuks, loendi vaatamine ei muuda midagi. Mitme lugemata vastusega ticket annab ühe ühiku. Süsteemi vastajatel on eraldi lugemisolek; ühe avamine ei eemalda teiste arvu. Nulli korral märk puudub, arv värskendatakse lehe laadimisel (polling'ut pole). Varasemaid vastuste lugemisi pole täpselt salvestatud: olemasolevad teiste saadetud vastused võivad olla lugemata kuni teema avamiseni pärast uuendust.

**Toe rea märgistus (1.89):** kasutaja lugemata aktiivse ticketi kogu tabelirida on paksu tekstiga. Kui lugemata on teise osalise vastus, kuvatakse teema kõrval **Uus vastus**; veel avamata algteema korral **Lugemata**. Read ja suletud teemad on tavatekstiga ilma märgistuseta. Teema avamise järel kaob märgistus sama lugemisoleku reegli alusel kui menüü number. See kehtib nii kliendi kui süsteemi toe loendis ning ei muuda olekuid, õigusi ega lugemisreegleid.

## 9. Teostamata sihtnõuete kokkuvõte

Ära taasta neid vaikimisi olemasoleva funktsioonina: demo, membership-mudel, dünaamilised klienditabelid/andmebaasid, üldine permission-mootor, A–F pärandumine, üldine organisatsiooni asendaja volitamine/lõpetamine (eraldi graafikuvahetuste asendus on 1.83 olemas), sundparoolivahetuse värav, e-posti kinnitamine, süsteemikasutajate täielik haldus, kliendi seadete leht, universaalne moodulisõltuvuste mootor, SMTP/queue/cron, kliiniline patsiendimoodul, eksport või automaattestide raamistik.

Taastamisel nõutud kliendieralduse ja veakäsitluse kontrollid võivad paljastada olemasoleva teostuse puudusi. Need tuleb registreerida kõrvalekaldena ning parandamise ulatus eraldi kinnitada, mitte dokumenteerida neid juba kontrollitud garantiina.
