# GRID Hotel Core

Datový základ webu: vlastní typy obsahu, taxonomie kategorií pokojů, globální
nastavení (adresa, telefony, e-maily), registr modulů GARRY a granulární
oprávnění pro personál. Vykreslování sem nepatří — to je v GRID Hotel
Components a v GARRY pluginech.

## Moduly

| Soubor | Co dělá |
|---|---|
| `inc/seo.php` | hreflang pro kategorie pokojů, strukturovaná data `LodgingBusiness` |
| `inc/staff-permissions.php` | granulární capability pro karty v GRID Nastavení |

## Historie změn

### 2.4.0 — 2026-09-07
- Nový modul `inc/seo.php`: hreflang pro stránky kategorií pokojů (Polylang je pro tuhle taxonomii nevypisuje, ačkoli jsou překlady termů propojené) a strukturovaná data `LodgingBusiness` s adresou, telefonem a časy check-inu (Yoast umí jen `Organization`).

### 2.1.0 — 2026-09-07
- Srovnán manifest, který zůstal na 2.0.2, zatímco hlavička pluginu i konstanta `GRIDCORE_VER` běžely dál.
