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

## Koridor obsahu — závazné vzorce

Geometrii počítá **jedna** sada proměnných. Žádná sekce si nesmí počítat
vlastní koridor, žádná stránka nesmí mít vlastní kompenzaci.

Pro návrh se HUD („situace na trati") považuje za **trvale rozbalený,
nezavíratelný objekt**. Hlavní obsah proto vždy začíná až za jeho pravým
okrajem plus 40 px. HUD nesmí překrývat text, formuláře ani karty.
Pravá sekční navigace je jediná fixed navigace; obsah končí 45 px před
její osou, a to všude, kde se navigace **skutečně vykreslí**.

```css
/* ≥ 960 px */
--hud-left: clamp(14px, 2vw, 26px);
--hud-width: 224px;
--content-left: calc(var(--hud-left) + var(--hud-width) + 40px);
--rail-axis: calc(100vw - 215px);
--content-right: calc(var(--rail-axis) - 45px);   /* = 100vw − 260 */

/* 641–959 px  — rail je skrytý, hranice vpravo platí dál */
--hud-width: 190px;
--content-left: calc(var(--hud-left) + var(--hud-width) + 40px);
--content-right: calc(100vw - clamp(24px, 4vw, 32px));

/* ≤ 640 px — HUD je ve sbaleném režimu a nerezervuje žádnou šířku */
--content-left: 20px;
--content-right: calc(100vw - 20px);
```

Kontrolní body (tolerance ±2 px, ověřuje `tools/qa`):

| Viewport | HUD | Obsah začíná | Obsah končí | Šířka |
|---:|---:|---:|---:|---:|
| 1600 | 224 | 290 | 1340 | 1050 |
| 1440 | 224 | 290 | 1180 | 890 |
| 1366 | 224 | 290 | 1106 | 816 |
| 1280 | 224 | 290 | 1020 | 730 |
| 1024 | 224 | 284 | 764 | 480 |
| 960 | 224 | 283 | 700 | 417 |
| 768 | 190 | 245 | 737 | 492 |
| 641 | 190 | 244 | 615 | 371 |
| 390 | sbalený | 20 | 370 | 350 |

Na stránce **bez** sekční navigace platí vpravo jen bezpečný inset, ne
rezerva na lištu. Rozhoduje o tom třída `has-section-nav`, kterou dává
plugin GARRY Boční posuvník podle toho, jestli se lišta opravdu vykreslí —
nikdy ne výčet ID stránek.

**Na mobilu se rezervace za HUD musí explicitně vynulovat.** Historická
chyba: `--hud-pad` zůstal vypočtený na ~278 px i na 390px displeji, takže
obsahu zbylo 92 px a každá stránka přetékala.

### Úzký koridor 960–1024 px

V tomhle pásmu je obsah široký jen 417–480 px. Dvousloupcové rozvržení by
tu dávalo poloviny po zhruba 200 px, tedy pod hranicí 220 px, kterou zadání
pro buňku dvousloupcové mřížky vyžaduje. **Karty, dělené sekce T2/T7 a
dvojice formulářových polí zde přecházejí na jeden sloupec dřív** než
ostatní mřížky. Geometrie koridoru se tím nemění.

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

**Šířka řádku.** Divi dává každému nevnořenému řádku
`width: var(--content-width)`, ve výchozím nastavení 80 %. Samotné
`max-width` na `.wrap` proto nestačí — řádek je na 1280px obrazovce široký
jen 1024 px. Řeší to
`.sec .et_pb_row.et_flex_row:not(.et_pb_row_nested):not(.et_pb_row_inner):not(.entries):not(.reviews){width:100% !important}`.
`.entries` a `.reviews` jsou vyjmuté — ty si šířku počítají samy z vlastních
okrajů a plná šířka by se k jejich marginům přičetla.

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
