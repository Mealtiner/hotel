# GRID HOTEL — tlačítka: závazná specifikace a CSS

**Stav:** schválený vzorník 6. 9. 2026  
**Určeno pro:** Claude Code; implementace v pluginu GARRY Typografie / globálním CSS Grid Hotelu.  
**Rozsah:** všechny stránky, Divi 5 moduly, formuláře Fluent Forms, filtry galerie i CTA ve fotografických kartách.

Tento dokument nahrazuje ad-hoc varianty tlačítek. V HTML se používá vždy skutečný `<a>` pro navigaci a `<button type="button">` pro akci na aktuální stránce. `div` s `role="button"` je zakázaný.

## 1. Neměnná pravidla

1. Všechna interaktivní tlačítka mají minimální aktivní plochu **44 × 44 px**. Nejde jen o vizuální výšku; platí i pro textový terciární odkaz, filtry a mobilní ovládání.
2. Každé tlačítko má hover (pouze na zařízeních podporujících hover), `:focus-visible`, aktivní stav a respektuje `prefers-reduced-motion`.
3. Hover všech tlačítek obsahuje jen velmi jemný pohyb: `translateY(-1px) scale(1.012)`. Nesmí měnit rozložení okolního obsahu ani způsobit CLS.
4. Červená je vyhrazena pro primární akci a pro aktivní/hover rámeček sekundární akce. V jedné skupině je pouze jedno primární CTA.
5. Text nesmí být vložený jen jako ikona nebo jen v obrázku. Ikona šipky je dekorativní (`aria-hidden="true"`), název akce je vždy text.
6. Nikde nepoužívat globální pravidlo typu `.btn { color:#fff !important; }`. Rozbije sekundární tlačítko na světlém pozadí. Barva sekundární varianty se řídí kontextem povrchu.

## 2. Design tokeny

```css
:root {
  --gh-red: #C20E1A;
  --gh-red-bright: #E11622;
  --gh-ink: #17181B;
  --gh-paper: #F5F3F0;
  --gh-white: #FFFFFF;
  --gh-focus-blue: #005FCC;
  --gh-transition: 200ms ease;
  --gh-button-lift: translateY(-1px) scale(1.012);
}

/* Kontext určuje sekundární a terciární barvu, ne samotná třída .btn. */
.gh-surface--light {
  --gh-action-fg: var(--gh-ink);
  --gh-action-border: #A3A19D;
}

.gh-surface--dark,
.gh-surface--photo {
  --gh-action-fg: var(--gh-white);
  --gh-action-border: rgb(255 255 255 / 55%);
}
```

## 3. Povinné varianty

| Varianta | Použití | Výchozí stav | Hover / focus |
|---|---|---|---|
| Primární `.gh-btn--primary` | rezervace, odeslání formuláře, hlavní konverze sekce | červená plocha, bílý text | jasnější červená, jemný výjezd/zvětšení, červený svit; focus je vždy zřetelný |
| Sekundární `.gh-btn--secondary` | detail, vybavení, druhá rovnocenná akce | transparentní, text a rámeček dle povrchu | transparentní zůstává, rámeček je **červený**, jemný výjezd/zvětšení |
| Terciární `.gh-btn--tertiary` | „Zjistit více“, více položek, detail sekce | text, červené podtržení, šipka skrytá | červené podtržení se zkrátí a šipka se plynule objeví; celý odkaz se posune doprava |
| Filtr `.gh-filter` | galerie, kategorie a přepínače obsahu | transparentní rámeček | hover červený rámeček; zvolený filtr je červeně vyplněný a používá `aria-pressed="true"` |
| Formulář `.gh-btn--submit` | Fluent Forms a rezervační formulář | stejný jako primární | stejný jako primární; žádný samostatný vzhled pluginu |

## 4. HTML vzor

```html
<!-- Primární navigace -->
<a class="gh-btn gh-btn--primary" href="/rezervace/">Rezervovat pobyt</a>

<!-- Sekundární navigace -->
<a class="gh-btn gh-btn--secondary" href="/ubytovani/standard/">Detail &amp; vybavení</a>

<!-- Terciární navigace: šipka je dekorativní a objeví se až při interakci -->
<a class="gh-btn gh-btn--tertiary" href="/zazitky/">
  <span class="gh-btn__label">Zjistit více</span>
  <span class="gh-btn__arrow" aria-hidden="true">→</span>
</a>

<!-- Skupina filtrů: jde o změnu obsahu, proto button a aria-pressed -->
<div class="gh-filter-group" aria-label="Filtrovat galerii">
  <button class="gh-filter" type="button" aria-pressed="true">Vše</button>
  <button class="gh-filter" type="button" aria-pressed="false">Pokoje</button>
  <button class="gh-filter" type="button" aria-pressed="false">Gastronomie</button>
</div>
```

## 5. Referenční CSS

Vlož do jednoho globálního stylesheetu Grid Hotelu. Pokud zůstávají historické třídy `.btn`, `.btn-ghost`, `.sec-more` a `.gal-fbtn`, namapuj je na tyto varianty nebo je postupně nahraď. Nesmí existovat dvojí protichůdná definice.

```css
/* Společný základ */
.gh-btn,
.gh-filter {
  align-items: center;
  border: 1px solid transparent;
  box-sizing: border-box;
  display: inline-flex;
  font: inherit;
  justify-content: center;
  letter-spacing: .13em;
  min-height: 44px;
  text-decoration: none;
  text-transform: uppercase;
  transition:
    background-color var(--gh-transition),
    border-color var(--gh-transition),
    color var(--gh-transition),
    box-shadow 250ms ease,
    opacity var(--gh-transition),
    transform var(--gh-transition);
}

.gh-btn:focus-visible,
.gh-filter:focus-visible {
  /* Bílý vnitřní a modrý vnější kroužek fungují na světlém, tmavém i červeném podkladu. */
  box-shadow: 0 0 0 3px var(--gh-white), 0 0 0 6px var(--gh-focus-blue);
  outline: none;
}

/* Primární CTA */
.gh-btn--primary {
  background: var(--gh-red);
  border-color: var(--gh-red);
  color: var(--gh-white);
  font-size: 12px;
  font-weight: 800;
  padding: 11px 20px;
}

/* Sekundární CTA — barvu určuje .gh-surface--* */
.gh-btn--secondary {
  background: transparent;
  border-color: var(--gh-action-border);
  color: var(--gh-action-fg);
  font-size: 12px;
  font-weight: 800;
  padding: 11px 20px;
}

/* Terciární odkaz — výchozí červené podtržení a šipka až při interakci. */
.gh-btn--tertiary {
  color: var(--gh-action-fg);
  font-size: 12px;
  font-weight: 780;
  gap: 0;
  justify-content: flex-start;
  padding: 0;
  position: relative;
  transform-origin: left center;
}

.gh-btn--tertiary::after {
  background: var(--gh-red);
  bottom: 8px;
  content: "";
  height: 1px;
  left: 0;
  position: absolute;
  transition: width var(--gh-transition);
  width: calc(100% - 2px);
}

.gh-btn__arrow {
  display: inline-block;
  font-size: 17px;
  line-height: 1;
  margin-left: 8px;
  opacity: 0;
  transform: translateX(-7px);
  transition: opacity var(--gh-transition), transform var(--gh-transition);
}

/* Filtry */
.gh-filter {
  background: transparent;
  border-color: var(--gh-action-border);
  color: var(--gh-action-fg);
  cursor: pointer;
  font-size: 12px;
  font-weight: 750;
  padding: 0 14px;
}

.gh-filter[aria-pressed="true"] {
  background: var(--gh-red);
  border-color: var(--gh-red);
  color: var(--gh-white);
}

@media (hover: hover) and (pointer: fine) {
  .gh-btn--primary:hover {
    background: var(--gh-red-bright);
    border-color: var(--gh-red-bright);
    box-shadow: 0 0 22px rgb(194 14 26 / 50%);
    color: var(--gh-white);
    transform: var(--gh-button-lift);
  }

  .gh-btn--secondary:hover {
    background: transparent;
    border-color: var(--gh-red);
    color: var(--gh-action-fg);
    transform: var(--gh-button-lift);
  }

  .gh-btn--tertiary:hover {
    transform: translateX(4px) scale(1.012);
  }

  .gh-btn--tertiary:hover::after {
    width: calc(100% - 25px);
  }

  .gh-btn--tertiary:hover .gh-btn__arrow {
    opacity: 1;
    transform: translateX(0);
  }

  .gh-filter:hover {
    border-color: var(--gh-red);
    transform: var(--gh-button-lift);
  }
}

/* Musí následovat až po hover pravidlech: focus nesmí zmizet při kombinaci myši a klávesnice. */
.gh-btn:focus-visible,
.gh-filter:focus-visible {
  box-shadow: 0 0 0 3px var(--gh-white), 0 0 0 6px var(--gh-focus-blue);
}

/* Klávesnice dostane stejnou informaci jako hover terciárního odkazu. */
.gh-btn--tertiary:focus-visible {
  transform: translateX(4px) scale(1.012);
}

.gh-btn--tertiary:focus-visible::after {
  width: calc(100% - 25px);
}

.gh-btn--tertiary:focus-visible .gh-btn__arrow {
  opacity: 1;
  transform: translateX(0);
}

@media (prefers-reduced-motion: reduce) {
  .gh-btn,
  .gh-btn::after,
  .gh-btn__arrow,
  .gh-filter {
    transition-duration: 0.01ms;
  }

  .gh-btn:hover,
  .gh-btn:focus-visible,
  .gh-filter:hover {
    transform: none;
  }
}
```

## 6. Pravidla podle pozadí

### Světlá sekce (`.gh-surface--light`)

- Primární: červená plocha a bílý text.
- Sekundární: tmavý text a neutrální rámeček; hover přebarví **pouze rámeček** na červenou.
- Terciární: tmavý text, červené podtržení, šipka se objeví až při interakci.
- Filtr: tmavý text; vybraný filtr červená plocha/bílý text.

### Tmavá sekce (`.gh-surface--dark`)

- Primární je stejný jako na světlém povrchu.
- Sekundární: bílý text a poloprůhledný bílý rámeček; hover je průhledný, rámeček se přebarví na červenou.
- Terciární: bílý text, červené podtržení a interaktivní šipka.

### Fotografie / hero / obrazová karta (`.gh-surface--photo`)

- Pod tlačítky musí být gradient nebo tmavý overlay, aby byl text i rámeček čitelný v každém výřezu fotografie.
- Primární CTA zůstává červené. Sekundární CTA je průhledné s bílým textem; při hoveru dostane červený rámeček.
- Nedávat bílé tlačítko na světlou část fotografie bez vlastního neprůhledného pozadí.
- Kontrast se ověřuje na skutečné fotografii, nikoli na prázdném placeholderu.

## 7. Povinné mapování starých tříd

| Historická třída | Cílová varianta | Nutná oprava |
|---|---|---|
| `.btn` | `.gh-btn.gh-btn--primary` | odstranit globální `color: #fff !important` z obecné třídy |
| `.btn.btn-ghost` | `.gh-btn.gh-btn--secondary` | kontextová barva přes povrch; při hoveru červený rámeček a jemný pohyb |
| `.sec-more` | `.gh-btn.gh-btn--tertiary` | zvětšit klikací plochu na 44 px; přidat červené podtržení a skrytou šipku |
| `.gal-fbtn` | `.gh-filter` | při změně filtru aktualizovat `aria-pressed`; hover červený rámeček |
| `.ff-btn-submit`, `.ff_btn_style` | `.gh-btn.gh-btn--primary.gh-btn--submit` | sjednotit vzhled, focus i minimální výšku s primárním CTA |

## 8. Akceptační test před odevzdáním

- [ ] Na světlé, tmavé a fotografické sekci jsou primární, sekundární a terciární akce čitelné.
- [ ] Každé tlačítko má plochu minimálně 44 × 44 px při desktopu i mobilu.
- [ ] Hover je jemný (`-1 px`, `1.012`) a nevzniká posun sousedních prvků.
- [ ] Sekundární tlačítko při hoveru nezíská plnou červenou plochu; získá červený rámeček.
- [ ] Terciární odkaz má v klidu červené podtržení a šipku až při hoveru/focusu.
- [ ] `Tab` ukazuje zřetelný focus na každém ovládacím prvku; focus není překrytý HUDem, right railem ani obrázkem.
- [ ] Filtry mají vždy právě jednu hodnotu `aria-pressed="true"` a fungují klávesou Enter i mezerníkem.
- [ ] Je ověřeno `prefers-reduced-motion`; bez hover zařízení nevynucují pohyb.
- [ ] Kontrast textu, rámečku a focusu je ověřen podle WCAG 2.2 AA na reálném pozadí.

## 9. Co není dovoleno

- Nezavádět další barevné varianty CTA bez předchozího schválení.
- Nedělat klikatelné celé karty pomocí `onclick` na `div`; použít odkaz/tlačítko s viditelným názvem akce.
- Neschovávat text tlačítka při mobilech; zmenšit lze odsazení, nikoli aktivní plochu pod 44 px.
- Nepoužívat `outline: none` bez náhrady `:focus-visible`.
- Nepoužívat pohyb větší než definovaný jemný hover bez samostatného návrhu.
