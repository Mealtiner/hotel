# GARRY – Boční posuvník

Svislá navigace po sekcích stránky s ukazatelem postupu.

## Historie změn

### 1.9.1 — 2026-09-08
- Výplň zase začíná u horní hrany lišty; ve verzi 1.9.0 se posunula až k prvnímu bodu a čára tak nezačínala od začátku.

### 1.9.0 — 2026-09-08
- Červená čára se řídí body lišty, ne procentem sjetí stránky — dřív ukazovala jinam než rozsvícený bod (na titulní straně až o dva body).
- Aktivní bod i výška čáry vycházejí z jednoho výpočtu, takže se nemůžou rozejít.
- Po načtení stránky je čára prázdná a roste teprve s rolováním.
- Poloha sekcí se měří vůči dokumentu, ne přes offsetTop (Divi sekce mají různé umístěné předky a výpočet ujížděl o sekci).

### 1.8.0 — 2026-09-08
- Popisek START patří jen titulní straně; na podstránkách jdou body od T1, aby odpovídaly vodoznakům sekcí.
- Sjednoceny verze v hlavičce, konstantě a manifestu (byly rozjeté).

