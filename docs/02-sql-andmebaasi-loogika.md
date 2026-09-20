# DemoIT CRM – SQL-andmebaasi loogika

**Versioon:** 1.1

## 1. Põhimõte

Andmebaas on MySQL-iga ühilduv ning peab toetama Laravelit. SQL-juhend peab hiljem sisaldama otse phpMyAdminis käivitatavaid `CREATE TABLE`, indeksite ja välisvõtmete lauseid.

## 2. Süsteemi põhitabelid

Soovituslikud ühised tabelid:

- `system_settings`
- `system_clients`
- `system_modules`
- `system_module_versions`
- `system_languages`
- `system_translation_keys`
- `system_translation_values`
- `system_users`
- `system_roles`
- `system_permissions`
- `system_role_permissions`
- `system_user_roles`
- `system_audit_logs`
- `system_version_logs`
- `system_initial_setup`

Kõigil tabelitel peavad olema sobivad indeksid, `created_at` ja `updated_at` väljad ning vajalikud välisvõtmed.

## 3. Kliendiprefiksid

Kavandatud kliendipõhine prefiks on `{client_code}_`, näiteks `13666_translations`. Kood `13666` on reserveeritud peaadministraatori/arenduse keskkonnale.

Enne realiseerimist tuleb kinnitada, kas kasutatakse:

1. eraldi prefiksiga tabeleid iga kliendi jaoks;
2. ühiseid tabeleid `client_id` väljaga;
3. hübriidlahendust.

Laravel + MySQL hooldatavuse seisukohalt tuleb eelistada ühiseid tabeleid või hübriidi, kuid kui prefiksilahendus kinnitatakse, peab rakendus tabelinimed serveris valideerima ja koostama ainult usaldatud kliendikoodist.

## 4. Kliendi andmed

Kliendi põhiandmetes peab olema ettevõtte nimi, registrikood, aadress, telefon, e-post, staatus ja unikaalne kliendikood. Kliendi füüsilist kustutamist ei kasutata; klient deaktiveeritakse.

## 5. Kasutajad ja liikmelisused

Üks isik võib olla seotud mitme kliendiga. Soovituslikult eristatakse:

- isik;
- autentimiskonto;
- kliendisuhe ehk membership;
- kliendipõhine roll, organisatsiooniüksus ja õigused.

Sama kasutajatunnus võib olla eri klientide kontekstis lubatud, kuid autentimine peab alati kasutama kliendikoodi, kasutajatunnust ja parooli.

## 6. Keelemoodul

Inglise keel on kohustuslik süsteemi põhikeel. Igal tõlgitaval väärtusel peab olema ingliskeelne väärtus. Teise keele puuduv väärtus langeb tagasi ingliskeelsele väärtusele.

Vajalikud andmed:

- keele kood ja nimi;
- aktiivsus ja vaikekeel;
- tõlkevõti;
- kontekst või moodul;
- ingliskeelne põhiväärtus;
- teise keele väärtus;
- tõlke olek;
- muutja ja muutmise aeg.

Tõlkeid ei kustutata füüsiliselt; keel või tõlge deaktiveeritakse.

## 7. Moodulid

Moodulite tabelid peavad toetama mooduli nime, tehnilist võtit, staatust, versiooni, sõltuvusi, demoolekut ja kliendile aktiveerimist. Kliendi mooduli deaktiveerimine ei kustuta mooduli andmeid.

## 8. Õigused ja organisatsioon

Andmemudel peab toetama rolle, õigusi, A–F organisatsioonitasemeid, osakondi, gruppe, rühmi, juhte ja alluvusi. Õiguste tüübid on vähemalt vaatamine, lisamine, muutmine, deaktiveerimine, kinnitamine, eksport ja seadistamine.

## 9. Asendajad

Vajalikud andmed:

- asendatav kasutaja;
- asendaja;
- klient ja organisatsiooniüksus;
- algus ja lõpp;
- asendamise tüüp;
- delegeeritud õigused;
- põhjus;
- staatus;
- kinnitused;
- tegevuslogi.

Sobivad asendajad tuleb arvutada sama üksuse, sama taseme, alluva või otsese ülemuse reeglite põhjal.

## 10. Audit ja versioonid

Kõik olulised muudatused logitakse: kes, millal, millise kliendi all, millist objekti muutis, vana väärtus, uus väärtus ja kinnitusinfo.

Süsteemi arendusversioon algab väärtusest `1.1`. Iga täiendav uuendus suurendab viimast numbrit ühe võrra: `1.2`, `1.3` jne. Versioonimuudatused salvestatakse `system_version_logs` tabelisse ja dokumenteeritakse `CHANGELOG.md` failis.

## 11. Algseadistus

`system_initial_setup` peab näitama, kas peasüsteemi algkasutaja on loodud, kes selle lõi, millal seadistus lõpetati ja kas algseadistus on lukustatud. Algkasutaja loomine peab olema ühekordne turvatud tegevus.

## 12. SQL-skripti nõuded

Lõplik SQL-fail peab:

- määrama UTF-8/`utf8mb4` kodeeringu;
- looma tabelid õiges järjekorras;
- kasutama InnoDB-d;
- lisama primaar- ja välisvõtmed;
- lisama unikaalsed indeksid kliendikoodile, keelekoodile ja mooduli võtmele;
- olema korduskäivitamisel kontrollitav;
- sisaldama kommentaare;
- mitte kustutama olemasolevaid tootmisandmeid ilma eraldi kinnituse ja migratsioonita.