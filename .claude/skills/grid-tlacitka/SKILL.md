---
name: grid-tlacitka
description: Jednotný systém tlačítek webu GRID Hotel — varianty, kontext povrchu, mapování historických tříd a soužití s nativním Divi 5 modulem Tlačítko. Načti při přidávání nebo úpravě jakéhokoli tlačítka, odkazu s vzhledem tlačítka, filtru nebo odesílacího prvku formuláře, i když jde jen o jedno místo.
---

# GRID Hotel — tlačítka

Závazná specifikace: `GPT/GRID-TLACITKA-SPECIFIKACE-A-CSS-2026-09-06.md`.
Implementace je na jednom místě v `grid-divi5-child/style.css`, blok
„TLAČÍTKA — jednotný systém pro celý web". Platí i pro obsah, který
vykreslují pluginy.

## Nejdůležitější pravidlo

**Barvu sekundární a terciární akce určuje povrch, ne třída tlačítka.**
Proto v CSS nesmí být nic jako `.btn { color:#fff !important }`. Takové
pravidlo rozbije sekundární tlačítko na světlé sekci a vynutí si další
`!important` na jeho přebití. Přesně to se tu jednou stalo a odstraňovalo
se to zpětně.

Povrch se deklaruje proměnnými:

```css
--gh-action-fg      /* barva textu sekundární a terciární akce */
--gh-action-border  /* barva rámečku sekundární akce a filtru */
```

| Povrch | Deklarují ho | Text | Rámeček |
|---|---|---|---|
| světlý | `.sec-light`, `.gh-surface--light` | `#17181B` | `#A3A19D` |
| tmavý | `.sec-dark`, `.gh-surface--dark`, patička | bílá | bílá 55 % |
| fotografie | `.hero`, `.sp-media`, `.room--m1/m2/m4`, `.hover-card2` | bílá | bílá 55 % |

Nový povrch se přidá tím, že si nastaví tyto dvě proměnné. Nikdy ne tím,
že se tlačítku vnutí barva.

## Varianty

| Varianta | Kdy | Klid | Hover |
|---|---|---|---|
| `.gh-btn--primary` | rezervace, odeslání, hlavní konverze sekce | červená plocha, bílý text | jasnější červená, svit, jemný výjezd |
| `.gh-btn--secondary` | detail, druhá rovnocenná akce | průhledná, barva dle povrchu | zůstává průhledná, **jen rámeček zčervená** |
| `.gh-btn--tertiary` | „Zjistit více", odkaz na detail sekce | text s červeným podtržením, šipka skrytá | podtržení se zkrátí, šipka najede, odkaz se posune doprava |
| `.gh-filter` | filtry galerie a kategorií | průhledná s rámečkem | červený rámeček; vybraný je červeně vyplněný |

V jedné skupině je **jen jedno** primární CTA.

## Mapování historických tříd

| Stará třída | Varianta |
|---|---|
| `.btn` | primární |
| `.btn.btn-ghost` | sekundární |
| `.sec-more` | terciární |
| `.gal-fbtn` | filtr |
| `.ff-btn-submit`, `button[type=submit]` | primární |
| `.et_pb_button` (nativní Divi modul) | primární |

Nová tlačítka piš rovnou v `.gh-btn` variantách. Historické třídy fungují
dál, ale nezavádět je do nového kódu.

## Divi 5

Cílový stav je, aby každé tlačítko bylo **nativní Divi modul Tlačítko** —
editor ho pak umí posunout i upravit bez zásahu do kódu.

Modul ve výchozím stavu vypadá jako primární. Jinou variantu určíš třídou
modulu (`gh-btn--secondary`, `gh-btn--tertiary`, `gh-filter`) v nastavení
modulu, záložka Pokročilé.

Divi si kreslí vlastní šipku přes `::after` a při hoveru mění `padding`
a `border`, takže tlačítko poskočí a změní velikost. Obojí je v CSS
potlačené. `!important` je tu v pořádku — přebíjí Diviho generované
hodnoty, ne naši vlastní kaskádu.

## Značka

```html
<a class="gh-btn gh-btn--primary" href="/rezervace/">Rezervovat pobyt</a>

<a class="gh-btn gh-btn--tertiary" href="/zazitky/">
  <span class="gh-btn__label">Zjistit více</span>
  <span class="gh-btn__arrow" aria-hidden="true">→</span>
</a>

<button class="gh-filter" type="button" aria-pressed="true">Vše</button>
```

Pro navigaci vždy `<a>`, pro akci na stránce `<button type="button">`.
`div` s `role="button"` je zakázaný. Název akce je vždy text, šipka je
dekorace (`aria-hidden`). Filtry musí mít právě jednu hodnotu
`aria-pressed="true"` a JavaScript ji při přepnutí aktualizuje.

Celé karty, které fungují jako odkaz, tento systém neřeší — spec je
vyjímá.

## Ověření

```bash
cd web-final/wordpress/tools/qa
node audit-tlacitka.mjs --vp=1600,390   # soupis prvků a jejich aktivní plochy
node kontrola-tlacitek.mjs              # barvy podle povrchu
node kontrola-stavu.mjs                 # klid, hover, focus
node kontrola-focus.mjs                 # focus přes Tab (cookie lišta se odbaví)
node kontrola-divi-ff.mjs               # nativní Divi modul a Fluent Forms
node kontrola-pohyb.mjs                 # prefers-reduced-motion
```

Focus se testuje **klávesou Tab**, ne programovým `focus()` — `:focus-visible`
na myš záměrně nereaguje. Po Tabu je potřeba počkat, box-shadow se rozsvěcí
přechodem 250 ms.

Viz [[grid-responzivita]] a [[grid-qa-responzivita]].
