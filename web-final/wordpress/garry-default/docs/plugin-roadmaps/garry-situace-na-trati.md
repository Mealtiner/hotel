# GARRY Situace na trati – roadmap

## Cíl

Poskytnout plovoucí widget počasí a stavu trati jako plně funkční WP, Divi a Elementor komponentu.

## Současný stav

- verze 1.3.0, Framework 2.3 a LocalLog;
- Open-Meteo integrace, technický placeholder shortcode grid_telemetry;
- CSS workaround pro Divi kontejnery; bez nativních builder modulů.

## Roadmap

1. Oddělit HTTP klient, cache, fallback dat, odhad povrchu a renderer.
2. Nahradit placeholder shortcodem garry_situace_na_trati a přidat dynamický blok.
3. Přidat Divi 4/5 modul a Elementor widget se stejnými parametry GPS, zobrazených dat a intervalu.
4. Zachovat bezpečné timeouty, SSL ověření, transient cache, rate limit a výstup při nedostupném API.
5. Testovat Divi transform kontejnery, mobil, cache, Open-Meteo failure a přístupný textový fallback.

## Akceptace

Widget je použitelný bez builderu i v obou builderech a při výpadku externího API neselže celá stránka.

