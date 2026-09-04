# GRID Hotel — Detail zážitku — Škola smyku / Polygon Brno

## Rozsah

- URL: kanonický CPT slug, očekávaně `/zazitky/skola-smyku-polygon-brno/`
- Datový obsah: úrovně Compact až Dynamic, popis, CTA, externí vazby
- Zdroj: CPT šablona `single-grid_experience.php` + `gridhotel-core`
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).

## Povinná atomická struktura

1. Header.
2. Detail hero: samostatné pozadí/Image, overlay, kicker, H1, lead a CTA.
3. Hlavní detailový grid:
   - galerie nebo hlavní média;
   - textový obsah;
   - parametry;
   - cena/dostupnost;
   - CTA;
   - aside se souhrnem pouze na desktopu vedle obsahu.
4. Doplňující vybavení/podmínky.
5. Související zážitku.
6. Finální CTA.
7. Footer.

Každý nadpis, text, obrázek a CTA musí být samostatně editovatelný. Dynamická data smějí být jedna komponenta pouze tehdy, když pocházejí z CPT/ACF; komponenta nesmí vykreslit header, footer ani sousední statické sekce.

## Přesná geometrie

| Režim | Koridor | Detailový grid | Parametry | CTA |
|---|---|---|---|---|
| 1600 | x 290–1340 | hlavní obsah minmax(0,1fr) + aside 340 px; gap 24–26 px | 3 sloupce | řádek/wrap |
| 1024 | x 284–764 | 1 sloupec; aside pod obsahem | 2 sloupce | wrap |
| 768 | x 245–737 | 1 sloupec | 2 sloupce, pokud buňka ≥220 px, jinak 1 | wrap |
| 390 | x 20–370 | 1 sloupec | 1 sloupec | 100 % |

Na 1024 px je pravý rail viditelný. Detail nesmí zůstat v současném poměru přibližně 302 + 340 px.

## Média

- hlavní obrázek: `aspect-ratio: 4/3` nebo schválený detailový poměr;
- galerie thumbnails: minimálně 96 × 72 px, bez horizontálního scrollu;
- `object-fit:cover`; konkrétní fokus přes `object-position`;
- iframe/video `width:100%; max-width:100%; aspect-ratio:16/9`;
- lazy loading mimo první viditelný obrázek.

## Typografie a data

- H1 respektuje škálu globálního dokumentu a `max-inline-size:14ch`;
- body max 68ch;
- každý grid/flex child `min-width:0`;
- e-maily, URL a dlouhé hodnoty `overflow-wrap:anywhere`;
- číselné parametry mohou použít `white-space:nowrap` pouze uvnitř buňky, která sama smí přejít na další řádek;
- žádná karta nemá pevnou výšku podle českého textu.

## Specifický stresový test

Seznam úrovní nesmí být jeden nowrap řetězec; na mobilu je vertikální.

## Současný auditní stav

- Desktopová šablona je strukturálně použitelná.
- Na 1024 px se detail musí skládat dříve kvůli povinně viditelnému railu.
- Na portrait je základní skládání použitelné, ale dvojité paddingy příliš zužují obsah.
- Na mobilu globální `--hud-pad` způsobuje overflow a příliš úzký obsah.
- Oprava musí být ve sdílené šabloně, nikoli per-URL CSS.

## Akceptační checklist

- [ ] 1600: aside přesně 340 px a celý grid uvnitř x=290–1340.
- [ ] 1024: rail viditelný, aside pod obsahem, vše uvnitř x=284–764.
- [ ] 768: rail skrytý, HUD viditelný, vše uvnitř x=245–737.
- [ ] 390: HUD bez rezervované šířky, obsah x=20–370.
- [ ] 320–1600: nulový document overflow.
- [ ] Nejdelší název a nejdelší lokalizace neporuší layout.
- [ ] Galerie, empty stav, cena, nedostupnost a chybový stav ověřeny.
- [ ] Po opravě zkontrolovány všechny stránky používající stejnou šablonu.

