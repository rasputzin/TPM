# Structura repository-ului / pluginului

## Nume propus

Slug plugin: `tpm-psych-platform` (namespace PHP `TpmPsych`) — de confirmat un nume final; folosit
aici doar ca exemplu consistent în restul documentelor.

## Layout de nivel înalt (monorepo, plugin + frontend ca pachete separate)

```
wp-content/plugins/tpm-psych-platform/
├── tpm-psych-platform.php          # bootstrap plugin, header WP, verificări versiune PHP/WP
├── composer.json                   # autoload PSR-4, dependențe backend
├── uninstall.php                   # curățare condiționată, NU implicită
├── readme.txt                      # standard WordPress.org (dacă se distribuie), changelog
├── build/                          # assets compilate (generate, .gitignore)
├── src/                            # cod PHP, PSR-4: TpmPsych\
│   ├── Plugin.php                  # orchestrare activare/dezactivare/hooks
│   ├── Http/
│   │   ├── Controllers/            # câte un controller per resursă REST (subțiri, deleagă la Services)
│   │   │   ├── Admin/
│   │   │   ├── Psychologist/
│   │   │   └── Session/            # endpointuri token-based pt. respondenți
│   │   ├── Middleware/             # rate limiting, nonce/token validation
│   │   └── RestRouteRegistrar.php
│   ├── Domain/
│   │   ├── Instrument/              # Models, DTOs specifice instrumentelor
│   │   ├── Administration/
│   │   ├── Scoring/
│   │   ├── Report/
│   │   ├── Client/
│   │   └── Consent/
│   ├── Services/
│   │   ├── InstrumentService.php
│   │   ├── FormEngineService.php
│   │   ├── InvitationService.php
│   │   ├── ScoringEngine/
│   │   │   ├── ScoringEngineInterface.php
│   │   │   ├── DefaultScoringEngine.php
│   │   │   └── Rules/              # strategii per `rule_type` (sum, weighted_sum, formula, ...)
│   │   ├── ReportService.php
│   │   ├── ConsentService.php
│   │   ├── AuditService.php
│   │   └── RetentionService.php
│   ├── Authorization/
│   │   ├── Capabilities.php         # definirea capabilities custom la activare
│   │   └── Policies/                # ClientPolicy, AdministrationPolicy, ReportPolicy...
│   ├── Repositories/
│   │   ├── Contracts/               # interfețe (pt. testabilitate/DI)
│   │   └── Wpdb/                    # implementare cu $wpdb, prepared statements
│   ├── Validation/                  # Validators pentru fiecare tip de input
│   ├── Pdf/
│   │   ├── PdfRendererInterface.php
│   │   └── DompdfRenderer.php       # sau alt renderer ales — vezi 09-tehnologii-si-biblioteci.md
│   ├── Infrastructure/
│   │   ├── Mailer.php
│   │   ├── TokenGenerator.php       # generare + hashing tokenuri
│   │   ├── EncryptionService.php    # criptare câmpuri sensibile
│   │   └── Queue/                   # integrare Action Scheduler
│   ├── Migrations/
│   │   ├── MigrationRunner.php
│   │   └── Versions/                # Migration_2025_01_000_initial.php etc.
│   ├── Integration/
│   │   ├── Divi/                    # shortcode-uri, module Divi opționale
│   │   └── Gutenberg/               # bloc opțional pt. inserare interfață
│   └── Support/                     # helpers, exceptions custom
├── admin-app/                       # pachet frontend — dashboard admin/psiholog (React + TS)
│   ├── package.json
│   ├── src/
│   └── vite.config.ts               # sau echivalent wp-scripts
├── respondent-app/                  # pachet frontend — interfață respondent (React + TS, minimal)
│   ├── package.json
│   └── src/
├── tests/
│   ├── php/
│   │   ├── Unit/                    # PHPUnit — Services, ScoringEngine, Validators
│   │   └── Integration/             # cu DB de test WP
│   └── e2e/                         # Playwright — fluxuri critice (vezi 08-roadmap)
├── docs/                            # documentație operațională (instalare, runbook)
└── .github/ (sau echivalent CI)     # pipeline lint + test
```

## Convenții de cod

- **Namespace PSR-4**: `TpmPsych\` → `src/`; autoload gestionat de Composer, **fără** includere
  manuală de fișiere (`require` presărat) — respectă cerința „fără logică disparată”.
- **O clasă = o responsabilitate**: controllerele nu conțin logică de business, doar
  orchestrare (validare input → apel Service → formatare răspuns); Service-urile nu fac SQL
  direct, ci apelează Repository-uri prin interfețe.
- **Dependency Injection minimal, dar real**: un container simplu (ex. PHP-DI sau un container
  propriu minimalist) instanțiază Service-urile cu dependențele lor (Repository, Logger,
  Encryption) — permite înlocuirea `DompdfRenderer` cu alt renderer, sau a `ScoringEngine`
  implicit cu unul specific unui instrument, fără a atinge controllerele.
- **Fișiere mici**: un fișier per clasă/interfață; niciun fișier „god class” de mii de linii —
  aplicabil strict la `ScoringEngine/Rules/*`, unde fiecare tip de regulă e propria clasă.

## Integrare Divi — cuplare slabă

- Shortcode-uri (`[tpm_respondent_form]`, `[tpm_psychologist_dashboard]`) montează aplicațiile
  React în `div`-uri cu date minime (ex. token din URL), fără dependență de markup-ul temei.
- Modulele Divi custom (opționale, versiune ulterioară) sunt un **wrapper subțire** peste aceleași
  shortcode-uri — nu duplică logică.
- Dacă tema Divi e dezactivată/înlocuită: pluginul continuă să funcționeze (rutare proprie prin
  REST API + pagini virtuale dacă e necesar acces direct), se pierde doar aspectul vizual
  încadrat de temă, conform cerinței explicite.

## Composer — schiță `composer.json` (ilustrativ, nu final)

```json
{
    "name": "yourorg/tpm-psych-platform",
    "type": "wordpress-plugin",
    "require": {
        "php": ">=8.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.0",
        "squizlabs/php_codesniffer": "^3.0",
        "wp-coding-standards/wpcs": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "TpmPsych\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "TpmPsych\\Tests\\": "tests/php/"
        }
    }
}
```

Alegerea bibliotecii PDF concrete (adăugată ca dependență `require`) e detaliată separat în
[09-tehnologii-si-biblioteci.md](09-tehnologii-si-biblioteci.md), după compararea opțiunilor.
