# Divi nativní obsah — texty editovatelné ve Visual Builderu (v1.11)

## Co se změnilo

Původní JSONy vkládaly na stránky jen shortcody (`[grid_hero]`, `[grid_rooms]`…) a veškerý
obsah generovalo PHP v child theme. Ve Visual Builderu proto nebylo co editovat a překladové
pluginy neměly texty kde vzít.

**Nově každý JSON obsahuje obsah přímo:**

- každá designová sekce = **jedna Divi sekce** s Text modulem,
- Text modul má `admin_label` s názvem sekce (např. „T3 — Pokoje", „HERO — úvod"),
  takže se ve wireframe/vrstvách Visual Builderu hned vyznáš,
- v Text modulu je HTML sekce **včetně všech textů, odkazů a obrázků** —
  kliknutím do modulu texty přímo upravíš,
- CSS třídy (`sec`, `kicker`, `entries`, `room`…) zůstaly 1:1, design se nemění,
- styl dodává child theme (`style.css`), interaktivitu `assets/grid.js` (váže se na
  stejná ID/třídy — nic se nemění).

## Jak nasadit

1. Nahraj/aktualizuj child theme (`grid-divi5-child.zip`, verze **1.11.0**) — obsahuje
   drobné CSS doplnění pro třídu `grid-html`.
2. V Divi → Divi Library (nebo přímo na stránce přes „Load From Library → Import")
   naimportuj příslušný `page-*.json` a přiřaď layout stránce.
   **Import nahrazuje obsah stránky** — stávající stránky se shortcody přepiš těmito layouty.
3. Hlavička a patička: `header-global.json` / `footer-global.json` do Theme Builderu
   (Global Header / Global Footer) — stejné jako dřív, jen s texty inline.

## Editace textů

- Visual Builder → klikni do sekce → Text modul → uprav text/odkaz/obrázek.
- Struktura HTML (třídy) nech beze změny — nese design. Měň texty, odkazy, obrázky.
- Obrázky: cesty vedou do `wp-content/themes/grid-divi5-child/assets/foto/` —
  můžeš je nahradit obrázky z Knihovny médií (vlož novou URL, nebo obrázek přes editor).

## Překlady

Texty jsou v `post_content` stránek → překladové pluginy (WPML, Polylang, TranslatePress)
je normálně nabídnou k překladu. Hlavička/patička se překládá přes překlad Theme Builder
šablon (WPML/Polylang) nebo přímo v TranslatePress.

## Co zůstává dynamické / mimo Divi

| Prvek | Kde se spravuje |
|---|---|
| `[grid_galerie]` (stránka Galerie) | fotky v GRID Nastavení → Galerie (ACF) — beze změny; časem lze nahradit Divi Gallery modulem |
| Právní stránky cookies / disclaimer / privacy-statement | text generuje Complianz (`[cmplz-document]`) — obal je nyní inline |
| Formuláře (kontakt, dotazník, čekací list…) | zatím statická ukázka („blind form"); ostré napojení = Fluent Forms dle `fluent-forms/FLUENTFORMS-navod.md` |
| Rezervace | mock Bookolo — ostrý systém se vloží při nasazení (viz memory projektu: booking řeší Bookolo) |
| Telemetry widget / track-nav | JS widgety child theme — beze změny |

## Co tím přestalo být potřeba

- ACF pole pro texty sekcí (hero, vstupy, příběh…) už stránky **nečtou** —
  texty se edituují přímo v Divi. ACF zůstává pro galerii, případně GRID Nastavení
  (kontakty v patičce jsou nyní také inline).
- CPT pokoje/zážitky/akce/recenze už homepage **nečte** — karty jsou statické moduly
  v Divi (jednodušší správa, plná kontrola ve VB).

## Jak se JSONy generují (pro vývoj)

Zdroj pravdy je HTML výstup shortcodů. Ve složce `tools/` je
`render.php` (stub WP funkcí → HTML sekcí) + `assemble.py` (HTML → Divi JSON) —
viz `tools/README.md`. Texty lze ale měnit i přímo ve Visual Builderu / v JSONu;
generátor slouží jen pro hromadnou regeneraci.
