# GARRY WordPress Security – detailní chování a workflow

> Stav návrhu: behaviorální specifikace  
> Datum ověření: 2026-08-30  
> Související dokumenty: [mapa funkcí](./GARRY-WP-SECURITY-FUNCTION-MAP.md), [plugin blueprint](./GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md), [master specifikace](./GARRY-WP-SECURITY-MASTER-SPEC.md)

## 1. Zásady chování

1. **Audit před změnou.** Aktivace nic rizikového nemění.
2. **Jedna kontrola, jeden vlastník.** GARRY buď koná, nebo ověřuje externího providera.
3. **Bez čerstvé zálohy není riziková změna.** Záloha musí být dokončena, ne pouze zahájena.
4. **Každá změna je transakční.** Preflight, snapshot, apply, verify, commit/rollback.
5. **Neznámé není bezpečné.** Stav `unknown` není zelený.
6. **Žádné tajné údaje v UI a logu.** Hesla, API secrets, salts, 2FA seeds a session tokeny se nikdy nelogují.
7. **Žádný automatický lockout.** Před změnou loginu/2FA/CAPTCHA existuje nouzová cesta.
8. **Kompatibilita před skóre.** Plugin raději doporučí ruční krok než neověřený zásah.

## 2. Životní cyklus po aktivaci

### 2.1 První aktivace

1. Zkontrolovat minimální WordPress/PHP verzi bez částečné inicializace.
2. Vytvořit DB schéma přes verzované migrace.
3. Zapsat pouze bezpečné výchozí options.
4. Naplánovat rychlý vstupní scan.
5. Zobrazit onboarding wizard administrátorovi s `manage_options`.
6. Nevytvářet účty, neinstalovat pluginy a neměnit DB prefix bez samostatného potvrzení.

### 2.2 Onboarding wizard

Kroky:

1. **Prostředí:** Production/Staging/Development, webserver, Multisite, proxy/CDN.
2. **Režim:** Audit, GARRY Standard, Strict nebo Custom.
3. **Profile a provider discovery:** zvolit typ webu a potřebné capability; objevit pouze skutečně instalované Wordfence/AIOS/Kadence/Sucuri, UpdraftPlus, 2FA, CAPTCHA, SMTP, activity log a schválené GARRY komponenty. Katalog není povinný instalační balík.
4. **E-mail test:** odeslat a nechat potvrdit testovací zprávu před vytvořením GARRY účtů.
5. **GARRY účty:** zobrazit plán, kolize a riziko tří adminů.
6. **Backup:** stav UpdraftPlus a vzdáleného úložiště.
7. **Bootstrap-only volby:** DB prefix a odstranění původního bootstrap admina.
8. **Náhled změn:** žádná změna bez posledního potvrzení.
9. **Apply safe changes:** pouze položky označené jako bezpečné.
10. **Závěrečný scan a report.**

Wizard je opakovatelný v read-only režimu, ale bootstrap-only akce po uzavření inicializačního okna zmizí z běžného UI.

## 3. Vytvoření tří GARRY administrátorů

### 3.1 Technická možnost

Ano, plugin může vytvořit účty pomocí standardního WordPress API. E-maily budou:

- `podpora@garry.eu`;
- `radovan@garry.eu`;
- `michal@garry.eu`.

Role podle potvrzené GARRY politiky: `administrator` pro všechny tři. UI zároveň zobrazí upozornění, že princip nejmenších oprávnění preferuje méně administrátorů a osobní účty.

### 3.2 Bezpečný postup

Pro každý účet:

1. Ověřit syntaxi e-mailu.
2. Přímým DB i WP API dotazem ověřit, zda e-mail/login už neexistuje.
3. Při kolizi nic nepřepisovat; zobrazit existující ID, roli a vyžádat rozhodnutí.
4. Vygenerovat unikátní `user_login`; e-mail zůstává přihlašovací možností WordPressu, ale veřejný display name nesmí kopírovat login.
5. Vygenerovat interní náhodné heslo nejméně 32 znaků přes kryptograficky bezpečný WordPress generátor.
6. Vytvořit účet přes `wp_insert_user()` s přesně vyjmenovanými poli.
7. Heslo nikde nezobrazit a neposílat je v e-mailu.
8. Zavolat `wp_new_user_notification($user_id, null, 'user')`, které odešle jednorázový odkaz pro nastavení hesla.
9. Zapsat GARRY baseline metadata bez tajných údajů.
10. U 2FA provideru označit účet jako vyžadující enrolment; nepokoušet se generovat 2FA seed za uživatele.
11. Ověřit výsledek WP API i přímým DB dotazem.
12. Zapsat audit event `garry_user_created`.

Moderní WordPress notification obsahuje odkaz pro nastavení hesla. Posílat náhodně vygenerované heslo v otevřeném e-mailu by bylo bezpečnostní zhoršení.

### 3.3 Specifika účtu `podpora@garry.eu`

Pokud je mailbox sdílený, nesmí být tento účet používán jako běžný sdílený login. Doporučený režim je break-glass/service účet:

- unikátní heslo v týmovém password manageru;
- 2FA recovery proces vlastněný GARRY;
- minimální běžná aktivita;
- alert při každém přihlášení;
- pravidelná kontrola, zda je stále potřeba.

### 3.4 Co při smazání baseline účtu

Plugin účet automaticky znovu nevytvoří. Vytvoří finding `USR-012 baseline_missing`. Automatická rekreace by mohla obnovit záměrně odebraný přístup nebo maskovat incident.

## 4. ID prvního administrátora

### 4.1 Rozhodnutí

Plugin nebude provádět SQL `UPDATE users SET ID=...`. ID je primární klíč s odkazy v core i cizích tabulkách.

Na čistém webu lze splnit cíl „nemít provozního admina s ID 1“ bezpečněji:

1. vytvořit tři nové GARRY účty standardním API;
2. ověřit přijetí pozvánky alespoň u jednoho osobního účtu;
3. dokončit jeho 2FA;
4. přihlásit se novým účtem;
5. z nového účtu odstranit instalační bootstrap účet ID 1;
6. pokud má obsah, vyžádat explicitní reassignment; na deklarovaně prázdném webu musí být count obsahu 0;
7. zrušit jeho sessions a Application Passwords;
8. spustit kontrolu orphan references.

Samotná existence ID 1 není kritická zranitelnost. Finding je maximálně informativní; bezpečnostní význam má odstranění známého instalačního loginu, silné heslo a 2FA.

## 5. DB prefix při inicializaci

### 5.1 Preferovaný postup

Prefix se nastaví ještě před instalací WordPressu v GARRY deployment receptu, například přes WP-CLI při tvorbě `wp-config.php`. To je bezpečnější než migrace již běžící instalace.

### 5.2 Post-install bootstrap varianta

Protože plugin lze aktivovat až po instalaci, změna prefixu uvnitř pluginu je migrace. Je dostupná pouze pokud preflight ověří:

- web je Single Site, nebo existuje samostatně otestovaný Multisite postup;
- web je označen jako inicializace;
- nejsou publikované příspěvky, objednávky, formulářové záznamy ani jiné business tabulky;
- nejsou aktivní neznámé pluginy vytvářející vlastní tabulky;
- `wp-config.php` je bezpečně zapisovatelný nebo je připraven ruční atomický krok;
- UpdraftPlus vytvořil fresh backup;
- nový prefix odpovídá `[A-Za-z0-9_]+`, není `wp_` a nekoliduje s tabulkami;
- existuje nouzový postup přes SFTP/SSH a kopie původního `wp-config.php`.

Transakce:

1. Zapnout maintenance mode.
2. Uložit seznam tabulek, options/usermeta prefixovaných klíčů a kontrolní součty.
3. Vytvořit fresh Updraft backup a čekat na kompletní úspěch.
4. Přejmenovat core tabulky a explicitně schválené plugin tabulky.
5. Upravit keys typu `<oldprefix>user_roles` a `<oldprefix>capabilities`/`user_level`; nepoužívat slepý globální search-replace v serializovaných datech.
6. Atomicky změnit `$table_prefix` v `wp-config.php`.
7. Ukončit současný request a provést nový health request.
8. Ověřit login, admin, REST, cron, počet tabulek a uživatelů.
9. Při chybě obnovit config a názvy tabulek podle rollback manifestu.
10. Vypnout maintenance a zapsat report.

Migrace zvyšuje pouze odolnost proti primitivním předpokladům útočného skriptu. Nesmí mít významnou váhu ve skóre.

## 6. Odhalování skrytých administrátorů

### 6.1 Proč nestačí obrazovka Users

`WP_User_Query` lze ovlivnit hookem `pre_user_query`. Efektivní oprávnění lze dynamicky měnit filtrem `user_has_cap`. Malware tedy může účet skrýt z administrace nebo udělit oprávnění, která nejsou zřejmá jen z role.

### 6.2 Třívrstvá kontrola

#### Vrstva A – standardní WordPress pohled

- načíst všechny users IDs po dávkách;
- načíst role a relevantní capabilities;
- evidovat seznam, který vidí běžná administrace.

#### Vrstva B – přímá DB kontrola

- přímý připravený `$wpdb` dotaz do `$wpdb->users` bez `WP_User_Query`;
- přímý dotaz do `$wpdb->usermeta` na přesný klíč `$wpdb->prefix . 'capabilities'`;
- hodnotu bezpečně deserializovat a ověřit přesný boolean `administrator` i individuální high-risk capabilities;
- prohledat meta keys končící `_capabilities`, aby se zachytily jiné blog prefixy a pozůstatky;
- u Multisite ověřit `site_admins` a členství webů;
- detekovat orphan usermeta, nečekané registrace a změny baseline.

SQL `LIKE '%administrator%'` se nepoužije jako jediný důkaz, protože serializovaná data a názvy mohou dát false positive.

#### Vrstva C – efektivní runtime oprávnění

Pro každý účet ověřit citlivé capabilities, například:

- `manage_options`, `activate_plugins`, `install_plugins`, `update_plugins`;
- `edit_users`, `create_users`, `promote_users`, `delete_users`;
- `unfiltered_html`, `edit_theme_options`;
- Multisite `manage_network*`.

Pokud účet nemá odpovídající DB roli, ale runtime capability je true, vzniká critical finding s informací o možné dynamické modifikaci.

### 6.3 Vyhodnocení

- API ID chybí v DB: data/cache problém, high.
- DB ID chybí v API: možné skrytí přes hook, critical.
- DB role není admin, ale efektivní kritické caps ano: critical.
- nový admin proti baseline: critical alert.
- známý GARRY účet změnil roli: high/critical podle směru.

### 6.4 Limity

Kontrola běží uvnitř stejného PHP procesu a DB připojení jako potenciální malware. Pro vysokou důvěru se doporučí externí:

- přímý read-only DB dotaz z hostingu;
- WP-CLI s `--skip-plugins --skip-themes` (MU pluginy se stále načítají);
- případně offline kontrola exportu databáze.

GARRY UI nikdy nebude tvrdit „žádný skrytý účet neexistuje“, pokud proběhl pouze interní scan.

## 7. Periodický scan a historie změn

### 7.1 Událostní kontroly

Okamžitě po relevantním WordPress hooku se do fronty vloží malý scan:

- core/plugin/theme update;
- instalace, aktivace, deaktivace nebo smazání pluginu;
- vytvoření/smazání uživatele;
- změna role, hesla, e-mailu nebo Application Password;
- změna GARRY nastavení;
- dokončení/selhání Updraft zálohy;
- změna cron schedule.

Hook není jediný důkaz. Útočník může DB změnit bez hooku, proto zůstává denní polling.

### 7.2 Denní rychlý scan

- aktualizace a automatické update policy;
- uživatelé přes API/DB/runtime;
- 2FA stav adminů;
- HTTPS, debug a file editor;
- WP-Cron overdue;
- stav poslední zálohy;
- stav a konflikty providerů;
- PHP/DB versions;
- veřejné REST/XML-RPC policy.

### 7.3 Týdenní deep scan

- checksum WordPress jádra;
- neočekávané soubory v core adresářích;
- oprávnění a public exposure testy;
- security headers;
- salts pouze jako validity check, nikdy nehodnotit jejich obsah do logu;
- změny GARRY manifestu;
- kontrola backup složek a zakázaných přípon ve webrootu.

Pokud Wordfence provádí malware scan, GARRY svůj hluboký read-only scan naplánuje do jiného časového okna a nebude číst/mazat Wordfence findings. GARRY checksum core může zůstat jako lehký nezávislý stavový test, ale obsahový malware scan se deleguje.

### 7.4 Scheduler

- WP-Cron úlohy jsou chunkované a idempotentní.
- Lock obsahuje scan UUID, heartbeat a expiraci.
- Stale lock lze převzít až po bezpečném timeoutu.
- Při low-traffic webu UI doporučí system cron.
- Výpadek cron je sám finding.
- Scan nemá běžet současně s Updraft backupem nebo Wordfence deep scanem, pokud by zatížení překročilo limit.

### 7.5 Historie

Při každém scanu:

1. vypočítat nový stav a hash redigovaného důkazu;
2. pokud je stejný, změnit pouze `last_seen`;
3. pokud se změnil stav nebo evidence, vytvořit event se starým/novým stavem;
4. pokud problém zmizel, nastavit `resolved_at` a vytvořit recovery event;
5. odeslat alert pouze podle severity, deduplication a cooldown policy.

## 8. Updraft backup jako brána změny

### 8.1 Tlačítko

Riziková akce zobrazí tlačítko **„Vytvořit bezpečnostní zálohu přes UpdraftPlus“**.

Před spuštěním:

- ověřit capability a nonce;
- ověřit aktivní podporovanou verzi UpdraftPlus;
- ověřit, že neběží jiná záloha;
- ukázat komponenty a remote destination;
- ukázat odhad, že operace může pokračovat přes WP-Cron.

### 8.2 Spuštění

Adapter použije feature detection a podporovanou Updraft akci `updraft_backupnow_backup_all` s cloud uploadem povoleným. Volání se nesmí považovat za dokončení. GARRY uloží job correlation ID/nonce a polluje stav nebo poslouchá dostupné completion/log hooks.

### 8.3 Stavový automat

`idle → requested → queued → running → uploading → verifying → completed | completed_with_warning | failed | timed_out`

Riziková změna se odemkne pouze ve `completed`. `completed_with_warning` vyžaduje ruční posouzení; `failed` a `timed_out` ji blokují.

### 8.4 Ověření

- backup set existuje v Updraft historii;
- obsahuje DB, plugins, themes, uploads a others podle změny;
- všechny požadované archivy mají úspěšný stav;
- remote upload je potvrzen, pokud nakonfigurován;
- backup timestamp je po zahájení workflow;
- log neobsahuje chybu; warnings se zobrazí;
- GARRY nikdy nečte ani nezobrazuje cloud credentials.

### 8.5 Pokud Updraft není připraven

- chybí plugin: nabídnout instalaci;
- je neaktivní: nabídnout aktivaci;
- není nakonfigurován: otevřít jeho nastavení;
- není remote storage: warning a doporučení Google Drive/S3/Dropbox;
- běží jiný backup: čekat;
- cron nefunguje: změnu zablokovat a opravit cron;
- adapter nerozumí nové verzi: stav `unknown`, nepoužít neověřenou interní option.

## 9. Obecná riziková změna

Každá změna má manifest:

- `change_id`, typ a verzi implementace;
- přesný cíl a očekávaný původní stav;
- preconditions;
- backup nonce;
- apply kroky;
- verification kroky;
- rollback kroky;
- expiry a actor.

Workflow:

1. Preflight scan.
2. Ukázat dopady a konflikty.
3. Vynutit fresh backup.
4. Vyžádat explicitní frázi/potvrzení.
5. Znovu ověřit capability a nonce.
6. Uložit rollback manifest mimo veřejný webroot, pokud to hosting dovolí.
7. Provedení po malých idempotentních krocích.
8. Health verification z nového requestu.
9. Commit nebo automatický rollback.
10. Závěrečný scan, event a report.

## 10. Interoperabilita providerů

### 10.1 Discovery

GARRY rozlišuje:

- není nainstalován;
- nainstalován/neaktivní;
- aktivní lokálně;
- aktivní network-wide;
- aktivní, ale nenakonfigurovaný;
- nakonfigurovaný a zdravý;
- degradovaný/neznámý;
- konflikt.

Detekce aktivace nestačí. Například Wordfence může být aktivní, ale firewall v learning mode, 2FA adminů vypnuté nebo licence nedokončená.

### 10.2 Ownership arbitration

Pro každou capability se vybere právě jeden owner:

- `firewall`: Wordfence **nebo** AIOS/Kadence/edge;
- `malware_scan`: Wordfence/Sucuri/AIOS;
- `2fa`: Wordfence/Two-Factor/AIOS/Kadence;
- `captcha:<surface>`: Turnstile/Wordfence reCAPTCHA;
- `login_rate_limit`: Wordfence/AIOS/Kadence/edge;
- `backup`: UpdraftPlus;
- `activity_log`: Simple History/security suite/GARRY limited log.

GARRY neupravuje neveřejné cizí options přímo v DB. Adapter používá veřejný hook/API nebo pouze čte stabilní, verzovaně ověřený stav. Při neznámé verzi přejde do `unknown`, nikoli do odhadu.

### 10.3 Kolize CAPTCHA

Capability je rozdělena po površích:

- WordPress login;
- registrace;
- reset hesla;
- komentáře;
- WooCommerce login/registration/checkout;
- Contact Form 7/Elementor/ostatní formuláře.

Výchozí GARRY profil:

- Cloudflare Turnstile je primární CAPTCHA provider;
- Wordfence reCAPTCHA je vypnuta;
- Wordfence 2FA zůstává aktivní;
- po aktivaci Turnstile se provede test loginu a recovery flow;
- pokud je zvolena Wordfence reCAPTCHA, Turnstile se na stejných core login surfaces nezapne.

### 10.4 Dvě security suites

Pokud jsou současně aktivní např. Wordfence a AIOS/Kadence s WAF/login lockoutem:

- GARRY nic automaticky nevypne;
- vytvoří `conflict` s vysokou prioritou;
- popíše překryvy: WAF, login limit, XML-RPC, file change, 2FA, CAPTCHA;
- nabídne volbu primárního providera a ruční checklist vypnutí modulů;
- po změně provede login, REST, AJAX, cron a frontend smoke test.

### 10.5 Projektový profil a běžný GARRY katalog

Každý web má verzovaný `site profile`, například prezentační Divi, prezentační Elementor, lead-generation, redakční nebo e-commerce. Profil obsahuje požadované capability a očekávané komponenty; neobsahuje příkaz „nainstaluj celý katalog“. Pro Divi, Elementor, UpdraftPlus, Yoast, Wordfence, AIOS, ACF, Complianz, LiteSpeed Cache, Microsoft Clarity, Google Site Kit a ostatní GARRY pluginy platí:

1. Discovery nejprve zaznamená `installed/active/version/update-channel/config-health`.
2. Nevybraná komponenta dostane `not_applicable`, nikoli warning za neinstalaci.
3. Chybějící povinná capability vede k doporučení jednoho vhodného providera.
4. Private/Premium varianta se identifikuje podle ověřeného entry file/plugin headeru; GARRY nečte licenční klíč.
5. Po aktivaci, deaktivaci nebo aktualizaci se přepočítá ownership a spustí cílený compatibility scan.
6. Neznámá komponenta nebo verze je `unknown/manual_review`; GARRY ji automaticky nedeaktivuje ani neoznačí za škodlivou.

Při souběhu Wordfence + AIOS se nehodnotí pouhá aktivace, ale stav jednotlivých modulů. Standardně Wordfence vlastní WAF, malware/integrity, login limit a 2FA; překrývající AIOS moduly mají být vypnuté. AIOS smí zůstat pro explicitně vybrané nepřekrývající hardening/audit capability. GARRY nevynucuje tuto konfiguraci zápisem do interních options: zobrazí ruční checklist, deep link a následný smoke test.

Compatibility smoke test podle nasazených komponent zahrnuje:

- Divi/Elementor frontend, editor, preview, REST, AJAX a formulář;
- LiteSpeed cache bypass login/admin/nonce/2FA/CAPTCHA a řízený purge;
- Yoast výslednou meta robots, sitemap a `robots.txt` bez dvojího vlastníka;
- Complianz consent stav před načtením Clarity/Site Kit a kontrolu duplicitního trackingu;
- ACF pouze technický audit REST/public-form exposure, nikdy změnu field groups nebo obsahu;
- standardní login, reset hesla, 2FA/recovery, cron a loopback.

## 11. Doporučení a instalace pluginů

### 11.1 Zobrazení

Každý doporučený plugin má stav:

`Recommended → Installed → Active → Connected → Configured → Healthy`

Každý mezikrok ukazuje konkrétní další akci. „Active“ se nikdy nezaměňuje za „chrání“.

### 11.2 Instalace

1. Ověřit `install_plugins`, `activate_plugins` a nonce.
2. Načíst metadata z WordPress.org podle pevně povoleného slugu.
3. Ukázat autora, poslední update, kompatibilitu, data sdílená externě a překryvy.
4. Vyžádat souhlas.
5. Instalovat přes core `Plugin_Upgrader`, nikdy vlastní downloader.
6. Ověřit výsledný entry file a metadata.
7. Aktivaci potvrdit zvlášť, pokud může změnit login/WAF.
8. Spustit provider-specific configuration wizard.
9. Provedení smoke testu a zápis auditu.

Automatická instalace všech doporučení při aktivaci GARRY pluginu je zakázána.

## 12. Dashboard chování

### Stav zabezpečení

- nahoře kritické findings, ne kosmetické procento;
- poslední úplný a rychlý scan;
- trend od minulého týdne;
- počet delegovaných a neověřitelných kontrol;
- jedno bezpečné CTA, ne globální „Fix all“ pro rizikové věci.

### GARRY karta

- servisní kontakt, prostředí, identifikátor webu;
- GARRY plugin/version/update channel;
- poslední a příští údržba;
- stav tří baseline účtů bez zobrazení login secretů;
- přístup podle samostatné read-only capability.

### Aktualizace a zálohy

- core/plugin/theme updates;
- auto-update policy;
- poslední úspěšná DB/files záloha a remote destination;
- další schedule;
- poslední chyba;
- restore drill age;
- tlačítko backup pouze uživateli s backup capability.

## 13. Alerting

Okamžitý high/critical alert:

- nový nebo skrytý admin;
- admin ztratil 2FA;
- deaktivace primárního security/backup provideru;
- změněný core checksum;
- selhání zálohy před změnou;
- kritická aktualizace čeká nad stanovený limit;
- scan/cron je overdue;
- HTTPS přestalo fungovat;
- GARRY plugin byl deaktivován lze spolehlivě hlásit jen externím monitorem/MU loaderem.

Alerty mají deduplikaci, recovery message a cooldown. E-mail není jediný kanál pro výpadek mailu; později lze přidat opt-in central GARRY webhook.

## 14. Recovery a nouzový přístup

- login URL změna má `GARRY_LOGIN_SAFE_MODE` postup přes `wp-config.php`;
- 2FA recovery vlastní provider, GARRY nikdy neskladuje seeds/codes;
- CAPTCHA změna má test mode a možnost deaktivace přes SFTP;
- config změna má přesnou předchozí kopii a checksum;
- DB prefix má rollback manifest a maintenance runbook;
- pokud plugin způsobí fatal error, standardní WordPress Recovery Mode nebo přejmenování adresáře pluginu musí obnovit přístup;
- serverová pravidla nesmí záviset jen na tom, že se běžný plugin načte.

## 15. Bezpečnost samotného pluginu

- vlastní capabilities pro view/run/fix/manage providers;
- na každé mutaci capability + nonce; nonce není autorizace sám o sobě;
- validace a allowlist vstupů, sanitizace při uložení, escape při výstupu;
- `$wpdb->prepare()` a žádné dynamické neověřené identifiers;
- žádné unserialize nedůvěryhodných objektů; povolené jsou jen očekávané arrays/scalars;
- REST endpoints mají explicitní `permission_callback`;
- externí URL jsou allowlisted, chráněné proti SSRF a mají timeout;
- cron handlers jsou idempotentní a ověřují interní job token;
- žádné secrets v options s `autoload=yes`;
- žádná telemetrie bez opt-in;
- uninstall nabízí zachování nebo odstranění logů, ale nikdy neodstraní cizí provider data;
- vlastní update kanál používá HTTPS, podepsané/reprodukovatelné balíčky a kontrolu integrity;
- pravidelný code review a testy podle WordPress Coding Standards.

### 15.1 Obranný vývojový kontrakt pro ChatGPT, Claude Code a další AI

Budoucí implementační zadání musí plugin jednoznačně definovat jako **autorizovaný obranný administrační nástroj pro WordPress weby vlastněné nebo spravované GARRY**. Cílem je hardening, audit, dostupnost a obnova; nikoli získání přístupu, skrytí přítomnosti nebo ovládání cizích systémů.

Závazné hranice implementace:

- žádné exploitování, skenování cizích hostů, obcházení autentizace nebo eskalace oprávnění;
- žádné obfuskace, `eval`, ukryté/base64 payloady, webshell, dropper, anti-analysis ani self-modifying code;
- žádné volání shellu (`exec`, `system`, `shell_exec`, `passthru`, procesy) pro běžnou funkci pluginu;
- žádné libovolné vzdálené příkazy, stahování a spuštění kódu, covert C2 nebo neřízené package URL;
- žádné sbírání/exfiltrace hesel, saltů, 2FA seedů, recovery codes, cookies, API/licenčních klíčů nebo databázových credentials;
- žádné skryté účty, persistence nebo automatické obnovení smazaného uživatele;
- žádné tiché vypínání Wordfence/AIOS nebo jiných ochranných komponent;
- změny jsou viditelné v administraci, adminem iniciované, autorizované capability + nonce, auditované a vratné;
- sken souborů je read-only; karanténa, smazání nebo oprava vyžaduje oddělený lidský krok, fresh backup a důkaz změny;
- remote heartbeat je opt-in, odesílá jen minimální stav do allowlisted endpointu s pevným schématem a nikdy nemůže přijmout PHP, SQL, shell nebo obecný příkaz;
- GARRY účty vznikají pouze v explicitním wizardu standardními WordPress API, jsou viditelné v Users/DB/auditu a plugin je po smazání automaticky neobnovuje;
- „detekce skrytých adminů“ pouze porovnává WP API, DB a runtime a hlásí rozdíl; žádného uživatele neschovává.

Pro AI je vhodné implementovat vždy jeden ohraničený modul s jeho testy. Nepoužívat vágní zadání spojující „vytvoř účty, změň DB, schovej login a připoj remote řízení“, protože bez bezpečnostního kontextu připomíná persistence nebo útok. Používat pojmy „autorizovaný defensive hardening“, „read-only audit“, „viditelný admin workflow“, „rollback“ a „web spravovaný GARRY“.

Doporučená hlavička každého implementačního promptu:

> Implementuj autorizovaný obranný WordPress administrační plugin pro weby vlastněné nebo spravované GARRY. Používej WordPress API, viditelné admin UI, přesné capabilities, nonce, audit log a vratné změny. Nevytvářej exploity, obfuskaci, shell/webshell, skryté účty, persistence, vzdálené příkazy, sběr credentials ani obcházení bezpečnostních pluginů. Implementuj pouze níže vymezený modul a jeho testy: **[název modulu]**.

Stejné vymezení má být v README, hlavní hlavičce pluginu, threat modelu a komentáři u citlivých workflow. Není to pokus obejít bezpečnostní kontrolu AI; je to přesný a ověřitelný popis legitimního účelu a technických hranic.

## 16. Akceptační kritéria

Návrh je funkčně splněn, pokud:

- umí zobrazit a logovat přechody všech požadovaných kontrol;
- denní scan funguje i po přerušení a nevytváří duplicitní eventy;
- skrytý DB admin v test fixture vyvolá critical rozdíl;
- capability přidaná filtrem bez DB role vyvolá critical finding;
- Wordfence deleguje WAF/2FA/login limit bez zapnutí duplicitního GARRY modulu;
- dvě CAPTCHA na stejném formuláři vyvolají conflict;
- Updraft tlačítko neodemkne změnu před dokončením celé zálohy;
- selhání remote uploadu blokuje fresh-backup gate podle policy;
- vytvoření GARRY users odešle set-password link a nikde neobsahuje plaintext password;
- prefix migrace není dostupná na neprázdném nebo neověřeném webu;
- každý rizikový zásah má testovaný rollback;
- `unknown` nikdy nezvyšuje skóre;
- žádný log/export neobsahuje heslo, salt, 2FA seed, recovery code nebo API secret;
- nevybrané katalogové pluginy nejsou chybně hlášeny jako chybějící;
- Wordfence + AIOS bez překrývajících se modulů je podporovaný stav, ale překryv stejné capability je conflict;
- Divi/Elementor/LiteSpeed/Yoast/Complianz integrační fixtures projdou příslušným smoke testem;
- remote odpověď ani GARRY manifest nemohou spustit obecný callback, PHP, SQL nebo shell příkaz;
- vytvořený GARRY účet je viditelný a po ručním smazání se automaticky neobnoví.
