# GRID Hotel — Gastronomie

## Rozsah

- URL: CZ `/gastronomie/`; EN `/en/gastronomy/`; DE `/de/verpflegung/`
- Zdroj/komponenty: `grid_gastro`, `grid_menu_hlavni`, plugin `garry-denni-menu`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro T5
2. Tři gastro provozy
3. Týdenní menu
4. Stálá nabídka
5. Catering CTA
6. Footer

## Atomická Divi 5 struktura

Každá gastro karta: Image module + obsahový Column s kickerem, H3, popisem, parametry a CTA. Menu je dynamická komponenta; její karty nesmí určovat šířku stránky.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Gastro: 3 svislé / 3 horizontální řádky 40:60 / stejné / 3 svislé řádky.
- Menu: 4×2 / 2×4 / 2×4 / 1×8.
- Catering CTA: 2 sloupce / stacked / stacked / stacked.

## Současné chyby, které je nutné odstranit

- Na 1024 i 768 zůstávají tři vertikální sloupce.
- Na 390 zůstávají tři sloupce přibližně po 15 px.
- Příčinou je `.et_pb_row.gastro` s `!important`, které přebíjí slabší breakpoint selector.
- Mobilní stránka přetékala přibližně o 42 px i mimo samotnou gastro mřížku.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Breakpointový selector má alespoň stejnou specificitu jako základ.
- [ ] Na obou tabletech má každá gastro karta obrázek vlevo 40 % a text vpravo 60 %.
- [ ] Na mobilu je obrázek nahoře a text dole.
- [ ] Menu přechází 4→2→2→1 bez overflow.
- [ ] Časy a ceny používají tabular-nums a nezvětšují sloupec.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

