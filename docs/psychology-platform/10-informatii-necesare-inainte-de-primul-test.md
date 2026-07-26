# Informații necesare înainte de a introduce BDEFS și MCMI-3

Acest document listează exact ce trebuie să furnizezi, și prin ce fel de canal, înainte ca
implementarea să atingă conținutul real al celor două instrumente. Nimic din lista de mai jos nu
este presupus, dedus sau completat de mine.

## 1. Clarificare denumire/scop — CONFIRMAT

- Instrumentele vizate pentru V1 profesională sunt exclusiv **BDEFS** și **MCMI-3**. „BDRFS” și
  „LVM” nu fac parte din scopul curent.

## 2. Verificarea licenței (NECESITĂ VERIFICAREA LICENȚEI — responsabilitatea ta ca titular al drepturilor de utilizare)

Înainte de a scrie o singură linie de configurare pentru conținutul real, verifică explicit în
contractul de licență:

- este permisă **administrarea electronică/online** a instrumentului (nu doar creion-hârtie sau
  softul oficial al editorului)?
- este permisă **generarea automată de rapoarte** în afara softului oficial al editorului?
- există restricții privind **stocarea digitală** a itemilor/cheilor de cotare, chiar și pe
  server propriu, cu acces restricționat?
- există cerințe specifice privind **afișarea drepturilor de autor/titularului** pe interfață și
  pe raportul PDF?
- există restricții privind **numărul de administrări simultane** sau utilizatori licențiați?

Dacă licența interzice explicit administrarea electronică sau generarea automată de rapoarte,
implementarea acelui instrument trebuie oprită sau renegociată înainte de a continua — acesta e un
blocant contractual, nu tehnic.

## 3. Conținutul propriu-zis (de furnizat printr-un canal securizat, definit separat)

Odată confirmate punctele 1-2, ai nevoie să furnizezi, per instrument:

- **Itemii** — textul exact, în ordinea oficială, cu identificatorul lor stabil (numărul de
  item conform manualului).
- **Variantele de răspuns** pentru fiecare item/grup de itemi (etichete + valori numerice brute
  asociate).
- **Itemii cu cotare inversă**, explicit listați.
- **Regulile pentru răspunsuri lipsă** (câte răspunsuri lipsă invalidează un protocol/o scală,
  cum se tratează scoruri parțiale).
- **Structura de scale/subscale** și formulele de calcul pentru fiecare (sumă simplă, sumă
  ponderată, formulă specifică).
- **Etaloanele (normele)** — tabelele de conversie scor brut → scor standardizat, cu variabilele
  de diferențiere autorizate (ex. vârstă, sex), inclusiv versiunea/anul etalonului.
- **Indicatorii de validitate** ai protocolului, dacă instrumentul îi definește (ex. scale de
  minciună/consistență pentru MCMI-3), și regulile de interpretare a lor.
- **Textele autorizate pentru raport** — formulările descriptive pe care ai dreptul să le
  reproduci în raportul generat, per interval de interpretare.
- **Instrucțiunile oficiale** de administrare a testului, pentru a fi afișate respondentului.
- **Durata estimată de completare**, populația țintă, limba oficială a versiunii folosite.

## 4. Metoda de introducere/stocare securizată (de definit împreună, înainte de pasul 3)

Cerința ta explicită e ca acest canal să fie definit **înainte** de introducerea conținutului.
Propuneri de discutat (nu decise încă):

- import printr-un **panou de administrare autentificat** (nu prin fișiere committed în
  repository Git, care ar expune conținutul licențiat în istoricul de cod, inclusiv dacă
  repository-ul devine privat/public ulterior sau e clonat);
- format de import structurat (ex. o unealtă internă cu formular per item, sau import dintr-un
  fișier structurat încărcat direct în aplicație, validat și apoi stocat criptat în DB, niciodată
  păstrat ca fișier persistent necriptat pe server);
- introducerea normelor printr-un ecran dedicat administratorului de platformă, cu validare a
  intervalelor (fără suprapuneri, fără găuri) înainte de activare.

**Recomandare**: acest conținut nu ajunge niciodată în fișiere `.php`/`.json` din repository — ci
exclusiv în baza de date, prin fluxul de administrare al pluginului, cu acces restricționat
conform modelului de roluri (04).

## 5. Elemente pentru șablonul de raport (de furnizat separat, per instrument sau generic)

- Identitatea cabinetului/psihologului de afișat pe raport (denumire, date de contact).
- Dacă și cum se folosește o parafă/semnătură — **NECESITĂ VERIFICARE JURIDICĂ** (vezi
  README.md, punctul 5) înainte de a decide implementarea tehnică (imagine încorporată vs.
  semnătură electronică calificată).
- Mențiunile legale/de drepturi de autor cerute de licența fiecărui instrument pe raportul final.

## 6. Rezumat — ce aștept de la tine înainte de a începe configurarea BDEFS/MCMI-3

1. Confirmare denumiri exacte + clarificare BDRFS/LVM (punctul 1).
2. Confirmare scrisă (pentru evidența ta, nu neapărat trimisă mie) că licența permite
   administrare electronică + generare automată de rapoarte (punctul 2).
3. Acordul asupra metodei de introducere securizată a conținutului (punctul 4) — o vom stabili
   împreună ca parte din implementarea modulului „Administrarea instrumentelor”, înainte de a
   introduce vreun item real.
4. Abia apoi: itemii, regulile de cotare, etaloanele și textele autorizate (punctul 3), introduse
   exclusiv prin canalul stabilit la punctul 4 — niciodată lipite direct într-un mesaj de chat sau
   într-un fișier de cod.
