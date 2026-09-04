# GRID Hotel — O nás

## Rozsah

- URL: CZ `/o-nas/`; EN `/en/about-the-hotel/`; DE `/de/ueber-uns/`
- Zdroj/komponenty: Divi stránka s `[grid_onas]` nebo atomizovaná ekvivalentní struktura; data z `gridhotel-components/inc/shortcodes/pages.php`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Úvodní T2: fotografie + příběh/statistiky/CTA
2. Světlý intro blok Proč GRID
3. Mřížka 6 výhod
4. Video/časosběr
5. Finální CTA
6. Footer

## Atomická Divi 5 struktura

Preferovaný stav: T2 rozdělit na Image modul a samostatný textový sloupec. Šest výhod je šest samostatných Blurb/Text modulů v jedné CSS grid Row. Video je samostatný Video/Code modul pouze pro embed.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Úvod: 50/50 / 50/50 / stacked / stacked.
- Výhody: 3×2 / 2×3 / 2×3 / 1×6.
- Statistiky: 3 / 3 / 3 nebo 2+1 / 1.
- Video 16:9 přes celý bezpečný koridor; max-width 960 px.
- CTA centrovaná výjimka bez HUD paddingu.

## Současné chyby, které je nutné odstranit

- Na mobilu stránka přetékala přibližně o 38 px.
- Na portrait tabletu dvojité vnořené paddingy zbytečně zužují text.
- Dělená sekce je ohrožena stejným specificity konfliktem `.et_pb_row.split`.
- Dlouhé DE nadpisy a CTA musí být otestovány bez ručního `<br>`.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Obrázek je na desktopu i landscape bez mezery k hornímu/levému okraji poloviny.
- [ ] Na 768 px je obrázek nahoře a text pod ním.
- [ ] Mřížka výhod má minimální vnitřní šířku karty 220 px.
- [ ] Video nevytváří overflow a drží 16:9.
- [ ] CTA a statistiky zůstanou editovatelné jako samostatné moduly.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

