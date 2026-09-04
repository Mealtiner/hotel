# GARRY Embedded Framework 2.3

Stav: návrh pro implementaci a aktualizaci pomocí Claude Code  
Určení: společný standard vložený do každého jednotlivého GARRY WordPress pluginu  
Verze protokolu: 2.3.0  
Závazné pravidlo: žádný GARRY plugin nesmí vyžadovat instalaci, aktivaci nebo existenci jiného GARRY pluginu.

## 1. Základní rozhodnutí

Framework 2.3 nebude samostatný koordinační plugin a nebude součástí GARRY Default. Bude dodáván jako stejná, interní a verzovaná knihovna uvnitř každého GARRY pluginu.

Každý plugin proto musí fungovat ve všech těchto situacích:

| Situace | Povinný výsledek |
|---|---|
| Je aktivní pouze daný plugin | veřejná funkce, vlastní Přehled, Info, Log a administrace fungují |
| Je aktivních několik libovolných GARRY pluginů | všechny fungují; společné prvky jsou zobrazeny pouze jednou |
| GARRY Default / Security není nainstalovaný | žádná ztráta funkce ani chyba |
| GARRY Default / Security je aktivní | je pouze další rovnocenný účastník |
| Elementor, Divi nebo ACF nejsou nainstalované | odpadne jen příslušná volitelná integrace daného pluginu |
| Jiný GARRY plugin se deaktivuje nebo aktualizuje | daný plugin zůstává funkční a sám převezme nutné společné zobrazení |

Slovo framework v tomto návrhu znamená dva odlišné prvky:

1. Stejný lokální kódový vzor vložený do každého pluginu.
2. Neutrální protokol pro aktuální WordPress request, kterým se aktivní pluginy dohodnou, kdo vykreslí konkrétní sdílený prvek.

Neznamená to společnou PHP třídu sdílenou mezi pluginy a neznamená to povinnou závislost.

## 2. Proč nesmí pokračovat starý model

Audit odhalil opakované kopie globální třídy Garry_Promotion_Registry ve většině GARRY pluginů. Existují varianty 2.0 a 2.1. Verze 2.1 obsahuje navíc například STAFF_CAPABILITY a grid_visible(), které využívají GARRY Denní menu, GARRY Kategorie pokojů a GARRY Sezóna / čekací list.

Globální název třídy je problém: první načtená kopie může potlačit novější variantu. Později načtený plugin pak volá funkci, kterou vybraná starší kopie nemá. Výsledkem může být fatální chyba a závislost na pořadí načtení pluginů.

Framework 2.3 tento model zakazuje:

- žádný globální název sdílené třídy;
- žádný class alias staré registry;
- žádné includování frameworku z adresáře jiného pluginu;
- žádná volba implementace pomocí class_exists u stejného globálního názvu;
- žádný předpoklad, že GARRY Default je aktivní.

## 3. Návrh embedded architektury

Každý plugin obsahuje stejnou souborovou strukturu, avšak vlastní PHP namespace podle svého stabilního slugu.

~~~text
garry-denni-menu/
├── garry-denni-menu.php
├── includes/
│   ├── framework-v23/
│   │   ├── bootstrap.php
│   │   ├── FrameworkBridge.php
│   │   ├── Protocol.php
│   │   ├── Election.php
│   │   ├── LocalAdmin.php
│   │   ├── LocalLog.php
│   │   ├── Manifest.php
│   │   └── Diagnostics.php
│   └── plugin-specific-files.php
├── assets/
│   └── framework-v23-admin.css
├── garry-plugin-manifest.json
└── docs/
~~~

Příklad namespace pro Denní menu:

~~~php
namespace Garry\Embedded\DenniMenu\V23;
~~~

Příklad namespace pro Foto galerii:

~~~php
namespace Garry\Embedded\FotoGalerie\V23;
~~~

Každý plugin má proto vlastní třídy FrameworkBridge, Protocol a LocalLog. Jejich zdrojový kód je při vydání synchronizován z jednoho interního template, ale PHP runtime je izolovaný. Žádná třída nenese obecný globální název.

### Co lze bezpečně sdílet

Pro koordinaci v rámci jednoho requestu je povolena pouze malá, datová struktura v globálním WordPress/PHP prostoru:

~~~php
$GLOBALS['garry_framework_v23'] = array(
    'participants' => array(),
    'claims'       => array(),
    'diagnostics'  => array(),
);
~~~

Toto není sdílený objekt ani zdroj executable callbacků. Obsahuje jen validovaná metadata aktivních GARRY pluginů a deterministické výsledky voleb. Každá lokální kopie FrameworkBridge ji inicializuje obranně, přidá pouze vlastní descriptor pod vlastním slugem a nikdy nepřepisuje descriptor jiného pluginu.

## 4. Protokol 2.3 a deterministická volba vlastníka

### 4.1 Registrace účastníků

Při načtení každého pluginu vloží jeho lokální FrameworkBridge descriptor do participant registry. Vlastník nesmí být pevně uložen do databáze; vyhodnocuje se při každém requestu po načtení všech aktivních pluginů.

K samotné volbě dojde na hooku plugins_loaded s prioritou 999. V tomto okamžiku jsou načteny soubory všech běžných aktivních pluginů a registry je kompletní pro aktuální request.

~~~php
array(
    'slug'                   => 'garry-denni-menu',
    'name'                   => 'GARRY – Denní menu',
    'plugin_version'         => '1.0.0',
    'framework_protocol'     => '2.3.0',
    'framework_major'        => 2,
    'framework_minimum'      => '2.3.0',
    'framework_priority'     => 100,
    'local_menu_slug'        => 'garry-denni-menu',
    'icon'                   => 'dashicons-calendar-alt',
    'claims'                 => array('root_menu', 'group_overview'),
    'manifest_path'          => '/absolute/path/garry-plugin-manifest.json',
)
~~~

Descriptor nesmí obsahovat objekt, closure, vzdálenou URL pro kód, heslo, token, e-mail uživatele ani jakékoli osobní údaje.

### 4.2 Volby probíhají pro jednotlivé funkce

Není jeden trvalý vedoucí plugin. Pro každou sdílenou funkci se vybere konkrétní vlastník.

Povolené claims 2.3:

| Claim | Co se zobrazuje pouze jednou | Kandidáti |
|---|---|---|
| root_menu | parent menu GARRY | všechny kompatibilní aktivní účastníky |
| group_overview | souhrnný přehled aktivních GARRY pluginů | všechny kompatibilní aktivní účastníky |
| group_info | obecná informace o standardu Frameworku 2.3 | všechny kompatibilní aktivní účastníky |
| group_assets | společné CSS pouze pro group overview screen | jen vlastník group_overview |

Přehled, Info a Log konkrétního pluginu nejsou claims. Ty vždy patří danému pluginu a musí být dostupné i když je aktivní pouze on.

### 4.3 Algoritmus volby

Pro každý claim:

1. Použij pouze deskriptory s framework_major 2.
2. Odmítni neplatný slug, neplatnou verzi, neplatný manifest nebo duplicitní descriptor.
3. Vyber kandidáta, který claim deklaruje a jehož framework_minimum je nejvýše 2.3.0.
4. Seřaď kandidáty podle framework_priority vzestupně.
5. Při shodě použij abecední slug jako stabilní tie-breaker.
6. Výsledek ulož jen do requestové registry.

Příklad: pokud mají Denní menu priority 100 a Foto galerie 200, root_menu vykreslí Denní menu. Po jeho deaktivaci v dalším requestu totéž menu automaticky převezme Foto galerie. Žádný plugin se neaktivuje, neinstaluje ani nekontroluje soubory druhého pluginu.

Vlastník group_overview musí být stejný jako vlastník root_menu, pokud obě funkce deklaruje. Pokud ne, group_overview je samostatný child screen ve společném parent menu.

### 4.4 Neodpovídající nebo starší kopie frameworku

| Nález | Reakce 2.3 |
|---|---|
| Neaktivní žádný jiný plugin | tento plugin je vlastníkem svých claims |
| Druhý plugin s 2.3.x | spojí se do stejné registry |
| Druhý plugin s budoucí 2.x | přijme známé klíče; neznámé ignoruje |
| Druhý plugin s jiným major protokolu | neúčastní se voleb 2.3; jeho lokální UI stále funguje |
| Legacy 2.0/2.1 Garry_Promotion_Registry | nezapisuje se do nové registry; lokální UI 2.3 funguje, zobrazí warning o legacy stavu |
| Duplicita stejného slugu | první validní descriptor zůstane, další je diagnostika; druhý nedostane shared claim |

Framework 2.3 nesmí legacy registry mazat ani přepisovat. V rámci přechodu může existovat legacy parent menu a nezávislé menu 2.3. To je administrativně viditelný, ale bezpečnější stav než fatální kolize.

## 5. Přehled, Info a Log v každém pluginu

Každý GARRY plugin implementuje své tři lokální části. Musí být viditelné ve vlastní administraci daného pluginu bez závislosti na jiném GARRY pluginu.

### 5.1 Přehled pluginu

Lokální stránka Přehled ukazuje:

- název, lokální ikonku a vlastní verzi pluginu;
- verzi embedded Frameworku a stav protokolu;
- stav vlastních volitelných integrací, například Elementor, Divi, ACF, Wordfence nebo UpdraftPlus, pokud je plugin skutečně používá;
- zda je plugin v aktuálním requestu vlastníkem některého shared claim;
- stručný seznam ostatních účastníků Frameworku, pokud nějaké existují;
- stav vlastních bezpečnostních kontrol, pouze pokud je daný plugin implementuje.

Přehled nesmí předstírat, že jiný plugin je povinný. Pro chybějící volitelný plugin použije formulaci Volitelné – není aktivní, ne Chyba instalace.

### 5.2 Info pluginu

Lokální stránka Info obsahuje:

- účel pluginu a jeho veřejné plochy, například shortcode, blok, widget;
- autora, plugin slug, verzi, datum vydání a odkaz na lokální dokumentaci;
- deklarované WordPress capability;
- volitelné závislosti a co bez nich nefunguje;
- vlastní data: prefix options, zda plugin zpracovává osobní údaje, retention a uninstall pravidlo;
- deklarované externí originy a účel každého spojení;
- kompatibilitu s Frameworkem 2.3.

Info nepřebírá HTML z cizích webů. Ikona musí být dashicon nebo lokální asset v pluginu, nikoli uživatelsky zadaná externí URL.

### 5.3 Log a verzování

Každý plugin vede vlastní omezený technický log pod vlastním option prefixem, například garry_denni_menu_framework_log. Log patří výhradně danému pluginu.

Povolené události:

| Událost | Uložená data |
|---|---|
| framework_boot | čas, framework verze, stav protokolu |
| plugin_version_changed | předchozí a nová vlastní verze |
| framework_version_changed | předchozí a nová embedded verze |
| migration_completed | vlastní migrační identifikátor, čas, výsledek |
| compatibility_state_changed | předchozí/nový stav a necitlivý kód důvodu |
| settings_saved | název vlastní bezpečné sekce, ne hodnoty a ne vstup uživatele |
| integration_changed | název volitelné integrace a stav dostupnosti |

Zakázaná data v logu:

- hesla, tokeny, API klíče, cookies a nonce;
- celé URL s query parametry;
- IP adresy, e-mailové adresy, jména a obsah formulářů;
- HTML response, SQL dotazy, backtrace a raw request data.

Retence: maximum 200 položek nebo 90 dní, podle toho, co nastane dříve. Log se zobrazuje jen uživatelům s capability pro administraci daného pluginu. Export logu je až budoucí volitelná funkce; pokud vznikne, musí vyžadovat explicitní potvrzení a nesmí obsahovat citlivá data.

## 6. Lokální manifest

Každý plugin po migraci obsahuje v kořeni soubor garry-plugin-manifest.json. Framework ho čte pouze z vlastního adresáře a nikdy jej nestahuje ze sítě.

~~~json
{
  "schema_version": "2.3",
  "slug": "garry-denni-menu",
  "name": "GARRY – Denní menu",
  "plugin_version": "1.0.0",
  "framework_protocol": "2.3.0",
  "framework_minimum": "2.3.0",
  "icon": {
    "type": "dashicon",
    "value": "dashicons-calendar-alt"
  },
  "surfaces": ["admin-page", "shortcode"],
  "admin": {
    "capability": "manage_options",
    "local_menu_slug": "garry-denni-menu"
  },
  "dependencies": {
    "required": [],
    "optional": ["elementor"]
  },
  "data": {
    "stores_personal_data": false,
    "settings_option_prefix": "garry_denni_menu_",
    "log_retention_days": 90,
    "uninstall_behavior": "preserve-unless-explicitly-requested"
  },
  "external_origins": [],
  "documentation": {
    "local_path": "docs/README.md"
  }
}
~~~

### Povinná validace manifestu

| Oblast | Pravidlo |
|---|---|
| slug | malá písmena, čísla a pomlčky; musí odpovídat pluginu |
| verze | validní SemVer MAJOR.MINOR.PATCH |
| capability | pevně povolený WordPress capability string |
| icon | dashicon z allowlistu nebo cesta do vlastního adresáře pluginu |
| option prefix | musí začít slugu odpovídajícím garry_ prefixem |
| external origins | jen doména a schéma; žádný executable endpoint |
| documentation | lokální cesta nebo HTTPS odkaz bez vkládání vzdáleného obsahu |

Manifest neobsahuje PHP callbacky, secrets, licence, přístupové údaje ani osobní údaje.

## 7. Administrace a chování menu

### Samostatný režim

Pokud je plugin jediným aktivním účastníkem Frameworku, vytvoří top-level menu se svým vlastním slugem. V něm jsou vždy tři záložky nebo stránky:

1. Přehled
2. Info
3. Log

Volitelně jsou na stejném menu vlastní funkční stránky pluginu, například Nastavení nebo Obsah.

### Režim více GARRY pluginů

Pokud je aktivních více kompatibilních účastníků:

- zvolený vlastník root_menu vytvoří jediný parent GARRY menu;
- zvolený vlastník group_overview vykreslí jeden souhrnný dashboard;
- každý plugin přidá přes vlastní lokální kód své submenu;
- v každém pluginovém submenu stále existují jeho Přehled, Info a Log;
- sdílené CSS pro group overview smí enqueue pouze jeho vlastník;
- žádná část UI nevyžaduje, aby byl vlastník konkrétní předem známý.

Při deaktivaci vlastníka se v dalším requestu určí nový. Child pluginy nemusejí měnit data, volat jiný plugin ani provádět migraci.

## 8. Bezpečnostní pravidla

Framework 2.3 i jeho hostitelské pluginy dodržují následující limity:

- nepoužívat eval, assert pro vstupy, obfuskaci, skryté loadery ani base64 vykonávání;
- nestahovat ani nespouštět vzdálený PHP, JavaScript, JSON config nebo HTML;
- nevypínat SSL ověřování ve WordPress HTTP klientovi;
- neinstalovat ani neaktivovat cizí plugin;
- neměnit .htaccess, databázový prefix, uživatele, login URL ani soubory bez samostatně schválené funkce konkrétního bezpečnostního pluginu;
- neukládat hesla, tokeny, e-maily, IP adresy nebo formulářová data do manifestu, registry či logu;
- u každé mutační akce kontrolovat capability, nonce, sanitizaci a kontextové escapování;
- nenačítat Framework CSS nebo JS na cizích wp-admin screens;
- nepoužívat globální admin_head pro vzhled Frameworku;
- GARRY Security může číst stav jiné registrace pouze read-only; nemůže ji opravovat ani měnit.

## 9. Zdroje dat a vztah k pluginům třetích stran

Framework sbírá pouze lokální metadata vlastního pluginu a data zveřejněná dobrovolně jinými účastníky téhož protokolu. Pro stav jiných pluginů může konkrétní GARRY plugin použít pouze veřejné WordPress funkce, například seznam aktivních pluginů, a jen pro informativní kartu.

| Oblast | Zdroj informací | Co Framework nedělá |
|---|---|---|
| vlastní verze | plugin header a manifest | neaktualizuje plugin |
| aktivita Wordfence/UpdraftPlus | veřejný seznam aktivních pluginů | nemění jejich nastavení |
| stav Elementor/Divi/ACF | bezpečná existence deklarované integrace | nevynucuje instalaci |
| log | pouze vlastní option prefix pluginu | nečte logy jiných pluginů |
| bezpečnost GARRY Default | jen deklarovaný read-only status, pokud je dostupný | neprovádí remediaci |
| ikony/dokumentace | lokální manifest a asset pluginu | nestahuje vzdálené HTML |

Poučení z auditu třetích stran:

- nepřebírat model vzdáleného JSON nebo HTML dashboardu;
- nepřebírat hardcoded credentials, automatické změny .htaccess ani nebezpečné uploady;
- přebrat jen bezpečné principy: namespacing, modulární kontrakty, capability, nonce, scoped assets, jasné versioning a lokální diagnostiku.

## 10. Postup migrace

### Fáze 1 – vytvořit frameworkový template

1. Vytvořit zdrojový template Embedded Framework 2.3.
2. Umožnit generování pluginově specifického namespace.
3. Přidat lokální manifest schema, LocalAdmin, LocalLog, Protocol a Election.
4. Otestovat template na jednom izolovaném testovacím pluginu.

### Fáze 2 – priorita rizikových pluginů

Nejdříve migrovat:

1. GARRY Denní menu
2. GARRY Kategorie pokojů
3. GARRY Sezóna / čekací list

Tyto pluginy využívají funkce přidané ve staré verzi 2.1 a jsou nejvíce citlivé na pořadí načtení.

### Fáze 3 – ostatní GARRY pluginy

Pro každý plugin:

1. přidat vlastní kopii frameworkového template s jedinečným namespace;
2. přidat manifest, lokální Přehled, Info, Log a ikonku;
3. odstranit includes/garry-framework.php a Garry_Promotion_Registry;
4. zachovat veřejné funkce, vlastní shortcode/widget a vlastní data;
5. otestovat samostatný a společný režim;
6. přidat položku do změnového logu daného pluginu.

GARRY Default / Security se migruje stejně jako každý jiný plugin. Nesmí být povýšen na povinného poskytovatele.

### Fáze 4 – kontrola legacy kopie

Po migraci zkontrolovat:

~~~text
rg -n "class[[:space:]]+Garry_Promotion_Registry|includes/garry-framework\.php" <adresář-GARRY-pluginů>
~~~

Výsledek musí být prázdný mimo archivní dokumentaci. Pokud zbývá legacy plugin, nový framework ho jen označí v lokálním Přehledu jako migrační stav. Nesmí jej upravovat, vypínat ani přebírat jeho data.

## 11. Testovací matice

| Test | Očekávaný výsledek |
|---|---|
| pouze jeden GARRY plugin | má vlastní menu, Přehled, Info, Log, ikonu a funkční frontend |
| stejný plugin bez GARRY Default | beze změny funkce |
| dva libovolné migrované pluginy | jeden parent GARRY menu, obě vlastní sady Přehled/Info/Log |
| tři a více migrovaných pluginů | každý shared claim vykreslen právě jednou |
| deaktivace zvoleného vlastníka menu | po reloadu claim převezme stabilně další kandidát |
| opětovná aktivace vlastníka | volba se znovu deterministicky přepočte |
| mix 2.3 a legacy 2.0/2.1 | žádný fatal error; legacy warning v lokálním Přehledu |
| chybí Elementor, Divi nebo ACF | selže jen volitelná integrační plocha daného pluginu |
| nižší role | nemůže číst log ani provést mutační akci |
| cizí wp-admin screen | neobsahuje Framework CSS/JS |
| log obsahuje novou událost | neobsahuje citlivá data a dodrží limit retence |
| multisite bez explicitní podpory | neprovádí síťový zápis ani hromadné změny |

Automatizovat je nutné zejména SemVer, validaci descriptoru, volbu vlastníka včetně tie-breaku, izolaci pluginových namespaces, limit logu, absenci citlivých dat a scoped assety.

## 12. Zadání pro Claude Code

~~~text
Implementuj GARRY Embedded Framework 2.3 podle dokumentu
GARRY-CORE-FRAMEWORK-2.3-SPEC.md.

Nejdůležitější pravidla:
- Framework musí být obsažen uvnitř každého GARRY pluginu.
- Neexistuje samostatný koordinační plugin a GARRY Default není povinný.
- Každý GARRY plugin musí fungovat samostatně bez všech ostatních GARRY pluginů.
- Mezi jednotlivými GARRY pluginy nesmí vzniknout žádná závislost.
- Každá kopie Frameworku musí mít pluginově specifický namespace; nesmí vzniknout
  žádná globální třída Garry_Promotion_Registry ani class alias.
- Ke koordinaci aktivních pluginů použij pouze datovou request registry
  garry_framework_v23 a deterministickou volbu claims na plugins_loaded, priorita 999.
- Volba je pro každý shared claim samostatná: root_menu, group_overview,
  group_info a group_assets.
- Každý plugin vždy implementuje vlastní Přehled, Info, Log, verzi a lokální ikonu.
- Když je aktivních více pluginů, shared prvky zobrazí jen zvolený vlastník;
  ostatní pluginy stále zobrazí vlastní Přehled, Info a Log.
- Bez ostatních GARRY pluginů daný plugin vytvoří své vlastní top-level menu.

Bezpečnost:
- Nepoužívej eval, obfuskaci, vzdálené vykonávání kódu ani remote config.
- Neinstaluj, neaktivuj ani neaktualizuj žádný plugin.
- Neměň .htaccess, databázový prefix, uživatele, login URL ani bezpečnostní nastavení.
- Neukládej hesla, tokeny, e-maily, IP adresy nebo formulářová data do manifestů či logů.
- U všech mutačních admin akcí zachovej capability, nonce, sanitizaci a escapování.
- Framework CSS/JS načítej pouze na vlastních obrazovkách.
- Needituj pluginy třetích stran.

Postup:
1. Inventarizuj staré definice frameworku v každém GARRY pluginu.
2. Vytvoř testovaný template Frameworku s pluginově specifickým namespace.
3. Nejprve migruj Denní menu, Kategorie pokojů a Sezónu / čekací list.
4. Každý migrovaný plugin testuj samostatně, s jedním dalším pluginem,
   s více pluginy a po deaktivaci zvoleného vlastníka claims.
5. Po každé migraci proveď PHP lint a test retence logu.
6. Na závěr napiš report: změněné soubory, testy, legacy zbytky, rollback.

Pokud by krok znamenal ztrátu dat, zásah do cizího pluginu nebo bezpečnostní
změnu webu, zastav se a vyžádej potvrzení.
~~~

## 13. Kritéria hotové verze 2.3

- Frameworkový template je vložen v každém migrovaném GARRY pluginu.
- Každá kopie má vlastní namespace odvozený od slugu pluginu.
- Žádný plugin nevyžaduje GARRY Default ani jiný GARRY plugin.
- Žádný migrovaný plugin nedefinuje Garry_Promotion_Registry ani neincluduje starý garry-framework.php.
- Každý plugin má Přehled, Info, Log, versioning, lokální ikonku a manifest.
- Pro více aktivních pluginů se každý shared claim zobrazuje právě jednou.
- Po deaktivaci libovolného pluginu je systém bez zásahu funkční.
- Logy jsou lokální, omezené, bez citlivých dat a čitelné jen oprávněnému uživateli.
- Framework neprovádí automatické změny, síťové akce ani zásahy do cizích pluginů.

