# GARRY Embedded Framework 2.4 – společné administrační rozhraní

## Účel

Verze 2.4 obnovuje společný administrační zážitek rodiny GARRY pluginů: barevné logo v levém WordPress menu, společný Přehled aktivních GARRY pluginů a společné Info o GARRY Promotion.

Toto UI je zároveň viditelná identita a přiměřená propagace GARRY Promotion v administraci webu. Nesmí měnit nezávislost pluginů ani zobrazovat citlivý bezpečnostní stav veřejně.

## Nezávislost

Každý GARRY plugin stále obsahuje vlastní namespacovaný Embedded Framework. GARRY Default není Core ani povinná závislost. Z aktivních pluginů se pro aktuální request deterministicky zvolí vlastník root_menu; po jeho deaktivaci funkci převezme další kompatibilní plugin bez zápisu do databáze.

Protokol:

- verze: 2.4.0;
- request registry: garry_framework_v24;
- shared claims: root_menu, group_overview, group_info a group_assets;
- každá kopie má vlastní namespace, LocalLog, manifest a lokální administraci.

## Povinné menu

Pokud je aktivní alespoň jeden GARRY plugin s Frameworkem 2.4:

1. zobrazí se právě jedno top-level menu GARRY nastavení;
2. používá lokální SVG asset assets/garry-logo.svg z právě zvoleného owner pluginu;
3. vždy obsahuje submenu Přehled a Info;
4. pod nimi obsahuje submenu jednotlivých aktivních GARRY pluginů;
5. každý plugin nadále zachovává svůj vlastní lokální Přehled, Info a Log.

Slug společného menu je garry-nastaveni. Je pevný; nesmí se odvozovat od slugu právě zvoleného pluginu.

## Přehled

Společný Přehled obsahuje:

- nadpis GARRY nastavení;
- krátkou informaci, že jde o společné administrační místo mikropluginů GARRY Promotion;
- karty všech aktivních účastníků Frameworku 2.4;
- na kartě lokální ikonu pluginu, jeho název a odkaz na vlastní administraci;
- patičku s verzí Frameworku a identitou GARRY Promotion.

Přehled nepředstírá, že některý plugin je povinný. Zobrazuje jen pluginy dobrovolně zaregistrované v aktuálním requestu.

## Info

Společné Info je lokální informační a propagační stránka GARRY Promotion. Obsahuje:

- barevné logo GARRY;
- stručné představení agentury;
- nabídku tvorby webů, vývoje pluginů, offline reklamy, online marketingu, automatizace, CRM/ERP/WMS a AI marketingu;
- odkazy na garry.cz, michal@garry.eu a podpora@garry.eu.

Obsah se skládá z lokálního PHP a lokálního SVG assetu. Nenačítá vzdálené HTML ani vzdálený config.

## Bezpečnost a oprávnění

- Společné Přehled a Info vyžadují manage_options.
- Bezpečnostní findings, skeny, adresy souborů, uživatelé, tokeny a osobní údaje se do společného Přehledu neposílají.
- GARRY Security má vlastní bezpečnostní stránku; skupinový Přehled může ukazovat pouze název tohoto pluginu.
- CSS se načítá jen na screens společného menu nebo konkrétního pluginu.

## Akceptační test

| Scénář | Očekávaný výsledek |
|---|---|
| Jeden aktivní GARRY plugin | jedno menu GARRY nastavení s logem, Přehledem, Infem a stránkou pluginu |
| Více aktivních GARRY pluginů | jedno root menu, jedna položka Přehled, jedna Info a jedna položka pro každý plugin |
| Deaktivace root ownera | po reloadu stejné menu převezme další aktivní plugin |
| GARRY Default neaktivní | společné menu i ostatní pluginy fungují |
| GARRY Default aktivní | Security zůstává samostatnou podstránkou, nikoli povinným Core |
| Cizí wp-admin screen | bez GARRY Framework CSS/JS |

