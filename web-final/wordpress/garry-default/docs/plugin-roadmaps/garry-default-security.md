# GARRY Default / Security – roadmap

## Cíl

Samostatný bezpečnostní a provozní plugin s Embedded Frameworkem 2.3. Nesmí být Core ani závislostí ostatních pluginů.

## Současný stav

- verze 1.4.0, Security DB pro scans, findings a events;
- Embedded Framework 2.3 (vlastní namespace, manifest, Přehled/Info/Log), rovnocenný účastník voleb – bez povinné role;
- administrativní bezpečnostní dashboard, žádný veřejný obsah.

## Cílové plochy

| Plocha | Rozhodnutí |
|---|---|
| WordPress admin a Site Health | Ano |
| Shortcode / Gutenberg blok | Ne: nesmí zveřejnit security stav |
| Divi modul / Elementor widget | Ne: jen případná bezpečná editorová notice, ne frontend widget |

## Roadmap

1. ~~Migrovat na vlastní namespacovaný Embedded Framework 2.3, manifest, ikonu, Přehled, Info a Framework LocalLog.~~ Hotovo (verze 1.4.0).
2. Zachovat Security Timeline odděleně od Framework Logu; redigovat secrets, cesty, IP a osobní údaje.
3. Zabezpečit provider ownership a backup-gated změny; nikdy neměnit cizí plugin bez explicitní akce.
4. Přidat read-only framework health kontroly a diagnostiku legacy kopií.
5. Ověřit samostatný provoz, provoz s jedním a s více GARRY pluginy a převzetí shared claims po deaktivaci.

## Akceptace

Security UI funguje bez ostatních GARRY pluginů; absence Default neovlivní ostatní pluginy; žádný veřejný builder výstup neprozradí findings ani stav ochrany.

