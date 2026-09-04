# GARRY Embedded Framework 2.4 – referenční zadání a podklady

> Stav: závazný podklad pro další vývoj rodiny GARRY pluginů.  
> Rozsah: WordPress administrativa; nesmí vytvářet závislost mezi GARRY pluginy.

## 1. Základní rozhodnutí

Framework 2.4 není samostatný koordinační plugin a **GARRY Default není povinný Core**. Každý jednotlivý GARRY plugin obsahuje vlastní kopii namespacovaného frameworku, včetně lokálního manifestu, logu a administrace. Plugin musí fungovat samostatně, s libovolnou kombinací ostatních GARRY pluginů i bez nich.

Pro spolupráci aktivních pluginů existuje pouze lehký requestový registr `garry_framework_v24`. Neobsahuje bezpečnostní data, neukládá se do databáze a žádný plugin jej nesmí potřebovat pro svou vlastní funkci.

## 2. Vizuální identita a menu

Je-li aktivní alespoň jeden kompatibilní GARRY plugin, administrace má právě jedno root menu:

- název: **GARRY nastavení**;
- pevný slug: `garry-nastaveni`;
- ikona: barevné lokální SVG logo GARRY (`assets/garry-logo.svg`), nikdy vzdálený obrázek;
- vzhled: konzistentní GARRY barevnost, karty, tlačítka, přepínače, záložky, ikony a patička.

Root menu musí obsahovat v tomto pořadí:

1. **Přehled** – společná stránka rodiny GARRY;
2. položku každého aktivního a kompatibilního GARRY pluginu;
3. **Info** – společná informační a propagační stránka GARRY Promotion.

Jeden z aktivních pluginů je pro aktuální request zvolen jako vlastník společných stránek. Volba musí být deterministická, bez zápisu do databáze. Po deaktivaci vlastníka ji bez konfigurace převezme další kompatibilní plugin. Vlastník nesmí být označován jako hlavní ani povinný plugin.

## 3. Společný Přehled

Přehled je jedno administrativní místo pro orientaci v rodině pluginů. Zobrazuje pouze účastníky, kteří se v aktuálním requestu dobrovolně zaregistrovali do Frameworku 2.4.

Každá karta obsahuje alespoň:

- lokální ikonu/dashicon pluginu;
- název a krátký popis;
- verzi pluginu;
- odkaz do vlastní administrace pluginu;
- volitelně neutrální provozní stav (například „aktivní“).

Přehled nesmí zveřejnit nálezy bezpečnostních skenů, cesty k souborům, údaje uživatelů, tokeny, licence, hesla nebo jiné citlivé provozní údaje.

## 4. Společné Info

Info je lokální informační a propagační stránka GARRY Promotion. Zachovává rozvržení a tón původní rodiny pluginů:

- barevné logo GARRY;
- krátké představení GARRY Promotion;
- nabídka tvorby webů, vývoje pluginů, offline reklamy, online marketingu, automatizace, CRM/ERP/WMS a AI marketingu;
- kontakty a odkazy: `garry.cz`, `michal@garry.eu`, `podpora@garry.eu`.

Stránka smí používat pouze text a assety distribuované v pluginu. Nesmí načítat vzdálené HTML, JavaScript, tracking ani konfiguraci. Propagace musí zůstat přiměřená, administrativní a nenarušovat pracovní rozhraní WordPressu.

## 5. Lokální administrace každého pluginu

Každý plugin má kromě vlastních funkcí vlastní administrativní oblast pod společným menu. Její minimální společná struktura je:

| Záložka | Povinnost | Význam |
|---|---:|---|
| Přehled | ano | Stav, krátké vysvětlení a rychlé akce konkrétního pluginu. |
| Info | ano | Účel, použití, kompatibilita, podpora a verze konkrétního pluginu. |
| Log | ano | Lokální auditní/provozní záznamy a změny verze; bez citlivých údajů. |
| Nastavení | podle potřeby | Konfigurace vlastní funkce pluginu. |
| Náhled | volitelně | Bezpečný náhled funkce nebo odkaz na náhled na webu. |

Náhled není univerzální povinný obsah: zapne se jen tam, kde dává smysl a kde ho plugin umí vykreslit bezpečně. GARRY Security například nesmí zobrazovat bezpečnostní data ve veřejném ani snadno sdílitelném náhledu.

## 6. Společné UI komponenty

Framework poskytuje názvosloví, CSS proměnné a přístupný vzhled pro:

- karty a přehledové gridy;
- záložky s aktivním a focus stavem;
- primární, sekundární a nebezpečné akce;
- přepínače/toggles včetně jasného popisku skutečného dopadu;
- stavové štítky: neutrální, v pořádku, upozornění, chyba;
- notices, prázdné stavy, potvrzovací dialogy a patičku;
- jednotné používání barevného GARRY brandu a lokálních ikon.

Všechny ovládací prvky musí fungovat klávesnicí, mít viditelný focus a nepředávat význam pouze barvou. Při postupné migraci se stávající ovládací prvky pluginu převádějí na tyto komponenty; funkční logika pluginu se tím nemění.

## 7. Oprávnění, bezpečnost a soukromí

- Skupinové Přehled a Info vyžadují `manage_options`.
- Lokální administrace používá vlastní schopnost pluginu; pokud není definována, minimálně `manage_options`.
- Každá akce měnící stav používá capability check, nonce, sanitizaci a escapování výstupu.
- Framework nevolá externí služby, neodesílá telemetrii a neukládá společný stav.
- Log je výhradně lokální pro konkrétní plugin, časově omezený a nesmí obsahovat tajemství ani osobní údaje nad nezbytnou míru.
- Framework nesmí vypínat, obcházet ani duplikovat ochrany Wordfence, All-in-One Security, UpdraftPlus, cache, SEO, Divi, Elementoru ani jiných pluginů.

## 8. Kompatibilita a odolnost

- WordPress bez Divi i Elementoru je vždy podporovaný základ.
- Divi a Elementor jsou volitelné integrace konkrétního pluginu; jejich absence nesmí způsobit fatální chybu ani skrýt jeho administraci.
- Každý plugin detekuje externí integrace sám a bezpečně se vrátí k WordPress shortcodu nebo nativnímu výstupu.
- Framework se nesmí spoléhat na GARRY Default, aktivaci jiné GARRY složky ani pevné pořadí načítání pluginů.
- Při nesouladu major verze frameworku se plugin nesmí pokoušet převzít cizí administraci; dál funguje sám.

## 9. Data a logování

Jediným zdrojem pro skupinový Přehled jsou manifesty aktivních účastníků requestového registru. Každý manifest deklaruje alespoň název, verzi, lokální slug, capability, ikonu a kompatibilitu frameworku.

Log a verzování patří vždy danému pluginu. Doporučené události: aktivace, deaktivace, změna vlastního nastavení, vlastní chyba, migrace a upgrade. Společný framework může určit formát události, ale nesmí vytvářet centrální log ani centrální databázovou tabulku.

## 10. Kontrolní kritéria pro každou aktualizaci

| Kontrola | Očekávaný výsledek |
|---|---|
| Jen jeden GARRY plugin | jedno barevné menu, Přehled, daný plugin, Info; lokální P/I/L. |
| Více GARRY pluginů | právě jedno root menu, žádné duplicitní Přehled/Info, jedna položka pro každý aktivní plugin. |
| Bez GARRY Defaultu | ostatní GARRY pluginy mají shodně funkční menu i lokální P/I/L. |
| Deaktivace vlastníka menu | po reloadu vlastnictví převezme jiný kompatibilní plugin. |
| Bez Divi/Elementoru | administrace i základní WordPress funkce pluginu dál fungují. |
| Neoprávněný uživatel | nevidí ani nemůže spouštět administrační akce. |
| Cizí wp-admin obrazovka | framework nenačítá vlastní CSS/JS. |
| Náhled | je-li implementován, neodhaluje citlivá data a má bezpečný fallback. |

## 11. Stav implementace 2.4

Aktuální implementace pokrývá barevné root menu, společné Přehled a Info, dynamický seznam aktivních pluginů, lokální P/I/L a základní společný vzhled karet a záložek. Další rozvoj má sjednotit konkrétní markup přepínačů ve stávajících pluginech a přidávat Náhled jednotlivě pouze tam, kde pro něj existuje smysluplný renderer.

Tento dokument doplňuje [administrativní specifikaci 2.4](GARRY-CORE-FRAMEWORK-2.4-ADMIN-UI.md), [specifikaci 2.3](GARRY-CORE-FRAMEWORK-2.3-SPEC.md) a [bezpečnostní dodatek](GARRY-WP-SECURITY-EMBEDDED-FRAMEWORK-2.3-ADDENDUM.md). Při rozporu má tento dokument přednost pro společné UI a nezávislost pluginů.
