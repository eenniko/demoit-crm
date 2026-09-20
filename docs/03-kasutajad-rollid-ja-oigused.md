# DemoIT CRM – kasutajad, rollid ja õigused

**Versioon:** 1.1

## 1. Rollide tasandid

### Peasüsteem

- Peaadministraator
- Arendaja
- Kliendihaldur
- Klienditugi

### Klient

- Kliendi peaadministraator
- A-tase
- B-tase
- C-tase
- D-tase
- E-tase
- F-tase
- Ajutine asendaja
- Vaataja

Rollid ja õigused peavad olema muudetavad, kuid süsteem peab tagama vähemalt ühe aktiivse peaadministraatori nii peasüsteemis kui iga kliendi all.

## 2. A–F hierarhia

- A: CEO, juhatus või kõrgeim juhtimine.
- B: jurist, kontrolli- või vastavusfunktsioon.
- C: osakonna või allüksuse juht.
- D: grupi juht.
- E: rühma juht.
- F: tavakasutaja, kelle alluvusse ei saa teisi määrata.

C, D ja E moodustavad alluvusstruktuuri. Kui madalama taseme juhti pole, pärandub vastutus ülemisele sobivale juhile ning süsteem kuvab hoiatuse.

## 3. Õiguste tüübid

Iga mooduli õigused peavad olema eraldi määratavad:

- vaatamine;
- lisamine;
- muutmine;
- deaktiveerimine;
- kinnitamine;
- eksport;
- alluvate haldamine;
- mooduli seadistamine;
- auditlogi vaatamine.

Õigus ei tohi olla ainult kasutajaliidese nähtavuse küsimus; server peab kontrollima iga toimingut.

## 4. Peaadministraatori reegel

Peaadministraatorit ei saa eemaldada, kui see jätaks süsteemi või kliendi ilma aktiivse peaadministraatorita. Enne deaktiveerimist tuleb määrata ja kinnitada uus peaadministraator.

Kasutaja kustutamise asemel kasutatakse deaktiveerimist. Säilivad kasutaja ID, loodud andmete seosed ja auditlogid.

## 5. Ajutine asendaja

Asendajaks võib olla sama isiku alluv, sama üksuse sama taseme kasutaja või otsene ülemus, kui tal on vajalikud õigused. Süsteem peab kuvama sobivuse põhjuse ja lubama määrata perioodi, tüübi, õigused, põhjuse ning kinnitajad.

Asendaja õigused lõppevad automaatselt perioodi lõpus või konto deaktiveerimisel. Kõik määramised ja asendajana tehtud toimingud logitakse.

## 6. Kinnitused

Kinnitust võivad vajada:

- peaadministraatori määramine;
- kasutaja deaktiveerimine;
- finants- või palgaandmete õigused;
- organisatsioonistruktuuri kriitiline muutmine;
- andmete eksport;
- mooduli aktiveerimine või sulgemine;
- B-taseme parandused.

## 7. Esimene kasutaja

Peasüsteemi algkasutaja luuakse esimesel turvatud seadistamisel. Kliendi esimene kasutaja luuakse kliendi loomise või esimese aktiveerimise voos. Algkasutaja peab seadma oma parooli ning tegevus logitakse.

## 8. Rollide haldus

Rolli nimi, kirjeldus, tasand ja õigused peab olema hallatav. Süsteemi kriitilisi rolle ei tohi kustutada, vaid neid saab deaktiveerida või muuta ainult sobiva kõrge õigusega kasutaja poolt.

## 9. Andmete ulatus

Iga õigus peab arvestama:

- aktiivset klienti;
- organisatsiooniüksust;
- kasutaja taset;
- moodulit;
- asendaja aktiivset perioodi;
- kinnituse staatust.

Kasutaja ei tohi saada teise kliendi andmeid isegi siis, kui ta kuulub mitmesse klienti.