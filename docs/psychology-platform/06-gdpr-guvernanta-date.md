# GDPR și guvernanța datelor

> **Acest document nu este consultanță juridică.** Elementele marcate
> **NECESITĂ VERIFICARE JURIDICĂ** trebuie confirmate de un specialist în protecția datelor
> (DPO/avocat) înainte de lansarea în producție cu date reale. Datele privind sănătatea mintală
> sunt categorie specială de date (Art. 9 GDPR) — recomand realizarea unei **DPIA** (Data
> Protection Impact Assessment) înainte de prima evaluare reală cu beneficiari, nu doar citirea
> acestui document.

## Matricea categoriilor de date

| Categorie de date | Scop prelucrare | Temei juridic | Cine are acces | Retenție | Măsuri de protecție | Ștergere/anonimizare | Notă informare |
|---|---|---|---|---|---|---|---|
| Date de identificare beneficiar (nume, contact) | Administrare evaluare, comunicare | **NECESITĂ VERIFICARE JURIDICĂ** (probabil executare relație contractuală de evaluare, posibil consimțământ complementar pt. comunicări) | Psiholog asignat, operator (doar contact) | **NECESITĂ DECIZIE** (politică internă, orientativ 5-10 ani conform practicii domeniului medical/psihologic din RO — de confirmat juridic) | Criptare aplicativă (`identity_encrypted`), acces prin policy per-client | Anonimizare (păstrare date psihometrice fără identificare) sau ștergere completă, la termen sau la cerere legală | Cine prelucrează, scop, bază legală, drepturi, DPO/contact |
| Date demografice (vârstă, sex, ocupație etc.) | Aplicarea corectă a etaloanelor | **NECESITĂ VERIFICARE JURIDICĂ** (accesoriu scopului principal) | Psiholog asignat | Aliniată cu evaluarea asociată | Câmpuri configurabile, separate de identitate directă unde posibil | Anonimizare împreună cu restul dosarului | Idem |
| Răspunsuri la itemi | Calcularea scorurilor | **NECESITĂ VERIFICARE JURIDICĂ** — categorie specială (date privind sănătatea) | Psiholog asignat (dacă licența instrumentului permite acces la răspunsuri brute) | Legată de politica de retenție a dosarului clinic — **NECESITĂ VERIFICARE JURIDICĂ** privind termenul minim/maxim aplicabil documentelor psihologice în RO | Stocare separată logic de identitate, imutabilă, fără expunere în frontend după finalizare | Anonimizare (decuplare de `client_id`) sau ștergere, conform politicii | Scop explicit „calcul scor psihometric”, nu generic |
| Scoruri calculate | Interpretare profesională | Idem răspunsuri | Psiholog asignat, supervizor (dacă autorizat) | Idem | Imutabile, jurnalizate cu versiune normă/motor | Idem | Idem |
| Rapoarte PDF | Livrare rezultat profesional către beneficiar/terț autorizat | **NECESITĂ VERIFICARE JURIDICĂ** | Psiholog validator, beneficiar (canal securizat) | Idem dosar clinic | Storage privat, acces autentificat, watermark DRAFT pre-validare, hash integritate | Ștergere fișier + păstrare doar metadate de audit, conform politicii | Idem |
| Consimțăminte | Dovadă conformitate | Obligație legală de evidență | Platform admin, psiholog | Cel puțin durata dosarului + termen legal de dovadă — **NECESITĂ VERIFICARE JURIDICĂ** | Stocare `evidence_reference`, versiune document | Nu se șterge înaintea dosarului la care se referă (ar anula dovada) | Text consimțământ separat, versionat |
| Jurnal de audit | Securitate, trasabilitate, investigare incidente | Interes legitim (securitate) — **NECESITĂ VERIFICARE JURIDICĂ** pentru durata de păstrare | Platform admin, tech admin (fără conținut clinic) | Termen tehnic definit (ex. 12-24 luni) — **NECESITĂ DECIZIE** | Append-only, fără conținut clinic, IP hash-uit | Arhivare/ștergere automată la termen | N/A (date tehnice) |
| Date de facturare/cont profesional (viitor SaaS) | Administrare abonament | Executare contract | Platform admin | Termen legal contabil | Standard | Standard | La activarea funcționalității SaaS |

## Funcții tehnice de guvernanță necesare

| Funcție | Componentă responsabilă | Notă |
|---|---|---|
| Informare (notă de informare) | `ConsentService` + pagină publică versionată | Textul notei de informare e conținut juridic — **NECESITĂ VERIFICARE JURIDICĂ**, nu generat de platformă |
| Evidența consimțământului | `tpm_consents` + `ConsentService` | Doar dacă temeiul aplicabil e consimțământul; dacă temeiul e alt tip (relație contractuală de evaluare), evidența rămâne utilă ca dovadă de informare, nu ca „bifă” obligatorie |
| Retragerea consimțământului | `ConsentService::withdraw()` | Trebuie definit efectul asupra evaluărilor în curs — **NECESITĂ DECIZIE** operațională + juridică |
| Exportul datelor (portabilitate/acces) | `DataExportService` | Export structurat (JSON/PDF) al datelor unui client, generat sub autorizare psiholog/platform admin, jurnalizat |
| Rectificare | UI standard de editare client, cu istoric de audit al modificării | |
| Restricționare prelucrare | Flag `clients.status` suplimentar (`restricted`) care blochează crearea de noi administrări fără a șterge date | |
| Ștergere | `DataDeletionService`, condiționat de reguli legale (dosar medical/psihologic poate avea termen minim de păstrare obligatoriu — **NECESITĂ VERIFICARE JURIDICĂ** înainte de a permite ștergere la simpla cerere) | |
| Anonimizare | `AnonymizationService` — decuplează `identity_encrypted`/contact de restul dosarului, păstrează date psihometrice agregate dacă justificat statistic/legal | |
| Evidența accesului | `tpm_audit_logs` + raport „cine a accesat dosarul clientului X” | |
| Raportarea incidentelor | Procedură +, tehnic, posibilitate de export rapid al jurnalului relevant pentru o breșă (termenul de 72h către ANSPDCP e obligație organizațională, nu doar tehnică) | **NECESITĂ VERIFICARE JURIDICĂ** pentru procedură completă |
| Politici de retenție | `tpm_retention_jobs` + `RetentionService` rulat programat (Action Scheduler) | Politicile concrete (durate) — **NECESITĂ DECIZIE** + verificare juridică |
| Blocarea automată la termen | `RetentionService` marchează/anonimizează/notifică la scadență, nu șterge silențios fără fereastră de revizuire umană | Recomand `notify_review` înainte de `delete` automat, pentru control profesional |

## Principii privacy-by-design aplicate

- **Separarea datelor de identificare de răspunsuri/scoruri**, pe cât tehnic posibil (identitate
  criptată separat, `client_id` intern folosit ca legătură, `pseudonymized_code` afișat în UI-uri
  unde identitatea directă nu e necesară).
- **Minimizare**: operatorul administrativ nu primește acces la date clinice (vezi
  04-roluri-si-permisiuni.md); logurile tehnice nu conțin conținut clinic (vezi threat model,
  punctul 9-10).
- **Retenție cu limită**, nu păstrare nedefinită — orice entitate cu date personale are o
  politică de retenție asociabilă din prima versiune a schemei, chiar dacă valorile concrete se
  configurează ulterior.
- **DPIA recomandată** înainte de procesarea primelor date reale — acest document servește ca
  input tehnic pentru DPIA, nu o înlocuiește.

## Ce rămâne de confirmat înainte de producție

1. Temeiul juridic exact pentru fiecare categorie din matrice (consimțământ vs. altă bază legală).
2. Termenele de retenție aplicabile documentelor de evaluare psihologică conform reglementărilor
   profesiei (Colegiul Psihologilor din România) și legislației naționale.
3. Conținutul notei de informare și al textului de consimțământ.
4. Dacă e necesară o DPIA formală (probabil da, dat fiind că se prelucrează date privind
   sănătatea la scară — chiar și „la scară mică”, caracterul special al datelor justifică
   evaluarea).
5. Procedura organizațională de notificare a incidentelor (nu doar componenta tehnică de export
   log).
