# GARRY WordPress Security – blueprint doporučených pluginů

> Cíl: schválený katalog GARRY, bezplatný doporučený bezpečnostní stack, ověření instalace/konfigurace a pravidla proti kolizím  
> Ověřeno: 2026-08-30  
> Poznámka: stav verzí, kompatibility a nabídek se musí při instalaci znovu načíst z WordPress.org; tento dokument není záruka budoucí kompatibility.

## 1. Doporučený bezpečnostní profil, ne povinný balík

Níže uvedené položky jsou **stavebnice profilu konkrétního webu**. Aktivace GARRY Security neznamená instalaci všech pluginů. Onboarding nejprve určí typ webu a jeho potřebné capability, objeví skutečně instalované komponenty a teprve potom nabídne chybějící doporučení. Stav `not_applicable` je legitimní a nesnižuje skóre, pokud danou funkci web nepotřebuje.

| Priorita | Plugin/služba | Slug | Role | Výchozí rozhodnutí |
|---|---|---|---|---|
| Vlastní | GARRY Security | vlastní balíček | audit, orchestrace, dashboard, stavová historie | vždy |
| P0 | Wordfence Security Free | `wordfence` | WAF, malware/integrity scan, brute force, 2FA, alerty | preferovaná security suite, pokud profil neurčí AIOS/jiného ownera |
| P0 | UpdraftPlus Free | `updraftplus` | DB/files backup, schedule, remote storage, restore | preferovaný backup provider; jiný provider vyžaduje adapter nebo manuální stav |
| P1 | Simple Cloudflare Turnstile | `simple-cloudflare-turnstile` | bot ochrana loginu a formulářů | primární CAPTCHA; Wordfence reCAPTCHA vypnout |
| P1 | FluentSMTP | `fluent-smtp` | spolehlivá pozvánka, password reset a alert e-mail | doporučený mail transport |
| P2 | Simple History | `simple-history` | širší activity audit mimo bezpečnostní findings | volitelně |
| Fallback | Two-Factor | `two-factor` | 2FA bez Wordfence | pouze bez jiného 2FA provideru |
| Volitelně | Patchstack Free | `patchstack` | vulnerability intelligence a centrální alerty | agenturní monitoring, ne druhý WAF |

Neinstalovat současně dvě plné security suites s aktivními WAF/login/hardening moduly. Wordfence + AIOS + Kadence Security není „více ochrany“, ale více hooků, lockout pravidel, filesystem zásahů a obtížnější diagnostika. Jsou-li Wordfence a AIOS na konkrétním webu oba, podporovaný je pouze níže popsaný modulární coexistence profil.

## 2. Pokrytí požadovaných oblastí

| Požadavek | GARRY | Doporučený provider | Stav v UI |
|---|---|---|---|
| Aktualizace | audit, historie a overdue policy | WordPress core; Wordfence/Patchstack intelligence | verze, update, stáří, auto-update |
| HTTPS | aktivní externí test | hosting/CDN | frontend/admin/redirect/cert/header |
| 2FA | kontrola všech adminů | Wordfence 2FA | required/enrolled/grace/unknown |
| Silná hesla | policy a finding | WordPress + Wordfence breached-password check | policy/provider/status |
| Správná oprávnění | scan, rozdíly a návod | server; Wordfence doplňkově | pass/warning/unknown |
| Zálohy | gate, historie a UI | UpdraftPlus | scheduled/fresh/remote/restore drill |
| Monitoring | scan log a alert orchestrace | Wordfence + externí uptime | last scan, findings, provider health |
| Omezení login útoků | ověřit vlastníka | Wordfence nebo edge WAF | delegated/configured/conflict |
| CAPTCHA | arbitration po formulářích | Turnstile | keys/tested/surfaces/conflict |

## 3. Wordfence Security Free

### Proč primární doporučení

Wordfence Free kombinuje endpoint WAF, scan integrity/malware, brute-force ochranu, 2FA, kompromitovaná hesla, login CAPTCHA, alerty a Wordfence Central. Má širokou instalační základnu a veřejnou dokumentaci. Free feed má proti placené variantě přibližně 30denní zpoždění nových firewall pravidel a malware signatures; UI to musí otevřeně uvést.

Zdroje: [WordPress.org plugin](https://wordpress.org/plugins/wordfence/), [Wordfence Free](https://www.wordfence.com/help/wordfence-free/), [Login Security](https://www.wordfence.com/help/login-security/).

### Co vlastní Wordfence

- WAF a blokování exploitů;
- malware/content scan a opravy souborů z WordPress.org;
- brute-force/rate-limit politiku;
- 2FA a recovery codes;
- compromised-password policy;
- XML-RPC login policy, pokud je zvolen;
- Wordfence alerty a Live Traffic.

GARRY nepíše do interních Wordfence DB options a nemaže jeho findings. Pouze adapterem zjišťuje dostupný veřejný stav a odkazuje do jeho konfigurace.

### Požadovaný GARRY Standard stav

- licence/onboarding dokončen;
- firewall enabled; po learning period přepnutý do protecting/extended protection, pokud hosting dovolí;
- alespoň standardní scan schedule aktivní;
- 2FA `Required` pro administrators;
- všichni tři GARRY admins enrolled, bez nekonečného grace period;
- login brute-force protection aktivní;
- důvěryhodná proxy/IP konfigurace ověřená, zejména za CDN;
- alert e-mail ověřený;
- pokud je CAPTCHA owner Turnstile, Wordfence reCAPTCHA vypnuta;
- Live Traffic/retence nastavena s ohledem na výkon a osobní údaje.

### Konflikty

- nezapínat GARRY login limiter vedle Wordfence;
- nezapínat druhý 2FA provider;
- Wordfence reCAPTCHA a Turnstile nesmí být na stejném surface;
- změna login URL musí projít Wordfence 2FA, reset hesla a WAF smoke testem;
- deep GARRY filesystem scan časově oddělit od Wordfence scanu;
- při Wordfence firewall optimization chránit `auto_prepend_file` a server config před slepým přepisem.

## 4. UpdraftPlus Free

### Role

- automatická a ruční DB záloha;
- files backup pro `wp-content`;
- remote storage ve free variantě například Google Drive, Dropbox, S3-compatible, Rackspace, FTP, DreamObjects, OpenStack nebo e-mail;
- obnova z administrace.

Free verze standardně nepokrývá všechny kořenové soubory jako `wp-config.php`; GARRY musí zobrazit skutečný rozsah, ne „full backup“ bez důkazu. Záloha pouze v `wp-content/updraft` na stejném serveru je warning.

Zdroje: [WordPress.org plugin](https://wordpress.org/plugins/updraftplus/), [co zálohuje](https://teamupdraft.com/documentation/updraftplus/topics/backing-up/faqs/what-does-updraftplus-back-up/), [remote storage](https://teamupdraft.com/documentation/updraftplus/topics/backing-up/faqs/where-are-my-updraftplus-backups-stored/).

### Tlačítko před rizikovou změnou

Updraft dokumentuje akci:

```php
do_action( 'updraft_backupnow_backup_all', array( 'nocloud' => 0 ) );
```

Zdroj: [Updraft shell/cron dokumentace](https://teamupdraft.com/documentation/updraftplus/topics/advanced-usage/faqs/can-i-run-backups-from-the-shell-cron/).

GARRY adapter ji použije jen po feature detection. Volání znamená **start**, nikoli úspěch. Adapter musí získat job/backup nonce, sledovat pokračování přes cron, ověřit všechny komponenty a remote upload a teprve potom vydat fresh-backup token.

### Instalace a konfigurace

1. Instalace z WordPress.org přes core upgrader.
2. Aktivace.
3. Nastavení zvláštního files a DB schedule podle RPO.
4. Připojení remote storage; doporučený free základ Google Drive/S3/Dropbox.
5. První ruční backup.
6. Ověření, že backup set lze zobrazit a stáhnout z remote storage.
7. Samostatný restore drill na stagingu a záznam data.

## 5. Cloudflare Turnstile

### Doporučení

Primární bezplatná CAPTCHA varianta je `Simple Cloudflare Turnstile`. Turnstile lze používat bez Cloudflare CDN, free plán je vhodný pro běžné weby a Cloudflare jej popisuje jako méně rušivou CAPTCHA alternativu. Plugin podporuje WordPress login/registraci/reset/komentáře a řadu formulářových a WooCommerce integrací.

Zdroje: [WordPress.org plugin](https://wordpress.org/plugins/simple-cloudflare-turnstile/), [Turnstile přehled](https://developers.cloudflare.com/turnstile/), [free plán](https://developers.cloudflare.com/turnstile/plans/).

### Výchozí vlastnictví surfaces

| Surface | Standard owner |
|---|---|
| `wp-login.php` | Turnstile; Wordfence reCAPTCHA off |
| Registrace/reset hesla | Turnstile |
| Komentáře | Turnstile podle typu webu |
| Contact Form 7/Elementor | Turnstile podle použité integrace |
| WooCommerce account/checkout | Turnstile až po end-to-end testu |

### Povinné preflighty

- Cloudflare site/secret key vložené, secret nikdy neodesílat do GARRY logu;
- plugin vlastní test API response je úspěšný;
- login s Wordfence 2FA funguje;
- reset hesla funguje;
- failover/fail-closed policy je zdokumentována;
- JS optimalizace/cache nezpožďuje widget;
- WooCommerce payment a AJAX formuláře jsou testovány;
- CSP dovoluje `https://challenges.cloudflare.com`.

Pokud je zvolena Wordfence Google reCAPTCHA v3, Turnstile se na core login surfaces vypne a může zůstat pouze na kontaktních formulářích. Wordfence reCAPTCHA posílá data Googlu a funguje zejména pro standardní/WooCommerce login; je validní fallback, ne druhá paralelní vrstva.

## 6. FluentSMTP

GARRY onboarding vytváří uživatele přes set-password e-mail a posílá bezpečnostní alerty. Funkční e-mail je proto provozní dependency. Doporučený free provider je FluentSMTP, který je dle autora 100% free/open source a podporuje více SMTP/API služeb a health monitoring.

Zdroj: [FluentSMTP na WordPress.org](https://wordpress.org/plugins/fluent-smtp/).

GARRY kontroluje pouze:

- plugin active/configured;
- úspěšný testovací e-mail;
- poslední známé delivery selhání, pokud existuje podporované API;
- právě jeden SMTP plugin.

Nikdy nečte nebo neloguje SMTP credentials. Alternativa: WP Mail SMTP Free. Dva SMTP providery současně jsou conflict.

## 7. Simple History

Volitelný širší audit log. Zaznamenává loginy, změny uživatelů, pluginů, obsahu a aktualizace a má vlastní logging API. GARRY si ponechá omezenou stavovou historii bezpečnostních kontrol; pokud je Simple History aktivní, může do něj emitovat významné GARRY události bez duplicitního ukládání celého payloadu.

Zdroj: [Simple History na WordPress.org](https://wordpress.org/plugins/simple-history/).

Výchozí rozhodnutí:

- není nutný pro bezpečnostní skóre;
- doporučený pro weby s více editory;
- sladit retenci, přístup a GDPR;
- nelogovat každý periodický pass, pouze přechody a lidské změny.

## 8. Two-Factor fallback

Komunitní plugin `two-factor` je fallback, pokud Wordfence/AIOS/Kadence nezajišťuje 2FA. Podporuje TOTP, e-mail a backup codes; WebAuthn lze doplnit providerem. Jeho základní workflow je více uživatelsky řízené, proto GARRY musí zvlášť auditovat, zda si admin 2FA nevypnul.

Zdroj: [Two-Factor na WordPress.org](https://wordpress.org/plugins/two-factor/).

Pravidlo: nikdy současně s Wordfence 2FA pro stejné uživatele.

## 9. Schválený katalog běžných GARRY komponent

Tento katalog popisuje komponenty, ze kterých GARRY podle projektu vybírá. **Nejde o požadavek, aby byly všechny současně aktivní.** Discovery musí rozlišit veřejný WordPress.org plugin, placenou/private variantu a theme. U privátní komponenty GARRY kontroluje existenci důvěryhodného aktualizačního kanálu, ale nikdy nečte ani neloguje licenční/API klíč.

| Komponenta | Identifikace | Typická role | Co kontroluje GARRY |
|---|---|---|---|
| Divi | aktivní theme `Divi`; případně privátní Divi Builder plugin | theme/page builder | podporovaný update channel, dostupná aktualizace, editor/preview smoke test |
| Elementor | `elementor`; případně privátní `elementor-pro` | page builder/forms | verze obou částí, update channel Pro, editor/preview/AJAX/REST, CAPTCHA surface |
| UpdraftPlus | `updraftplus` | zálohy | schedule, remote kopie, dokončení a restore drill |
| Yoast SEO | `wordpress-seo`; případně Premium | SEO, sitemap, meta robots | update channel, ownership robots/meta/sitemap, konflikt crawler politiky |
| Wordfence | `wordfence` | security suite | stav skutečně zvolených capability a kolize |
| All-In-One Security | `all-in-one-wp-security-and-firewall` | alternativní/modulární security suite | module-by-module ownership; server compatibility |
| ACF | `advanced-custom-fields`; ACF Pro jako private build | datový model/custom fields | update channel, REST exposure a veřejné formuláře pouze jako audit |
| Complianz | `complianz-gdpr` | consent/cookie orchestrace | stav wizardu, consent integration a health bez právního verdiktu |
| LiteSpeed Cache | `litespeed-cache` | cache/optimalizace | cache výjimky, purge adapter, nonce/challenge/script smoke test |
| Microsoft Clarity | `microsoft-clarity` nebo schválená manuální integrace | analytika/heatmaps | přítomnost, consent owner, duplicitní vložení skriptu |
| Google Site Kit | `google-site-kit` | Google služby/analytics | spojení služby, aktualizace, consent owner a duplicitní tracking |
| Ostatní GARRY pluginy | schválený vendor/manifest a update channel | projektové funkce | identity, verze, update health, deklarované capability/konflikty |

Zdroje: [Divi dokumentace](https://www.elegantthemes.com/documentation/divi/), [Elementor](https://wordpress.org/plugins/elementor/), [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/), [ACF](https://wordpress.org/plugins/advanced-custom-fields/), [Complianz](https://wordpress.org/plugins/complianz-gdpr/), [LiteSpeed Cache](https://en-gb.wordpress.org/plugins/litespeed-cache/), [Microsoft Clarity](https://wordpress.org/plugins/microsoft-clarity/), [Site Kit](https://wordpress.org/plugins/google-site-kit/).

### Kompatibilitní pravidla katalogu

- **Divi/Elementor:** změna login route, CSP, cache, CAPTCHA, REST nebo `admin-ajax.php` musí projít testem frontendu, editoru, preview, formuláře a přihlášení. GARRY nesmí označit REST API plošně za zbytečné jen proto, že web nemá veřejné API.
- **LiteSpeed Cache:** GARRY používá pouze zdokumentovaný purge mechanismus; po bezpečnostní změně invaliduje jen nutný rozsah. Login, admin, nonce, session, 2FA a CAPTCHA challenge odpovědi se nesmějí cacheovat. Kombinace/defer JavaScriptu musí projít testem Turnstile, Clarity, Site Kit a builderu.
- **Complianz + Clarity/Site Kit:** Complianz je výchozí vlastník consent vrstvy, pokud je v profilu. GARRY hledá duplicitní tracking a ověřuje technický stav blokování/Consent Mode, ale nevydává právní potvrzení GDPR ani bez souhlasu nemění consent kategorii.
- **Yoast:** Yoast vlastní SEO meta, sitemap a zpravidla veřejnou indexační konfiguraci. Crawler modul GARRY detekuje jeho výstup a spolupracuje přes podporované filtry; nepřepisuje jednostranně `robots.txt`, meta robots nebo sitemap pravidla.
- **ACF:** GARRY nemění field groups ani obsah. Upozorní pouze na zbytečné veřejné REST exposure, nechráněné front-end formuláře a chybějící aktualizační kanál ACF Pro.
- **Ostatní GARRY pluginy:** capability manifest je deklarativní. Nesmí obsahovat spustitelné příkazy, libovolné callbacky z remote odpovědi ani secrets. Neznámý plugin zůstane `unknown/manual_review`, nikoli automaticky „malware“.

### Wordfence + AIOS coexistence profil

Oba pluginy mohou být nainstalované nebo aktivní, pokud to vyžaduje konkrétní projekt, ale každá capability má právě jednoho vlastníka. Výchozí GARRY profil při souběhu je:

| Capability | Výchozí owner | Požadavek na druhý plugin |
|---|---|---|
| WAF/firewall pravidla | Wordfence | AIOS firewall vypnutý |
| Malware/core integrity scan | Wordfence | AIOS file-change/content scan vypnutý nebo časově i rozsahem jednoznačně oddělený |
| Brute-force/login lockout | Wordfence | AIOS login lockout vypnutý |
| 2FA/passkeys | Wordfence | AIOS 2FA vypnuté |
| CAPTCHA na konkrétním formuláři | Turnstile nebo Wordfence | AIOS CAPTCHA na stejném surface vypnutá |
| XML-RPC/login policy | Wordfence | AIOS nesmí aplikovat druhou protichůdnou politiku |
| Rename login | maximálně jeden explicitně zvolený provider | všechny ostatní route-changing moduly vypnuté |
| Vybrané statické hardening kontroly | GARRY/server, případně AIOS | pouze po kontrole, že stejný soubor/pravidlo nespravuje Wordfence, LiteSpeed nebo hosting |

GARRY stav modulů pouze bezpečně detekuje. Nebude naslepo přepisovat interní AIOS/Wordfence options. Pokud chybí stabilní veřejné API, zobrazí ruční checklist s deep linkem a po zásahu provede smoke test. Souběh aktivních překryvů je `conflict`/`critical`; pouhá přítomnost obou pluginů bez překryvu nikoli. AIOS upozornění založená na `.htaccess` se označí `not_applicable/manual` na Nginx/IIS; na Apache/LiteSpeed se kontroluje i vlastnictví pravidel.

Reference k principu souběhu: [Wordfence podpora – používat jednoho vlastníka překrývající se funkce](https://wordpress.org/support/topic/aios-and-wordfence/), [AIOS na WordPress.org](https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/).

## 10. Alternativy k Wordfence

### AIOS – All-In-One Security

Free verze nabízí firewall, login security, 2FA, file permission/change kontroly, audit log a hardening. Je rozumná alternativa, zejména v ekosystému Team Updraft. Nesmí být defaultně kombinována s aktivním Wordfence WAF/login/2FA. [WordPress.org](https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/)

### Kadence Security Basic

Dříve Solid Security/iThemes Security. Nabízí login ochranu, 2FA, firewall a site scan. Adapter musí počítat s aktuálním názvem i historickým slugem `better-wp-security`. Je alternativa, ne doplněk Wordfence. [WordPress.org](https://wordpress.org/plugins/better-wp-security/)

### Sucuri Security Scanner

Free plugin je vhodný pro audit log, integrity monitoring a remote SiteCheck; cloud WAF je placená služba. Může být alternativa scanneru/monitoringu, ale kombinace s Wordfence vytváří překryv scanů a alertů. [WordPress.org](https://wordpress.org/plugins/sucuri-scanner/)

### Patchstack Free

Free varianta přidává vulnerability intelligence, e-mailové alerty a centrální přehled; firewall/virtual patching je placený. Lze ji volitelně kombinovat s Wordfence, pokud je vlastník capability pouze „vulnerability intelligence“ a duplicitní alerty se sloučí. I Patchstack doporučuje používat co nejméně překrývajících se security pluginů. [WordPress.org](https://wordpress.org/plugins/patchstack/)

## 11. Kolizní matice

| Kombinace | Stav | Akce GARRY |
|---|---|---|
| Wordfence + UpdraftPlus | podporováno | oddělit časy scan/backup |
| Wordfence 2FA + Turnstile | podporováno po testu | Wordfence reCAPTCHA off; otestovat druhý krok |
| Wordfence reCAPTCHA + Turnstile na loginu | konflikt | vybrat právě jednu |
| Wordfence + Two-Factor | konflikt | vybrat 2FA ownera |
| Wordfence + AIOS/Kadence full modules | kritický překryv | vybrat jednu suite |
| Wordfence + Patchstack Free intelligence | podmíněně | sjednotit alerty; žádný druhý WAF |
| Wordfence + Sucuri free scan | podmíněně | rozvrhnout scany a logy |
| Updraft + jiný scheduled backup | warning/conflict | odlišné časy a účel nebo jeden vypnout |
| FluentSMTP + WP Mail SMTP | konflikt | jeden transport |
| Simple History + suite audit log | podporováno podmíněně | sladit rozsah a retenci |

## 12. Provider registry

Každý adapter má deklarativní manifest:

```yaml
id: wordfence
slug: wordfence
entry_files:
  - wordfence/wordfence.php
capabilities:
  - firewall
  - malware_scan
  - login_rate_limit
  - two_factor
  - captcha_login
conflicts:
  exclusive:
    - firewall
    - two_factor
    - captcha_login
external_account_required: true
status_checks:
  - installed
  - active_or_network_active
  - supported_version
  - onboarding_complete
  - capability_configured
privacy_review_required: true
```

Registry obsahuje pevně povolené WordPress.org slugy a entry files. Nikdy nepřijímá libovolnou package URL od browser requestu.

## 13. Instalace a ověření

### Discovery

- `get_plugins()` pro instalované pluginy;
- `is_plugin_active()` a `is_plugin_active_for_network()`;
- explicitní kontrola MU plugins;
- WordPress update transient pro dostupnou verzi;
- WordPress.org Plugins API pro autora, kompatibilitu, poslední update a package URL;
- provider adapter pro configuration health.

### Bezpečná instalace

- pouze uživatel s `install_plugins` a `activate_plugins`;
- CSRF nonce a explicitní potvrzení;
- core `Plugin_Upgrader`, ne `curl`, vlastní unzip nebo skrytý sideload;
- instalovat pouze allowlisted slug z WordPress.org;
- aktivaci a propojení externího účtu zobrazit jako samostatné kroky;
- po aktivaci smoke test a rollback/deactivation nabídka;
- logovat slug/verzi/výsledek, nikoli license/API key.

### Health stav

Aktivní plugin není automaticky zdravý. Stav `Healthy` vyžaduje:

- podporovanou a aktuální verzi;
- dokončenou konfiguraci;
- požadované capability aktivní;
- úspěšný provider-specific test;
- žádný konflikt s jiným ownerem;
- funkční cron/remote API, pokud je potřebuje.

## 14. Výběrová kritéria pro budoucí doporučení

- aktivní údržba a kompatibilita s podporovaným WordPress/PHP;
- jasný autor a veřejný changelog;
- instalace z WordPress.org nebo schváleného privátního zdroje;
- bezpečný update kanál;
- free funkce skutečně pokrývá deklarovanou capability;
- export/recovery a žádný lock-in tam, kde je kritický;
- dokumentované externí přenosy dat a privacy policy;
- žádný neřešitelný překryv s výchozím stackem;
- adapter má stabilní API nebo bezpečný read-only fallback;
- změna doporučení je verzovaná a neinstaluje se automaticky na existující weby.
