# GARRY Typografie – roadmap

## Cíl

Zachovat globální správu typografie a dodat bezpečné integrační plochy pro WordPress, Divi a Elementor.

## Současný stav

- verze 1.1.3, Framework 2.3 a LocalLog;
- funguje ve WordPressu; CSS konfigurace obsahuje Divi selektory a výslovnou podporu Divi 5;
- bez shortcode, bloku, Divi modulu a Elementor widgetu.

## Roadmap

1. Oddělit globální tokeny typografie od CSS selektorů konkrétního builderu.
2. Přidat pouze bezpečný shortcode a Gutenberg blok garry_typography_preview pro editorový náhled, nikoli druhý globální style engine.
3. Přidat Divi 4/5 a Elementor preview modul/widget pro výběr tokenů; globální změny zůstávají v administraci pluginu.
4. Přidat Elementor selektory a testovat kolizi s theme style, custom CSS, cache a editor preview.
5. Testovat font fallback, WCAG kontrast, responzivní velikosti, lokalizaci a výkon fontů.

## Akceptace

Globální typografie zůstane jedním vlastníkem, zatímco WP, Divi a Elementor získají bezpečné preview/inserční komponenty bez duplicitního globálního CSS.

