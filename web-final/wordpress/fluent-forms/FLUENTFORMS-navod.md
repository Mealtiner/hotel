# Fluent Forms — import & napojení do webu GRID

## Soubor
`gridhotel-fluentforms-import.json` — **5 formulářů** v jednom importovatelném souboru (jediný soubor ve složce).

## ✅ Co je opravené
1. **Prázdné formuláře** (první pokus): pole měla `uniqElKey` místo **`uniqueId`** a chyběl **`editor_options.template`** → renderer je přeskočil.
2. **Fatální chyba editoru** (druhý pokus): složené pole **Jméno** (`input_name` s vnořenou strukturou) editor Fluent Forms na PHP 8.4 neustrávil a spadl.

Aktuální verze používá **jen jednoduché, bezpečné typy polí** (`input_text`, `input_email`, `input_number`, `textarea`, `select`), každé se správným **`uniqueId`**, **`editor_options.template`**, inkrementálním `index` a plnými `validation_rules`. Jméno je rozdělené na dvě textová pole `jmeno` + `prijmeni`. `form_meta` obsahuje jen **formSettings** (potvrzovací hláška); e-mail notifikaci si zapni v UI.

## Import (2 min)
1. **Fluent Forms → Forms** → tlačítko **„Import a Form"** (nahoře vpravo).
2. Nahraj `gridhotel-fluentforms-import.json` → naimportuje se všech **5** formulářů.
3. Otevři každý v editoru a ověř, že **pole jsou vidět** (měla by být). U každého zjisti **ID** (v seznamu formulářů nebo v URL editoru `form_id=…`).

## Napojení na web
1. **GRID Nastavení → záložka „Formuláře (Fluent Forms)"**.
2. Vlož **ID** (např. `3`) nebo celý shortcode `[fluentform id="3"]` do příslušného pole:
   - Firemní poptávka
   - Čekací list
   - Kontakt / dotaz k pobytu
   - Newsletter
   - Dotazník spokojenosti
3. Ulož. Šablona automaticky **nahradí placeholder** vyrenderovaným Fluent Form. Prázdné pole = zůstane neaktivní placeholder.

## Kde jsou formuláře na webu
| Sekce | Formulář | Pole v GRID Nastavení |
|---|---|---|
| T7 Firemní akce & svatby | GRID — Firemní poptávka & svatby | Firemní poptávka |
| T6 Sezóna 2026 (čekací list) | GRID — Čekací list (Sezóna 2026) | Čekací list |
| Stránka Kontakt | GRID — Kontakt / dotaz k pobytu | Kontakt |
| Patička (newsletter) | GRID — Newsletter | Newsletter |
| Stránka Dotazník spokojenosti | GRID — Dotazník spokojenosti | Dotazník |

> Rezervační lišta v hero **není** Fluent Form — patří do **Bookola** (skládání URL), řeší se zvlášť.

## Pole jednotlivých formulářů (a `name` atributy pro notifikace/mapování)

### GRID — Kontakt / dotaz k pobytu
- Jméno `jmeno` + Příjmení `prijmeni`
- E-mail `email` *(povinné)*
- Telefon `telefon`
- Vaše zpráva `zprava` (textarea, povinné)
- Tlačítko: **Odeslat dotaz**

### GRID — Firemní poptávka & svatby
- Kontaktní osoba: Jméno `jmeno` + Příjmení `prijmeni`
- Firma / organizace `firma`
- E-mail `email` *(povinné)*
- Telefon `telefon`
- Typ akce `typ_akce` (výběr: Konference/školení, Teambuilding, Firemní večírek, Svatba, Jiné) *(povinné)*
- Počet osob `pocet_osob` (číslo)
- Preferovaný termín `termin`
- Popis akce / požadavky `zprava` (textarea)
- Tlačítko: **Odeslat nezávaznou poptávku**

### GRID — Čekací list (Sezóna 2026)
- Jméno `jmeno` + Příjmení `prijmeni`
- E-mail `email` *(povinné)*
- Telefon `telefon`
- O co máte zájem `udalost` (výběr: MotoGP 2026, WorldSBK, Jiný závod/akce, Zatím nevím) *(povinné)*
- Poznámka `poznamka` (textarea)
- Tlačítko: **Zapsat na čekací list**

### GRID — Newsletter
- Váš e-mail `email` *(povinné)*
- Tlačítko: **Odebírat novinky**

### GRID — Dotazník spokojenosti
- Jméno `jmeno` + Příjmení `prijmeni`
- E-mail `email`
- Celková spokojenost `celkova` (5–1) *(povinné)*
- Ubytování / pokoj `ubytovani` (5–1)
- Gastronomie / snídaně `gastronomie` (5–1)
- Personál a přístup `personal` (5–1)
- Doporučili byste nás? `doporuceni` (Určitě ano … Určitě ne)
- Co můžeme zlepšit? `komentar` (textarea)
- Tlačítko: **Odeslat hodnocení**

## Doporučení po importu
- Přidej **Email Notification** (Settings → Email Notifications → Add Notification) → *Send To* `reservations@gridhotel.cz`, předmět např. „Web GRID: {form_name}", tělo `{all_data}`. (Do importu se notifikace záměrně nedávají, aby editor nespadl.)
- Zapni **antispam** (honeypot / reCAPTCHA / Turnstile).
- Odesílání pošty veď přes **SMTP** (WP Mail SMTP / Post SMTP), ne přes `wp_mail()`.
- Formuláře se na tmavém pozadí stylují přes child theme (FF default styl je povolen).
