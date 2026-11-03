# Ce informații trebuie strânse de la biserică

Pe site, fiecare informație lipsă e marcată `[À COMPLÉTER : ...]` (sau `[À VÉRIFIER : ...]`) și apare **surlignată cu galben**. Mai jos sunt grupate pe teme, cu fișierul în care se completează.
Listă tehnică completă, cu numerele liniilor: `php tools/lister-placeholders.php` (108 mențiuni la livrare, multe fiind aceeași informație în FR și RO).

## 1. Asociația (ASBL)
Fișiere: `content/site.json` → `association`, `content/pages/mentions-legales.*.md`, `content/pages/confidentialite.*.md`

- [ ] Numele exact al ASBL (ca în Moniteur belge)
- [ ] Numărul de întreprindere (BCE)
- [ ] Adresa sediului social
- [ ] Responsabilul de publicare (nume și funcție)

## 2. Contact
Fișier: `content/site.json` → `contact`

- [ ] Adresa bisericii (strada și numărul; codul poștal 1400 Nivelles e deja pus)
- [ ] Număr de GSM + numele persoanei de contact (în FR și RO)
- [ ] Adresa de e-mail publică
- [ ] Coordonatele GPS ale clădirii (latitudine, longitudine) — pentru hartă
- [ ] Opțional: linkuri Facebook / YouTube / Instagram

## 3. Banca (virament + cod QR)
Fișier: `content/site.json` → `don.virement`

- [ ] Titularul contului (beneficiarul)
- [ ] IBAN
- [ ] BIC
- [ ] Comunicarea de pus la virament (FR și RO, ex. „Don travaux”)

## 4. De verificat înainte de publicare
- [ ] **Atestate fiscale**: are asociația dreptul să elibereze atestate fiscale pentru donații? Până la confirmare nu se publică nimic despre asta (`don.fiscalite` → `afficher: false` dacă nu).

## 5. Campania
Fișier: `content/site.json` → `campagne`

- [ ] Obiectivul total în euro
- [ ] Suma deja primită prin virament / numerar (`montant_hors_ligne`)

## 6. Istoria clădirii (pagina „Notre histoire”)
Fișier: `content/site.json` → `histoire`

- [ ] Anul construcției bisericii, cine a construit-o, pentru ce parohie/comunitate, în ce context
- [ ] Perioade sau evenimente importante (extindere, transformare, închidere, schimbare de destinație…) cu anii
- [ ] Anul venirii comunității Philadelphia și condițiile (cumpărare, închiriere, convenție…)
- [ ] Starea actuală a clădirii, problemele constatate
- [ ] Arhitectura: stil, materiale, elemente deosebite (vitralii, orgă, clopotniță…), eventual statut de monument protejat

## 7. Comunitatea (pagina „Notre communauté”)
Fișier: `content/site.json` → `communaute`

- [ ] Limba / limbile slujbelor (traducere în franceză?)
- [ ] Mărturisirea de credință: 3–5 puncte scurte, **validate de conducerea bisericii**
- [ ] Uniunea / federația de care ține biserica (dacă e cazul)
- [ ] Programul: ora slujbei de duminică; alte întâlniri (zi, oră, ce anume)
- [ ] Activități: copii (școala duminicală, vârste, program), tineret, familii (cor, grupe…)

## 8. Proiectul (pagina „Le projet”)
Fișier: `content/site.json` → `projet`

- [ ] Câteva fraze: ce trebuie făcut și de ce
- [ ] Pentru fiecare etapă (acum sunt 3, se pot adăuga/șterge): titlu, descriere, buget în euro, stadiu (urmează / în lucru / finalizat)

## 9. Video
Fișiere: `content/site.json` → `accueil.video_texte`, `content/videos.json`

- [ ] O frază de prezentare a video-ului de pe prima pagină (FR și RO)
- [ ] Linkuri YouTube/Vimeo și titlurile lor (FR și RO)

## 10. Date legale / confidențialitate
Fișiere: `content/pages/mentions-legales.*.md`, `content/pages/confidentialite.*.md`

- [ ] Firma de hosting (nume, adresă, site)
- [ ] Cât timp se păstrează mesajele de contact (ex. 12 luni)
- [ ] Cât timp se păstrează datele donațiilor (ex. 7 ani, documente contabile — de confirmat cu contabilul)
- [ ] Data ultimei actualizări a politicii de confidențialitate

## 11. Fotografii (fără ele apare „Photo à venir”)
Folder: `uploads/images/` — lista exactă a numelor de fișiere în `uploads/images/LISEZ-MOI.txt`

- [ ] Imaginea principală (exterior sau interior luminos al bisericii), peisaj
- [ ] 3 imagini pentru blocurile de pe prima pagină
- [ ] 4 imagini pentru cronologie (inclusiv fotografii vechi, dacă există) + 1 a clădirii
- [ ] 1 imagine a comunității + 3 pentru activități (copii, tineri, familii) — **cu acordul persoanelor fotografiate** (și al părinților pentru copii)
- [ ] Pentru fiecare etapă: poză „înainte” (și „după” când e gata)
- [ ] Albume pentru galerie (`uploads/galerie/`)

## 12. Conturi și setări tehnice (în `philadelphia-private/config.php`, nu pe site)
- [ ] Cont Stripe (sau Mollie) pe numele asociației; cheile test și live; webhook-ul
- [ ] Adresa care primește mesajele de contact (`contact_to`)
- [ ] Cont de e-mail `site@philadelphia-nivelles.be` creat în cPanel (`mail_from`)

## 13. De șters după completare
- [ ] Noutatea exemplu: `content/actualites/2026-11-03-lancement-du-site/`
- [ ] Albumul exemplu: `uploads/galerie/2026-11 Exemple (à supprimer)/`
