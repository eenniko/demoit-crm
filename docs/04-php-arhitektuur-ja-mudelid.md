# DemoIT CRM – PHP arhitektuur ja mudelid

**Versioon:** 1.2

## 1. Tehniline alus

- Vanilla PHP (ilma raamistikuta, nt Laravel/Symfony)
- PDO (prepared statements) MySQL-i vastu suhtlemiseks
- MySQL
- Native PHP sessioonid autentimiseks
- Bootstrap kasutajaliidese (HTML/CSS/JS) jaoks
- Andmebaasipõhine tõlkelahendus (vt jaotis 7)

Konkreetne PHP versioon ja abiteegid (nt front controller, autoloader) tuleb dokumenteerida eraldi enne esimese koodi loomist ning kontrollida ühilduvust kasutatava hostiga.

## 2. Soovituslik kihiline arhitektuur

- Front controller / router: kõik päringud suunatakse ühte sisenemispunkti (nt `public/index.php`), mis marsruudib URL-i vastava handler'i juurde.
- Controllers/Handlers: päringu vastuvõtt, sisendi kaasamine ja vastuse koostamine.
- Request validation: sisendi valideerimise klassid või funktsioonid iga vormi/endpointi jaoks.
- Auth/Policy kiht: rollide ja õiguste kontroll enne iga toimingut.
- Services: äriloogika, mis ei sõltu HTTP kihist.
- Repositories/Queries: PDO-põhine andmepääsu kiht, kõik SQL-päringud prepared statement'itega.
- Models/Entities: lihtsad andmestruktuurid ja nendega seotud reeglid (ilma ORM-i "aktiivse kirje" mustrita).
- Events/Logging: keskne teenus auditlogi, teavituste ja versioonisündmuste jaoks.
- Jobs/Queue (valikuline): rasked või taustal tehtavad toimingud, nt cron-põhiselt käivitatavad skriptid.

## 3. Kliendikontekst

Pärast sisselogimist määratakse aktiivne klient serveripoolsesse PHP sessiooni (`$_SESSION`) või samaväärsesse turvatud konteksti. Kõik kliendipõhised päringud peavad kasutama sama konteksti. Kliendikoodi ei tohi usaldada otse URL-ist ega kasutaja sisendist.

Soovituslikud komponendid:

- `ClientContext` teenus, mis loeb ja valideerib aktiivse kliendi sessioonist;
- kliendikonteksti kontrolliv keskne funktsioon/middleware-laadne kiht, mida kutsutakse iga kaitstud päringu alguses;
- kliendipõhine õiguste kontrolli (Policy-laadne) kiht;
- keskne auditlogimise teenus;
- aktiivse mooduli kontroll enne mooduli sisu näitamist.

## 4. Autentimine

Sisselogimine kasutab kliendikoodi, kasutajatunnust ja parooli. Paroolid räsitakse `password_hash()` abil ja kontrollitakse `password_verify()` abil. Ajutise parooliga kasutaja suunatakse kohustuslikule paroolivahetusele.

Administraatori kood `13666` ei tohi automaatselt anda õigusi. Roll ja õigus tuleb kontrollida eraldi, iga päringu juures serveris.

Sessioonihaldus peab kasutama turvalisi seadeid (nt `session.cookie_httponly`, `session.cookie_secure` HTTPS-i puhul, sessiooni ID uuendamine pärast sisselogimist).

## 5. Algseadistus

Rakendus peab sisaldama ühekordset initial setup voogu:

1. kontrolli, kas peasüsteemi algkasutaja on olemas (`system_initial_setup` tabeli põhjal);
2. kui ei ole, luba ainult turvatud esmakordne seadistus;
3. loo esimene peaadministraator;
4. räsitud parool ja kinnitatud e-post;
5. märgi seadistus lõpetatuks;
6. blokeeri algseadistus edaspidi (kontroll iga päringu alguses).

Sama põhimõtet kasutatakse kliendi esimese peaadministraatori loomisel.

## 6. Moodulite arhitektuur

Moodulil on tehniline võti, nimi, kirjeldus, staatus, versioon, sõltuvused ja kliendi aktivatsioon. Kliendile kuvatakse ainult lubatud ja aktiivsed moodulid.

Iga moodul peab võimalusel määrama:

- marsruudid (registreeritud kesksesse routerisse);
- õigused;
- menüüelemendid;
- tõlkevõtmed;
- seadistused;
- demoandmete generaatori;
- SQL-migratsioonifailid (nummerdatud, korduskäivitamisel kontrollitavad).

## 7. Keeled

Inglise keel on kohustuslik põhikeel. Tõlkeotsing peab kasutama järgmist varumehhanismi:

1. kasutaja valitud keel;
2. kliendi vaikekeel;
3. inglise keel;
4. tehniline varuväärtus.

Kõik tõlked salvestatakse SQL-i ja loetakse PDO abil. Kliendi tõlked ei tohi mõjutada teisi kliente.

## 8. Andmebaasi strateegia

Kliendiprefiksiga tabelid, nagu `13666_translations`, on lubatud ainult pärast arhitektuurilise otsuse kinnitamist. Tabelinime koostamisel tuleb kasutada serveri poolt kontrollitud kliendikoodi whitelist'i või ranget numbrilist valideerimist – kliendikoodi ei tohi kunagi otse SQL-i stringi liita ilma valideerimiseta.

Eelistatud lahendus on ühised tabelid koos `client_id` väljaga või hübriid. Kui prefiksilahendus valitakse, tuleb luua klienditabelite tehase-/provisioning-skript ning iga kliendi loomisel hallatud migratsiooniprotsess.

Kõik SQL-päringud peavad kasutama PDO prepared statement'e (`bindParam`/`bindValue`) SQL-i süstimise vältimiseks.

## 9. Auditlogi

Auditlogi tuleb luua keskse logimisteenuse kaudu, mida kutsutakse iga olulise toimingu järel. Logida tuleb kasutaja, klient, toiming, objekt, vana väärtus, uus väärtus, IP, kasutajaagent, aeg ja vajadusel kinnitaja.

## 10. Versioonihaldus

Praegune arendusversioon on `1.2`. Iga täiendav uuendus suurendab viimast numbrit ühe võrra: `1.3`, `1.4`, jne. Iga versioon peab sisaldama dokumenteeritud muudatusi, migratsioonide infot, mõjutatud mooduleid ja vajadusel uuendamisjuhendit.

## 11. Testimine

Kohustuslikud testirühmad:

- autentimine;
- kliendieraldus;
- rollid ja õiguste kontroll;
- esimene seadistus;
- ajutine parool;
- moodulite aktivatsioon;
- keelte varuväärtus;
- peaadministraatori säilitamine;
- deaktiveerimine;
- auditlogi;
- mobiilne kasutajaliides.

## 12. Turvareeglid

Ära kasuta frontendi nähtavust turvana. Valideeri kõik sisendid serveris. Kasuta CSRF-kaitset (nt sessioonipõhine token igal vormil), räsitud paroole (`password_hash`), piiratud ja turvaliselt seadistatud sessioone, rate limiting'ut sisselogimisel ning serveripoolset autoriseerimist iga toimingu juures. Ära logi paroole ega tundlikke saladusi. Kasuta alati prepared statement'e, et vältida SQL-i süstimist.
