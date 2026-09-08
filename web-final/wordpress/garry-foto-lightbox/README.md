# GARRY – Foto lightbox

Lightbox pro fotogalerie s nastavitelným pozadím, logem webu, popiskem nad
snímkem, doprovodnou informací pod ním a vodorovným ukazatelem pořadí ve stylu
trackovače na trati.

- Verze: 1.0.0
- Framework: GARRY Embedded 2.4 (vlastní namespacovaná kopie, žádná závislost
  na jiném GARRY pluginu)
- Nastavení: **GARRY nastavení → Foto lightbox**

## Jak se lightbox dostane na stránku

Nemusíte nic vkládat. Je-li plugin zapnutý, převezme na frontendu odkazy na
obrázky podle selektorů v nastavení (výchozí: `.roomgallery a`,
`.wp-block-gallery a`, `.gallery a`, `[data-lightbox]`).

Nad rámec toho jsou k dispozici tři cesty:

| Cesta | K čemu |
|---|---|
| `[garry_lightbox]` | přenastaví lightbox jen pro jednu stránku, nic nevykreslí |
| `[garry_lightbox_galerie ids="12,13,14"]` | vlastní mřížka náhledů napojená na lightbox |
| Elementor widget **GARRY – Foto lightbox** | totéž klikacím rozhraním |
| Divi modul **GARRY – Foto lightbox** | jen Divi 4, viz níže |

### Shortcode `[garry_lightbox]`

Přijímá stejné klíče jako nastavení:

```
[garry_lightbox pozadi_typ="radial" pozadi_barva1="#0af" sipky_zobrazit="0"]
[garry_lightbox aktivni="0"]   vypne lightbox na téhle stránce
```

### Shortcode `[garry_lightbox_galerie]`

```
[garry_lightbox_galerie ids="12,13,14" sloupce="3" mezera="12"
                        velikost="large" nahled="medium_large" skupina="pokoj"]
```

`skupina` určuje, které snímky spolu listují. Bez ní se odvodí z výběru
obrázků.

## Divi a Elementor

**Elementor** — widget se registruje, jen když Elementor běží. Umí buď jen
aktivovat lightbox, nebo vykreslit mřížku náhledů, a v záložce Styl přenastavit
vzhled pro danou stránku.

**Divi** — modul se registruje **jen na Divi 4**. Na Divi 5 by registrace
legacy modulu vynutila načtení celého Divi 4 frameworku a přepnula stránky do
kompatibilního režimu, což rozbíjí vykreslování. Stejná strážní funkce
`garry_divi4_legacy_builder()` je i v ostatních GARRY pluginech.

Na Divi 5 tím nic neztrácíte: lightbox se aktivuje sám nad odkazy na obrázky
a shortcode funguje v modulu Text i Kód. Nativní Divi 5 modul je React
komponenta se samostatným sestavením — kdyby byl potřeba, je to samostatný
úkol, ne dopsání jednoho souboru.

## Co jde nastavit

**Pozadí** — plná barva, lineární, radiální nebo kónický přechod, nebo barva
v každém ze čtyř rohů. Dvě až tři barvy podle typu, úhel, střed, krytí
a rozostření pozadí pod lightboxem.

**Logo** — zapnout/vypnout, zdroj (logo webu z Přizpůsobení, vybraný obrázek
z médií, vlastní adresa), roh, výška, krytí.

**Text nad snímkem** — zapnout/vypnout, šablona se značkami `{web}`,
`{galerie}`, `{index}`, `{celkem}`, `{titulek}`, `{popisek}`, `{popis}`,
`{alt}`, zarovnání, barva, velikost.

**Informace pod snímkem** — zapnout/vypnout, zdroj (popisek, popis,
alternativní text, titulek), barva, velikost.

**Ukazatel pořadí** — zapnout/vypnout, čísla pod body, barvy čáry, projité
části, bodu, aktivního bodu a čísla, a horní hranice počtu bodů (nad ní
zůstane jen postupová čára).

**Šipky a křížek** — zapnout/vypnout, styl (samotná šipka, v kroužku,
v rámečku), barvy základní i při najetí, velikost.

**Chování** — hlavní vypínač, selektory odkazů, smyčka, klávesnice, dotyková
gesta, kolečko myši, přednačítání sousedních snímků, zápis pořadí do adresy
(`#foto-3`) a automatické přehrávání s prodlevou.

## Odkud se bere doprovodná informace

U galerie z `[garry_lightbox_galerie]` (a z widgetu) plugin vypisuje popisek,
popis i titulek přímo z knihovny médií — má je k dispozici při vykreslení.

U cizích galerií pracuje s tím, co je opravdu ve stránce: popisek z
`<figcaption>`, alternativní text a titulek z obrázku. **Popis z knihovny
médií tam WordPress nevypisuje**, takže volba „Popis z knihovny médií“
zůstane u cizích galerií prázdná. To není chyba, ale důsledek toho, že plugin
si kvůli jednomu popisku nedělá dotaz do databáze pro každý snímek na stránce.

## Přístupnost

Dialog má `role="dialog"` a `aria-modal`, ohnisko se drží uvnitř (Tab
i Shift+Tab), Esc zavírá a po zavření se ohnisko vrací na odkaz, ze kterého se
lightbox otevřel. Body ukazatele jsou tlačítka s `aria-current`. Při zapnutém
`prefers-reduced-motion` se vypnou všechny přechody.

## Bezpečnost a výkon

- Žádný inline `<style>` ani `<script>` — nastavení jde do `data-` atributů
  a JS je přepíše do CSS custom properties. Manifest proto deklaruje
  `inline_assets` false/false.
- Sanitizace nastavení je whitelist podle `gflb_defaults()`; barvy se ověřují
  proti hex i `rgb()/rgba()`, číselné hodnoty se ořezávají na rozsah.
- Administrační assety se načítají jen na vlastní stránce pluginu.
- Na frontendu se nedělá žádný dotaz do databáze — seznam snímků se sbírá
  z už vykresleného DOM.

## Historie změn

### 1.4.0 — 2026-09-07
- Skrytý odkaz na další galerii už nenese prázdné `href="#"` — bez cíle to není odkaz a nezasahuje do pořadí procházení.

### 1.3.0 — 2026-09-07
- Dlaždice galerie dostala `aria-label` složený z názvu fotky a pořadí („… — zvětšit fotku 2 z 8"); bez něj ji čtečka ohlásila jen jako „odkaz".
- `alt` obrázku je prázdný, aby se stejný text nečetl dvakrát.

### 1.0.0 — 2026-09-06
- První verze.

