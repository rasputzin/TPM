# Threat model

Format: pentru fiecare actor/scenariu — vector de atac principal, impact dacă reușește, mitigări
tehnice concrete legate de arhitectura propusă.

## 1. Respondent rău intenționat (are un link valid, își completează propriul test)

- **Vectori**: manipularea răspunsurilor din browser (modificare DOM/JS înainte de submit),
  retrimiterea formularului de mai multe ori, deschiderea aceluiași link în două ferestre,
  încercarea de a accesa itemi/scoruri ale altei administrări prin manipularea ID-urilor din
  request-uri.
- **Impact**: rezultat de test nevalid, coruperea datelor, acces la date ale altcuiva.
- **Mitigări**: cotarea rulează **exclusiv server-side** pe baza `response_value`-urilor salvate
  (nu se acceptă niciun scor trimis din client); `POST /finalize` e idempotent și verifică
  `administration.status` înainte de a accepta finalizarea (dublu-submit → răspuns 409, nu
  recalcul); accesul e legat de `token_hash`, nu de ID secvențial — un respondent nu poate ghici
  ID-ul altei administrări; rate limiting pe endpointurile de sesiune.

## 2. Utilizator autentificat (psiholog/operator) care încearcă să vadă alți clienți (IDOR)

- **Vectori**: modificarea unui `client_id`/`administration_id` în request către un endpoint
  REST pentru care e altfel autentificat legitim.
- **Impact**: acces neautorizat la date clinice ale altui psiholog — cea mai gravă breșă posibilă
  în acest sistem.
- **Mitigări**: `Policy::canAccess()` obligatoriu pe fiecare endpoint parametrizat cu ID de
  resursă (vezi 04-roluri-si-permisiuni.md), verificat prin `tpm_client_professional`; teste
  automate dedicate de IDOR pentru fiecare endpoint nou (parte din Definition of Done — vezi
  08-roadmap); niciun query de listă nu se bazează doar pe filtrare din UI.

## 3. Cont de psiholog compromis (phishing, parolă reutilizată)

- **Vectori**: autentificare cu credențiale furate.
- **Impact**: acces la toți clienții asignați acelui psiholog.
- **Mitigări**: MFA obligatoriu pentru rolurile profesionale (reduce drastic probabilitatea de
  compromitere doar prin parolă); politici de parolă (lungime minimă, verificare împotriva
  parolelor compromise cunoscute); expirare sesiune după inactivitate; blocare cont după
  încercări repetate eșuate; audit log al autentificărilor și al acțiunilor sensibile (export,
  descărcare PDF în masă) — permite detectarea comportamentului anormal; notificare
  administrator de platformă la activitate suspectă (volum neobișnuit de accesări).

## 4. Plugin WordPress vulnerabil (alt plugin instalat pe același site)

- **Vectori**: un alt plugin din instalație are o vulnerabilitate (SQLi, RCE, upload arbitrar)
  care compromite întregul proces PHP/WordPress.
- **Impact**: acces potențial la toată baza de date, inclusiv tabelele `tpm_*`.
- **Mitigări**: criptare la nivel de aplicație a câmpurilor de identificare directă
  (`clients.identity_encrypted`, `contact_email_encrypted`) — chiar dacă DB e citită direct,
  identitatea nu e în clar; cheia de criptare stocată separat de DB (secret management, nu în
  `wp_options`); fișierele PDF stocate **în afara webroot public**, cu control de acces la nivel
  de aplicație, nu doar „security through obscurity” a căii; recomandare operațională: mediu de
  producție cu un set minimal de plugin-uri instalate, actualizate regulat, și separare de rol
  clar între site-ul de prezentare Divi și instalația care găzduiește date clinice (discutat ca
  opțiune la maturizare — posibil instanță WordPress dedicată doar platformei, subdomeniu propriu).

## 5. Administrator tehnic (insider cu acces la server/DB)

- **Vectori**: acces direct la bază de date sau la fișiere, ocolind aplicația.
- **Impact**: expunerea potențială a datelor clinice fără urmă în audit log aplicativ.
- **Mitigări**: criptare la nivel de aplicație pentru identificare directă (vezi mai sus) —
  reduce ce poate vedea cineva cu acces doar la DB; separarea rolului `tpm_tech_admin` de
  `tpm_platform_admin` (tech admin nu are capability de vizualizare clinică prin aplicație);
  logare la nivel de infrastructură a accesului la server/DB (în afara scopului pluginului, dar
  documentată ca cerință operațională); principiul celor patru ochi pentru operațiuni critice pe
  producție, unde e fezabil.

## 6. Acces neautorizat la backup

- **Vectori**: backup-ul (DB + fișiere) stocat necriptat sau accesibil public/greșit permisionat.
- **Impact**: expunerea completă a datelor istorice, inclusiv date deja „șterse” logic.
- **Mitigări**: backup **criptat** obligatoriu (cerință explicită); stocare backup separată de
  webroot, cu acces restricționat; testare periodică de restaurare (altfel un backup criptat
  netestat e un risc ascuns, nu o garanție); politică de retenție și pentru backup-uri (nu păstra
  la nesfârșit date care ar fi trebuit anonimizate).

## 7. Furtul unui link de testare (token intercept: e-mail compromis, ecran partajat, link redirecționat)

- **Vectori**: cineva neautorizat obține linkul unic al unui beneficiar.
- **Impact**: acces la formularul de test al altcuiva, posibil completare frauduloasă.
- **Mitigări**: token cu entropie ridicată (imposibil de ghicit prin brute force în timp util);
  expirare configurabilă; limită de accesări (`max_access_count`); posibilitate de invalidare
  manuală imediată de către psiholog; jurnalizarea fiecărui acces (IP hash, timestamp) vizibilă
  psihologului, pentru a detecta accesări suspecte (ex. două IP-uri diferite la interval scurt);
  token stocat **doar ca hash** în DB — chiar și o exfiltrare a DB nu dă acces direct la linkuri
  active.

## 8. Manipularea răspunsurilor din browser

- Acoperit tehnic la punctul 1: server-ul nu are încredere în niciun scor sau flag de validitate
  trimis din client; toată logica de validare/cotare se recalculează server-side din
  `response_value`-urile brute stocate.

## 9. Atac asupra endpointurilor REST (fuzzing, enumerare, automatizare)

- **Vectori**: request-uri automate către `/tpm/v1/*` pentru enumerare de ID-uri, brute-force de
  token-uri, exfiltrare în masă.
- **Impact**: enumerare utilizatori/evaluări, epuizare resurse, acces neautorizat.
- **Mitigări**: rate limiting per IP și per token/cont; răspunsuri **neutre** la ID inexistent vs.
  neautorizat (ambele 403/404 generic, nu se scurge informație despre existența resursei —
  previne enumerarea); `nonce` WordPress pentru request-urile din admin SPA (protecție CSRF
  suplimentară pentru utilizatori autentificați cu cookie); validare strictă de schema pe fiecare
  input; logare a request-urilor respinse pentru detectarea pattern-urilor de atac.

## 10. Exfiltrarea cheilor de cotare / conținutului protejat

- **Vectori**: inspectarea traficului de rețea sau a codului JavaScript livrat browserului;
  acces la fișierele sursă ale pluginului; request direct către endpointuri interne.
- **Impact**: expunerea conținutului licențiat (itemi, formule, chei de cotare) — încălcare
  directă a condițiilor de licență, nu doar risc tehnic.
- **Mitigări**: **niciun** payload trimis către frontend nu conține `scoring_rules`,
  `norm_entries` sau formule — endpointul care servește structura formularului expune explicit
  doar câmpurile necesare randării (text item, tip răspuns, opțiuni), printr-un DTO dedicat
  (`FormItemDTO`), nu prin serializarea directă a entităților DB; `ScoringEngine` și
  `ReportService` rulează exclusiv server-side, apelate din controller după autorizare; codul
  sursă al pluginului nu conține niciodată itemi/formule reale în fișiere committed în
  repository — acestea sunt date, introduse printr-un canal de import securizat (vezi
  10-informatii-necesare-inainte-de-primul-test.md), nu cod; revizuire explicită la fiecare
  endpoint nou: „acest răspuns conține vreun câmp din `scoring_rules`/`norm_entries`/
  `prompt_text` needitat?” ca parte din code review.

## Rezumat mitigări transversale

- Server-side only pentru orice logică de decizie (cotare, validare finală, autorizare).
- Autorizare pe două niveluri (capability + policy per-rând) pe fiecare endpoint.
- Criptare aplicativă a datelor de identificare directă + PDF-uri în storage privat.
- Audit log append-only, fără conținut clinic, pentru trasabilitate fără expunere suplimentară.
- Tokenuri opace, hash-uite, cu expirare/limită/revocare.
- Rate limiting și răspunsuri neutre împotriva enumerării.
