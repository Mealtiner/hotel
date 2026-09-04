# GRID Hotel — Firemní akce a svatby

## Rozsah

- URL: CZ `/firemni-akce-svatby/`; EN `/en/corporate-events-weddings/`; DE `/de/firmenevents-hochzeiten/`
- Zdroj/komponenty: `grid_firemni`, sdílený T2/T7 a formulář.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Úvodní T7
2. Statistiky/parametry
3. Nabídka služeb
4. Fotografie
5. Poptávkový formulář
6. CTA
7. Footer

## Atomická Divi 5 struktura

T7 musí být skutečný Divi split se samostatným Image modulem. Statistiky jsou samostatné moduly. Formulář je vlastní komponenta pod textem, nikoli absolutně umístěný vedle fotografie.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- T7: 50/50 / 50/50 / stacked / stacked.
- Statistiky: 4 / 2×2 / 2×2 / 1 nebo 2 podle textu.
- Služby: 3 / 2 / 2 / 1.
- Formulář: 2 / 2 / 2 při min.220 / 1.

## Současné chyby, které je nutné odstranit

- Mobilní CZ přetečení přibližně +195 px; DE až +272 px.
- Split se na 768/390 nemusí složit kvůli specificity konfliktu.
- Dlouhé německé názvy a labely odhalují chybějící `min-width:0`.
- Formulář a statistiky dědí globální HUD padding.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Na portrait i mobilu je fotografie vždy nad obsahem.
- [ ] DE verze má nulový overflow na 320/390.
- [ ] Form fields mají plnou šířku rodiče.
- [ ] Statistiky se zalamují bez ořezu jednotek.
- [ ] T7 obraz je full bleed, text v koridoru.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

