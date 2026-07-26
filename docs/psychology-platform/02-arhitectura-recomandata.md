# Arhitectura recomandată

## Justificarea alegerii

Se recomandă **Varianta 1**: plugin WordPress custom, monolitic la nivel de deployment, dar
**strict stratificat intern** (separare clară Controllers / Services / Repositories / Domain /
Infrastructure), cu:

- REST API intern propriu (namespace dedicat, nu endpoint-uri publice implicite pentru date
  clinice);
- frontend React/TypeScript separat ca *cod* (pachet npm propriu, build separat), dar **servit din
  același domeniu WordPress** — combină izolarea de Divi (Varianta 2) cu simplitatea operațională
  a Variantei 1;
- schema de bază de date proiectată cu `organization_id` prezent de la primul tabel (principiul
  Variantei 4), chiar dacă în MVP există o singură organizație implicită și UI-ul nu expune
  gestiunea multi-organizație.

**De ce nu Varianta 3 (backend extern) acum:** volumul confirmat (cabinet individual/echipă mică,
fără plăți în prima versiune) nu justifică dublarea suprafeței operaționale și de securitate.
Riscul real pentru date psihologice sensibile nu vine din faptul că logica rulează „în” WordPress,
ci din calitatea implementării (capabilities, prepared statements, criptare, izolare fișiere).
Stratificarea internă riguroasă face ca, **dacă** volumul o va cere ulterior, motorul de cotare și
generarea de rapoarte să poată fi extrase într-un serviciu separat fără rescriere completă —
interfețele (Scoring Engine, Report Renderer) sunt deja definite ca și cum ar fi servicii
independente, doar că rulează in-process.

**De ce nu Varianta 2 completă (SPA găzduit separat):** ar introduce CORS/CSRF suplimentar și un
al doilea pipeline de deploy fără beneficiu real, atât timp cât frontendul e servit din același
domeniu.

## Principii arhitecturale aplicate

1. **Separarea clară a straturilor** (impusă prin namespace-uri PHP și structură de foldere —
   vezi [07-structura-repository.md](07-structura-repository.md)).
2. **Cheile de cotare, formulele și normele nu părăsesc niciodată serverul.** Frontend-ul
   respondentului primește doar: structura formularului (întrebări, tip răspuns, opțiuni,
   validări de format), nu reguli de scorare. Cotarea rulează exclusiv în
   `ScoringEngine` (PHP, server-side), apelat din controller după `POST` final, niciodată din
   JavaScript.
3. **Versionare imutabilă**: orice evaluare e legată de un `instrument_version_id` fix la
   momentul creării invitației; modificarea unui instrument creează o versiune nouă, nu editează
   una existentă folosită.
4. **Autorizare pe două niveluri**: WordPress Capabilities (poate psihologul accesa panoul?) +
   Authorization Policy custom la nivel de rând (poate *acest* psiholog accesa *acest* client/
   această evaluare?) — capabilities WP nu sunt suficiente singure pentru IDOR.
5. **Auditul e o cross-cutting concern**, nu un modul opțional — orice acțiune sensibilă trece
   printr-un `AuditService` apelat din service layer, nu din controllere individuale (ca să nu
   poată fi omis).

## Diagrama componentelor (Mermaid)

```mermaid
graph TB
    subgraph "Prezentare"
        DIVI["Pagini Divi<br/>(shortcode / modul)"]
        ADMIN_UI["Admin/Dashboard SPA<br/>(React + TS)"]
        RESP_UI["Interfață Respondent SPA<br/>(React + TS, minimal, fără Divi)"]
    end

    subgraph "API Layer (WP REST API, namespace propriu)"
        REST_ADMIN["/tpm/v1/admin/*<br/>capability + policy checks"]
        REST_PSY["/tpm/v1/psychologist/*<br/>capability + policy checks"]
        REST_TOKEN["/tpm/v1/session/*<br/>token-based, fără cont WP"]
    end

    subgraph "Application / Service Layer"
        INSTR_SVC["Instrument Service"]
        FORM_SVC["Form Engine"]
        INVITE_SVC["Invitation Service"]
        SCORING["Scoring Engine<br/>(server-only)"]
        REPORT_SVC["Report Service"]
        CONSENT_SVC["Consent Service"]
        AUDIT_SVC["Audit Service"]
        RETENTION_SVC["Retention Service"]
    end

    subgraph "Domain"
        MODELS["Domain Models / DTOs<br/>Instrument, Administration, Response, Score"]
        POLICIES["Authorization Policies"]
        VALIDATORS["Validators"]
    end

    subgraph "Infrastructure"
        REPO["Repositories<br/>(wpdb, prepared statements)"]
        PDF["PDF Renderer"]
        MAILER["Mailer (WP mail / SMTP)"]
        QUEUE["Action Scheduler<br/>(cron/queue)"]
        AUTH["WP Auth + Capabilities + MFA"]
        FILES["Secure File Storage<br/>(PDF, în afara webroot public)"]
    end

    subgraph "WordPress / Divi"
        WPCORE["WordPress Core"]
        WPDB[("MySQL/MariaDB<br/>tabele custom tpm_*")]
    end

    DIVI --> ADMIN_UI
    DIVI --> RESP_UI
    ADMIN_UI --> REST_ADMIN
    ADMIN_UI --> REST_PSY
    RESP_UI --> REST_TOKEN

    REST_ADMIN --> AUTH
    REST_PSY --> AUTH
    REST_TOKEN --> AUTH

    REST_ADMIN --> INSTR_SVC
    REST_PSY --> INVITE_SVC
    REST_PSY --> REPORT_SVC
    REST_PSY --> CONSENT_SVC
    REST_TOKEN --> FORM_SVC

    INSTR_SVC --> MODELS
    FORM_SVC --> MODELS
    INVITE_SVC --> MODELS
    FORM_SVC --> SCORING
    SCORING --> REPORT_SVC

    INSTR_SVC --> POLICIES
    INVITE_SVC --> POLICIES
    REPORT_SVC --> POLICIES

    INSTR_SVC --> VALIDATORS
    FORM_SVC --> VALIDATORS

    INSTR_SVC --> REPO
    FORM_SVC --> REPO
    INVITE_SVC --> REPO
    SCORING --> REPO
    REPORT_SVC --> REPO
    CONSENT_SVC --> REPO
    RETENTION_SVC --> REPO

    REPORT_SVC --> PDF
    PDF --> FILES
    INVITE_SVC --> MAILER
    RETENTION_SVC --> QUEUE
    INVITE_SVC --> QUEUE

    INSTR_SVC -.audit.-> AUDIT_SVC
    FORM_SVC -.audit.-> AUDIT_SVC
    INVITE_SVC -.audit.-> AUDIT_SVC
    SCORING -.audit.-> AUDIT_SVC
    REPORT_SVC -.audit.-> AUDIT_SVC
    CONSENT_SVC -.audit.-> AUDIT_SVC
    AUDIT_SVC --> REPO

    REPO --> WPDB
    AUTH --> WPCORE
```

## Fluxul complet al unei evaluări (sequence diagram)

```mermaid
sequenceDiagram
    actor Psy as Psiholog (cont WP + MFA)
    participant Adm as Admin SPA
    participant Api as REST API
    participant Inv as Invitation Service
    participant Mail as Mailer
    actor Ben as Beneficiar (fără cont WP)
    participant Resp as Respondent SPA
    participant Form as Form Engine
    participant Score as Scoring Engine
    participant Rep as Report Service
    participant Aud as Audit Service

    Psy->>Adm: Selectează client + instrumente (BDEFS, MCMI-3)
    Adm->>Api: POST /admin/administrations (baterie, ordine, expirare, max accesări)
    Api->>Inv: creează administration + token
    Inv->>Inv: generează token aleator criptografic (hash stocat, nu token brut)
    Inv->>Aud: log: administration_created
    Inv-->>Api: link unic (token în URL, fără ID secvențial expus)
    Api-->>Adm: confirmare + link
    Adm->>Mail: (opțional) trimite invitația pe e-mail
    Mail-->>Ben: e-mail cu linkul unic

    Ben->>Resp: deschide linkul unic
    Resp->>Api: GET /session/{token}
    Api->>Inv: validează token (hash match, neexpirat, sub limita de accesări)
    alt token invalid/expirat/epuizat
        Api-->>Resp: 410/403, mesaj neutru
        Api->>Aud: log: token_invalid_attempt
    else token valid
        Inv->>Aud: log: administration_started (dacă e primul acces)
        Api->>Form: încarcă structura formularului pt. instrument_version_id fixat
        Form-->>Resp: schema formular (fără reguli de cotare)
        Ben->>Resp: completează itemi (autosave periodic)
        Resp->>Api: PATCH /session/{token}/responses (parțial, idempotent)
        Api->>Form: validează + salvează progres (status: in_progress)
        Api->>Aud: log: responses_autosaved (fără conținut clinic în log)
        Ben->>Resp: confirmă finalizarea (ecran de confirmare explicit)
        Resp->>Api: POST /session/{token}/finalize
        Api->>Form: validează completitudine conform regulilor testului
        Form->>Score: trimite răspunsurile complete
        Score->>Score: calculează scoruri brute/scale/standardizate (server-only)
        Score->>Aud: log: scoring_completed (versiune motor + normă folosită)
        Score-->>Api: calculated_scores salvate, administration.status = completed
        Api-->>Resp: confirmare finalizare (mesaj neutru, non-diagnostic)
    end

    Score->>Rep: generează raport (status: draft, watermark DRAFT)
    Rep->>Aud: log: report_generated
    Psy->>Adm: deschide dashboard → "Rapoarte de validat"
    Adm->>Api: GET /psychologist/reports/{id}
    Api-->>Adm: scor + clasificare psihometrică + text autorizat (editabil) + observații
    Psy->>Adm: adaugă observații/concluzii, aprobă raportul
    Adm->>Api: POST /psychologist/reports/{id}/approve
    Api->>Rep: marchează validat (validator, dată, elimină watermark DRAFT)
    Rep->>Aud: log: report_approved
    Api-->>Adm: PDF final disponibil (acces autentificat, nu URL public)
```

## Note privind evoluția către SaaS

Fluxul de mai sus nu se schimbă la trecerea la multi-organizație — singura diferență e că fiecare
entitate (client, instrument, administrare, raport) e deja filtrată prin `organization_id` în
Repository Layer, prin implementarea `AuthorizationPolicy`, nu prin schimbări în Service Layer.
Aceasta e exact investiția „Variantă 4” aplicată la nivel de date, fără a construi UI de
onboarding organizații, planuri sau facturare în MVP.
