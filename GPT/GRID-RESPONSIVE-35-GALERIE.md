# GRID Hotel — Galerie

## Rozsah

- URL: CZ `/galerie/`; EN `/en/gallery/`; DE `/de/galerie/`
- Zdroj/komponenty: `grid_galerie`, data z `gridhotel_get_gallery_blocks()`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Filtry kategorií
3. Mřížka fotografií
4. Lightbox
5. Footer

## Atomická Divi 5 struktura

Filtry jsou skutečná tlačítka a mřížka iteruje jednotlivé obrázky. Neřešit layout přes pořadové inline styly. Prázdný stav je samostatný textový blok.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Galerie: 4 / 2 / 2 / 2.
- Filtry: flex-wrap ve všech režimech.
- Obrázky 4:3; lightbox používá původní poměr.
- Mezera 16–24 px podle režimu.

## Současné chyby, které je nutné odstranit

- Aktuální local galerie je prázdná; vizuální responzivita proto nebyla reálně ověřena.
- Současné CSS předpokládá 4 sloupce při 1024 a 3 při 768, což je v bezpečném koridoru příliš husté.
- Mobilní prázdná stránka přetékala přibližně o 38 px kvůli globálnímu paddingu.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Před schválením nahrát alespoň 12 fotografií ve 3 kategoriích.
- [ ] Při 1024 a 768 jsou přesně 2 sloupce.
- [ ] Při 320 px zůstávají 2 sloupce jen pokud každá buňka ≥136 px; jinak 1.
- [ ] Filtry se zalamují bez interního horizontálního scrollu.
- [ ] Lightbox ovladatelný klávesnicí a tlačítka nejsou pod overlayem.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

