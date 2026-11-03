---
name: compacteaza
description: Comprimă o sesiune lungă într-un rezumat dens, salvat ca fișier Markdown, ca să poți continua într-un context curat fără să pierzi firul. Folosește-l când utilizatorul scrie /compacteaza sau spune că sesiunea e prea lungă / „mănâncă” contextul.
argument-hint: "[pe ce să se concentreze rezumatul, opțional]"
disable-model-invocation: true
allowed-tools: Read, Write, Glob, Bash(date:*), Bash(mkdir:*), Bash(git status:*), Bash(git log:*), Bash(git diff:*)
---

# /compacteaza — sesiune lungă → rezumat scurt

Rolul acestui skill: **să ia o sesiune foarte lungă și să o facă scurtă**, ca
lucrul să poată continua fără să consume tot contextul.

Diferența față de `/jurnal`: jurnalul e pentru *tine, mai târziu* (memorie pe
termen lung). Compactarea e pentru *Claude, acum* — un rezumat de lucru, cât mai
dens, din care sesiunea poate fi reluată imediat.

Dacă `$ARGUMENTS` e dat, păstrează mai mult detaliu pe subiectul acela și
comprimă mai agresiv restul.

## Pași

1. Rulează `date +"%Y-%m-%d-%H%M"` pentru numele fișierului.
2. Dacă e un repo git, verifică rapid `git status` și `git log --oneline -5`
   ca starea descrisă să corespundă realității.
3. Scrie rezumatul în `jurnal/compact/AAAA-LL-ZZ-HHMM-slug.md` după șablonul de
   mai jos. Țintă: **sub 80 de rânduri**, indiferent cât de lungă a fost sesiunea.
4. Răspunde utilizatorului cu:
   - calea fișierului;
   - instrucțiunea de continuare:
     > Rulează `/clear`, apoi scrie: `Continuăm din @jurnal/compact/<fișier>.md`

## Ce păstrezi / ce arunci

**Păstrezi:** obiectivul, constrângerile și preferințele exprimate de
utilizator, deciziile luate (cu motivul), starea exactă a lucrului, fișierele
relevante (căi exacte), comenzile care funcționează, erorile încă nerezolvate
(mesajul exact, pe scurt), următorul pas concret.

**Arunci:** încercările abandonate (păstrează doar „X nu merge pentru că Y”),
output-uri lungi de comenzi, conținut de fișiere care poate fi recitit oricând,
explicații deja înțelese, politețuri.

## Șablon

```markdown
---
tip: compact
data: AAAA-LL-ZZ HH:MM
proiect: <nume>
---

# Compact: <subiect>

## Obiectiv
O propoziție: ce încercăm să obținem.

## Context și constrângeri
- Preferințe / reguli exprimate de utilizator.
- Limite tehnice relevante.

## Stare actuală
- Gata: ...
- În lucru: ... (unde exact s-a oprit)
- Blocat: ... (eroarea, pe scurt)

## Decizii
- Decizie — motiv.

## Fișiere cheie
- `cale/fisier` — rol / ce s-a schimbat.

## Nu merge / evită
- X — pentru că Y.

## Următorul pas
1. Acțiunea concretă imediată.
2. ...
```

## Reguli

- Densitate maximă: liste, fără proză de umplutură.
- Nu inventa progres. Dacă ceva nu e verificat, scrie „neverificat”.
- Fără parole, token-uri sau chei API.
- Notă: `/compact` (fără „-eaza”) e comanda încorporată a Claude Code care
  comprimă contextul automat, fără fișier. Skill-ul acesta face același lucru,
  dar lasă un fișier pe care îl controlezi și îl poți reciti.
