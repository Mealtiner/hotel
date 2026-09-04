# GARRY Příspěvková matice – roadmap

## Cíl

Zpřístupnit příspěvkovou mřížku mimo Elementor a udržet výkon při větším obsahu.

## Současný stav

- verze 1.2.2, Framework 2.3 a LocalLog;
- Elementor widget; bez shortcode, bloku a Divi modulu;
- historicky riziko neomezeného posts_per_page dotazu.

## Roadmap

1. Vytvořit sdílený query/render service s pevnými limity a stránkováním.
2. Přidat shortcode garry_prispevkova_matice a dynamický blok.
3. Přidat Divi 4/5 modul a Elementor adapter se stejnými controls.
4. Omezit rows=0 bezpečným maximem nebo explicitním stránkováním.
5. Testovat ACF mapping, taxonomie, cache, nulové výsledky a responzivní mřížku.

## Akceptace

Mřížka má shodný výstup ve WP, Divi a Elementoru a žádná konfigurace nespustí nekontrolovaný dotaz.

