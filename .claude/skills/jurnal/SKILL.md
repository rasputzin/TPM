---
name: jurnal
description: Extrage ce e important din sesiunea curentă și salvează un fișier Markdown de jurnal (compatibil Obsidian), organizat pe an/lună. Folosește-l când utilizatorul scrie /jurnal sau cere să "pună sesiunea în jurnal".
argument-hint: "[subiect sau notă opțională]"
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Glob, Bash(date:*), Bash(mkdir:*), Bash(git log:*), Bash(git diff:*), Bash(git status:*)
---

# /jurnal — jurnalul sesiunii

Rolul acestui skill: **să structureze informațiile din sesiunea curentă** într-o
singură notă Markdown, ușor de citit și de regăsit mai târziu în Obsidian.

Nu copia sesiunea. **Extrage doar ce contează**: ce s-a decis, ce s-a făcut, ce
s-a învățat, ce a rămas de făcut. Tot restul (încercări eșuate fără lecție,
output-uri lungi, discuții de detaliu) se lasă afară.

Argumentul opțional (`$ARGUMENTS`) e un subiect sau o notă de la utilizator —
folosește-l pentru titlu și pune-l în secțiunea de rezumat.

## Pași

1. **Data și ora**: rulează `date +"%Y-%m-%d %H:%M"`. Nu ghici data.
2. **Tipul sesiunii** — alege unul: `aplicatie`, `site`, `cod`, `design`,
   `cercetare`, `planificare`, `personal`, `altele`.
3. **Titlu scurt** (3–6 cuvinte) și un **slug** din el: litere mici, fără
   diacritice, cuvinte legate cu `-` (ex. `login-translator-fix`).
4. **Calea fișierului**:
   `jurnal/sesiuni/AAAA/LL/AAAA-LL-ZZ-slug.md` (relativ la rădăcina proiectului).
   Creează folderele dacă lipsesc. Dacă fișierul există deja (aceeași zi, același
   subiect), **adaugă** o secțiune nouă `## Continuare — HH:MM` la final, nu
   suprascrie.
5. **Scrie nota** după șablonul de mai jos. Secțiunile goale se șterg, nu se
   lasă cu „N/A”.
6. **Indexul lunii**: în `jurnal/sesiuni/AAAA/LL/_index.md` adaugă o linie
   `- [[AAAA-LL-ZZ-slug]] — titlu (tip)`. Creează fișierul dacă nu există, cu
   antetul `# Jurnal AAAA-LL`.
7. **Răspunde scurt**: calea fișierului + 3–5 rânduri cu ce ai reținut. Nu
   repeta toată nota în chat.

## Șablon

```markdown
---
data: AAAA-LL-ZZ
ora: HH:MM
tip: aplicatie
proiect: <numele proiectului / repo-ului>
tags: [jurnal, <tip>, <2-4 etichete de subiect>]
---

# <Titlu>

## Rezumat
2–4 propoziții: ce s-a urmărit și unde s-a ajuns.

## Ce s-a făcut
- Acțiuni concrete, cu fișierele atinse (`cale/fisier.ext`) și commit-uri dacă există.

## Decizii
- **Decizia** — de ce (alternativa respinsă, pe scurt).

## Ce am învățat
- Lucruri non-evidente, capcane, comenzi utile, cauze de bug-uri.

## De făcut
- [ ] Pași rămași, în ordinea priorității.

## Întrebări deschise
- Ce nu e lămurit și cine/ce ar trebui să răspundă.

## Legături
- [[note înrudite din jurnal]], link-uri externe relevante.
```

## Reguli

- Scrie în limba în care a decurs sesiunea (implicit română).
- Concis: o notă tipică are 20–60 de rânduri. O sesiune lungă nu înseamnă o notă lungă.
- Fapte, nu presupuneri. Dacă ceva nu e sigur, marchează-l „(neconfirmat)”.
- Nu pune parole, token-uri, chei API sau date personale sensibile în jurnal.
- Etichetele (`tags`) fără spații și fără diacritice, ca Obsidian să le grupeze corect.
- Reorganizarea ulterioară (mutări, unificări de note) e permisă — structura de
  mai sus e punctul de plecare, nu o regulă fixă.
