# GRID Hotel — globální pravidla implementace

## 1. Rozdělení odpovědností

### Divi 5

Divi vlastní strukturu stránky a editovatelný obsah:

- Section = jedna vizuální sekce;
- Row = layoutový kontejner;
- Column = skutečný sloupec;
- Image module = každý samostatný obrázek;
- Heading/Text module = každý samostatně editovatelný nadpis nebo text;
- Button module = samostatné CTA;
- Form/Code/Shortcode module jen pro komponentu, která je skutečně dynamická.

Zakázáno:

- vložit celý HTML obsah stránky do jednoho Text nebo Code modulu;
- řídit stránku inline styly;
- kopírovat responzivní pravidla do každé stránky;
- opravovat layout pomocí záporných marginů závislých na konkrétním textu;
- používat pevnou výšku pro blok obsahující text.

### Child theme

`grid-divi5-child/style.css` vlastní:

- globální design tokeny;
- koridor obsahu;
- breakpointy;
- layoutové třídy;
- obecnou responzivitu;
- výjimky T2/T7, hero a finální CTA;
- kompatibilní přepis Divi wrapperů.

### Pluginy

Pluginy vlastní data a dynamické chování. Framework musí zůstat 2.4. Plugin nesmí přidávat druhý konkurenční layoutový systém.

- `garry-typografie`: fonty, typografické tokeny a atomické třídy;
- `garry-situace-na-trati`: levý fixed HUD;
- `garry-bocni-posuvnik`: pravý fixed track progress;
- `gridhotel-components`: dynamické komponenty a jejich sémantický markup;
- `gridhotel-core`: data, CPT, ACF a API;
- specializované pluginy: pouze jejich doménová data/chování.

## 2. Jediný zdroj geometrie

Definovat jedinou sadu proměnných. Žádná sekce nesmí znovu vypočítávat vlastní koridor.

Doporučené názvy:

```css
:root {
  --gh-header-h: 86px;
  --gh-hud-left: clamp(14px, 2vw, 26px);
  --gh-hud-w: 224px;
  --gh-overlay-gap-left: 40px;
  --gh-rail-axis-offset: 215px;
  --gh-overlay-gap-right: 45px;
  --gh-content-max: 1050px;
  --gh-section-py: clamp(80px, 12vh, 150px);
  --gh-grid-gap: clamp(16px, 1.6vw, 26px);
}
```

Odvozené hodnoty se počítají pouze jednou:

```css
--gh-hud-edge: calc(var(--gh-hud-left) + var(--gh-hud-w));
--gh-content-start: calc(var(--gh-hud-edge) + var(--gh-overlay-gap-left));
--gh-rail-axis: calc(100vw - var(--gh-rail-axis-offset));
--gh-content-end: calc(var(--gh-rail-axis) - var(--gh-overlay-gap-right));
--gh-content-width: calc(var(--gh-content-end) - var(--gh-content-start));
```

Pro desktop může být obsah omezen na 1050 px. Musí však zůstat zarovnaný k levé bezpečné hraně, ne libovolně centrovaný vůči viewportu.

## 3. Overlay objekty

HUD i track progress jsou sourozenci obsahu připojení k `body`, nikoli potomci sekce.

- `position: fixed`;
- vlastní z-index nad stránkou;
- jejich obdélníky se nesmí započítat do šířky sekce;
- obsah jim ustupuje pomocí globálního koridoru;
- při otevřeném Divi editoru nesmí blokovat editorové ovládání;
- admin bar posouvá jejich `top`, ale nemění obsahový koridor.

Z-index kontrakt:

- stránka a pozadí: 0–20;
- fixed header: 80;
- right rail: 90;
- HUD: 100;
- mobilní menu: 130;
- Divi editorové UI: nesmí být přebito vlastním z-indexem.

## 4. Povolené výjimky z koridoru

Pouze tyto prvky mohou být full bleed:

- pozadí hero;
- fotografie v levé polovině T2/T7;
- celoplošná barva/pozadí sekce;
- finální CTA pozadí;
- případně mapa, pokud její obsahový rám zůstane v koridoru.

Texty, formuláře, karty, tabulky, navigační odkazy a tlačítka nesmí překročit bezpečné hranice.

## 5. Atomické CSS třídy

Každá třída má jedinou odpovědnost.

- `.gh-section`: pouze vertikální sekční odsazení;
- `.gh-container`: bezpečný koridor;
- `.gh-container--centered`: explicitní centrovaná výjimka;
- `.gh-intro`: blok kicker + heading + lead;
- `.gh-intro--half`: omezení textu na polovinu dostupné šířky;
- `.gh-grid`: pouze display a gap;
- `.gh-grid--2`, `.gh-grid--3`, `.gh-grid--4`: počet sloupců;
- `.gh-split`: dělená sekce;
- `.gh-card`: společná geometrie karty;
- `.gh-form-grid`: mřížka formuláře;
- `.gh-full-bleed`: jediná explicitní výjimka;
- `.gh-stack-tablet`, `.gh-stack-mobile`: pouze tam, kde komponenta nemá vlastní typ.

Původní třídy lze dočasně mapovat na nové, ale výsledkem nesmí být dva odlišné systémy.

## 6. Specificita a Divi 5

Aktuální chyby vznikají mimo jiné tím, že:

- `.et_pb_row.split { grid-template-columns: 1fr 1fr !important; }`
- `.et_pb_row.gastro { grid-template-columns: repeat(3, 1fr) !important; }`

mají vyšší specificitu než responzivní `.split` a `.gastro`.

Pravidlo:

- responzivní přepis musí používat stejnou nebo vyšší specificitu;
- preferovat `:where()` pro základ a vrstvy CSS v řízeném pořadí;
- `!important` používat jen na přepis Divi inline/generated hodnot;
- je-li `!important` v základu, musí být i v breakpointovém přepisu;
- pořadí: tokeny → základ → komponenty → breakpointy → Divi compatibility.

## 7. Typografie a textový tok

- Hero H1: maximálně 12–14 znaků na vizuální řádek; zalomení řídit šířkou, ne `<br>`, pokud nejde o schválený copy lock.
- Sekční H2: `max-inline-size: 16ch`.
- Lead: `max-inline-size: 52–60ch`.
- Běžný odstavec: `max-inline-size: 68ch`.
- Odkazy, e-maily a URL: `overflow-wrap:anywhere`.
- Německé složeniny nesmí rozšiřovat kontejner.
- Textové boxy používají `min-width:0`.
- Neomezovat blok textu pevnou výškou.

## 8. Obrázky a média

- `display:block; width:100%; height:100%; object-fit:cover`;
- `object-position` je atribut konkrétního snímku;
- karty mají stabilní `aspect-ratio`, nikoli výšku podle jednoho desktopového screenshotu;
- iframe/video: `width:100%; aspect-ratio:16/9; border:0`;
- žádné médium nesmí určovat minimální šířku rodiče;
- `max-width:100%` pro SVG, canvas a iframe.

## 9. Formuláře

- všechny kontroly `width:100%; min-width:0`;
- grid child `min-width:0`;
- label zůstává nad polem;
- chybová zpráva je pod konkrétním polem;
- tlačítko má na mobilu šířku 100 %;
- minimální výška ovládacího prvku 44 px;
- textarea min-height 120 px;
- datumové, select a captcha prvky musí projít testem v Safari/Chrome;
- formulář nikdy nesmí být širší než jeho rodič.

## 10. Přístupnost a pohyb

- kontrast WCAG 2.2 AA;
- viditelný `:focus-visible`;
- klikací cíl nejméně 44 × 44 px, i když vizuální ikona je menší;
- pořadí DOM musí odpovídat mobilnímu čtení;
- `prefers-reduced-motion` vypne reveal/scroll animace;
- fixed overlay nesmí zakrýt aktivní focus;
- kotvy mají `scroll-margin-top: calc(var(--gh-header-h) + 16px)`.

## 11. Lokalizace

Každá úprava se testuje minimálně v CZ, EN a DE.

- nestylovat podle délky českého řetězce;
- žádné pevné šířky navigačních položek;
- CTA dovolí zalomení na dva řádky, ale nemění výšku sousední karty nekontrolovaně;
- tabulky a formuláře používají lokalizované labely bez ořezu;
- jazyková verze nesmí mít odlišný layoutový shortcode.

## 12. Globální zákaz horizontálního overflow

`overflow-x:hidden` na `body` není oprava. Lze jej použít jen jako pojistku po odstranění skutečných příčin.

Povinná invariantní podmínka:

```js
document.documentElement.scrollWidth <= document.documentElement.clientWidth
```

Musí platit při 320, 360, 390, 640, 641, 768, 959, 960, 1024, 1279, 1280 a 1600 px.

