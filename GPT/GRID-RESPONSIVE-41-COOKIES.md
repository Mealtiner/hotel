# GRID Hotel — Cookies

## Rozsah

- URL: CZ `/cookies/`; EN `/en/cookie-policy/`; DE `/de/cookie-richtlinie/`
- Zdroj/komponenty: `grid_legal` a cookie consent komponenta.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. H1
2. Kapitoly
3. Tabulka cookies
4. Nastavení souhlasu
5. Footer

## Atomická Divi 5 struktura

Article používá stejný long-form kontrakt jako GDPR. Tabulka je sémantická. Ovládání souhlasu je skutečný button/form control, ne odkaz bez role.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Article max 820 px.
- Tabulka full wrapper; lokální scroll pouze pod 641 px, pokud se nevejde.
- Consent controls se zalamují; mobil 1 sloupec.

## Současné chyby, které je nutné odstranit

- Sdílí globální mobilní overflow a dvojité paddingy právních stránek.
- Tabulka názvů/domén může určit min-width dokumentu.
- DE názvy kategorií souhlasu jsou delší.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Tabulka cookies nezvětší document scrollWidth.
- [ ] Consent tlačítka mají 44 px.
- [ ] Text čitelný na 320 px.
- [ ] Stav souhlasu je dostupný klávesnicí.
- [ ] CZ/EN/DE používají stejný layout.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

