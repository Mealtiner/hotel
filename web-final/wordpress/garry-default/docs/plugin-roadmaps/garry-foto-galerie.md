# GARRY Foto galerie – roadmap

## Cíl

Z Elementor galerie vytvořit univerzální galerii s jednotným rendererem a progresivním načítáním.

## Současný stav

- verze 1.2.0, Framework 2.3 a LocalLog;
- Elementor widget s mřížkou, lightboxem a postupným odkrýváním;
- bez shortcode, bloku a Divi modulu; Elementor verze není deklarována.

## Roadmap

1. Oddělit zdroj médií, layout a stránkování od Elementor widgetu.
2. Přidat shortcode garry_foto_galerie a dynamický blok.
3. Přidat Divi modul pro Divi 4 a 5; modul nesmí kopírovat renderer.
4. Zachovat Elementor widget jako adapter a doplnit tested range.
5. Ověřit media permissions, alt texty, keyboard lightbox, lazy load, cache a velké galerie.

## Akceptace

Galerie má rovnocenný WordPress, Divi a Elementor výstup a nevyžaduje Elementor, pokud je použita shortcode nebo bloková varianta.

