# GARRY Hero křivka – roadmap

## Cíl

Poskytnout animovanou hero křivku jako samostatnou komponentu pro nativní WordPress i oba buildery.

## Současný stav

- verze 1.1.0, Framework 2.3 a LocalLog;
- frontend bez přímé závislosti na builderu;
- bez shortcode, Gutenberg bloku, Divi modulu a Elementor widgetu.

## Roadmap

1. Definovat renderer SVG/CSS a bezpečná nastavení rychlosti, tloušťky, barvy a zobrazení.
2. Přidat shortcode garry_hero_krivka a dynamický blok.
3. Přidat Divi 4/5 modul a Elementor widget nad stejným rendererem.
4. Ověřit reduced motion, kontrast, responzivní výšku, z-index a konflikt s hero obrázky.
5. Načítat assety jen při použití komponenty a logovat změnu kompatibility.

## Akceptace

Křivka funguje v čistém WordPressu, Divi i Elementoru s identickým přístupným fallbackem při vypnuté animaci.

