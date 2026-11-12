# Site de strângere de fonduri — Église Philadelphia Nivelles

Site pentru **philadelphia-nivelles.be**: franceză (principal) + română, donații online (Stripe sau Mollie), virament cu cod QR SEPA, galerie automată din folder, video, noutăți, formular de contact.

Ghidul e scris pentru cine administrează site-ul, nu pentru programatori. Toate modificările obișnuite (texte, poze, video, noutăți, obiectiv) se fac **din File Manager-ul cPanel, fără cod**.

---

## Cuprins

1. [Ce primești](#1-ce-primești)
2. [Instalare în cPanel, pas cu pas](#2-instalare-în-cpanel-pas-cu-pas)
3. [Fișierul config.php (cheile secrete)](#3-fișierul-configphp-cheile-secrete)
4. [Stripe: cont, chei, webhook](#4-stripe-cont-chei-webhook)
5. [Trecerea din modul test în modul live](#5-trecerea-din-modul-test-în-modul-live)
6. [Alternativa Mollie](#6-alternativa-mollie)
7. [Textele site-ului (content/site.json)](#7-textele-site-ului-contentsitejson)
8. [Obiectivul campaniei și suma strânsă](#8-obiectivul-campaniei-și-suma-strânsă)
9. [Poze pe pagini](#9-poze-pe-pagini)
10. [Galeria foto](#10-galeria-foto)
11. [Video](#11-video)
12. [Noutăți (Actualités)](#12-noutăți-actualités)
13. [Virament și cod QR](#13-virament-și-cod-qr)
14. [Formularul de contact](#14-formularul-de-contact)
15. [Registrul donațiilor](#15-registrul-donațiilor)
16. [Culori, fonturi, pagini legale](#16-culori-fonturi-pagini-legale)
17. [Probleme frecvente](#17-probleme-frecvente)
18. [Pentru programatori: testare locală](#18-pentru-programatori-testare-locală)
19. [Lucru în paralel: site de test pe subdomeniu](#19-lucru-în-paralel-site-de-test-pe-subdomeniu)
20. [Setări adăugate după prima versiune](#20-setări-adăugate-după-prima-versiune)

---

## 1. Ce primești

```
philadelphia-nivelles/
├── README.md                    ← acest ghid
├── PLACEHOLDERS.md              ← lista informațiilor de cerut de la biserică
├── tools/                       ← utilitare (arhivă ZIP, listă de placeholdere)
└── dist/                        ← ASTA urci pe server
    ├── public_html/             ← conținutul site-ului (merge în public_html)
    │   ├── content/             ← TEXTE: site.json, videos.json, actualites/, pages/
    │   ├── uploads/             ← POZE ȘI VIDEO: images/, galerie/, video/
    │   ├── assets/              ← CSS, JavaScript, fonturi, logo, favicon
    │   ├── app/                 ← codul PHP + librăriile (Stripe, QR) — nu modificați
    │   └── cache/               ← miniaturi generate automat
    └── philadelphia-private/    ← merge ÎN AFARA public_html (chei secrete + date)
        ├── config.example.php
        └── data/                ← donațiile confirmate, copii ale mesajelor
```

**De ce PHP și nu HTML static pur?** Cerințele „adaug poze în folder și apar singure”, „bara de progres se actualizează automat”, „noutăți ca fișiere” și „toate textele într-un singur fișier” au nevoie de un server care citește aceste fișiere. PHP e deja pe hostingul cPanel, nu necesită nicio compilare (fără Node, fără build). CSS-ul și JavaScript-ul sunt fișiere simple, gata de folosit.

**Ce NU folosește site-ul:** niciun cookie de urmărire, nicio statistică Google, nicio cerere către Google Fonts (fonturile sunt pe serverul vostru). De aceea **nu e nevoie de banner de cookies**. Video-urile YouTube/Vimeo și harta se încarcă doar la clic.

---

## 2. Instalare în cPanel, pas cu pas

### 2.1 Versiunea PHP

1. cPanel → **Select PHP Version** (sau **MultiPHP Manager**).
2. Alegeți **PHP 8.1 sau mai nou** (minim 8.0) pentru domeniul philadelphia-nivelles.be.
3. În lista de extensii, verificați că sunt bifate: **curl, gd, mbstring, json** (de obicei sunt implicit). Opțional: **exif** (rotirea corectă a pozelor făcute cu telefonul).

### 2.2 Pregătiți arhiva

Varianta simplă: pe un calculator cu Python, rulați `python3 tools/construire-zip.py` → rezultă `philadelphia-nivelles-site.zip`.

Fără Python: descărcați repo-ul de pe GitHub (butonul *Code → Download ZIP*), dezarhivați, apoi faceți voi un ZIP **cu cele două foldere din `dist/`**: `public_html` și `philadelphia-private`.

### 2.3 Urcarea fișierelor

1. cPanel → **File Manager**. Mergeți în folderul principal al contului (`/home/NUMELE_CONTULUI/`, cel în care vedeți `public_html`).
2. Dacă `public_html` conține deja un `index.html` sau `default.html` de la hosting, **ștergeți-l** (altfel apare pagina implicită a hostingului).
3. **Upload** → alegeți `philadelphia-nivelles-site.zip`.
4. Click dreapta pe zip → **Extract** → în `/home/NUMELE_CONTULUI/`. Rezultat:
   ```
   /home/NUMELE_CONTULUI/public_html/index.php       ← site-ul
   /home/NUMELE_CONTULUI/philadelphia-private/       ← lângă public_html, NU înăuntru
   ```
5. Ștergeți zip-ul.
6. În File Manager, activați **Settings → Show Hidden Files (dotfiles)** și verificați că `public_html/.htaccess` există (e esențial).

> Dacă domeniul e un *addon domain* cu alt folder (ex. `/home/cont/philadelphia-nivelles.be/`), puneți conținutul `public_html` acolo și `philadelphia-private` **lângă** acel folder (`/home/cont/philadelphia-private/`).

### 2.4 Permisiuni

Implicit (foldere 755, fișiere 644) e bine pe aproape toate hostingurile cPanel. Folderele în care site-ul scrie singur sunt `public_html/cache/thumbs/` și `philadelphia-private/data/`. Dacă miniaturile din galerie nu apar, puneți-le pe 755 (click dreapta → *Change Permissions*).

### 2.5 Certificat SSL (https)

1. cPanel → **SSL/TLS Status** → **Run AutoSSL** (gratuit, Let's Encrypt).
2. După ce `https://philadelphia-nivelles.be` funcționează, deschideți `public_html/.htaccess` și **scoateți `#`** din fața celor 3 linii de sub „Forcer HTTPS”.

### 2.6 Verificare

Deschideți `https://philadelphia-nivelles.be` → trebuie să ajungeți pe `/fr/`. Testați comutatorul **FR | RO** din meniu.

---

## 3. Fișierul config.php (cheile secrete)

1. În File Manager, în `philadelphia-private/`, click dreapta pe `config.example.php` → **Copy** → nume nou: `config.php`.
2. Click dreapta pe `config.php` → **Edit**. Completați:

| Setare | Ce puneți |
|---|---|
| `site_url` | `https://philadelphia-nivelles.be` (deja completat). Pentru un test pe o adresă temporară, puneți `''`. |
| `payment_provider` | `'stripe'` (recomandat) sau `'mollie'` |
| `payment_mode` | `'test'` la început, `'live'` când e totul verificat |
| `stripe` → `test` → `secret_key` | cheia `sk_test_...` (vezi secțiunea 4) |
| `stripe` → `test` → `webhook_secret` | `whsec_...` (vezi 4.4) |
| `stripe` → `live` → ... | aceleași, pentru modul live |
| `contact_to` | adresa care primește mesajele formularului |
| `mail_from` | o adresă **a domeniului**, ex. `site@philadelphia-nivelles.be` (creați-o în cPanel → *Email Accounts*) |
| `donation_min` / `donation_max` | suma minimă/maximă acceptată online (implicit 2 € / 10 000 €) |

Atenție la sintaxă: textul rămâne între apostrofuri `'...'`, iar fiecare linie se termină cu virgulă.

Fără `config.php`, site-ul merge, dar formularul de donație afișează „le don en ligne n'est pas encore activé” și propune viramentul.

---

## 4. Stripe: cont, chei, webhook

### 4.1 Contul

1. Creați contul pe [stripe.com](https://stripe.com) pe numele asociației (ASBL), țara **Belgia**.
2. Pentru plățile reale, Stripe cere datele asociației, ale unui reprezentant și IBAN-ul pe care se virează banii.

### 4.2 Metodele de plată

Stripe → **Settings → Payments → Payment methods**: activați **Bancontact**, **Cards** și **SEPA Direct Debit**.

- Donație unică: Bancontact, card, SEPA.
- Donație lunară: card și SEPA (Bancontact nu poate fi folosit pentru prelevări repetate).

Lista e în `config.php` (`methods_once`, `methods_monthly`). Dacă puneți `null`, Stripe afișează automat metodele activate în contul vostru.

### 4.3 Cheile API

Stripe → **Developers → API keys**. În modul **Test** (comutatorul „Test mode”), copiați **Secret key** (`sk_test_...`) în `config.php` la `stripe → test → secret_key`. Cheia publică (`pk_...`) **nu e necesară** (folosim Stripe Checkout, pagina de plată găzduită de Stripe).

### 4.4 Webhook-ul (obligatoriu pentru bara de progres)

Webhook-ul este adresa la care Stripe anunță site-ul că o plată a reușit. **Doar așa** se înregistrează donațiile și se actualizează bara.

1. Stripe → **Developers → Webhooks** → **Add endpoint** (în modul Test).
2. **Endpoint URL**: `https://philadelphia-nivelles.be/webhook-stripe.php`
3. **Events to send** — bifați exact aceste 4:
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
   - `invoice.paid`
4. Salvați, apoi la endpoint → **Signing secret** → **Reveal** → copiați `whsec_...` în `config.php` la `stripe → test → webhook_secret`.

Verificare: deschideți `https://philadelphia-nivelles.be/webhook-stripe.php` în browser → trebuie să scrie „Webhook Stripe actif”.

### 4.5 Test complet în modul test

Cât timp `payment_mode` e `'test'`, sus pe site apare o bandă galbenă „Mode test”.

1. Pe `/fr/faire-un-don` alegeți o sumă → **Continuer vers le paiement sécurisé**.
2. Pe pagina Stripe, plătiți cu:
   - card de test **4242 4242 4242 4242**, orice dată viitoare, orice CVC;
   - **Bancontact**: alegeți Bancontact → pe pagina de test apăsați „Authorize”;
   - **SEPA**: IBAN de test **AT61 1904 3002 3457 3201**.
3. Reveniți pe pagina „Merci”. În câteva secunde, bara de progres crește.
4. În Stripe → Webhooks → endpoint-ul vostru trebuie să vedeți răspunsuri **200**.

Donațiile lunare apar în Stripe → **Subscriptions**. Un donator care vrea să oprească donația lunară vă scrie; o anulați din Subscriptions → *Cancel subscription*.

---

## 5. Trecerea din modul test în modul live

1. Stripe: activați contul (date ale asociației verificate), apoi treceți comutatorul pe **Live**.
2. În modul Live: copiați **Secret key** `sk_live_...` → `config.php` la `stripe → live → secret_key`.
3. În modul Live, creați **din nou** webhook-ul (aceeași adresă, aceleași 4 evenimente) → copiați noul `whsec_...` la `stripe → live → webhook_secret`. *(Secretul de test nu funcționează în live.)*
4. În `config.php`: `'payment_mode' => 'live',`
5. Faceți o donație reală mică (ex. 2 €) ca să verificați tot lanțul, apoi o puteți rambursa din Stripe.
6. Dacă vreți ca donația de test să nu conteze în bară: ștergeți fișierul `philadelphia-private/data/dons.json` (se recreează singur) **înainte** de prima donație reală.

---

## 6. Alternativa Mollie

Mollie (firmă olandeză, foarte folosită în Belgia) e integrat și el. Pentru a-l folosi:

1. Cont pe [mollie.com](https://www.mollie.com), activați Bancontact, carduri și (pentru lunar) SEPA Direct Debit.
2. Mollie → **Developers → API keys** → copiați `test_...` și `live_...` în `config.php` (`mollie → test_key / live_key`).
3. `'payment_provider' => 'mollie',`
4. Nimic de configurat pentru webhook: adresa `webhook-mollie.php` e trimisă automat la fiecare plată.

Donațiile lunare cu Mollie: prima plată creează mandatul, apoi abonamentul lunar se creează automat (vizibil în Mollie → Customers).

---

## 7. Textele site-ului (content/site.json)

**Toate textele principale sunt într-un singur fișier:** `public_html/content/site.json` (titluri, texte de pe fiecare pagină, etapele proiectului, program, contact, IBAN, obiectiv).

Editare: File Manager → click dreapta pe `site.json` → **Edit**.

Fiecare text are două variante:
```json
"hero_titre": { "fr": "Une vieille église, une communauté vivante", "ro": "O biserică veche, o comunitate vie" },
```

Reguli ca să nu stricați fișierul:
- modificați **doar textul dintre ghilimele**;
- nu folosiți ghilimele `"` în interiorul textului (folosiți « » sau „ ”);
- păstrați virgulele dintre elemente; **fără virgulă după ultimul element** dintr-o listă `[ ... ]` sau `{ ... }`;
- numerele (obiectiv, buget) se scriu **fără ghilimele**: `"budget": 45000`.

Dacă greșiți ceva, site-ul nu rămâne alb: afișează un mesaj care spune ce fișier are eroare (și, de cele mai multe ori, linia). Corectați și reîncărcați.

Toate mențiunile `[À COMPLÉTER : ...]` apar **surlignate cu galben** pe site până le înlocuiți. Lista completă: [PLACEHOLDERS.md](PLACEHOLDERS.md).

**Etapele proiectului** (`projet → etapes`): pentru fiecare: `titre`, `description`, `budget` (număr sau `null`), `statut` = `"a_venir"` (urmează), `"en_cours"` (în lucru) sau `"termine"` (finalizat). Pentru o etapă nouă, copiați un bloc `{ ... }` întreg, adăugați virgulă între blocuri și dați-i un `id` nou (ex. `"etape-4"`). **Nu schimbați `id`-ul** unei etape după lansare: el leagă donațiile de etapă.

**Sumele propuse** în formular: `don → montants` (implicit `[25, 50, 100, 250]`).

---

## 8. Obiectivul campaniei și suma strânsă

În `content/site.json`, secțiunea `campagne`:

```json
"campagne": {
  "objectif": 150000,
  "montant_hors_ligne": 3200,
  "montant_force": null,
  "afficher_nombre_dons": true
}
```

| Câmp | Rol |
|---|---|
| `objectif` | obiectivul total, în euro (număr fără ghilimele). Cât e `null`, bara nu apare. |
| `montant_hors_ligne` | bani primiți prin **virament** sau numerar — se **adună** la donațiile online. Actualizați-l când verificați extrasul de cont. |
| `montant_force` | dacă puneți un număr, **înlocuiește** complet calculul automat. Lăsați `null` în rest. |
| `afficher_nombre_dons` | `true`/`false`: afișează „X dons en ligne”. |

Suma afișată = donațiile online confirmate (prin webhook) + `montant_hors_ligne`.

---

## 9. Poze pe pagini

Pozele paginilor stau în `public_html/uploads/images/`, cu **exact** numele din `site.json` (lista e și în `uploads/images/LISEZ-MOI.txt`):

| Fișier | Unde apare |
|---|---|
| `accueil.jpg` | imaginea mare de pe prima pagină (peisaj, min. 1920 px lățime) |
| `accueil.mp4` | opțional: video de fundal pe prima pagină (fără sunet, scurt, < 10 MB) |
| `bloc-histoire.jpg`, `bloc-projet.jpg`, `bloc-aider.jpg` | cele 3 blocuri de pe prima pagină |
| `histoire-1.jpg` … `histoire-4.jpg`, `batiment.jpg` | pagina Notre histoire |
| `communaute.jpg`, `activite-enfants.jpg`, `activite-jeunes.jpg`, `activite-familles.jpg` | pagina Notre communauté |
| `etape-1-avant.jpg`, `etape-1-apres.jpg` (etc.) | pagina Le projet — înainte/după |

- Merge și `.png` sau `.webp` cu același nume.
- Cât timp o poză lipsește, apare un desen neutru „Photo à venir” cu numele fișierului așteptat.
- Pozele mari sunt **micșorate automat** (versiuni de 480, 960 și 1600 px, păstrate în `cache/thumbs/`). Puteți urca direct pozele din telefon.
- Când există ambele poze „avant” și „après” ale unei etape, apare un cursor de comparație.

Ca să schimbați o poză: urcați noul fișier cu **același nume** (suprascrieți). Miniaturile se regenerează singure.

---

## 10. Galeria foto

1. File Manager → `public_html/uploads/galerie/`.
2. **+ Folder** → numele albumului, de preferat cu data în față: `2025-03 Travaux toiture`.
3. Intrați în folder → **Upload** → selectați pozele (.jpg, .png, .webp).
4. Gata: albumul apare pe pagina **Galerie**, cu miniaturi, încărcare progresivă și mărire la clic (navigare cu săgeți, Esc, swipe pe telefon).

Detalii:
- Albumele sunt ordonate de la cel mai nou la cel mai vechi (după numele folderului — de aceea data în față).
- Titlul afișat = numele folderului fără dată. Pentru un titlu diferit în FR și RO, creați în folder un fișier `album.txt`: linia 1 = titlul în franceză, linia 2 = titlul în română.
- Numele fișierului devine legenda (`fatada-nord.jpg` → „Fatada nord”).
- Prima deschidere a unui album nou poate dura câteva secunde (se generează miniaturile); apoi e instantaneu.
- **Ștergeți** folderul `2026-11 Exemple (à supprimer)` când aveți poze reale.

---

## 11. Video

### YouTube / Vimeo

Editați `public_html/content/videos.json`:

```json
{
  "videos": [
    { "titre": { "fr": "Visite de l'église", "ro": "Vizită în biserică" }, "lien": "https://www.youtube.com/watch?v=XXXXXXXXXXX" },
    { "titre": { "fr": "Le chantier", "ro": "Șantierul" }, "lien": "https://vimeo.com/123456789" }
  ]
}
```

- Orice format de link YouTube merge (`youtube.com/watch?v=`, `youtu.be/`, `shorts/`).
- **Primul** video din listă apare și pe prima pagină.
- YouTube e încărcat în modul de confidențialitate (`youtube-nocookie.com`), Vimeo cu „do not track”, și **doar după clic**.

### Fișiere MP4

Urcați fișierul în `public_html/uploads/video/` (ex. `culte-noel.mp4`). Apare automat în galerie. O imagine cu același nume (`culte-noel.jpg`) servește ca previzualizare.
Atenție: hostingurile limitează adesea mărimea upload-ului; pentru video-uri lungi, YouTube e de preferat.

---

## 12. Noutăți (Actualités)

Fiecare noutate = **un folder** în `public_html/content/actualites/`, numit `AAAA-LL-ZZ-titlu-scurt` (litere mici, fără accente, fără spații):

```
content/actualites/2025-04-12-debut-toiture/
├── fr.md        ← textul în franceză
├── ro.md        ← textul în română (opțional; altfel se afișează franceza)
└── photo.jpg    ← imaginea (opțional; prima imagine din folder)
```

Conținutul unui fișier `fr.md`:

```
---
titre: Les travaux de la toiture ont commencé
resume: Une phrase qui apparaît sur la carte de l'actualité.
---
Le texte de la nouvelle. Un retour à la ligne = un retour à la ligne sur le site.

Une ligne vide = un nouveau paragraphe.

## Un intertitre

- une liste
- à puces

**gras**, *italique*, [un lien](https://exemple.be)
```

Pași în File Manager: **+ Folder** (cu numele de mai sus) → în folder **+ File** `fr.md` → Edit → lipiți textul → Save. La fel pentru `ro.md`. Urcați poza.

- Data e luată din numele folderului; adresa paginii din restul numelui (`/fr/actualites/debut-toiture`).
- Pentru a ascunde temporar o noutate: adăugați linia `publie: non` în antet (între `---`).
- Exemple reale: `2026-11-12-concert-de-noel` și `2026-11-10-ou-en-sont-les-travaux`.

---

## 13. Virament și cod QR

În `site.json` → `don → virement`: `beneficiaire`, `iban`, `bic`, `communication`.

Imediat ce **IBAN-ul este valid** și beneficiarul completat, pe pagina „Faire un don” apare un **cod QR SEPA (EPC)**: donatorul îl scanează cu aplicația băncii (funcția „scanner pour payer” din aplicațiile belgiene), iar beneficiarul, IBAN-ul și comunicarea se completează singure. Donatorul poate alege și o sumă care se include în cod. Butoanele „Copier” copiază fiecare informație.

**Mențiunea fiscală** (`don → fiscalite`): este un placeholder. **Nu publicați nimic** despre deducerea fiscală până nu confirmați că asociația are dreptul să elibereze atestate fiscale. Dacă nu, puneți `"afficher": false`.

---

## 14. Formularul de contact

- Trimite mesajul la `contact_to` (din `config.php`), cu „Reply-To” = adresa vizitatorului: apăsați **Reply** și răspundeți direct.
- `mail_from` trebuie să fie o adresă reală a domeniului, altfel mesajele ajung în spam. În cPanel → **Email Deliverability**, verificați că SPF și DKIM sunt „Valid”.
- Anti-spam fără captcha: câmp capcană invizibil, timp minim de completare, limită de 5 mesaje/oră/conexiune.
- Copie de siguranță: fiecare mesaj e salvat și în `philadelphia-private/data/messages-contact.log` (dezactivabil cu `contact_keep_copy => false`). Ștergeți periodic mesajele vechi (vezi politica de confidențialitate).
- Link direct cu subiect preselectat: `/fr/contact?sujet=benevolat` (`general`, `don`, `benevolat`, `materiaux`, `entreprise`, `visite`).

---

**Dacă mesajele nu pleacă** („Le message n'a pas pu être envoyé”): multe hostinguri blochează funcția `mail()` din PHP. Completează în `config.php` secțiunea `'smtp'` cu contul info@ (server, port și parolă: cPanel → Email Accounts → info@ → **Connect Devices**). Cu `'debug' => true`, sub mesajul de eroare apare motivul exact.

## 15. Registrul donațiilor

`philadelphia-private/data/dons.json` conține donațiile confirmate de webhook: id, sumă, unică/lunară, etapă, nume și e-mail (doar dacă nu e anonimă), dată, statut (`confirme`, `en_attente` pentru SEPA în curs, `echoue`). Doar cele `confirme` intră în bară.

- E în afara `public_html`: nu poate fi citit de pe internet.
- Fiecare plată e înregistrată o singură dată, chiar dacă Stripe trimite notificarea de mai multe ori.
- Contabilitatea oficială rămâne cea din Stripe/Mollie (exporturi CSV din tabloul lor de bord).

---

## 16. Culori, fonturi, pagini legale

- **Culori**: la începutul fișierului `public_html/assets/css/style.css`, blocul `:root`. Verdele principal `#23514A`, cremul `#F3EAD8` și auriul `#E2A764` sunt extrase exact din logo. Schimbați o valoare → se schimbă peste tot.
- **Fonturi** (Google Fonts, găzduite local, licență OFL): Cormorant Garamond (titluri), Marcellus (etichete), Source Sans 3 (text).
- **Logo**: `assets/img/logo.png`; emblema vectorială e desenată în `app/templates/partials/sprite.php`. Favicon-urile sunt în `assets/img/` și `favicon.ico`.
- **Mențiuni legale și confidențialitate**: `content/pages/mentions-legales.fr.md` / `.ro.md` și `confidentialite.fr.md` / `.ro.md` (același format ca noutățile).
- **SEO**: titluri și descrieri pe fiecare pagină, Open Graph (imagine `assets/img/og-image.jpg`), `hreflang` FR/RO, `/sitemap.xml` și `/robots.txt` generate automat (includ noutățile). Opțional: declarați `https://philadelphia-nivelles.be/sitemap.xml` în Google Search Console.

---

## 17. Probleme frecvente

| Simptom | Soluție |
|---|---|
| Pagina implicită a hostingului în loc de site | Ștergeți `index.html` / `default.html` din `public_html`. |
| Eroare 404 pe toate paginile în afară de prima | Lipsește `.htaccess` (activați „Show Hidden Files” și verificați) sau `mod_rewrite` nu e activ (cereți-l hostingului). |
| „Erreur dans le fichier content/site.json” | Ghilimele sau virgule greșite: vezi secțiunea 7. |
| Pagina e albă / eroare 500 | Puneți temporar `'debug' => true` în `config.php` ca să vedeți eroarea; reveniți la `false` după. Verificați versiunea PHP (≥ 8.0). |
| Formularul de donație spune „pas encore activé” | `config.php` lipsește, e în locul greșit (trebuie lângă `public_html`) sau cheia conține încă `REMPLACER`. |
| Plata merge, dar bara nu crește | Webhook-ul: adresă greșită, evenimente lipsă sau `webhook_secret` greșit (atenție: test ≠ live). În Stripe → Webhooks vedeți eroarea exactă. |
| Miniaturile din galerie nu apar | Permisiuni pe `public_html/cache/thumbs/` (755) și extensia PHP **gd** activă. |
| Mesajele de contact nu ajung | Verificați spam-ul, `mail_from` (adresă a domeniului), SPF/DKIM în *Email Deliverability*. Mesajele sunt oricum în `messages-contact.log`. |
| Codul QR nu apare | IBAN-ul din `site.json` nu e valid sau beneficiarul conține încă `[À COMPLÉTER`. |

---

## 18. Pentru programatori: testare locală

Fără build. Cu PHP 8.x local:

```bash
cp dist/philadelphia-private/config.example.php dist/philadelphia-private/config.php
```

```bash
php -S 127.0.0.1:8080 -t dist/public_html dist/public_html/index.php
```

Apoi `http://127.0.0.1:8080`. În `config.php` puneți `'site_url' => ''` pentru test local.

Ce a fost testat înainte de livrare (PHP 8.3, Apache 2.4 cu `.htaccess`, Chromium):
- toate paginile FR și RO (200), 404, redirecționări, `sitemap.xml`, `robots.txt`; folderele `app/`, `content/` (json/md), `philadelphia-private` și scripturile PHP din `uploads/` sunt blocate (403);
- comutatorul FR | RO păstrează pagina (inclusiv pe noutăți); meniul mobil (Esc, aria-expanded);
- galeria din folder: miniaturi generate și puse în cache, lightbox (săgeți, Esc, focus);
- formularul de donație: validare, sume predefinite/liberă, unic/lunar, anonim, etapă; sesiunea Stripe Checkout creată corect (contra unui server Stripe simulat local, fără chei reale);
- webhook-ul Stripe cu semnătură HMAC: donație unică, SEPA întârziată, mensualitate (`invoice.paid`), dubluri ignorate, semnătură falsă respinsă (400); bara de progres actualizată;
- fluxul Mollie (unic + lunar cu crearea abonamentului) contra unui API simulat;
- codul QR EPC, decodat și verificat;
- formularul de contact: validare FR/RO, capcană anti-robot, e-mail trimis cu Reply-To;
- accesibilitate: axe-core (WCAG 2 A/AA) fără erori pe toate paginile; fără depășire orizontală pe mobil (390 px).

**Ce trebuie încă testat de voi**, cu cheile voastre: o plată în modul test pe serverul real (secțiunea 4.5) și primirea unui e-mail de contact de pe serverul real.

Utilitare:
- `php tools/lister-placeholders.php` — listează toate `[À COMPLÉTER]` rămase;
- `python3 tools/construire-zip.py` — creează arhiva de urcat (fără `config.php`, date sau cache).

Actualizarea librăriilor PHP (opțional): în `dist/public_html/app/`, `composer update --no-dev` (platforma e fixată pe PHP 8.0 pentru compatibilitate).

---

## 19. Lucru în paralel: site de test pe subdomeniu

Pagina „work in progress” rămâne pe `philadelphia-nivelles.be`, iar site-ul nou se construiește pe un subdomeniu de test (ex. **work.philadelphia-nivelles.be**), protejat cu parolă.

1. cPanel → **Domains** (sau **Subdomains**) → **Create a new domain**: `nou.philadelphia-nivelles.be`. Debifați „Share document root” și puneți ca folder `/home/CONT/nou.philadelphia-nivelles.be` (în afara `public_html`).
2. Urcați zip-ul în `/home/CONT/` și dezarhivați. Mutați **conținutul** folderului `public_html` din zip în `nou.philadelphia-nivelles.be/` (nu în `public_html`, unde e pagina WIP). `philadelphia-private/` rămâne în `/home/CONT/`.
3. În `config.php`: `'site_url' => 'https://nou.philadelphia-nivelles.be',` și `'payment_mode' => 'test',`.
4. **SSL/TLS Status** → Run AutoSSL (include și subdomeniul).
5. Parolă: cPanel → **Directory Privacy** → folderul `nou.philadelphia-nivelles.be` → bifați „Password protect” → creați un utilizator. Webhook-urile Stripe/Mollie rămân accesibile (regula e deja în `.htaccess`).
6. Webhook Stripe de test: `https://nou.philadelphia-nivelles.be/webhook-stripe.php`.

**La lansare:**
1. Faceți o copie a paginii WIP (File Manager → selectați conținutul `public_html` → *Compress*).
2. Goliți `public_html` și copiați acolo tot conținutul din `nou.philadelphia-nivelles.be/` (inclusiv `.htaccess`).
3. Scoateți parola: Directory Privacy pe `public_html` trebuie să fie dezactivat (verificați că `.htaccess` nu conține linii `AuthType`/`require valid-user`).
4. `config.php`: `'site_url' => 'https://philadelphia-nivelles.be',`, cheile live, `'payment_mode' => 'live',`.
5. În Stripe (Live), webhook-ul cu adresa `https://philadelphia-nivelles.be/webhook-stripe.php`.
6. Ștergeți `philadelphia-private/data/dons.json` dacă nu vreți ca donațiile de test să apară în bară.
7. Opțional: redirecționați `nou.` spre domeniul principal (cPanel → Redirects) sau ștergeți subdomeniul.

---

## 20. Setări adăugate după prima versiune

Toate se fac în `public_html/content/site.json` (sau în `content/videos.json` pentru video):

| Setare | Ce face |
|---|---|
| `"menu": [...]` | paginile din meniul de sus, în ordine (`accueil`, `histoire`, `communaute`, `projet`, `galerie`, `actualites`, `contact`) |
| `"actualites_actives": false` | ascunde complet noutățile (prima pagină + pagina Actualités). Pune `true` ca să le arăți (și adaugă `"actualites"` în `menu`). |
| `contact.lieu_cultes` | adresa slujbelor pe durata lucrărilor (Genappe). `"afficher": false` când slujbele revin în biserică. |
| `contact.telephones` | lista numerelor afișate (nume + număr) |
| `contact.carte.google_recherche` / `google_lien` | harta Google de la „Plan d'accès” (se încarcă doar la clic) și linkul „Ouvrir dans Google Maps” |
| `campagne.libelle_montant` | textul de lângă suma din bara de progres (acum „déjà investis”) |
| `projet.etapes[].curseur` | `false` = pozele avant/après stau una lângă alta (când nu sunt făcute din același unghi); altfel apare cursorul de comparație |
| `projet.plan_image` | planul arhitectului afișat pe pagina Proiect |
| `histoire.chronologie[].legende` | legenda și sursa sub poza unei etape din istorie |
| `don.payconiq` | blocul Payconiq; urcă codul QR ca `uploads/images/payconiq.png` |

**Video din fișiere** (`content/videos.json`): pe lângă linkuri YouTube/Vimeo (`"lien"`), poți lista fișiere MP4 din `uploads/video/` cu `"fichier"`, titlu FR/RO și `"vertical": true` pentru filmări verticale de telefon. O imagine cu același nume (`clip.jpg` pentru `clip.mp4`) servește ca previzualizare. Comprimați video-urile înainte (ideal sub 10 MB).

**Previzualizare la distribuire (WhatsApp, Facebook)**: imaginile `assets/img/og-image-fr.jpg` și `og-image-ro.jpg` (1200×630). Funcționează doar pe site-ul public (fără parolă) și cu `site_url` corect în `config.php`.

**Actualizarea site-ului pe server**: încarci `1-site.zip` și îl dezarhivezi peste cel existent. Fișierele șterse sau redenumite în versiunea nouă **rămân pe server** până le ștergi manual din File Manager.
