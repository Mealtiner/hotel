# GRID Hotel — Kontakt

## Rozsah

- URL: CZ `/kontakt/`; EN `/en/contact/`; DE `/de/kontakt/`
- Zdroj/komponenty: `grid_kontakt`, `grid_paticka_kontakt`, případně Fluent Forms komponenta.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Kontaktní informace
3. Kontaktní formulář
4. Mapa
5. Doplňující údaje
6. Footer

## Atomická Divi 5 struktura

Desktopový top blok je grid 1:1. Levá část obsahuje samostatné kontaktní bloky; pravá formulář. Mapa je samostatný Row pod nimi. Formulář nesmí mít šířku nezávislou na rodiči.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Desktop info:form 1:1, formulář 2 sloupce.
- Landscape: info nad formulářem; formulář 2 sloupce.
- Portrait: info nad formulářem; formulář 2 sloupce jen pokud pole ≥220 px.
- Mobil: vše 1 sloupec; tlačítko 100 %.
- Mapa min-height 360/320/300/260 px.

## Současné chyby, které je nutné odstranit

- Na 768 px má rodič kontaktu přibližně 292 px, ale formulář 350 px a uniká ven.
- Na 390 px je formulář přibližně x=142–491, tedy overflow +111 px.
- Hlavní `.wrap` může mít kvůli `--hud-pad` prakticky 2 px.
- E-mailové adresy potřebují `overflow-wrap:anywhere`.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Každý field má `width:100%; min-width:0`.
- [ ] Form grid přejde na 1 sloupec dříve, než pole klesne pod 220 px.
- [ ] Na 390 px je celý formulář mezi x=20–370.
- [ ] Mapa nepřetéká a zůstane ovladatelná.
- [ ] Validace, captcha i úspěšná zpráva mají nulový layout shift mimo formulář.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

