# GARRY Informační bublina – roadmap

## Cíl

Převést oznámení na bezpečnou, přístupnou a builderově nezávislou komponentu.

## Současný stav

- verze 1.3.1, Framework 2.3 a LocalLog;
- funguje ve WordPressu; volitelně načítá Divi barevné palety;
- bez shortcode, bloku, Divi modulu a Elementor widgetu.

## Roadmap

1. Oddělit data oznámení, cílení stránek, render a preview od administrace.
2. Přidat shortcode garry_informacni_bublina a dynamický blok.
3. Přidat Divi 4/5 modul a Elementor widget se stejnými barvami, viditelností a CTA.
4. Zachovat Divi palety jen jako volitelný zdroj barev; vždy umožnit vlastní bezpečně validované HEX hodnoty.
5. Opravit preview proti self-XSS: žádné neescapované HTML ani vzdálené obrázky.
6. Testovat close button, focus, aria-live, mobil, cache a cílení na stránku.

## Akceptace

Stejné oznámení lze vložit shortcodem, blokem, Divi modulem nebo Elementor widgetem; žádná varianta neobchází sanitizaci.

