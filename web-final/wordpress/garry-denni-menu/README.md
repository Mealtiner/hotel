# GARRY – Denní menu

Denní, týdenní, stálá a večerní nabídka a nápojový lístek pro jednotlivé provozy.

## Historie změn

### 1.13.0 — 2026-09-08
- Sazba dlouhých nabídek do sloupců přesunuta z child motivu do pluginu (assets/jidelnicek.css + jidelnicek.js) — patří k vykreslování jídelníčku, ne k webu, na kterém běží.
- Odsazení, které po přeuspořádání sloupců zůstalo viset uprostřed sloupce, se nově odebere (projevovalo se prázdným místem mezi položkami).
- Assety se načítají jen na stránkách, kde je jídelníček; filtr `garry_menu_nacist_sazbu` umí načtení vynutit.

### 1.12.0 — 2026-09-08
- Nápojový lístek dostal nadpis nabídky — jako jediný ho neměl.

### 1.11.0 — 2026-09-08
- Shortcode [grid_menu_provozy] přijímá `nadpis="ne"` pro případ, kdy nadpis nese sekce stránky a druhý by se opakoval.

### 1.10.0 — 2026-09-08
- Skupina jídelníčku se vykresluje jako `<dl>` (název jídla `dt`, cena `dd`), popisek skupiny je připojený přes `aria-labelledby` (WCAG 1.3.1).
- Vykreslování skupiny sjednoceno do jedné funkce — dřív bylo čtyřikrát zkopírované.

### 1.9.0 — 2026-09-07
- Nový shortcode `[grid_menu_provozy]` — jídelníček všech provozů pod sebou, v pořadí podle nastavení a jen s nabídkami, které má provoz zaškrtnuté. Ve stránce nezůstává nic natvrdo.
