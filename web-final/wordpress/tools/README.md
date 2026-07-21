# Generátor Divi JSON layoutů

1. `php render.php` — vyrenderuje HTML všech sekcí (stub WP funkcí, fallback texty ze shortcodes.php) do `out/`
2. `python3 assemble.py` — složí `out/*.html` do `../divi-json/*.json` (1 designová sekce = 1 Divi sekce s Text modulem)

Po změně textů ve `shortcodes.php` (fallbacky) spusť oba kroky.
Pozn.: texty lze měnit i přímo v JSONu / ve Visual Builderu — generátor je pro hromadnou regeneraci.

## Nasazení do Local WP (gridhotel.local) — automaticky

Web v aplikaci Local („GRID HOTEL", Divi 5) používá nový blokový formát `wp:divi/*`.
Skript `local-d5-update.py` vymění v post_content obsahové shortcode moduly za statické
HTML z `out/` a zachová widgety (`[grid_telemetry]`, `[grid_galerie]`).

Postup (wp-cli přes Local socket, cesty dle Local → Database):

```bash
LPHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
SOCK="$HOME/Library/Application Support/Local/run/<ID>/mysql/mysqld.sock"   # viz Local → Database → Socket
WP() { "$LPHP" -d mysqli.default_socket="$SOCK" wp-cli.phar --path="$HOME/Local Sites/grid-hotel/app/public" "$@"; }

php render.php && python3 assemble.py            # 1) vygenerovat HTML + JSONy
for id in 99 220 226 229 232 235 238 241 244 247 250 258 261 264 270 293 324 337 340 11 106; do
  WP post get $id --field=post_content > d5-dump/$id.txt   # 2) dump aktuálního stavu
done
python3 local-d5-update.py                       # 3) výměna modulů → d5-new/
for f in d5-new/*.txt; do WP post update "$(basename $f .txt)" "$f"; done   # 4) upload
WP cache flush && WP rewrite flush --hard
```

Mapování ID ↔ stránka je v `local-d5-update.py` (PAGE_MAP). Header/footer = Theme
Builder posty 11/106. Před hromadnou změnou: `mysqldump --socket=$SOCK -uroot -p local > zaloha.sql`.
