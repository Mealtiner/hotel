# GARRY Boční posuvník – roadmap

## Cíl

Převést boční navigaci sekcí na builderově nezávislý navigační renderer s plnohodnotným WP, Divi a Elementor použitím.

## Současný stav

- verze 1.3.0, Embedded Framework 2.3 a Framework LocalLog;
- automatická navigace, technický shortcode grid_tracknav;
- dílčí podpora kotev uložených v Divi 5 blocích; bez nativního Divi modulu a Elementor widgetu.

## Cílové plochy

| Plocha | Roadmapa |
|---|---|
| WordPress | skutečný shortcode garry_tracknav a dynamický blok |
| Divi 4 / 5 | nativní modul s ručními položkami i bezpečnou detekcí kotev |
| Elementor | widget se stejnými položkami, offsetem a styly |

## Roadmap

1. Oddělit sběr sekcí, validaci kotev a render navigace od administrace.
2. Nahradit placeholder shortcode skutečným rendererem a přidat blok.
3. Zavést Divi modul; testovat ručně definované kotvy, Divi 4 obsah i Divi 5 uložené bloky.
4. Zavést Elementor widget nad stejným rendererem.
5. Doplnit přístupnost: nav landmark, focus, klávesnice, reduced motion a správný offset sticky hlavičky.
6. Zapsat minimální/testované verze builderů do manifestu a logovat jen změny kompatibility.

## Akceptace

Stejná navigace se vykreslí z WP shortcodu, bloku, Divi modulu i Elementor widgetu bez závislosti na jiném GARRY pluginu.

