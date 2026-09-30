# DemoIT CRM - töötajate mooduli kasutusjuhend

**Juhendi versioon:** 1.0  
**Süsteemi arendusversioon:** 1.22  
**Sihtrühm:** süsteemiadministraatorid ja ettevõtte kliendiadministraatorid

## 1. Mooduli eesmärk

Töötajate moodul haldab ettevõtte töötajate kasutajakontosid, kontaktandmeid, ligipääsutasemeid ja konto olekut. Moodul sisaldab:

- töötajate nimekirja;
- uue töötaja lisamist;
- töötaja andmete ja ligipääsutaseme muutmist;
- konto aktiveerimist ja deaktiveerimist;
- ajutise parooli loomist ning e-postiga saatmist;
- administraatori algatatud parooli lähtestamist;
- vana `db_user_data` SQL-dumpi importi;
- ettevõttepõhist andmeruumi provisioneerimist mooduli aktiveerimisel.

## 2. Rollid ja ligipääs

Peaadministraator või sobiva süsteemse rolliga kasutaja aktiveerib mooduli ettevõtte detailvaates. Ettevõtte sees saab töötajaid hallata aktiivse `client_admin` rolliga kasutaja.

Tavakasutaja ei saa töötajaid lisada, muuta, aktiveerida, importida ega nende paroole lähtestada. Kõik kontrollid toimuvad serveris; menüü nähtavus ei ole ainus turvakontroll.

Ettevõttel peab alati säilima vähemalt üks aktiivne kliendiadministraator. Süsteem blokeerib viimase aktiivse administraatori deaktiveerimise või tema administraatorirolli eemaldamise.

## 3. Mooduli aktiveerimine ettevõttele

1. Logi sisse peaadministraatori keskkonda.
2. Ava **Clients** ja vali ettevõte.
3. Leia moodulite loendist **Employees**.
4. Vajuta **Activate**.
5. Kontrolli rohelist teadet, et moodul aktiveeriti ja ettevõtte andmeruum provisioneeriti.

Aktiveerimisel tehakse ühe andmebaasitransaktsiooniga järgmised toimingud:

- luuakse või aktiveeritakse ettevõtte ja mooduli seos;
- luuakse ettevõtte provisioneerimise kirje;
- luuakse ettevõtte töötajate mooduli seadistus;
- vaikimisi ligipääsutasemeks määratakse F.

Kui mõni toiming ebaõnnestub, pööratakse kogu aktiveerimine tagasi. Poolikult aktiveeritud moodulit ei jää.

Mooduli deaktiveerimine peidab töötajate menüü ja blokeerib marsruudid, kuid ei kustuta töötajaid ega ettevõtte seadistusi. Uuesti aktiveerimisel kasutatakse olemasolevaid andmeid.

## 4. Ettevõtte andmete eraldamine

DemoIT CRM kasutab ühist MySQL andmebaasi. Ettevõtetele ei looda eraldi MySQL kasutajat ega eraldi füüsilist andmebaasi.

Ettevõtte andmed eraldatakse `client_id` väärtusega. Mooduli aktiveerimisel luuakse sellele ettevõttele vajalik provisioneerimise ja seadistuse kirje. Kõik töötajate päringud peavad sisaldama aktiivse ettevõtte `client_id` filtrit.

## 5. Töötajate nimekiri

Pärast mooduli aktiveerimist ilmub C-paneli menüüsse **Employees & permissions**. Nimekirjas kuvatakse:

- töötaja tunnus ehk kasutajatunnus;
- töötaja nimi;
- e-post ja telefon;
- ligipääsutase või roll;
- konto olek;
- muutmise, parooli lähtestamise ja aktiveerimise toimingud.

## 6. Uue töötaja lisamine

1. Ava **Employees & permissions**.
2. Vajuta **Add employee**.
3. Sisesta töötaja nimi.
4. Sisesta töötaja tunnuskood. Sama väärtus on tema kasutajatunnus.
5. Sisesta kehtiv e-post ja soovi korral telefon.
6. Vali ligipääsutase ja vajaduse korral organisatsiooniüksus.
7. Vajuta **Create employee**.

Kui ligipääsutaset ei edastata, kasutatakse ettevõtte mooduli seadistuses määratud vaikimisi F-taset. Uus konto luuakse mitteaktiivsena. See võimaldab administraatoril andmed enne sisselogimise lubamist üle kontrollida.

## 7. Töötaja muutmine

Vajuta töötaja real **Edit**. Muuta saab nime, e-posti, telefoni, ligipääsutaset ja organisatsiooniüksust.

Töötaja tunnuskood/kasutajatunnus on muutmisvaates lukustatud, sest see on autentimise identifikaator. Viimase aktiivse kliendiadministraatori rolli ei saa eemaldada.

## 8. Konto aktiveerimine ja deaktiveerimine

Uue töötaja real vajuta **Activate**. Aktiveerimisel:

- konto olek muutub aktiivseks;
- luuakse uus ajutine parool;
- kasutaja peab pärast sisselogimist parooli muutma;
- sisselogimisandmed saadetakse töötaja e-postile.

Kui e-kirja saatmine ebaõnnestub, konto aktiveeritakse, kuid ajutine parool kuvatakse administraatorile üks kord. Parool tuleb kasutajale edastada turvalise kanali kaudu.

Deaktiveerimine keelab sisselogimise, kuid ei kustuta kasutaja andmeid ega auditlogi.

## 9. Parooli lähtestamine

Vajuta töötaja real **Reset password** ja kinnita toiming. Süsteem:

- genereerib uue ajutise parooli;
- asendab vana parooliräsi;
- nõuab järgmisel sisselogimisel parooli muutmist;
- saadab uued andmed kasutaja e-postile.

Saatmisvea korral kuvatakse ajutine parool administraatorile üks kord. Paroole ei salvestata auditlogisse ega kuvata hiljem uuesti.

E-kirja kohaletoimetamine sõltub serveri PHP `mail()` transpordi seadistusest.

## 10. Vana andmebaasi import

Vana süsteemi töötajate importimiseks:

1. Ava **Employees & permissions**.
2. Vajuta **Import legacy database**.
3. Vali phpMyAdmini `.sql` dump, mis sisaldab tabeli `db_user_data` `INSERT` ridu.
4. Vajuta **Import employees** ja kinnita toiming.

Nõuded failile:

- laiend peab olema `.sql`;
- maksimaalne suurus on 5 MB;
- fail peab sisaldama `db_user_data` tabeli `INSERT` ridu;
- vajalikud väljad on `db_users_id` ja `cn`;
- toetatud lisaväljad on `email`, `phone`, `created_at` ja `updated_at`.

Impordi teisendus:

| Vana väli | Uus väärtus |
|---|---|
| `db_users_id` | töötaja tunnus ja kasutajatunnus |
| `cn` | töötaja nimi |
| `email` | e-post, kui väärtus on kehtiv |
| `phone` | telefon |
| `created_at` | loomise aeg, kui kuupäev on kehtiv |
| `updated_at` | muutmise aeg, kui kuupäev on kehtiv |

Imporditud konto on mitteaktiivne ja saab ettevõtte vaikimisi ligipääsutaseme, algselt F. Vana parooli ei impordita. Sama ettevõtte olemasoleva kasutajatunnusega kirje ja sama faili korduv tunnus jäetakse vahele.

Üleslaaditud SQL-i ei käivitata. Rakendus parsib ainult täpselt `db_user_data` tabelile mõeldud `INSERT` read ning ignoreerib tabeli loomist, võtmeid, piiranguid ja muid SQL-käske.

Pärast importi kontrolli töötajate nimed ja kontaktandmed ning aktiveeri kontod ükshaaval. Aktiveerimisel luuakse igale kasutajale uus ajutine parool.

## 11. Auditlogi

Auditlogisse salvestatakse vähemalt:

- töötaja loomine ja muutmine;
- konto oleku muutmine;
- parooli lähtestamine;
- vana andmebaasi koondimport;
- mooduli aktiveerimine ja deaktiveerimine.

Ajutisi ega kasutaja valitud paroole auditlogisse ei salvestata.

## 12. Tõrkeotsing

**Employees menüü puudub**  
Kontrolli peaadministraatori ettevõtte detailvaates, kas moodul on ettevõttele aktiivne.

**Mooduli aktiveerimine ebaõnnestub**  
Kontrolli, kas migratsioon `008_module_tenant_provisioning.sql` on rakendunud ja `level_f` roll on olemas. Aktiveerimise veateade kuvatakse ettevõtte detailvaates.

**Import teatab, et moodul pole provisioneeritud**  
Deaktiveeri ja aktiveeri Employees moodul uuesti. See käivitab idempotentse provisioneerimise.

**Import ei leia ridu**  
Kontrolli, et dump sisaldaks kujul `INSERT INTO db_user_data (...) VALUES (...)` andmeridu.

**E-kiri ei saabu**  
Kontrolli serveri PHP `mail()` seadistust, saatja domeeni DNS/SPF/DKIM seadeid ja rämpsposti kausta. Saatmisvea korral kasuta administraatorile ühekordselt kuvatud ajutist parooli.

## 13. Seotud failid

- `src/Services/UserService.php`
- `src/Services/LegacyEmployeeImportService.php`
- `src/Services/EmailService.php`
- `src/Services/ModuleProvisioningService.php`
- `src/Services/ModuleService.php`
- `views/panel/users/`
- `sql/006_employee_users.sql`
- `sql/007_legacy_employee_import.sql`
- `sql/008_module_tenant_provisioning.sql`