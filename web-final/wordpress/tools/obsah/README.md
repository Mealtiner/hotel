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
