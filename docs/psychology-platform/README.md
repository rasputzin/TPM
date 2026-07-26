# Platformă de testare psihologică integrată în WordPress/Divi — Etapa 1: Analiză și proiectare

Acest folder conține livrarea Etapei 1 (analiză și proiectare), solicitată înainte de orice
implementare de cod. Nu conține plugin funcțional. Nu conține și nu trebuie interpretat ca
incluzând conținut psihometric protejat (itemi, chei de cotare, etaloane, texte de interpretare).

## Index documente

| # | Document | Conținut |
|---|----------|----------|
| 1 | [01-comparatie-arhitecturi.md](01-comparatie-arhitecturi.md) | Comparația celor 4 arhitecturi posibile |
| 2 | [02-arhitectura-recomandata.md](02-arhitectura-recomandata.md) | Arhitectura recomandată, diagrama componentelor, fluxul unei evaluări |
| 3 | [03-schema-baza-de-date.md](03-schema-baza-de-date.md) | Schema inițială a bazei de date |
| 4 | [04-roluri-si-permisiuni.md](04-roluri-si-permisiuni.md) | Modelul rolurilor și permisiunilor |
| 5 | [05-threat-model.md](05-threat-model.md) | Threat model |
| 6 | [06-gdpr-guvernanta-date.md](06-gdpr-guvernanta-date.md) | Matricea GDPR și guvernanța datelor |
| 7 | [07-structura-repository.md](07-structura-repository.md) | Structura repository-ului / pluginului |
| 8 | [08-roadmap-implementare.md](08-roadmap-implementare.md) | Roadmap MVP → V1 profesională → V2 SaaS |
| 9 | [09-tehnologii-si-biblioteci.md](09-tehnologii-si-biblioteci.md) | Tehnologii, biblioteci, criterii alegere PDF |
| 10 | [10-informatii-necesare-inainte-de-primul-test.md](10-informatii-necesare-inainte-de-primul-test.md) | Ce trebuie să îmi furnizezi înainte de a introduce BDEFS/MCMI-3 |

---

## 1. Reformularea cerințelor

Obiectivul este o **platformă profesională de testare psihologică**, livrată ca **plugin
WordPress custom, modular și versionabil**, integrată vizual în site-ul existent (Divi), dar
independentă funcțional de temă. Platforma trebuie să:

- gestioneze **instrumente psihologice** (definiție, versiuni, itemi, scale, reguli de cotare,
  etaloane) ca date administrate, nu ca reguli hard-codate per test;
- permită **administrarea securizată** a testelor către beneficiari (link unic, token puternic,
  expirare, limită de accesări, reluare controlată);
- ofere o **interfață de completare** simplă, responsive, cu salvare automată a progresului;
- **coteze automat** exclusiv server-side, conform cheilor autorizate, fără a expune vreodată
  logica de cotare sau conținutul protejat către browser;
- separe strict **scorul calculat** de **interpretarea profesională**, raportul fiind întotdeauna
  `draft` până la validarea explicită a psihologului;
- genereze **rapoarte PDF** profesionale, cu identificare, versiuni, watermark „DRAFT” înainte de
  validare și acces strict autentificat;
- gestioneze **clienți, profesioniști, roluri, consimțăminte, audit și retenție** conform
  principiilor GDPR și privacy-by-design;
- fie **extensibilă** la noi instrumente fără rescriere, și **pregătită arhitectural** (dar nu
  neapărat implementată acum) pentru evoluție spre multi-organizație / SaaS.

Primele două instrumente vizate pentru implementare sunt **BDEFS** și **MCMI-3**. Niciun conținut
al acestora (itemi, formule, etaloane, texte) nu este presupus, dedus sau inventat în acest
document sau în oricare livrare ulterioară — vezi
[10-informatii-necesare-inainte-de-primul-test.md](10-informatii-necesare-inainte-de-primul-test.md).

## 2. Întrebări și presupuneri critice

Fiecare element este marcat astfel:

- **CONFIRMAT** — afirmat explicit de tine în cerință, îl tratez ca atare.
- **PRESUPUNERE** — aleg o valoare implicită rezonabilă; poate fi schimbată fără a rescrie
  arhitectura.
- **NECESITĂ DECIZIE** — trebuie să alegi tu, deciziile ulterioare depind de răspuns.
- **NECESITĂ VERIFICARE JURIDICĂ** — nu este o opinie juridică definitivă; recomand consultarea
  unui specialist GDPR/avocat.
- **NECESITĂ VERIFICAREA LICENȚEI** — depinde exclusiv de textul contractelor tale de licență cu
  deținătorii drepturilor asupra instrumentelor.

| # | Element | Marcaj | Notă |
|---|---------|--------|------|
| 1 | Instrumentele inițiale sunt BDEFS și MCMI-3 | CONFIRMAT | — |
| 2 | Instrumentele confirmate pentru V1 profesională sunt exclusiv **BDEFS** și **MCMI-3** | CONFIRMAT | Ai confirmat explicit. „BDRFS” și „LVM”, menționate în formularea inițială a roadmap-ului cerinței, **nu** fac parte din scopul curent — au fost eliminate din roadmap (08) și din lista de informații necesare (10). Dacă vor fi introduse ulterior, vor intra ca instrumente separate, într-o versiune viitoare. |
| 3 | Deții licențe legale pentru itemi, reguli și chei de cotare, în limitele contractelor | CONFIRMAT | Arhitectura presupune că introducerea conținutului protejat se face de tine, printr-un canal definit separat, nu de mine. |
| 4 | Dreptul de a reproduce integral itemii **în format digital administrat online** (nu doar pe hârtie) | NECESITĂ VERIFICAREA LICENȚEI | Multe licențe pentru instrumente psihometrice comerciale (ex. Pearson/MHS) restricționează explicit administrarea electronică, generarea automată de rapoarte sau folosirea în afara softului oficial al editorului. Trebuie verificat înainte de orice implementare a conținutului real. |
| 5 | Dreptul de a folosi/scana o parafă sau semnătură electronică a psihologului pe raportul PDF | NECESITĂ VERIFICARE JURIDICĂ | Depinde de reglementările Colegiului Psihologilor din România privind semnătura electronică pe documente profesionale. |
| 6 | Temeiul juridic pentru prelucrarea datelor (consimțământ vs. interes legitim/relație contractuală de evaluare psihologică) | NECESITĂ VERIFICARE JURIDICĂ | Datele privind sănătatea mintală sunt categorie specială (Art. 9 GDPR); temeiul trebuie stabilit de un specialist, nu presupus tehnic. |
| 7 | Volumul inițial: un singur cabinet/psiholog vs. mai mulți de la început | PRESUPUNERE | Presupun **un singur psiholog/cabinet** pentru MVP și V1, cu arhitectură multi-tenant pregătită dar dezactivată — conform recomandării arhitecturale. |
| 8 | Necesitatea MFA pentru rolurile profesionale | CONFIRMAT (cerut explicit) | Implementat din MVP pentru rolurile profesionale (nu pentru respondenți, care nu au cont). |
| 9 | Găzduire: hosting WordPress existent vs. hosting nou dedicat | NECESITĂ DECIZIE | Cerințele de securitate (HTTPS obligatoriu, izolare medii, backup criptat, resurse pentru generare PDF) pot depăși un hosting shared ieftin. Trebuie confirmat tipul de hosting disponibil. |
| 10 | Versiunea PHP/MySQL disponibilă pe hosting | NECESITĂ DECIZIE | Recomand minim PHP 8.1 și MySQL 8 / MariaDB 10.6; trebuie confirmat ce oferă hostingul actual. |
| 11 | Volumul estimat de evaluări/lună și clienți concurenți | PRESUPUNERE | Presupun volum mic-mediu (cabinet individual/echipă mică) pentru dimensionarea inițială a infrastructurii; nu schimbă arhitectura, doar dimensionarea hostingului. |
| 12 | Plata/facturarea SaaS în prima versiune | CONFIRMAT (exclusă explicit) | Nu se implementează acum; arhitectura DB include câmpuri pregătite (organizație, plan, limite) fără logică de facturare activă. |
| 13 | Necesitatea suportului multisite WordPress | NECESITĂ DECIZIE | Presupun **nu**, pentru MVP; dacă apare nevoia multi-cabinet, recomand multi-tenant „soft” (o singură instalare, date izolate logic per `organization_id`), nu WordPress Multisite, din motive explicate în 01-comparatie-arhitecturi.md. |
| 14 | Limba interfeței | PRESUPUNERE | Română ca limbă principală de interfață și diacritice complete în PDF; instrumentele pot avea propria limbă (câmp `language` per versiune de instrument). |

Dacă vreuna dintre presupuneri este greșită, spune-mi explicit înainte să trecem la implementare —
unele (ex. #7, #9, #13) influențează structura schemei de bază de date chiar din MVP.
