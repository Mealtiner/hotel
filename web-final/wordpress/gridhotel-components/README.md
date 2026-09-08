# GRID Hotel Components

Shortcody, ze kterých se skládají sekce webu GRID Hotelu. Obsah, který se mění,
sem nepatří — ten drží GARRY pluginy (kategorie pokojů, jídelníček, sezóna,
turistické cíle) a Nastavení v GRID Core. Components jsou vykreslovací vrstva.

## Skupiny shortcodů

| Soubor | Co obsahuje |
|---|---|
| `inc/shortcodes/global.php` | hlavní menu, patička, kontaktní blok, přepínač jazyků, `[grid_paticka_menu]` |
| `inc/shortcodes/hero-landing.php` | hero, rezervační lišta, `[grid_vyber_pokoje]` |
| `inc/shortcodes/rooms.php` | karty a přehledy pokojů |
| `inc/shortcodes/gastro.php` | gastro provozy |
| `inc/shortcodes/experiences-season.php` | zážitky, poukazy, sezónní karty |
| `inc/shortcodes/business.php` | firemní akce a svatby |
| `inc/shortcodes/forms-contact.php` | kontakt, doprava |
| `inc/shortcodes/pages.php` | ostatní stránkové sekce |

## Historie změn

### 1.13.0 — 2026-09-08
- Nový modul `inc/forms.php` — napojení formulářů Fluent Forms na data webu. Přebral z child motivu plnění pole „Typ pokoje" z kategorií pokojů, lokalizaci kalendáře podle jazyka stránky a značku formuláře poptávky firemních akcí pro vlastní rozbalovací seznam.
- Formuláře jsou součástí sekcí, které vykresluje tenhle plugin, takže jejich chování patří sem; motiv drží jen vzhled.
- Zmizela duplicita: výběr typů pokojů podle jazyka byl napsaný dvakrát (jednou pro `[grid_vyber_pokoje]`, podruhé pro filtr Fluent Forms). Obě cesty teď čtou `gridc_typy_pokoju()`.

### 1.12.0 — 2026-09-08
- Nový `[grid_paticka_menu sloupec="hotel|informace"]` — sloupce odkazů v patičce byly napsané natvrdo v šabloně zápatí a nedaly se editovat. Teď je vypisuje menu WordPressu (Vzhled → Menu, pozice „Patička — …"), pro každý jazyk vlastní. Bez přiřazeného menu shortcode nevrátí nic.

### 1.11.0 — 2026-09-08
- Karty letišť mají odkaz na navigaci do hotelu a na web letiště; badge se vzdáleností je v pravém horním rohu.

### 1.10.0 — 2026-09-07
- Opakující se odkazy v kartách („Detail", „Možnosti", „Partner") dostaly skrytý dovětek s názvem zážitku, aby byly ve výpisu odkazů rozlišitelné (WCAG 2.4.4).
- Neviditelný překryvný odkaz karty je `aria-hidden` a mimo pořadí procházení.

### 1.9.0 — 2026-09-07
- Karta zážitku nevypisuje číslo (4.0, 4.1…) — kartu o řádek prodlužovalo a nic neříkalo. Odlišení nese značka v pravém horním rohu: „Prime" u vlajkové akce, „voucher" u poukazů.
- Vlajková karta dostala obrys trati jako dekoraci (`alt=""`).

### 1.8.0 — 2026-09-07
- Nový `[grid_vyber_pokoje]` — pole „Typ pokoje" v rezervační liště se plní z kategorií pokojů v jazyce stránky. Dřív byly možnosti natvrdo v obsahu a nesouhlasily se skutečností: chyběl Superior s terasou i Apartmá Superior, přebýval neexistující „Superior Plus".

### 1.7.0 — 2026-09-07
- Otevřené mobilní menu je modální dialog: `role="dialog"`, `aria-modal`, past na fokus, fokus po otevření na první prvek a po zavření zpět na hamburger (WCAG 2.4.3, 2.1.2).

### 1.6.0 — 2026-09-07
- Vodoznak nad ukázkovým rezervačním widgetem je `aria-hidden` — je to dekorace.

### 1.5.0 — 2026-09-07
- Rozbalovací tlačítko v hlavním menu má `aria-controls` svázané s podmenu (WCAG 4.1.2).

### 1.4.0 — 2026-09-07
- Přepínač jazyků je `<nav>` místo `<div>` — `aria-label` na bezrolovém divu čtečky ignorují (WCAG 4.1.2).

### 1.3.0 — 2026-09-07
- Kontaktní údaje provozovatele jsou v `<address>`.

### 1.2.0 — 2026-09-07
- Nadpisy karet jsou `<h2>` místo `<h3>` — karty stojí přímo pod nadpisem sekce (WCAG 1.3.1).

### 1.1.0 — 2026-09-07
- Údaj „0 m od trati" opraven na „1 m".
