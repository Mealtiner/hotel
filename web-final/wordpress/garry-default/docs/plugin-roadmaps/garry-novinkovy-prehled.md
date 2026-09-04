# GARRY Novinkový přehled – roadmap

## Cíl

Převést Elementor widget novinek na univerzální výpis příspěvků.

## Současný stav

- verze 1.2.2, Framework 2.3 a LocalLog;
- funkční Elementor widget; bez Elementoru nevznikne veřejný výstup;
- bez shortcode, bloku a Divi modulu.

## Roadmap

1. Oddělit WP_Query, mapování ACF, perex, datum a markup od Elementor třídy.
2. Přidat shortcode garry_novinkovy_prehled a dynamický blok.
3. Přidat Divi 4/5 modul a ponechat Elementor jako adapter.
4. Validovat post type, taxonomii, počet výsledků a ACF hodnoty; omezit náklady dotazů.
5. Doplnit Elementor minimum/tested range, testovat editor, preview, cache a prázdný stav.

## Akceptace

Výpis novinek funguje bez Elementoru a má stejné filtrování, paging a přístupný markup ve všech adaptérech.

