# Schema inițială a bazei de date

## Convenții globale

- Prefix tabele: `{$wpdb->prefix}tpm_*` (ex. `wp_tpm_instruments`), engine **InnoDB**, charset
  **utf8mb4/utf8mb4_unicode_520_ci** (necesar pentru diacritice și emoji în note libere).
- **Toate** tabelele scoped-organizațional includ `organization_id BIGINT UNSIGNED NOT NULL` cu
  index, chiar dacă în MVP există o singură organizație implicită (id=1) — principiul din
  02-arhitectura-recomandata.md.
- Coloane de audit standard pe toate tabelele mutabile: `created_at DATETIME`,
  `created_by BIGINT UNSIGNED` (FK către `wp_users.ID`), `updated_at DATETIME`,
  `updated_by BIGINT UNSIGNED`. Tabelele cu date istorice imutabile (ex. `responses`,
  `calculated_scores`) nu au `updated_by` — se creează rânduri noi, nu se editează.
- **Soft delete** (`deleted_at DATETIME NULL`) pe tabelele cu implicații GDPR de ștergere
  controlată (`clients`, `administrations`, `reports`), pentru a permite anonimizare/retenție
  auditabilă în loc de `DELETE` fizic imediat.
- Chei externe: `dbDelta()` (folosit de WordPress la activare) **nu suportă fiabil constrângeri
  FOREIGN KEY**. Strategia: se creează tabelele cu `dbDelta()` pentru coloane/indexuri, iar
  constrângerile `FOREIGN KEY` se aplică separat, prin migrații SQL directe (`$wpdb->query()`),
  rulate de un `MigrationRunner` propriu care ține evidența versiunii schemei într-un tabel
  `wp_tpm_schema_migrations (version, applied_at)`. Integritatea referențială e verificată **și**
  la nivel de aplicație (Repository layer), nu doar la nivel de DB.
- JSON este folosit **numai** pentru structuri auxiliare bine definite și validate prin schema
  JSON aplicativă (ex. reguli condiționale de ramificare, configurație layout raport), niciodată
  ca depozit generic pentru date care ar trebui interogabile/indexabile (scoruri, chei străine,
  stări).

## Tabele

### `tpm_organizations`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `name` | VARCHAR(190) NOT NULL | |
| `slug` | VARCHAR(190) UNIQUE | |
| `status` | ENUM('active','suspended','archived') | index |
| `plan` | VARCHAR(50) NULL | pregătit pentru SaaS, neutilizat în MVP |
| `settings_json` | JSON NULL | branding, config e-mail — auxiliar, validat |
| audit standard | | |

### `tpm_professionals`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NOT NULL, FK → organizations.id | index |
| `wp_user_id` | BIGINT UNSIGNED NOT NULL UNIQUE, FK → wp_users.ID | 1:1 cu userul WP |
| `role` | ENUM('tech_admin','platform_admin','psychologist','supervisor','operator') | index |
| `professional_title` | VARCHAR(190) NULL | titlu profesional afișat pe raport |
| `license_number` | VARCHAR(100) NULL | număr atestare Colegiul Psihologilor, dacă aplicabil |
| `mfa_enforced` | TINYINT(1) NOT NULL DEFAULT 1 | |
| `status` | ENUM('active','suspended') | |
| audit standard | | |

### `tpm_client_professional` (asignare beneficiar ↔ psiholog, bază pentru least-privilege)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NOT NULL | index |
| `client_id` | BIGINT UNSIGNED NOT NULL, FK → clients.id | index |
| `professional_id` | BIGINT UNSIGNED NOT NULL, FK → professionals.id | index |
| `access_level` | ENUM('owner','supervisor_view','shared') | |
| `granted_at` | DATETIME | |
| `revoked_at` | DATETIME NULL | |
| UNIQUE | `(client_id, professional_id, access_level)` | previne duplicate active |

### `tpm_clients` (beneficiari)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | folosit doar intern, niciodată expus în URL |
| `organization_id` | BIGINT UNSIGNED NOT NULL | index |
| `pseudonymized_code` | VARCHAR(64) NOT NULL UNIQUE | identificator intern pseudonimizat afișat în UI/rapoarte în loc de `id` secvențial |
| `identity_encrypted` | VARBINARY(2048) NULL | nume/prenume/CNP etc. — **criptat la nivel de aplicație** (vezi 04. securitate), separat de date demografice generale |
| `demographics_json` | JSON NULL | vârstă, sex, ocupație etc. — câmpuri configurabile, ne-identificatoare direct |
| `contact_email_encrypted` | VARBINARY(512) NULL | pentru trimitere invitații |
| `status` | ENUM('active','anonymized','deleted') | |
| `retention_policy_id` | BIGINT UNSIGNED NULL, FK → retention_jobs / politici | |
| audit standard + `deleted_at` | | |

### `tpm_instruments`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NULL | NULL = instrument global de bibliotecă, altfel specific organizației |
| `code` | VARCHAR(50) NOT NULL UNIQUE | acronim (ex. `BDEFS`, `MCMI-3`) |
| `name` | VARCHAR(190) NOT NULL | |
| `rights_holder` | VARCHAR(190) NOT NULL | titularul drepturilor |
| `license_reference` | VARCHAR(190) NULL | referință internă contract licență (nu conținutul licenței) |
| `target_population` | VARCHAR(190) NULL | |
| `status` | ENUM('draft','active','archived') | index |
| audit standard | | |

### `tpm_instrument_versions` (unitatea reală de versionare — vezi principiul de imutabilitate)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `instrument_id` | BIGINT UNSIGNED NOT NULL, FK → instruments.id | index |
| `version_label` | VARCHAR(50) NOT NULL | ex. `1.0.0` |
| `language` | VARCHAR(10) NOT NULL | ex. `ro` |
| `estimated_duration_minutes` | SMALLINT UNSIGNED NULL | |
| `instructions_text` | MEDIUMTEXT NULL | |
| `missing_response_policy_json` | JSON NULL | reguli validate pt. răspunsuri lipsă |
| `status` | ENUM('draft','active','deprecated') | o singură versiune `active` implicită per instrument, restul `deprecated`; niciodată ștearsă |
| `published_at` | DATETIME NULL | |
| audit standard | | |
| UNIQUE | `(instrument_id, version_label)` | |

### `tpm_sections`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `instrument_version_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `parent_section_id` | BIGINT UNSIGNED NULL, FK → sections.id | permite substructuri |
| `title` | VARCHAR(190) NULL | |
| `order_index` | SMALLINT UNSIGNED NOT NULL | |
| `branching_rule_json` | JSON NULL | condiție de afișare (ramificație logică), schema validată aplicativ |

### `tpm_items`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `instrument_version_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `section_id` | BIGINT UNSIGNED NULL, FK → sections.id | index |
| `code` | VARCHAR(50) NOT NULL | identificator item stabil în cadrul versiunii (ex. `Q12`) |
| `prompt_text` | MEDIUMTEXT NOT NULL | textul itemului (conținut protejat — introdus doar prin canalul securizat) |
| `item_type` | ENUM('likert','single_choice','multiple_choice','boolean','numeric','free_text','matrix') | |
| `is_reverse_scored` | TINYINT(1) NOT NULL DEFAULT 0 | |
| `is_required` | TINYINT(1) NOT NULL DEFAULT 1 | |
| `conditional_rule_json` | JSON NULL | afișare condiționată de răspuns anterior |
| `order_index` | SMALLINT UNSIGNED NOT NULL | |
| UNIQUE | `(instrument_version_id, code)` | |

### `tpm_response_options`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `item_id` | BIGINT UNSIGNED NOT NULL, FK → items.id | index |
| `label_text` | VARCHAR(255) NOT NULL | |
| `raw_value` | DECIMAL(10,4) NOT NULL | valoarea numerică brută asociată opțiunii |
| `order_index` | SMALLINT UNSIGNED NOT NULL | |

### `tpm_scales` (scale/subscale)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `instrument_version_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `parent_scale_id` | BIGINT UNSIGNED NULL, FK → scales.id | subscale |
| `code` | VARCHAR(50) NOT NULL | |
| `name` | VARCHAR(190) NOT NULL | |
| UNIQUE | `(instrument_version_id, code)` | |

### `tpm_scoring_rules`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `scale_id` | BIGINT UNSIGNED NOT NULL, FK → scales.id | index |
| `rule_type` | ENUM('sum','weighted_sum','formula','conditional','lookup') | |
| `formula_definition_json` | JSON NOT NULL | definiție validată printr-un schema-validator dedicat (nu cod PHP liber interpretat dinamic — vezi securitate) |
| `engine_version` | VARCHAR(20) NOT NULL | versiunea algoritmului `ScoringEngine` compatibilă, jurnalizată la fiecare calcul |
| `status` | ENUM('draft','active','deprecated') | |
| audit standard | | |

### `tpm_norm_sets`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `instrument_version_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `label` | VARCHAR(190) NOT NULL | ex. „adulți 18-40, populație clinică RO” |
| `applicable_criteria_json` | JSON NULL | variabile autorizate de diferențiere (vârstă, sex etc.) |
| `version_label` | VARCHAR(50) NOT NULL | etaloanele au propria versionare, independentă de versiunea instrumentului |
| `status` | ENUM('draft','active','deprecated') | |
| audit standard | | |

### `tpm_norm_entries`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `norm_set_id` | BIGINT UNSIGNED NOT NULL, FK → norm_sets.id | index |
| `scale_id` | BIGINT UNSIGNED NOT NULL, FK → scales.id | index |
| `raw_score_min` | DECIMAL(10,4) NOT NULL | |
| `raw_score_max` | DECIMAL(10,4) NOT NULL | |
| `standard_score_type` | ENUM('percentile','t_score','z_score','stanine','custom') | |
| `standard_score_value` | DECIMAL(10,4) NOT NULL | |
| `interpretation_range_code` | VARCHAR(50) NULL | leagă de textul autorizat |
| INDEX | `(norm_set_id, scale_id, raw_score_min, raw_score_max)` | căutare rapidă la cotare |

### `tpm_report_templates`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NOT NULL | |
| `instrument_id` | BIGINT UNSIGNED NULL, FK | NULL = șablon generic |
| `version_label` | VARCHAR(50) NOT NULL | |
| `layout_definition_json` | JSON NOT NULL | secțiuni, ordine, texte autorizate legate de `interpretation_range_code` |
| `status` | ENUM('draft','active','deprecated') | |
| audit standard | | |

### `tpm_administrations` (o „administrare”/invitație — poate conține mai multe instrumente = baterie)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | intern, niciodată în URL |
| `organization_id` | BIGINT UNSIGNED NOT NULL | index |
| `client_id` | BIGINT UNSIGNED NOT NULL, FK → clients.id | index |
| `created_by_professional_id` | BIGINT UNSIGNED NOT NULL, FK → professionals.id | index |
| `status` | ENUM('not_started','in_progress','completed','expired','invalidated') | index |
| `expires_at` | DATETIME NOT NULL | |
| `max_access_count` | SMALLINT UNSIGNED NULL | NULL = nelimitat |
| `access_count` | SMALLINT UNSIGNED NOT NULL DEFAULT 0 | |
| `allow_resume` | TINYINT(1) NOT NULL DEFAULT 1 | |
| `started_at` | DATETIME NULL | |
| `completed_at` | DATETIME NULL | |
| audit standard + `deleted_at` | | |

### `tpm_administration_instruments` (ordinea instrumentelor în baterie)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `administration_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `instrument_version_id` | BIGINT UNSIGNED NOT NULL, FK | index — **fixat la creare**, nu urmărește versiunea activă curentă |
| `order_index` | SMALLINT UNSIGNED NOT NULL | |
| `status` | ENUM('pending','in_progress','completed') | |
| UNIQUE | `(administration_id, instrument_version_id)` | |

### `tpm_administration_tokens`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `administration_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `token_hash` | CHAR(64) NOT NULL UNIQUE | SHA-256 al tokenului aleator (min. 256 biți entropie); **tokenul brut nu se stochează niciodată** |
| `issued_at` | DATETIME NOT NULL | |
| `revoked_at` | DATETIME NULL | invalidare manuală de către psiholog |
| `last_used_at` | DATETIME NULL | |
| INDEX | `(administration_id, revoked_at)` | |

### `tpm_responses` (istoric imutabil, nu se suprascrie)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `administration_instrument_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `item_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `response_value` | VARCHAR(500) NULL | valoare brută selectată/introdusă |
| `is_final` | TINYINT(1) NOT NULL DEFAULT 0 | distinge autosave (draft) de răspunsul final |
| `recorded_at` | DATETIME NOT NULL | |
| INDEX | `(administration_instrument_id, item_id, is_final)` | |
| | | Autosave: se face `UPSERT` logic pe `(administration_instrument_id, item_id, is_final=0)`; la finalizare se creează rândurile `is_final=1`, imutabile |

### `tpm_calculated_scores`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `administration_instrument_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `scale_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `raw_score` | DECIMAL(10,4) NULL | |
| `standard_score_type` | ENUM('percentile','t_score','z_score','stanine','custom') NULL | |
| `standard_score_value` | DECIMAL(10,4) NULL | |
| `norm_set_id` | BIGINT UNSIGNED NOT NULL, FK | normă exactă folosită, jurnalizată |
| `scoring_rule_id` | BIGINT UNSIGNED NOT NULL, FK | |
| `engine_version` | VARCHAR(20) NOT NULL | |
| `validity_flags_json` | JSON NULL | avertismente validitate/date lipsă — auxiliar, structurat |
| `calculated_at` | DATETIME NOT NULL | |
| | | **imutabil** — o recotare (ex. după corectarea unei norme) creează un rând nou, nu suprascrie |

### `tpm_reports`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NOT NULL | |
| `administration_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `report_template_id` | BIGINT UNSIGNED NOT NULL, FK | |
| `unique_report_number` | VARCHAR(50) NOT NULL UNIQUE | pt. referință externă/tipărire |
| `status` | ENUM('draft','pending_review','approved','revoked') | index |
| `file_path` | VARCHAR(500) NULL | cale în storage privat, **în afara webroot public** |
| `content_hash` | CHAR(64) NULL | verificare integritate PDF generat |
| `professional_observations` | MEDIUMTEXT NULL | |
| `professional_conclusion` | MEDIUMTEXT NULL | |
| `recommendations` | MEDIUMTEXT NULL | |
| `validated_by_professional_id` | BIGINT UNSIGNED NULL, FK | |
| `validated_at` | DATETIME NULL | |
| `instrument_versions_used_json` | JSON NOT NULL | listă exactă de versiuni (instrument, cotare, normă) pt. trasabilitate |
| audit standard + `deleted_at` | | |

### `tpm_consents`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `client_id` | BIGINT UNSIGNED NOT NULL, FK | index |
| `consent_type` | VARCHAR(100) NOT NULL | ex. „prelucrare date evaluare psihologică” |
| `document_version` | VARCHAR(50) NOT NULL | versiunea textului de informare/consimțământ |
| `granted_at` | DATETIME NULL | |
| `withdrawn_at` | DATETIME NULL | |
| `evidence_reference` | VARCHAR(255) NULL | referință dovadă (hash document semnat, IP+timestamp etc.) |

### `tpm_audit_logs` (append-only)
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NULL | |
| `actor_type` | ENUM('professional','system','respondent_token') | |
| `actor_id` | BIGINT UNSIGNED NULL | |
| `event_type` | VARCHAR(100) NOT NULL | index |
| `entity_type` | VARCHAR(100) NOT NULL | |
| `entity_id` | BIGINT UNSIGNED NULL | index |
| `metadata_json` | JSON NULL | **fără** conținut clinic (fără text de răspunsuri, fără itemi) |
| `ip_hash` | CHAR(64) NULL | IP hash-uit, nu în clar, dacă reținut |
| `created_at` | DATETIME NOT NULL | index |
| | | Tabel fără `UPDATE`/`DELETE` la nivel aplicativ; retenția se face prin job de arhivare, nu editare |

### `tpm_retention_jobs`
| Coloană | Tip | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | |
| `organization_id` | BIGINT UNSIGNED NOT NULL | |
| `entity_type` | VARCHAR(100) NOT NULL | ex. `client`, `administration` |
| `entity_id` | BIGINT UNSIGNED NOT NULL | |
| `policy_code` | VARCHAR(100) NOT NULL | referință politică de retenție aplicabilă |
| `scheduled_action` | ENUM('anonymize','delete','notify_review') | |
| `scheduled_at` | DATETIME NOT NULL | index |
| `executed_at` | DATETIME NULL | |
| `status` | ENUM('pending','executed','failed','cancelled') | index |

## Strategia de migrare

- Un `MigrationRunner` propriu, cu fișiere de migrare numerotate (`Migration_2025_01_000_initial.php`
  etc.), fiecare implementând `up()`; versiunea aplicată se ține în `wp_tpm_schema_migrations`.
- La activarea pluginului (`register_activation_hook`) se rulează doar migrațiile neaplicate,
  idempotent.
- Nu se folosește `dbDelta()` pentru `FOREIGN KEY` — doar pentru definiția inițială de coloane;
  constrângerile FK și indexurile compuse se aplică prin `ALTER TABLE` explicit în migrații.
- Dezactivarea pluginului **nu** șterge date; ștergerea schemei se face doar la `uninstall.php`,
  condiționată explicit de o setare confirmată de administratorul tehnic (nu implicit).
