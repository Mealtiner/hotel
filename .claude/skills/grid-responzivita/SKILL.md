---
name: grid-responzivita
description: Závazný layoutový systém webu GRID Hotel — koridor obsahu, čtyři breakpointové režimy, jednotné názvosloví tříd a proměnných, pravidla specificity vůči Divi 5. Načti VŽDY před jakoukoli úpravou rozvržení, šířek, mezer, mřížek nebo responzivity na tomto webu, i když jde o "jen jednu stránku" nebo "jen jeden odstavec" — cílem je, aby se každá oprava dala udělat jednou globálně, ne dvacetkrát lokálně.
---

# GRID Hotel — layoutový a responzivní systém

Zdroj pravdy je `GPT/GRID-RESPONSIVE-*.md` v repu. Pořadí autority při rozporu:
globální pravidla → breakpointy → sekce a komponenty → specifikace stránky → QA.
Tento skill je provozní shrnutí; když se rozejde se zadáním, platí zadání.

## Nejdůležitější pravidlo

**Nikdy neopravuj layout na konkrétní stránce, pokud stejný prvek existuje i jinde.**
Web má jednu sadu sekcí (T1–T8) sdílenou napříč stránkami. Oprava patří do
`grid-divi5-child/style.css` k té sdílené třídě, ne do per-page výjimky.
Per-page pravidlo (`body.home …`, `#firemni …`) je přípustné jen tehdy, když
se ta odlišnost dá popsat jako záměr designu, ne jako kompenzace chyby jinde.

## Čtyři režimy — jediné povolené breakpointy

| Režim | Rozsah | Referenční viewport | Pravá sekční navigace | Levý HUD |
|---|---|---|---|---|
| Desktop | ≥ 1280 px | 1600 × 1000 | viditelná | 224 px |
| Tablet na šířku | 960–1279 px | 1024 × 768 | viditelná | 224 px |
| Tablet na výšku | 641–959 px | 768 × 1024 | skrytá | 190 px |
| Mobil | ≤ 640 px | 390 × 844 | skrytá | sbalený, bez rezervace šířky |

Hranice 960, 641 a 1280 jsou atomické: při 959 px navigace nesmí být vidět,
při 960 px musí být vidět a obsah se přepočítá.

**Zděděný problém:** stávající CSS používá dvanáct různých breakpointů
(520, 560, 600, 640, 720, 780, 782, 860, 900, 980, 1080, 1081). To je hlavní
důvod, proč nejde nic opravit globálně. Každý dotyk kódu je příležitost
sjednotit dotčené pravidlo na tyto čtyři režimy. Nezavádět nové mezihodnoty.

## Koridor obsahu — jediný zdroj geometrie

Geometrii počítá **jedna** sada proměnných v `:root`. Žádná sekce si nesmí
počítat vlastní koridor, žádná stránka nesmí mít vlastní kompenzaci.

```css
:root{
  --gh-header-h: 86px;
  --gh-hud-left: clamp(14px, 2vw, 26px);
  --gh-hud-w: 224px;             /* tablet na výšku: 190px */
  --gh-overlay-gap-left: 40px;   /* mezera HUD → obsah */
  --gh-rail-axis-offset: 215px;  /* šířka overlay pravé navigace */
  --gh-overlay-gap-right: 45px;  /* mezera obsah → osa navigace */
  --gh-content-max: 1050px;
  --gh-section-py: clamp(80px, 12vh, 150px);
  --gh-grid-gap: clamp(16px, 1.6vw, 26px);
}
```

Odvozené hodnoty se počítají **jen jednou**:

```css
--gh-hud-edge:      calc(var(--gh-hud-left) + var(--gh-hud-w));
--gh-content-start: calc(var(--gh-hud-edge) + var(--gh-overlay-gap-left));
--gh-rail-axis:     calc(100vw - var(--gh-rail-axis-offset));
--gh-content-end:   calc(var(--gh-rail-axis) - var(--gh-overlay-gap-right));
--gh-content-width: calc(var(--gh-content-end) - var(--gh-content-start));
```

Cílové bezpečné hranice (tolerance ±2 px):

| Viewport | Obsah od | Obsah do | Šířka |
|---|---|---|---|
| 1600 | 290 | 1340 | 1050 |
| 1024 | 284 | 764 | 480 |
| 768 | 245 | 737 | 492 |
| 390 | 20 | 370 | 350 |

**Na mobilu se rezervace za HUD musí explicitně vynulovat.** Nejčastější
chyba webu: `--hud-pad` zůstane vypočtený na ~278 px i na 390px displeji,
takže obsahu zbude ~92 px. Reset patří do mobilního režimu, ne do jednotlivých
sekcí.

## Co smí přesáhnout koridor

Jen tyto prvky, a vždy explicitně přes `.gh-full-bleed`:

- pozadí hero;
- fotografie v levé polovině dělených sekcí T2/T7;
- celoplošná barva nebo pozadí sekce;
- pozadí finálního CTA;
- mapa, pokud její obsahový rám zůstane v koridoru.

Texty, formuláře, karty, tabulky, odkazy a tlačítka nikdy.

## Názvosloví — jedno jméno pro jednu věc

Aby šlo opravovat globálně, musí mít táž věc všude tentýž název. Při psaní
nového kódu používej tyto názvy; při dotyku starého kódu ho na ně převáděj.

**Layoutové třídy — každá má jedinou odpovědnost:**

| Třída | Odpovědnost |
|---|---|
| `.gh-section` | pouze svislé odsazení sekce |
| `.gh-container` | bezpečný koridor |
| `.gh-container--centered` | explicitní centrovaná výjimka |
| `.gh-intro` | blok kicker + nadpis + lead |
| `.gh-intro--half` | text omezený na polovinu dostupné šířky |
| `.gh-grid` | pouze `display` a `gap` |
| `.gh-grid--2/3/4` | počet sloupců |
| `.gh-split` | dělená sekce |
| `.gh-card` | společná geometrie karty |
| `.gh-form-grid` | mřížka formuláře |
| `.gh-full-bleed` | jediná explicitní výjimka z koridoru |
| `.gh-stack-tablet`, `.gh-stack-mobile` | skládání tam, kde komponenta nemá vlastní typ |

**Sekční typy (drž toto značení, řídí se jím matice sloupců i QA):**

| Typ | Význam | Desktop / Tablet-L / Tablet-P / Mobil |
|---|---|---|
| T1 | vstupy | 4 / 2 / 2 / 1 |
| T2, T7 | dělená sekce obraz + text | 2 / 2 / stacked / stacked |
| T3 | pokoje | 2×2 / 2×2 / 2×2 / 1 |
| T4 | zážitky | 3 / 2 / 2 / 1 |
| T5 | gastronomie | 3 svislé / 3 vodorovné / 3 vodorovné / 3 svislé |
| T6 | sezóna a formulář | 3:2 / stacked / stacked / stacked |
| T8 | recenze a partneři | 3 / 1 / 1 / 1; partneři 4 / 2 / 2 / 2 |
| — | galerie | 4 / 2 / 2 / 2 |
| — | patička | 4 / 2 / 2 / 1 |

Stávající třídy (`.entries`, `.rooms`, `.exp`, `.gastro`, `.split`, `.reviews`,
`.season`, `.foot-top`) jsou legacy jména těchto typů. Lze je dočasně mapovat
na nová, ale výsledkem nesmí být dva souběžné systémy.

**Stránky** mají stabilní ID v `tools/qa/qa-config.mjs` (`domov`, `kontakt`,
`pokoj-standard`, …) shodné s číslem specifikace v `GPT/`. Používej tato ID
v commitech, reportech i poznámkách, ať se dá práce dohledat.

## Specificita vůči Divi 5

Divi generuje vlastní pravidla s vysokou specificitou a inline hodnoty.
Historicky vznikly chyby tím, že základ má `!important`, ale breakpointový
přepis ne — pak se sekce na mobilu nesloží.

Závazně:

1. Responzivní přepis má **stejnou nebo vyšší specificitu** než základ.
2. Je-li `!important` v základu, musí být i v breakpointovém přepisu.
3. `!important` jen na přepis Divi inline/generovaných hodnot, ne z pohodlí.
4. Pro základ preferuj `:where()` (nulová specificita), aby komponentové
   třídy mohly přepisovat bez boje.
5. Pořadí vrstev v souboru: tokeny → základ → komponenty → breakpointy →
   Divi kompatibilita.

Konkrétně: `.et_pb_row.split` a `.et_pb_row.gastro` mají v základu
`grid-template-columns … !important`. Přepis musí být psaný jako
`.et_pb_row.split` / `.et_pb_row.gastro` (ne holé `.split` / `.gastro`),
jinak neprojde.

## Typografie a textový tok

- Hero H1 zhruba 12–14 znaků na vizuální řádek; zalomení řídí šířka, ne `<br>`.
- Sekční H2 `max-inline-size: 16ch`; lead 52–60ch; odstavec 68ch.
- Odkazy, e-maily a URL `overflow-wrap: anywhere`.
- Každý grid/flex potomek `min-width: 0` — bez toho německé složeniny roztáhnou
  kontejner a vznikne přetečení.
- Blok obsahující text nikdy nemá pevnou výšku.

Typografická škála podle režimu je v `GPT/GRID-RESPONSIVE-02` §5; používej
`clamp()` uvnitř režimu, nikdy `transform: scale()`.

## Média a formuláře

- Obrázky: `display:block; width:100%; height:100%; object-fit:cover`;
  výřez řeš `object-position` konkrétního snímku, ne rozměrem rodiče.
- Karty mají stabilní `aspect-ratio`, ne výšku odvozenou z desktopového snímku.
- Iframe a video `width:100%; aspect-ratio:16/9; border:0`.
- Formulářové prvky `width:100%; min-width:0`, minimální výška 44 px,
  textarea alespoň 120 px, tlačítko na mobilu 100 %.
- Dvousloupcová mřížka formuláře jen tehdy, když pole zůstane ≥ 220 px.

## Zákaz maskování přetečení

`overflow-x: hidden` na `body` **není oprava**. Skutečná podmínka, která musí
platit na každé URL a v každém režimu:

```js
document.documentElement.scrollWidth <= document.documentElement.clientWidth
```

Protože stávající `overflow-x:hidden` tuto vadu schová, měř ji nástrojem
`tools/qa/resp-audit.mjs`, který ho pro měření dočasně vypne. Viz [[grid-qa-responzivita]].

## Lokalizace

Každá úprava se ověřuje v CZ, EN i DE. Nestylovat podle délky českého řetězce,
nedávat pevné šířky navigačním položkám, nemít jazykově odlišný layout.
Němčina je nejtvrdší test šířky.

## Než označíš stránku za hotovou

- nemá horizontální přetečení v žádném testovaném viewportu;
- obsah respektuje koridor a overlay hranice;
- správně se přeskupí ve všech čtyřech režimech;
- CZ, EN i DE se vejdou bez rozbití;
- každý obsahový prvek zůstává editovatelný přes vlastní Divi 5 modul
  (viz [[grid-divi-moduly]]);
- neexistuje monolitický Text/Code modul s celou stránkou;
- oprava je ve sdíleném pravidle, ne v per-URL výjimce.
