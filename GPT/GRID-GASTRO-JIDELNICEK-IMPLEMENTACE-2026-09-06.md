# GRID Hotel — gastronomie: tři jídelní lístky

## Nasazené řešení

Shortcode `[grid_gastro]` na stránce `/gastronomie/` nyní vykresluje tři samostatné panely v jedné sekci `#jidelnicek`:

1. `#jidelnicek-hotel` — **RESTAURACE · TÝDENNÍ MENU** / *JÍDELNÍČEK TOHOTO TÝDNE*;
2. `#jidelnicek-paddock` — **PADDOCK RESTAURANT · TÝDENNÍ MENU** / *JÍDELNÍČEK TOHOTO TÝDNE*;
3. `#jidelnicek-bar` — **HOTEL BAR · NÁPOJOVÉ MENU** / *NÁPOJOVÝ LÍSTEK*.

V horní části je plně přístupný filtr: **Ukázat vše**, Restaurace, PADDOCK Restaurant a Hotel bar. Aktivní tlačítko má `aria-pressed`; ostatní panely jsou při filtrování opravdu skryté pomocí atributu `hidden`. Přímé kotvy např. `/gastronomie/#jidelnicek-paddock` po načtení automaticky zobrazí správný panel.

Týdenní nabídky jsou načítány existujícím shortcodem pluginu GARRY Denní menu:

```text
[grid_menu_tydne provoz="…" typ="cely"]
[grid_napojovy_listek provoz="…"]
```

Každý provoz se tedy dá dál editovat výhradně v jeho stávajícím pluginu a nevzniká kopie dat v Divi.

## Administrace provozu Gastro

V editaci každého záznamu **Gastronomie** jsou nová pole:

- **Provoz jídelníčku** — vybere odpovídající provoz z GARRY Denní menu;
- **Odkaz na jídelní lístek** — volba *Kotva jídelníčku* nebo *Externí odkaz / PDF*;
- **URL externího odkazu / PDF** — zobrazí se jen při druhé volbě.

Je nutné jednou otevřít všechny tři provozy a nastavit přesný provoz jídelníčku. Pokud je volba prázdná, systém se pokusí rozumně mapovat názvy `Hotelová restaurace`, `PADDOCK Restaurant` a `GRID Club`; explicitní výběr je ale závazný a doporučený.

CTA na každé kartě zní **Zobrazit jídelní lístek**. V režimu kotvy vede na filtrovaný panel. V režimu externího odkazu vede přímo na zadaný soubor nebo web.

## Divi

Zachovejte na stránce modul/shortcode `[grid_gastro]`. Výstup je plně Divi-kompatibilní: pro ručně skládáné alternativy poskytuje GARRY Denní menu nadále vlastní Divi 5 moduly pro denní menu i nápojový lístek. Nedoplňujte stejná menu ručně do textových modulů — zdrojem dat zůstává plugin jídelníčku.

## Změněné zdroje

- `gridhotel-components/inc/shortcodes/gastro.php`
- `gridhotel-components/inc/assets.php`
- `gridhotel-components/inc/shortcode-registry.php`
- `gridhotel-components/assets/components.js`
- `grid-divi5-child/style.css`
- `gridhotel-core/acf-json/group_grid_cpt_gastro.json`
- `gridhotel-core/inc/acf.php`
- `gridhotel-core/inc/data-api.php`
- `garry-denni-menu/garry-denni-menu.php`

Stejně je upravena local instalace, `web-final` i zdrojový balíček `GRID/new`.

## Důležitá oprava chování

GARRY Denní menu dříve při prázdném jednom denním menu vložil CSS, které skrylo celý prvek `#jidelnicek`. To bylo v konfliktu se třemi nezávislými panely. Prázdný provoz nyní vrátí pouze prázdný výstup a jeho panel ukáže srozumitelný fallback; ostatní dva panely zůstanou viditelné.
