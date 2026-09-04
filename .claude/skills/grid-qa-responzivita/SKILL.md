---
name: grid-qa-responzivita
description: Měření a akceptace responzivity webu GRID Hotel — jak spustit audit přes Playwright, co jednotlivé nálezy znamenají a kdy je stránka hotová. Načti při ověřování layoutu, hledání příčiny přetečení, kontrole počtu sloupců nebo před uzavřením jakékoli úpravy rozvržení.
---

# GRID Hotel — QA responzivity

Akceptační protokol je `GPT/GRID-RESPONSIVE-04-QA-A-AKCEPTACE.md`.
Nástroje jsou v `web-final/wordpress/tools/qa/`.

## Proč se neměří okem ani curlem

Stránka má `overflow-x: hidden` na `body`. To přetečení **schová**, ale
neopraví — vizuálně i v curlu vypadá vše v pořádku, zatímco obsah je zúžený
na desítky pixelů. `resp-audit.mjs` proto `overflow-x` na okamžik vypne,
změří skutečný `scrollWidth` a vrátí ho zpět. Bez toho měříš iluzi.

Divi navíc generuje část layoutu až za běhu a servíruje statické CSS z cache.
Curl ani čtení `style.css` proto neukáže, co uživatel vidí.

## Spuštění

```bash
cd web-final/wordpress/tools/qa
node resp-audit.mjs                                  # vše: 29 stránek × 3 jazyky × 11 viewportů
node resp-audit.mjs --vp=1600,1024,768,390           # jen referenční režimy
node resp-audit.mjs --pages=kontakt,doprava --lang=cs
node resp-audit.mjs --shots                          # + fullpage screenshoty do out/shots/
node qa-report.mjs                                   # vyhodnocení proti kritériím
node qa-report.mjs --rule=SLOUPCE --page=domov
```

Web musí běžet — viz [[grid-local-nasazeni]]. Po každém zásahu do CSS nebo
layoutu **nejdřív smaž Divi cache**, jinak měříš starý stav.

## Co která hláška znamená

| Nález | Význam | Kde se to opravuje |
|---|---|---|
| `OVERFLOW` | dokument je širší než viewport | najdi viníka přes `PRVEK`, oprav příčinu, ne `overflow-x` |
| `PRVEK` | konkrétní prvek přesahuje pravý okraj | obvykle chybí `min-width:0` na grid/flex potomkovi nebo zalamování dlouhého řetězce |
| `RAIL` | pravá sekční navigace je vidět/skrytá proti pravidlu | hranice je 960 px; stávající CSS ji chybně skrývá už na 1080 px |
| `KORIDOR` | kontejner je mimo bezpečné hranice | patří do globálních proměnných koridoru, ne do pravidla té sekce |
| `ROZJEZD` | sdílené kontejnery nemají shodný okraj | dva různé výpočty téhož koridoru; sjednotit na jeden |
| `SLOUPCE` | mřížka má jiný počet sloupců než matice | skoro vždy specificita: základ má `!important`, přepis ne |

`ROZJEZD` je nejcennější nález — ukazuje místa, kde web počítá tutéž hranici
víckrát a různě. To je přesně to, co brání globálním opravám.

## Povinná testovací matice

Referenční viewporty 1600 × 1000, 1024 × 768, 768 × 1024, 390 × 844, 320 × 700
plus hraniční dvojice 959/960, 640/641 a 1279/1280. Vždy CZ, EN i DE.

Tolerance: bezpečné hranice ±2 px, mezery ±4 px, **počet sloupců bez tolerance**,
horizontální přetečení 0 px, překrytí textu overlayem 0 px.

## Co nástroj neměří a musíš ověřit ručně

- mobilní menu: otevřít, projít focus, zavřít;
- jazykový přepínač bez JS;
- filtr a lightbox galerie;
- formuláře: povinná pole, chyba serveru, úspěšná zpráva;
- stav bez hoveru na dotykovém zařízení — hover nesmí být jediným nositelem informace;
- `prefers-reduced-motion`;
- zobrazení s přihlášeným adminem (admin bar posouvá fixní prvky);
- Safari vedle Chromia.

## Regresní brána před předáním

- PHP syntax check všech změněných souborů;
- kontrola manifestu každého změněného pluginu;
- GARRY framework nesmí regredovat;
- vyčistit Divi statickou CSS cache;
- screenshoty znovu bez přihlášení i s admin barem;
- sesynchronizovat web zpět do repa (viz [[grid-local-nasazeni]]).

## Stabilní ID stránek

Používej ID z `tools/qa/qa-config.mjs` (`domov`, `o-nas`, `pokoj-standard`,
`z-drift`, `kontakt`, …). Odpovídají číslům specifikací v `GPT/` a drží se
napříč reporty, commity i poznámkami. Viz [[grid-responzivita]].
