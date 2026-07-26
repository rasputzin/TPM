# Modelul rolurilor și permisiunilor

## Două niveluri de autorizare

1. **WordPress Capabilities** — răspunde la „poate acest tip de utilizator accesa această
   funcționalitate?” (ex. poate vedea panoul de administrare a instrumentelor).
2. **Authorization Policy custom** (rulează în `Service Layer`, nu doar în `Controller`) —
   răspunde la „poate *acest* utilizator specific accesa *această* resursă specifică?” (ex. poate
   psihologul X vedea clientul Y). WP Capabilities **nu pot exprima** acest nivel de granularitate
   per-rând, de aceea e obligatoriu un strat suplimentar — altfel apare exact riscul IDOR descris
   în threat model.

Regula de bază: **orice** query care întoarce date despre un client/administrare/raport trece
printr-o `Policy::canAccess($user, $resource)` explicită înainte de a ajunge la Repository — nu se
presupune că filtrarea din UI e suficientă.

## Roluri și capabilities custom propuse

Rolurile WordPress native (`administrator`, `editor` etc.) **nu se reutilizează** pentru rolurile
clinice — se creează roluri custom la activare, cu capabilities proprii prefixate `tpm_`, pentru a
nu se amesteca accidental cu managementul de conținut al site-ului Divi.

| Rol WP custom | Capabilities cheie | Domeniu |
|---|---|---|
| `tpm_tech_admin` | `tpm_manage_system`, `tpm_manage_plugin_config`, `tpm_view_error_logs`, `tpm_manage_backups` | Configurare tehnică, erori, backup — **fără** acces implicit la date clinice |
| `tpm_platform_admin` | `tpm_manage_instruments`, `tpm_manage_users`, `tpm_manage_organizations`, `tpm_manage_templates`, `tpm_manage_retention_policies`, `tpm_view_audit_log` | Administrare platformă, fără acces automat la conținutul răspunsurilor |
| `tpm_psychologist` | `tpm_manage_own_clients`, `tpm_create_administrations`, `tpm_view_assigned_reports`, `tpm_approve_reports`, `tpm_view_assigned_responses` (dacă licența permite) | Acces **doar** la clienți asignați lui (verificat prin `tpm_client_professional`) |
| `tpm_supervisor` | `tpm_view_supervised_reports`, `tpm_view_supervised_clients` (read-only) | Acces read-only la evaluările psihologilor pe care îi supervizează, prin asignare explicită, nu global |
| `tpm_operator` | `tpm_manage_invitations_admin_only`, `tpm_view_client_contact_info` | Poate crea/retrimite invitații și gestiona date administrative (nume, e-mail, programare); **nu** are `tpm_view_assigned_responses` sau `tpm_view_assigned_reports` — nu vede răspunsuri sau scoruri clinice |
| *(fără rol WP)* Beneficiar/respondent | — | Acces exclusiv prin token de sesiune (`tpm_administration_tokens`), fără cont WordPress, fără capabilities |

## Matrice roluri × acțiuni (rezumat)

| Acțiune | tech_admin | platform_admin | psychologist | supervisor | operator | respondent (token) |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Configurare sistem/backup | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Administrare instrumente/versiuni | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Vizualizare jurnal audit complet | ❌ | ✅ | doar propriile acțiuni | ❌ | ❌ | ❌ |
| Creare client | ❌ | ✅ | ✅ (proprii) | ❌ | ✅ (date administrative) | ❌ |
| Vizualizare listă clienți | ❌ | ✅ (toți, cu justificare auditată) | ✅ (doar asignați) | ✅ (doar supervizați) | ✅ (doar date de contact) | ❌ |
| Creare invitație/administrare | ❌ | ❌ | ✅ (clienți proprii) | ❌ | ✅ (la cererea psihologului) | ❌ |
| Completare formular | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (propriul token) |
| Vizualizare răspunsuri brute | ❌ | ❌ | ✅ dacă licența permite, doar clienți proprii | ✅ dacă permis, read-only | ❌ | ❌ |
| Vizualizare scoruri calculate | ❌ | ❌ | ✅ (clienți proprii) | ✅ (supervizați, read-only) | ❌ | ❌ |
| Validare/aprobare raport | ❌ | ❌ | ✅ (clienți proprii) | ❌ | ❌ | ❌ |
| Descărcare PDF final | ❌ | ❌ | ✅ (clienți proprii) | ✅ dacă autorizat | ❌ | ❌ |
| Export/ștergere date (GDPR) | ❌ | ✅ (proces oficial, cu audit) | inițiere cerere, nu execuție directă | ❌ | ❌ | ❌ |

Legendă: „✅ (proprii/asignați)” înseamnă că verificarea WP capability e **necesară dar
insuficientă** — decizia finală vine din `Policy::canAccess()` pe baza `tpm_client_professional`.

## Principii aplicate

- **Cel mai mic privilegiu implicit**: niciun rol nou creat nu primește acces la date clinice
  fără asignare explicită.
- **Operatorul administrativ nu vede automat rezultate clinice** — separare impusă la nivel de
  capability (`tpm_view_client_contact_info` vs `tpm_view_assigned_responses`), nu doar convenție
  de UI.
- **Supervizarea e explicită, nu ierarhică implicit** — un supervizor nu vede toți psihologii
  organizației doar pentru că are rolul; are nevoie de o relație de supervizare înregistrată
  (tabel suplimentar `tpm_supervision_assignments`, analog cu `tpm_client_professional`).
- **Administratorul de platformă nu are acces implicit la conținutul clinic** — poate gestiona
  metadate (instrumente, șabloane, utilizatori), dar accesul la răspunsuri/rapoarte ale unui
  client anume necesită un motiv explicit auditat (ex. investigare incident), nu navigare liberă.
- **MFA obligatoriu** pentru toate rolurile profesionale (`tech_admin`, `platform_admin`,
  `psychologist`, `supervisor`, `operator`); nu se aplică respondenților, care nu au cont.

## REST API permission callbacks

Fiecare endpoint din `/tpm/v1/*` definește explicit `permission_callback`, care verifică:
1. autentificare validă (user WP cu capability necesară, **sau** token de sesiune valid pentru
   endpointurile de respondent);
2. capability specifică acțiunii;
3. pentru endpointuri parametrizate cu ID de resursă (`/administrations/{id}`), apelarea
   `Policy::canAccess()` **înainte** de a executa orice query pe baza ID-ului din URL — previne
   IDOR chiar dacă un utilizator autentificat ghicește/incrementează un ID.

Niciun endpoint REST care întoarce date clinice nu rămâne cu `permission_callback => '__return_true'`.
