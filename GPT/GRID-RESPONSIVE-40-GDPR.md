# GRID Hotel — Ochrana osobních údajů

## Rozsah

- URL: CZ `/ochrana-osobnich-udaju-gdpr/`; EN `/en/statement-for-processing-of-personal-data/`; DE `/de/erklaerung-zur-verarbeitung-von-personenbezogenen-daten/`
- Zdroj/komponenty: `grid_gdpr` nebo `grid_legal`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. H1 + datum
2. Kapitoly H2/H3
3. Odstavce/seznamy
4. Kontaktní a externí odkazy
5. Footer

## Atomická Divi 5 struktura

Jeden sémantický article max 760–820 px, ale nadpisy a odstavce jsou samostatně editovatelné Divi moduly/Repeaters. Nepoužívat jednu obří textovou HTML stránku, pokud je obsah spravovaný v Divi.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Article max 820 px; vlevo v koridoru.
- Body max 68ch.
- Tabulky v lokálním responsive wrapperu.
- Na mobilu padding 20 px a všechna URL zalamovat.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 73 px.
- Dlouhé URL a DE složeniny mohou overflow zesílit.
- Dvojité vnořené paddingy zužují portrait.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Nulový dokumentový overflow; pouze tabulka smí mít lokální scroll.
- [ ] Heading hierarchy H1→H2→H3.
- [ ] Externí URL `overflow-wrap:anywhere`.
- [ ] Focus anchor není zakryt headerem.
- [ ] Čitelnost při 200% zoomu.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

