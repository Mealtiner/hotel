# GRID Hotel — pravidla pro AI nástroje

Na tomhle projektu pracuje víc nástrojů (Claude Code, ChatGPT / Codex).
Tenhle soubor je pro ně společný. Platí pro všechny stejně.

## 1. Než cokoli uděláš

```bash
python3 web-final/wordpress/tools/stav.py
```

Vypíše a zapíše `STAV.md` — **jediné místo pravdy** o projektu: verze šablony
a všech pluginů, jestli se repo shoduje s nasazeným webem, stav gitu a stav
obsahu v databázi. Trvá pár sekund.

Udělej to **jako první krok každého úkolu**, i když si myslíš, že stav znáš.
Mezi tvým minulým a dnešním během mohl na projektu pracovat někdo jiný.

`STAV.md` se **negeneruje ručně a necommituje**. Je to výstup, ne dokument.
Ručně psaný přehled se rozejde se skutečností — na tomhle projektu se to už
dvakrát stalo (repo bylo pět týdnů pozadu za nasazeným webem; jiný nástroj
editoval nasazené soubory a jeho práce se málem ztratila).

Co ve výpisu ověřit, než začneš:

- **Rozcházejí se repo a web?** Zjisti, která strana je novější, a tu druhou
  dorovnej samostatným commitem „import reality". Nikdy nenasazuj starší
  soubor z repa — přepsal bys cizí práci.
- **Jsou necommitnuté změny?** Můžou být od jiného nástroje. Přečti si je.
- **Sedí manifest s hlavičkou pluginu?** Když ne, někdo zapomněl manifest.
- **Jsou neplatné Divi bloky?** Rozbitý blok se vykreslí prázdný a vypadá to
  jako chyba pluginu nebo cache.

## 2. Kam co patří

| Vrstva | Kde | Co tam patří |
|---|---|---|
| Vzhled a responzivita | `web-final/wordpress/grid-divi5-child` | design tokeny, koridor obsahu, breakpointy, tlačítka, layoutové třídy |
| Obsah a data | pluginy, stránky, příspěvky | texty, fotky, jídelníček, pokoje, zážitky, formuláře |
| Struktura stránky | Divi 5 moduly v obsahu | sekce, řádky, sloupce, nadpisy, tlačítka |

Vzhled se **nikdy** neřeší v pluginu ani inline v obsahu.
Obsah se **nikdy** nezadrátuje do CSS.
Strukturu vlastní **Divi**, ne PHP šablona.

Praktický důsledek: když se něco nezobrazuje, nejdřív zjisti, **ze které
vrstvy se to vykresluje**. Stránka postavená z granulárních Divi modulů se
neřídí shortcodem v šabloně, i když ten shortcode existuje a vypadá správně.

## 3. Soužití nástrojů

1. **Zdroj je repo, ne Local.** Nikdy needituj soubory
   v `~/Local Sites/grid-hotel/...`. Do Localu se nasazuje `rsync`em z repa.
   Kdo edituje nasazené soubory, vyrobí rozpor, který ten druhý přepíše.
2. **Commituj a pushuj průběžně**, ne až na konci. Přerušené sezení bez
   commitu je ztracená práce. Stalo se to oběma nástrojům.
3. **Jedna větev.** Nedělit práci do souběžných větví bez domluvy.
4. **Cizí rozpracovanou změnu nejdřív ulož** samostatným commitem
   „checkpoint", teprve pak na ní stav.
5. **Zvyšuješ verzi pluginu? Zvyš i manifest** (`garry-plugin-manifest.json`).
6. **Po zásahu do CSS nebo obsahu smaž Divi cache**, jinak měříš starý web:
   `find ~/"Local Sites"/grid-hotel/app/public/wp-content/et-cache -mindepth 1 -delete`
7. **Neukončuj cizí procesy** (PHP-FPM, MySQL) bez ověření, co na nich visí.
   Když PHP-FPM restartuješ, spusť ho s `-c` a správným `php.ini`, jinak
   nezná socket databáze a celý web spadne na chybu databáze.

## 4. Na konci úkolu

```bash
python3 web-final/wordpress/tools/stav.py     # repo == web? git čistý?
git add -A web-final/ && git commit && git push
```

## 5. Závazné podklady

| Téma | Kde |
|---|---|
| Responzivita, koridor, breakpointy | `GPT/GRID-RESPONSIVE-*.md` |
| Tlačítka | `GPT/GRID-TLACITKA-SPECIFIKACE-A-CSS-2026-09-06.md` |
| Akceptační protokol responzivity | `web-final/wordpress/tools/qa/AKCEPTACE.md` |
| Měřicí nástroje | `web-final/wordpress/tools/qa/` |

Claude Code má tytéž věci rozepsané jako skills v `.claude/skills/`
(`grid-stav`, `grid-responzivita`, `grid-tlacitka`, `grid-divi-moduly`,
`grid-qa-responzivita`, `grid-local-nasazeni`). Když je čteš jako jiný
nástroj, ber je jako závazné taky — je to tentýž obsah.
