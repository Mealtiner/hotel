# GRID Hotel — nasazovací checklist

Aktuální verze: **child theme 1.11.0**, **plugin gridhotel-core 1.1.4**.
Soubory: `web-final/wordpress/` (`grid-divi5-child.zip`, `gridhotel-core.zip`, `divi-json/`, `fluent-forms/`).

> ⚠️ **Od v1.11 mají JSONy obsah přímo v Divi Text modulech** (texty editovatelné ve
> Visual Builderu, připravené pro překladový plugin) — shortcody `[grid_*]` už stránky
> nepoužívají (výjimka: galerie). Stránky importované ze starších JSONů **přeimportuj**.
> Detaily: `DIVI-NATIVNI-OBSAH.md`.

## A) Základ (nejdřív)
- [ ] 1. Plugin **gridhotel-core 1.1.4** → Pluginy → Přidat → Nahrát → **Nahradit**
- [ ] 2. Motiv **child 1.3.2** → Vzhled → Motivy → Nahrát → **Nahradit**
- [ ] 3. **Nastavení → Trvalé odkazy → Uložit** (přegeneruje URL typů/taxonomie)
- [ ] 4. **Nástroje → GRID: Naplnit obsahem** → *Naplnit obsahem* (kategorie, zážitky, akce, gastro, reference)
- [ ] 5. Ověřit **ACF PRO** aktivní a v menu je **GRID Nastavení** (červené „G")

## B) Fluent Forms
- [ ] 6. **Fluent Forms → Forms → Import a Form** → `fluent-forms/gridhotel-fluentforms-import.json` (naimportuje 5 formulářů)
- [ ] 7. Zjistit **ID** formulářů a vložit v **GRID Nastavení → Formuláře**:
  - [ ] Firemní poptávka
  - [ ] Čekací list (Sezóna)
  - [ ] Kontakt / dotaz k pobytu
  - [ ] Newsletter
  - [ ] Dotazník spokojenosti
- [ ] 8. Nastavit u formulářů **e-mail notifikace** na `reservations@gridhotel.cz` + SMTP + antispam

## C) Globální hlavička a patička (Divi Theme Builder)
- [ ] 9. Theme Builder → **Global Header** → Import `divi-json/header-global.json`
- [ ] 10. Theme Builder → **Global Footer** → Import `divi-json/footer-global.json`
- [ ] 11. **Save Changes** v Theme Builderu

## D) Stránky — založit + importovat layout
Pro každou: Stránky → Přidat → název → Publikovat → Divi Builder → ⚙ Importovat → soubor → Uložit.

- [ ] 12. **Domů** — `page-domov.json` *(pokud už máš z dřívějška, přeskoč)*
- [ ] 13. **Pokoje** (slug `pokoje`? pozor na kolizi s taxonomií — použij `ubytovani`) — `page-pokoje.json`
- [ ] 14. **Zážitky** (slug `zazitky-u-okruhu`) — `page-zazitky.json`
- [ ] 15. **Gastronomie** (slug `gastronomie`) — `page-gastronomie.json`
- [ ] 16. **Sezóna 2026** (slug `sezona-2026`) — `page-sezona-2026.json`
- [ ] 17. **Firemní akce & svatby** (slug `firemni-akce-svatby`) — `page-firemni-svatby.json`
- [ ] 18. **Kontakt** (slug `kontakt`) — `page-kontakt.json`
- [ ] 19. **Jak se k nám dostanete** (slug `doprava`) — `page-doprava.json` *(mapa + Navigovat — hotové)*
- [ ] 20. **Dotazník spokojenosti** (slug `dotaznik-spokojenosti`) — `page-dotaznik.json`
- [ ] 21. **Ubytovací a reklamační řád** (slug `podminky`) — `page-podminky.json` → **vložit text 1:1**
- [ ] 22. **Ochrana osobních údajů** (slug `ochrana-osobnich-udaju`) — `page-ochrana-udaju.json` → **vložit text 1:1**
- [ ] 23. **Prohlášení o cookies** (slug `cookies`) — `page-cookies.json` *(Complianz shortcode — auto)*

## E) Homepage + obsah
- [ ] 24. **Nastavení → Čtení → statická stránka = Domů**
- [ ] 25. **GRID Nastavení → Kategorie pokojů** — nahrát náhledové fotky + zkontrolovat popisy (4 typy)
- [ ] 26. Ověřit **detail typu** pokoje (`/kategorie-pokoje/standard/` …) — srovnávací tabulka + galerie
- [ ] 27. **Vybrat styl karet T3** (1 / 2 / 3 / 4) — pak sjednotíme a smažeme štítky
- [ ] 28. Zážitky: zaškrtnout **„Doporučeno"** u 6 zážitků (GRID: Zážitky) + doplnit odkazy
- [ ] 29. **Právní texty** vložit 1:1 do podminky + ochrana (CZ; EN/DE přes Polylang)

## F) Menu, patička, footer odkazy
- [ ] 30. **Vzhled → Menu** — hlavní menu na založené stránky (nebo kotvy pro one-page)
- [ ] 31. Napojit footer právní odkazy (Cookies / Podmínky / Ochrana údajů) na založené stránky

## G) Finále
- [ ] 32. **LiteSpeed → Purge All** + tvrdý refresh, projet web (desktop + mobil)
- [ ] 33. Kontrola formulářů (testovací odeslání), map, telefonních odkazů

## H) Později (samostatné velké kroky)
- [ ] 34. GARRY mikropluginy (situace na trati + boční posuvník) + GRID Nastavení pod GARRY
- [ ] 35. Vícejazyčnost Polylang CZ/EN/DE (překlady stránek/CPT + slovník sekcí)
- [ ] 36. Rezervace → napojení na Bookolo (skládání URL z lišty)
- [ ] 37. Reference → Google recenze + ruční prokládání
- [ ] 38. SEO (Yoast titulky/sitemap/schema Hotel+Restaurant)

> ⚠️ Slug u „Pokoje": taxonomie kategorií má slug `kategorie-pokoje` a jednotlivé pokoje CPT `pokoje`. Aby nedošlo ke kolizi, dej stránce „Pokoje" slug **`ubytovani`** (nebo ji vynech — na homepage sekci Pokoje odkazuje kotva `#pokoje`).
