# Comparația celor patru arhitecturi

## Varianta 1 — Plugin WordPress monolitic, dar strict modular

Tot codul de business rulează în procesul PHP al WordPress, ca plugin propriu, cu straturi
interne separate (Controllers / Services / Repositories / Domain), API REST intern folosit chiar
și de propriul frontend React, DB proprie (tabele custom, nu doar postmeta).

| Criteriu | Evaluare |
|---|---|
| Avantaje | Un singur deployment, o singură bază de utilizatori/sesiuni (WP), reutilizează auth/capabilities/nonces WP, cost hosting minim, timp de dezvoltare mai scurt, integrare naturală cu Divi via shortcode/page builder module |
| Dezavantaje | Cuplat la ciclul de viață WordPress (update-uri core/plugin-uri terțe pot afecta stabilitatea); scalare orizontală mai dificilă la volum foarte mare; disciplina de modularizare trebuie impusă manual (risc de „plugin spaghetti” dacă nu se respectă separarea pe straturi) |
| Nivel de securitate | Bun, **dacă** se respectă riguros WP capabilities, prepared statements, nonces, REST permission callbacks. Suprafața de atac include tot ce e instalat în același WordPress (alte plugin-uri vulnerabile pot compromite indirect datele) |
| Complexitate dezvoltare | Medie |
| Cost hosting/mentenanță | Scăzut–mediu (un singur server/hosting gestionat) |
| Scalabilitate | Bună pentru cabinet individual/echipă mică–medie; necesită efort suplimentar (cache, queue, DB tuning) peste câteva mii de evaluări active/lună |
| Compatibilitate Divi | Foarte bună — shortcode-uri/module Divi pot încărca ecranele pluginului fără cuplare puternică |
| Ușurință adăugare teste noi | Foarte bună, **dacă** modelul de date e generic de la început (vezi schema din 03) |
| Risc vendor lock-in | Scăzut față de WordPress ca ecosistem (cod PHP standard, DB proprie portabilă), dar există dependență de mediul de execuție WP pentru auth/cron/rutare |
| Adecvare date sensibile | Bună, cu criptare la nivel de câmp pentru date clinice și izolare a accesului la fișierele PDF, independent de restul WordPress-ului |

## Varianta 2 — Plugin WordPress + aplicație frontend separată (SPA dedicat)

WordPress rămâne backend (REST API + DB), dar interfața de completare și/sau dashboard-ul
psihologului sunt o aplicație SPA separată (build distinct, posibil găzduită separat sau ca bundle
static încărcat în pagină).

| Criteriu | Evaluare |
|---|---|
| Avantaje | UX mai curat, izolat de tema Divi; posibilitate de deploy independent al frontendului; testare frontend mai ușoară |
| Dezavantaje | Două cicluri de build/deploy de sincronizat; complexitate suplimentară de autentificare cross-origin (CORS, cookie-uri, CSRF) dacă e găzduit separat; overhead de infrastructură fără beneficiu proporțional la acest volum |
| Nivel de securitate | Similar cu Varianta 1 dacă rămâne pe același domeniu; scade dacă e pe subdomeniu separat (suprafață CORS suplimentară) |
| Complexitate dezvoltare | Medie–mare (două aplicații de menținut) |
| Cost hosting/mentenanță | Mediu (posibil hosting suplimentar pentru SPA, sau doar build tooling suplimentar dacă rămâne servit din WP) |
| Scalabilitate | Ușor mai bună pentru interfața de completare (poate fi servită static/CDN) |
| Compatibilitate Divi | Bună pentru părțile administrative; interfața respondentului fiind complet separată de Divi, izolarea e chiar mai curată |
| Ușurință adăugare teste noi | Bună, dar schema de formular trebuie sincronizată strict cu backend-ul (contract API) |
| Risc vendor lock-in | Scăzut, cod portabil, dar crește complexitatea operațională fără câștig arhitectural real față de V1 la acest volum |
| Adecvare date sensibile | Bună, echivalentă cu V1, cu grijă suplimentară la CORS/CSRF dacă domenii diferite |

**Notă:** părțile React ale Variantei 1 (dashboard psiholog, interfață respondent) sunt oricum
„aplicații frontend separate” din punct de vedere al codului — diferența reală față de V2 e doar
dacă sunt *găzduite și deployate* separat de WordPress. Recomand păstrarea codului frontend
separat logic (pachet npm propriu în monorepo) dar **servit din același domeniu WordPress**,
combinând avantajele ambelor fără costul operațional al V2 pure.

## Varianta 3 — WordPress ca interfață, backend extern dedicat (microserviciu separat)

WordPress devine doar „shell” de prezentare/autentificare front-office; toată logica (instrumente,
cotare, rapoarte, DB clinică) rulează într-un serviciu backend separat (ex. Node/Laravel/FastAPI +
DB proprie), cu care WordPress comunică prin API.

| Criteriu | Evaluare |
|---|---|
| Avantaje | Separare maximă a datelor clinice de restul WordPress-ului (reduce suprafața de atac moștenită din alte plugin-uri); tehnologie backend aleasă liber; scalare independentă a componentei critice |
| Dezavantaje | Complexitate operațională mult mai mare (două sisteme de auth de sincronizat, două infrastructuri, două pipeline-uri de deploy, două suprafețe de securitate de auditat); cost și timp de dezvoltare mult mai mari; peste capacitatea justificată de volumul inițial (cabinet individual) |
| Nivel de securitate | Poate fi cel mai ridicat *în teorie* (izolare completă), dar în practică crește riscul global dacă echipa/procesul nu poate menține corect **două** sisteme cu standarde înalte simultan |
| Complexitate dezvoltare | Mare |
| Cost hosting/mentenanță | Mare (server/serviciu backend suplimentar, monitorizare dublă, DevOps dedicat) |
| Scalabilitate | Foarte bună, dar e o soluție pentru scară pe care platforma nu o are încă |
| Compatibilitate Divi | Doar la nivel de „vitrină”; toată interacțiunea reală ocolește WordPress, deci beneficiul Divi e minim |
| Ușurință adăugare teste noi | Bună tehnic, dar orice schimbare de model de date implică sincronizare de contract API între 2 sisteme |
| Risc vendor lock-in | Scăzut tehnic, dar risc operațional ridicat (dependență de o a doua platformă de hosting/DevOps) |
| Adecvare date sensibile | Foarte bună dacă bine implementată, dar overkill pentru volumul curent — riscul de configurare greșită într-un sistem nou, sub presiune de timp, poate fi mai periculos decât beneficiul teoretic de izolare |

## Varianta 4 — Arhitectură pregătită de la început pentru transformare în SaaS

Aceasta nu e o alternativă la 1–3, ci o **dimensiune transversală**: cât de mult se investește
încă din prima versiune în multi-tenancy, facturare, branding per organizație etc.

| Criteriu | Evaluare |
|---|---|
| Avantaje | Evită o rescriere costisitoare dacă platforma crește spre mai multe cabinete/organizații |
| Dezavantaje | Investiție de timp „upfront” în capacități neutilizate în MVP (organizații multiple, planuri, credite) |
| Nivel de securitate | Neutru — depinde de implementare, nu de opțiunea în sine |
| Complexitate dezvoltare | Mai mare inițial (schema DB cu `organization_id` peste tot, izolare logică), dar previne o refactorizare majoră ulterioară |
| Cost hosting/mentenanță | Neutru la volum mic; se amortizează dacă apare nevoia SaaS |
| Scalabilitate | Cel mai bun scenariu pe termen lung |
| Compatibilitate Divi | Neutru |
| Ușurință adăugare teste noi | Neutru |
| Risc vendor lock-in | Scăzut |
| Adecvare date sensibile | Foarte bună — izolarea per organizație de la început e un plus GDPR (limitează „blast radius” al unei breșe) |

## Recomandare

**Varianta 1 (plugin WordPress modular, strict stratificat) + principiul Variantei 4 aplicat la
schema de date (izolare logică pe `organization_id` de la primul tabel), fără a activa
funcționalitatea multi-organizație reală în MVP.**

Detalii și justificare completă în
[02-arhitectura-recomandata.md](02-arhitectura-recomandata.md).
