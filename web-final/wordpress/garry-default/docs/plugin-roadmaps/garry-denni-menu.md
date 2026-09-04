# GARRY Denní menu – roadmap

## Cíl

Zpřístupnit týdenní jídelníček konzistentně přes WordPress, Gutenberg, Divi a Elementor.

## Současný stav

- verze 1.4.1, Framework 2.3 a LocalLog;
- funkční shortcode grid_menu_tydne;
- vícejazyčná data a administrace personálu; bez builder modulů.

## Cílové plochy

| Plocha | Roadmapa |
|---|---|
| WordPress | zachovat shortcode a přidat dynamický blok |
| Divi 4 / 5 | modul denního menu s týdnem, jazykem a stylem |
| Elementor | widget se stejnými parametry a responzivním stylem |

## Roadmap

1. Vytvořit jeden render service pro týden, jazyk, prázdné dny a cache.
2. Zachovat kompatibilitu shortcodu; formalizovat atributy a sanitizaci.
3. Přidat Gutenberg blok, Divi modul a Elementor widget pouze jako adaptéry render service.
4. Ověřit timezone, přelom roku, nevyplněné dny, překlady a nižší staff capability.
5. Doplnit builder verze do manifestu a regression testy pro editor, preview a frontend.

## Akceptace

Všechny čtyři výstupní plochy ukazují pro shodné parametry stejný jídelníček a neodhalí administrativní data.

