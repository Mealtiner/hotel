# GRID Hotel — akceptační protokol responzivity

Měřeno nástroji v této složce proti `GPT/GRID-RESPONSIVE-04-QA-A-AKCEPTACE.md`
a proti závazným vzorcům koridoru zadaným klientem.

## Rozsah

29 stránek × CZ/EN/DE × 11 viewportů = **935 měření**
(320, 390, 640, 641, 768, 959, 960, 1024, 1279, 1280, 1600 px)

## Výsledek

| Pravidlo | Na začátku | Po opravách |
|---|---:|---:|
| Horizontální přetečení dokumentu | 84 | **0** |
| Prvky za pravým okrajem viewportu | 329 | **0** |
| Počet sloupců proti matici T1–T8 | 160 | **0** |
| Viditelnost boční sekční navigace | 3 | **0** |
| Obsah mimo bezpečné hranice koridoru | 985 | **0** |
| Rozjezd okrajů sdílených kontejnerů | 515 | **0** |
| Chyby načtení | 0 | **0** |
| **Celkem** | **2076** | **0** |

Test překryvu (`test-prekryv.mjs`, krok čtvrtiny výšky okna):
**0 prvků** pod HUD widgetem nebo boční lištou.

## Koridor proti zadání

Změřeno na stránce s vykreslenou boční navigací, tolerance ±2 px.

| Viewport | Obsah začíná | Obsah končí | Zadání |
|---:|---:|---:|---|
| 1600 | 290 | 1340 | 290–1340 ✓ |
| 1440 | 290 | 1180 | 290–1180 ✓ |
| 1366 | 290 | 1106 | 290–1106 ✓ |
| 1280 | 290 | 1020 | 290–1020 ✓ |
| 1024 | 284 | 764 | 284–764 ✓ |
| 960 | 283 | 700 | 283–700 ✓ |
| 768 | 245 | 737 | 245–737 ✓ |
| 641 | 244 | 615 | 244–615 ✓ |
| 390 | 20 | 370 | 20–370 ✓ |

## Regresní brána

- PHP syntax všech změněných souborů: bez chyb
- GARRY Embedded Framework: 2.3, beze změny
- Repo a nasazený web: shodné
- Divi statická cache vyčištěna před každým měřením
- Klíčové stránky odpovídají HTTP 200, zrušená URL 301

## Co protokol nepokrývá

Ověřit ručně: mobilní menu (otevřít, projít focus, zavřít), jazykový
přepínač bez JS, filtr a lightbox galerie, odeslání formulářů včetně
chybových stavů, stav bez hoveru na dotykovém zařízení,
`prefers-reduced-motion`, zobrazení s přihlášeným adminem, Safari.

## Zopakování měření

```bash
cd web-final/wordpress/tools/qa
node resp-audit.mjs --vp=320,390,640,641,768,959,960,1024,1279,1280,1600
node qa-report.mjs
node test-prekryv.mjs > out/prekryv.json
```

Web musí běžet — postup je ve skillu `grid-local-nasazeni`.
