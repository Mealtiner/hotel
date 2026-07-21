# GRID HOTEL — Základní skills / design systém

Závazná pravidla pro tvorbu webu (a pro 4 grafické verze). Vychází z rešerší a **logomanuálu GRID Hotel 2017**.

---

## 1. Barevnost — z logomanuálu 2017 (jádro značky)

| Barva | Pantone | RGB | HEX | Použití |
|---|---|---|---|---|
| **GRID červená** | 485 C | 194 / 14 / 26 | `#C20E1A` | Akcent, CTA, logo „G“, klíčové detaily. Střídmě. |
| **Černá** | Black C | 0 / 0 / 0 | `#000000` | Text, tmavá pozadí, logo. |
| **Cool Gray 5 C** | Cool Gray 5 C | 185 / 183 / 185 | `#B9B7B9` | Sekundární text, linky, jemné plochy. |

### Rozšířená webová paleta (odvozená z manuálu — „Trackside Calm“)

Neutrály odvozené z asfaltu / světel / hotelového interiéru. Jádro značky zůstává výše.

| Token | HEX | Role |
|---|---|---|
| `--graphite` | `#16181B` | Hlavní tmavé pozadí |
| `--asphalt` | `#23262B` | Plochy, karty na tmavém |
| `--steel` | `#3A3E45` | Linky, okraje |
| `--offwhite` | `#F4F2ED` | Světlé pozadí |
| `--champagne` | `#C9B89C` | Teplý akcent (lifestyle verze) |
| `--grid-red` | `#C20E1A` | Akcent / CTA |
| `--grid-red-700` | `#9A0B14` | Hover stav červené |

> **Pravidlo:** červená je akcent, ne plocha. Žádné plameny, karbon, falešná závodní estetika.

## 2. Typografie

- **Wordmark** v logu = technický wide sans (Square 721 / Eurostile). Neopisovat fontem — používat dodané logo.
- **Display / titulky (web):** kondenzovaný technický sans — `Saira Condensed` / `Archivo` (alt. Oswald). Působí motorsportově, ale čitelně.
- **Datové popisky, časy, čísla:** monospace — `Space Mono` / `JetBrains Mono` (lap-time pásky, pit-board).
- **Body text:** čistý sans — `Inter` / `Manrope`.

Velká, sebevědomá typografie. Versálky a tracking u krátkých labelů.

## 3. Motorsport motivy — jemně, jako systém

✅ Mapa trati, telemetry linky, lap-time pásky, pit-board karty, startovní světla, čísla, šrafování asfaltu.
❌ Šachovnice přes celou plochu, plameny, karbon, stock auta v kouři, závodní slang všude.

Šachovnicová vlajka je v logu „G“ — to stačí. Na webu pracovat s **reálnou fotografií okruhu**.

## 4. Tone of voice

Stručný, sebevědomý, obrazový. CZ primárně, EN/DE pro mezinárodní fanoušky.

- „Přijeď. Ubytuj se. Sleduj trať z první řady.“
- „Spíš uprostřed Masarykova okruhu.“
- „Tvůj víkend začíná na startovním roštu.“

## 5. Práce s logem (100 % dle manuálu)

- Světlé pozadí → `logo/grid-hotel-horizontal.png`.
- Tmavé pozadí → `logo/grid-hotel-negativ.png` (bílý text + červené G).
- Akcent / favicon / watermark → `logo/g-mark.png` (barevné), `g-mark-white.png` (na tmavém).
- Restaurace → `logo/grid-restaurant-*.png`.
- Dodržet **ochrannou zónu** (B = 2× výška „A“ z manuálu). Logo nedeformovat, nepřebarvovat mimo schválené verze.

## 6. Klíčové UI komponenty (design systém pro Divi 5)

- **Sticky booking lišta / panel** (datum příjezdu, odjezdu, hosté, CTA Rezervovat).
- **Vstupy podle motivace** (4 karty: Závody / Firma / Zážitek / Pobyt).
- **Karty pokojů** (foto, výhled, terasa, kapacita, od ceny, CTA).
- **Karty zážitků / balíčků** (Track day, Firemní den, Dárkový poukaz, Race weekend).
- **Editorial blok** (příběh místa, mapa, kalendář).
- **Food & bar sekce** (terasa, večerní atmosféra).
- **Sociální důkaz** (recenze, loga partnerů).
- **Patička jako servisní centrum** (viz [zadani-web.md](zadani-web.md), sekce 4).

## 7. Cesta klienta (závazná dramaturgie stránky)

`Emoce (hero)` → `Orientace (vstupy podle motivace)` → `Hodnota (track view, foto, příběh)` → `Důvěra (recenze, partneři, kalendář)` → `Konverze (booking / poptávka)` → `Servis (patička)`.

Web musí prodávat **celý zážitek**, ne jen pokoj.

---

## 8. Čtyři koncepty — každý jiný STRUKTUROU a KOMPOZICÍ

Požadavek: 4 návrhy se musí lišit nejen barvou a fontem, ale celou stavbou a kompozicí webu.

### Verze 1 — „Trackside Calm“ (prémiové editorial)
- **Pocit:** Aman/Escapade. Klid, prostor, kino.
- **Struktura:** centrované horní menu, **fullscreen hero**, vertikální editorial scroll, střídání full-bleed foto a textových bloků na střed, hodně bílého prostoru.
- **Paleta:** graphite + off-white + červený akcent.
- **Kompozice:** symetrická, klidná, jeden sloupec, velká typografie.

### Verze 2 — „Pole Position / Telemetry“ (technický dashboard)
- **Pocit:** motorsport HUD, paddock, data.
- **Struktura:** **fixní levý sidebar s navigací** + číslované sekce, asymetrický grid, monospace datové popisky, lap-time pásky, startovní světla, linky mřížky.
- **Paleta:** off-white/graphite + výrazná červená + safety detaily.
- **Kompozice:** asymetrická, mřížková, „přístrojová deska“.

### Verze 3 — „Paddock Lifestyle“ (teplý magazín / bento)
- **Pocit:** The Hoxton / 25hours. Lidé, jídlo, bar, komunita.
- **Struktura:** **bento / masonry grid**, zaoblené karty, horizontálně scrollovatelné zážitky, editorial dlaždice, teplá paleta.
- **Paleta:** off-white + champagne + warm gray, červená jako tečka.
- **Kompozice:** mozaiková, dlaždicová, neformální.

### Verze 4 — „Circuit Line“ (immerzivní jízda po trati)
- **Pocit:** projedeš trať shora dolů, každá sekce = zatáčka.
- **Struktura:** **trať jako páteř stránky** — svislý indikátor postupu (track progress) po straně, **split-screen** sekce („zatáčky“), full-bleed kontrast, jiná navigační logika (zastávky na okruhu).
- **Paleta:** tmavá graphite + červená, dramatický kontrast.
- **Kompozice:** split / diagonální, řízená čarou trati.

> Všechny 4 sdílejí stejný obsah a značku (loga + barvy z manuálu), ale jiný layout, navigaci, rytmus a kompozici.
