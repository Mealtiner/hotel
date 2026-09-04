# GARRY – Default

Verze: 2.2.0

Výchozí základní plugin agentury **GARRY Promotion**. Instaluje se na **všechny weby**, které agentura vytváří. Obsahuje **GARRY Security** – bezpečnostní audit orchestrátor podle zadání v `docs/GARRY-WP-SECURITY-*.md`.

## Stav tohoto vydání

**Fáze 1 – audit foundation** (viz `docs/GARRY-WP-SECURITY-MASTER-SPEC.md`, sekce 16):

- napojení na sdílené administrační menu **„GARRY nastavení"** se záložkami **Přehled**, **Zabezpečení** a **Info**,
- databázové schéma pro scans/findings/events (`Garry_Security_DB`),
- katalog kontrol a read-only collectory: prostředí, jádro/aktualizace, HTTPS, vícezdrojová detekce administrátorů (WP API vs. přímá DB kontrola vs. runtime capabilities), stav WP-Cron scheduleru,
- scan engine s ochranou proti souběžnému běhu (`Garry_Security_Scanner`),
- denní scan přes WP-Cron (`Garry_Security_Cron`) + tlačítko „Spustit scan nyní",
- dashboard se třemi kartami (Stav zabezpečení / GARRY / Aktualizace a zálohy) a tabulkou aktuálních nálezů,
- vlastní capabilities `garry_security_view` a `garry_security_run_scan` přidané roli Administrator.

**Fáze 2 – provider stack** (viz master spec, sekce 16):

- **Provider registry** (`security-providers.php`) – 9 schválených pluginů z blueprintu (Wordfence, UpdraftPlus, Simple Cloudflare Turnstile, Two-Factor, FluentSMTP, WP Mail SMTP, Simple History, All-In-One Security, Patchstack) s discovery (`PROV-*` nálezy),
- **Ownership/conflict engine** (`security-ownership.php`) – pro capability `firewall`, `malware_scan`, `login_rate_limit`, `two_factor`, `captcha_login`, `backup`, `smtp` detekuje, zda má právě jednoho aktivního vlastníka; dva současně aktivní = `conflict` finding (`OWN-*`). Wordfence záměrně nenabízí `captcha_login` (jeho reCAPTCHA je ve výchozím profilu vypnutá a bez ověřeného čtení jeho options by šlo o neověřený, tedy falešný konflikt s Turnstile),
- **UpdraftPlus backup gate** (`Garry_Security_Backup_Gate`) – tlačítko „Vytvořit bezpečnostní zálohu" spouští `updraft_backupnow_backup_all` po feature detection. Od 1.6.0 navíc best-effort čte vlastní historii záloh UpdraftPlus (`updraft_backup_history`, jen nejobecnější tvar) – i tak nikdy netvrdí „hotovo" bez důkazu, jen ukazuje poslední záznam a odkazuje na UpdraftPlus vlastní historii k ručnímu ověření,
- **Bezpečná instalace/aktivace doporučených pluginů** – sekce „Doporučené pluginy" na dashboardu, instalace jen přes `Plugin_Upgrader` a `plugins_api()` (adresa balíčku se vždy dotazuje čerstvě na WordPress.org, nikdy se nepřebírá z requestu), jen pro slug z registru; instalace a aktivace jsou dva oddělené kroky s vlastním potvrzením.

**Kompatibilita ekosystému GARRY pluginů** (viz `docs/GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md` a `docs/GARRY-WP-PLUGIN-ECOSYSTEM-STANDARD.md`) – auditní zjištění, že tehdy sdílený `Garry_Promotion_Registry` byl v každém GARRY pluginu vlastní kopie chráněná jen `class_exists()`, takže při různých verzích hrozilo PHP fatal chybou podle pořadí načtení. Od verze 1.4.0 (GARRY Embedded Framework 2.3, viz sekce „Sdílený GARRY rámec" níže) je tohle riziko odstraněné architektonicky – version handshake tedy už není potřeba:

- **`garry-plugin-manifest.json`** – lokální, neexekuční manifest identity/capabilities/lifecycle podle schématu z ekosystémového standardu, sekce 3.1,
- **Kontroly GARRY-001/002/003** (`security-ecosystem.php`) – read-only detekce duplicitních kopií frameworku napříč aktivními `garry-`/`cit-` pluginy (a jestli mají stejnou verzi), chybějícího `Update URI` a chybějícího lokálního manifestu; vlastní samostatnou sekci „Kompatibilita ekosystému" na dashboardu,
- **Rozšířené capabilities** (SEC-SELF-001) – přidány `garry_security_apply_safe`, `garry_security_apply_risky`, `garry_security_manage_providers`; instalace/aktivace pluginů a vyžádání zálohy nově vyžadují `garry_security_manage_providers` navíc k odpovídající WP capabilitě,
- **Tvrdší scan lock** (SEC-SELF-005) – transient nese scan UUID, vlastníka (user ID nebo `cron`) a expiraci, ne jen holý timestamp.

**Fáze C – rozšířený audit ekosystému** (viz auditní plán, sekce 4):

- **Kontroly GARRY-004 až GARRY-012** (`security-hardening.php`) – nad lokálními manifesty aktivních GARRY pluginů: admin assety mimo vlastní obrazovku, deklarace inline JS/CSS (podklad budoucí CSP politiky), anti-spam/rate-limit u veřejných AJAX/REST/form vstupů, vlastní CSS nastavitelné editorem, neomezený veřejný dotaz, formát externích origin, retenční politika osobních údajů, ověřený stav Multisite, shoda verze manifestu se skutečně nasazeným pluginem. Vlastní sekce „Rozšířený audit ekosystému" na dashboardu.

**Fáze D – aktivní workflow (zahájeno)**:

- **HTTP/Site Health adapter** (`security-http-health.php`) – HTTP→HTTPS redirect, doporučené bezpečnostní hlavičky, loopback (deleguje na `WP_Site_Health`, neduplikuje ho). Všechny požadavky míří výhradně na vlastní `home_url()` – žádné SSRF riziko,
- **CORE-008** – politika automatických aktualizací jádra; jen sleduje, nikdy nevynucuje,
- **Safe remediation** – sekce „Doporučené kroky" na dashboardu nabízí kopírovatelné `wp-config.php` snippety pro pár dobře známých nálezů (WP_DEBUG, display_errors, DISALLOW_FILE_EDIT, FORCE_SSL_ADMIN). GARRY nic nezapisuje sám.

Adaptéry na skutečný interní stav Wordfence/Two-Factor/Turnstile (nad rámec už existujícího ownership/conflict enginu) zatím chybí – vyžadovaly by spoléhat na nedokumentované interní API cizích pluginů bez možnosti to ověřit proti živé instalaci.

**Přístup a účty** (`security-access-hardening.php`, od 2.1.0) – GARRY od téhle verze sám sleduje čas posledního přihlášení administrátorů (`wp_login` hook, jen usermeta, žádná IP/e-mail), a na tomhle vlastním, poctivě „sledování teprve začíná" označeném základu staví ACC-001 (poslední přihlášení), ACC-002 (neaktivní účty, pevný práh 180 dní) a ACC-003 (počet Application Passwords). **Vystavení webu** (`security-exposure.php`, od 2.1.0) – WEB-003 (dostupnost xmlrpc.php), WEB-004 (enumerace uživatelů přes REST API), WEB-005 (enumerace přes `?author=`), WEB-006 (platnost TLS certifikátu, čteno přímo z TLS handshake). Všechny self-requesty míří výhradně na vlastní `home_url()`.

**Non-goals dosud** (viz function-map, sekce 7, a auditní plán, Fáze D): žádné automatické zásahy do cizích options, žádná aktivní ochrana (WAF, DB prefix migrace), žádné automatické potvrzení dokončení zálohy (jen best-effort informativní odhad), žádný automatický zápis do `wp-config.php` ani `.htaccess` (od 1.7.0 GARRY zapisuje jen do vlastního, izolovaného `wp-content/mu-plugins/garry-security-hardening.php` – viz „Jednokliknové bezpečné opravy" výše), žádná úprava sourozeneckých GARRY pluginů (mimo rozsah tohoto pluginu) – to jsou další kroky.

## Instalace

1. **Pluginy → Přidat nový → Nahrát plugin** → `garry-default.zip`.
2. Aktivujte. V levém menu se objeví (nebo rozšíří, pokud už je aktivní jiný GARRY plugin) položka **GARRY nastavení** s podpoložkou **Zabezpečení**.
3. Na stránce Zabezpečení spusťte první scan tlačítkem „Spustit scan nyní" (další scany běží automaticky jednou denně přes WP-Cron).

## Kompatibilita

- **WordPress:** 7.0 a novější (vynuceno hlavičkou `Requires at least` i vlastní kontrolou při aktivaci).
- **PHP:** 7.4 a novější, včetně aktuálních verzí PHP 8.x. Kód záměrně nepoužívá syntaxi vyžadující PHP 8+ (enumy, `readonly`, `match`, union types apod.).
- **DB:** MySQL a MariaDB (schéma přes `dbDelta`, žádné nepřenositelné konstrukty).

## Bezpečnost

- **`ABSPATH`** guard ve všech souborech, `uninstall.php` navíc guard na `WP_UNINSTALL_PLUGIN` – žádný soubor nelze spustit přímo.
- **Kontrola minimálních požadavků** při aktivaci; při nesplnění se aktivace bezpečně odmítne a plugin se sám deaktivuje.
- **Vlastní capabilities** (`garry_security_view`, `garry_security_run_scan`, `garry_security_apply_safe`, `garry_security_apply_risky`, `garry_security_manage_providers`) místo plošného `manage_options` – přesně podle GARRY Security master specu, sekce 14, a auditního SEC-SELF-001.
- **Nonce + capability** na každé mutaci (tlačítko „Spustit scan" prochází přes `admin-post.php`, `check_admin_referer()` a `current_user_can()`).
- **Read-only audit** – collectory nic v prostředí nemění, jen čtou a zapisují do vlastních tabulek. Přímý DB dotaz na administrátory záměrně obchází `WP_User_Query`, protože tu lze ovlivnit hookem `pre_user_query` – to je podstata detekce skrytých účtů, ne obcházení bezpečnosti.
- **Bezpečná deserializace**: `unserialize()` nad usermeta capabilities běží vždy s `allowed_classes => false`.
- **Prepared statements**: všechny dotazy s uživatelsky ovlivnitelnými hodnotami jdou přes `$wpdb->prepare()`; názvy tabulek se skládají z `$wpdb->prefix`, nikdy nejsou hardcoded ani odvozené z uživatelského vstupu.
- **Žádné secrets**: evidence u nálezů obsahuje jen neutrální strukturovaná data (počty, verze, booleans) – nikdy hesla, tokeny ani jiné citlivé údaje.
- **Instalace pluginů jen z WordPress.org**: adresa balíčku se vždy dotazuje čerstvě přes `plugins_api()`; slug je vždy ověřen proti pevnému allowlistu z provider registry, nikdy se nepřebírá URL ani cesta k souboru z requestu. Instalace vyžaduje `install_plugins`, aktivace `activate_plugins` – oba jako samostatné, nonce-chráněné kroky.
- **Žádná fabrikovaná jistota**: pokud GARRY nemá ověřený způsob, jak přečíst stav cizího pluginu (např. dokončení UpdraftPlus zálohy), nikdy netvrdí úspěch bez důkazu – zobrazí `requested`/`unknown` a odkáže na ruční ověření v daném pluginu.
- **Obranný rámec**: implementace odpovídá `docs/GARRY-WP-SECURITY-BEHAVIOR-SPEC.md` sekci 15.1 – autorizovaný obranný nástroj, žádný shell/eval, žádné skryté účty, žádná persistence, žádné vzdálené příkazy, žádné tiché vypínání cizích bezpečnostních pluginů.

## Ukládání dat a úklid

Vlastní databázové tabulky (`{prefix}garry_security_scans`, `_findings`, `_events`) vytvořené přes `dbDelta`, plus drobné options (`garry_default_settings`, `garry_security_db_version`, `garry_security_backup_request`) a naplánovaná WP-Cron úloha `garry_security_daily_scan`.

Při **odinstalaci** (`uninstall.php`) se vše výše uvedené trvale smaže – tabulky, options, naplánovaný cron, transient lock i přidané capabilities role Administrator – včetně multisite instalací (`switch_to_blog` / `restore_current_blog` po každém webu sítě). Cizí data (Wordfence, UpdraftPlus a další providery, včetně jimi vytvořených zálohy a nastavení) se nikdy nemažou ani neupravují – GARRY Security je pouze instaluje/aktivuje na výslovné přání administrátora a dál je nechává být.

## Sdílený GARRY rámec

Od verze 1.4.0 plugin používá **GARRY Embedded Framework 2.3** (`docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md`) místo dřívější sdílené třídy `Garry_Promotion_Registry`. Žádná globální třída/`class_exists()` dohad – každý GARRY plugin (GARRY Default nevyjímaje) má vlastní PHP namespace (`Garry\Embedded\GarryDefault\V23`) a vlastní kopii frameworkových tříd v `includes/framework-v23/`. Koordinace mezi aktivními GARRY pluginy běží přes čistě datový registr `$GLOBALS['garry_framework_v23']` a deterministickou volbu vlastníka sdílených „claimů" (`root_menu`, `group_overview`, `group_info`, `group_assets`) podle priority a abecedy slugu – žádný plugin nemá zaručenou výhru.

**GARRY Default není povýšen na povinného poskytovatele.** Účastní se voleb jako rovnocenný peer (priorita 300) a nezávisle na výsledku má vždy vlastní, samostatně fungující lokální administraci se záložkami **Zabezpečení** (vlastní obsah, `Garry_Security_Admin::render_page()`), **Přehled**, **Info** a **Log** – funguje i na webu, kde je jediným aktivním GARRY pluginem, i na webu, kde prohraje o hlavní menu proti jinému GARRY pluginu (pak se zobrazí jako podpoložka). Kontroly GARRY-001/002/003 (`security-ecosystem.php`) nadále hlídají, zda se na webu ještě nachází nemigrovaná starší kopie `Garry_Promotion_Registry` (framework 2.0/2.1) – detekci navíc dělá i sám framework 2.3 (`Diagnostics::has_legacy_framework()`) a upozorní na ni přímo na záložce Přehled.

## Dokumentace zadání

Kompletní specifikace GARRY Security (produktová, behaviorální, mapa funkcí, blueprint providerů) je v `docs/`:

- `GARRY-WP-SECURITY-MASTER-SPEC.md`
- `GARRY-WP-SECURITY-BEHAVIOR-SPEC.md`
- `GARRY-WP-SECURITY-FUNCTION-MAP.md`
- `GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md`
- `GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md` (dokument 6 – auditní zjištění a plán nápravy)
- `GARRY-WP-PLUGIN-ECOSYSTEM-STANDARD.md` (dokument 7 – závazný standard pro všechny GARRY pluginy)

## Autor a podpora

GARRY Promotion — https://garry.cz/ · Realizace: Michal Truhlář, michal@garry.eu · Podpora: podpora@garry.eu

## Licence

Proprietární – zakázkový kód pro použití na webech agentury GARRY Promotion.

## Changelog

### 2.2.0
Zpětná vazba k 2.1.0 ("neoznačovat POKRYTO bez důkazu", duplicitní doporučení, chudá "Akce nyní") + nová prioritizovaná sada kontrol (P0–P2: skutečná účinnost ochrany, obnovitelnost, vystavené soubory, opuštěné pluginy, e-mailová důvěryhodnost).

**Oprava věrohodnosti stavů (P0, nejdůležitější bod zpětné vazby):**
- Kapacitní matice už nikdy neoznačí schopnost jako "V pořádku" (zeleně), pokud pro ni GARRY nemá skutečný, čerstvý důkaz účinnosti – jen aktivní plugin bez ověřené konfigurace dostane výslovně **"Zajištěno — čeká na ověření"** (modrá pilulka), ne zelené "Pokryto".
- **2FA administrátorů**: pokud je vlastníkem plugin **Two-Factor** (má dokumentované veřejné API), GARRY teď skutečně spočítá, kolik administrátorů má 2FA aktivní (`2/3 aktivováno`) – u Wordfence (nedokumentované interní API) zůstává poctivě neověřeno.
- **WAF Wordfence**: nová kontrola PROV-WORDFENCE-WAF ověřuje přítomnost bootstrap souboru rozšířené ochrany (`wordfence-waf.php`) – jen přítomnost souboru, ne vnitřní stav Wordfence.
- **Turnstile na resetu hesla**: nová kontrola PROV-TURNSTILE-RESET (reset hesla je alternativní vstup do účtu, OWASP Authentication Cheat Sheet) – GARRY záměrně nezkouší aktivně testovat rate-limit ani enumeraci účtů (vyžadovalo by simulaci odeslání formuláře, mimo rozsah pluginu).
- **Zálohy/obnovitelnost**: karta Zálohy teď ukazuje typ, vzdálené úložiště a retenci (best-effort z UpdraftPlus) a hlavně nové tlačítko **"Zaznamenat test obnovy"** – bez záznamu je stav "Zajištěno, test obnovy nezaznamenán", nikdy tiché "V pořádku".

**Duplicitní doporučení (zpětná vazba, bod 2):**
- "Doporučené pluginy" (teď "Volitelná rozšíření") už nenabízí instalaci/aktivaci pluginu, jehož všechny schopnosti už pokrývá jiný aktivní poskytovatel – Two-Factor se nenabízí, pokud 2FA řeší aktivní Wordfence, AIOS dostane text "Nainstalován, ale nepoužíván; překrývá Wordfence. Zvažte odstranění."

**"Akce nyní" a "Poslední bezpečnostní události" (body 3–4):**
- Aktualizace pluginů teď vypisují konkrétní jména (ne jen počet) a mají tlačítko "Zobrazit aktualizace"; GARRY čestně přiznává, že nemá zdroj, které z nich řeší bezpečnostní chybu (doporučen Patchstack).
- "Akce nyní" umí zařadit i nálezy na úrovni "Zkontrolovat" (např. chybějící zpevnění .htaccess), pokud je v top-3 místo.
- Poslední bezpečnostní události teď ukazují lidský název kontroly a stavu, technické ID je jen v title atributu.

**Nové kontroly podle prioritizace P0–P2:**
- **EXP-001/002** – veřejně dostupné citlivé soubory (`.env`, `.git/config`, zálohy typu `.sql`, `wp-config.php.bak`, `debug.log`) a výpis obsahu adresáře uploads. Jen pasivní GET na předem známé cesty vlastního webu.
- **PLG-001** – neaktivní pluginy mimo GARRY katalog, s doporučením odstranit, ne aktivovat.
- **ENV-009** – SPF a DMARC (DKIM se neověřuje, selector se liší podle poskytovatele).
- Rozšířené bezpečnostní hlavičky (HTTP-002): přidán HSTS a ochrana proti vložení do iframe (CSP se záměrně nekontroluje – postupný cíl, ne automaticky kritická chyba).
- **"Přístup k obnově"** (Technické detaily): soukromé pole pro kontakt na incident, umístění záloh a odkaz na postup – jen pro administrátory.

**Záměrně nedokončeno i v této verzi**: DKIM (potřebuje známý selector), pravidelný automatický testovací e-mail (nová cron infrastruktura), plná databáze zranitelností pro opuštěné pluginy (vyžaduje placenou feed – doporučen Patchstack), a samostatný "Audit zásadních změn" (nový admin, změna role, aktivace pluginu…) – tohle už řeší capabilita `activity_log` (Simple History) v kapacitní matici; stavět vedle ní vlastní duplicitní log by šlo proti principu "nenabízet druhé řešení tam, kde první už funguje".

### 2.1.0
Zapracování dokumentu "GARRY – návrh struktury a bezpečnostních kontrol" (revize návrhu z 2.0.0) do zbytku plánu: sjednocený stavový slovník, doplněný capability model, nové bezpečně ověřitelné kontroly přístupu a vystavení, přehled GARRY ekosystému jako modulová tabulka.

- **Sjednocený stavový model**: šest lidsky čitelných štítků napříč celou administrací (Kritické / Konflikt / Vyžaduje akci / Zkontrolovat / V pořádku / Neplatí) místo syrových anglických názvů stavu – `garry_security_state_label()`. `Vyžaduje akci` vs. `Zkontrolovat` se odvozuje od závažnosti stejně jako dřív "Akce nyní".
- **Kapacitní matice rozšířena**: sloupce Poskytovatel / **Konfigurace** / **Ověřeno** / Stav / **Akce** (dřív jeden sloučený "Skutečný stav" sloupec), rozdělená do tří bloků – Ochrana a přístup, Obnovitelnost, Provoz a dohled. Sloupec Akce vede přímo do nastavení pokrývajícího pluginu.
- **Výjimky "Není potřeba pro tento web"**: u schopnosti bez poskytovatele lze zapsat důvod a volitelné datum expirace (po expiraci se výjimka sama vrátí na "Zvážit doplnění"). Odlehčená náhrada plného policy enginu z návrhu – řeší úroveň schopností, ne každou jednotlivou kontrolu.
- **"Doporučené pluginy" → "Volitelná rozšíření"**: přejmenováno a jasněji označeno jako instalační mechanismus, ne primární pohled.
- **Kontroly – výchozí zobrazení zkráceno**: defaultně jen otevřené nálezy a nálezy změněné posledním scanem + souhrn počtů ("2 vyžadují akci, 3 ke kontrole…"), s odkazem na plný výpis. Filtr podle stavu a kategorie (čistě serverový, žádné JS).
- **GARRY-001..012 seskupeny** do pěti rozbalovacích bloků (Framework a manifest / Admin assety a CSP / Veřejné vstupy / Data a retence / Výkon a Multisite) místo dvou plochých tabulek.
- **Nový souhrnný panel "GARRY ekosystém"** v Technických detailech: počet aktivních/kompatibilních modulů, konflikty frameworku, shoda manifestů, čekající aktualizace GARRY pluginů, poslední kontrola – plus tabulka jednotlivých modulů s verzí, frameworkem, stavem kompatibility a odkazem na jejich administraci.
- **Nové kontroly přístupu** (ACC-001/002/003) a **vystavení** (WEB-003/004/005/006) – viz sekce "Přístup a účty" výše. Žádná nehádá interní strukturu cizího pluginu; kde GARRY nemá jak bezpečně ověřit, vrací `unknown`, nikdy fabrikované `pass`.
- **robots.txt/llms.txt**: explicitní upozornění, že robots.txt není ochrana a llms.txt se nepočítá do bezpečnostního skóre (návrh struktury na tom trval samostatně).
- **PHP limity**: hodnoty pod doporučeným minimem se teď v kartě vizuálně zvýrazní (dřív jen v textu nálezu ENV-006). Tlačítko stažení .htaccess přejmenováno na "Stáhnout bezpečnostní kopii .htaccess".

**Záměrně nedokončeno i v této verzi**: plný "Nastavení politiky webu" (profily webu, prahy a schvalovatelé pro každou jednotlivou kontrolu – 2.1 řeší jen výjimky na úrovni schopností), plná obrazovka "Skeny a historie" s rozdílem mezi běhy, jednotný exportovatelný Log se zdrojem/objektem/výsledkem (to je sdílená karta Log napříč všemi 21 GARRY pluginy – změna tohoto rozsahu čeká na samostatné posouzení dopadu na celý ekosystém, ne jen na tenhle plugin), cookie flags a kontrola změněných souborů jádra (vyžadovala by nové externí závislosti – WordPress.org checksums API – které si zaslouží vlastní návrh, ne narychlo přidanou kontrolu).

### 2.0.0 – "control plane" redesign
Reakce na rozsáhlou zpětnou vazbu: GARRY Security se místo výpisu PASS/WARNING/CRITICAL testů mění na panel, který řekne, co je skutečné riziko, odkud to víme a jaký je bezpečný další krok. Podle vlastní priority uživatele: Přehled → capability model → Zálohy → GARRY ekosystém → Doporučení.

- **Pokrytí bezpečnostních schopností** (nová primární karta, nahrazuje jen seznam "doporučené pluginy"): jeden řádek na schopnost (Firewall, 2FA, CAPTCHA, Zálohy, SMTP, Audit změn, Vulnerability scanning…), ne na plugin. GARRY doporučí instalaci jen tam, kde schopnost skutečně chybí. Kde má ověřený detail (Turnstile konfigurace, historie záloh UpdraftPlus), ukáže ho; jinak poctivé "Aktivní" bez fabrikovaných podrobností.
- **"Akce nyní"**: max. 3 nejnaléhavější položky nahoře s tlačítkem přímo na opravu/instalaci, místo nutnosti pročítat dlouhou tabulku.
- **Přehled restrukturalizován na 5 karet**: Přístup, Ochrana, Aktualizace, Zálohy, Poslední bezpečnostní události (místo předchozích 3 obecných karet).
- **Přecejchování závažností**: `SCAN-CRON`, GARRY-001 (kolize frameworku), GARRY-006 (anti-spam), GARRY-010 (retence), GARRY-012 (shoda verze) už nejsou `critical` – jde o spolehlivost/konzistenci, ne o aktivní zranitelnost, únik dat, vypnutý firewall nebo kompromitovaný účet. Kritické zůstává jen USR-003/USR-004 (skrytý/neočekávaně oprávněný účet) a WEB-001 (žádné HTTPS).
- **`DELEGATED` → "Ověřeno přes {Provider}"**: srozumitelnější popisek všude, kde GARRY nález deleguje na jiný aktivní plugin.
- **Sloupec "Náprava"** v tabulce Kontroly: přímý odkaz na opravu, nebo tlačítko Instalovat/Aktivovat konkrétní doporučený plugin.
- **"Technické detaily"** – nová záložka uvnitř stránky Zabezpečení: kompatibilita ekosystému GARRY pluginů, rozšířený audit (GARRY-004..012), prostředí/server a robots.txt/llms.txt se sem přesunuly z hlavní obrazovky, aby nezabíraly prominentní místo.
- **PROV-TURNSTILE-CONFIG**: ověří přes self-request na vlastní login stránku, jestli je Turnstile nejen aktivní, ale skutečně nakonfigurovaný (vykreslený widget s nastaveným site key).
- **ENV-007 – obsah .htaccess**: statická textová analýza (žádný zápis) – rozpozná známé rizikové direktivy (např. PHP spustitelné jako obrázek – typický vzor webshellu) i chybějící běžné zpevnění (blokace wp-config.php, .ht* souborů, výpisu adresářů).
- **Server/.htaccess detekce zfunkčněna**: kombinuje `SERVER_SOFTWARE` se skutečnou HTTP hlavičkou z self-requestu na vlastní web, místo jen statické heuristiky.
- **LiteSpeed Cache – funkční ověření**: tři nezávislé signály (aktivní plugin, hlavička `x-litespeed-cache` ve skutečné odpovědi, typ serveru), ne jen "server je LiteSpeed".
- **Nová karta DNS záznamy**: A/AAAA/MX/NS/TXT/CNAME přes vestavěné PHP `dns_get_record()` (žádné externí API) + detekce Cloudflare (nameservery + `cf-ray` hlavička). Cloudflare Tunnel od běžného Cloudflare proxy zvenčí spolehlivě nerozlišit – GARRY hlásí jen "za Cloudflare".
- Karta "Poslední bezpečnostní události" nad posledními 5 záznamy tabulky `events`.

**Záměrně nedokončeno v této verzi** (poctivě, ne kvůli přehlédnutí): plné sekce Skeny (životní cyklus, plán rychlý/standardní/hloubkový), Nastavení (politiky/prahy), rozšířená Pluginy a aktualizace (licence, mu-plugins, neznámé pluginy) a GARRY ekosystém (tabulka modulů s health-check). Nové kontroly z bodů 1–2 zpětné vazby (počet a poslední přihlášení administrátorů, aplikační hesla, XML-RPC/REST enumerace, `?author=`, cookie flags, expirace certifikátu, změněné soubory jádra) nejsou zatím implementované – vyžadují buď novou trackovací infrastrukturu (poslední přihlášení), nebo je GARRY nemá jak bezpečně ověřit bez hádání interní struktury Wordfence (per-administrátor stav 2FA, přesné prahy blokace).

### 1.8.0
- **PROV-TURNSTILE-CONFIG** (Fáze D, dokončení bodu 3 – "Turnstile adapter"): ověří, jestli je Simple Cloudflare Turnstile nejen aktivní, ale skutečně nakonfigurovaný – self-requestem na vlastní přihlašovací stránku a hledáním vykresleného widgetu s nastaveným site key. Žádné hádání interní struktury options pluginu (ta není dokumentované API) – černá skříňka, stejný důkaz jako vidí návštěvník.
- **Napojení na nativní WordPress Privacy API** (`security-privacy.php`): `wp_add_privacy_policy_content()`, exportér a mazač osobních údajů (`wp_privacy_personal_data_exporters`/`erasers`) pro jediný skutečný zdroj osobních údajů v GARRY Security – ID uživatelských účtů v evidenci nálezů USR-003/USR-004. Tohle je způsob, jak se napojit na compliance pluginy obecně: přes jádrové WordPress rozhraní, které tyhle nástroje respektují, ne přes vlastní integraci konkrétního pluginu. Mazač čestně přiznává, že evidence odráží živý stav a při dalším scanu se může znovu objevit, pokud podkladová příčina pořád trvá. Přidána i informativní detekce známého consent pluginu (Complianz a další) – bez vydávání právního verdiktu.
- **ENV-006 + karta "PHP limity"**: memory_limit, upload_max_filesize, post_max_size, max_execution_time, max_input_vars, OPcache – čtení přes `ini_get()`, nikdy zápis (úprava vyžaduje php.ini/hosting).
- Opraveno zastaralé "GARRY/cit-" v textech nálezů na "GARRY" (přejmenování `cit-` pluginů proběhlo už dříve).

### 1.7.0
- **Jednokliknové bezpečné opravy** (Fáze D, dokončeno pro CORE-006/007/WEB-002): tlačítko „Zapnout opravu" u nálezu skutečně provede nápravu – ne zápisem do `wp-config.php` (to GARRY nadále nedělá, viz níže), ale přes vlastní, GARRY-spravovaný soubor `wp-content/mu-plugins/garry-security-hardening.php`, který se při každé změně kompletně přegeneruje. Po zapnutí/vypnutí opravy proběhne rovnou nový scan, takže dashboard hned ukáže ověřený aktuální stav. CORE-005 (WP_DEBUG) tudy jít nemůže – WordPress ho vyhodnocuje dřív, než se mu-pluginy načtou – tam zůstává jen kopírovatelný snippet pro `wp-config.php`.
- Ke každé doporučené opravě přibyl rozepsaný popis („Co to dělá" / „Proč na tom záleží").
- V tabulce Kontroly nový sloupec „Náprava": u GARRY-006 přímo tlačítko Instalovat/Aktivovat Turnstile, u oprav s mu-pluginem odkaz na příslušnou kartu.
- Nová karta „Prostředí a server": PHP, MySQL/MariaDB, typ serveru (heuristika z `SERVER_SOFTWARE`), detekce LiteSpeed, odhad podpory `.htaccess` a bezpečné stažení `.htaccess` jako lokální zálohy (jen čtení, žádný zápis).
- Nová sekce „robots.txt a llms.txt": robots.txt se čte/upravuje výhradně přes nativní WordPress filtr `robots_txt` (nikdy zápis do souboru) s přepínači pro blokování AI/LLM crawlerů, `/wp-json/`, `/?s=` a odkaz na mapu webu. llms.txt (neformální, zatím nestandardizovaná konvence) se vykresluje virtuálně na `/llms.txt` podle stejného principu – žádný fyzický soubor, nikdy zápis na disk. Fyzický soubor na webu (pokud existuje) má vždy přednost a je o tom zobrazené upozornění.
- `uninstall.php` nově maže i mu-plugin soubor a nová options.

### 1.6.0
- **Fáze D pokračuje**: best-effort čtení vlastní historie záloh UpdraftPlus (`updraft_backup_history`, jen nejobecnější tvar – klíče jako časy sad, nikdy se nedomýšlí vnitřní struktura) vedle existujícího `requested` stavu; nikdy netvrdí „dokončeno" bez důkazu.
- **Safe remediation** (Fáze D, bod 6): sekce „Doporučené kroky" na dashboardu – kopírovatelné `wp-config.php` snippety pro WP_DEBUG, display_errors, DISALLOW_FILE_EDIT a FORCE_SSL_ADMIN. GARRY nic nezapisuje sám, jen nabízí text k ručnímu vložení.
- GARRY-006 (anti-spam/rate-limit u veřejných vstupů) nyní kontroluje, jestli je Simple Cloudflare Turnstile na webu aktivní, a podle toho upraví doporučení.

### 1.5.0
- **Fáze C** (auditní plán, sekce 4): kontroly GARRY-004 až GARRY-012 nad lokálními manifesty aktivních GARRY pluginů (`security-hardening.php`) – admin assety mimo vlastní obrazovku, deklarace inline JS/CSS, anti-spam/rate-limit u veřejných AJAX/REST/form vstupů, vlastní CSS nastavitelné editorem, neomezené veřejné dotazy, formát externích origin, retenční politika osobních údajů, ověřený stav Multisite, shoda verze manifestu se skutečně nasazeným pluginem. Vlastní sekce „Rozšířený audit ekosystému" na dashboardu.
- **Fáze D** (částečně): HTTP/Site Health adapter (`security-http-health.php`) – HTTP→HTTPS redirect, doporučené bezpečnostní hlavičky, loopback (deleguje na `WP_Site_Health`), vše výhradně proti vlastnímu `home_url()` (žádné SSRF riziko). CORE-008 – politika automatických aktualizací jádra (jen sleduje, nevynucuje).
- Migrace na **Framework 2.4**: opraven namespace mismatch mezi `bootstrap.php` a zbytkem frameworkových tříd (fatal chyba „Class ... not found" po nasazení 2.4), manifest aktualizován na `framework_protocol`/`framework_minimum` 2.4.0, přidán bootstrap self-check konzistence tříd s bezpečným fallbackem beze ztráty vlastní administrace.

### 1.4.0
- Migrace na **GARRY Embedded Framework 2.3**: vlastní PHP namespace (`Garry\Embedded\GarryDefault\V23`) nahradil sdílenou třídu `Garry_Promotion_Registry` a s ní i version-handshake pojistku (architektonicky už nemůže nastat kolize verzí mezi pluginy). GARRY Default se nyní účastní deterministických voleb o sdílené menu claimy jako rovnocenný peer, ne jako povinný poskytovatel. Zabezpečení běží jako vlastní záložka vedle nových lokálních záložek Přehled/Info/Log, dostupných i bez ostatních GARRY pluginů.

### 1.3.0
- Reakce na auditní dokumenty 6 a 7: version handshake proti kolizi sdíleného frameworku (GARRY Default se bezpečně vypne s notice místo rizika fatal chyby), `garry-plugin-manifest.json`, kontroly GARRY-001/002/003 (duplicitní framework, chybějící Update URI, chybějící manifest) se samostatnou dashboard sekcí, `Update URI` v hlavičce pluginu, capabilities `apply_safe`/`apply_risky`/`manage_providers`, tvrdší scan lock s UUID a vlastníkem.

### 1.2.0
- GARRY Security Fáze 2 (provider stack): provider registry a discovery (9 schválených pluginů), ownership/conflict engine pro exkluzivní capability, UpdraftPlus backup gate (honestní `requested` stav bez fabrikovaného potvrzení dokončení), bezpečná instalace/aktivace doporučených pluginů přes `Plugin_Upgrader` s allowlistem, rozšířený dashboard.

### 1.1.0
- GARRY Security Fáze 1 (audit foundation): DB schéma, katalog kontrol, collectory (prostředí, jádro/aktualizace, HTTPS, uživatelé/administrátoři, scheduler, provider discovery), scan engine s findings/events historií, denní WP-Cron scan, dashboard se třemi kartami, vlastní capabilities.

### 1.0.0
- První vydání: kostra pluginu – sdílené menu GARRY nastavení (Přehled, Info), bezpečná instalace/aktivace/deaktivace, kompletní úklid při odinstalaci.
