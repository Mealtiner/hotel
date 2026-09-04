# GRID Hotel — Kariéra

## Rozsah

- URL: CZ `/kariera/`; EN `/en/career/`; DE `/de/karriere/`
- Zdroj/komponenty: `grid_kariera` nebo `grid_kariera_pozice`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Prázdný stav nebo seznam pozic
3. Detailní metadata pozice
4. CTA e-mail
5. Footer

## Atomická Divi 5 struktura

Obsahový sloupec max 760 px je zarovnaný k levé hraně koridoru. Každá pozice je samostatná karta/Divi Group; seznam nikdy netvoří horizontální grid.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Sloupec max 760 px ve všech režimech.
- Pozice vždy 1 sloupec.
- Metadata desktop/tablet flex-wrap; mobil stacked.
- CTA mobil 100 %.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 65 px.
- Portrait je zbytečně úzký kvůli dvojitému paddingu.
- Dlouhý DE text prázdného stavu a názvy pozic nesmí určovat min-width.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Obsah začíná na bezpečné levé hraně.
- [ ] Prázdný i neprázdný stav se testují samostatně.
- [ ] E-mail CTA se zalomí a zůstane 44 px vysoké.
- [ ] Metadata pozice nepřetékají.
- [ ] Nulový overflow v CZ/EN/DE.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

