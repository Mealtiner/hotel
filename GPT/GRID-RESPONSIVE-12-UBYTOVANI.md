# GRID Hotel — Ubytování

## Rozsah

- URL: CZ `/ubytovani/`; EN `/en/accommodation/`; DE `/de/unterkunft/`
- Zdroj/komponenty: `grid_rooms`, Kategorie pokojů plugin a atomické Divi moduly.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro T3
2. Čtyři karty kategorií pokojů
3. Vybavení/amenity seznam
4. Srovnávací tabulka
5. Dobré vědět
6. CTA
7. Footer

## Atomická Divi 5 struktura

Každá kategorie je samostatná karta s Image, Heading, Text/metadata a Button modulem. Tabulka a datové seznamy mohou být dynamická komponenta, ale musí mít vlastní sémantickou tabulku a responsive wrapper.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Pokoje: 2×2 / 2×2 / 2×2 / 1×4.
- Amenities: 3 / 2 / 2 / 1 nebo 2 podle délky textu.
- Tabulka: plná šířka koridoru; na mobilu řízený vnitřní scroll jen tabulky.
- Definiční seznamy: 2 / 2 / 1 / 1.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 168 px.
- Karty se na portrait aktuálně skládají do 1 sloupce; cílem je 2×2, pokud min. šířka 220 px vyjde.
- Amenities na portrait zůstávají tři úzké sloupce okolo 105 px.
- Dlouhé parametry mohou určovat min-width rodiče.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Čtyři karty mají stejný rastr bez pevné výšky textu.
- [ ] Na 768 px jsou dvě karty v řádku a žádná není užší než 220 px.
- [ ] Na 390 px je jedna karta na řádek a obsah má 350 px.
- [ ] Tabulka nepřenáší svůj overflow na dokument.
- [ ] Dlouhé DE názvy vybavení se zalomí.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

