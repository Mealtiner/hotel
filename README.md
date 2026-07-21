# GRID Hotel — web (záloha projektu)

Záloha kompletního stavu projektu nového webu GRID Hotel Brno (WordPress + Divi 5).

## Struktura repozitáře

### Grafické návrhy (větev `graficke-navrhy` i zde ve složkách)
| Složka | Obsah |
|---|---|
| `web-navrh/` | 1. kolo — 12 grafických návrhů (`verze-1` … `verze-12`), každý ve vlastní složce, + rešerše a zadání |
| `web2/` | 2. kolo — 8 vybraných/upravených verzí |

Každou verzi lze otevřít přes její `index.html`.

### Aktuální řešení pro WordPress
| Složka | Obsah |
|---|---|
| `web-final/` | Finální statický návrh + `wordpress/` s exportem pro WP |
| `web-final/wordpress/divi-json/` | Divi 5 layouty všech stránek (import do Divi Library) |
| `web-final/wordpress/grid-divi5-child/` | Child theme pro Divi 5 |
| `web-final/wordpress/gridhotel-core/` | Core plugin |
| `web-final/wordpress/garry-*/` | GARRY pluginy (boční posuvník, hero křivka, situace na trati) |
| `web-final/wordpress/fluent-forms/` | Import formulářů Fluent Forms |
| `GARRY plugin/` | Starší samostatné pluginy (toggle text, informační bublina) |

### Podklady
| Složka / soubor | Obsah |
|---|---|
| `texty z webu/` | Stažené texty ze současného webu gridhotel.cz |
| `upravené texty/` | Přepracované texty pro nový web |
| `grid_hotel_0*.docx` | Rešerše (stávající web, konkurence, inspirace) |

## Co v repozitáři NENÍ (viz `.gitignore`)
- **`data klienta/`** — 1,7 GB fotek, log a tiskovin; obsahuje soubory přes 100 MB, které GitHub nepřijme. Zálohováno lokálně.
- **`no-git/`** — složka pro hesla, přístupy a citlivé údaje. Cokoli sem uložené se do gitu nikdy nedostane.
- `web-navrh.zip` — duplikát složky `web-navrh/`.

## Větve
- `main` — kompletní záloha (návrhy + WP řešení + podklady)
- `graficke-navrhy` — pouze grafické návrhy (`web-navrh/`, `web2/`)
