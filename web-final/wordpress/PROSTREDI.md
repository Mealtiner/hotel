# Dvě lokální prostředí (Local by Flywheel)

Od 7. 9. 2026 je projekt rozdvojený. Weby jsou úplně oddělené: vlastní složka,
vlastní MySQL instance, vlastní uploads. Změna v jednom se do druhého nepromítne.

| | gridhotel.local | grid2.local |
|---|---|---|
| účel | klientská verze — stav, který jsme ukázali klientovi | pracovní větev pro další úpravy |
| složka | `~/Local Sites/grid-hotel/app/public` | `~/Local Sites/grid2/app/public` |
| ID v Localu | `BGQr9NWq9` | `SEOjEhlv7` |
| MySQL socket | `~/Library/Application Support/Local/run/BGQr9NWq9/mysql/mysqld.sock` | `~/Library/Application Support/Local/run/SEOjEhlv7/mysql/mysqld.sock` |
| větev v gitu | `responzivita-jednotny-koridor` (značka `klientska-verze-2026-09-07`) | `grid2` |

Obě instalace vznikly ze stejného stavu: WP 7.1, Divi 5.11.1, child theme 3.2.4,
gridhotel-core 2.3.0, gridhotel-components 1.3.0, 61 stránek, 33 zážitků, 3 jazyky.

## wp-cli

Local nemá wp-cli v PATH — volá se přes PHP binárku Localu se socketem daného webu:

```sh
SITE=SEOjEhlv7                       # grid2.local (pro gridhotel.local: BGQr9NWq9)
SOCK="$HOME/Library/Application Support/Local/run/$SITE/mysql/mysqld.sock"
PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
cd "$HOME/Local Sites/grid2/app/public"
"$PHP" -d mysqli.default_socket="$SOCK" .../tools/wp-cli.phar plugin list
```

Příkazy, které si volají binárky `mysql` / `mysqldump` (`db export`, `db import`,
`db query`, `db reset`), o socketu z php.ini nevědí. Export bere `--socket=…`,
import a reset ne — u těch se použije rovnou klient MySQL:

```sh
mysql --socket="$SOCK" -u root -proot local < zaloha.sql
```

## Přenos stavu z jednoho webu do druhého

```sh
# 1) soubory (vzory s lomítkem na začátku, jinak by se smazaly i složky uvnitř pluginů)
rsync -a --delete --exclude '/et-cache/' --exclude '/upgrade/' --exclude '/cache/' \
      --exclude '/updraft/' --exclude '.DS_Store' \
      ~/"Local Sites/grid-hotel/app/public/wp-content/" \
      ~/"Local Sites/grid2/app/public/wp-content/"

# 2) databáze (wp-config.php cílového webu zůstává jeho vlastní)
mysql --socket="$SOCK_CIL" -u root -proot local < databaze.sql

# 3) adresy
wp search-replace 'gridhotel.local' 'grid2.local' --all-tables --precise --skip-columns=guid

# 4) cache Divi a rewrite pravidla
find wp-content/et-cache -mindepth 1 -delete
wp cache flush && wp rewrite flush --hard
```

## Zálohy

Plná záloha klientské verze (databáze, wp-content, manifest, kontrolní součty,
postup obnovy) je mimo git v `no-git/zalohy/2026-09-07-klientska-verze/`.
