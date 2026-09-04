# GARRY Kategorie pokojů – roadmap

## Cíl

Zpřístupnit karty a srovnávací tabulky pokojů přes jeden datový a renderovací kontrakt.

## Současný stav

- verze 1.3.0, Framework 2.3 a LocalLog;
- shortcody grid_rooms_cards a grid_rooms_table;
- bez nativního Gutenberg bloku, Divi modulu a Elementor widgetu.

## Roadmap

1. Oddělit query dat, lokalizaci a render karet/tabulky.
2. Formalizovat shortcode atributy, filtr kategorií, jazyk a cache.
3. Přidat dva Gutenberg bloky, dva Divi moduly a dva Elementor widgety: Karty pokojů a Tabulka pokojů.
4. Testovat vícejazyčný obsah, prázdné hodnoty, tabulkovou přístupnost, mobilní variantu a staff capability.
5. Omezit query rozsah, invalidovat cache po změně pokojů a zapsat builder verze do manifestu.

## Akceptace

Karty i tabulka používají shodná data a bezpečný render ve WP, Divi a Elementoru.

