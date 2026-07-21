# GRID HOTEL — návrh nového webu

Návrh nového webu hotelu **GRID** (4★ hotel a restaurace uprostřed Masarykova okruhu, Brno).
Cílová platforma realizace: **WordPress + Divi 5**. Tyto návrhy jsou klikací prototypy (statické HTML), plně responzivní.

## Jak prohlížet

Otevřete **`index.html`** (rozcestník) — odtud se proklikáte na všech **12 grafických verzí** i podklady.
Na každé verzi je vlevo přilepený **vysouvací přepínač návrhů** (najetím / klepnutím) — rychlé přepínání mezi verzemi a návrat na rozcestník.

## Struktura — 3 bloky (4 + 4 + 4)

```
web-navrh/
├── index.html                  ← ROZCESTNÍK (začněte zde)
├── reserse/ 01..03.md          ← rešerše (.docx → .md)
├── zadani/ zadani-web.md, skills.md
├── assets/ logo/ foto/ nahledy/
│
│  ── BLOK 1 — Motorsport koncepty (+ zlatý akcent) ──
├── verze-1-trackside-calm/      ← V1: prémiové editorial, klid
├── verze-2-pole-position/       ← V2: technický dashboard / telemetrie
├── verze-3-paddock-lifestyle/   ← V3: teplý lifestyle magazín / bento
├── verze-4-circuit-line/        ← V4: immerzivní jízda po trati
│
│  ── BLOK 2 — Divi šablony + kombinace ──
├── verze-5-grid-showroom/       ← V5: dle Divi „Car Dealer“
├── verze-6-grid-residence/      ← V6: dle Divi „Bed & Breakfast“
├── verze-7-grid-journey/        ← V7: dle Divi „Travel Agency“
├── verze-8-grid-fusion/         ← V8: kombinace V5+V6+V7
│
│  ── BLOK 3 — Luxusní hotel (zlatá linie) ──
├── verze-9-grid-noir/           ← V9: tmavá luxusní studie, černá + zlatá
├── verze-10-grid-prestige/      ← V10: světlá luxusní studie, krém + zlatá
├── verze-11-grid-signature/     ← V11 ★ VLAJKOVÁ: tmavý luxus + okruh + KOMPLET
└── verze-12-grid-lumiere/       ← V12 ★ VLAJKOVÁ: světlý luxus + okruh + KOMPLET
```

## Bloky

### Blok 1 — Motorsport koncepty
V1 Trackside Calm · V2 Pole Position · V3 Paddock Lifestyle · V4 Circuit Line.
Nově doplněn **zlatý akcent** (sekundární k brandové červené) — stat čísla, linky, hover detaily, patička.

### Blok 2 — Divi šablony + kombinace
V5 GRID Showroom (Car Dealer) · V6 GRID Residence (Bed & Breakfast) · V7 GRID Journey (Travel Agency) · V8 GRID Fusion (kombinace).

### Blok 3 — Luxusní hotel (černá–červená–bílá + zlatá)
| | Verze | Typ | Poznámka |
|---|---|---|---|
| **V9** | GRID Noir | estetická studie | Tmavá luxusní elegance, zlaté linky, editorial řady pokojů |
| **V10** | GRID Prestige | estetická studie | Světlá galerijní prestiž, zlaté rámy, suite grid |
| **V11 ★** | GRID Signature | **vlajková / komplet** | Tmavý luxus **+ okruh + vše z rešerší** |
| **V12 ★** | GRID Lumière | **vlajková / komplet** | Světlý luxus **+ okruh + vše z rešerší** |

## V11 / V12 — co obsahují navíc (doplněné MUST prvky z rešerší)

Obě vlajkové verze spojují luxusní hotel s motorsportovou polohou a doplňují vše, co v ostatních verzích chybělo:

- **Hero připravené na video** (`<video autoplay muted loop>` s posterem; do dodání filmu se zobrazí foto).
- **Sticky / plovoucí booking lišta + rychlá B2B poptávka.**
- **Segmentace podle motivace** (Závody / Firma / Zážitek / Luxusní pobyt).
- **Produktové karty pokojů** s rozlišením track-view / terasa + rozklikávací detail („front-row stay").
- **Živý kalendář race weekendů / eventů** se stavy dostupnosti (Volné / Poslední pokoje / Čekací list) a balíčky.
- **Zážitky u okruhu, gastronomie jako zážitek, recenze + loga partnerů.**
- **Funkční přepínač CZ / EN / DE** (reálně překládá klíčové texty, ne jen vizuál).
- **Měření GA4 / GTM** — `dataLayer` eventy na rezervaci, B2B poptávku, telefon, e-mail, poukaz, čekací list, změnu jazyka.

## Barvy

Z **logomanuálu GRID 2017**: červená `#C20E1A` · černá `#000` · Cool Gray 5C `#B9B7B9`.
**Zlatá** (`#b08d3f` – `#caa75f`) je přidaná jako sekundární akcent ve **všech 12 verzích**.

## Poznámky

- Všech 12 verzí je **plně responzivních** a stavěných na standardních Divi 5 patternech.
- Vlevo na každé verzi je **vysouvací přepínač návrhů** (jen pro prezentaci, ve finále se odstraní).
- **Stále k doplnění reálnými daty/produkcí:** hero **video** (soubor `assets/video/hero-okruh.mp4`), profesionální **foto pokojů „z pohledu hosta"**, reálné kontakty/ceny/IČO-DIČ a napojení rezervačního systému + GA4 účtu.
- Doporučený hlavní směr pro realizaci: **V11 nebo V12** (komplet), s designovým jazykem dle preference (tmavý vs. světlý luxus).
