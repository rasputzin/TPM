# Tehnologii, biblioteci și criterii de alegere

## Stack propus

| Strat | Tehnologie | Justificare |
|---|---|---|
| Backend | PHP 8.1+ | Minim necesar pentru sintaxă modernă (enums, readonly properties, tipuri union) folosită pentru claritatea Domain Models; **NECESITĂ DECIZIE** confirmarea versiunii disponibile pe hosting |
| Framework aplicativ | Niciunul extern — WordPress ca „framework” de bază (auth, REST routing, cron/hooks) + structură proprie stratificată | Evită dependență suplimentară grea; WordPress oferă deja auth/capabilities/nonces necesare |
| Dependency management | Composer, autoload PSR-4 | Standard, necesar pentru structura pe clase/interfețe |
| Bază de date | MySQL 8 / MariaDB 10.6+, InnoDB, utf8mb4 | Suport corect diacritice, JSON nativ pentru câmpurile auxiliare validate |
| Cron/queue | Action Scheduler (biblioteca folosită și de WooCommerce, matură, testată la scară) | Evită cron WP nativ nefiabil pentru joburi critice (expirare tokenuri, retenție, trimitere e-mail) |
| REST API | WP REST API (namespace `tpm/v1`) | Reutilizează infrastructura de autentificare/nonces din WordPress |
| Frontend admin/psiholog | React + TypeScript, build cu `@wordpress/scripts` sau Vite | TypeScript reduce erorile la nivelul contractelor DTO ↔ formular; ecosistem matur pentru dashboard-uri cu tabele/filtre |
| Frontend respondent | React + TypeScript, aplicație minimală, fără dependențe de design system Divi | Cerință explicită: interfață simplă, fără elemente care distrag atenția |
| State/form management frontend | React Query (sau echivalent) pt. sincronizare cu API + autosave; React Hook Form pt. validare formular | Simplifică autosave și gestionarea stării de reluare a testului |
| PDF | *A se vedea secțiunea dedicată mai jos* | |
| Testare backend | PHPUnit + WP_Mock/Brain Monkey pentru izolare de WordPress core la teste unitare | |
| Analiză statică | PHPStan (nivel ridicat, min. 6-8) | Detectează erori de tip devreme, important pt. `ScoringEngine` |
| Coding standards | PHPCS + WordPress Coding Standards (WPCS) | Consistență, cerută explicit |
| Testare frontend | Vitest/Jest + React Testing Library | |
| Testare E2E | Playwright (deja disponibil în mediul de dezvoltare) | Fluxurile critice: invitație → completare → cotare → raport → validare |
| CI | Pipeline (GitHub Actions sau echivalent) rulând PHPCS, PHPStan, PHPUnit, lint frontend, teste E2E pe fluxurile critice | |

## Criterii de alegere a bibliotecii PDF

Cerințe obligatorii (din specificație): HTML/CSS controlabil, fonturi Unicode (diacritice
românești corecte), paginare A4, antet/subsol, prevenirea tăierii tabelelor, mentenanță
rezonabilă, **licență verificată** înainte de alegere finală.

| Bibliotecă | HTML/CSS | Unicode/diacritice RO | Paginare/antet-subsol | Mentenanță | Licență (de verificat la momentul integrării) | Notă |
|---|---|---|---|---|---|---|
| **Dompdf** | Bun (subset CSS 2.1 + parțial CSS3) | Da, cu fonturi TTF embedate explicit | Da, inclusiv `page-break` | Activă, folosită pe scară largă în ecosistem WP/PHP | LGPL 2.1 (de reconfirmat versiunea exactă la integrare) | Cel mai simplu de integrat într-un plugin WP; suficient pentru rapoarte tabelare + grafice ca imagini |
| **mPDF** | Foarte bun, suport extins CSS + limbi RTL | Da, foarte bun suport Unicode nativ | Da, control fin (headers/footers per secțiune) | Activă | GPL v2+ (de reconfirmat) | Motor mai puternic decât Dompdf pentru layout-uri complexe; fișier de output puțin mai mare, timp de generare puțin mai mare |
| **TCPDF** | Slab pt. HTML/CSS complex, API mai degrabă „desenare directă” | Da | Da, control foarte fin dar manual | Activă, foarte veche/stabilă | LGPL | Potrivit dacă se dorește control absolut pixel-perfect, dar productivitate mai mică pentru șabloane HTML/CSS întreținute de non-dezvoltatori |
| **Browsershot/Puppeteer (Chromium headless → PDF)** | Excelent (CSS complet, real browser rendering) | Excelent | Excelent (CSS `@page` complet) | Depinde de disponibilitatea unui binar Chromium pe server | MIT (wrapper), dar necesită Chromium instalat — implicații de resurse server | Cea mai bună fidelitate vizuală, dar cost operațional mai mare (memorie/CPU, proces separat) — posibil excesiv pt. hosting shared |

**Recomandare inițială: Dompdf**, pentru echilibrul simplitate/mentenanță/compatibilitate cu un
hosting WordPress obișnuit, cu opțiunea de migrare la **mPDF** dacă șabloanele de raport devin
suficient de complexe (tabele multi-pagină cu control fin de layout) încât limitările CSS ale
Dompdf devin un obstacol real. Ambele sunt suficient de mature pentru fonturi Unicode embedate
legal (ex. familii open-source precum Noto Sans, cu licență OFL, care acoperă corect diacriticele
românești).

**Important**: alegerea finală trebuie să reconfirme la momentul integrării versiunea exactă a
licenței pachetului Composer folosit (LGPL/GPL au implicații diferite de distribuție dacă
pluginul ar fi vreodată distribuit terților, nu doar folosit intern) — marcat
**NECESITĂ VERIFICARE JURIDICĂ** dacă apare vreodată intenția de a redistribui pluginul, nu doar
de a-l folosi intern.

## Fonturi pentru PDF

Necesită o familie de fonturi cu acoperire completă a diacriticelor românești (ă, â, î, ș, ț) și
licență compatibilă cu embedarea legală într-un document generat (nu doar afișare pe ecran) — ex.
familii open-source cu licență OFL (Open Font License), embedate explicit în bibliotecă, nu
depinde de fonturile instalate pe serverul de generare.
