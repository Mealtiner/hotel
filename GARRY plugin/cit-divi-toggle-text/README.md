# GARRY - Toggle Text

Verze: 1.2.1
Datum publikace verze: 28. 5. 2026

Zakázkový mikroplugin pro WordPress / Divi, vytvořený pro potřeby Centra inovativní terapie Kliniky Podané ruce.

## Co plugin dělá

Plugin umožňuje správcům webu zkracovat delší textové bloky a zobrazit návštěvníkovi tlačítko pro rozbalení a sbalení textu.

Použití v Divi:

- pro světlé pozadí vložit CSS třídu `divi-toggle-text`
- pro tmavé / zelené pozadí vložit CSS třídu `divi-toggle-text2`

Třída se vkládá do Text modulu v Divi do pole:

Pokročilé → CSS ID a třídy → CSS třída

## Společné menu GARRY nastavení

Plugin se v administraci napojuje do společného menu **GARRY nastavení**, kam se automaticky řadí všechny mikropluginy od agentury GARRY Promotion. V menu se zobrazí pouze ty pluginy, které jsou aktivní – pořadí instalace ani aktivace nehraje roli. Vždy poslední položkou je stránka **Info** s prezentací agentury a kontakty.

Plugin si do menu hlásí položku **Rozbalovací texty** s ikonou „editor-expand".

## Ukládání dat

Plugin ukládá nastavení do jedné položky ve WordPress options:

`cit_divi_toggle_text_settings`

Při odinstalaci pluginu se tato položka smaže.

## Autor a podpora

Autor / dodavatel: GARRY Promotion
Web: https://garry.cz/
Realizace: Michal Truhlář, michal@garry.eu
Technická podpora: podpora@garry.eu

## Licence

Doprovodné texty, administrační popisy a dokumentace pluginu jsou poskytovány za podmínek licence Creative Commons Attribution / Uveďte původ. Při dalším použití nebo úpravách těchto textů uveďte autora: GARRY Promotion / Michal Truhlář.

Zdrojový kód pluginu je určen pro zakázkové použití v rámci tohoto webu.


## Verze 1.0.1

- V části Texty tlačítka přidáno nastavení fontu, velikosti a váhy textu tlačítka.


## Verze 1.0.2

- Opraven boční náhled.
- Náhled pro světlou i tmavou variantu nyní obsahuje funkční tlačítko pro rozbalení a sbalení ukázkového textu.


## Verze 1.0.3

- V části Texty tlačítka přidán rozpal písmen.
- Text tlačítka a nastavení vzhledu textu jsou odděleny do dvou vnitřních karet.
- Náhledové texty jsou delší.
- CSS třída v tmavém náhledu je lépe čitelná.


## Verze 1.1.0

- Zkrácení textu přesunuto do samostatného rámce.
- Přidán rámec pro HEX barvy pozadí světlého a tmavého náhledu.
- Zadané barvy pozadí se propisují do živého náhledu i do karet nastavení barev.


## Verze 1.1.1

- Sjednocena délka lorem ipsum textu ve světlém i tmavém náhledu.
- V rámci Barvy pozadí pro náhled upraven název na „Tmavé pozadí".
- Texty v tmavém poli pro barvu pozadí jsou bílé pro lepší čitelnost.


## Verze 1.1.2

- Přesun administrace pod společné menu **GARRY nastavení**.


## Verze 1.1.3

- Barevné logo GARRY v hlavním menu a ikonky podstránek ve společném menu.
- ⚠️ Verze obsahovala závažnou chybu (nedostupné helper funkce) – opraveno ve v1.2.0.


## Verze 1.2.0

- Přepracovaný **GARRY framework 2.0** – dynamický registr pluginů. V menu se zobrazí pouze aktivní pluginy, nezáleží na pořadí instalace.
- Přidána nová stránka **Info** s prezentací agentury GARRY Promotion a kontaktními údaji. Vždy poslední položka v menu.
- Opravena závažná chyba způsobená chybějícími funkcemi `cit_divi_toggle_font_choices()`, `cit_divi_toggle_font_weight_choices()`, `cit_divi_toggle_sanitize_css_size()` a `cit_divi_toggle_css_font_value()`, které se ztratily ve verzi 1.1.3.
- Bezpečnostní revize: ověřeny capability checks, escaping výstupů, sanitization vstupů, nonces přes Settings API.
- Architektura připravená na přidávání dalších GARRY mikropluginů bez úprav existujících.


## Verze 1.2.1

- Plugin přejmenován v instalaci na **GARRY - Toggle Text** (původně CIT – Divi Toggle Text).
- Opraveno zarovnání seznamu služeb v Info stránce: ikona a popis služby jsou nyní v jednom řádku, tučný text se už netlačí doprostřed.
