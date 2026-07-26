# Checkpoint 2: Interfața de administrare (React) + mediu local de testare

Răspunde direct la decizia luată: construim întâi interfața admin, ca tu să poți introduce
itemii/cheile de cotare/etaloanele **direct în platformă**, fără ca acel conținut să treacă prin
această conversație.

## Ce conține acest checkpoint

1. **`admin-app/`** — o aplicație React + TypeScript (Vite), separată ca pachet de cod, dar
   compilată și servită din același domeniu WordPress (conform arhitecturii din
   [02-arhitectura-recomandata.md](02-arhitectura-recomandata.md)). Acoperă tot modulul
   „Administrarea instrumentelor” construit în checkpoint-ul anterior:
   - listă/creare/arhivare instrumente;
   - versiuni (creare draft, clonare, publicare — cu avertisment vizual clar când o versiune nu
     mai e editabilă);
   - secțiuni, itemi (toate cele 7 tipuri), opțiuni de răspuns per item;
   - scale + reguli de cotare (toate cele 5 tipuri), cu exemple pre-completate per tip;
   - etaloane (norm sets) + intrări, cu publicare separată;
   - șabloane de raport (organizație), cu publicare.
2. **`src/Admin/AdminPage.php`** — montează SPA-ul într-o pagină proprie din `wp-admin`
   („Testare psihologică”), vizibilă doar utilizatorilor cu capability `tpm_manage_instruments`.
   Autentificare prin cookie WP + nonce REST standard (`X-WP-Nonce`) — nimic nou de autentificare,
   reutilizează exact mecanismul WordPress.
3. **`docker-compose.yml` + `docker/setup.sh`** — un WordPress + MariaDB local, cu pluginul montat
   ca volum, pentru **mașina ta**, nu pentru acest sandbox (explic mai jos de ce).

## De ce ai nevoie de un WordPress local acum

Interfața pe care tocmai am construit-o rulează *în* WordPress (pagină de admin, autentificare
prin cookie WP, apeluri către REST API-ul WordPress). Nu există un site WordPress real conectat la
această conversație — codul e doar în repository. Ca s-o folosești efectiv, ai nevoie de un
WordPress pornit undeva. `docker-compose.yml` e cea mai rapidă cale s-o faci pe mașina ta.

## Cum pornești mediul local (pe mașina ta, nu aici)

Cerințe: Docker + Docker Compose instalate.

```bash
cd wp-content/plugins/tpm-psych-platform
docker compose run --rm composer install   # instalează dependențele PHP (fără deps reale în MVP, dar generează autoload)
docker compose up -d                       # pornește MariaDB + WordPress
./docker/setup.sh                          # instalează WP, activează pluginul
```

Apoi: `http://localhost:8080/wp-admin`, user `admin`, parolă `admin` (doar dev local — nu expune
acest port public). În meniul din stânga apare **„Testare psihologică”**.

Dacă preferi un WordPress deja existent (hosting propriu, altă instalație locală) în loc de
docker-compose: copiază `wp-content/plugins/tpm-psych-platform/` în `wp-content/plugins/` al
acelei instalații, rulează `composer install` acolo, activează pluginul din `wp-admin → Plugins`.

## Cum rebuilzi interfața după ce modific codul React

```bash
cd admin-app
npm install
npm run build
```

`admin-app/dist/` (build-ul compilat) **e commis în git**, spre deosebire de `vendor/` sau
`node_modules/` — majoritatea hostingurilor WordPress nu au Node.js disponibil ca să ruleze
`npm run build` la deploy, deci build-ul trebuie să fie deja gata în repository.

## Fluxul de utilizare, pas cu pas

1. **Instrumente** → „+ Instrument nou” → completezi cod (`BDEFS`), denumire, titular drepturi.
2. Deschizi instrumentul → „+ Versiune nouă (draft)” → `1.0.0`, `ro`.
3. Deschizi versiunea → tab **Secțiuni și itemi** → adaugi secțiuni (opțional) și itemi, cu textul
   exact al fiecăruia, tipul corect și opțiunile de răspuns (eticheta + valoarea de cotare).
4. Tab **Scale și cotare** → adaugi scala (ex. `TOTAL`), apoi regula de cotare — alegi tipul
   (`sum`/`weighted_sum`/`formula`/`conditional`/`lookup`) și completezi definiția JSON (câmpul
   vine pre-completat cu un exemplu pentru tipul ales; server-ul respinge orice cod de item
   necunoscut sau formulă cu caractere neautorizate).
5. Tab **Etaloane** → creezi etalonul (ex. „Adulți 18-40, populație clinică RO”), adaugi intrările
   (interval scor brut → scor standardizat + cod de interpretare), apoi „Publică etalonul”.
6. Din meniul **Șabloane de raport** → creezi un șablon (opțional legat de instrument), completezi
   `interpretation_texts` cu codurile de interpretare folosite la pasul 5 și textele autorizate,
   apoi „Publică”.
7. Înapoi la versiune → „Publică” — serverul verifică automat că fiecare scală are o regulă de
   cotare și că există cel puțin un item; dacă lipsește ceva, primești eroarea exactă.

Nimic din pașii 3-6 nu trece prin mine — introduci direct în formulare, salvate prin REST API în
baza de date a WordPress-ului tău.

## Ce am verificat, ce nu

La fel ca la checkpoint-ul anterior, acest sandbox nu are un WordPress/MySQL real, deci n-am putut
rula fluxul complet end-to-end aici. Ce am verificat concret:

- `npm run build` (TypeScript + Vite) trece fără erori — tot codul React e type-checked corect.
- `php -l` pe toate fișierele PHP noi (`AdminPage.php`, `Plugin.php` modificat) — fără erori de
  sintaxă.
- Am verificat manual, citind codul, coerența dintre fiecare apel din `admin-app/src/api/endpoints.ts`
  și rutele efectiv înregistrate în `RestRouteRegistrar`/controllerele PHP (metodă, path, body).

Rămâne de verificat **de tine**, cu WordPress-ul local pornit: că fluxul de mai sus chiar
funcționează cap-coadă în browser. Dacă întâlnești o eroare, spune-mi exact la ce pas și ce mesaj
apare — o pot diagnostica din cod fără să am nevoie să văd conținutul real pe care l-ai introdus.

## Limitări cunoscute ale acestui checkpoint

- Câmpurile complexe (`formula_definition`, `applicable_criteria`, `missing_response_policy`,
  `layout_definition`) sunt editate ca text JSON brut, nu prin sub-formulare dedicate per tip —
  funcțional, dar cere atenție la sintaxă. Un builder vizual per tip de regulă e o îmbunătățire
  posibilă ulterioară, nu un blocaj.
- Secțiunile și scalele nu au încă editare/ștergere din UI (doar creare) — dacă greșești, cea mai
  simplă soluție acum e să clonezi versiunea ca draft nou și să repornești acea parte.
- Nu există paginare — nepotrivit pentru sute de itemi într-un singur ecran, dar suficient pentru
  volumul unui instrument obișnuit (zeci de itemi).
- UI-ul e vizibil oricui are `tpm_manage_instruments`/`tpm_manage_templates` — pentru MVP-ul cu un
  singur psiholog/administrator, asta ești tu (contul WP `administrator` primește aceste
  capabilities automat la activare).
- Interfața pentru rolul `psychologist` (dashboard clienți/evaluări) nu există încă — vine odată cu
  modulul de administrări/completare, următorul pas din roadmap.
