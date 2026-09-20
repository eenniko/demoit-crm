# DemoIT CRM – projekti- ja disaininõuded

**Dokumendi versioon:** 1.2  
**Süsteemi arendusversioon:** 1.2  
**Põhikeel:** English  
**Tehniline alus:** raamistikust sõltumatu veebilahendus, SQL/MySQL ja serveri-/rakenduskihi kokkulepitav teostus

## 1. Eesmärk

DemoIT CRM on modulaarne ettevõtte haldussüsteem, mis sobib eri tüüpi ettevõtetele. Klient saab aktiveerida ainult vajalikud moodulid ning aktiivsed moodulid peavad omavahel andmeid ja töövooge jagama.

Süsteem koosneb avalikust veebilehest, C-panelist ehk Client Panelist ning peaadministraatori/arenduse keskkonnast. Konkreetset veebiraamistikku, ORM-i või programmeerimiskeelt ei määrata selles etapis kohustuslikult.

## 2. Keskkonnad

### Avalik veebileht

Aadress: `www.demoit.eu`

Avalik veebileht tutvustab kogu lahendust, mooduleid, demo kasutamist ja süsteemi võimalusi. Avalik sisu ei tohi kuvada päris klientide andmeid.

### C-panel

Kasutaja siseneb kliendikoodi, kasutajatunnuse ja parooliga. Pärast autentimist kuvatakse ainult aktiivse kliendi ja kasutaja õigustega seotud moodulid ning andmed.

Kavandatud kliendivaate aadress võib olla `www.demoit.eu/#kliendinumber`, kuid URL-i fragmenti ei tohi kasutada turvakontrollina. Tegelik kliendikontekst peab tulema serveripoolsest autentimisest ja sessioonist või samaväärsest turvalisest autentimismehhanismist.

### Peaadministraatori keskkond

Reserveeritud kliendikood on `13666`. See kood ei tohi olla tavakliendile määratav. Kood üksi ei anna õigusi; ligipääs peab sõltuma aktiivsest administraatorikontost ja rollidest.

## 3. Ühine kujundus

Avalik veebileht ja C-panel kasutavad sama visuaalset keelt.

- Päis: 100% laius, 75 px kõrgus.
- Jalus: 100% laius, 75 px kõrgus.
- Päis sisaldab logo, süsteemi nime, avalikke linke, keelevalikut ja C-paneli autentimise olekut.
- Pärast sisselogimist avalikud lingid kaovad ning kuvatakse kasutaja nimi ja väljalogimine.
- Päise ja jaluse vahel on põhisisu.
- Põhisisu jaguneb vasakuks peamenüüks ja parempoolseks tööruumiks.
- Parempoolne tööruum jaguneb valitud mooduli alammenüüks ja sisuks.

## 4. C-paneli struktuur

Vasak menüü kuvab ainult kliendile aktiveeritud ja kasutajale lubatud mooduleid. Paremal kuvatakse valitud mooduli alammenüü ja lubatud sisu.

Kliendi põhiosad:

- töölaud;
- kliendi haldus;
- kasutajad ja õigused;
- organisatsioon;
- aktiivsed moodulid;
- raportid ja statistika;
- seadistused;
- tugi.

Kõik ligipääsud tuleb kontrollida serveris või samaväärses usaldusväärses rakenduskihis, mitte ainult kasutajaliideses.

## 5. Peaadministraatori menüü

- Dashboard
- Kliendilahendused
  - Kliendinimekiri
  - Lisa klient
- Moodulid
  - Kõik moodulid
  - Arendatud moodulid
  - Mooduli seadistused
  - Moodulite versioonid
- Dev-moodul
  - Keelemoodul
    - Keeled
    - Tõlked
    - Puuduvad tõlked
    - Keele seadistused
- Kasutajad ja õigused
  - Peaadministraatorid
  - Arendajad
  - Kliendihaldurid
  - Klienditugi
  - Rollid
  - Õigused
- Süsteemi logid
- Teavitused
- Süsteemi seaded

Kliendi detailvaade avaneb kliendinimekirjast ning sisaldab kliendi andmeid, mooduleid, kasutajaid, organisatsiooni, keeli, tõlkeid ja tegevuslogi.

## 6. Kliendi loomine

Uue kliendi andmed:

- ettevõtte nimi;
- registrikood või muu unikaalne kliendikood;
- aadress;
- kontakttelefon;
- kontakt-e-post;
- esindaja nimi;
- esindaja isikukood;
- esindaja telefon ja e-post;
- kasutajatunnus;
- esmane roll.

Süsteem genereerib ajutise parooli. Esimesel sisselogimisel peab kasutaja määrama uue parooli; ajutise parooliga tavavaatesse ei pääse.

## 7. Algkasutajate loomine

Süsteemi esmasel käivitamisel ei tohi eeldada, et peaadministraator või kliendi kasutajad on käsitsi andmebaasi valmis loodud. Rakendus peab tuvastama algseadistuse oleku ja kuvama ühekordse turvatud seadistusvoo.

- Peasüsteemi esimene seadistaja loob esimese peaadministraatori.
- Esimene peaadministraator saab süsteemi kõrgeima rolli.
- Kliendi esimene kasutaja luuakse kliendi loomise protsessi käigus või kliendi esimese aktiveerimise seadistusvoos.
- Igal kliendil peab alati olema vähemalt üks aktiivne kliendi peaadministraator.
- Algseadistus lukustatakse pärast edukat lõpetamist.
- Algkasutaja loomine, kinnitamine ja hilisemad muudatused logitakse.

## 8. Demo

Demo on avalik vaaterežiim. Demoandmeid ei saa lisada, muuta ega kustutada. Näidisandmed genereeritakse mooduli loogika põhjal, peavad olema omavahel seotud ja ei tohi sisaldada päris isikuandmeid.

## 9. Kvaliteedi- ja turvanõuded

Süsteem peab olema mobiilis kasutatav, ligipääsetav, turvaline, hooldatav ja laiendatav. Paroole säilitatakse ainult turvaliselt räsituna. Kasutaja, kliendi, mooduli, õiguste ja andmete muudatused lähevad auditlogisse. Füüsilise kustutamise asemel kasutatakse deaktiveerimist, kui andmete ja logide säilitamine on vajalik.

## 10. Arenduse tööjärjekord

1. Kinnita struktuur ja andmemudel.
2. Vali ja dokumenteeri rakenduse tehniline teostus eraldi otsusena.
3. Loo algseadistuse ja autentimise loogika.
4. Loo peaadministraatori keskkond.
5. Loo kliendi- ja moodulihaldus.
6. Loo keelemoodul.
7. Loo C-paneli baasstruktuur.
8. Lisa moodulid järk-järgult.
9. Testi kliendieraldust, õigusi, mobiilivaadet ja logisid.

Ära loo enne koodi, kui vastava etapi struktuur ja nõuded on kinnitatud.