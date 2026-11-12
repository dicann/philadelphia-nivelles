# Ce mai lipsește de la biserică

Răspunsurile din chestionarul „Întrebări pentru site-ul Philadelphia Nivelles” sunt deja puse pe site.
Ce a rămas apare pe site **surlignat cu galben** (`[À COMPLÉTER : ...]`). Listă tehnică, cu numerele liniilor: `php tools/lister-placeholders.php`.

## Date administrative
- [ ] Numărul de întreprindere (BCE) al ASBL — `content/site.json` și paginile legale
- [ ] Adresa sediului social al ASBL (dacă e alta decât a bisericii)
- [ ] **Numărul** clădirii pe Parvis Notre-Dame
- [ ] Coordonatele GPS ale bisericii (pentru hartă)
- [ ] Adresa de e-mail (în curs de creare) — pe site și în `config.php` (`contact_to`)
- [ ] Linkurile paginilor Facebook și Instagram (`contact.facebook`, `contact.instagram`)

## Istorie
- [ ] Anul în care biserica a fost cumpărată
- [ ] Arhitectura: stil, materiale, elemente deosebite (vitralii, orgă…)

## Comunitate
- [ ] Mărturisirea de credință: 3–5 puncte scurte, validate de conducere
- [ ] Copii: școala duminicală (vârste, program), dacă există

## Proiect
- [ ] Etapele următoare și bugetul fiecăreia (ați spus că reveniți cu calculele). Bugetele etapelor deja făcute apar ca „À préciser” până le completați.

## Noutăți
- [ ] Concertul de Crăciun (5 și 6 decembrie): ora și condițiile de intrare — `content/actualites/2026-11-12-concert-de-noel/`

## Pagini legale
- [ ] Firma de hosting (nume, adresă, site)
- [ ] Cât timp se păstrează mesajele de contact și datele donațiilor
- [ ] Data actualizării politicii de confidențialitate

## Imagini și video (fără ele apare „Photo à venir”)
Folder: `uploads/images/` — numele exacte sunt în `content/site.json`
- [ ] Imaginea principală (`accueil.jpg`) și cele 3 blocuri de pe prima pagină
- [ ] Istorie: `histoire-1.jpg` … `histoire-6.jpg` (poze vechi, ziua intrării, acum) și `batiment.jpg`
- [ ] Comunitate: `communaute.jpg`, `activite-enfants.jpg`, `activite-jeunes.jpg`, `activite-familles.jpg` — cu acordul celor din poze
- [ ] Proiect, înainte/după: `toiture-avant.jpg` / `toiture-apres.jpg`, `cave-…`, `interieur-…`, `isolation-…`, `electricite-…`
- [ ] Codul QR Payconiq: `payconiq.png`
- [ ] Albume în `uploads/galerie/` (pozele de pe Facebook „Biserica Filadelfia Genappe”)
- [ ] Linkuri video în `content/videos.json` (când există un canal YouTube sau Vimeo)

## Conturi
- [ ] Cont Stripe (sau Mollie) pe numele asociației, cu cheile puse în `config.php`

## De șters
- [ ] Albumul exemplu: `uploads/galerie/2026-11 Exemple (à supprimer)/`
