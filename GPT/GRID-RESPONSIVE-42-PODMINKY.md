# GRID Hotel — Obchodní, ubytovací a reklamační podmínky

## Rozsah

- URL: CZ `/ubytovaci-a-reklamacni-rad/` nebo kanonický slug nastavený ve WordPressu; EN `/en/terms-and-conditions/`; DE `/de/allgemeine-geschaeftsbedingungen/`.
- Zdroj/komponenty: `grid_podminky` nebo `grid_legal`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. H1 + účinnost
2. Číslované kapitoly
3. Seznamy
4. Tabulky/ceny
5. Kontakty
6. Footer

## Atomická Divi 5 struktura

Stejný article kontrakt jako GDPR. Číslování musí být skutečný uspořádaný seznam nebo konzistentní headings, ne ručně odsazené mezery.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Max 820 px, body 68ch.
- Číslované seznamy 1 sloupec.
- Tabulky v lokálním wrapperu.
- Mobil padding 20 px.

## Současné chyby, které je nutné odstranit

- Právní šablony na mobilu přetékají přibližně 38–73 px podle obsahu.
- Německý slug i nadpis jsou extrémně dlouhé; breadcrumbs/heading nesmí mít nowrap.
- Ceny a data nesmí být jediným nedělitelným řetězcem.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] DE H1 se zalomí bez horizontálního posunu.
- [ ] Seznamy zachovají marker a 20 px vnější inset.
- [ ] Tabulky nepřenášejí overflow na dokument.
- [ ] Kotvy kapitol respektují fixed header.
- [ ] Stejná typografie jako ostatní právní stránky.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

