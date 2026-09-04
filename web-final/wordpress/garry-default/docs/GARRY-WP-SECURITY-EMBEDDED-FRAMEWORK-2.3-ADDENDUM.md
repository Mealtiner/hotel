# GARRY Security – závazný dodatek pro Embedded Framework 2.3

Stav: závazná aktualizace základního zadání GARRY Default / GARRY Security  
Platnost: od verze GARRY Embedded Framework 2.3  
Účel: sladit bezpečnostní plugin s nezávislým modelem všech GARRY pluginů

## 1. Přednost a nahrazená rozhodnutí

Tento dokument doplňuje hlavní specifikaci GARRY Security a má přednost před všemi staršími ustanoveními, která:

- označují GARRY Default, GARRY Core nebo GARRY Security za jediného poskytovatele shared frameworku;
- vyžadují GARRY Default pro funkčnost jiného GARRY pluginu;
- předpokládají jednu společně deklarovanou PHP třídu nebo globální registry;
- požadují, aby se jiný GARRY plugin při neaktivním GARRY Default bezpečně vypnul.

Platí naopak toto:

1. GARRY Default / GARRY Security je jeden samostatný GARRY plugin.
2. Obsahuje vlastní embedded kopii Frameworku 2.3 s vlastním namespace.
3. Není hostitelem, povinným Core ani závislostí jiného pluginu.
4. Může být zcela odinstalovaný, aniž by to porušilo funkčnost ostatních GARRY pluginů.
5. Pokud je aktivní s dalšími GARRY pluginy, účastní se pouze neutrální requestové registry a volby shared claims.

Referenční pravidla frameworku jsou v dokumentu GARRY-CORE-FRAMEWORK-2.3-SPEC.md. V případě rozporu má ochrana proti ztrátě přístupu a dat přednost, následně tento dodatek a specifikace Embedded Frameworku 2.3.

## 2. Nová role GARRY Default / GARRY Security

GARRY Default je bezpečnostní a provozní plugin. Jeho produktová odpovědnost zůstává:

1. audit a evidence zabezpečení;
2. zjišťování providerů a prevence kolizí;
3. bezpečné workflow pro explicitně schválené změny;
4. bezpečnostní dashboard, historie a doporučení.

Navíc je plnohodnotným účastníkem Embedded Frameworku 2.3:

| Oblast | Povinnost GARRY Default |
|---|---|
| Embedded framework | obsahuje vlastní soubory Frameworku 2.3 v namespace odvozeném od svého slugu |
| Descriptor | registruje jen vlastní, validovaná a necitlivá metadata |
| Přehled | zobrazuje vlastní bezpečnostní stav, vlastní verzi, framework stav a volitelné integrace |
| Info | zobrazuje účel Security, capabilities, data/retenci, providery a dokumentaci |
| Log | vede oddělený omezený technický framework log a samostatnou security timeline |
| Ikona | používá lokální ikonu nebo povolený dashicon, například shield |
| Shared claims | může kandidovat na root_menu, group_overview, group_info a group_assets |
| Samostatný režim | bez jiných GARRY pluginů funguje beze změny |

Security nesmí vystupovat jako vlastník existence jiného pluginu. Je-li aktivní, může být preferovaným kandidátem pro group_overview, ale nikdy výhradním kandidátem.

## 3. Descriptor GARRY Default

GARRY Default deklaruje stejná minimální metadata jako každý jiný plugin. Doporučená priorita je 50. Běžné obsahové pluginy používají například 100 a více; hodnota pouze určuje přednost ve vykreslení shared claimu, ne autoritu nebo závislost.

~~~php
array(
    'slug'                => 'garry-default',
    'name'                => 'GARRY – Security',
    'plugin_version'      => '1.3.0',
    'framework_protocol'  => '2.3.0',
    'framework_major'     => 2,
    'framework_minimum'   => '2.3.0',
    'framework_priority'  => 50,
    'local_menu_slug'     => 'garry-security',
    'icon'                => 'dashicons-shield',
    'claims'              => array(
        'root_menu',
        'group_overview',
        'group_info',
        'group_assets'
    ),
    'manifest_path'       => '/absolute/path/garry-plugin-manifest.json',
)
~~~

Když je GARRY Default deaktivovaný, náhradní vlastník se zvolí z ostatních kompatibilních aktivních pluginů při dalším requestu. Žádný plugin nesmí do databáze uložit, že GARRY Default má menu nebo dashboard vlastnit trvale.

## 4. Chování administračního rozhraní

### 4.1 GARRY Default jako jediný GARRY plugin

V samostatném režimu používá vlastní top-level menu garry-security. Obsahuje minimálně:

1. Bezpečnostní přehled
2. Findings a doporučení
3. Providery a ownership mapu
4. Historii bezpečnostních scanů a změn
5. Framework Přehled
6. Framework Info
7. Framework Log

Bezpečnostní přehled nenazývá nepřítomnost jiných GARRY pluginů chybou. V části Framework uvede stav Samostatný účastník, žádný další GARRY plugin není aktivní.

### 4.2 GARRY Default s dalšími GARRY pluginy

Je-li GARRY Default vlastníkem root_menu, vytvoří jediné společné parent menu. Je-li vlastníkem group_overview, zobrazí jeden souhrnný přehled aktivních účastníků Frameworku.

Je-li vlastníkem jiný plugin:

- GARRY Default vytvoří či připojí vlastní submenu podle pravidel Frameworku;
- bezpečnostní stránky, security timeline a risk workflow zůstávají v GARRY Default;
- druhý plugin smí ve skupinovém přehledu zobrazit pouze redigovaný read-only security summary, který GARRY Default dobrovolně zveřejní v descriptoru;
- druhý plugin nesmí měnit GARRY Security nastavení, findings, scan scheduler ani providery.

Každý plugin stále zobrazuje vlastní Přehled, Info a Log. Jednou se vykreslují jen skupinové prvky: parent menu, skupinový přehled, skupinové info a jeho assety.

### 4.3 Redigovaný bezpečnostní summary

GARRY Security smí do requestové registry zveřejnit pouze tento typ přehledu:

~~~php
array(
    'available'        => true,
    'last_quick_scan'  => '2026-08-30T10:15:00+00:00',
    'overall_status'   => 'warning',
    'critical_count'   => 0,
    'warning_count'    => 2,
    'details_url'      => 'admin.php?page=garry-security',
)
~~~

Nesmí zveřejnit název nalezeného souboru, cestu, uživatelská jména, e-mail, IP adresu, provider secrets, přesný obsah findingu ani interní evidence payload. Zobrazení tohoto shrnutí je čistě informativní. Vlastník group_overview nesmí vytvořit tlačítko pro opravu Security stavu.

## 5. Úprava bezpečnostní architektury

Původní logická architektura GARRY Security se doplňuje takto:

~~~text
GARRY Default / Security
├── Vlastní Embedded Framework 2.3
│   ├── pluginově specifický namespace
│   ├── descriptor a manifest
│   ├── request registry participant
│   ├── deterministic claim election
│   ├── vlastní Přehled / Info / Log
│   └── lokální ikona a versioning
├── Security Bootstrap & Environment
├── Control Catalog
├── Scanner & Job Queue
├── Evidence Collectors
├── Provider Registry
├── Site Profile & Compatibility Catalog
├── Policy & Conflict Engine
├── Change Transaction Engine
├── Findings, Events & Retention
└── Security Dashboard, alerts a Site Health
~~~

Embedded Framework nesmí být implementován uvnitř Security scanneru. Je to malá lokální administrativní/protokolová vrstva, která musí bootovat bezpečně i tehdy, když Security scan, cron nebo provider adapter selže.

## 6. Bezpečnostní kontroly navázané na Framework

GARRY Security rozšiřuje read-only katalog o následující kontroly. Nejsou oprávněním měnit jiný GARRY plugin.

| ID | Kontrola | Důkaz | Stav při problému | Remediace |
|---|---|---|---|---|
| GARRY-FW-001 | Starý globální framework | aktivní GARRY plugin obsahuje známou legacy registry nebo soubor | critical, pokud hrozí API fatal; jinak warning | aktualizovat pouze daný plugin na 2.3 |
| GARRY-FW-002 | Chybějící/invalidní manifest | lokální manifest participujícího GARRY pluginu | warning | aktualizovat nebo opravit vlastní release pluginu |
| GARRY-FW-003 | Neplatný descriptor | request registry obsahuje chybný slug, verzi, icon nebo duplicate slug | warning | opravit konkrétní plugin; nic neprovádět automaticky |
| GARRY-FW-004 | Neplatná volba shared claimu | stejný claim má více rendererů nebo žádného kompatibilního kandidáta | warning | opravit template/priority a provést smoke test |
| GARRY-FW-005 | Neizolovaný namespace | nový GARRY plugin deklaruje globální sdílenou třídu | critical | stáhnout chybný release a obnovit poslední kompatibilní verzi |
| GARRY-FW-006 | Framework log mimo politiku | log obsahuje citlivé klíče nebo překračuje retention | critical | zastavit logování, smazat jen explicitně cílené citlivé záznamy po záloze a opravit plugin |
| GARRY-FW-007 | Nescoped admin assets | Framework CSS/JS je na cizím admin screenu | medium | opravit enqueue podmínku v konkrétním pluginu |

Evidence musí být redigovaná. Pro GARRY-FW-006 se do Security timeline uloží jen název pluginu, kód kontroly a hash zjištění; nikdy citlivý log entry.

## 7. Vztah ke scanům, aktualizacím a zálohám

### Aktualizace

Když WordPress aktualizuje GARRY plugin, GARRY Security pouze:

1. zachytí standardní WordPress update událost;
2. vyvolá nebo naplánuje lehký read-only framework recheck;
3. porovná starou a novou verzi pluginu, manifestu a embedded protokolu;
4. uloží redigovanou bezpečnostní timeline událost;
5. při nekompatibilitě vytvoří finding a doporučí rollback či aktualizaci konkrétního pluginu.

Security nesmí sám provádět update, automaticky rollbackovat ani přepisovat manifest a frameworkové soubory.

### Zálohy a rizikové změny

Před bezpečnostní změnou platí stávající backup gate přes UpdraftPlus. Frameworkové metadata, menu claims, ikona a lokální log však nejsou důvodem pro risk workflow a nesmí se kvůli nim měnit jiný plugin.

Pokud aktualizace GARRY pluginu obsahuje datovou migraci, náleží to tomuto pluginu. GARRY Security jen vyhodnotí deklaraci migrace v manifestu a může doporučit čerstvou zálohu před ruční aktualizací.

### Scheduler

Framework election je requestová a nesmí používat cron, lock ani persistentní leader option. Security scheduler může jednou denně provést framework health scan, ale výsledek nesmí určovat, kdo vykreslí menu při běžném requestu.

## 8. Data, logy a retence

GARRY Default vede dvě oddělené evidence:

| Evidence | Vlastník | Obsah | Doporučená retence |
|---|---|---|---|
| Framework Log | jen GARRY Default | boot, verze, claim, kompatibilita, migrace | max. 200 položek nebo 90 dní |
| Security Timeline | jen GARRY Security | scan summary, findings state, explicitní změny, backup gate | dle Security master specifikace |

Mezi evidencemi se nepřenášejí citlivá data. Framework Log není náhradou Security Timeline a Security Timeline není rozšířeným debug logem.

Povolené položky Framework Log jsou: framework_boot, plugin_version_changed, framework_version_changed, compatibility_state_changed, migration_completed a integration_changed. Zakázané jsou hesla, tokeny, nonce, e-maily, IP adresy, request data, celé URL query, cesty k citlivým souborům, SQL a stack trace.

## 9. Rozhraní pro ostatní GARRY pluginy

GARRY Default nesmí poskytovat povinné PHP API jiným pluginům. Ostatní GARRY pluginy mohou z requestové registry číst pouze standardní datové keys Frameworku 2.3 a pouze read-only summary, které GARRY Default dobrovolně deklaruje.

Zakázáno:

- volat metody třídy GARRY Default z jiného pluginu;
- includovat soubory z adresáře GARRY Default;
- sdílet Security options, capability nebo nonce;
- automaticky zapnout Wordfence, UpdraftPlus, 2FA, CAPTCHA či bezpečnostní hardening podle informace z registry;
- považovat neaktivní GARRY Default za framework error.

## 10. Akceptační kritéria GARRY Default

| Test | Očekávaný výsledek |
|---|---|
| GARRY Default sám | funguje celý Security modul, Přehled, Info, Framework Log, ikona i vlastní menu |
| GARRY Default chybí | jiný GARRY plugin funguje plně samostatně |
| GARRY Default + libovolný obsahový GARRY plugin | jeden shared parent menu/dashboard podle voleb; oba mají vlastní P/I/L |
| GARRY Default je deaktivačním vlastníkem claimu | po reloadu claim převezme jiný plugin bez zásahu do databáze |
| jiný plugin je vlastníkem claimu | GARRY Security zůstává dostupný jako child/local admin; security action fungují |
| aktualizace GARRY Default | recheck manifestu/protokolu, žádná automatická změna jiného pluginu |
| legacy GARRY plugin 2.0/2.1 | warning/critical finding podle rizika, žádné automatické patchování |
| Security scan/cron selže | Framework P/I/L a menu zůstávají funkční |
| nižší role | nevidí Framework Log ani Security Timeline bez odpovídající capability |

## 11. Instrukce pro Claude Code

~~~text
Aktualizuj pouze plugin GARRY Default / GARRY Security podle dokumentů
GARRY-CORE-FRAMEWORK-2.3-SPEC.md a
GARRY-WP-SECURITY-EMBEDDED-FRAMEWORK-2.3-ADDENDUM.md.

Architektonická pravidla:
- GARRY Default je samostatný plugin a nesmí být povinnou závislostí.
- Vlož do něj vlastní Embedded Framework 2.3 s pluginově specifickým namespace.
- Neimplementuj globální Garry_Promotion_Registry, class alias ani shared PHP API.
- Použij pouze datovou request registry a claim election podle Frameworku 2.3.
- GARRY Default může mít priority 50, ale nesmí být jediným kandidátem.
- Musí vždy fungovat samostatně se svým Přehledem, Info, Logem, ikonou
  a bezpečnostním dashboardem.
- Jiný GARRY plugin nesmí GARRY Default includovat ani volat.

Bezpečnost:
- Zachovej audit-only výchozí režim pro security kontroly.
- Security smí read-only vyhodnocovat framework metadata, ale nesmí upravovat
  jiné GARRY pluginy, jejich manifesty, logy, settings ani soubory.
- Neinstaluj/aktivuj pluginy, neměň .htaccess, databázový prefix, login URL,
  uživatele, provider settings ani files bez samostatně autorizovaného workflow.
- Nezapisuj citlivá data do Framework Logu, Security Timeline nebo dashboardu.
- Všechny akce chraň capability, nonce, sanitizací a escapováním.

Ověření:
1. Test GARRY Default samostatně.
2. Test s jedním migrovaným GARRY pluginem.
3. Test s více pluginy a vypnutím zvoleného vlastníka claimu.
4. Test s legacy pluginem 2.0/2.1 bez fatální chyby.
5. PHP lint, test retence logu, test scoped assetů a test oprávnění.
6. Vypracuj report změn a rollbacku; neupravuj pluginy třetích stran.
~~~

