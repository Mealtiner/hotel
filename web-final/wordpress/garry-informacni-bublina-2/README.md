# GARRY – Informační bublina 2

Vyskakovací bublina s vlastním obsahem pro každou stránku, ve třech jazycích.
Nástupce pluginu *GARRY – Informační bublina* (dřív `cit-informacni-bublina`,
Centrum inovativní terapie), přepsaný pro GRID Hotel.

- Verze: 2.0.0
- Framework: GARRY Embedded 2.4 (vlastní namespacovaná kopie, bez závislosti na
  jiném GARRY pluginu)

## Co se kde nastavuje

Vzhled a obsah jsou schválně oddělené — vzhled je věc návrhu webu, obsah mění
redakce.

| kde | kdo | co |
|---|---|---|
| **GARRY nastavení → Informační bublina** | administrátor | barvy, rozměry, umístění, písmo, animace, jak často se bublina ukazuje |
| **GRID Nastavení → Informační bublina** | personál (Editor+) | na kterých stránkách se bublina zobrazí, šablona, texty CZ/EN/DE, fotka, tlačítko |

## Jazykové mutace

Jeden záznam platí pro stránku **i všechny její jazykové mutace**. Klíčem je ID
stránky v základním jazyce, ne ID konkrétní mutace — kdyby se klíčem stalo ID
mutace, musel by se stejný obsah zadávat třikrát a časem by se rozešel.

Texty se proto zadávají vedle sebe ve třech sloupcích. Když mutace text nemá,
vykreslí se česká verze: prázdná bublina by byla horší než bublina v jiném
jazyce, a redakce si toho na webu hned všimne.

## Pět šablon

| # | šablona | na mobilu |
|---|---|---|
| 1 | čtverec, jen text | beze změny |
| 2 | na šířku — vlevo fotka, vpravo text | překlopí se na 4 |
| 3 | na šířku — vlevo text, vpravo fotka | překlopí se na 5 |
| 4 | na výšku — nahoře text, dole fotka | beze změny |
| 5 | na výšku — nahoře fotka, dole text | beze změny |

Fotka se vybírá z knihovny médií. **Přizpůsobuje se rámu, ne rám fotce** —
`object-fit: cover` přebytek ořízne a poměr stran nikdy nedeformuje. Kolik
plochy fotka zabere, se řídí nastavením *Podíl fotky*.

## Jak často se bublina ukazuje

- **při každém načtení** — na testování
- **jednou za návštěvu** — výchozí, pamatuje `sessionStorage`
- **jednou za N dní** — pamatuje `localStorage`

Zapamatování drží prohlížeč návštěvníka, na server se nic nezapisuje. Klíč
obsahuje otisk obsahu, takže po úpravě textu se bublina ukáže znovu i tomu, kdo
předchozí verzi zavřel.

## Přístupnost

Bublina je `role="dialog"` s `aria-modal="false"` — nezavírá stránku pod sebou,
takže neblokuje čtečku. Zavírá se křížkem, kliknutím na ztmavené pozadí i
klávesou Esc. Při zapnutém `prefers-reduced-motion` se zobrazí bez animace a bez
zpoždění.

## Bezpečnost a výkon

- Žádný inline `<style>` ani `<script>` — nastavení jde do `data-` atributů a JS
  je přepíše do CSS custom properties. Manifest proto deklaruje `inline_assets`
  false/false.
- Rozměry se přijímají jako CSS délka včetně `clamp()`, ale validují se tvarem;
  `url()`, `expression`, `@import`, středníky a složené závorky projít nemohou.
- Barvy se ověřují proti hex i `rgb()/rgba()`, číselné hodnoty se ořezávají na
  rozsah.
- Texty prochází `wp_kses_post`, nadpisy `sanitize_text_field`.
- Administrační assety se načítají jen na vlastních stránkách pluginu.
- Na stránce je vždy nanejvýš jedna bublina, obsah pochází z jedné volby.

## Historie verzí

**2.0.0** (2026-09-06) — první verze pro GRID Hotel. Proti původní bublině
přibylo pět šablon, obsah zvlášť pro každou stránku ve třech jazycích, fotka
z knihovny médií a oddělení vzhledu od obsahu.
