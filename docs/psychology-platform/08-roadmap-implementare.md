# Roadmap: MVP → Versiunea 1 profesională → Versiunea 2 (SaaS)

Notă privind instrumentele: lista din cerința inițială (BDEFS, MCMI-3) e tratată ca referință
pentru V1 profesională. Mențiunile ulterioare „BDRFS/BDEFS” și „LVM” din secțiunea de roadmap a
cerinței originale sunt semnalate ca **NECESITĂ DECIZIE** (vezi README.md, punctul 2) — roadmap-ul
de mai jos folosește BDEFS + MCMI-3 ca instrumente confirmate pentru V1 și tratează un al treilea
instrument ca „opțional, de confirmat”.

## MVP tehnic

**Scop**: validarea end-to-end a arhitecturii (invitație → completare → cotare → raport →
validare) cu un instrument demonstrativ fără conținut protejat, fără date reale de beneficiari.

### User stories (selectiv, reprezentativ)

- Ca administrator de platformă, pot crea un instrument demonstrativ cu 2-3 itemi Likert și o
  regulă de sumă simplă, ca să pot testa întregul flux tehnic.
- Ca psiholog, pot crea o invitație către un client de test, cu link unic și expirare, ca să
  validez fluxul de trimitere.
- Ca beneficiar, pot completa formularul, cu salvare automată a progresului, ca să validez
  reluarea unei sesiuni întrerupte.
- Ca psiholog, primesc un raport PDF „draft” după finalizare, pe care îl pot valida, ca să
  confirm separarea scor/interpretare/validare.
- Ca administrator tehnic, pot restaura un backup criptat într-un mediu de test, ca să validez
  procedura de recuperare.

### Criterii de acceptanță (nivel MVP)

- Fluxul complet (secțiunea „Fluxul unei evaluări” din 02) funcționează fără intervenție manuală
  în bază de date.
- Cotarea rulează exclusiv server-side; verificabil prin inspectarea payload-urilor de rețea
  livrate frontend-ului (fără reguli/formule vizibile).
- Raportul PDF poartă watermark „DRAFT” înainte de validare și dispare după aprobare.
- Jurnalul de audit înregistrează fiecare pas al fluxului, fără conținut clinic.
- Backup criptat + restaurare testată cu succes într-un mediu separat.

### Dependențe / riscuri

- Depinde de finalizarea schemei DB (03) și a modelului de permisiuni (04) înainte de a începe
  codul.
- Risc: subestimarea efortului de „Form Engine” generic (secțiuni condiționale, ramificații) —
  recomand ca MVP să acopere doar Likert + răspuns unic + numeric, lăsând matrice/ramificații
  complexe pentru V1, dacă termenul o cere.

### Definition of Done (MVP)

- Cod cu teste unitare pe `ScoringEngine` (cazuri cunoscute pentru instrumentul demonstrativ).
- Teste de policy/IDOR pe fiecare endpoint nou.
- PHPCS/PHPStan fără erori blocante.
- Instrucțiuni de instalare/activare verificate pe o instalație WordPress curată.

### Strategie rollout/rollback

- Rollout: activare pe un mediu de staging separat, cu date fictive, înainte de orice acces al
  unui psiholog real.
- Rollback: plugin dezactivabil fără pierdere de date (dezactivarea **nu** rulează
  `uninstall.php`); migrațiile de schemă sunt aditive (nu se șterg coloane în MVP) pentru a
  permite revenirea la o versiune anterioară de cod fără migrare de downgrade.

---

## Versiunea 1 profesională

**Scop**: instrumentele reale (BDEFS, MCMI-3, confirmate), management complet de clienți,
baterii, șabloane PDF, dashboard, politici de retenție, export/ștergere GDPR, jurnalizare
completă, documentație operațională.

### User stories (selectiv)

- Ca psiholog, pot crea o baterie cu BDEFS și MCMI-3 în ordine definită, pentru un singur client,
  ca să administrez o evaluare compusă.
- Ca psiholog, pot vedea în dashboard evaluările în așteptare de validare și pot compara evaluări
  succesive ale aceluiași client.
- Ca administrator de platformă, pot defini o politică de retenție per organizație și pot vedea
  joburile programate de anonimizare.
- Ca beneficiar/reprezentant legal, pot solicita exportul datelor mele, iar psihologul/platform
  admin poate genera acest export din platformă.
- Ca psiholog, pot regenera un PDF de raport deja aprobat, cu jurnalizarea regenerării.

### Criterii de acceptanță

- Instrumentele BDEFS și MCMI-3 configurate corect (itemi, cotare, norme) **numai după**
  furnizarea materialelor conform 10-informatii-necesare-inainte-de-primul-test.md, cu teste de
  regresie comparate cu protocoale cotate manual.
- Export GDPR și anonimizare funcționale și testate pe date fictive echivalente structural cu
  date reale.
- Documentație operațională (instalare, backup/restore, gestionare incident) livrată.

### Dependențe / riscuri

- **Blocant real**: fără materialele autorizate (itemi, chei de cotare, etaloane) pentru
  BDEFS/MCMI-3, această versiune nu poate începe implementarea de conținut, doar infrastructura.
- Risc juridic: dacă verificarea licenței (README.md, punctul 4) arată restricții privind
  administrarea electronică, scopul acestei versiuni trebuie renegociat înainte de a scrie cod
  pentru instrumentele reale.
- Risc: volumul de configurare per instrument (norme diferențiate, reguli condiționale) poate fi
  subestimat — recomand ca primul instrument real implementat (BDEFS sau MCMI-3, cel cu structură
  mai simplă) să servească drept „instrument pilot” înainte de al doilea.

### Definition of Done

- Toate criteriile MVP, plus: teste de regresie de cotare validate de un psiholog față de
  protocoale cotate manual; revizuire de securitate dedicată înainte de acces cu date reale de
  beneficiari; DPIA finalizată (organizațional, nu doar tehnic).

### Strategie rollout/rollback

- Rollout progresiv: mai întâi un singur psiholog (tu), cu clienți reali, monitorizare activă
  primele săptămâni.
- Rollback: posibilitatea de a dezactiva un instrument (`status = archived`) fără a afecta
  evaluările istorice deja legate de versiunea lor fixă.

---

## Versiunea 2 (extindere SaaS)

**Scop**: mai mulți psihologi/organizații, supervizare formalizată, branding, credite/abonamente,
facturare, API-uri controlate.

### User stories (selectiv)

- Ca administrator de platformă, pot crea o organizație nouă cu branding propriu, izolată complet
  de datele altor organizații.
- Ca administrator de organizație, pot invita psihologi în organizația mea și le pot atribui
  clienți.
- Ca platform admin, pot suspenda o organizație și îi pot exporta datele înainte de suspendare
  definitivă.

### Criterii de acceptanță

- Izolare verificată prin teste automate: niciun query cross-organizație nu returnează date fără
  autorizare explicită transversală auditată.
- Facturarea (dacă aprobată separat, conform cerinței explicite de a nu o implementa fără
  aprobare) integrată fără a necesita rescrierea schemei de date existente.

### Dependențe / riscuri

- Depinde ca V1 să fi validat deja modelul `organization_id` prezent de la început (03) — motivul
  pentru care a fost inclus încă din schema inițială.
- Risc: complexitatea facturării/creditelor poate introduce presiune să se relaxeze izolarea de
  date pentru „viteză” — de evitat explicit.

### Definition of Done

- Testare de penetrare/audit de securitate dedicat înainte de a accepta prima organizație
  externă reală (nu doar organizația proprie).

### Strategie rollout/rollback

- Rollout: o singură organizație suplimentară de test înainte de deschidere mai largă.
- Rollback: posibilitatea de a readuce sistemul la „o singură organizație implicită” fără pierdere
  de date, dat fiind că izolarea era deja prezentă structural.

---

## Plan etapizat de dezvoltare (ordinea propusă a livrărilor tehnice, conform Etapei 10)

1. Fundație plugin: bootstrap, autoload, migrații schemă inițială, roluri/capabilities, audit
   service.
2. Modulul Instrumente + Form Engine (generic, cu instrument demonstrativ).
3. Modulul Invitații/Tokenuri + interfața respondentului (MVP).
4. Scoring Engine (mecanism generic) + teste unitare cu cazuri cunoscute demonstrative.
5. Report Service + integrare bibliotecă PDF + flux validare psiholog.
6. Dashboard psiholog + panou administrator (MVP minimal).
7. Backup/restore testat, checkpoint de închidere MVP.
8. **Checkpoint decizional**: primirea materialelor autorizate BDEFS/MCMI-3 → configurare
   instrumente reale → teste de regresie de cotare.
9. Managementul clienților complet, baterii, șabloane PDF configurabile, politici de retenție,
   export/ștergere GDPR.
10. Documentație operațională + revizuire de securitate dedicată → V1 profesională.
11. (Ulterior, la decizie explicită) fundația multi-organizație vizibilă în UI → V2.
