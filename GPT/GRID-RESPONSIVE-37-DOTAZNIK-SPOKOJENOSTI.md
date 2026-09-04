# GRID Hotel — Dotazník spokojenosti

## Rozsah

- URL: CZ `/dotaznik-spokojenosti/`; EN `/en/satisfaction-questionnaire/`; DE `/de/zufriedenheitsfragebogen/`
- Zdroj/komponenty: `grid_form_dotaznik` nebo Fluent Forms.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Identifikační pole
3. Hodnoticí pole
4. Komentář
5. Souhlasy
6. Odeslání
7. Footer

## Atomická Divi 5 struktura

Form wrapper max 820 px v koridoru. Každé pole zůstává samostatně konfigurovatelné ve formulářovém pluginu; CSS pouze rozkládá existující sémantický markup.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Desktop: 2 sloupce.
- Landscape: 2 sloupce při ≥220 px na pole.
- Portrait: 2 sloupce pouze při splnění minima, jinak 1.
- Mobil: 1 sloupec.
- Textarea/souhlas/submit vždy přes celý řádek.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 86 px.
- Globální HUD padding posouvá formulář doprava.
- Nativní select/radio prvky mohou mít vlastní min-width.
- DE labely vyžadují výšku podle obsahu.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Na 390 px všechna pole mezi x=20–370.
- [ ] Žádný label není oříznutý.
- [ ] Radio/checkbox skupiny se zalamují vertikálně.
- [ ] Chyba pod polem nezmění šířku gridu.
- [ ] Klávesnicí dosažitelné pořadí odpovídá vizuálnímu.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

