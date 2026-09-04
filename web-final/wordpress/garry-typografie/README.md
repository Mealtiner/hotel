# GARRY - Typografie

Verze: 1.2.2
Datum publikace verze: 1. 9. 2026

Zakázkový mikroplugin pro WordPress / Divi. Sjednocuje typografii (fonty, velikosti, řádkování, rozpal, barvy)
celého webu na jednom místě, se samostatnou světlou a tmavou variantou pro každý prvek. Původně vytvořený pro
Centrum inovativní terapie Kliniky Podané ruce, aktuálně nasazený a nastavený pro GRID Hotel.

## Co plugin dělá

Plugin sjednocuje typografii celého webu na jednom místě. Místo toho, aby se písma nastavovala zvlášť v motivu Divi, zvlášť v jednotlivých modulech a zvlášť ve vlastních snippetech, řídí je jedna vrstva stylů.

Pro každou typografickou úroveň lze nastavit:

- font (z fontů nahraných přes Use Any Font, z vlastní registrace nebo ze systémových sad),
- tučnost písma,
- velikost zvlášť pro počítač, tablet a mobil,
- řádkování,
- **rozpal mezi písmeny**,
- převod velikosti písmen.

Dále pro každou úroveň:

- **barvu pro světlé pozadí** a zvlášť **barvu pro zelené pozadí**,
- barvu při najetí myší,
- spodní mezeru, vnitřní odsazení, zarovnání a podtržení.

Řízených úrovní je 22: základní text webu, odstavce, odkazy v textu, nadpisy H1 až H6, nadpisy vykreslované Divi moduly, odrážkové a číslované seznamy, buňky a záhlaví tabulek, hlavní menu, zelený pruh s odkazy na sekce, boční seznam odkazů, tlačítka, odkaz „Přečíst více…“, citace, popisky obrázků a formulářové prvky.

## Dvě barevná prostředí

Web používá bílé i tmavě zelené sekce a nadpisy v nich mají mít různé barvy – například podnadpisy jsou na bílém pozadí světle zelené, ale na zeleném bílé. Plugin proto u každé úrovně nastavuje dvě barvy.

Tmavé sekce se poznají podle selektorů v poli *Jak poznat zelenou sekci*. Divi si třídu `.et_pb_bg_layout_dark` doplňuje samo; má-li některá sekce vlastní třídu, dopíše se sem.

Živý náhled v pravém sloupci zobrazuje obě prostředí vedle sebe a mění se okamžitě při každé úpravě kteréhokoli atributu.

Protože jsou záhlaví i zápatí v Divi řešená jako globální prvky, projeví se změna nastavení na všech stránkách webu najednou.

## Proč plugin vznikl

Klient nahlásil několik připomínek k písmům na webu:

- u některých textů vypadává správný font u znaků s diakritikou,
- na řadě míst je příliš stažený rozpal písma,
- položky horního menu působí natěsno,
- typografie není napříč webem konzistentní.

Plugin proto kromě nastavování obsahuje i **diagnostickou část**, která ukáže, co je na webu skutečně nastavené, a **test vykreslení české diakritiky** přímo v prohlížeči.

## Síla přepisu

O tom, které CSS pravidlo na webu vyhraje, nerozhoduje pořadí v kódu, ale specificita selektoru a značka `!important`. Plugin proto nabízí dvě úrovně:

- **Běžná** – spolehlivě přebije základní styly motivu Divi, globální fonty nastavené v možnostech motivu i vlastní snippety WPCode bez `!important`.
- **Vysoká** – ke každému selektoru předřadí trojitý `:root`, čímž přebije i font nastavený přímo u konkrétního Divi modulu, třídy z globálních stylů WordPressu (`.has-nagel-font-family`) a vlastní CSS s vlastní třídou.

Toto řeší situaci, kdy font Nagel nejde vybrat v nastavení šablony, protože je do webu vložený mimo standardní nabídku Divi. Nastavení se dělá zde a Divi se nechá na výchozích hodnotách – v kódu tak zůstane jediný zdroj pravdy.

Plugin konfliktní pravidlo přebíjí, nemaže. Nejčistší je konfliktní deklaraci v Divi nebo ve snippetu skutečně odstranit; k tomu slouží diagnostika níže.

## Diagnostika

Karta „Kontrola nastavení fontů na webu“ vypisuje:

- fonty zaregistrované pluginem Use Any Font včetně řezů, chování při načítání a názvů souborů,
- upozornění, pokud je font registrovaný jen v jednom lehkém řezu, ale na webu se používá tučně (prohlížeč si pak tučnost dopočítává sám),
- globální fonty nastavené v motivu Divi,
- snippety WPCode, které rovněž zasahují do písem a mohou se s pluginem přetahovat.

## Společný rozpal písmen

Rozpal lze nastavit u každé úrovně zvlášť, ale i jedním centrálním polem pro všechny úrovně psané vybraným fontem. Vlastní hodnota u úrovně má vždy přednost; centrální pole se uplatní tam, kde je pole prázdné.

Používá se jednotka `em`, protože se počítá z velikosti písma daného prvku – jedna hodnota tak sedí na velký nadpis i na drobný popisek, přestože mají různou velikost. Rozumné hodnoty:

| Hodnota | Kdy použít |
| --- | --- |
| `normal` | rozpal navržený autorem písma, výchozí a nejbezpečnější volba |
| `0.02em` až `0.05em` | nadpisy psané verzálkami, kde písmena působí natěsno |
| `-0.01em` až `-0.02em` | velmi velké nadpisy, které naopak působí rozvolněně |

Pixely se pro nadpisy nehodí: `2px` je na malém nadpisu velký rozestup a na velkém skoro neznatelný.

## Test české diakritiky

Pravý panel obsahuje test, který v prohlížeči změří šířku vykresleného textu a porovná ji se záložními fonty. Rozlišuje zvlášť čárkované samohlásky (á, é, í, ó, ú, ý ze znakové sady Latin-1) a znaky s háčky a kroužky (č, ě, ř, š, ž, ů, ď, ť, ň z Latin Extended-A), protože ořezané webfonty obsahují typicky jen tu první skupinu. Rozliší čtyři stavy:

- font se používá včetně kompletní diakritiky,
- font zvládá čárkované samohlásky, ale znaky s háčky bere z náhradního písma,
- font se načetl, ale diakritiku nevykresluje vůbec,
- font se nepoužil vůbec.

Ukázky jednotlivých řezů jsou vykreslené s náhradou `monospace`, takže případný chybějící znak je vidět i pouhým okem.

## Font Nagel je součástí balíčku

Plugin s sebou nese soubory **Nagel Regular, Medium a Bold** ve formátech `woff2` i `woff` (složka `assets/fonts/`) a po instalaci je rovnou registruje pod názvem rodiny `Nagel`. Nic se nemusí nahrávat.

V nastavení se na ně odkazuje zkráceným zápisem `plugin:Nagel-Regular.woff2`. Ten se na skutečnou adresu překládá až při vykreslování, takže nastavení přežije přesun webu na jinou doménu i přejmenování složky pluginu.

Všechny tři řezy mají ověřenou **kompletní českou diakritiku včetně znakové sady Latin Extended-A**, tedy znaků č, ě, ř, š, ž, ů, ď, ť, ň.

## Nahrání dalších řezů písma

Plugin umí přijmout i vlastní soubory fontu přes knihovnu médií. Nahrávání souborů `woff2`, `woff`, `ttf` a `otf` je povolené pouze uživatelům s právem správce webu.

Ke každému řezu (Regular, Medium, Bold …) se přiřadí odpovídající soubor a plugin z nich vytvoří samostatné deklarace `@font-face` se stejným názvem rodiny. Prohlížeč si pak pro každou tučnost vezme skutečný soubor a **nedopočítává si tučnost sám** – to bývá příčina toho, že písmo vypadá jinak, než má.

Doporučený formát je `woff2`, volitelně doplněný o `woff` jako zálohu. Soubory `otf` a `svg` na web nahrávat netřeba.

Alternativně lze zvolit režim jednoho variabilního souboru pokrývajícího rozsah řezů.

Původní registrace z pluginu Use Any Font zůstává nedotčená.

## Nahrazení snippetu „CIT – Základní typografie WCAG“

Plugin pokrývá všechno, co dělal tento WPCode snippet: fluidní velikosti nadpisů přes `clamp()`, řádkování 1.7, spodní mezery u nadpisů, odstavců a seznamů, odsazení a zarovnání tabulkových buněk i podtržené odkazy s barvou při najetí. Tyto hodnoty jsou předvyplněné v tlačítku *Načíst doporučené nastavení pro CIT*.

Postup výměny:

1. V pluginu načtěte doporučené nastavení, zkontrolujte hodnoty a uložte.
2. Zapněte hlavní vypínač a na webu ověřte vzhled.
3. Teprve potom snippet ve WPCode deaktivujte.
4. Znovu otevřete diagnostiku – snippet už se nesmí objevit v seznamu konfliktních vrstev.

Oproti snippetu plugin navíc řeší barvy pro zelené pozadí, rozpal písmen u všech úrovní a font Nagel u nadpisů.

## Instalace

1. V administraci WordPressu otevřete Pluginy → Instalace pluginů.
2. Nahrajte ZIP soubor.
3. Aktivujte plugin.
4. V levém menu otevřete **GARRY nastavení → Typografie a fonty**.
5. Projděte diagnostiku, upravte nastavení a uložte.

Po aktivaci je hlavní vypínač **záměrně vypnutý** – samotná instalace tak vzhled webu nezmění. Plugin začne styly vypisovat až po vědomém zapnutí.

## Společné menu GARRY nastavení

Plugin se v administraci napojuje do společného menu **GARRY nastavení**, kam se automaticky řadí všechny mikropluginy od agentury GARRY Promotion. V menu se zobrazí pouze ty pluginy, které jsou aktivní – pořadí instalace ani aktivace nehraje roli. Vždy poslední položkou je stránka **Info** s prezentací agentury a kontakty.

Plugin si do menu hlásí položku **Typografie a fonty** s ikonou „editor-textcolor“.

## Vztah k ostatním GARRY pluginům

Odkaz **„Přečíst více…“** vykresluje plugin **Rozbalovací texty** a nastavuje mu vlastní písmo. Typografie má proto samostatnou úroveň *Odkaz „Přečíst více…“*, jejíž selektory mají záměrně vyšší specificitu, takže nastavení odsud vyhraje. Chování rozbalování zůstává i nadále na pluginu Rozbalovací texty.

Stejný princip platí pro zelený pruh s odkazy na sekce a pro boční seznam odkazů: **vzhled písma řídí tento plugin, rozvržení a chování zůstává na příslušném snippetu nebo pluginu.**

## Ukládání dat

Plugin ukládá nastavení do jedné položky ve WordPress options:

`garry_typografie_settings`

Při odinstalaci pluginu se tato položka smaže.

## Autor a podpora

Autor / dodavatel: GARRY Promotion
Web: https://garry.cz/
Realizace: Michal Truhlář, michal@garry.eu
Technická podpora: podpora@garry.eu

## Licence

Doprovodné texty, administrační popisy a dokumentace pluginu jsou poskytovány za podmínek licence Creative Commons Attribution / Uveďte původ. Při dalším použití nebo úpravách těchto textů uveďte autora: GARRY Promotion / Michal Truhlář.

Zdrojový kód pluginu je určen pro zakázkové použití v rámci tohoto webu.

## Správa verzí

Číslo verze je uvedené na třech místech a musí se vždy shodovat:

1. hlavička souboru `garry-typografie.php` (`Version:`),
2. konstanta `GARRY_TYPO_VERSION` tamtéž,
3. záhlaví tohoto souboru a poslední záznam v logu změn níže.

Konstanta zároveň slouží jako parametr verze u načítaného CSS a JS administrace, takže se po vydání nové verze prohlížečům nenabídne stará mezipaměť. Aktuálně načtená verze se pro kontrolu vypisuje i v horní části administrační stránky.

Používá se sémantické verzování: opravy zvyšují třetí číslo, nové funkce druhé, nekompatibilní změna uloženého nastavení první.

## Log změn

## Verze 1.2.2

- **Odstraněny zbylé viditelné stopy po CIT z admin UI.** Úvodní text na stránce nastavení, tlačítko „Načíst doporučené nastavení pro CIT" (a barvy, které posílalo do prohlížeče), a ukázkový náhledový obsah (menu, nadpisy, tabulka) – dřív text kliniky ("Historie a vize", "Ordinační doba", "Objednejte se"), teď GRID Hotel ("Kde po jízdě zastavíš", ceník pokojů, "Rezervovat pobyt").
- **Nadpisům a základnímu textu doplněny reálné barvy** z aktuálního webu GRID Hotel přímo do výchozích hodnot (`--fg`/`--muted` podle `.sec-light`/`.sec-dark` v `style.css`) – dřív byly barvy záměrně prázdné kvůli riziku přebití inline `style="color:var(--muted)"`, teď je hodnota shodná s tím, co web už stejně používá, takže bezpečně jde předvyplnit.

## Verze 1.2.1

- **Oprava: čerstvá instalace si sama zapnula font Nagel.** Migrace nastavení (`garry_typo_maybe_upgrade()`, běží při první návštěvě admin stránky) neuměla rozeznat "web tenhle plugin nikdy neměl nastavený" od "web má starší verzi nastavení" – u zcela nové instalace tak sama doplnila vlastní font Nagel (font předchozího klienta CIT) a zapnula `custom_font_enabled`, přestože výchozí hodnota pro nový web měla zůstat vypnutá. Migrace teď běží jen tam, kde už dřív existoval uložený záznam nastavení.
- Obecné výchozí hodnoty pluginu (`custom_font_family`, `custom_font_faces`) už nejsou natvrdo vyplněné fontem Nagel – nový klient si vlastní font zvolí v adminu sám, nemá se mu tam objevit název fontu, který nepoužívá.

## Verze 1.2.0

- **Rozšíření pro GRID Hotel: samostatná tmavá varianta pro font/velikost/tučnost/řádkování/rozpal/transformaci textu**, ne jen pro barvu jako dřív. Každá vlastnost má teď nepovinný `_dark` protějšek — nevyplněný `_dark` = na tmavém pozadí se použije stejná hodnota jako na světlém, takže není nutné duplikovat vše, jen to, co se má opravdu lišit.
- **Nové globální třídy pro ruční přiřazení v Divi builderu** (`.{prefix}-{úroveň}`, výchozí prefix `garry-typo`, např. `.garry-typo-h2`) — nezávisí na tom, jaký HTML tag/Divi třídu modul zrovna vykresluje. Tmavá varianta se použije buď automaticky (modul leží uvnitř `dark_context` sekce), nebo ručně přidáním `.{prefix}-dark` ke stejnému modulu.
- Nové výchozí nastavení `garry_typo_grid_hotel_elements()` — fonty/velikosti/řádkování/rozpal předvyplněné podle aktuálního child theme GRID Hotel (`--f-head`, `--f-body`, `clamp()` velikosti nadpisů). Barvy záměrně NEJSOU předvyplněné (web má na řadě míst záměrně odlišnou "muted" barvu textu přes inline `style`, kterou by `force_important` přebilo plošně) — správce je doplní ručně jen tam, kde je chce řídit centrálně.
- `dark_context` výchozí hodnota rozšířena o `.sec-dark` (GRID Hotel theme vlastní značka tmavé sekce), vedle původních Divi `.et_pb_bg_layout_dark`/`.et_pb_section_dark`.
- `custom_font_enabled` nově výchozí vypnuto pro tuto instalaci — GRID Hotel používá Google Fonts, které si theme načítá samo, ne vlastní nahraný font jako CIT (Nagel).
- Sladěna verze napříč souborem pluginu, README a manifestem (dřív `GARRY_TYPO_VERSION` zaostávala za hlavičkou souboru).

## Verze 1.1.3

- **Opravena tučnost písma.** Nastavená tučnost se při uložení zahazovala a na web se proto nikdy nedostala. Příčinou bylo, že PHP převádí číselné klíče pole na celá čísla, takže striktní porovnání uložené hodnoty `"700"` se seznamem povolených tučností vždy selhalo. Stejná chyba srovnávala všechny nahrané řezy písma na jedinou váhu 400, takže se tučný řez registroval jako obyčejný a prohlížeč si tučnost dopočítával.
- Při aktualizaci se poškozené hodnoty automaticky doplní z doporučeného nastavení.

## Verze 1.1.2

- Živý náhled nově obsahuje **všechny** typografické úrovně. Dosud v něm chyběl mimo jiné nadpis H1, takže jeho nastavení nebylo v náhledu vidět a působilo to, jako by se změny nepropisovaly.
- Nová karta **Nasazení na webu** hned nahoře: ukazuje stav hlavního vypínače, počet zapnutých úrovní a velikost vypisovaného CSS. Rovnou tak řekne, jestli plugin na web vůbec něco posílá.
- Výstup na webu je uvozený značkou `<!-- GARRY Typografie x.y.z -->`, takže jde nasazení ověřit ve zdrojovém kódu stránky.
- Diagnostika nově hlídá **fonty přebíjející systémová písma** – tedy webfonty registrované pod názvem jako `arial`. Názvy rodin se v CSS porovnávají bez ohledu na velikost písmen, takže takový webfont zastíní skutečný systémový Arial, a je-li ořezaný, začne u něj vypadávat česká diakritika.

## Verze 1.1.1

- Opraveno hlášení „Soubor se na uvedené cestě nepodařilo najít“ u řezů dodaných s pluginem. Soubory byly na místě, jen je kontrola existence neuměla dohledat podle zkráceného zápisu `plugin:`.
- Deklarace `@font-face` se nově vypisuje i v administraci, takže živý náhled a test diakritiky opravdu použijí vlastní font. Dosud se do administrace nenačítal a náhled proto padal do záložního písma.
- Přidáno povýšení uloženého nastavení při aktualizaci pluginu. Uložená konfigurace má přednost před výchozími hodnotami, takže se novinky z verze 1.1.0 do existujícího nastavení samy nepropsaly. Nově se při aktualizaci dosadí dodané řezy, zapne se jejich registrace a úrovně používající jednořezový font z Use Any Font se převedou na font z pluginu.
- Nové tlačítko **Použít soubory dodané s pluginem** pro ruční dosazení řezů.
- Do uloženého nastavení se ukládá značka verze, podle které povýšení pozná, že už proběhlo.

## Verze 1.1.0

- Součástí balíčku jsou soubory fontu Nagel v řezech Regular, Medium a Bold (`woff2` i `woff`); plugin je po instalaci rovnou registruje a nic se nemusí nahrávat.
- Zkrácený zápis `plugin:soubor.woff2` pro odkaz na font dodaný s pluginem, odolný vůči změně domény.
- Nové centrální pole **Společný rozpal písmen** pro všechny úrovně psané vybraným fontem.
- Test diakritiky nově rozlišuje čárkované samohlásky a znaky s háčky, takže rovnou pojmenuje ořezaný webfont bez sady Latin Extended-A.
- Odstavce a seznamy mají v doporučeném nastavení font vynucený explicitně, ne jen zděděný.
- Opraveno tlačítko pro výběr souboru fontu: doplněna závislost na skriptech knihovny médií a spolehlivější rozpoznání administrační stránky.

## Verze 1.0.0

- První vydání.
- 22 typografických úrovní: font, tučnost, velikost pro počítač, tablet i mobil, řádkování, rozpal písmen, převod velikosti písmen, spodní mezera, vnitřní odsazení, zarovnání a podtržení.
- Samostatná barva pro světlé a pro zelené pozadí u každé úrovně, včetně barvy při najetí myší.
- Nahrávání vlastních řezů písma přes knihovnu médií a jejich registrace samostatnými deklaracemi `@font-face`; alternativně režim variabilního fontu.
- Volba síly přepisu pro případ, kdy si prvek drží písmo nastavené u modulu v Divi nebo ve vlastním CSS.
- Diagnostika fontů Use Any Font, globálních fontů Divi a konfliktních snippetů WPCode, včetně přehledu, které vrstvy plugin přebije.
- Test vykreslení české diakritiky přímo v prohlížeči.
- Vlastní úroveň pro odkaz „Přečíst více…“, zelený pruh s odkazy na sekce a boční seznam odkazů.
- Živý náhled typografie na světlém i zeleném pozadí a zobrazení výsledného CSS.
- Hromadné akce: vynulování rozpalu u všech úrovní a načtení doporučeného nastavení pro CIT, které nahrazuje snippet „CIT – Základní typografie WCAG“.
