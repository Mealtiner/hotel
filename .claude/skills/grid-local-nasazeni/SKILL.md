---
name: grid-local-nasazeni
description: Provoz vývojového webu GRID Hotel v aplikaci Local — nastartovat služby bez GUI, připojit wp-cli, vyčistit Divi cache a hlavně udržet git v souladu s nasazeným webem. Načti před jakoukoli úpravou souborů webu, před měřením a vždy, když web neodpovídá.
---

# GRID Hotel — provoz Localu a soulad s gitem

## Nejdřív ověř, kde je pravda

**Repo bývá pozadu za nasazeným webem.** V září 2026 byl git o pět týdnů
starší: child theme 2.17.0 v repu proti 3.0.4 na webu, sedm pluginů pozadu
a tři pluginy (`garry-default`, `gridhotel-components`, `garry-typografie`)
v gitu vůbec nebyly. Kdo by tehdy upravil soubor v repu a nasadil ho,
přepsal by měsíc práce.

Proto **před každou úpravou** porovnej obě strany:

```bash
P=~/"Local Sites"/grid-hotel/app/public/wp-content
R=web-final/wordpress
diff -rq "$R/grid-divi5-child" "$P/themes/grid-divi5-child" | head
for pl in gridhotel-core gridhotel-components garry-default garry-typografie \
          garry-bocni-posuvnik garry-situace-na-trati garry-denni-menu \
          garry-kategorie-pokoju garry-sezona-cekaci-list garry-hero-krivka; do
  printf "%-28s repo=%-8s web=%s\n" "$pl" \
    "$(grep -m1 -hoE 'Version: *[0-9.]+' "$R/$pl/$pl.php" 2>/dev/null | grep -oE '[0-9.]+')" \
    "$(grep -m1 -hoE 'Version: *[0-9.]+' "$P/plugins/$pl/$pl.php" 2>/dev/null | grep -oE '[0-9.]+')"
done
```

Když se rozcházejí, **nejdřív naimportuj web do repa** (`rsync` směrem
web → repo, samostatný commit „import reality"), teprve pak edituj.
Nikdy neřeš rozpor tím, že nasadíš starší soubor z repa.

Po dokončení práce vždy synchronizuj zpět web → repo a commitni.
Repo tracked jen vlastní kód: `grid-divi5-child`, `gridhotel-*`, `garry-*`.
Cizí pluginy (ACF, Polylang, Yoast, Complianz, Fluent Forms, Wordfence…)
do repa nepatří.

## Nastartování bez GUI Localu

Local sám neběží headless a jeho router na portech 80/443 potřebuje práva.
Služby stránky se dají spustit ručně; stránka pak odpovídá na
`127.0.0.1:10018` (site ID `BGQr9NWq9`, ověř v `~/Library/Application Support/Local/sites.json`).

```bash
R="$HOME/Library/Application Support/Local/run/BGQr9NWq9"
LS="$HOME/Library/Application Support/Local/lightning-services"

# 1) MySQL
rm -f "$R/mysql/mysqld.sock" "$R/mysql/mysqld.sock.lock"
nohup "$LS/mysql-8.4.0/bin/darwin-arm64/bin/mysqld" \
      --defaults-file="$R/conf/mysql/my.cnf" >/tmp/gridhotel-mysqld.log 2>&1 &

# 2) PHP-FPM — vygenerovaný conf má include s build-time cestou, nahraď ji absolutní
sed "s|^include=php-fpm.d/\*.conf|include=$R/conf/php/php-fpm.d/*.conf|" \
    "$R/conf/php/php-fpm.conf" > /tmp/gh-php-fpm.conf
nohup "$LS/php-8.2.29+0/bin/darwin-arm64/sbin/php-fpm" \
      --fpm-config /tmp/gh-php-fpm.conf -c "$R/conf/php/php.ini" >/tmp/gridhotel-phpfpm.log 2>&1 &

# 3) nginx — adresář logs si nevytvoří sám
mkdir -p "$R/conf/nginx/logs" "$HOME/Local Sites/grid-hotel/logs/nginx"
nohup "$LS/nginx-1.26.1+3/bin/darwin-arm64/sbin/nginx" \
      -p "$R/conf/nginx" -c "$R/conf/nginx/nginx.conf" -g "daemon off;" >/tmp/gridhotel-nginx.log 2>&1 &

curl -s -o /dev/null -w "%{http_code}\n" -H "Host: gridhotel.local" http://127.0.0.1:10018/
```

`siteurl` i `home` jsou `http://gridhotel.local` (bez portu). V prohlížeči proto
přesměruj port přes resolver, ne přes přepis URL — jinak WordPress vygeneruje
odkazy na port 80 a assety se nenačtou:

```
--host-resolver-rules=MAP gridhotel.local:80 127.0.0.1:10018
```

Přesně to dělá `tools/qa/qa-config.mjs`. Po skončení práce procesy ukonči
(`pkill -f BGQr9NWq9`), ať si je Local při dalším startu spustí sám.

## wp-cli

Není nainstalované globálně. Použij `wp-cli.phar` s PHP binárkou Localu
a socketem MySQL:

```bash
LPHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
SOCK="$HOME/Library/Application Support/Local/run/BGQr9NWq9/mysql/mysqld.sock"
WP(){ "$LPHP" -d mysqli.default_socket="$SOCK" wp-cli.phar \
       --path="$HOME/Local Sites/grid-hotel/app/public" "$@"; }
```

## Divi cache — bez tohohle měříš starý web

`wp cache flush` nestačí. Divi generuje per-post statické CSS do
`wp-content/et-cache/<post_id>/` a ta se po změně `style.css` nebo obsahu
občas nepřegeneruje. Po **každém** zásahu do CSS nebo layoutu:

```bash
find ~/"Local Sites"/grid-hotel/app/public/wp-content/et-cache -mindepth 1 -delete
```

Je to jen build cache, smazání je bezpečné. `litespeed-cache` je sice
nainstalovaný, ale server je nginx — page-cache nedělá a řešit ho netřeba.

## Záloha před zásahem

Než začneš měnit obsah nebo hromadně soubory:

```bash
BK=~/GRIDhotel-zalohy/$(date +%Y%m%d-%H%M); mkdir -p "$BK"
tar -czf "$BK/wp-soubory.tar.gz" -C ~/"Local Sites" grid-hotel
"$LS/mysql-8.4.0/bin/darwin-arm64/bin/mysqldump" --socket="$SOCK" -uroot -proot \
  --single-transaction --default-character-set=utf8mb4 local | gzip > "$BK/db-local.sql.gz"
git bundle create "$BK/repo.bundle" --all
```

Když MySQL neběží, jde místo dumpu zkopírovat celý datadir
(`run/BGQr9NWq9/mysql/data`) — zastavený server znamená konzistentní kopii.

## Pasti, které už jednou stály čas

1. Divi 5 v Theme Builderu **nespouští shortcody** — tokeny se nahrazují
   v output bufferu (`template_redirect`, priorita 1). Fluent Forms se musí
   renderovat před `ob_start`, jinak se nenačtou jejich assety.
2. Divi 5 bloky **negenerovat od nuly**; obsah měnit přes `wp post update`.
3. **Nikdy nemazat `wp:divi` bloky regexem** přes `/-->`— nesamouzavírací
   otvírače se přeskočí a smaže se i otevírací sekce; stránka pak vypíše
   surový escapovaný obsah.
4. `get_term_by` filtruje přes Polylang → použij `get_terms` s `lang => ''`.
5. Jakýkoli shortcode-modul v obsahu spustí legacy Divi 4 notice.
6. Filtr Fluent Forms pro options selectů je per-element
   (`fluentform/rendering_field_data_select`), ne obecný.

Viz [[grid-responzivita]] a [[grid-qa-responzivita]].
