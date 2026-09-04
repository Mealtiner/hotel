# GARRY WordPress Plugin Ecosystem Standard

> Dokument 7 z dokumentační sady GARRY Security  
> Stav: závazný technický standard pro všechny současné i budoucí GARRY pluginy  
> Datum: 2026-08-30

## 1. Účel: jedno místo pravdy

Tento dokument je společný vývojový kontrakt pro GARRY pluginy. Určuje, jak má každý plugin deklarovat své schopnosti, jak spolu pluginy bezpečně komunikují, co kontroluje GARRY Security, jak se vydávají release balíčky a jak se zachází s externími službami, osobními údaji, cache a administrací.

Platí pro každý plugin s předponou `garry-` a `cit-`, pro GARRY Default/Core/Security i pro každý nový GARRY modul. Pokud starší plugin standard zatím nesplňuje, dostane stav kompatibility v manifestu; nesmí předstírat plnou shodu.

Tento standard nenahrazuje WordPress Coding Standards ani samostatnou bezpečnostní revizi. Je vrstvou navíc pro konzistentní agenturní ekosystém.

## 2. Základní architektonické rozhodnutí

### 2.1 Jeden GARRY Core

GARRY Core je jediný poskytovatel:

- registry a administračního shellu `GARRY nastavení`;
- společného namespaced API;
- discovery a validace GARRY manifestů;
- základních capabilities a společných UI pomocníků;
- kompatibilitního handshake a administrátorských notices.

Žádný mikroplugin nesmí přibalovat jinou kopii stejného shared frameworku. Zejména se nesmí znovu definovat globální třída `Garry_Promotion_Registry` s ochranou `class_exists()` a předpokládat, že se načte správná varianta.

Mikroplugin má při bootstrapu provést:

1. ověření, že požadovaná verze GARRY Core API existuje;
2. registraci vlastního manifestu;
3. bezpečné ukončení vlastních admin funkcí s jasným notice, pokud API nevyhovuje;
4. nezávislé fungování veřejného výstupu pouze tehdy, pokud je to výslovně podporovaný fallback.

Neprovádí vlastní fallbackovou definici Core třídy.

### 2.2 Namespace a prefixy

Nový kód používá namespace, například `Garry\Plugin\PhotoGallery`. Starší globální symboly se při migraci nešíří dál.

Každý plugin má unikátní prefix pro:

- options;
- transients;
- cron hooks;
- capabilities;
- REST namespace;
- AJAX action;
- nonce action;
- CSS class a JavaScript handle;
- databázové tabulky;
- text domain;
- upload adresáře a metadata keys.

Prefix nesmí být obecný (`settings`, `security`, `admin`) ani sdílený bez schváleného Core API.

### 2.3 SemVer a kompatibilita

Plugin deklaruje svou verzi, minimální WordPress/PHP verzi, verzi Core API a kompatibilní rozsah. Nekompatibilní major změna Core API vyžaduje nový major nebo explicitní migration layer.

`Update URI` musí jasně identifikovat vlastní distribuční kanál. Slug, adresář, hlavní soubor a manifest identity musejí být konzistentní.

## 3. Povinný lokální manifest

Každý GARRY plugin obsahuje lokální, verzovaný a neexekuční soubor `garry-plugin-manifest.json`. Manifest se nikdy nestahuje z internetu a žádný vzdálený obsah jej nesmí přepisovat nebo rozšiřovat.

### 3.1 Minimální schéma

```json
{
  "schema_version": 1,
  "identity": {
    "slug": "garry-example",
    "name": "GARRY Example",
    "plugin_version": "1.0.0",
    "update_uri": "https://updates.garry.example/garry-example",
    "core_api": { "min": "3.0.0", "tested_max": "3.x" }
  },
  "runtime": {
    "min_wordpress": "6.6",
    "min_php": "7.4",
    "multisite": "audit_only | supported | unsupported",
    "public_surface": true
  },
  "capabilities": {
    "wordpress": ["edit_posts"],
    "garry": ["garry_example_manage"]
  },
  "interfaces": {
    "admin_screens": ["garry-settings_page_garry-example"],
    "ajax": [],
    "rest": [],
    "shortcodes": [],
    "blocks_or_widgets": ["elementor:garry-example"]
  },
  "security": {
    "custom_css": "none | admin_only | restricted",
    "public_input": [],
    "rate_limit": "not_applicable",
    "file_operations": "none",
    "network_requests": "none"
  },
  "integrations": {
    "requires": [],
    "optional": ["elementor", "divi"],
    "ownership_claims": []
  },
  "privacy": {
    "personal_data": "none",
    "retention": "not_applicable",
    "external_origins": [],
    "complianz_notes": []
  },
  "operations": {
    "cache": { "nonce_sensitive": false, "purge_required_after_change": false },
    "csp": { "script_src": [], "style_src": [], "connect_src": [], "img_src": [], "font_src": [] },
    "performance_budget": { "max_public_items": 24, "remote_cache_ttl_seconds": 0 }
  },
  "lifecycle": {
    "options": [],
    "tables": [],
    "cron_hooks": [],
    "uninstall": "single_site | multisite_supported | retains_data_by_choice"
  }
}
```

Hodnoty jsou allowlistované. Manifest neobsahuje credential, interní IP, kompletní cesty, e-mail návštěvníka, licence ani vzdálený příkaz.

### 3.2 Proč manifest existuje

Manifest je zdroj pravdy pro GARRY Security a release CI. Umožní:

- přesně zobrazit, co plugin vlastní a co nikoliv;
- najít kolize mezi pluginy;
- generovat CSP, privacy a cache checklist;
- vyžadovat testy podle rizika;
- zjistit, zda je upgrade kompatibilní;
- rozlišit běžný obsahový plugin od veřejného formuláře nebo síťového adapteru.

Manifest není bezpečnostní hranice. Hodnoty deklarované pluginem se ověřují testy a případně adaptorem. Je-li manifest chybějící nebo rozporuplný, GARRY Security vrátí `unknown` nebo `warning`.

## 4. Globální povinné bezpečnostní standardy

### 4.1 Přímé spuštění a bootstrap

Každý vykonatelný PHP soubor má `ABSPATH` nebo `WPINC` guard. `uninstall.php` ověřuje `WP_UNINSTALL_PLUGIN`.

Plugin nesmí při načtení:

- provádět síťové volání;
- zapisovat do souborů;
- měnit options bez explicitní akce nebo lifecycle hooku;
- zobrazovat globální admin notice bez capability a relevance;
- definovat společný framework symbol, pokud nepatří do GARRY Core.

### 4.2 Oprávnění, nonce a mutace

Nonce chrání před CSRF, nenahrazuje autorizaci. Každá změna má na serveru:

1. přesnou capability;
2. nonce;
3. validaci vstupu;
4. auditovatelný výsledek;
5. bezpečnou odpověď přes `wp_send_json_success()` / `wp_send_json_error()` nebo standardní redirect.

Neinstalovat, neaktivovat ani neupdatovat jiné pluginy z GARRY pluginu bez explicitně schváleného workflow, `install_plugins`/`activate_plugins`/`update_plugins`, lokálního allowlistu a potvrzení administrátora. UI nesmí ukázat akci uživateli, který pro ni nemá capability.

### 4.3 Vstup a výstup

- Sanitizovat při vstupu podle významu dat, ne jednou univerzální funkcí.
- Escapovat pozdě podle kontextu: `esc_html`, `esc_attr`, `esc_url`, `wp_kses`.
- Nikdy nevkládat uživatelský nebo vzdálený text do `innerHTML` / jQuery `.html()`; pro prostý text použít `textContent`.
- Rich text musí použít úzký `wp_kses` allowlist; nepoužívat neomezený HTML vstup.
- SVG, HTML a JSON z internetu nejsou důvěryhodné assety. Nevykreslují se bez validace a bez lokálního, verzovaného zdroje.

### 4.4 AJAX, REST a veřejné formuláře

Každý endpoint je deklarován v manifestu s povoleným publikem, capability, nonce strategií a limitem.

Veřejný endpoint navíc potřebuje:

- serverovou anti-spam strategii; honeypot je pouze doplněk;
- rate limit podle IP a action, bez dlouhodobého ukládání plné IP pokud to není nutné;
- formulářový scope a maximální velikost vstupu;
- ochranu proti opakovanému odeslání;
- bezpečnou chybovou odpověď bez interních detailů;
- privacy a retention deklaraci;
- cache test, pokud stránku obsluhuje LiteSpeed nebo CDN.

Pro čekací list je doporučen Wordfence/edge rate limit a Turnstile nebo ochrana poskytovaná formulářovým pluginem. Nonce u nepřihlášeného návštěvníka není CAPTCHA ani rate limiting.

### 4.5 Databáze, options a osobní údaje

- Všechny proměnné SQL hodnoty jdou přes `$wpdb->prepare()`.
- Názvy tabulek se skládají jen z důvěryhodného `$wpdb->prefix` a předem daného suffixu.
- `unserialize()` se nepoužívá, pokud není nezbytný pro kompatibilitu; pak pouze s `allowed_classes => false` a následnou validací struktury.
- Secrets nejsou v autoloaded options ani v logu.
- Každé ukládání osobních údajů deklaruje účel, minimální strukturu, retenci, export a výmaz přes WordPress privacy API.
- Uložení IP adresy je nutné zdůvodnit; preferuje se hash nebo zkrácená síťová informace, pokud postačí pro rate limit.

### 4.6 Soubory, upload a fonty

Povoleny jsou pouze úzce vymezené operace deklarované v manifestu.

- Používat WordPress Filesystem/Media API, ne vlastní `file_put_contents()` do adresáře pluginu.
- Nahraný soubor ověřit podle obsahu i přípony, velikosti, MIME a oprávnění uživatele.
- Nezapisovat data z externí URL jako důvěryhodný asset bez validace.
- Font upload je pouze pro správce s odpovídajícím oprávněním, s allowlistem formátů a velikostí; preferují se Media Library attachment IDs před libovolnou URL.
- Nikdy nenahrávat ani neprovádět PHP soubory.

### 4.7 Síť a externí služby

Používat WordPress HTTP API (`wp_safe_remote_get`, `wp_safe_remote_request`) s timeoutem, limitem odpovědi, kontrolou HTTP kódu a Content-Type. Externí origin musí být v manifestu a v project profilu.

Je zakázáno:

- raw cURL bez nutné technické výjimky;
- URL z neověřeného requestu;
- obecný proxy endpoint;
- download-and-execute;
- vzdálená konfigurace, která se bez lokálního schválení projeví jako PHP, HTML, JS nebo akce;
- vkládání statických API klíčů, bearer tokenů a hesel do zdrojového kódu.

V případě externího API se musí definovat cache TTL, fallback, provozní limit, privacy důvod a CSP directive. Prohlížečové volání Open-Meteo, externí font nebo obraz z jiné domény se počítají jako externí origin.

### 4.8 Admin UI, assety a CSP

Admin CSS/JS se načítá pouze na vlastním screenu, Elementor/Divi editoru nebo konkrétní stránce, kde je skutečně nutný. Selektory jsou scopeované pod vlastní root class; žádné globální `.wrap h1`, `:root`, obecné `button` nebo přepis WordPress UI.

Nový kód nemá přidávat inline JavaScript/CSS. Pokud je dočasně nutný, manifest uvádí přesný důvod a cesta k budoucímu odstranění. GARRY Security nepovolí striktní CSP v enforce režimu, dokud manifesty a integrační testy neprokážou kompatibilitu s Divi, Elementor, LiteSpeed, Yoast, Site Kit, Clarity a Complianz.

### 4.9 Cache a výkon

Každý plugin deklaruje:

- zda pracuje s nonce/session/2FA/CAPTCHA;
- zda potřebuje cache bypass, ESI nebo dynamické načtení;
- zda vyžaduje purge po změně;
- maximální počet veřejně vykreslených položek;
- cache TTL externích dat;
- dlouhé background jobs a jejich lock.

`posts_per_page = -1` na veřejném frontendu není povoleno bez výslovné výjimky, pagination a performance testu. Všechny seznamy mají rozumný default a horní limit.

### 4.10 Lifecycle, multisite a cleanup

Activation/deactivation/uninstall je idempotentní. Každý plugin dokumentuje options, transients, cron hooks, tabulky, metadata a soubory, které vytváří.

Pokud plugin deklaruje multisite podporu, uninstall i upgrade migrují všechny sites bezpečně s `switch_to_blog()` a `restore_current_blog()`. Pokud multisite není otestován, manifest uvádí `unsupported` nebo `audit_only`.

Odinstalace nikdy nemaže data cizího pluginu, backupy, credentials nebo GARRY Core data bez jasného vlastnictví a potvrzení.

## 5. Globální ownership pravidla

GARRY pluginy standardně vlastní pouze svůj obsahový nebo prezentační scope. Bezpečnostní capability spravuje jeden deklarovaný owner podle GARRY Site Profile.

| Capability / scope | Povolený primární owner | Pravidlo |
|---|---|---|
| WAF a malware scan | Wordfence nebo jiná jedna suite | GARRY obsahové pluginy do něj nezasahují. |
| Login throttling | Wordfence nebo edge WAF | Neimplementovat druhý limit bez profile ownershipu. |
| 2FA administrátorů | Wordfence nebo Two-Factor | GARRY Security pouze zjišťuje stav. |
| CAPTCHA loginu | Turnstile nebo Wordfence | Jeden widget a jeden server validator pro jeden surface. |
| CAPTCHA veřejného formuláře | Turnstile / formulářový plugin | Může být jiný než login, ale je deklarovaný. |
| Backup | UpdraftPlus nebo jiný adapter | GARRY Security je orchestrátor/gate, ne druhý backup engine. |
| SMTP | FluentSMTP nebo vybraný transport | Žádný GARRY plugin neukládá vlastní SMTP secret. |
| Robots, sitemap, SEO meta | Yoast pokud je aktivní | GARRY crawler modul jen čte, pokud není výslovně owner. |
| Consent | Complianz pokud je aktivní | GARRY uvádí technická data, neřídí souhlasy paralelně. |
| Cache | LiteSpeed Cache/CDN | Plugin deklaruje potřebu, cache provider provádí purge. |

## 6. Doporučení po jednotlivých GARRY pluginech

Níže jsou změny, které mají být postupně provedeny nad rámec obecného standardu. Priorita znamená pořadí doporučené práce, ne potvrzení zneužitelnosti.

| Plugin | Priorita | Povinné úpravy |
|---|---:|---|
| `garry-default` / GARRY Security | P0 | Stát se jediným GARRY Core ownerem; odstranit duplicitní framework; zavést manifest discovery, version handshake, release gate a kontroly `GARRY-001` až `GARRY-012`. |
| `cit-divi-toggle-text` | P1 | Migrovat na Core API a manifest; deklarovat Divi surface a assety; doplnit `Update URI`, multisite stav a release metadata. |
| `cit-informacni-bublina` | P1 | Nahradit admin preview přes `.html()` bezpečným DOM vykreslením; externí obrázky preferovat jako Media Library attachment, jinak deklarovat origin/privacy/CSP. |
| `garry-bocni-posuvnik` | P1 | Migrovat na Core API; odstranit `innerHTML` z preview; rozsah admin assetů omezit na vlastní screen; deklarovat externí assety. |
| `garry-denni-menu` | P0 | Opravit závislost na frameworku 2.1 prostřednictvím Core handshake; odstranit HTML interpolaci preview; omezit počet položek a validovat strukturu menu. |
| `garry-foto-carousel` | P1 | Zrušit nebo výrazně omezit editorové custom CSS; nepovolit `@import`, externí `url()`, globální selektory ani overlay pravidla; deklarovat Elementor a performance budget. |
| `garry-foto-galerie` | P1 | Stejná CSS policy jako carousel; limit velikosti a počtu obrázků; externí obrázky řešit přes Media Library nebo allowlist originů. |
| `garry-hero-krivka` | P1 | Migrovat na Core API, manifest, scope assetů a release identity. |
| `garry-kategorie-pokoju` | P0 | Opravit Core 2.1 závislost; rich text omezit úzkým allowlistem; u externích obrázků deklarovat origin/privacy; limitovat počet řádků a velikost option dat. |
| `garry-novinkovy-prehled` | P1 | Stejná restricted custom CSS policy; manifest pro Elementor, query limit, cache a public render budget. |
| `garry-prispevkova-matice` | P0 | Zakázat veřejné `posts_per_page = -1`; zavést maximální limit, pagination/lazy load a performance test na velkém webu; omezit custom CSS. |
| `garry-prispevkovy-carousel` | P1 | Restricted custom CSS, limit query a položek, manifest pro Elementor a cache. |
| `garry-sezona-cekaci-list` | P0 | Zavést serverový rate limit, Turnstile/anti-spam ownership, idempotenci, limit vstupu, privacy text, retention, export/erase integraci a cache-safe nonce workflow. E-mail odesílání koordinovat s Fluent Forms, aby nevznikaly duplicity. |
| `garry-situace-na-trati` | P1 | Validovat latitude/longitude jako čísla v rozsahu; cacheovat Open-Meteo; deklarovat `connect-src`, privacy a fallback; odstranit `innerHTML` z preview; nepřenášet zbytečně data návštěvníka do externí služby. |
| `garry-typografie` | P1 | Font upload jen s přísnou capability, MIME/content/size validací a allowlistem; preferovat attachment ID před URL; externí fonty deklarovat v privacy/CSP; zkontrolovat scope CSS selektorů. |

### 6.1 Společné změny pro všechny pluginy

Každý plugin musí v jednom release dodat:

1. manifest;
2. `Update URI` a konzistentní verze;
3. minimální Core API a graceful compatibility failure;
4. ABSPATH guard ve vykonatelných souborech;
5. capability + nonce pro mutace;
6. dokumentaci lifecycle a multisite stavu;
7. seznam externích originů, osobních údajů, endpointů a cache/CSP potřeb;
8. testovací důkaz z release pipeline.

## 7. Globální kontroly napříč pluginy

Tyto kontroly provádí kombinace GARRY Security, release CI a manuální code review. Jedna runtime kontrola nemůže nahradit review zdrojového kódu.

| Oblast | Kde se ověřuje | Co je důkaz |
|---|---|---|
| Core API kompatibilita | runtime + integrační test | manifest handshake a aktivace v různém pořadí |
| Aktualizační kanál | release CI + runtime | `Update URI`, release manifest, známý zdroj |
| PHP syntaxe a standard | CI | `php -l`, coding standards, statická analýza |
| Nebezpečné konstrukce | CI/code review | žádné `eval`, shell, obfuskované payloady, statické secrets, neřízený `unserialize` |
| AJAX/REST oprávnění | automatizované testy | capability, nonce, REST permission callback, guest test |
| XSS | testy/code review | escapování, žádné nedůvěryhodné `innerHTML`, allowlist rich text |
| SQL a options | CI/testy | prepared statements, sanitizace, non-autoload secrets |
| Externí komunikace | manifest + test | allowlist originů, HTTP API, timeout, validace odpovědi, privacy |
| Veřejné formuláře | integration test | rate limit, CAPTCHA owner, cache, retention, e-mail idempotence |
| Cache/CSP | staging smoke test | Divi/Elementor/editor/frontend, LiteSpeed purge/bypass, CSP report-only |
| Výkon | staging benchmark | query limit, počet renderovaných položek, payload, cache TTL |
| Lifecycle | instalační test | activate/deactivate/uninstall na single i deklarovaném multisite |
| Release ZIP | CI | file modes, žádné `.DS_Store`, žádné dev secrets, inventory/hash |

## 8. Zdroje informací a dat

### 8.1 V runtime na WordPress webu

- WordPress Core API: verze, update transients, aktivní pluginy/šablony, cron, Site Health, uživatelé a roles.
- Přímý `$wpdb` pouze pro jasně ohraničené read-only kontroly, například porovnání adminů s usermeta.
- GARRY manifesty pro capability, endpointy, originy, privacy, cache a lifecycle.
- Stabilní adaptery Wordfence, UpdraftPlus, LiteSpeed, Yoast, Complianz a dalších schválených providerů.
- `wp_safe_remote_request()` pouze k vlastní canonical URL nebo pevně schválenému originu.

### 8.2 V release CI

- source inventory, dependencies a licence;
- statická analýza a testy;
- build manifest s hashi vydaných souborů;
- ZIP file modes;
- test čisté instalace a upgrade;
- test s kombinací běžného GARRY katalogu;
- changelog, version a update channel validation.

### 8.3 Mimo WordPress

Citlivé skutečnosti, které lokální plugin nemůže spolehlivě doložit, se berou z externího, schváleného zdroje:

- server cron z hostingu;
- restore drill z provozního procesu;
- DNS/TLS/CDN konfigurace;
- hosting permissions a DB firewall;
- monitoring dostupnosti;
- password vault pro servisní účet a recovery data.

GARRY Security tyto údaje může evidovat jako časově omezené potvrzení nebo odkaz na provozní záznam; nesmí předstírat vlastní ověření.

## 9. Release workflow

1. Vývojář vytvoří nebo aktualizuje manifest a threat model modulu.
2. CI ověří syntax, standardy, manifest schéma, závislosti, statické bezpečnostní zákazy, testy a ZIP obsah.
3. Na stagingu se provede integrační smoke test s profilem webu: Wordfence/AIOS podle zvoleného ownera, UpdraftPlus, LiteSpeed, Divi nebo Elementor, Yoast, Complianz, ACF, Site Kit a Clarity podle relevance.
4. Reviewer zkontroluje privacy, externí originy, capability a změny lifecycle.
5. Release dostane verzi, changelog, hash manifest a update channel.
6. Produkční nasazení vytvoří audit event a GARRY Security provede cílený post-deploy scan.

Riziková změna pluginu, která ovlivňuje login, formulář, databázi, soubory, cache, CSP nebo externí API, má před produkcí potvrzenou zálohu a rollback plán.

## 10. Akceptační kritéria nového GARRY pluginu

Nový plugin nesmí být označen jako GARRY Standard-ready, pokud nemá:

- schválený use case a jednoznačného vlastníka;
- manifest a Core API handshake;
- bezpečné capability/nonce workflow;
- žádný statický secret;
- žádný nevalidovaný vzdálený obsah vykreslovaný jako HTML;
- žádné globální admin assety;
- dokumentované endpointy, privacy, externí originy, cache a CSP;
- limitovaný veřejný query/render výkon;
- lifecycle a uninstall test;
- staging test s relevantními GARRY komponentami;
- release ZIP bez nevhodných souborů a s odpovídajícími oprávněními;
- aktualizovaný changelog a `Update URI`.

## 11. Co tento standard výslovně zakazuje

- kopii společného frameworku v mikropluginu;
- aktivní bezpečnostní zásah do cizího pluginu bez ownershipu a potvrzení;
- instalaci/aktivaci pluginu pouze na základě nonce;
- vzdálený JSON/HTML, který může změnit admin UI nebo akce bez lokální validace;
- statické credentials v repozitáři;
- raw cURL a zápisy do plugin adresáře pro aktualizaci obsahu;
- `eval`, obfuskaci, shell/webshell, hidden account, persistence, libovolný vzdálený příkaz nebo sběr credentials;
- `innerHTML` s nedůvěryhodným obsahem;
- neomezený veřejný query bez performance výjimky;
- dvě současně aktivní implementace stejného security scope bez profilového rozhodnutí;
- deklarovat `pass`, pokud stav není prokazatelný.

## 12. Údržba tohoto zdroje pravdy

Tento dokument se aktualizuje při:

- přidání nebo odstranění GARRY pluginu;
- změně Core API;
- nalezení bezpečnostního incidentu nebo závažného auditu;
- přidání externího providera nebo veřejného endpointu;
- změně release procesu;
- novém požadavku na privacy, cache, CSP nebo multisite.

Každá změna má datum, stručné odůvodnění a odkaz na manifest/release, kterého se týká. Dokumentace je součástí release review, nikoliv následná administrativní poznámka.

