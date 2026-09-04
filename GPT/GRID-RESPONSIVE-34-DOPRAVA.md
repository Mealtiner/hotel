# GRID Hotel — Jak se k nám dostanete

## Rozsah

- URL: CZ `/jak-se-k-nam-dostanete/`; EN `/en/getting-here/`; DE `/de/wegbeschreibung/`
- Zdroj/komponenty: `grid_doprava`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Mapa
3. Příjezd autem
4. Veřejná doprava/shuttle
5. Parkování
6. Letiště
7. CTA
8. Footer

## Atomická Divi 5 struktura

Mapa je samostatný Map/Code modul v koridoru. Trasy jsou samostatné karty v `.dp-cols`; letiště v `.dp-air-grid`. Každá karta má vlastní heading, text a odkaz.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Mapa vždy 100 % koridoru.
- Trasy: 2 / 2 / 1 / 1.
- Letiště: 2 / 2 / 1 / 1.
- CTA řádek se zalomí; na mobilu tlačítka 100 %.

## Současné chyby, které je nutné odstranit

- Na 390 px měl `.wrap` prakticky 2 px a mapa kolabovala na přibližně 2 px.
- Mobilní overflow CZ přibližně +63 px; DE přibližně +133 px.
- Dětské route karty zůstávají širší než rodič.
- Tlačítka a dlouhé adresy překračují pravý okraj.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Mapa má na mobilu šířku 350 px a min-height 260 px.
- [ ] Portrait i mobil skládají všechny trasy do jednoho sloupce.
- [ ] Dlouhá DE adresa se zalomí bez změny viewportu.
- [ ] Externí mapový embed má `max-width:100%`.
- [ ] CTA mají 44 px výšku a nevytvářejí horizontální scroll.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

