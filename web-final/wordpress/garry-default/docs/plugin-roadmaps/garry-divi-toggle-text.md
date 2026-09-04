# GARRY Toggle Text – roadmap

## Cíl

Nahradit závislost na ručně přidané CSS třídě univerzálním komponentovým renderem pro obsah WordPressu, Divi a Elementoru.

## Současný stav

- verze 1.2.1, Framework 2.3 a LocalLog;
- primárně CSS třídy v Divi Text modulu;
- bez shortcode, bloku, Divi modulu a Elementor widgetu; Divi verze nejsou ověřené.

## Roadmap

1. Oddělit sanitizované textové body, limit, popisky a styl od inline JavaScriptu.
2. Přidat shortcode garry_toggle_text a dynamický Gutenberg blok.
3. Přidat nativní Divi modul pro Divi 4 i 5; zachovat staré CSS třídy jako compatibility mode.
4. Přidat Elementor widget se stejnými ovládacími prvky.
5. Ověřit klávesnici, aria-expanded, focus, reduced motion, cache a více instancí na jedné stránce.
6. Odstranit případné duplicitní inline assety; enqueue pouze tam, kde je komponenta použita.

## Akceptace

Komponenta má stejné chování ve shortcodu, bloku, Divi modulu a Elementor widgetu; staré Divi CSS třídy zůstávají po jednu major verzi podporované.

