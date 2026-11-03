---
tags: [jurnal, index]
---

# Jurnal

Folderul acesta poate fi deschis direct ca vault în Obsidian
(*Open folder as vault* → `jurnal/`), sau copiat în vault-ul tău existent.

| Comandă | Ce face | Unde salvează |
|---|---|---|
| `/jurnal [subiect]` | Extrage ce e important din sesiune într-o notă | `sesiuni/AAAA/LL/AAAA-LL-ZZ-slug.md` + `_index.md` lunar |
| `/compacteaza [focus]` | Comprimă o sesiune lungă într-un rezumat dens, ca să continui după `/clear` | `compact/AAAA-LL-ZZ-HHMM-slug.md` |
| `/handoff [destinatar]` | Document complet de predare pentru o sesiune nouă sau altă persoană | `handoff/AAAA-LL-ZZ-HHMM-slug.md` + `handoff/ULTIMUL.md` |

## Flux recomandat

1. Lucrezi normal.
2. Sesiunea devine lungă → `/compacteaza`, apoi `/clear` și
   `Continuăm din @jurnal/compact/<fișier>.md`.
3. La final de sesiune → `/jurnal` (ce ai reținut).
4. Dacă lucrul continuă altundeva → `/handoff`, iar în sesiunea nouă:
   `Preia lucrul din @jurnal/handoff/ULTIMUL.md`.

Skill-urile sunt definite în `.claude/skills/` și pot fi modificate oricând.
