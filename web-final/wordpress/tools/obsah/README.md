# Obsahové generátory

Skripty, kterými vznikl obsah převzatý z profilu hotelu na Booking.com
(<https://www.booking.com/hotel/cz/grid.cs.html>). Uloženy proto, aby šlo
dohledat, odkud která věta a která položka výbavy pochází, a aby se dal
převod zopakovat, kdyby se data na Bookingu změnila.

Vlastní obsah žije v databázi (volba `garry_pokoje`, termy taxonomie
`grid_room_cat`, Divi bloky stránek). Tyto soubory jsou zdroj, ne runtime.

## Kategorie pokojů — 5 kategorií podle Bookingu

| soubor | co dělá |
|---|---|
| `pokoje-generator.py` | sestaví `pokoje-data.json` — 5 pokojů, 18 štítků, 17 řádků srovnání, CZ/EN/DE |
| `pokoje-data.json` | vygenerovaná data (podoba volby `garry_pokoje`) |
| `pokoje-aplikuj.php` | zapíše volbu, přejmenuje a rozdělí termy, propojí Polylang, doplní fotky a mapu přesměrování |

Změna oproti dřívějšímu stavu (4 kategorie):

- `superior-plus` → `superior-s-terasou` („Superior s terasou“)
- `apartma-a-apartma-plus` → rozděleno na `apartma` (47 m²) a `apartma-superior` (58 m²)

Staré adresy přesměrovává child theme (funkce v `functions.php`) podle volby
`grid_presmerovani_pokoju`, kterou zapisuje `pokoje-aplikuj.php`.

## Vybavení hotelu (stránka Ubytování)

`vybaveni-hotelu.py` drží seznam ve 12 skupinách ve třech jazycích,
`patch-ubytovani.py` ho vloží do Divi bloků stránek 220 / 380 / 381
a zároveň opraví počet kategorií v nadpisu.

## Okolí hotelu (stránka O nás)

`okoli-hotelu.py` drží vedoucí text o Automotodromu Brno a 21 míst v okolí
(typ, vzdálenost, popis) ve třech jazycích. `patch-onas.py` z nich složí
světlou sekci `#okoli` a vloží ji mezi časosběr a rezervaci na stránkách
261 / 400 / 401.

## Masarykův okruh — sekce T6 (rezervace)

`okruh-obsah.py` drží texty stránky, `okruh-build.py` z nich skládá Divi bloky.
Sekce T6 je závěrečná CTA (`sec sec-dark final`, kotva `#rezervace`) s odkazy
na rezervaci, pokoje a kontakt podle jazyka (`LINKY` v `okruh-obsah.py`).
Výstup jde přes `wp post update 1018|1019|1020 okruh-<lang>.txt`.

`patch-home-okruh.py` přidá na titulní stránce (sekce T2 · Příběh místa)
druhé tlačítko „Masarykův okruh" vedle „Celý příběh hotelu".

## Zážitek MotoGP (prime karta)

`motogp-obsah.py` = texty CZ/EN/DE (perex, parametry, dlouhý popis s mapou
okruhu a odkazy na vstupenky), `motogp-build.py` je vyexportuje do JSON a
`motogp-vytvorit.php` z nich založí/aktualizuje CPT zážitky ve třech jazycích
(pole `prime` = zvýrazněná karta). `motogp-karta.py` vloží prime kartu na
první místo do mřížky `.exp` v sekci T4 (stránky 99/378/379 a 337/382/383).

## Fotky gastro provozů

`gastro-foto-dynamicky.py` nahradí natvrdo vložený Divi obrázek na kartách
v sekci T5 shortcodem `[grid_gastro_foto provoz="…" foto="…"]`. Fotka se pak
bere z náhledového obrázku CPT „Gastro provoz"; když chybí, použije se
původní soubor z motivu.

## Zážitky u okruhu (obsah + copy)

`zazitky-obsah.py` drží texty devíti zážitků ve třech jazycích (perex, parametry,
plný popis se sekcemi a odkazy na zdroje). `zazitky-build.py` je vyexportuje do
`zazitky.json`, `zazitky-vytvorit.php` z nich založí nebo aktualizuje CPT záznamy,
propojí je v Polylangu a nastaví přepínače „Zobrazit na homepage" a „Zobrazit na
stránce přehledu zážitků". Prime zážitek MotoGP má vlastní soubor `motogp-obsah.py`.

Fakta pocházejí z automotodrombrno.cz (jízdy veřejnosti, motokáry, motoškoly,
minibiková akademie, jízdy v supersportu) a polygonbrno.cz (škola smyku, drift,
gangster kurz, odpočet bodů, poukazy).

Popis detailu se skládá do matice dvou sloupců (`.rd-mrizka` / `.rd-blok`),
na úzkém displeji jde do jednoho sloupce. Karta „Dárkové poukazy“ není zážitek —
má v administraci zapnuté pole „Karta dárkových poukazů“, takže místo čísla
ukazuje `voucher`, nevede na detail ani na web pořadatele, ale na kotvu
`#poukazy` na stránce Zážitky, a v mřížce stojí vždy poslední.

`zazitky-karty-dynamicky.py` nahradí natvrdo vypsané karty v sekci T4 shortcodem
`[grid_zazitky_karty misto="homepage|prehled"]` — výpis se pak řídí výhradně
přepínači u zážitku v administraci (stránky 99/378/379 a 337/382/383).

## Responzivita úzkých režimů (7. 9. 2026)

Úpravy pro tablet na výšku (≤ 959 px) a mobil (≤ 640 px) jsou v `style.css`
v bloku na konci souboru; širší rozlišení se nemění. K obsahu patří tři skripty:

- `nadpisy-zlom.py` — `<br>` v nadpisech nahradí `<span class="zlom">`, které
  se na širokém rozlišení chová jako zalomení a na úzkém jako mezera
- `kickery-zlom.py` — vloží `<span class="zlom-uzky">` (opačné chování) do
  dlouhých kickerů „T1 · Ubytování · 60 pokojů…" a „T5 · Gastronomie · …"
- `sloucit-radky.py` — sloučí dva Divi řádky se stejnou třídou do jednoho
  (karty firemních akcí `onas-grid`, karty letišť `dp-air-grid`), aby se
  chovaly jako jedna mřížka na všech rozlišeních
- `hamburger-spodni-listu.py` — přestaví rozbalené menu v šabloně hlavičky
  (logo, pevná spodní lišta Rezervovat / Navigovat + jazyky + Volat)

Ovládání srovnávací tabulky (šipky po sloupcích) je v `assets/js/grid.js`.

## Spuštění

```sh
cd web-final/wordpress/tools/obsah
python3 pokoje-generator.py .        # -> pokoje-data.json
SP=. wp eval-file pokoje-aplikuj.php # potřebuje pokoje-data.json a media-map.txt
python3 patch-ubytovani.py .         # potřebuje vstup-<ID>.txt, píše vystup-<ID>.txt
python3 patch-onas.py .              # potřebuje onas-<ID>.txt, píše onas-out-<ID>.txt
```

Vstupy `vstup-<ID>.txt` / `onas-<ID>.txt` jsou `wp post get <ID> --field=content`,
výstupy se zpátky nahrají přes `wp post update <ID> <soubor>`.
