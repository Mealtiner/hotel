# GARRY - Informační bublina

Verze: 1.3.1
Datum publikace verze: 28. 5. 2026

Zakázkový mikroplugin pro WordPress / Divi, vytvořený pro potřeby Centra inovativní terapie Kliniky Podané ruce.

## Co plugin dělá

Plugin umožňuje správcům webu zobrazit na vybraných stránkách krátké informační oznámení ve formě grafické bubliny. Je vhodný pro informace o aktuální kapacitě služeb, provozních změnách, novinkách nebo jiných důležitých sděleních.

## Instalace

1. V administraci WordPressu otevřete Pluginy → Instalace pluginů.
2. Nahrajte ZIP soubor.
3. Aktivujte plugin.
4. V levém menu otevřete **GARRY nastavení → Informační bublina**.
5. Upravte nastavení a uložte.

## Společné menu GARRY nastavení

Plugin se v administraci napojuje do společného menu **GARRY nastavení**, kam se automaticky řadí všechny mikropluginy od agentury GARRY Promotion. V menu se zobrazí pouze ty pluginy, které jsou aktivní – pořadí instalace ani aktivace nehraje roli. Vždy poslední položkou je stránka **Info** s prezentací agentury a kontakty.

Plugin si do menu hlásí položku **Informační bublina** s ikonou „format-status".

## Ukládání dat

Plugin ukládá nastavení do jedné položky ve WordPress options:

`cit_bubble_settings`

Při odinstalaci pluginu se tato položka smaže.

## Autor a podpora

Autor / dodavatel: GARRY Promotion
Web: https://garry.cz/
Realizace: Michal Truhlář, michal@garry.eu
Technická podpora: podpora@garry.eu

## Licence

Doprovodné texty, administrační popisy a dokumentace pluginu jsou poskytovány za podmínek licence Creative Commons Attribution / Uveďte původ. Při dalším použití nebo úpravách těchto textů uveďte autora: GARRY Promotion / Michal Truhlář.

Zdrojový kód pluginu je určen pro zakázkové použití v rámci tohoto webu.


## Verze 1.2.0

- Přidán živý náhled bubliny v pravém sloupci administrace.
- Upraveno rozložení sekce Obsah bubliny.
- Výchozí barva textu změněna na #222222.
- Stav přepínače nyní slovně vypisuje, zda se bublina zobrazuje, a na kterých stránkách.


## Verze 1.2.1

- Pravý panel „Živý náhled bubliny" nyní obsahuje tlačítko.
- Po kliknutí se bublina zobrazí přímo na administrační stránce podle aktuálně vyplněných hodnot.
- Statický zmenšený náhled v pravém panelu byl odstraněn.


## Verze 1.2.2

- Přidána volba typu pozadí: SVG obrázek nebo klasický rámeček / okno.
- Klasický rámeček má bílé pozadí, jemný zelený okraj, zaoblené rohy 20 px a zavírací křížek v pravém horním rohu.
- Přidána volba formy zobrazení: příjezd z levého horního rohu nebo klasický fade-in / fade-out.


## Verze 1.2.3

- Opraveno chování režimu fade-in / fade-out.
- Fade režim nyní respektuje nastavenou pozici X/Y a časování stejně jako příjezd z rohu.
- Platí pro SVG bublinu i klasický rámeček.


## Verze 1.2.4

- Přidán checkbox pro vynucené zobrazení bubliny uprostřed obrazovky.
- Volba platí pro SVG obrázek i klasický rámeček.
- Při zapnutí se ignorují pozice X/Y, ale časování a forma animace zůstávají zachované.


## Verze 1.2.5

- Přesun administrace pod společné menu **GARRY nastavení**.


## Verze 1.2.6

- Barevné logo GARRY v hlavním menu a ikonky podstránek ve společném menu.


## Verze 1.3.0

- Přepracovaný **GARRY framework 2.0** – dynamický registr pluginů. V menu se zobrazí pouze aktivní pluginy, nezáleží na pořadí instalace.
- Přidána nová stránka **Info** s prezentací agentury GARRY Promotion a kontaktními údaji. Vždy poslední položka v menu.
- Bezpečnostní revize: ověřeny capability checks, escaping výstupů, sanitization vstupů, nonces přes Settings API.
- Architektura připravená na přidávání dalších GARRY mikropluginů bez úprav existujících.


## Verze 1.3.1

- Plugin přejmenován v instalaci na **GARRY - Informační bublina** (původně CIT – Informační bublina).
- Opraveno zarovnání seznamu služeb v Info stránce: ikona a popis služby jsou nyní v jednom řádku, tučný text se už netlačí doprostřed.
