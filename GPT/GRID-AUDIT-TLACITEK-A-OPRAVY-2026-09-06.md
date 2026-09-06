# GRID HOTEL — audit tlačítek a provedené opravy

**Datum:** 6. 9. 2026  
**Kontrolovaný cíl:** `http://gridhotel.local/` a všechny sdílené šablony/shortcody, které generují tlačítka na stránkách Grid Hotelu.  
**Kritérium:** [GRID-TLACITKA-SPECIFIKACE-A-CSS-2026-09-06.md](GRID-TLACITKA-SPECIFIKACE-A-CSS-2026-09-06.md).

## Výsledek

Globální systém tlačítek je nyní opraven v aktivní lokální child theme i v jejím zdrojovém zrcadle `web-final`. Opravy jsou společné pro úvodní stránku, O nás, ubytování a detail pokojů, zážitky a jejich detaily, gastronomii, sezónu, firemní akce, kontakt/dopravu, kariéru, galerii, rezervaci, právní stránky a 404 — tedy pro všechna místa, která používají sdílené třídy `.btn`, `.btn-ghost`, `.sec-more`, `.gal-fbtn` nebo Fluent Forms.

Ve zdrojích bylo nalezeno **87 použití** tlačítek/odkazových CTA. Nejde o 87 samostatných stylů: jsou odvozeny z níže uvedených společných variant.

## Co po opravě odpovídá specifikaci

| Oblast | Stav | Ověření |
|---|---|---|
| Primární CTA `.btn` | **ANO** | červená plocha, bílý text, min. výška 44 px, hover `translateY(-1px) scale(1.012)`, jasnější červená a jemný svit |
| Sekundární CTA `.btn.btn-ghost` na světlém podkladu | **ANO** | text se nyní skutečně přebarví podle povrchu (opraveno `!important`); hover zůstává transparentní a zvýrazní jen červený rámeček |
| Sekundární CTA na tmavém/fotografickém podkladu | **ANO** | bílý text, průhledné pozadí, červený rámeček při hoveru; odstraněno chybné červené vyplnění v kartách pokojů |
| Terciární odkaz `.sec-more` | **ANO** | min. 44 px, červené podtržení v klidu, šipka je dekorativní/skrytá a objeví se při hoveru nebo focusu |
| Filtry galerie `.gal-fbtn` | **ANO** | min. 44 px, hover červený rámeček, zvolený filtr je červený; `type="button"` a `aria-pressed` jsou v markupu i v JavaScriptu aktualizovány |
| Fluent Forms | **ANO** | odesílací tlačítka mají min. 44 px, shodné primární CTA a hover |
| Cookie banner | **ANO** | volby mají min. 44 px; přijmout je primární CTA, ostatní jsou sekundární s červeným rámečkem při hoveru |
| Hamburger a zavření HUD/menu | **ANO — velikost** | aktivní plocha je 44 px; opraven také rozpor `.is-open` v JavaScriptu vs. `.open` v CSS, který dříve bránil zobrazení mobilního menu |
| Klávesnicový focus | **ANO** | primární, sekundární, terciární, filtry a Fluent Forms mají viditelný dvoubarevný focus ring i přes světlé/tmavé/fotografické pozadí |
| Omezení pohybu | **ANO** | globální `prefers-reduced-motion` vypíná animace a transformace |

## Ověření v prohlížeči

Po změně byly na lokálním webu zkontrolovány reálné prvky na titulní stránce a `/o-nas/`:

- hlavní CTA: výška **48 px**;
- Divi modul `.btn`: výška **44 px**;
- sekundární tlačítko na světlém pozadí: tmavý text `rgb(22, 24, 27)`, výška **44 px**;
- sekundární tlačítko na tmavém/fotografickém pozadí: světlý text, výška **48 px**;
- `.sec-more`: výška **44 px**;
- Fluent Forms submit: výška **45–63 px** podle konkrétního formuláře;
- cookie volby: výška **44 px**.

## Konkrétně opravené soubory

### Nasazená lokální instalace

- `Local Sites/grid-hotel/app/public/wp-content/themes/grid-divi5-child/style.css`
- `Local Sites/grid-hotel/app/public/wp-content/themes/grid-divi5-child/inc/shortcodes.php`
- `Local Sites/grid-hotel/app/public/wp-content/plugins/gridhotel-components/assets/components.js`
- `Local Sites/grid-hotel/app/public/wp-content/plugins/gridhotel-components/inc/shortcodes/pages.php`

### Zdrojové zrcadlo

- `GRIDhotel/web-final/wordpress/grid-divi5-child/style.css`
- `GRIDhotel/web-final/wordpress/grid-divi5-child/inc/shortcodes.php`
- `GRIDhotel/web-final/wordpress/gridhotel-components/assets/components.js`
- `GRIDhotel/web-final/wordpress/gridhotel-components/inc/shortcodes/pages.php`

Zrcadla child theme i JavaScriptu komponent jsou po opravě shodná s lokální instalací. PHP soubory prošly `php -l` bez chyby.

## Zbývající odchylky / práce pro další krok

| Priorita | Prvek | Stav a doporučení |
|---|---|---|
| P1 | Pravý track-progress `.tp-point` | Má `min-height:24px`. To splní WCAG 2.2 AA (minimální cíl 24 px), ale **ne** interní pravidlo Grid Hotelu 44 px. Nezvětšovat bez návrhu: deset bodů by změnilo hustotu svislé navigace. Vhodné řešení je rozšířit neviditelnou hit area do stran, ne vertikální rozteč railu. |
| P1 | Jazykový přepínač `.lang a` | Má 24 × 24 px: WCAG AA splní, interní 44 px nikoli. Na desktopu lze kolem jednotlivých jazyků přidat transparentní plochu bez změny rozteče textu; na mobilu je nutné ověřit prostor vedle hamburgeru. |
| P1 | Mobilní menu | Funkční rozpor tříd je opraven, ale menu zatím nemá focus trap a po Escape/zavření nevrací focus na hamburger. Doplnit do `gridhotel-components/assets/components.js`. |
| P2 | Galerie / lightbox | Filtry jsou opravené. Pokud se pro fotky používá lightbox, musí mít `role="dialog"`, Escape, uzamčení focusu a návrat focusu na vybraný náhled. V aktuálním `components.js` není implementace lightboxu; ověřit, zda ji neposkytuje Divi/plugin. |
| P2 | Karty pokojů stylu 4 | Textové informace se vysouvají pouze hoverem. Musí být dostupné i po focusu odkazu/karty a bez hover zařízení. Nejde o samotné tlačítko, ale o obsah, který je jinak obtížně dostupný na mobilu. |

## Poznámka k repozitáři `GARRY plugin/GRID/new`

Tato kopie child theme není shodná s právě nasazeným lokálním souborem (`style.css` je starší a kratší). Záměrně do ní nebyl mechanicky přenesen patch: hrozilo by přepsání novějších úprav. Claude má přenést výše uvedený společný systém do nové pluginové architektury cíleně podle specifikace, ne kopírováním celého aktuálního child stylesheetu.
