# GRID Hotel → WordPress + Divi 5 + ACF — průvodce převodem

Kompletní balík pro převod návrhu **Circuit Line (V4)** do WordPressu.

```
wordpress/
├── grid-divi5-child/          ← child theme (nahrát do WP)
│   ├── style.css              designový systém (barvy, fonty, karbon, komponenty)
│   ├── functions.php          enqueue, fonty, ACF options, šířka webu
│   ├── inc/shortcodes.php     [grid_*] sekce, obsah z ACF (s fallbacky)
│   ├── acf-json/              2 skupiny polí (auto-load i importovatelné)
│   └── assets/                grid.js (widget, čekací list, reveal) + foto + loga
├── divi-json/                 9 layout JSONů (stránky + hlavička + patička)
└── README-PREVOD.md           tento soubor
```

Filozofie: **vizuál řídí Divi, obsah drží ACF, styl a interaktivní prvky child theme.**
Každá sekce návrhu = jeden shortcode (`[grid_hero]`, `[grid_rooms]`, `[grid_season]`…), který
vykreslí přesně původní HTML a texty tahá z ACF. Web funguje i **bez vyplněného ACF** (fallbacky).

---

## ČÁST A — Co nastavit PŘED nahráním kódu

### A1. WordPress (Nastavení →)
| Kde | Hodnota |
|---|---|
| Obecné → Jazyk webu | Čeština |
| Obecné → Časové pásmo | Praha |
| Obecné → Formát data / času | dle CZ |
| Čtení → Vaše homepage zobrazuje | **Statická stránka** → „Domů" (až vytvoříš) |
| Čtení → Viditelnost pro vyhledávače | na staging **zaškrtnout „odrazovat"**, na produkci vypnout |
| Trvalé odkazy (permalinky) | **Název příspěvku** (`/%postname%/`) |
| Diskuse | vypnout komentáře (hotelový web je nepotřebuje) |
| Média | vypnout „organizovat do složek podle měsíce" je volitelné |

### A2. Šablona (téma)
1. Nahraj a aktivuj **Divi** (rodičovské téma) — nutné mít licenci Elegant Themes.
2. Teprve pak aktivuj **grid-divi5-child** (child).
3. Smaž výchozí témata (Twenty*) až na jedno záložní.
4. Divi → Theme Options → API: vlož **Elegant Themes Username + API Key** (kvůli aktualizacím).

### A3. Divi (Divi → Theme Options / Builder)
| Nastavení | Hodnota | Proč |
|---|---|---|
| **Website Content Width** | `1200px` (doporučeno) / `1280px` | sladění s návrhem (viz ČÁST E) |
| Website Gutter Width | `3` | mezery mezi sloupci |
| Section Height / padding | ponech default | naše sekce si řídí padding samy |
| Divi → Theme Options → General → **Google Fonts** | **Disable Google Fonts = ON** | fonty načítá child theme (nezdvojovat) |
| Divi → Theme Options → Performance | zapnout **Dynamic CSS, Dynamic JS, Critical CSS** | rychlost |
| Divi → Theme Options → Builder → Advanced → **Static CSS File Generation** | ON | výkon |
| Role Editor | omezit klientovi „Divi Library / Theme Builder" | ať nerozbije layout |

> **Důležité:** V Divi → Theme Options → General vypni „Use Divi Gallery" jen dle potřeby. Fonty **nech na child theme** (bod výše), jinak se Saira/Inter/Space Mono načtou dvakrát.

### A4. Pluginy (nainstalovat před obsahem)
Minimální rozumný stack:
| Oblast | Plugin |
|---|---|
| Custom fields | **ACF** (stačí free; pro CPT landing pages později **ACF Pro** kvůli repeaterům) |
| Vícejazyčnost | Polylang (free) |
| SEO | Yoast SEO (free) — dobře ladí s Polylang |
| Formuláře | Fluent Forms *nebo* Divi Engine Form Builder |
| SMTP | WP Mail SMTP / Post SMTP (nutné pro odesílání formulářů) |
| Cache | WP Rocket / LiteSpeed Cache (dle hostingu) |
| Obrázky | ShortPixel / Imagify + WebP |
| Cookies | Complianz / CookieYes |
| Bezpečnost | Wordfence / Solid Security |
| Analytika | GTM + GA4 |

---

## ČÁST B — Instalace child theme
1. Zazipuj složku `grid-divi5-child` (viz `grid-divi5-child.zip`, pokud je přiložen).
2. WP → Vzhled → Motivy → Přidat → Nahrát motiv → vyber ZIP → Instalovat → **Aktivovat**.
3. Ověř, že běží Divi jako rodič (child má v hlavičce `Template: Divi`).

---

## ČÁST C — ACF obsah

> ⚠️ **ACF Free vs PRO.** Skupiny polí používají **repeatery** a **Options page** — obojí je
> **jen v ACF PRO**. Na ACF Free se „GRID Nastavení" nevytvoří a repeatery nefungují.
> **Web ale běží i tak** — všechny sekce mají v `shortcodes.php` plné fallbacky (reálné texty
> z gridhotel.cz). ACF tedy **teď není potřeba**; slouží až k pohodlné editaci.

- **Teď (ACF Free):** v ACF → Skupiny polí **nesynchronizuj** (nabídku „Synchronizace (2)" ignoruj) — na Free by pole stejně nefungovala. Obsah řídí fallbacky.
- **Později (ACF PRO):** po instalaci PRO se pole z `acf-json/` načtou automaticky → v menu **„GRID Nastavení"** vyplníš kontakt, hero, widget, menu a repeatery (pokoje, akce…). Shortcody je začnou přebírat.

---

## ČÁST D — Divi import (stránky + hlavička + patička)

### D1. Stránky
Pro každou stránku (`divi-json/page-*.json`):
1. WP → Stránky → **Přidat** (název: Domů / Pokoje / …), Šablona stránky = **Blank / Fullwidth**.
2. Otevři **Divi Builder** → ikona přenositelnosti (⇅ nahoře) → **Import** → vyber příslušný `page-*.json` → Import.
3. Publikuj. (Homepage nastav v Nastavení → Čtení.)

### D2. Hlavička a patička (globální) — Divi Theme Builder
1. Divi → **Theme Builder** → Add Global Header → Build Global Header.
2. V editoru headeru: přenositelnost → **Import** → `divi-json/header-global.json`. Ulož.
3. Totéž pro patičku: Add Global Footer → Import `footer-global.json`.
4. Save Changes v Theme Builderu.

### D3. Jak jsou layouty postavené
Každá sekce = **Text modul** v plnošířkové řadě (`.grid-fullrow`) s naším shortcodem
(`[grid_hero]` …). Text modul shortcody spouští spolehlivě; CSS v child theme zajistí, že
řada spanuje 100 % šířky. Kdyby se přesto někde ukázal doslovný text `[grid_hero]`, smaž
modul a vlož nový **Text modul** s tím shortcodem. Seznam shortcodů podle sekcí:

| Sekce | Shortcode |
|---|---|
| Živý widget (čas+teplota) | `[grid_telemetry]` |
| Pravá „trať" (jen homepage) | `[grid_tracknav]` |
| Hero | `[grid_hero]` |
| Rezervační lišta | `[grid_booking]` |
| T1 Vstupy | `[grid_vstupy]` |
| T2 Příběh | `[grid_pribeh]` |
| T3 Pokoje | `[grid_rooms]` |
| T4 Zážitky | `[grid_zazitky]` |
| T5 Gastronomie | `[grid_gastro]` |
| T6 Sezóna 2026 / čekací list | `[grid_season]` |
| T7 Firemní akce & svatby | `[grid_firemni]` |
| T8 Reference | `[grid_reference]` |
| CÍL / finální CTA | `[grid_final]` |
| Hlavička | `[grid_header]` |
| Patička | `[grid_footer]` |

> Tip: Nejrychlejší cesta bez importu = založit stránku, vložit jeden **Code modul** a nasypat
> do něj shortcody sekcí za sebou. Funguje 100 %.

---

## ČÁST E — Šířka webu
Návrh je stavěný na max. obsahové šířce **1320 px**. Doporučení: **1200 px** (moderní standard, drží mřížky).
Nastav na dvou místech, ať sedí:
- **Divi → Theme Options → Website Content Width = 1200px**
- **GRID Nastavení → Šířka webu = 1200** (řídí `--maxw` našich sekcí)

Child theme má default `--maxw:1280px` (kompromis). Změna = jedna hodnota v ACF.

---

## ČÁST F — Architektura: teď vs. produkce (doporučení)

Tento balík je **rychlá, funkční varianta**: obsah v ACF Options (repeatery), render přes shortcody v child theme.
Ideální pro spuštění draftu a jednu one-page + pár podstránek.

**Pro plnou produkci** (SEO landing pages `/pokoje/superior/`, `/race-weekendy/motogp-2026/`, 3 jazyky, kampaně)
doporučuji obsahovou logiku přesunout z child theme do samostatného pluginu **`gridhotel-core`** s **custom post types**:
`room`, `experience`, `race_event`, `package`, `testimonial`, `partner` + ACF Pro (repeater/relationship/options)
+ booking wrapper na externí rezervační engine (Previo / Bookolo / SiteMinder…), ne rezervace přímo ve WP.

Výhoda: data přežijí změnu šablony, dají se řadit/filtrovat, mají vlastní URL a schema.org.
Migrace je přímočará — shortcody zůstanou stejné, jen budou tahat z CPT místo z Options.

> Poznámka k paletě: drž se **GRID palety** (červená #C20E1A / zlatá #CAA75F / grafit), ne generické
> limetkové palety z obecných doporučení. Obsah (pokoje, akce, kontakt) je převzatý z gridhotel.cz.
