# GRID Hotel — Rezervace

## Rozsah

- URL: CZ `/rezervace/`; jazykové varianty musí použít stejnou šablonu a lokalizovaná data.
- Zdroj/komponenty: `grid_rezervace` a externí/placeholder rezervační integrace.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Vyhledávací ovládání
3. Výsledky
4. Souhrn
5. Stavy bez výsledků/chyba
6. Footer

## Atomická Divi 5 struktura

Ovládání je samostatný grid. Výsledek je karta Image + informace + sazba + cena/CTA. Souhrn je aside pouze na desktopu; na tabletech jde pod výsledky.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Controls: 5 / 3+2 / 2 nebo 1 podle minima / 1.
- Body: výsledky+aside / stacked / stacked / stacked.
- Result card: desktop 34% image + obsah; landscape/portrait může být stacked při nedostatku 220 px; mobil stacked.
- CTA mobil 100 %.

## Současné chyby, které je nutné odstranit

- Na 1024 zůstává výsledek zhruba 280 + 98 px, text je nepoužitelně úzký.
- Na 768 se skládá, ale dvojité paddingy prostor dál zužují.
- Mobilní overflow přibližně +38 px.
- Dlouhé ceny, termíny a DE labely mohou rozšířit grid.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Na 1024 je rail viditelný a celé rozhraní v x=284–764.
- [ ] Souhrn je na 1024 pod výsledky.
- [ ] Výsledková karta má při úzkém prostoru obrázek nahoře.
- [ ] Loading/error/empty stavy drží stejnou šířku.
- [ ] Žádný externí iframe/widget nepřeteče svůj wrapper.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

