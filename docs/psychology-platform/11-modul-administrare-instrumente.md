# Checkpoint 1: Fundația pluginului + modulul „Administrarea instrumentelor”

Acest document e evidența cerută la Etapa 10: ce s-a construit, de ce, ce rămâne de făcut și cum
se verifică. Corespunde primului pas din
[08-roadmap-implementare.md](08-roadmap-implementare.md) („Fundație plugin” + „Modulul Instrumente
+ Form Engine”), livrat ca răspuns direct la cerința: *„am nevoie să facem o structură în care să
introduc cheile de cotare, întrebările și metoda de scorare, precum și un șablon pentru diverse
răspunsuri posibile în funcție de scor”*.

## Ce conține acest checkpoint

Un plugin WordPress funcțional (`wp-content/plugins/tpm-psych-platform/`) care oferă, prin
REST API autentificat:

1. **Administrarea instrumentelor** — creare/editare instrumente, versiuni imutabile odată
   publicate, secțiuni, itemi (7 tipuri), opțiuni de răspuns.
2. **Motorul de cotare configurabil** — scale, reguli de cotare (`sum`, `weighted_sum`, `formula`,
   `conditional`, `lookup`), evaluator de formule sigur (fără `eval()`), etaloane (`norm_sets` +
   `norm_entries`) cu validare de suprapunere/goluri.
3. **Șabloane de raport condiționate de scor** — `report_templates` cu blocuri de text legate de
   `interpretation_range_code`, plus `ReportContentResolver` care leagă un scor calculat de
   textul autorizat corespunzător.

**Ce NU conține**: nu conține fluxul de completare efectivă a unui test de către un beneficiar
(`administrations`, `responses`, tokenuri de acces, generare PDF) — acela e pasul următor din
roadmap, odată ce mecanismul de definire a instrumentelor e validat. Nu conține niciun item, cheie
de cotare, etalon sau text real al BDEFS/MCMI-3 — conform
[10-informatii-necesare-inainte-de-primul-test.md](10-informatii-necesare-inainte-de-primul-test.md),
acelea se introduc separat, prin acest mecanism, după confirmarea metodei de import.

## Decizii tehnice cheie

| Decizie | Motivație |
|---|---|
| Evaluatorul de formule (`ExpressionEvaluator`) e un parser recursive-descent scris manual, cu whitelist strict de caractere — **niciodată** `eval()`/`create_function()` | O regulă de cotare configurată în DB e input de configurare, nu cod de încredere implicit; a o executa ca PHP arbitrar ar fi RCE din propria bază de date (vezi threat model, punctul 10) |
| Componența unei scale (ce itemi intră în ea) se definește **în cadrul regulii de cotare** (`item_codes`/`weights`/`expression`), nu printr-un tabel separat „item aparține de scală” | Simplifică schema și reflectă realitatea: aceeași scală poate combina itemi diferit în formule diferite; validat totuși strict față de codurile de item existente în versiune (`FormulaDefinitionValidator`) |
| Etaloanele (`norm_sets`) au versionare **independentă** de `instrument_versions` | Permite adăugarea unui etalon nou (ex. populație diferită) fără a redeschide o versiune de instrument deja publicată — dar odată `active`, un `norm_set` e la fel de imutabil ca o versiune |
| Reversarea itemilor (`is_reverse_scored`) e informativă; valoarea de cotare corectă se introduce direct în `response_options.raw_value` la configurare | Nu presupunem o formulă generică de reversare (ex. `max+min-x`) fără să știm structura reală a scalelor BDEFS/MCMI-3 — evită inventarea unei reguli de cotare. **Cunoscut și de revizuit** când vin materialele reale, dacă editorul specifică altă convenție |
| Publicarea unei versiuni (`InstrumentVersionService::publish`) blochează dacă vreo scală nu are nicio regulă de cotare, sau dacă versiunea nu are itemi | Previne activarea accidentală a unei versiuni incomplete, folosibilă pentru o invitație reală |
| Endpointurile din acest modul verifică doar `current_user_can()` (o singură capability), fără al doilea nivel „policy per-rând” | Instrumentele sunt resurse administrate global (de `platform_admin`), nu date asociate unui client anume — al doilea nivel de autorizare (owner-per-client) devine necesar abia la modulul de clienți/administrări, conform 04-roluri-si-permisiuni.md |
| Erorile PHP neașteptate sunt jurnalizate server-side (`error_log`) și înlocuite cu un mesaj generic în răspunsul REST | Cerință de securitate explicită: „nu afișa erori PHP utilizatorilor” |
| `AuditService` e extras în spatele unei interfețe (`AuditServiceInterface`) | Permite testarea Service-urilor fără o instalare WordPress reală, prin duble de test în memorie |

## Fișiere create

```
wp-content/plugins/tpm-psych-platform/
├── tpm-psych-platform.php
├── composer.json
├── phpunit.xml.dist
├── src/
│   ├── Plugin.php
│   ├── Authorization/Capabilities.php
│   ├── Migrations/
│   │   ├── MigrationInterface.php
│   │   ├── MigrationRunner.php
│   │   └── Versions/Migration_2026_01_000_Initial.php
│   ├── Domain/
│   │   ├── Instrument/{Instrument,InstrumentVersion,Section,Item,ResponseOption,Scale}.php
│   │   ├── Scoring/{ScoringRule,NormSet,NormEntry}.php
│   │   └── Report/ReportTemplate.php
│   ├── Validation/
│   │   ├── ValidationException.php, ExpressionException.php
│   │   ├── Expression/{ExpressionToken,ExpressionTokenizer,ExpressionEvaluator}.php
│   │   ├── FormulaDefinitionValidator.php
│   │   ├── NormRangeValidator.php
│   │   └── ReportTemplateValidator.php
│   ├── Repositories/
│   │   ├── Contracts/*.php (7 interfețe)
│   │   └── Wpdb/*.php (7 implementări, prepared statements)
│   ├── Services/
│   │   ├── AuditService.php, AuditServiceInterface.php
│   │   ├── InstrumentService.php, InstrumentVersionService.php, InstrumentStructureService.php
│   │   ├── ScaleService.php, ScoringRuleService.php, NormService.php, ReportTemplateService.php
│   │   ├── ScoringEngine/
│   │   │   ├── ScoringEngineInterface.php, DefaultScoringEngine.php, ScoringResult.php, NormLookup.php
│   │   │   └── Rules/{ScoringRuleStrategyInterface,SumRule,WeightedSumRule,FormulaRule,ConditionalRule,LookupRule}.php
│   │   └── Report/{ScaleReportSection,ReportContentResolver}.php
│   ├── Http/
│   │   ├── RestRouteRegistrar.php, ExceptionResponseMapper.php
│   │   └── Controllers/Admin/{Instruments,InstrumentVersions,InstrumentStructure,Scales,ScoringRules,Norms,ReportTemplates}Controller.php
│   └── Support/{Container.php, DomainException.php}
└── tests/php/
    ├── bootstrap.php
    ├── Unit/Validation/{ExpressionEvaluatorTest,NormRangeValidatorTest}.php
    ├── Unit/ScoringEngine/{DefaultScoringEngineTest,NormLookupTest}.php
    ├── Unit/Services/InstrumentVersionServiceTest.php
    ├── Unit/Services/Report/ReportContentResolverTest.php
    └── Fixtures/InMemory{AuditService,InstrumentVersionRepository,InstrumentStructureRepository,ScaleRepository,ScoringRuleRepository}.php
```

## Migrații

`Migration_2026_01_000_Initial`: creează `tpm_organizations`, `tpm_instruments`,
`tpm_instrument_versions`, `tpm_sections`, `tpm_items`, `tpm_response_options`, `tpm_scales`,
`tpm_scoring_rules`, `tpm_norm_sets`, `tpm_norm_entries`, `tpm_report_templates`,
`tpm_audit_logs` (+ `tpm_schema_migrations` pentru urmărirea versiunilor rulate). Coloanele prin
`dbDelta()`, constrângerile `FOREIGN KEY` prin `ALTER TABLE` separat (dbDelta nu le suportă
fiabil). Seed: o organizație implicită (`id=1`).

Tabelele pentru `clients`/`administrations`/`responses`/`calculated_scores`/`reports` **nu** sunt
încă create — vin în migrația următoare, odată cu fluxul de completare.

## Endpointuri REST (namespace `tpm/v1`, toate necesită capability `tpm_manage_instruments` sau `tpm_manage_templates`)

| Metodă | Rută | Acțiune |
|---|---|---|
| GET/POST | `/admin/instruments` | listă / creare instrument |
| GET/PUT | `/admin/instruments/{id}` | detaliu / actualizare |
| POST | `/admin/instruments/{id}/archive` | arhivare |
| GET/POST | `/admin/instruments/{instrument_id}/versions` | listă / creare versiune draft |
| GET/PUT | `/admin/instrument-versions/{id}` | detaliu / actualizare metadate (doar draft) |
| POST | `/admin/instrument-versions/{id}/clone` | clonare ca draft nou |
| POST | `/admin/instrument-versions/{id}/publish` | publicare (imutabilizare) |
| GET/POST | `/admin/instrument-versions/{version_id}/sections` | secțiuni |
| GET/POST | `/admin/instrument-versions/{version_id}/items` | itemi |
| PUT/DELETE | `/admin/items/{id}` | actualizare/ștergere item (doar draft) |
| GET/POST | `/admin/items/{item_id}/response-options` | opțiuni de răspuns |
| GET/POST | `/admin/instrument-versions/{version_id}/scales` | scale |
| PUT | `/admin/scales/{id}` | actualizare scală (doar draft) |
| GET/POST | `/admin/scales/{scale_id}/scoring-rules` | reguli de cotare |
| GET/POST | `/admin/instrument-versions/{version_id}/norm-sets` | etaloane |
| POST | `/admin/norm-sets/{id}/publish` | publicare etalon |
| GET/POST | `/admin/norm-sets/{norm_set_id}/entries` | intrări etalon |
| GET/POST | `/admin/organizations/{organization_id}/report-templates` | șabloane de raport |
| POST | `/admin/report-templates/{id}/publish` | publicare șablon |

Protecție CSRF pentru utilizatorii autentificați prin cookie: standard WordPress REST API — orice
client (SPA-ul de admin, când va fi construit) trebuie să trimită header-ul `X-WP-Nonce` cu
valoarea din `wp_create_nonce('wp_rest')`, altfel WordPress core respinge automat cererea.

## Teste

`composer install && vendor/bin/phpunit` — 38 teste, toate pure (fără DB/WordPress încărcat).

**Verificare efectuată în acest checkpoint**: mediul de sandbox în care am lucrat blochează accesul
la `github.com` (politică de rețea a mediului — `packagist.org` e accesibil, dar arhivele
pachetelor PHPUnit sunt găzduite pe GitHub, care întoarce 403), deci nu am putut rula
`composer install` cu dependențele de dezvoltare aici. Nu am ocolit acest blocaj. În loc să declar
testele „scrise, deci corecte”, am rulat `php -l` pe toate fișierele (fără erori de sintaxă) și am
executat manual, cu `php` simplu, 32 de verificări echivalente cu scenariile din suita PHPUnit (aceeași
logică, assert-uri simple în loc de adnotări PHPUnit) — toate au trecut. Suita PHPUnit propriu-zisă
rămâne în `tests/php/` și trebuie rulată cu `vendor/bin/phpunit` într-un mediu cu acces la
GitHub/Packagist (mașina ta locală sau CI) înainte de a considera acest modul validat definitiv.

- `ExpressionEvaluatorTest` — aritmetică corectă, precedență, paranteze, împărțire la zero,
  variabile necunoscute, **respingere explicită a caracterelor neautorizate** (inclusiv o
  încercare simulată de injectare `system(...)`/`;`).
- `DefaultScoringEngineTest` — toate cele 5 tipuri de reguli, cu date demonstrative fabricate;
  verifică atât calculul corect cât și marcarea ca „incomplet” la itemi lipsă.
- `NormLookupTest` — găsirea intervalului corect (inclusiv la limitele exacte), scor în afara
  oricărui interval.
- `NormRangeValidatorTest` — respinge intervale suprapuse și inversate, detectează goluri.
- `InstrumentVersionServiceTest` — imutabilitatea versiunilor publicate, clonare, deprecierea
  versiunii anterioare la publicare, blocarea publicării fără itemi.
- `ReportContentResolverTest` — lanțul complet scor → interval etalon → text autorizat din
  șablon, cu cazuri de protocol incomplet și scor în afara etalonului.

## Instrucțiuni de instalare/verificare

```bash
cd wp-content/plugins/tpm-psych-platform
composer install
vendor/bin/phpunit
```

Pentru activare într-o instalație WordPress reală: copiază folderul în `wp-content/plugins/`,
rulează `composer install --no-dev` acolo, apoi activează pluginul din `wp-admin → Plugins`.
Activarea rulează automat migrațiile și înregistrează capabilities (`administrator`-ul site-ului
primește `tpm_manage_instruments`/`tpm_manage_templates` automat).

## Probleme cunoscute / limitări asumate în acest checkpoint

1. Nu există încă interfață de administrare React — toate operațiile se fac prin REST API direct
   (ex. Postman/curl). Construirea `admin-app/` e pasul următor logic, dar am preferat să validez
   întâi mecanismul de date/business logic, care e partea greu de schimbat ulterior.
2. Reversarea itemilor (`is_reverse_scored`) e doar informativă (vezi tabelul de decizii) — de
   revizuit quando vin regulile reale ale BDEFS/MCMI-3, dacă editorul cere altă convenție.
3. `NormService::publishSet` deprecă *orice* alt etalon activ al aceleiași versiuni de instrument,
   ignorând diferențierea pe `applicable_criteria` (ex. etaloane diferite pe vârstă/sex active
   simultan) — funcționează corect doar pentru cazul „un etalon activ per versiune”. De extins
   când apare nevoia reală de etaloane diferențiate concurente.
4. Nu există încă rate limiting la nivelul acestor endpointuri admin (mai puțin critic aici, dat
   fiind că sunt protejate de autentificare + capability, spre deosebire de endpointurile publice
   de completare a testului, care nu există încă).
5. `Migration_2026_01_000_Initial` nu are o cale de rollback (`down()`) — pentru acest stadiu
   incremental, revenirea la o versiune de cod anterioară se face fără downgrade de schemă
   (migrațiile sunt aditive), conform strategiei de rollback din 08-roadmap-implementare.md.

## Pas următor propus

Modulul „Invitații/Tokenuri + interfața respondentului” (pasul 3 din roadmap), care va introduce
`tpm_clients`, `tpm_administrations`, `tpm_administration_tokens`, `tpm_responses` și va conecta
`DefaultScoringEngine`/`ReportContentResolver` (deja construite și testate aici) la răspunsuri
reale, prin un `ScoringOrchestrator` care rămâne de scris.
