# DemoIT CRM – Laravel arhitektuur ja mudelid

**Versioon:** 1.1

## 1. Tehniline alus

- Laravel
- PHP vastavalt Laraveli toetatud versioonile
- MySQL
- Eloquent ORM
- Laravel autentimine ja sessioonid
- Laravel localization või andmebaasipõhine tõlkelahendus

Enne konkreetse koodi loomist tuleb kontrollida kasutatava Laraveli versiooni dokumentatsiooni ja sõltuvuste ühilduvust.

## 2. Soovituslik kihiline arhitektuur

- Routes: ainult marsruutide määramine.
- Controllers: päringu vastuvõtt ja vastuse koostamine.
- Form Requests: sisendi valideerimine.
- Policies/Gates: õiguste kontroll.
- Services: äriloogika.
- Repositories või Queries: andmete lugemise koondloogika, kui vajalik.
- Models: andmete seosed ja lihtsamad reeglid.
- Events/Listeners: logimine, teavitused ja versioonisündmused.
- Jobs: rasked või taustal tehtavad toimingud.

## 3. Kliendikontekst

Pärast sisselogimist määratakse aktiivne klient serveripoolsesse sessiooni või turvatud konteksti. Kõik kliendipõhised päringud peavad kasutama sama konteksti. Kliendikoodi ei tohi usaldada otse URL-ist ega kasutaja sisendist.

Soovituslikud komponendid:

- `ClientContext` teenus;
- `ResolveClient` middleware;
- kliendipõhine Policy;
- keskne auditlogimise teenus;
- aktiivse mooduli kontroll.

## 4. Autentimine

Sisselogimine kasutab kliendikoodi, kasutajatunnust ja parooli. Parooli kontroll toimub Laravel Hash abil. Ajutise parooliga kasutaja suunatakse kohustuslikule paroolivahetusele.

Administraatori kood `13666` ei tohi automaatselt anda õigusi. Roll ja õigus tuleb kontrollida eraldi.

## 5. Algseadistus

Rakendus peab sisaldama ühekordset initial setup voogu:

1. kontrolli, kas peasüsteemi algkasutaja on olemas;
2. kui ei ole, luba ainult turvatud esmakordne seadistus;
3. loo esimene peaadministraator;
4. räsitud parool ja kinnitatud e-post;
5. märgi seadistus lõpetatuks;
6. blokeeri algseadistus edaspidi.

Sama põhimõtet kasutatakse kliendi esimese peaadministraatori loomisel.

## 6. Moodulite arhitektuur

Moodulil on tehniline võti, nimi, kirjeldus, staatus, versioon, sõltuvused ja kliendi aktivatsioon. Kliendile kuvatakse ainult lubatud ja aktiivsed moodulid.

Iga moodul peab võimalusel määrama:

- marsruudid;
- õigused;
- menüüelemendid;
- tõlkevõtmed;
- seadistused;
- demoandmete generaatori;
- migratsioonid.

## 7. Keeled

Inglise keel on kohustuslik põhikeel. Tõlkeotsing peab kasutama järgmist varumehhanismi:

1. kasutaja valitud keel;
2. kliendi vaikekeel;
3. inglise keel;
4. tehniline varuväärtus.

Kõik tõlked salvestatakse SQL-i. Kliendi tõlked ei tohi mõjutada teisi kliente.

## 8. Andmebaasi strateegia

Kliendiprefiksiga tabelid, nagu `13666_translations`, on lubatud ainult pärast arhitektuurilise otsuse kinnitamist. Tabelinime koostamisel tuleb kasutada serveri poolt kontrollitud kliendikoodi whitelist'i või ranget numbrilist valideerimist.

Eelistatud lahendus Laravelis on ühised tabelid koos `client_id` väljaga või hübriid. Kui prefiksilahendus valitakse, tuleb luua klienditabelite tehase-/provisioning-teenus ning iga kliendi loomisel hallatud migratsiooniprotsess.

## 9. Auditlogi

Auditlogi tuleb luua Event/Listener või keskse teenuse kaudu. Logida tuleb kasutaja, klient, toiming, objekt, vana väärtus, uus väärtus, IP, kasutajaagent, aeg ja vajadusel kinnitaja.

## 10. Versioonihaldus

Praegune arendusversioon on `1.1`. Iga täiendav uuendus suurendab viimast numbrit ühe võrra: `1.2`, `1.3`, jne. Iga versioon peab sisaldama dokumenteeritud muudatusi, migratsioonide infot, mõjutatud mooduleid ja vajadusel uuendamisjuhendit.

## 11. Testimine

Kohustuslikud testirühmad:

- autentimine;
- kliendieraldus;
- rollid ja Policies;
- esimene seadistus;
- ajutine parool;
- moodulite aktivatsioon;
- keelte varuväärtus;
- peaadministraatori säilitamine;
- deaktiveerimine;
- auditlogi;
- mobiilne kasutajaliides.

## 12. Turvareeglid

Ära kasuta frontendi nähtavust turvana. Valideeri kõik sisendid. Kasuta CSRF-kaitset, räsitud paroole, piiratud sessioone, rate limiting'ut ning serveripoolset autoriseerimist. Ära logi paroole ega tundlikke saladusi.