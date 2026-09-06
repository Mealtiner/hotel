# GRID Hotel — WCAG 2.2 audit tlačítek a klikacích prvků

**Datum:** 2026-09-05  
**Rozsah:** veřejný frontend v `GARRY plugin/GRID/new/`; bez WordPress administrace a bez externího rezervačního systému.  
**Metoda:** statická kontrola aktuálního markupu, CSS a JavaScriptu. Nejde o prohlášení o shodě: před vydáním je nutná ruční kontrola v prohlížeči, klávesnicí a se čtečkou obrazovky.

## Hodnoticí pravidla

- WCAG 2.2 AA: 1.4.3 kontrast textu nejméně 4,5:1; 1.4.11 kontrast netextových ovladačů nejméně 3:1.
- WCAG 2.2 AA: 2.1.1 ovladatelnost klávesnicí, 2.4.7 viditelný focus, 2.4.11 focus nesmí být zcela zakryt, 4.1.2 jméno/role/stav programově dostupné.
- WCAG 2.2 AA: 2.5.8 minimální cíl 24 × 24 CSS px, pokud se neuplatní výjimka.
- Interní pravidlo GRID: 44 × 44 px. Je přísnější než WCAG 2.2 AA; v reportu je proto vedeno samostatně.

Referenční standard: [WCAG 2.2 — W3C](https://www.w3.org/TR/WCAG22/).

## Výsledek

| Stav | Počet | Význam |
|---|---:|---|
| **FAIL** | 8 | Prokazatelná chyba v kódu nebo barevné kaskádě. |
| **PASS** | 7 | Správně definovaný základ; stále vyžaduje vizuální regresní test. |
| **MANUAL** | 4 | Nelze potvrdit bez běhu stránky, rozlišení, obsahu a asistivní technologie. |

## Chyby — opravit

### A11Y-BTN-01 — Ghost CTA je na světlém podkladu bílé

- **WCAG:** 1.4.3 Contrast (Minimum), P0.
- **Kde:** `.btn.btn-ghost` na světlých sekcích, zejména Kontakt, Doprava, O nás, světlé CTA řádky a pokojové karty stylu 3.
- **Důkaz:** základ `.btn` nastavuje `color:#fff !important`, zatímco `.btn-ghost` používá `color:var(--fg)` bez `!important`. Bílá zůstane silnější než požadovaná tmavá barva světlé sekce.
- **Dopad:** bílý text na `--paper #F4F2F0` má kontrast přibližně 1,1:1.
- **Oprava:** vytvořit kontextové varianty se stejnou prioritou: `.btn.btn-ghost { color:var(--fg) !important; border-color:var(--line-c) !important; }`; pro fotografické/tmavé karty samostatně nastavit bílý text a minimálně 3:1 obrys.
- **Zdroj:** `grid-divi5-child/style.css:156-169`.

### A11Y-BTN-02 — Sezónní událost nelze ovládat z klávesnice

- **WCAG:** 2.1.1 Keyboard a 4.1.2 Name, Role, Value, P0.
- **Kde:** seznam akcí sezóny `.ev-row`.
- **Důkaz:** markup je `div role="button" tabindex="0"`; JavaScript registruje jen `click`, nikoli `keydown` pro Enter a Space.
- **Oprava:** nahradit celý řádek nativním `<button type="button">`, nebo doplnit Enter/Space, `aria-pressed` pro zvolený stav a zabránit scrollu mezerníkem. Preferovaný je nativní button.
- **Zdroj:** `garry-sezona-cekaci-list.php:647`; `grid-divi5-child/assets/js/grid.js:128-136`.

### A11Y-BTN-03 — Filtr galerie neoznamuje vybraný stav

- **WCAG:** 4.1.2, P1.
- **Kde:** `.gal-fbtn` na stránce Galerie.
- **Důkaz:** stav je pouze CSS třídou `.active`; markup ani JavaScript nenastavují `aria-pressed`, `aria-current` ani vazbu na filtrovaný obsah.
- **Oprava:** použít `aria-pressed="true|false"` na každém filtru a aktualizovat jej při kliknutí; doplnit `type="button"` a vhodný živý status typu „Zobrazeno: …“.
- **Zdroj:** `gridhotel-components/inc/shortcodes/pages.php:187-188`; `gridhotel-components/assets/components.js:53-61`; `style.css:1321-1325`.

### A11Y-BTN-04 — Zavření HUD nechá focus na skrytém tlačítku

- **WCAG:** 2.4.11 Focus Not Obscured (Minimum), P1.
- **Kde:** tlačítko zavření HUD `#hudX`.
- **Důkaz:** kliknutí přidá panelu `.hidden` a ukáže `#hudReopen`, ale fokus se na něj nepřenese. Aktivní prvek zůstane vizuálně skrytý transformací/opacity.
- **Oprava:** po zavření vykonat `reopen.focus()`; po znovuotevření vrátit focus na `#hudX` nebo první smysluplný ovladač panelu.
- **Zdroj:** `garry-situace-na-trati.php:248-251`; `style.css:200-219`.

### A11Y-BTN-05 — Mobilní menu používá jinou stavovou třídu než CSS a neřídí fokus

- **WCAG:** 2.4.11, P1.
- **Kde:** hamburger a full-screen mobilní menu.
- **Důkaz:** JavaScript přidává `is-open`, ale CSS zobrazuje pouze `.mobile-menu.open`; menu se proto nemusí vůbec otevřít. I po sjednocení třídy zůstane focus na hamburgeru pod full-screen vrstvou a po zavření se nevrací.
- **Oprava:** sjednotit stav na jednu třídu; po otevření přesunout focus na zavírací tlačítko, při zavření jej vrátit na hamburger. Pro modální menu použít dialogový vzor (včetně `aria-modal`) nebo bezpečný focus trap.
- **Zdroj:** `gridhotel-components/assets/components.js:26-45`; `style.css:756-760`.

### A11Y-BTN-06 — Popisy pokojů jsou dostupné pouze při hoveru myší

- **WCAG:** 1.4.13 Content on Hover or Focus a 2.1.1, P2.
- **Kde:** pokojové karty `.room` stylů 1/2/4.
- **Důkaz:** `.room:hover .r-desc` zobrazí obsah, ale neexistuje rovnocenné `.room:focus-within .r-desc` ani ovládání pro klávesnici.
- **Oprava:** stejný stav aktivovat pro `:focus-within`; pokud se skrývá zásadní obsah, nesmí být hover jedinou cestou k němu.
- **Zdroj:** `style.css:974-975`.

### A11Y-BTN-07 — Textové CTA `.sec-more` je menší než WCAG minimum

- **WCAG:** 2.5.8 Target Size (Minimum), P1.
- **Kde:** „Více“, „Detail“, „Aktuální týdenní menu“ a obdobné samostatné CTA.
- **Důkaz:** `.sec-more` má font 0,74rem, zděděný line-height 1,65 a pouze `padding-bottom:3px`; výsledná výška je přibližně 22,5 px. Nemá `min-height`, vertikální padding ani výjimku pro odkaz vložený v běžné větě.
- **Oprava:** vytvořit klikací plochu nejméně 24 × 24 px; pro interní GRID standard preferovat 44 px, například `min-height:44px; padding-block:10px`.
- **Zdroj:** `style.css:482-485`.

### A11Y-BTN-08 — Otevřený lightbox nepřesune focus do dialogu

- **WCAG:** 2.4.3 Focus Order, 2.4.11 Focus Not Obscured a 4.1.2, P1.
- **Kde:** Galerie a detail pokoje po otevření lightboxu.
- **Důkaz:** JavaScript otevře overlay a vloží zavírací tlačítko, ale nepoužívá dialogovou roli, `aria-modal`, přesun fokusu ani jeho návrat na původní obrázek.
- **Oprava:** použít `<dialog>` nebo ekvivalentní `role="dialog" aria-modal="true"`; po otevření fokusovat `glb-close`, po zavření jej vrátit na zdrojový odkaz.
- **Zdroj:** `grid-divi5-child/assets/js/grid.js:153-165`; `style.css:965`.

## Správně definované prvky

| Prvek | Stav | Proč |
|---|---|---|
| Primární CTA `.btn` v klidovém i hover stavu | PASS | `#FFFFFF` na `#C20E1A` = 6,23:1; `#FFFFFF` na hover `#E11622` = 4,84:1. Nativní `<a>`/`button` a obecný viditelný focus. |
| Textový odkaz `.sec-more` na světlé sekci | PASS | `#5B5F66` na `#F4F2F0` = 5,74:1; hover přidává jiný než barevný signál — spodní linku. Cílovou velikost nutno ručně změřit. |
| Textový odkaz `.sec-more` na tmavé sekci | PASS | `#B9B7B9` na `#0E0F11` = 9,62:1. |
| Klikací karta T1 `.entry` | PASS | Nativní odkaz přes celou kartu má `aria-label`; aktivní/hover stav používá bílý text na brandové červené. |
| Klikací karta zážitku `.exp-item` | PASS | Při URL se vykreslí jako nativní `<a>`; světlejší varianta přepíná při hoveru text na bílý nad červenou. |
| Pravá sekční navigace | PASS | Aktuální plugin generuje nativní `<a href="#id">`; funguje i bez JavaScriptu a aktivní bod používá `aria-current="location"`. |
| Zavírací tlačítka HUD/menu | PASS s výhradou | Mají dostupné `aria-label` a explicitní minimum 24 × 24 px. Focus po změně stavu však musí opravit A11Y-BTN-04/05. |

## Ruční ověření před schválením

| Kontrola | Co ověřit | WCAG |
|---|---|---|
| Cílová velikost | `.btn`, `.sec-more`, `.gal-fbtn`, newsletter, language switch a pravý posuvník při 320/390/768/1024 px. WCAG minimum je 24 px; interní GRID cíl je 44 px. | 2.5.8 |
| Fotografie v hero | Kontrast menu a ghost CTA na každé fotografii/stavu overlaye; textový stín sám není záruka kontrastu. | 1.4.3, 1.4.11 |
| Focus | Tab pořadí po otevření mobilního menu/HUD/lightboxu; focus nesmí být pod fixed headerem, HUDem, lištou ani dialogem. | 2.4.3, 2.4.11, 2.4.13 |
| Lightbox galerie | Role dialogu, `aria-modal`, přesun a návrat fokusu, Escape i zavírací tlačítko. | 2.1.2, 2.4.3, 4.1.2 |
| Formuláře | Skutečné odeslání, chybové a úspěšné zprávy přes `aria-live`, viditelný focus v Safari/Chrome. | 3.3.1–3.3.3, 4.1.3 |

## Funkční problémy mimo samotné WCAG

- Některé formulářové CTA ve starších shortcodech mají `onclick="return false"`; tlačítko se tváří jako „Odeslat“, ale neodešle formulář. Buď formuláře napojit, nebo je veřejně nenabízet jako odesílací akci.
- Rezervační maketa používá vizuální `span.bkmock-btn`; musí zůstat jasně označená jako neinteraktivní ukázka. Pokud má rezervovat, musí to být `<button>` nebo `<a>`.

## Minimální implementační standard pro Claude

1. Založit jednotné komponenty `gh-button--primary`, `gh-button--secondary`, `gh-button--text`, `gh-button--filter` a `gh-button--icon`.
2. Varianty definovat tokeny podle kontextu `.sec-light`, `.sec-dark`, `.hero`, `footer`, nikoli individuálními inline styly.
3. Všechny změny stavu musí mít programový ekvivalent: `aria-pressed`, `aria-current`, `aria-expanded` nebo nativní stav formuláře.
4. Pro všechny modální/fixed vrstvy explicitně řídit focus při otevření i zavření.
5. Po opravě spustit ruční matici: CZ/EN/DE × 320/390/768/1024/1600 px × myš/klávesnice.
