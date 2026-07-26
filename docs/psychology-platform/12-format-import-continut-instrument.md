# Cum îmi dai itemii, cheile de cotare și etaloanele

Mecanismul tehnic (modulul „Administrarea instrumentelor”) e gata să primească exact aceste date —
problema rămasă e **prin ce canal** ajung la mine, ca să nu încalce nici arhitectura stabilită
(„conținutul protejat nu ajunge niciodată în fișiere din repository”), nici, eventual, contractul
tău de licență.

## O distincție juridică de verificat înainte să trimiți orice

Ai confirmat că licența permite **administrarea electronică** a testelor și **generarea automată
de rapoarte**. Asta e o întrebare diferită de: *„îmi permite licența să încredințez textul
itemilor unui terț — în acest caz, un serviciu de AI (Claude/Anthropic) — în scop de
configurare?”* Multe licențe de instrumente psihometrice (mai ales cele comerciale, gen
Pearson/MHS) restricționează explicit cine poate avea acces la itemi, nu doar cum sunt
administrați către respondenți. **NECESITĂ VERIFICARE JURIDICĂ / VERIFICAREA LICENȚEI** — verifică
punctul acesta explicit înainte de a-mi trimite conținutul real, nu presupune că răspunsul de mai
devreme („da, licența permite administrare electronică”) acoperă și acest aspect.

## Trei moduri posibile de a-mi trimite conținutul — alege în funcție de răspunsul de mai sus

1. **Îl lipești direct în acest chat**, în formatul structurat de mai jos. E cel mai rapid, dar
   înseamnă că textul trece prin această conversație (deci, indirect, prin infrastructura
   Anthropic) — potrivit doar dacă ai verificat că licența permite asta.
2. **Îmi trimiți un fișier** (JSON, conform template-ului de mai jos) — dacă interfața pe care o
   folosești permite atașarea de fișiere în conversație, îl pot citi direct de pe disc. Practic
   echivalent cu varianta 1 din punct de vedere al expunerii conținutului către mine, doar mai
   ordonat pentru seturi mari de itemi.
3. **Aștepți până construim ecranul de administrare (admin UI) și un WordPress real** pe care să-l
   folosești, și introduci itemii/regulile direct în platformă, prin formularul din
   `wp-admin` — eu nu văd niciodată textul. E varianta cea mai aliniată cu principiul „cheile de
   cotare nu ajung la un terț”, dar presupune mai întâi să construim acel UI și să ai unde să-l
   rulezi (hosting/instalație WordPress de test).

Indiferent ce alegi la 1 sau 2: **conținutul nu va ajunge niciodată într-un fișier commis în git**.
Îl folosesc doar ca să scriu un script de import (seed) care populează baza de date atunci când
pluginul rulează pe un WordPress real — scriptul de import (fără conținut) poate fi în git, dar
fișierul cu datele reale rămâne local, într-un folder exclus explicit prin `.gitignore`.

## Formatul JSON așteptat

Un fișier per instrument (sau per versiune). Exemplul de mai jos e complet **fictiv** — arată doar
structura, nu conține niciun item real.

```json
{
  "instrument": {
    "code": "DEMO-1",
    "name": "Instrument demonstrativ",
    "rights_holder": "Editura X",
    "license_reference": "Contract nr. ... din ...",
    "target_population": "Adulți 18-65"
  },
  "version": {
    "version_label": "1.0.0",
    "language": "ro",
    "estimated_duration_minutes": 15,
    "instructions_text": "Instrucțiunile oficiale afișate respondentului înainte de start.",
    "missing_response_policy": {
      "max_missing_per_scale": 2,
      "note": "descrie aici regula reală, dacă manualul o specifică"
    }
  },
  "sections": [
    { "code": "S1", "title": "Partea I", "order_index": 0 }
  ],
  "items": [
    {
      "code": "Q1",
      "section_code": "S1",
      "prompt_text": "Textul exact al itemului 1.",
      "item_type": "likert",
      "is_reverse_scored": false,
      "is_required": true,
      "order_index": 0,
      "response_options": [
        { "label_text": "Niciodată", "raw_value": 0, "order_index": 0 },
        { "label_text": "Rar", "raw_value": 1, "order_index": 1 },
        { "label_text": "Des", "raw_value": 2, "order_index": 2 },
        { "label_text": "Foarte des", "raw_value": 3, "order_index": 3 }
      ]
    }
  ],
  "scales": [
    { "code": "TOTAL", "name": "Scor total" }
  ],
  "scoring_rules": [
    {
      "scale_code": "TOTAL",
      "rule_type": "sum",
      "formula_definition": { "item_codes": ["Q1"] }
    }
  ],
  "norm_sets": [
    {
      "label": "Etalon adulți RO",
      "version_label": "1.0.0",
      "applicable_criteria": { "age_min": 18, "age_max": 65 },
      "entries": [
        {
          "scale_code": "TOTAL",
          "raw_score_min": 0,
          "raw_score_max": 5,
          "standard_score_type": "t_score",
          "standard_score_value": 40,
          "interpretation_range_code": "low"
        }
      ]
    }
  ],
  "report_template": {
    "version_label": "1.0.0",
    "interpretation_texts": [
      {
        "interpretation_range_code": "low",
        "title": "Nivel scăzut",
        "body": "Textul autorizat, exact așa cum ai dreptul să-l reproduci în raport."
      }
    ]
  }
}
```

Tipurile de `rule_type` acceptate și forma lor exactă (`sum`, `weighted_sum`, `formula`,
`conditional`, `lookup`) sunt documentate în cod, cu exemple, în
`wp-content/plugins/tpm-psych-platform/src/Validation/FormulaDefinitionValidator.php`.

## Ce fac cu el după ce îl primesc

1. Validez structura față de mecanismul deja construit (coduri de item unice, scale referite corect
   în reguli, intervale de etalon fără suprapuneri/goluri).
2. Scriu un script de import care apelează serviciile deja existente
   (`InstrumentService`, `InstrumentVersionService`, `ScoringRuleService`, `NormService`,
   `ReportTemplateService`) — aceleași care validează totul și înregistrează audit — nu inserții
   SQL brute.
3. Fișierul JSON cu conținutul real e salvat în
   `wp-content/plugins/tpm-psych-platform/import-data/` — folder deja adăugat în `.gitignore`,
   deci `git status`/`git add -A` nu-l poate include accidental într-un commit. Doar scriptul de
   import (gol de conținut) intră în git.
4. Rulez testele de regresie de cotare pe câteva cazuri pe care tu le confirmi manual (protocoale
   cotate deja de tine, cu rezultat cunoscut), ca să validăm că motorul calculează identic cu
   cotarea manuală, înainte de a considera instrumentul gata de uz real.
