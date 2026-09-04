# GARRY WordPress Security – detailní mapa funkcí

> Stav návrhu: funkční specifikace, nikoli implementace  
> Datum ověření zdrojů: 2026-08-30  
> Související dokumenty: [hlavní specifikace](./GARRY-WP-SECURITY-MASTER-SPEC.md), [chování](./GARRY-WP-SECURITY-BEHAVIOR-SPEC.md), [blueprint dalších pluginů](./GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md)

## 1. Účel mapy

Tento dokument převádí požadavky na GARRY bezpečnostní plugin do jednotlivých modulů, kontrol a akcí. Plugin je navržen jako **orchestrátor bezpečnostního standardu**, nikoli jako druhý Wordfence. Vlastní kontroly jsou pokud možno read-only; aktivní ochranu lze delegovat Wordfence, AIOS, Kadence Security nebo jiné podporované službě.

Každá kontrola má:

- stabilní ID použitelné v databázi a API;
- aktuální a požadovaný stav;
- závažnost a váhu ve skóre;
- vlastníka kontroly: GARRY, externí plugin, server nebo člověk;
- frekvenci kontroly;
- důkaz, podle kterého byl stav vyhodnocen;
- bezpečný způsob opravy a případný rollback;
- čas prvního výskytu, poslední kontroly a poslední změny.

## 2. Model stavů

Jedna kontrola smí mít právě jeden z těchto stavů:

| Stav | Význam |
|---|---|
| `pass` | Požadavek byl ověřen a splněn. |
| `warning` | Doporučená úprava nebo částečně splněný požadavek. |
| `critical` | Bezprostřední nebo významné bezpečnostní riziko. |
| `unknown` | Plugin stav neumí důvěryhodně ověřit. Nesmí se zobrazit zeleně. |
| `not_applicable` | Kontrola pro tento web nedává smysl. |
| `delegated` | Kontrolu prokazatelně zajišťuje jiný aktivní poskytovatel. |
| `conflict` | Dva poskytovatelé současně zasahují do stejného bezpečnostního bodu. |
| `suppressed` | Správce výslovně přijal riziko do konkrétního data. |

Závažnost je `info`, `low`, `medium`, `high`, `critical`. Kosmetické prvky jako nestandardní DB prefix, skrytá login URL, ID prvního uživatele, `wp-config-sample.php`, `robots.txt` a `llms.txt` se nezapočítávají stejně jako aktualizace, 2FA, HTTPS nebo zálohy.

## 3. Režimy

| Režim | Chování |
|---|---|
| Audit | Pouze kontroly, logy, doporučení a bezpečné odkazy. Žádné změny konfigurace. |
| GARRY Standard | Doporučené bezpečné změny, integrace Wordfence/UpdraftPlus, povinné 2FA administrátorů, pravidelný scan. |
| GARRY Strict | Přísnější omezení pro jednoduchý prezentační web bez veřejné registrace, XML-RPC a aplikačních integrací. |
| Custom | Každá kontrola má vlastní požadovaný stav a vlastníka. |

Aktivace pluginu nikdy automaticky nezapne Strict ani neprovede migraci DB prefixu, změnu login URL nebo zásah do `wp-config.php`/`.htaccess`.

## 4. Mapa modulů

### 4.1 Bootstrap a prostředí

| ID | Funkce | Výchozí režim | Riziko změny | Vykonavatel |
|---|---|---:|---:|---|
| `ENV-001` | Detekce production/staging/development | všechny | žádné | GARRY |
| `ENV-002` | Detekce Apache/LiteSpeed/Nginx/IIS a reverzní proxy | všechny | žádné | GARRY |
| `ENV-003` | Detekce Single Site/Multisite | všechny | žádné | GARRY |
| `ENV-004` | Kontrola podporované verze PHP | všechny | manuální | hosting |
| `ENV-005` | Kontrola verze DB a `utf8mb4` | všechny | manuální | hosting |
| `ENV-006` | Detekce objektové cache, page cache a CDN | všechny | žádné | GARRY |
| `ENV-007` | Kontrola schopnosti loopback HTTP požadavků | všechny | nízké | GARRY/hosting |
| `ENV-008` | Kontrola spolehlivosti `wp_mail()` | Standard | žádné | GARRY/SMTP provider |

### 4.2 Jádro, aktualizace a supply chain

| ID | Kontrola nebo funkce | Frekvence | Závažnost | Oprava |
|---|---|---:|---:|---|
| `CORE-001` | Verze WordPressu a dostupná aktualizace | denně + event | high/critical | WordPress Upgrader |
| `CORE-002` | Povolené automatické minor/security aktualizace | denně | high | konfigurace/deployment |
| `CORE-003` | Oficiální checksumy WordPress jádra | týdně + po update | critical při změně | pouze návrh opravy/Wordfence |
| `CORE-004` | Neočekávané soubory v `wp-admin` a `wp-includes` | týdně | high | karanténa až po člověku |
| `CORE-005` | `WP_DEBUG` na produkci | denně | medium | `wp-config.php` snippet |
| `CORE-006` | PHP `display_errors` na produkci | denně | high | hosting/php.ini |
| `CORE-007` | Vestavěný editor pluginů a šablon | denně | medium | `DISALLOW_FILE_EDIT` |
| `UPD-001` | Aktualizace pluginů | 2× denně + event | high | core updater |
| `UPD-002` | Aktualizace šablon | 2× denně + event | high | core updater |
| `UPD-003` | Aktualizace překladů | denně | low | core updater |
| `UPD-004` | Neaktivní pluginy | denně | medium | doporučit smazání |
| `UPD-005` | Neaktivní šablony mimo aktivní/child/fallback | denně | low | doporučit smazání |
| `UPD-006` | Plugin bez známého update kanálu | denně | medium | ruční evidence zdroje |
| `UPD-007` | Auto-update policy podle kritičnosti pluginu | denně | medium | jednotlivě |
| `UPD-008` | Poslední úspěch/neúspěch aktualizace | event | high při selhání | log + alert |

### 4.3 Scheduler a průběžný scan

| ID | Funkce | Plán |
|---|---|---|
| `SCAN-001` | Rychlý health scan | denně |
| `SCAN-002` | Update scan | 2× denně, respektuje core transienty |
| `SCAN-003` | Uživatelé a oprávnění | denně a ihned po změně uživatele/role |
| `SCAN-004` | Integrita jádra a nečekané PHP soubory | týdně a po core update |
| `SCAN-005` | Oprávnění souborů a citlivé soubory | týdně |
| `SCAN-006` | HTTPS, hlavičky a veřejné endpointy | denně/týdně podle ceny testu |
| `SCAN-007` | Stav skutečně nasazených providerů a komponent z GARRY profilu | denně a po aktivaci/deaktivaci/aktualizaci pluginu |
| `SCAN-008` | Měsíční úplný audit | měsíčně |
| `SCAN-009` | On-demand scan z administrace | na vyžádání |

Požadavky scheduleru:

- jeden scan nesmí běžet paralelně dvakrát;
- dlouhé kontroly se dělí do kroků s časovým limitem;
- přerušený scan lze bezpečně obnovit;
- při nízké návštěvnosti se doporučí skutečný server cron;
- stav `overdue` vznikne, pokud se úloha nespustí do dvojnásobku intervalu;
- každá změna výsledku vytvoří event, opakovaný stejný stav pouze aktualizuje `last_seen`.

### 4.4 Uživatelé a oprávnění

| ID | Kontrola nebo akce | Zdroj důkazu | Závažnost |
|---|---|---|---:|
| `USR-001` | Počet administrátorů přes WP API | `WP_User_Query` | info |
| `USR-002` | Počet uživatelů přímo z `users/usermeta` | přímý `$wpdb` dotaz | critical při rozdílu |
| `USR-003` | Porovnání API seznamu a přímého DB seznamu | množinový rozdíl IDs | critical |
| `USR-004` | Efektivní kritické capabilities | runtime `user_can()` | high |
| `USR-005` | Individuální capabilities mimo roli | raw usermeta | high |
| `USR-006` | Multisite super admins | network option `site_admins` | critical při změně |
| `USR-007` | Nový administrátor | event + denní scan | critical alert |
| `USR-008` | Admin bez 2FA | provider adapter | critical po grace period |
| `USR-009` | Administrátor bez aktivity | login metadata/provider | medium |
| `USR-010` | Login `admin`/`administrator` | users table | medium |
| `USR-011` | Login shodný s display name/nicename | users table | low |
| `USR-012` | Nečekané účty mimo GARRY baseline | baseline delta | high |
| `USR-013` | Orphan usermeta a podezřelé capabilities klíče | DB | high |
| `USR-014` | Aktivní sessions a možnost revokace | session tokens | medium |
| `USR-015` | Application Passwords a poslední použití | WP API | medium |

Kontrola skrytých účtů je vícezdrojová. Přímý DB dotaz obchází `pre_user_query`, kterým lze změnit běžný seznam uživatelů. Runtime capabilities zachytí i oprávnění přidaná filtrem `user_has_cap`. Protože kompromitovaný WordPress může ovlivnit i běžící plugin, úplná nezávislá kontrola vyžaduje externí přímý DB dotaz nebo WP-CLI se skipnutými pluginy a šablonami; MU pluginy se při `--skip-plugins` stále načítají.

### 4.5 GARRY uživatelský baseline

Bootstrap wizard nabízí vytvoření těchto účtů:

| E-mail | Zamýšlená role | Doporučené použití |
|---|---|---|
| `podpora@garry.eu` | Administrator | servisní/break-glass účet, ne sdílené každodenní přihlašování |
| `radovan@garry.eu` | Administrator | osobní účet |
| `michal@garry.eu` | Administrator | osobní účet |

Funkce:

- ověří kolizi e-mailu i loginu;
- existujícímu účtu nikdy bez potvrzení nezmění heslo ani roli;
- vytvoří účet s náhodným interním heslem o vysoké entropii;
- odešle standardní jednorázový odkaz „nastavit heslo“, nikoli heslo v otevřeném textu;
- označí účet jako `2FA pending` a vyžádá 2FA podle zvoleného provideru;
- ověří doručení testovacího e-mailu před hromadným vytvořením;
- záznam baseline neregeneruje smazaný účet automaticky, pouze upozorní;
- servisní účet má mít heslo a recovery codes v GARRY password vaultu.

Tři trvalé administrátorské účty zvětšují útokovou plochu, proto je 2FA pro všechny povinné. Každodenní sdílení účtu `podpora@` se nedoporučuje kvůli auditovatelnosti.

### 4.6 Přihlášení a autentizace

| ID | Funkce | Preferovaný vlastník | Kolizní pravidlo |
|---|---|---|---|
| `AUTH-001` | 2FA administrátorů | Wordfence | pouze jeden 2FA provider |
| `AUTH-002` | Grace period pro 2FA | Wordfence | GARRY pouze čte stav |
| `AUTH-003` | Recovery codes | 2FA provider | GARRY je nikdy nečte ani neloguje |
| `AUTH-004` | Silná hesla | WordPress + Wordfence breached-password check | neprovádět nucenou periodickou rotaci |
| `AUTH-005` | Login throttling | Wordfence nebo edge WAF | GARRY nezapne vlastní, pokud je provider aktivní |
| `AUTH-006` | CAPTCHA na loginu | Turnstile nebo Wordfence reCAPTCHA | právě jeden provider na formulář |
| `AUTH-007` | Generické login chyby | vlastník login security | neduplikovat filtry |
| `AUTH-008` | XML-RPC politika | Wordfence/GARRY | delegovat Wordfence, pokud aktivní |
| `AUTH-009` | Změna login URL | GARRY volitelně | vyžaduje compatibility test a nouzový bypass |
| `AUTH-010` | Detekce neobvyklých loginů | Wordfence/monitoring | GARRY přebírá jen agregovaný stav |

### 4.7 Zálohy a brána rizikových změn

| ID | Funkce | Požadovaný důkaz |
|---|---|---|
| `BKP-001` | UpdraftPlus instalován a aktivní | aktivní plugin + podporovaná verze |
| `BKP-002` | Naplánovaná DB záloha | neprázdný schedule |
| `BKP-003` | Naplánovaná souborová záloha | neprázdný schedule |
| `BKP-004` | Poslední úspěšná DB záloha | dokončený backup set |
| `BKP-005` | Poslední úspěšná souborová záloha | požadované komponenty dokončeny |
| `BKP-006` | Vzdálené úložiště | nakonfigurovaná metoda + úspěšný upload |
| `BKP-007` | Stáří zálohy v rámci RPO | podle profilu webu |
| `BKP-008` | Poslední restore drill | ručně/externě potvrzený záznam |
| `BKP-009` | Tlačítko „Vytvořit bezpečnostní zálohu“ | capability + nonce + Updraft adapter |
| `BKP-010` | Fresh-backup token pro rizikovou změnu | navázaný na konkrétní backup nonce a změnu |

Fresh backup je platný pouze pokud:

- vznikl po otevření daného change workflow;
- zahrnuje DB a požadované souborové komponenty;
- skončil bez chyb;
- byl úspěšně odeslán do remote storage, pokud je remote storage nakonfigurováno;
- není starší než konfigurované okno, výchozí návrh 30 minut;
- jeho identifikátor nebyl již použit pro jinou destruktivní transakci.

### 4.8 DB bootstrap

| ID | Funkce | Dostupnost |
|---|---|---|
| `DB-001` | Kontrola výchozího prefixu `wp_` | vždy, pouze informativní |
| `DB-002` | Generování prefixu pro nový deployment | doporučeně před instalací přes WP-CLI |
| `DB-003` | Jednorázová migrace prefixu na prázdném webu | pouze Advanced Bootstrap |
| `DB-004` | Kontrola samostatného DB účtu a hostu | omezeně |
| `DB-005` | Kontrola DB práv | pouze pokud je možné bezpečně zjistit |
| `DB-006` | Kontrola veřejné dostupnosti DB | server/externí test |

Plugin se spouští až po instalaci WordPressu, takže technicky nemůže nastavit prefix před vytvořením tabulek. Preferované řešení je GARRY instalační recept `wp config create --dbprefix=...` před `wp core install`. Post-install migrace na jinak čistém webu zůstává volitelná, riziková a jednorázová.

### 4.9 Soubory a konfigurace

| ID | Kontrola | Akce pluginu |
|---|---|---|
| `FILE-001` | Soubory/adresáře s `777` | critical + návod |
| `FILE-002` | Vlastnictví a zapisovatelnost core | warning/critical |
| `FILE-003` | Oprávnění `wp-config.php` | doporučení podle serveru |
| `FILE-004` | Veřejné `.env`, logy, SQL/ZIP/TAR zálohy | externí HTTP test |
| `FILE-005` | Spustitelné PHP v uploads | server test/snippet |
| `FILE-006` | Directory listing | externí HTTP test |
| `FILE-007` | Změny vlastních GARRY souborů | manifest/checksum |
| `CFG-001` | Všech 8 salts/keys je unikátních | read-only audit |
| `CFG-002` | `FORCE_SSL_ADMIN`/HTTPS administrace | audit + snippet |
| `CFG-003` | `DISALLOW_FILE_EDIT` | audit + snippet |
| `CFG-004` | `DISALLOW_FILE_MODS` | jen managed deployment |
| `CFG-005` | `wp-config-sample.php` | info, bez bodů |
| `CFG-006` | `.htaccess` GARRY marker blok | pouze Apache/LiteSpeed |
| `CFG-007` | Nginx/IIS ekvivalent | vygenerovat návod |

### 4.10 HTTPS, hlavičky a veřejný povrch

| ID | Kontrola | Vlastník |
|---|---|---|
| `WEB-001` | Frontend HTTPS | server/CDN |
| `WEB-002` | Admin a login HTTPS | server/WordPress config |
| `WEB-003` | HTTP – HTTPS bez smyčky | server/CDN |
| `WEB-004` | Platný certifikát a blížící se expirace | externí monitor |
| `WEB-005` | `X-Content-Type-Options` | server/CDN |
| `WEB-006` | `Referrer-Policy` | server/CDN |
| `WEB-007` | `frame-ancestors`/X-Frame-Options | server/CDN |
| `WEB-008` | HSTS až po preflightu | server/CDN, ruční potvrzení |
| `WEB-009` | CSP report-only a později enforce | individuální implementace |
| `SURF-001` | Veřejný REST users endpoint | GARRY dle profilu |
| `SURF-002` | Author enumeration | GARRY dle profilu |
| `SURF-003` | XML-RPC/pingback | Wordfence/GARRY dle integrací |
| `SURF-004` | Application Passwords | audit a revokace |

### 4.11 Crawlers a AI

| ID | Funkce | Bezpečnostní skóre |
|---|---|---:|
| `CRAWL-001` | Stav `blog_public` a `noindex` | pouze environment guard |
| `CRAWL-002` | Virtuální/fyzický `robots.txt` a konflikty | ne |
| `CRAWL-003` | Production/Staging/Private preset | ne |
| `CRAWL-004` | Generovaný `llms.txt` | ne |
| `CRAWL-005` | Náhled a HTTP test obou souborů | ne |

`robots.txt` ani `llms.txt` nejsou access control. Soukromý obsah chrání autentizace a server.

### 4.12 Provider registry a doporučené pluginy

| Capability | Primární provider | Fallback | Zakázaná kombinace |
|---|---|---|---|
| Firewall/malware/login defense | Wordfence Security Free | AIOS nebo Kadence Security | dvě plné security suites |
| Backup | UpdraftPlus Free | jiný adapter v budoucnu | dvě automatické zálohy ve stejný čas |
| 2FA | Wordfence 2FA | Two-Factor | dva 2FA providery |
| CAPTCHA | Simple Cloudflare Turnstile | Wordfence reCAPTCHA v3 | dvě CAPTCHA na stejném formuláři |
| Security status history | GARRY | – | – |
| Širší activity audit | Simple History | security-suite audit log | duplicitní rozsáhlé logování bez retence |
| Transactional e-mail | FluentSMTP | WP Mail SMTP | dva SMTP providery |
| Vulnerability intelligence | Wordfence | volitelně Patchstack Free | duplicitní alerty je nutné sloučit |
| Uptime/TLS monitoring | externí služba | hosting | pouze lokální plugin nestačí |

### 4.13 Dashboard a reporting

| ID | Widget/stránka | Obsah |
|---|---|---|
| `UI-001` | Stav zabezpečení | kritické, warning, trend, poslední scan, vlastníci kontrol |
| `UI-002` | GARRY informace | správce, kontakt, prostředí, verze, další údržba |
| `UI-003` | Aktualizace a zálohy | update counts, poslední/next backup, remote, restore drill, cron |
| `UI-004` | Provider conflicts | konflikty a přesný návod, co vypnout |
| `UI-005` | Historie změn | timeline stavových přechodů |
| `UI-006` | Doporučené pluginy | Installed/Active/Configured/Healthy |
| `UI-007` | Export reportu | JSON/CSV/PDF až v pozdější fázi, bez secrets |

### 4.14 Projektový profil a kompatibilita GARRY katalogu

Katalog Divi, Elementor, UpdraftPlus, Yoast SEO, Wordfence, All-In-One Security, ACF, Complianz, LiteSpeed Cache, Microsoft Clarity, Google Site Kit a ostatních GARRY pluginů je **výběrový**. Profil webu deklaruje očekávané capability a komponenty; nevybraná položka je `not_applicable`. Kontroly nesmějí vytvářet tlak na instalaci všech položek.

| ID | Kontrola | Výsledek/reakce |
|---|---|---|
| `COMP-001` | Typ a verze site profilu | verzovaný baseline; drift je warning |
| `COMP-002` | Discovery schválených veřejných/private komponent | installed/active/version/update-channel/config-health |
| `COMP-003` | Chybějící capability, ne chybějící libovolný plugin | doporučit právě jednoho vhodného providera |
| `COMP-004` | Divi/Elementor frontend, editor, preview, REST/AJAX | cílený smoke test po relevantní změně/update |
| `COMP-005` | LiteSpeed cache výjimky pro login/admin/session/nonce/challenge | conflict/critical při cacheování citlivé odpovědi |
| `COMP-006` | LiteSpeed JS optimalizace vs builder/Turnstile/Clarity/Site Kit | browser smoke test, řízený purge přes adapter |
| `COMP-007` | Yoast vs GARRY ownership meta robots/robots/sitemap | právě jeden writer; výsledný HTTP/HTML test |
| `COMP-008` | Complianz jako consent owner Clarity/Site Kit | technický consent test, duplicita skriptů; bez právního verdiktu |
| `COMP-009` | ACF REST/public form exposure | read-only audit; žádná změna field groups/obsahu |
| `COMP-010` | Private Divi/Elementor Pro/Yoast Premium/ACF Pro update channel | healthy/unknown; nikdy nečíst licenční klíč |
| `COMP-011` | Ostatní GARRY pluginy a deklarativní capability manifest | allowlisted identita/verze/konflikty; žádné spustitelné příkazy |
| `COMP-012` | Wordfence + AIOS capability overlap | přítomnost obou je povolena; dvojí owner stejného scope je conflict |

Výchozí coexistence mapa při souběhu Wordfence + AIOS:

| Scope | Owner | Stav AIOS překryvu |
|---|---|---|
| WAF/firewall | Wordfence | vypnout |
| Malware/core integrity | Wordfence | vypnout nebo explicitně oddělit nekolidující read-only kontrolu |
| Login throttling/lockout | Wordfence | vypnout |
| 2FA/passkeys | Wordfence | vypnout |
| CAPTCHA | jeden owner podle formuláře | vypnout na stejném surface |
| XML-RPC/login route | jeden owner | žádná druhá protichůdná politika |
| Statický/server hardening | GARRY/server, případně vybraný AIOS modul | povolit jen bez druhého writera stejného souboru/pravidla |

Detekce má být read-only přes podporované API/hooky. Neznámá verze nebo nedostupný module status vede k `unknown` a ručnímu checklistu, nikoli k přímému zápisu do cizích interních options.

## 5. Datový model

Navržené tabulky používají aktuální `$wpdb->prefix`, ale názvy v kódu nikdy nejsou hardcoded.

### `garry_security_scans`

- `id`, `scan_uuid`, `scan_type`, `trigger`;
- `status`: queued/running/completed/partial/failed/cancelled;
- `started_at`, `finished_at`, `heartbeat_at`;
- `plugin_version`, `schema_version`;
- počty pass/warning/critical/unknown/conflict;
- runtime, peak memory a sanitizovaný error code.

### `garry_security_findings`

- stabilní `control_id` a scope;
- severity, state, owner, provider;
- `evidence_hash`, bezpečný serializovaný detail bez secretů;
- `first_seen`, `last_seen`, `changed_at`, `resolved_at`;
- suppression důvod, autor a expirace;
- doporučená akce a dokumentační URL.

### `garry_security_events`

- čas, event type, control ID;
- předchozí a nový stav;
- actor user ID nebo `system/cron/provider`;
- zdroj události;
- correlation ID změnové transakce;
- redigovaný kontext.

### Retence

- current findings: trvale;
- změnové eventy a scan summaries: výchozí návrh 365 dní;
- detailní evidence: 90 dní;
- bezpečnostní konfigurace: do odinstalace, pokud uživatel zvolí zachování;
- IP adresy pouze tehdy, pokud jsou nezbytné, se zkrácenou retencí/anonymizací.

Lokální log není forenzně nezměnitelný. Pozdější remote GARRY heartbeat smí být opt-in, podepsaný, minimalizovaný a bez osobních údajů či secretů.

## 6. Skóre

Skóre je doplňkové; vždy mají přednost konkrétní findings.

| Skupina | Návrh váhy |
|---|---:|
| Aktualizace a supply chain | 20 % |
| Autentizace, 2FA, hesla | 20 % |
| Zálohy a obnova | 15 % |
| HTTPS a serverový transport | 10 % |
| Uživatelé a oprávnění | 10 % |
| Integrita a oprávnění souborů | 10 % |
| Firewall, rate limiting, monitoring | 10 % |
| Konfigurace a veřejný povrch | 5 % |

Skrytí login URL, změna DB prefixu, odstranění sample/readme souborů, `robots.txt` a `llms.txt` mají váhu 0 %. Kritický finding současně zastropuje celkové hodnocení, aby mnoho kosmetických zelených bodů nepřekrylo chybějící zálohu nebo 2FA.

## 7. Explicitní non-goals

První verze nebude:

- druhý WAF nebo malware-cleanup engine;
- automaticky deaktivovat cizí pluginy;
- měnit primární klíče uživatelů v DB;
- posílat hesla e-mailem;
- přepisovat celý `.htaccess` nebo `wp-config.php` bez transakce;
- tvrdit, že `robots.txt` chrání neveřejná data;
- považovat lokální scan za nezávislou forenzní kontrolu kompromitovaného serveru;
- zapínat dva poskytovatele 2FA, CAPTCHA, login throttlingu nebo WAF současně;
- vyžadovat instalaci celého schváleného GARRY katalogu na každý web;
- vydávat technickou kontrolu Complianz/Clarity/Site Kit za právní stanovisko;
- upravovat obsah, ACF field groups nebo SEO nastavení bez explicitního vlastnictví;
- spouštět shell, vzdálené příkazy nebo kód z provider manifestu/heartbeat odpovědi;
- skrývat uživatele, obnovovat smazané admin účty nebo provádět neviditelnou persistence.
