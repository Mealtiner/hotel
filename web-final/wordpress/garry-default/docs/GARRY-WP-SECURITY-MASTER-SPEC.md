# GARRY WordPress Security – hlavní produktová a technická specifikace

> Pracovní název: **GARRY Security**  
> Typ: vlastní WordPress plugin / bezpečnostní orchestrátor  
> Stav: návrh před implementací  
> Datum rešerše: 2026-08-30

## 1. Dokumentační sada

Tento soubor je hlavní zdroj kontextu pro implementaci. Detailní katalogy jsou rozdělené do:

- [detailní mapa všech funkcí](./GARRY-WP-SECURITY-FUNCTION-MAP.md);
- [detailní chování a workflow](./GARRY-WP-SECURITY-BEHAVIOR-SPEC.md);
- [blueprint doporučených pluginů, instalace a kolizí](./GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md).
- [závazný dodatek pro Embedded Framework 2.3](./GARRY-WP-SECURITY-EMBEDDED-FRAMEWORK-2.3-ADDENDUM.md).

Při rozporu platí pořadí: bezpečnost a zákaz ztráty přístupu/dat → dodatek pro Embedded Framework 2.3 → tento master dokument → behavior spec → function map → provider blueprint.

## 2. Produktová definice

GARRY Security sjednocuje bezpečnostní baseline všech WordPress webů vytvořených GARRY. Není náhradou hostingu, edge WAF, backup storage ani plnohodnotného malware scanneru. Má čtyři odpovědnosti:

1. **Audit:** pravidelně měřit stav WordPressu, uživatelů, souborů, konfigurace, HTTPS, aktualizací, záloh a providerů.
2. **Orchestrace:** určit, zda kontrolu vlastní GARRY, Wordfence, UpdraftPlus, Turnstile, server nebo správce, a zabránit dvojímu zásahu.
3. **Bezpečné workflow:** u bezpečných změn nabídnout opravu; u rizikových vyžadovat fresh Updraft backup, souhlas, ověření a rollback.
4. **Provozní přehled:** tři dashboard karty, historie stavových změn, doporučení pluginů a alerty.

Hlavní princip: plugin nemá maximalizovat počet zapnutých přepínačů, ale průkazně snížit riziko bez kritických kolizí.

### 2.1 Závazná role v GARRY Embedded Frameworku 2.3

GARRY Security je samostatný GARRY plugin, nikoli povinný Core nebo poskytovatel frameworku pro ostatní pluginy. Obsahuje vlastní embedded kopii Frameworku 2.3 s pluginově specifickým namespace, vlastním Přehledem, Info, Logem, verzováním, ikonou a manifestem.

Bez aktivního GARRY Security musí každý jiný GARRY plugin fungovat plně samostatně. Je-li GARRY Security aktivní společně s nimi, může být podle deterministické requestové volby vlastníkem společného menu nebo skupinového dashboardu. Tato přednost není trvalá ani výhradní; po deaktivaci Security ji bez zápisu do databáze převezme další kompatibilní plugin.

Security smí číst pouze redigovaná read-only metadata ostatních účastníků Frameworku. Nesmí je includovat, vyžadovat, aktualizovat, aktivovat, opravovat ani měnit jejich data, logy, manifesty nebo nastavení.

## 3. Hrozby a hranice důvěry

Plugin řeší:

- zastaralé jádro/pluginy/šablony a neznámý update kanál;
- kompromitovaná nebo slabá autentizace;
- nečekané administrátory a změny capabilities;
- nefunkční/neudržované zálohy;
- změny core souborů a nebezpečná oprávnění;
- špatnou produkční konfiguraci, HTTPS a veřejný povrch;
- deaktivaci nebo chybnou konfiguraci bezpečnostního providera;
- selhání pravidelných scanů/cron úloh;
- kolize dvou WAF, 2FA, CAPTCHA, SMTP nebo login-limit providerů.

Plugin sám nemůže plně garantovat:

- čistotu kompromitovaného OS/PHP procesu nebo DB drop-inu;
- síťovou konfiguraci, TLS private keys, SSH/SFTP účty a DB firewall;
- dostupnost webu, když je celý server down;
- nezměnitelnost lokálních logů;
- bezpečnost cizího pluginu po budoucí aktualizaci.

Proto výsledky rozlišují `pass`, `warning`, `critical`, `unknown`, `delegated`, `conflict` a `not_applicable`. Interní audit skrytých uživatelů je silnější než admin UI, ale nezávislou jistotu poskytne až externí DB/WP-CLI kontrola.

## 4. Profil webu a schválený katalog

### GARRY Standard security baseline

- GARRY Security: audit/orchestrace/UI/history;
- Wordfence Security Free: WAF, malware/integrity, login limit, 2FA, breached passwords;
- UpdraftPlus Free: DB/files backup + remote storage;
- Simple Cloudflare Turnstile: CAPTCHA na zvolených formulářích;
- FluentSMTP: spolehlivý mail transport;
- Simple History: volitelný širší audit log.

Wordfence reCAPTCHA je ve výchozím profilu vypnuta, pokud Turnstile vlastní login surface. Dva plné security plugins nebo dva 2FA/CAPTCHA providery se nepovolí bez vyřešení conflict findingu. Pluginy se nikdy neinstalují automaticky pouhou aktivací GARRY.

Tento baseline je doporučená skladba capability, nikoli požadavek instalovat všechny pluginy na každý web. Konkrétní web má verzovaný profil podle typu projektu. Profil vybírá z katalogu GARRY: Divi, Elementor, UpdraftPlus, Yoast SEO, Wordfence, All-In-One Security, ACF, Complianz, LiteSpeed Cache, Microsoft Clarity, Google Site Kit a ostatní GARRY pluginy. Nevybraná komponenta je `not_applicable`; skóre nepenalizuje její absenci. Penalizuje se chybějící požadovaná capability, ne nepřítomnost konkrétní značky.

### Wordfence a All-In-One Security

Wordfence je preferovaný primární owner WAF, malware/integrity, login throttlingu a 2FA. AIOS může být alternativou nebo může na vybraném webu zůstat aktivní pouze s nepřekrývajícími moduly. Samotná přítomnost Wordfence + AIOS není automaticky chyba; `conflict` vzniká až tehdy, když oba spravují stejný scope, zejména firewall, login lockout, 2FA, CAPTCHA, XML-RPC, rename login nebo stejný soubor/serverové pravidlo. GARRY nic z toho tiše nevypíná a nepíše přímo do neověřených interních options. Nabídne ownership mapu, ruční checklist, deep links a následný smoke test.

### Kompatibilitní priority katalogu

- Divi/Elementor: zachovat editor, preview, REST/AJAX a formuláře při změnách loginu, CSP, cache a CAPTCHA.
- LiteSpeed Cache: necachovat login/admin/session/nonce/2FA/CAPTCHA; po změně použít podporovaný purge a ověřit optimalizovaný JavaScript.
- Yoast SEO: respektovat ownership meta robots, sitemap a `robots.txt`; Crawler modul nesmí být druhý nekontrolovaný writer.
- Complianz + Clarity/Site Kit: Complianz vlastní consent vrstvu, pokud je vybrán; GARRY kontroluje technickou integraci a duplicity, neposkytuje právní verdikt.
- ACF: pouze read-only audit veřejných REST/form surfaces a update channelu; žádná změna field groups nebo obsahu.
- Private/Premium komponenty: ověřit důvěryhodný update channel bez čtení nebo logování licence/API klíče.
- Ostatní GARRY pluginy: verzovaný deklarativní manifest identity a capability; žádný vzdáleně spustitelný příkaz nebo callback.

## 5. Režimy

### Audit

Pouze read-only kontroly, reporty a doporučení. Vhodné pro existující web před migrací na GARRY baseline.

### GARRY Standard

Produkční výchozí profil. Povinné 2FA adminů, aktuální komponenty, remote backup, HTTPS, podporovaný security provider, denní rychlý a týdenní hluboký scan.

### GARRY Strict

Pro jednoduché prezentační weby bez veřejné registrace a integrací. Může omezit XML-RPC, author/REST enumeration, Application Passwords a další nepoužívané surfaces. Nikdy se neaplikuje bez compatibility preflightu.

### Custom

Jednotlivé požadavky, vlastníci a výjimky. Výjimka má autora, důvod a expiraci; trvalé „ignore“ bez zdůvodnění není povoleno pro critical kontroly.

## 6. Logická architektura

```text
GARRY Security
├── Bootstrap & Environment
├── Control Catalog
├── Scanner & Job Queue
├── Evidence Collectors
│   ├── WordPress API
│   ├── Direct DB
│   ├── Runtime capabilities
│   ├── Filesystem/checksums
│   └── HTTP/loopback
├── Provider Registry
│   ├── Wordfence adapter
│   ├── UpdraftPlus adapter
│   ├── Turnstile adapter
│   ├── 2FA adapters
│   ├── AIOS adapter
│   ├── LiteSpeed/Yoast/Complianz adapters
│   ├── Divi/Elementor/ACF/Site Kit/Clarity discovery
│   └── GARRY/logging/SMTP adapters
├── Site Profile & Compatibility Catalog
├── Policy & Conflict Engine
├── Change Transaction Engine
├── Findings, Events & Retention
├── Dashboard/Site Health/Admin UI
├── Alerts & optional remote heartbeat
└── Install/Update/Uninstall lifecycle
```

### Rozhraní kontroly

Každá kontrola deklaruje ID, title, category, severity, default mode, cadence, dependencies, owner capability, collector, evaluator, remediation, verification a rollback. Výsledek nese redigovaný evidence payload a hash. UI nesmí bezpečnost odvozovat z textu; používá strukturovaný stav.

### Provider adapter

Adapter umí discovery, version support, configuration status, capability status, admin URL a případně explicitní veřejnou akci. Neznámá verze vede k `unknown`. Cizí neveřejné options se bez verzovaného adapteru neupravují.

### Conflict engine

Capability je exkluzivní podle scope. Například `captcha:login` může mít jiného ownera než `captcha:contact_form`, ale dva vlastníci téhož scope jsou conflict. Engine automaticky nic cizího nevypne; poskytne přesný plán a po ruční změně smoke test.

## 7. Instalační bootstrap

### Doporučené pořadí pro nový web

1. GARRY deployment vytvoří DB, samostatný DB účet a náhodný table prefix před `wp core install`.
2. Nainstaluje WordPress přes HTTPS staging/production workflow.
3. Aktivuje GARRY Security v Audit režimu.
4. Onboarding detekuje prostředí a existující providery.
5. Ověří mail transport.
6. Nabídne vytvoření tří GARRY účtů.
7. Připojí Updraft remote storage a vytvoří první backup.
8. Nainstaluje/konfiguruje Wordfence, 2FA a Turnstile s arbitration pravidly.
9. Provede vstupní deep scan a uloží baseline.
10. Teprve potom přepne do Standard/Strict.

### GARRY accounts

Po explicitním potvrzení vytvořit `podpora@garry.eu`, `radovan@garry.eu`, `michal@garry.eu` jako administrátory. Heslo je náhodné interní a nikdy není e-mailem ani logem zveřejněno. Uživatel dostane standardní jednorázový set-password link. Všechny účty musí po grace period mít 2FA.

`podpora@` je ideálně break-glass účet s alertem při použití. Osobní odpovědnost je bezpečnější než sdílený login.

### ID 1

Primární klíč existujícího uživatele se nemění. Na prázdném webu se po ověření nového admina odstraní instalační účet ID 1 standardním WordPress API. Toto je bootstrap hygiene, nikoli významný bod skóre.

### DB prefix

Preferovaně před instalací. Volitelná post-install migrace je povolena jen na prokazatelně prázdném Single Site, s fresh backupem, maintenance režimem, atomickou změnou configu, explicitním seznamem tabulek/metadata keys a rollbackem. Kosmetická váha ve skóre je nula.

## 8. Scan engine

### Události

Plugin poslouchá core/plugin/theme updates, user/role changes, plugin activation/deactivation, Application Password changes, Updraft completion a vlastní nastavení. Událost spustí cílený scan, ale nenahrazuje polling.

### Frekvence

- 2× denně: update availability;
- denně: users/API-DB-runtime diff, 2FA, HTTPS, backup, cron, debug, providers;
- týdně: core checksum, unexpected files, permissions, public exposure, headers;
- měsíčně: kompletní audit a policy drift;
- on-demand a po rizikové změně.

Dlouhé úlohy jsou chunkované, resumovatelné, idempotentní a chráněné lockem/heartbeat. Při low traffic webu se doporučí skutečný server cron. Deep GARRY scan, Wordfence scan a Updraft backup se časově nekoordinují souběžně.

### Historie

Ukládají se scan summaries, current findings a pouze změnové eventy. Stejný výsledek aktualizuje `last_seen`; změna stavu/evidence vytváří timeline událost. Výchozí retence: eventy/summaries 365 dní, detail evidence 90 dní. Žádné secrets.

## 9. Detekce administrátorů

Počet a seznam se nebere jen z obrazovky Users:

1. standardní `WP_User_Query` pohled;
2. přímý `$wpdb` seznam `users` a přesně parsované `usermeta` capabilities;
3. runtime ověření efektivních kritických capabilities;
4. Multisite super-admin option;
5. porovnání s uloženým GARRY baseline.

DB user chybějící ve WP API nebo runtime admin bez odpovídající DB role je critical. Externí kontrola mimo běžné pluginy je doporučená pro vysokou důvěru, protože `pre_user_query` a `user_has_cap` jsou legitimní rozšiřující body, které může zneužít malware.

## 10. Backup-gated changes

Rizikové akce mají stavový automat:

`draft → preflight → awaiting_backup → backup_running → awaiting_confirmation → applying → verifying → committed | rolling_back | failed`

Tlačítko spustí Updraft full backup s remote uploadem. GARRY čeká na dokončení všech komponent; pouhé vyvolání akce není důkaz. Fresh token obsahuje backup nonce, čas, components, remote status, change ID a expiraci. Výchozí platnost 30 minut. Warning/partial/failed backup změnu neodemkne.

Rizikové akce zahrnují DB prefix, login route, `wp-config.php`, `.htaccess`, server snippets aplikované z WP, hromadné user/role změny a opravy/mazání souborů. Každá má manifest, očekávaný původní stav, apply/verify/rollback kroky a correlation ID.

## 11. Dashboard

### Karta 1 – Stav zabezpečení

- critical/warning/unknown/conflict v tomto pořadí;
- poslední quick/deep scan a overdue;
- 2FA adminů, HTTPS, update a backup headline;
- trend a poslední změny;
- owner badge GARRY/Wordfence/Updraft/server/manual;
- žádné zavádějící zelené checkboxy bez důkazu.

### Karta 2 – GARRY

- servisní kontakt, environment, site ID;
- GARRY plugin version/update channel;
- poslední a plánovaná údržba;
- baseline účty a jejich stav bez citlivých údajů;
- dokumentace a support link.

### Karta 3 – Aktualizace a zálohy

- počty core/plugin/theme updates a auto-update policy;
- poslední DB/files backup, remote destination, příští schedule;
- poslední chyba, restore drill age a WP-Cron health;
- tlačítko pro Updraft backup podle capability.

Detailní stránka navíc obsahuje control catalog, timeline, provider conflicts, recommendations a export redigovaného reportu. Vlastní Site Health tests zpřístupní kritické kontroly i ve standardním WordPress rozhraní.

## 12. Security score

Skóre je sekundární. Váhy: updates 20 %, auth 20 %, backup 15 %, HTTPS 10 %, users 10 %, files 10 %, firewall/monitoring 10 %, config/surface 5 %. Critical finding stropuje maximální hodnocení. `unknown` nepřidává body. DB prefix, user ID, login obscurity, sample files, robots/llms mají nulovou váhu.

## 13. Crawlers

`robots.txt`, `blog_public/noindex` a `llms.txt` tvoří oddělený modul Crawlers & AI. Presety Production/Staging/Private musí ověřit výslednou HTTP response a konflikt fyzického/virtuálního souboru. Ani `robots.txt`, ani `llms.txt` nejsou bezpečnostní access control a nevstupují do skóre.

## 14. Bezpečnost implementace

- oddělené capabilities `view`, `run_scan`, `apply_safe`, `apply_risky`, `manage_providers`;
- capability + nonce pro každou mutaci;
- validace/allowlist, sanitizace při vstupu a late escaping;
- prepared DB queries a bezpečné parsování serialized arrays;
- REST permission callbacks a minimální response;
- SSRF ochrana a allowlist externích API;
- žádná hesla, salts, 2FA data, tokens, DB/SMTP/cloud credentials v logu/exportu;
- secrets neukládat do autoloaded options;
- žádná telemetrie bez opt-in;
- cizí plugin data se při uninstall nemění;
- update balíček z kontrolovaného HTTPS kanálu, integrity/signing proces a release audit;
- podpora WordPress Recovery Mode a deaktivace přes SFTP;
- lokální log není vydáván za tamper-proof.

### Obranný a AI-safe implementační rámec

Plugin je autorizovaný obranný administrační nástroj pouze pro WordPress weby vlastněné nebo spravované GARRY. Dokumentace, README, hlavička pluginu, threat model i implementační prompty musí účel uvádět výslovně. Citlivé funkce se zadávají po jednom ohraničeném modulu s testy a se zřejmou autorizací.

Zakázané konstrukce: exploity a sken cizích hostů; obfuskace/`eval`/ukryté payloady; webshell nebo shell execution; skryté účty a persistence; bypass oprávnění; sběr credentials/2FA/secrets; libovolné vzdálené příkazy; download-and-execute; covert C2; tiché vypnutí bezpečnostních providerů; self-modifying nebo anti-analysis chování. Remote heartbeat je opt-in read-only stav do allowlisted endpointu s pevným schématem a nemůže přijímat PHP, SQL, shell ani obecné callbacky.

Všechny změny jsou viditelné, iniciované autorizovaným administrátorem, chráněné capability + nonce, auditované a vratné. User wizard vytváří pouze standardní viditelné účty a po smazání je automaticky neobnovuje. „Hidden admin scan“ pouze detekuje rozdíl API/DB/runtime; nikoho neskrývá. File scan je read-only a destruktivní oprava vyžaduje samostatné potvrzení a fresh backup.

Doporučená hlavička implementačních zadání:

> Implementuj autorizovaný obranný WordPress administrační plugin pro weby vlastněné nebo spravované GARRY. Používej WordPress API, viditelné admin UI, přesné capabilities, nonce, audit log a vratné změny. Nevytvářej exploity, obfuskaci, shell/webshell, skryté účty, persistence, vzdálené příkazy, sběr credentials ani obcházení bezpečnostních pluginů. Implementuj pouze tento modul a jeho testy: **[modul]**.

Tato hlavička nemá obcházet ochrany AI; poskytuje kontrolovatelný účel, autorizaci a technické non-goals, aby legitimní hardening nebyl zaměněn za malware nebo útok.

## 15. Testovací matice

### Platforma

- poslední dvě podporované major WordPress řady;
- podporované PHP řady;
- MySQL a MariaDB;
- Apache/LiteSpeed, Nginx a reverzní proxy/CDN;
- Single Site; Multisite nejprve audit-only, dokud nejsou migrační workflow otestována.

### Integrace

- Wordfence Free: firewall modes, 2FA grace/recovery, proxy IP, scan;
- Updraft Free: local/remote, partial failure, cron resume, concurrent backup;
- Turnstile: login, 2FA second step, reset, cache, WooCommerce/AJAX;
- SMTP: success/failure bez čtení credentials;
- Simple History logging API;
- konfliktní i podporovaný modulární fixture Wordfence+AIOS, dvě CAPTCHA, dva SMTP;
- Divi a Elementor editor/preview/AJAX/REST po security/cache změně;
- LiteSpeed cache bypass/purge a optimalizace Turnstile/Clarity/Site Kit skriptů;
- Yoast ownership robots/meta/sitemap;
- Complianz consent integration s Clarity/Site Kit bez duplicitního trackingu;
- ACF REST/public-form read-only audit a private update channels.

### Bezpečnostní testy

- CSRF a privilege escalation u všech akcí;
- XSS ve findings/provider metadata;
- SQL injection a serialized payloady;
- SSRF přes package/scan URL;
- race conditions scanů, backupů a změn;
- skrytý DB admin a runtime capability injection;
- rollback DB prefix/config/login route;
- žádný secret v DB logu, REST, e-mailu nebo exportu;
- žádný manifest/heartbeat/provider response nemůže spustit obecný PHP, SQL nebo shell příkaz;
- GARRY account je viditelný a po ručním smazání nevznikne automaticky znovu;
- nevybrané položky katalogu jsou `not_applicable`, ne falešně chybějící.

## 16. Implementační fáze

### Fáze 1 – audit foundation

Control catalog, scan queue, findings/events, Site Health, tři dashboard karty, users API/DB/runtime diff, update/HTTPS/debug/cron/provider discovery.

### Fáze 2 – provider stack

Wordfence read-only adapter a ownership engine; Updraft status + backup gate; Turnstile/2FA/SMTP discovery; plugin recommendation/install workflow.

### Fáze 3 – safe remediation

Bezpečné WordPress-level opravy, config/server snippet generator, REST/XML-RPC/crawler policy, conflict resolution smoke tests.

### Fáze 4 – bootstrap advanced

GARRY account wizard, odstranění bootstrap ID1 účtu, preinstall WP-CLI recipe a striktně omezená post-install prefix migrace s rollbackem.

### Fáze 5 – fleet operations

Opt-in GARRY remote heartbeat, externí uptime/TLS kontrola, central alerts, podepsané reporty a maintenance přehled. Remote část vyžaduje samostatný threat model a privacy dokument.

## 17. Go-live gates

Plugin není připraven k plošnému nasazení, dokud:

- prošly unit/integration/E2E a rollback testy;
- žádný provider adapter nepoužívá neověřenou tichou mutaci interních options;
- skrytý admin fixture je detekován;
- fresh backup gate blokuje partial/failed backup;
- testovací login nelze zamknout kombinací 2FA/CAPTCHA/rename;
- Multisite nepouští nepodporovanou mutaci;
- privacy/log retention a uninstall jsou zdokumentovány;
- existuje emergency runbook přes SFTP/SSH;
- release balíček projde security code review.

## 18. Primární zdroje

- [WordPress Hardening](https://developer.wordpress.org/advanced-administration/security/hardening/)
- [WordPress Brute Force](https://developer.wordpress.org/advanced-administration/security/brute-force/)
- [WordPress Plugin Security](https://developer.wordpress.org/apis/security/)
- [Site Health tests](https://developer.wordpress.org/reference/hooks/site_status_tests/)
- [Dashboard Widgets API](https://developer.wordpress.org/apis/dashboard-widgets/)
- [Wordfence Free](https://www.wordfence.com/help/wordfence-free/)
- [UpdraftPlus dokumentace](https://teamupdraft.com/documentation/updraftplus/topics/backing-up/)
- [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/)
- [All-In-One Security](https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/)
- [Wordfence – souběh s AIOS](https://wordpress.org/support/topic/aios-and-wordfence/)
- [Divi dokumentace](https://www.elegantthemes.com/documentation/divi/)
- [Elementor](https://wordpress.org/plugins/elementor/)
- [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)
- [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/)
- [Complianz](https://wordpress.org/plugins/complianz-gdpr/)
- [LiteSpeed Cache](https://en-gb.wordpress.org/plugins/litespeed-cache/)
- [Microsoft Clarity](https://wordpress.org/plugins/microsoft-clarity/)
- [Google Site Kit](https://wordpress.org/plugins/google-site-kit/)
- [OWASP Authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Logging](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
- [OWASP HTTP Headers](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html)

## 19. Závazná rozhodnutí tohoto návrhu

1. GARRY je orchestrátor, ne druhá security suite.
2. Schválený katalog není povinný balík; každý web má vlastní verzovaný profil a nevybrané komponenty jsou `not_applicable`.
3. Wordfence je preferovaný primární free security provider; AIOS je alternativa nebo pouze vlastník explicitně nepřekrývajících modulů.
4. UpdraftPlus je primární backup provider a poskytuje gate před rizikovými akcemi.
5. Turnstile je primární CAPTCHA; Wordfence reCAPTCHA se na stejném surface vypne.
6. Počet adminů se ověřuje WP API + raw DB + runtime capabilities + baseline.
7. Tři GARRY admin účty lze vytvořit, ale bez plaintext hesla v e-mailu a s povinným 2FA.
8. User ID se v DB nepřepisuje; instalační účet ID1 se na prázdném webu odstraní standardním API.
9. Prefix se preferovaně nastaví před instalací; post-install migrace je bootstrap-only risk workflow.
10. Scan je periodický, event-driven i logovaný po stavových změnách.
11. Žádná riziková změna bez dokončené čerstvé zálohy, explicitního potvrzení, verifikace a rollbacku.
12. Implementace je výhradně obranná: žádná obfuskace, shell, skryté účty, persistence, credentials collection nebo vzdálené příkazy.
