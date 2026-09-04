# GRID Hotel — Úvodní stránka

## Rozsah

- URL: CZ `/`; EN `/en/`; DE `/de/`
- Zdroj/komponenty: Divi obsah + `gridhotel-components`: `grid_hero`, `grid_booking`, `grid_vstupy`, `grid_pribeh`, `grid_rooms`, `grid_zazitky`, `grid_gastro`, `grid_season`, `grid_firemni`, `grid_reference`, `grid_final`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Globální header
2. Hero START
3. Rezervační lišta
4. T1 vstupy
5. T2 příběh
6. T3 pokoje
7. T4 zážitky
8. T5 gastronomie
9. T6 sezóna
10. T7 firemní akce
11. T8 recenze/partneři
12. Finální CTA
13. Footer

## Atomická Divi 5 struktura

Každá položka musí být samostatná Divi Section. Uvnitř použít samostatné moduly pro kicker, nadpis, text, obrázek a CTA. Dynamická rezervační pole, data pokojů, sezóna a recenze mohou zůstat komponentou; nesmí však obalit sousední statické sekce.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Hero full bleed ve všech režimech; text v koridoru.
- T1: 4×1 / 2×2 / 2×2 / 1×4.
- T2 a T7: 50/50 / 50/50 / stacked / stacked.
- T3: 2×2 / 2×2 / 2×2 / 1×4.
- T4: 3×2 / 2×3 / 2×3 / 1×6.
- T5: 3 svislé / 3 horizontální / 3 horizontální / 3 svislé.
- T6: 3:2 / stacked / stacked / stacked.
- T8: 3 / 1 / 1 / 1; partneři 4 / 2 / 2 / 2.

## Současné chyby, které je nutné odstranit

- Na 1024 px je `.track-progress` skrytý, ale musí být viditelný.
- T1 končí přibližně na x=968 místo x=764.
- T4 je na desktopu mimo jednotný koridor (cca x=159–1427).
- T2/T7 zůstávají na 768/390 ve dvou sloupcích kvůli specificitě.
- T5 zůstává na tabletech i mobilu třísloupcový; na 390 px mají sloupce přibližně 15 px.
- Mobilní T1/T3/T6/T8 mají kvůli `--hud-pad` použitelnou šířku jen kolem 77 px.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Všechny sekce sdílejí x=290–1340 na 1600 a x=284–764 na 1024.
- [ ] Rail je viditelný na 1024 a nezasahuje do obsahu.
- [ ] Počet sloupců odpovídá matici bez výjimky v CZ/EN/DE.
- [ ] Mobil 390 i 320 má nulový horizontální overflow.
- [ ] T2/T7 fotografie jsou full bleed, ale text nikdy neleží pod HUD/rail.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

