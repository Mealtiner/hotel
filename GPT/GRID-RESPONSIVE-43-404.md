# GRID Hotel — 404

## Rozsah

- URL: Jakákoli neexistující URL ve všech jazykových kontextech.
- Zdroj/komponenty: `grid-divi5-child/404.php`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Globální header
2. Chybový kód
3. Nadpis a vysvětlení
4. Primární návrat
5. Sekundární kontakt/rezervace
6. Footer

## Atomická Divi 5 struktura

Jednoduchá sémantická main sekce. Žádný Divi shortcode není nutný; layout však používá stejné tokeny a koridor.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Desktop/landscape obsah v koridoru.
- Portrait bez railu, s HUD rezervací.
- Mobil bez HUD rezervace, CTA stacked.
- Min-height pouze tak, aby footer nebyl uprostřed viewportu; textová výška zůstává auto.

## Současné chyby, které je nutné odstranit

- 404 musí být ověřena samostatně, protože WordPress/Divi může načíst odlišné body třídy.
- Dlouhá lokalizovaná URL nesmí být vypsána v nowrap režimu.
- Fixed overlay může zakrýt středový obsah, pokud se stránka centruje vůči viewportu místo koridoru.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] HTTP status 404 zůstane zachován.
- [ ] Nulový overflow na 320–1600 px.
- [ ] CTA fungují v CZ/EN/DE.
- [ ] Header/footer odpovídají webu.
- [ ] Focus přejde na hlavní chybový nadpis nebo první akci.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

