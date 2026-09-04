# GRID Hotel — Přehled zážitků

## Rozsah

- URL: CZ `/zazitky/`; EN `/en/experiences/`; DE `/de/erlebnisse/`
- Zdroj/komponenty: `grid_zazitky` + `grid_poukazy`; CPT `grid_experience`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro T4
2. Mřížka šesti zážitků
3. Poukazy/cenové karty
4. Formulář poukazu
5. CTA
6. Footer

## Atomická Divi 5 struktura

Šest zážitků musí být šest samostatně editovatelných karet nebo jedna dynamická CPT komponenta s identickým markupem. Poukazový formulář je oddělená komponenta; nesmí být součástí mřížky zážitků.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Zážitky: 3×2 / 2×3 / 2×3 / 1×6.
- Poukazy: 3 / skupiny po jednom sloupci v úzkém koridoru / skupiny pod sebou / 1.
- Formulář: 2 / 2 při poli ≥220 px / 2 při poli ≥220 px / 1.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 141 px.
- T4 není na desktopu zarovnaná do společného koridoru.
- Na portrait je T4 aktuálně 1 sloupec místo 2×3.
- Poukazové karty mají na 1024 a 768 přibližně 150/110 px, což je pod minimem.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Mřížka začíná/končí přesně na hranách koridoru.
- [ ] Při 1024 i 768 jsou dvě karty v řádku.
- [ ] Formulář nikdy nepřekročí rodiče.
- [ ] Poukazové CTA jsou minimálně 44 px vysoké.
- [ ] CZ/EN/DE názvy zážitků nezpůsobí overflow.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

