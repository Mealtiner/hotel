# GARRY – Turistické cíle

Místa v okolí (památky, příroda, gastronomie, doprava) jako filtrovatelné karty.
Obsah se spravuje v **GARRY nastavení → Turistické cíle**, na stránku se vkládá
shortcodem `[grid_turisticke_cile]`.

- Verze: 1.2.0
- Framework: GARRY Embedded 2.4 (vlastní namespacovaná kopie)

## Co plugin umí

| Část | Popis |
|---|---|
| Štítky | skupiny pro filtr — klíč, ikona ze zabudované sady a název ve třech jazycích |
| Místa | název a popis CZ/EN/DE, vzdálenost, odkaz na web lokality, pořadí, přepínač zobrazení |
| Výpis | mřížka 3 / 2 / 1 sloupec podle šířky, filtr podle štítků, vzdálenost vlevo nahoře, ikona štítku vpravo nahoře |

Vypnuté místo se nezobrazí ani se nepočítá do čísla u filtru. Chybí-li překlad
názvu nebo popisu, použije se česká verze.

## Shortcode

| Atribut | Výchozí | K čemu |
|---|---|---|
| `stitky` | `1` | `0` skryje panel filtru a vypíše jen karty |

## Historie změn

### 1.2.0 — 2026-09-08
- Název místa v kartě je `h3` místo `h4` — karty jsou ve stejné rovině jako vedoucí karta nad mřížkou. Úroveň navíc rozhodovala o písmu: motiv vynucuje nadpisové písmo jen pro h1–h3, takže názvy míst se jako jediné karty na webu vykreslovaly písmem běžného textu.

### 1.1.0 — 2026-09-08
- Administrace rozdělena na dvě záložky (Cíle / Štítky); každý cíl je sbalitelný.
- Pořadí karet určuje pozice v seznamu — přetažením myší nebo šipkami z klávesnice,
  místo ručního číslování.
- Stejná stránka je i v **GRID Nastavení → Turistické cíle**, pod vlastním
  oprávněním `garry_grid_manage_turisticke_cile`.
- Opraveny zbytky po pluginu Kategorie pokojů, ze kterého byl deskriptor zkopírovaný:
  plugin psal do jeho logovací option, dědil jeho oprávnění a v nápovědě i v historii
  verzí měl jeho obsah. Ikona dostala vlastní glyf (dřív přepisovala `admin-multisite`).

### 1.0.0 — 2026-09-08
- První verze. Data převzata z dosavadní pevné sekce „Co máte na dosah“
  (18 míst ve třech jazycích, 4 štítky).
