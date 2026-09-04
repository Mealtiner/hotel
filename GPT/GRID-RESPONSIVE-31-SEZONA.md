# GRID Hotel — Sezóna

## Rozsah

- URL: CZ `/sezona/`; EN `/en/season/`; DE `/de/saison/`
- Zdroj/komponenty: `grid_season`, plugin `garry-sezona-cekaci-list`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro T6
2. Mřížka/chronologie akcí
3. Rezervační a čekací list panel
4. Navazující program
5. CTA
6. Footer

## Atomická Divi 5 struktura

Akce jsou dynamické karty/řádky z Core API. Čekací list je samostatná formulářová komponenta. Obě části sdílí T6 wrapper, nikoli vnořené absolutní pozice.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Desktop 3:2.
- Tablet landscape stacked; seznam akcí nad formulářem.
- Tablet portrait stacked.
- Mobil stacked, formulář 1 sloupec.
- Samostatná mřížka akcí 3 / 2 / 2 / 1 podle varianty obsahu.

## Současné chyby, které je nutné odstranit

- Na 390 px stránka přetékala přibližně o 187 px.
- Na 1024 se obsah sice skládá, ale nepočítá s povinně viditelným railem.
- Form controls nesmí dědit pevné desktopové šířky.
- Dlouhé DE názvy závodů a select options vyžadují `min-width:0`.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Rail je na 1024 viditelný a seznam/formulář končí na x=764.
- [ ] Formulář je na mobilu přesně 1 sloupec.
- [ ] Select, datum a CTA nepřetékají.
- [ ] Události nemají pevnou výšku a zvládnou dvouřádkový název.
- [ ] Odesílací a chybové stavy nemění šířku panelu.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

