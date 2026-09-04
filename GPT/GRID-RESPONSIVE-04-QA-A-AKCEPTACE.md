# GRID Hotel — QA a akceptační protokol

## Povinná testovací matice

Každou stránku otevřít v CZ, EN a DE a otestovat:

- 1600 × 1000;
- 1024 × 768;
- 768 × 1024;
- 390 × 844;
- 320 × 700;
- 959 a 960 px;
- 640 a 641 px.

## Automatické podmínky

Na každé URL:

```js
const root = document.documentElement;
console.assert(root.scrollWidth <= root.clientWidth);
```

Dále ověřit:

- žádný viditelný prvek nemá `right > viewportWidth + 1`;
- žádný obsahový prvek neleží vlevo od bezpečné hranice, pokud není schválený full bleed;
- na 1024 px je `.track-progress` viditelný;
- na 768 px je `.track-progress` skrytý;
- na 390 px HUD nerezervuje šířku obsahu;
- `.wrap`, `.entries`, `.reviews`, `.exp`, `.gastro` používají stejný koridor.

## Vizuální kontrola

Pro každou stránku uložit full-page screenshot všech čtyř režimů. Porovnat:

1. levý a pravý obsahový okraj;
2. pořadí sekcí;
3. počet sloupců;
4. vertikální mezery;
5. ořez obrázků;
6. zalomení nadpisů;
7. výšku karet v řádku;
8. polohu HUD/railu;
9. focus a chybové stavy formulářů;
10. patičku.

Tolerance:

- bezpečné hranice ±2 px;
- mezery ±4 px;
- počet sloupců bez tolerance;
- horizontální overflow 0 px;
- překrytí textu overlayem 0 px.

## Interakce

- mobilní menu: otevřít, projít focus, zavřít;
- jazykový přepínač funguje i bez JS;
- všechny CTA jsou klikatelné;
- track progress aktivuje správnou sekci;
- HUD lze zavřít/obnovit bez posunu obsahu;
- galerie filtr/lightbox;
- formuláře: required, chyba serveru, úspěch;
- rezervační kroky a datum;
- accordion/tabulka, pokud jsou použity;
- hover nesmí být jediný nositel informace.

## Divi 5 editovatelnost

V editoru ověřit, že:

- každá sekce má jasně pojmenovanou třídu;
- nadpis, text, obrázek a tlačítko lze samostatně vybrat;
- přesun modulu nerozbije globální CSS;
- žádná stránka není jedním monolitickým HTML shortcode;
- globální třídy fungují bez per-page inline oprav;
- dynamické shortcody nevykreslují celý statický obsah stránky.

## Regresní brány

Před předáním:

- PHP syntax check všech změněných PHP souborů;
- kontrola manifestu každého změněného pluginu;
- framework 2.4 zachován;
- žádné změny v `backup/`;
- porovnat změny proti souběžné práci Claude Code;
- vyčistit Divi/static CSS cache;
- opakovat screenshoty bez přihlášení a s admin barem;
- ověřit Safari i Chromium.

## Známé aktuální regresní body

- rail se skrývá při `max-width:1080px`, což je chybně pro 1024;
- mobil dědí přibližně 278 px `--hud-pad`;
- `.et_pb_row.split` přebíjí mobilní skládání;
- `.et_pb_row.gastro` přebíjí tabletové i mobilní skládání;
- kontakt na 390 px přetéká přibližně o 111 px;
- doprava na 390 px přetéká přibližně o 63 px a mapa může mít prakticky nulovou šířku;
- sezóna na 390 px přetéká přibližně o 187 px;
- firemní stránka na 390 px přetéká přibližně o 195 px;
- DE řetězce zvyšují přetečení, například firemní stránka až přibližně o 272 px.

