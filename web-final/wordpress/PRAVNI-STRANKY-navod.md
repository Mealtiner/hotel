# Právní a faktické stránky — GRID

Importní layouty jsou v `divi-json/`. Importuj stejně jako ostatní stránky
(Stránka → Divi Builder → ⚙ Importovat → soubor).

| Stránka | Soubor | Obsah |
|---|---|---|
| Jak se k nám dostanete | `page-doprava.json` | **Hotové** — Google mapa + tlačítko „Navigovat →" + příjezd autem/MHD/letecky + adresa, shuttle, parkování. |
| Dotazník spokojenosti | `page-dotaznik.json` | **Hotové** — stránka s Fluent Form „Dotazník spokojenosti" (vlož jeho ID v GRID Nastavení → Formuláře). |
| Ubytovací a reklamační řád (podmínky) | `page-podminky.json` | **Kostra** — stylovaný obal `[grid_legal]`, text vlož 1:1 (viz níže). |
| Ochrana osobních údajů (GDPR) | `page-ochrana-udaju.json` | **Kostra** — stylovaný obal `[grid_legal]`, text vlož 1:1. |

## ⚠️ Proč právní texty nevkládám automaticky
Právní texty (podmínky, GDPR) **musí být doslovné (1:1)**. Automatické načtení je bohužel
**zkracuje/shrnuje**, což je u právního textu nepřípustné. Proto jsou tyto dvě stránky
připravené jako **stylovaná kostra** a text do nich vložíš přesně zkopírovaný z webu:

- Podmínky: https://www.gridhotel.cz/cz/podminky/
- GDPR: https://www.gridhotel.cz/cz/prohlaseni-o-ochrane-osobnich-udaju/

Jak: otevři stránku → Divi Builder → Text modul (uvnitř `[grid_legal …] … [/grid_legal]`) →
vlož zkopírovaný text. Styling (nadpisy, odstavce, seznamy) se aplikuje automaticky třídou `.grid-legal`.

## Vícejazyčnost (Polylang CZ/EN/DE)
Pro každou právní stránku vytvoř v Polylangu variantu v každém jazyce a vlož **text daného jazyka 1:1**:
- CZ z `/cz/...`, EN z `/en/...`, DE z `/de/...` odpovídající stránky na gridhotel.cz.
- U `page-doprava` a `page-dotaznik` stačí přeložit krátké nadpisy/popisky (zbytek jsou fakta/formulář).

## Doprava — co je uvnitř
- Mapa: Google Maps embed (bez API klíče) na adresu Ostrovačická 936/65.
- „Navigovat →": otevře trasu v Google Maps k hotelu.
- Adresu/telefon shuttle bere z GRID Nastavení (jde měnit).
