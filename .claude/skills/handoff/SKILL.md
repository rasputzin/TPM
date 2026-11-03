---
name: handoff
description: Pregătește un document de predare (handoff) complet, din care o sesiune nouă sau altă persoană poate prelua lucrul fără să mai întrebe nimic. Folosește-l când utilizatorul scrie /handoff sau vrea să închidă sesiunea și să continue în alta.
argument-hint: "[cui se predă / ce urmează, opțional]"
disable-model-invocation: true
allowed-tools: Read, Write, Glob, Grep, Bash(date:*), Bash(mkdir:*), Bash(git status:*), Bash(git log:*), Bash(git diff:*), Bash(git branch:*), Bash(git remote:*)
---

# /handoff — predarea sesiunii

Rolul acestui skill: **să închidă sesiunea curentă cu un document de predare**,
astfel încât cine preia (o sesiune nouă Claude, alt agent sau un om) să poată
continua **fără acces la conversația de acum**.

Diferența față de celelalte:
- `/jurnal` — ce s-a întâmplat (memorie, pentru mai târziu).
- `/compacteaza` — rezumat dens ca să continui *în aceeași direcție, acum*.
- `/handoff` — tot ce trebuie ca *altcineva* să preia: context complet, stare
  verificată, pași clari, capcane.

Dacă `$ARGUMENTS` e dat (ex. „pentru Andrei”, „urmează partea de backend”),
adaptează documentul la destinatar și la următoarea etapă.

## Pași

1. Rulează `date +"%Y-%m-%d-%H%M"`.
2. **Verifică starea reală** înainte să o descrii: `git status`,
   `git branch --show-current`, `git log --oneline -10`, `git diff --stat`.
   Notează modificările necomise și dacă branch-ul e împins.
3. Scrie documentul în `jurnal/handoff/AAAA-LL-ZZ-HHMM-slug.md` după șablonul
   de mai jos.
4. Copiază același conținut în `jurnal/handoff/ULTIMUL.md` (suprascrie), ca
   ultimul handoff să fie mereu în același loc.
5. Răspunde cu:
   - calea fișierului;
   - un rând de pornire pentru sesiunea nouă:
     > `Preia lucrul din @jurnal/handoff/ULTIMUL.md`
   - avertismente dacă există modificări necomise sau neîmpinse.

## Șablon

```markdown
---
tip: handoff
data: AAAA-LL-ZZ HH:MM
proiect: <nume>
branch: <branch curent>
destinatar: <sesiune nouă / persoană>
---

# Handoff: <subiect>

## Pe scurt
3–5 rânduri: ce e proiectul, ce se urmărea în sesiune, unde s-a ajuns.

## Obiectiv și criteriu de „gata”
- Ce trebuie obținut.
- Cum știm că e terminat (test, comportament, aprobare).

## Starea lucrului
| Element | Stare | Note |
|---|---|---|
| ... | gata / în lucru / blocat / neînceput | ... |

## Starea repo-ului
- Branch: `...` — împins: da/nu
- Ultimele commit-uri relevante: `abc1234` mesaj
- Modificări necomise: ... (sau „niciuna”)

## Decizii luate (nu le redeschide fără motiv)
- **Decizie** — motiv; alternativa respinsă.

## Fișiere importante
- `cale/fisier` — ce rol are, ce s-a schimbat.

## Cum rulezi / testezi
```bash
# comenzile exacte care funcționează
```

## Capcane și lucruri non-evidente
- Ce a mușcat deja o dată și cum se ocolește.

## Următorii pași
1. [ ] Primul pas concret (cu fișierul / comanda).
2. [ ] ...

## Întrebări deschise
- Ce trebuie lămurit cu utilizatorul înainte de a merge mai departe.
```

## Reguli

- Scrie pentru cineva care **nu a văzut conversația**: fără „cum am zis mai sus”,
  fără referințe la mesaje anterioare.
- Doar stare verificată. Ce nu e verificat se marchează „neverificat”.
- Căi exacte, comenzi exacte, hash-uri de commit — nu descrieri vagi.
- Fără parole, token-uri sau chei API; scrie doar *unde* se găsesc (ex. „în `.env`, necomis”).
- Lungime: cât e nevoie, dar fiecare secțiune trebuie să fie utilă — șterge secțiunile goale.
