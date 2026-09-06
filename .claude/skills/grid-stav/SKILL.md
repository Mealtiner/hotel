---
name: grid-stav
description: Jediné místo pravdy o projektu GRID Hotel — verze šablony a pluginů, soulad repa s nasazeným webem, stav gitu a stav obsahu. Načti a spusť VŽDY jako první krok každého úkolu na tomhle webu, ještě než něco přečteš nebo změníš.
---

# GRID Hotel — jediné místo pravdy

## Nejdřív tohle, vždy

```bash
python3 web-final/wordpress/tools/stav.py
```

Vypíše a zapíše `STAV.md`. Trvá pár sekund. **Udělej to jako první krok
každého úkolu**, i když si myslíš, že stav znáš — na tomhle projektu pracuje
víc nástrojů a stav se mění mezi sezeními.

`STAV.md` se **needituje ručně** a **necommituje**. Je to výstup, ne dokument.
Všechny údaje se čtou ze skutečnosti: verze z hlaviček souborů, rozdíly
porovnáním souborů, git z gitu, obsah z databáze. Ručně psaný přehled by se
rozešel — na tomhle projektu se to už dvakrát stalo.

## Co v něm hledat, než začneš

1. **Rozcházejí se repo a nasazený web?** Když ano, zjisti která strana je
   novější, a tu druhou dorovnej samostatným commitem „import reality".
   Nikdy neřeš rozpor nasazením staršího souboru z repa — přepsal bys práci,
   kterou udělal někdo jiný přímo v Localu.
2. **Jsou necommitnuté změny?** Můžou být od jiného nástroje. Přečti si je,
   než na ně budeš stavět.
3. **Sedí verze v manifestu s hlavičkou pluginu?** Když ne, někdo zapomněl
   manifest při zvýšení verze.
4. **Jsou neplatné Divi bloky?** Rozbitý blok se vykreslí prázdný a vypadá to
   jako chyba pluginu nebo cache. Nejčastější příčina je syrová uvozovka
   uvnitř JSON hodnoty — Divi ji jinde ukládá jako `"`.
5. **Běží web?** Když ne, postup spuštění je ve skillu [[grid-local-nasazeni]].

## Kam co patří

| Vrstva | Kde | Co tam patří |
|---|---|---|
| Vzhled a responzivita | `grid-divi5-child` | design tokeny, koridor, breakpointy, tlačítka, layoutové třídy |
| Obsah a data | pluginy, stránky, příspěvky | texty, fotky, jídelníček, pokoje, zážitky, formuláře |
| Struktura stránky | Divi 5 moduly v obsahu | sekce, řádky, sloupce, nadpisy, tlačítka |

Vzhled se nikdy neřeší v pluginu ani inline v obsahu. Obsah se nikdy
nezadrátuje do CSS. Strukturu vlastní Divi, ne PHP šablona.

Praktický důsledek: když se něco nezobrazuje, **nejdřív zjisti, ze které
vrstvy se to vykresluje**. Stránka postavená z granulárních Divi modulů se
neřídí shortcodem v šabloně, i když ten shortcode existuje a vypadá správně.

## Pravidla soužití více nástrojů

Na projektu pracuje Claude Code i ChatGPT. Aby si nepřepisovaly práci:

1. **Vždy začni `stav.py`**, skonči `stav.py`.
2. **Nikdy needituj soubory přímo v Localu.** Zdroj je repo, do Localu se
   nasazuje `rsync`em. Kdo edituje `~/Local Sites/...`, vyrobí rozpor, který
   ten druhý přepíše.
3. **Commituj a pushuj průběžně**, ne až na konci. Přerušená sezena bez
   commitu je ztracená práce; obojí se tu už stalo.
4. **Jedna větev.** Práci nedělit do souběžných větví bez domluvy.
5. **Než sáhneš na cizí rozpracovanou změnu**, ulož ji samostatným commitem
   „checkpoint". Pak teprve stav na ní.
6. **Zvyšuješ verzi pluginu? Zvyš i manifest.** `stav.py` nesoulad odhalí.
7. **Po zásahu do CSS nebo obsahu smaž Divi cache**, jinak měříš starý web.

## Na konci úkolu

```bash
python3 web-final/wordpress/tools/stav.py    # repo == web? git čistý?
git add -A web-final/ && git commit && git push
```

Viz [[grid-local-nasazeni]], [[grid-responzivita]], [[grid-tlacitka]],
[[grid-divi-moduly]], [[grid-qa-responzivita]].
