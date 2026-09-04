# GARRY Foto carousel – roadmap

## Cíl

Z Elementor widgetu vytvořit opakovaně použitelný foto carousel pro čistý WordPress, Divi i Elementor.

## Současný stav

- verze 1.2.0, Framework 2.3 a LocalLog;
- funkční Elementor widget; Elementor je povinný pro hlavní výstup;
- bez shortcode, Gutenberg bloku a Divi modulu; verze Elementoru nejsou deklarované.

## Roadmap

1. Oddělit výběr médií, render HTML, lightbox a assets od Elementor třídy.
2. Přidat shortcode garry_foto_carousel a dynamický blok se stejným media pickerem.
3. Přidat Divi modul pro Divi 4 a 5 se stejnou konfigurací fotografií.
4. Zachovat Elementor widget jako tenký adapter a deklarovat minimální/testovanou verzi Elementoru.
5. Testovat prázdná média, lazy loading, lightbox, reduced motion, mobil, cache a více carouselů.

## Akceptace

Carousel funguje bez Elementoru přes shortcode/blok a v builderech vykreslí stejná média, ovládání a bezpečně escapené popisky.

