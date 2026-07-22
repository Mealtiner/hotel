# GRID Hotel — nasazovací checklist (migrace z Local na produkci)

> ⚠️ **Tento dokument nahrazuje starší checklist, který popisoval manuální import
> jednotlivých `divi-json/page-*.json` souborů.** Ten postup je od cca poloviny
> vývoje **zastaralý** — obsah webu dnes žije v databázi (Divi 5 nativní bloky,
> GARRY pluginy, Fluent Forms, WP menu, Yoast SEO), ne v samostatných JSON
> souborech pro každou stránku. Reimport starých `divi-json/*.json` by vrátil
> hlavičku, patičku, formuláře, menu i texty o měsíce zpátky. **Nepoužívat.**
>
> Aktuální postup nasazení je **plná migrace přes UpdraftPlus** (DB + soubory
> v jednom balíku) — to je jediný způsob, který přenese vše najednou beze ztrát.

Aktuální stav (2026-07-22): child theme **2.17.0**, plugin **gridhotel-core 1.5.0**,
6× GARRY mikropluginů, Fluent Forms **6.2.7** (18 formulářů), Polylang **3.8.6**
(CZ/EN/DE), ACF PRO, Yoast SEO, Complianz **7.5.0**, LiteSpeed Cache **7.8.1**,
UpdraftPlus **1.26.5**.

## A) Před migrací (na Local)

- [ ] 1. Ověřit, že web na Local běží bez PHP fatal chyb (`wp-content/debug.log` /
      `Local Sites → grid-hotel → Logs`) a bez viditelných chyb v konzoli.
- [ ] 2. **Nástroje → GARRY nastavení → Přehled** — zkontrolovat, že jsou
      všechny GARRY pluginy zaregistrované a v pořádku.
- [ ] 3. `web-final/wordpress/*.zip` v repu odpovídají aktuálně nasazeným
      verzím pluginů/šablony na Local (viz níže „Sync se zipy").
- [ ] 4. Provést zálohu **UpdraftPlus → Zálohovat a obnovit → Zálohovat nyní**
      (databáze + pluginy + šablony + uploads). Stáhnout všechny 4 soubory
      lokálně jako pojistku, nebo nechat v cloudovém úložišti, pokud je
      nakonfigurované.

## B) Na produkci — nová instalace

- [ ] 5. Založit čistou instalaci WordPress na produkčním hostingu
      (doména `gridhotel.cz` nebo dle zadání klienta).
- [ ] 6. Nainstalovat a aktivovat **UpdraftPlus** (stejná/kompatibilní verze).
- [ ] 7. **UpdraftPlus → Zálohovat a obnovit → Migrace/klonování webu** (nebo
      Restore → nahrát 4 soubory zálohy z kroku 4) → spustit obnovu.
      UpdraftPlus provede tabulkovou náhradu URL (Local → produkční doména)
      automaticky v rámci migračního průvodce — **nespoléhat na to naslepo**,
      po obnově vždy provést kontrolu URL (bod 10).
- [ ] 8. Po obnově: **Nastavení → Trvalé odkazy → Uložit** (přegeneruje
      rewrite pravidla pro CPT/taxonomie na nové doméně).

## C) Serverová konfigurace (mimo WordPress, nutná ručně)

Toto UpdraftPlus nepřenáší — je to konfigurace webového serveru, ne WP obsah.

- [ ] 9. Nasadit **`DEPLOY-nginx-hardening.conf`** (nebo Apache ekvivalent
      v `.htaccess`) do produkčního vhostu — blokuje veřejný přístup k
      `/wp-content/updraft/` (zálohy!), readme/license, zálohovací přípony
      souborů (.bak/.old/.orig/.sql/.zip) a XML-RPC.
- [ ] 10. Zkontrolovat/aplikovat `wp-config.php`: `DISALLOW_FILE_EDIT`,
      `WP_ENVIRONMENT_TYPE` → `'production'` (bez toho Yoast odmítá
      indexovat a některé bezpečnostní kontroly se chovají jinak),
      produkční DB přístupy, `WP_HOME`/`WP_SITEURL` na `https://` variantu.
- [ ] 11. Vynutit HTTPS (produkční certifikát) a **jednosměrný** redirect
      HTTP → HTTPS na úrovni webserveru (ne jen ve WP) — ověřit, že žádná
      jazyková mutace (`/en/`, `/de/`) nedělá downgrade zpět na HTTP.
- [ ] 12. `errors/htaccess-snippet.txt` (Apache) nebo `errors/nginx-snippet.conf`
      (nginx) — zapíchnout serverové chybové stránky 400/403/500/503 do
      vhostu, ať fungují i když WordPress/PHP neběží.
- [ ] 13. Produkční `php.ini`: `display_errors=Off`, `expose_php=Off`.
- [ ] 14. Zkontrolovat, že Mailpit/Local-specifické služby nejsou na
      produkci vůbec přítomné (jsou jen vývojářský nástroj Local by Flywheel).

## D) Kontrola po migraci

- [ ] 15. Projet homepage + všech 5 landing pages (Ubytování/Gastronomie/
      Sezóna/Firemní akce/Zážitky) ve všech 3 jazycích — HTML `view-source`
      kontrola `<h1>` (má být přesně 1), `<title>`, `<meta name="description">`.
- [ ] 16. **Vzhled → Menu** — ověřit, že se u produkční domény zobrazuje
      správné menu pro CZ/EN/DE (lokace `grid-hlavni`).
- [ ] 17. **Fluent Forms** — zkontrolovat/aktualizovat e-mail notifikace
      (SMTP na produkci se liší od Local/Mailpitu!) a odeslat testovací
      formulář z každého typu (kontakt, firemní, dotazník, newsletter,
      čekací list, poukaz) ve všech jazycích.
- [ ] 18. **GRID Nastavení → Sezóna & čekací list** — ověřit e-mail pro
      poptávky/objednávky poukazů je produkční adresa, ne testovací.
- [ ] 19. **Complianz** — znovu proskenovat skripty/cookies na produkční
      doméně (skenování je doménově závislé).
- [ ] 20. **Yoast SEO → Nástroje → Import a export** nebo `wp yoast index
      --reindex` (na produkci, kde `WP_ENVIRONMENT_TYPE=production`, na
      rozdíl od Local, tento příkaz nebude blokovaný) — přepočítá
      indexovatelné položky z migrované databáze.
- [ ] 21. Yoast **XML Sitemap** (`/sitemap_index.xml`) — zkontrolovat, že
      generuje produkční `https://` URL, odeslat do Google Search Console.
- [ ] 22. **LiteSpeed Cache → Purge All** + tvrdý refresh, projet web
      desktop + mobil (4 breakpointy).
- [ ] 23. Ověřit mapy, telefonní odkazy (`tel:`), e-mailové odkazy
      (`mailto:`), booking CTA na produkční `/rezervace/`.
- [ ] 24. Zkontrolovat práva rolí: Editor/personál nevidí GARRY nastavení
      (widgety/hero), vidí jen editaci pokojů/jídelníčku/sezóny/kariéry.

## E) Sync repa se zipy (dělat PŘED každou migrací, ne jen jednou)

Pluginy a šablona žijí ve `web-final/wordpress/*.zip` v repu (větev `main`).
Před balením ověřit shodu nasazených souborů na Local se soubory v repu:

```bash
diff -rq web-final/wordpress/grid-divi5-child "$LOCAL_PATH/wp-content/themes/grid-divi5-child"
diff -rq web-final/wordpress/gridhotel-core "$LOCAL_PATH/wp-content/plugins/gridhotel-core"
# … totéž pro každý garry-*/ plugin
```

Pokud diff nic nevypíše, přebalit zipy (`zip -qr <plugin>.zip <plugin>/`)
a commitnout. Zipy slouží jako **build artefakt pro ruční instalaci** (např.
když produkce neumožňuje UpdraftPlus migraci celého webu a je nutné nahrát
jen kód) — ne jako primární migrační cesta, tou je bod A–B výše.

## F) Známý backlog (neřešeno, mimo rozsah této migrace)

- [ ] Bookolo — napojení skutečného rezervačního widgetu (nyní statická ukázka)
- [ ] Google recenze / reference — odloženo
- [ ] Schema.org strukturovaná data (Hotel + Restaurant) — Yoast má základ,
      chybí custom schema pro pokoje/ceny
- [ ] CSP (Content-Security-Policy) hlavička — vědomě nenasazeno, viz komentář
      ve `functions.php` (Divi/Google Fonts/inline styly by vyžadovaly rozsáhlé
      ladění, riziko rozbití webu je vyšší než přínos v této fázi)
