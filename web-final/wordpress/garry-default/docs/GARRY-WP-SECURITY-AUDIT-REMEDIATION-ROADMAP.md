# GARRY WordPress Security – auditní plán nápravy a rozvoje

> Dokument 6 z dokumentační sady GARRY Security  
> Stav: závazný návrh pro další vývoj  
> Vychází z: bezpečnostního auditu celé sady GARRY pluginů a kontrolovaných referenčních pluginů ve složce `ne-garry`  
> Datum: 2026-08-30

## 1. Účel a závaznost

Tento dokument převádí zjištění auditu do konkrétního pořadí změn pro **GARRY Default / GARRY Security**. Neopakuje produktovou specifikaci ani nahrazuje dokumenty `MASTER-SPEC`, `FUNCTION-MAP`, `BEHAVIOR-SPEC` a `PLUGIN-BLUEPRINT`; určuje, co musí být doplněno nebo změněno, aby se specifikace bezpečně promítla do reálného ekosystému GARRY pluginů.

Při rozporu platí toto pořadí:

1. zákaz ztráty dat, přístupu nebo nezamýšlené změny konfigurace;
2. bezpečnostní kontrakt a non-goals z `MASTER-SPEC`;
3. tento auditní plán;
4. ostatní specifikace GARRY Security;
5. lokální README jednotlivého pluginu.

Tento dokument je návrhový. Žádná položka není oprávněním tiše měnit WordPress, konfiguraci serveru, cizí plugin nebo produkční data. Aktivní změna vždy podléhá režimu, ownership pravidlům, capability, nonce, čerstvé záloze a ověření podle stávajících specifikací.

## 2. Výsledek auditu v jedné větě

GARRY Security má správný směr jako **read-only orchestrátor**, ale před širším nasazením musí nejprve vyřešit kolizi sdíleného frameworku celé sady, zavést jednotný manifest a kompatibilitní kontrakt pro GARRY pluginy a až poté rozšiřovat aktivní hardening workflow.

## 3. Neodkladná rozhodnutí

### 3.0 Závazná oprava architektury: Embedded Framework 2.3

Následující body nahrazují dřívější předpoklad, že GARRY Default/Core bude jediným poskytovatelem shared frameworku. Každý GARRY plugin obsahuje vlastní namespacovanou embedded kopii Frameworku 2.3, lokální Přehled, Info, Log, versioning, ikonu a manifest. Žádný plugin, včetně GARRY Default, nesmí být povinnou závislostí jiného pluginu.

Aktivní pluginy se koordinují pouze přes omezenou datovou request registry. Pro každý sdílený prvek se deterministicky zvolí renderer podle priority a slugu. Volba se nepersistuje; po deaktivaci zvoleného pluginu ji převezme další aktivní kompatibilní kandidát. GARRY Default je samostatný Security účastník, nikoli Core provider.

Podrobná pravidla a aktualizované akceptační testy stanoví dokument GARRY-WP-SECURITY-EMBEDDED-FRAMEWORK-2.3-ADDENDUM.md. Tento dodatek má pro frameworková rozhodnutí přednost před kapitolami 3 a 4 tohoto plánu.

### 3.1 Zastavit další kopírování `Garry_Promotion_Registry`

V některých pluginech je vložena verze frameworku 2.0.0, v jiných 2.1.0. Třída má stejné globální jméno a je chráněná pouze `class_exists()`. Výsledná implementace tak závisí na pořadí načtení pluginů. Novější pluginy volají metody a konstanty, které starší třída nemá; administrace pak může skončit PHP fatal chybou.

**Rozhodnutí:** jediným vlastníkem shared frameworku bude GARRY Default/Core. Ostatní GARRY pluginy nesmějí dodávat kopii stejné třídy ani spoléhat na náhodné pořadí aktivních pluginů.

### 3.2 Oddělit GARRY Core od GARRY Security

`GARRY Default` může zůstat distribučním a onboarding balíčkem, ale logicky obsahuje dvě samostatné odpovědnosti:

- **GARRY Core** – identita, shared registry, manifest discovery, administrační shell, kompatibilitní API;
- **GARRY Security** – kontroly, scan scheduler, evidence, provider registry, policy a změnové workflow.

Bezpečnostní scanner nesmí být jediným důvodem, proč je potřeba základní registry API. Pokud se bezpečnostní modul vypne při incidentu, GARRY mikropluginy musí nadále bezpečně fungovat nebo se bezpečně samy vypnout s administrátorským oznámením.

### 3.3 Z bezpečnostního pluginu nedělat druhý Wordfence

Wordfence, AIOS, UpdraftPlus, Turnstile, FluentSMTP a Complianz mohou být na konkrétním webu vlastníky vybraných schopností. GARRY Security je má detekovat, evidovat a koordinovat. Bez deklarovaného ownershipu nesmí zapnout druhou implementaci WAF, login throttlingu, 2FA, CAPTCHA, změny login URL, XML-RPC blokace, SMTP ani serverových pravidel.

### 3.4 Přijmout „unknown je horší než zelená“

Kontrola bez ověřitelného důkazu má stav `unknown`, nikoliv `pass`. Platí to zejména pro 2FA, vzdálené zálohy, serverová oprávnění, hlavičky za CDN a konfiguraci cizích pluginů v neznámé verzi.

## 4. Program nápravy podle priorit

### Fáze A – release blocker: společný základ ekosystému

Tato fáze musí být hotová dříve, než budou zapnuty další aktivní security akce.

| ID | Požadavek | Jak má fungovat | Akceptační důkaz |
|---|---|---|---|
| `CORE-ARCH-001` | Jedna registry implementace | `Garry_Promotion_Registry` je součástí GARRY Core a používá unikátní namespace. Mikroplugin ji pouze požaduje. | Aktivace celé sady v libovolném pořadí bez warningu a fatal chyby. |
| `CORE-ARCH-002` | Version handshake | Každý plugin deklaruje minimální a testovanou verzi GARRY Core API. Nevyhovující kombinace se bezpečně deaktivuje jen pro vlastní UI/funkce a zobrazí přesnou zprávu. | Integrační test staré/nové kombinace. |
| `CORE-ARCH-003` | GARRY plugin manifest | Každý plugin dodá strojově čitelný lokální manifest podle dokumentu 7. | Scanner zobrazí manifest, verzi, update kanál a kompatibilitu všech aktivních GARRY pluginů. |
| `CORE-ARCH-004` | Release identity | Každý vlastní plugin uvede `Update URI`, vlastního vydavatele a zdokumentovaný update kanál. | Žádná kolize názvu se slugem WordPress.org; scan umí vysvětlit zdroj aktualizace. |
| `CORE-ARCH-005` | Distribuční kontrola | ZIP balíčky používají soubory `0644` a adresáře `0755`; release kontroluje nepřítomnost `.DS_Store`, vývojových klíčů a nechtěných artefaktů. | CI report a test instalace ZIPu na hostingu s odděleným uživatelem webserveru. |

### Fáze B – bezpečnost samotného GARRY Security

| ID | Požadavek | Jak |
|---|---|---|
| `SEC-SELF-001` | Role a capabilities | Zachovat samostatné `garry_security_view`, `garry_security_run_scan`, `garry_security_apply_safe`, `garry_security_apply_risky`, `garry_security_manage_providers`. Každá akce kontroluje capability i nonce na serveru. |
| `SEC-SELF-002` | Bezpečná evidence | Do findings, eventů, exportů, e-mailů ani debug logu se neukládá heslo, salt, recovery code, token, API klíč, SMTP credential, celá cesta k citlivému souboru ani obsah souboru. Ukládá se redigovaný důkaz a jeho hash. |
| `SEC-SELF-003` | XSS-safe administrace | Finding title, provider metadata, manifest, vzdálená HTTP odpověď i text z cizího pluginu se považují za nedůvěryhodný vstup. Všechny výstupy se escapují podle kontextu. |
| `SEC-SELF-004` | Žádné vzdálené příkazy | Volitelný heartbeat je pouze opt-in, používá pevný allowlist a pevné schéma a nikdy neinterpretuje vzdálený JSON jako konfiguraci, HTML, PHP, SQL ani callback. |
| `SEC-SELF-005` | Bezpečný scheduler | Lock nese scan UUID, heartbeat, expiraci a vlastníka. Dlouhé úlohy jsou chunkované, idempotentní a obnovitelné. Při nezdařeném běhu vznikne event, ne tichá zelená karta. |
| `SEC-SELF-006` | Disaster recovery | Každá aktivní změna má manifest původního stavu, preflight, apply, verify a rollback. Vždy existuje ruční recovery postup přes SFTP/WP-CLI. |

### Fáze C – rozšířit read-only scan o nálezy z auditu

Následující kontroly patří do GARRY Security. V první iteraci pouze měří a vysvětlují stav; samy neprovádějí opravu.

| Control ID | Kontrola | Zdroj dat | Stav při problému |
|---|---|---|---|
| `GARRY-001` | Kolize GARRY Core API a duplicitní framework | manifest aktivních pluginů, runtime reflection jen pro vlastní namespace | `critical` |
| `GARRY-002` | Chybějící/neočekávaný `Update URI` a update kanál | hlavička pluginu, manifest, core API | `medium` |
| `GARRY-003` | GARRY plugin bez manifestu nebo bez deklarované kompatibility | lokální discovery | `warning` až `high` podle rizika pluginu |
| `GARRY-004` | Globální admin assety mimo vlastní screen | manifest + kontrola enqueue hooků v interním testu | `medium` |
| `GARRY-005` | Inline JS/CSS a požadované CSP zdroje | manifest a build report, ne heuristické blokování za běhu | `warning` |
| `GARRY-006` | Veřejný AJAX/REST/form surface bez deklarované anti-spam a rate-limit strategie | manifest + cílený adapter | `high` |
| `GARRY-007` | Vlastní CSS nastavitelné editorem | manifest, capability policy | `medium` |
| `GARRY-008` | Neomezený veřejný query/render limit | manifest s `performance_budget` a plugin adapter | `medium` |
| `GARRY-009` | Externí API/font/image origin bez privacy a CSP deklarace | manifest | `warning` |
| `GARRY-010` | Plugin ukládající osobní údaje bez retention/privacy deklarace | manifest | `high` |
| `GARRY-011` | Multisite lifecycle neověřen | manifest + test status release | `warning` |
| `GARRY-012` | Neshoda release hashů a balíčku | CI artefakt / známý release manifest | `high` |

Kontroly `GARRY-004`, `GARRY-005` a `GARRY-012` nejsou vhodné jako drahý runtime scanner na každém webu. Primárním zdrojem má být release CI artefakt uložený lokálně v balíčku; web pouze ověří dostupný manifest a verzi.

### Fáze D – provider adaptery a bezpečné aktivní workflow

Teprve po fázích A–C se doplňují aktivní akce. Každá akce musí být samostatný modul s vlastními testy.

1. **UpdraftPlus adapter:** čtení stavu schedule, poslední DB/files backup, remote upload a chyb; tlačítko „Vytvořit bezpečnostní zálohu“ až po ověření podporované verze adapteru. Žádné čtení cloud credentials.
2. **Wordfence/Two-Factor adapter:** evidence toho, zda existuje právě jeden owner 2FA a login limiting; GARRY nečte recovery kódy ani neposílá 2FA setup za uživatele.
3. **Turnstile adapter:** evidence ownershipu po surfaces (`login`, `waiting_list`, `contact`, `comments`), test validace na serveru a kompatibilita s cache. Nikdy dva CAPTCHA widgety na stejném formuláři.
4. **WordPress core update adapter:** sleduje transients a události, ale nenahrazuje WordPress upgrader ani nenutí auto-updates bez projektového profilu.
5. **HTTP/Site Health adapter:** bezpečně ověřuje HTTPS, redirect, hlavičky, robots/llms response a loopback. SSRF ochrana dovolí pouze vlastní canonical host a explicitní interní targety.
6. **Safe remediation:** může nabídnout například generování snippetů, odkaz na správnou obrazovku, purge cache či ověřený GARRY manifest. Zápis do `wp-config.php`, `.htaccess`, databázový prefix, login route nebo smazání souboru zůstává riziková transakce.

## 5. Povinná data a jejich zdroje

GARRY Security musí data sbírat z nejméně invazivního důvěryhodného zdroje. „Detekce“ neznamená právo cizí konfiguraci měnit.

| Doména | Primární zdroj | Doplňkový zdroj | Zakázané/nevhodné chování |
|---|---|---|---|
| WordPress, pluginy, šablony, překlady | Core API a update transients | `get_plugins()`, Site Health | Neposílat seznam komponent na cizí server bez opt-in. |
| Uživatelé a admini | `WP_User_Query` | přímý `$wpdb` dotaz, runtime `user_can()`, multisite `site_admins` | Neměnit role nebo hesla během scanu. |
| Core integrity | oficiální WordPress checksum API / Wordfence delegated status | lokální hash pouze známých GARRY souborů | Neodstraňovat nalezený soubor automaticky. |
| Soubory a konfigurace | WordPress filesystem API / čitelné konstanty | server-provided status | Nečíst ani nelogovat obsah secrets. |
| Backups | veřejně podporovaný Updraft adapter a jeho completion stav | ruční potvrzení restore drill | Nečíst cloud credentials ani samovolně mazat backup set. |
| 2FA, WAF, login limit | stabilní provider adapter | explicitní manuální attestace s expirací | Neodvozovat stav jen z aktivního pluginu. |
| HTTPS a hlavičky | `wp_safe_remote_request()` na canonical URL | CDN/server metadata a externí monitor | Nenechat URL zadat bez allowlistu; žádný síťový scan cizích hostů. |
| Cache | LiteSpeed/WordPress discovery, manifest | test nonce a cookies na vlastním webu | Nečistit cache bez potvrzení, pokud nejde o bezpečný ověřený krok. |
| Privacy/consent | aktivní Complianz discovery + manifest | projektový profil | Nevydávat právní verdikt. |
| GARRY komponenty | lokální GARRY manifest | CI release evidence | Nestahovat vzdálený konfigurační JSON, který mění chování pluginu. |

## 6. Provozní model a profile webu

Každý web dostane lokální **GARRY Site Profile**. Profil není vzdálený command channel; je to verzovaná, administrátorem schválená konfigurace uložená v databázi nebo deployment repozitáři.

Minimální obsah profilu:

```text
site_id
environment                 production | staging | development
security_mode               audit | standard | strict | custom
approved_components         seznam slugů a očekávaných verzí
capability_owners           např. 2fa -> wordfence, backup -> updraftplus
enabled_surfaces            login, contact, waiting_list, comments, WooCommerce...
external_origins            pouze zdroje schválené pro konkrétní web
backup_rpo                  maximální stáří DB/files zálohy
exceptions                  ID kontroly, důvod, autor, expirace
deployment_reference        release/build identifier
```

Profil nesmí obsahovat hesla, API klíče, recovery kódy, cloud credentials ani plné osobní údaje. Změna profilu je auditovaná, vyžaduje capability `garry_security_manage_providers` a u produkčního režimu explicitní potvrzení.

## 7. Doporučený implementační backlog

### Milník 0 – kompatibilní základ

- přesun registry do GARRY Core;
- zavedení manifestu v GARRY Default a alespoň jednom mikropluginu;
- test aktivace všech GARRY pluginů v různém pořadí;
- oprava balíčkových oprávnění a release metadata;
- zastavení duplicity framework souborů v nových pluginech.

### Milník 1 – audit jako zdroj pravdy

- doplnit kontroly `GARRY-001` až `GARRY-012`;
- dashboard zobrazí stav kompatibility sady jako samostatnou sekci;
- do každého findingu uložit ownera, důkaz, poslední změnu, doporučenou akci a odkaz do dokumentace;
- přidat Site Health testy pro critical GARRY findingy;
- exportovat redigovaný report pro support.

### Milník 2 – veřejné surface a privacy

- manifesty pro veřejné formuláře, AJAX/REST, externí originy, cache a data retention;
- adapter pro sezónní čekací list s anti-spam/rate limit/retention kontrolami;
- kontrola custom CSS policy a admin output safety;
- evidovat zdroje vyžadující Complianz/CSP.

### Milník 3 – provider orchestrace

- stabilní discovery Wordfence, AIOS, UpdraftPlus, Turnstile, FluentSMTP, LiteSpeed, Yoast a Complianz;
- ownership mapa po konkrétních surfaces;
- Updraft fresh-backup gate;
- konflikt dashboard a smoke-test checklist.

### Milník 4 – řízené remediation workflow

- nejprve bezpečné a vratné úkony;
- potom přísně omezené rizikové transakce s preflightem, backupem, maintenance plánem, ověřením a rollbackem;
- DB prefix a změna prvního user ID pouze v Advanced Bootstrap workflow na prázdném, potvrzeném webu.

### Milník 5 – fleet provoz

- opt-in fleet health report bez secrets;
- schválené release manifesty;
- alert deduplikace, eskalace a SLA;
- centrální přehled stavu webů, ale žádný vzdálený execute mechanismus.

## 8. Go-live gates pro GARRY Security

Bezpečnostní plugin může přejít z Audit do Standard pouze pokud:

1. je splněn `CORE-ARCH-001` až `CORE-ARCH-005`;
2. běžel nejméně jeden úspěšný quick scan a jeden deep scan;
3. není kritický konflikt frameworku, dvou WAF, dvou 2FA providerů nebo dvou login CAPTCHA na stejné ploše;
4. provider backupu má ověřený poslední DB i files backup a vyhovuje RPO, nebo je stav viditelně `unknown`/`warning` a režim Standard nebyl falešně potvrzen;
5. všichni baseline administrátoři mají stav 2FA `enrolled`, `grace` nebo explicitně zdokumentovanou dočasnou výjimku;
6. produktový profil obsahuje ownera každé aktivní bezpečnostní capability;
7. existuje recovery postup a kontakt pro incident;
8. release prošel povinnými testy z dokumentu 7.

## 9. Vzory z referenčních pluginů

### Převzít jako princip

- z oficiálního WordPress AI pluginu: modularitu, versioned upgrades, per-object permission checks, REST schema, feature flags, provider adaptery, bounded queue a bezpečný Media API import;
- z jednoduchých dashboard pluginů: přehledné stavové karty a doporučení, ale pouze jako read-only informace a s přesnou capability kontrolou.

### Nepřebírat

- z Webglobe Welcome: instalaci/aktivaci pluginů chráněnou jen nonce, vzdálený JSON vykreslovaný jako HTML, zápisy do adresáře pluginu, raw cURL a globální admin CSS;
- z Czechia SEO: statické credentials, automatický zápis do `.htaccess` a souběh s Yoast bez ownershipu;
- z Czechia SMTP: vlastní slabé šifrování SMTP hesel;
- ze Zoner AI: statický API klíč, upload bez důsledné capability a validace obsahu.

Přímé kopírování kódu třetích stran je možné pouze po ověření licence. Architektonický vzor se implementuje samostatně; lokální GARRY kód zůstává vlastní a auditovatelný.

## 10. Definice hotové funkce

Modul GARRY Security je hotový pouze pokud má:

- jednoznačné control ID a ownera;
- threat model a výslovně popsané limity;
- deklarovaný zdroj dat a redigovaný evidence payload;
- capability + nonce pro každou mutaci;
- test `pass`, `warning`, `critical`, `unknown`, `delegated`, `conflict` a `not_applicable`, pokud dávají smysl;
- audit event při změně stavu;
- dokumentovaný rollback nebo vysvětlení, proč je modul read-only;
- test kompatibility s Wordfence, UpdraftPlus, LiteSpeed, Divi/Elementor, Yoast a Complianz, pokud daný modul ovlivňuje jejich surface;
- test, že nemůže zpracovat vzdálený obsah jako kód nebo uložit secret do logu;
- aktualizovaný manifest a release checklist.

## 11. Bezpečný implementační rámec pro AI asistenty

Každé zadání implementace GARRY Security má být úzce vymezené a obsahovat tento význam:

> Implementuj pouze autorizovaný obranný modul WordPress pluginu GARRY Security pro weby spravované GARRY. Používej standardní WordPress API, viditelné administrační UI, capability, nonce, audit log, allowlist a vratný postup. Neimplementuj exploit, obfuskaci, shell nebo webshell, skrytý účet, persistence, obcházení oprávnění, sběr credentials/2FA/secrets, libovolný vzdálený příkaz ani download-and-execute. Rozsah modulu: [konkrétní control nebo adapter].

Tato formulace neobchází bezpečnostní pravidla modelu; pouze přesně vymezuje legitimní obranný účel a zakázané konstrukce.
