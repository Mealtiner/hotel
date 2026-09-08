# GRID Hotel — Divi 5 Child

Vzhled webu: designové tokeny (barvy, písma, rozpal), skin sekcí a komponent,
lokální písma a doplňkový JavaScript. **Obsah ani vykreslování sekcí sem
nepatří** — ty vlastní `gridhotel-core` (data a nastavení),
`gridhotel-components` (shortcody sekcí) a jednotlivé GARRY pluginy.

## Dělba motiv / plugin

| Vrstva | Kdo vlastní | Příklad |
|---|---|---|
| Data a nastavení | `gridhotel-core` | adresa, telefony, taxonomie pokojů |
| Markup sekce | `gridhotel-components`, GARRY pluginy | `[grid_rooms]`, `[grid_menu_provozy]` |
| Struktura, na které závisí výstup pluginu | plugin | sazba jídelníčku do sloupců (`garry-denni-menu/assets/jidelnicek.css`) |
| Vzhled (barvy, písma, rozestupy) | **motiv** | `.menu-grp-l`, `.exp-item`, `.sez-card` |

`inc/shortcodes.php` drží záložní kopie sekčních shortcodů z doby před
vyčleněním do pluginu. Všechny registrace jsou hlídané `shortcode_exists()`,
takže se uplatní jen kdyby `gridhotel-components` nebyl aktivní. **Úpravy sekcí
proto patří do pluginu — změna v téhle kopii se na webu neprojeví.**

## Historie změn

### 3.38.0 — 2026-09-08
- Selektor karet turistických cílů narovnán na `h3` (plugin GARRY – Turistické cíle 1.2.0 změnil úroveň nadpisu).

### 3.37.0 — 2026-09-08
- Stránky kategorií pokojů přestaly stahovat Open Sans z `fonts.googleapis.com`. Divi má pro variabilní Open Sans víc handlů podle toho, jak se stránka staví; `et-builder-googlefonts-variable` (šablony mimo builder) v seznamu odhlašovaných chyběl. Volání odcházelo ještě před souhlasem s cookies, přitom se žádný jeho řez na webu nepoužívá.

### 3.36.0 — 2026-09-08
- Nadpisy, které mají být drobné verzálkové popisky v mono (skupiny vybavení na Ubytování, sloupce patičky, nadpisy nabídek v jídelníčku), se konečně v mono i vykreslují. Vynucené `h1,h2,h3{font-family:var(--f-head)!important}` — které existuje kvůli Divi 5 atomickým modulům — je tiše přebíjelo na Saira Condensed, takže se rozcházely s ostatními mikropopisky (`.kicker`, klíč skupiny).

### 3.35.0 — 2026-09-08
- Datum termínu (`.ev-date`) na stránce Sezóna je brandová červená `#C20E1A`, ne světlejší `#FF5A50`. Byla to poslední světlá červená mimo tmavé plochy — na celém webu má být pro základní prvky jedna červená.

### 3.34.0 — 2026-09-08
- Napojení formulářů na data webu přesunuto do GRID Hotel Components 1.13.0 (`inc/forms.php`): plnění pole „Typ pokoje", lokalizace kalendáře i značka formuláře poptávky. Motiv si veze jen knihovnu flatpickr pro rezervační lištu a i18n si vyžádá z pluginu.

### 3.33.0 — 2026-09-08
- Rozbalovací seznam „Typ akce" v poptávce firemních akcí dostal stejnou vlastní komponentu jako rezervační lišta a čekací list. Formulář se značí přes oficiální filtr `fluentform/form_class` podle názvu, ne podle ID — poptávka má tři jazykové mutace (22 / 23 / 24).
- Komponenta i pole Fluent Forms mají variantu pro světlý povrch; barvu určuje povrch (`.sec-light`), ne třída.
- Odstavce s inline `max-width:50%` jdou na úzkém displeji (≤ 959 px) přes celou šířku — na mobilu z nich byl sloupec na polovinu obrazovky. Nadpisů se to netýká, ty mají poloviční šířku záměrně.

### Starší verze
Změny motivu do 3.32.0 nebyly vedené v changelogu; historie je jen v gitu.
