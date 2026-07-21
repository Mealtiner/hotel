# GRID HOTEL — Design tokeny & nastavení pro Divi 5

Zdroj: `web-final/index.html` (návrh Circuit Line / V4, finální).

---

## 1) Barevná paleta

### Brand
| Název | HEX | Použití |
|---|---|---|
| Red (primární) | `#C20E1A` | tlačítka, akcenty, kicker, linky |
| Red bright (hover) | `#E11622` | hover plných tlačítek |
| Gold (akcent) | `#CAA75F` | aktivní navigace, jazyky hover, čísla |
| Gold deep | `#B8954A` | tmavší zlatá |

### Pozadí / plochy
| Název | HEX | Použití |
|---|---|---|
| Graphite deep | `#0E0F11` | tmavé pozadí, patička |
| Graphite | `#16181B` | tmavé karty, rezervační lišta |
| Graphite soft | `#1D2025` | jemné tmavé plochy |
| Paper / Offwhite | `#F4F2F0` | světlé pozadí + text na tmavém |
| Card light | `#FFFFFF` | karty na světlých sekcích |

### Text
| Název | HEX | Použití |
|---|---|---|
| Ink | `#16181B` | text na světlém |
| Ink muted | `#5B5F66` | vedlejší text na světlém |
| Grey | `#B9B7B9` | vedlejší text na tmavém |
| Grey dim | `#7A797B` | labely, popisky |

### Stavové / linky
| Název | Hodnota | Použití |
|---|---|---|
| Green | `#2ECC71` | status OTEVŘENO / Volné pokoje |
| Linka na tmavém | `rgba(255,255,255,.11)` | oddělovače |
| Linka na světlém | `rgba(20,22,25,.12)` | oddělovače |
| Karbon základ | `#0D0F12` (+ `#16181C`, `#1B1E22`, `#191C20`) | textura tmavého pozadí |

### Hover stavy
| Prvek | Normál | Hover |
|---|---|---|
| Tlačítko plné | bg `#C20E1A` / text `#FFF` | bg `#E11622` + záře `0 0 22px rgba(194,14,26,.5)` + `translateY(-1px)` |
| Tlačítko ghost | průhledné + linka | rámeček `#C20E1A` |
| Menu odkaz | `#B9B7B9` | `#FFFFFF` + červené podtržení |
| Jazyk | `#7A797B` | `#CAA75F` |
| Karta pokoj/gastro | — | obrázek `scale(1.06)` |
| Řádek akce | — | posun `+8px` + `rgba(194,14,26,.06)` |
| Odkaz patička | `#B9B7B9` | `#F4F2F0` |

---

## 2) Fonty (Google Fonts — dostupné v Divi)

| Role | Font | Váhy | Styl |
|---|---|---|---|
| Nadpisy H1–H3 | Saira Condensed | 500/600/**700**/800 | UPPERCASE, LS 0.01em, LH 0.95 |
| Body | Inter | 300/**400**/500/600/700 | LH 1.65 |
| Labely / mono | Space Mono | 400/700 | UPPERCASE, LS 0.12–0.32em |

---

## 3) Divi 5 — kroky

1. **Website Settings → Global Colors** – přidej 10 barev z tabulky Brand+Pozadí+Text+Success.
2. **Website Settings → Global Fonts / Typography:**
   - Headings: Saira Condensed, 700, Uppercase, LS 0.01em, LH 0.95em
   - Body: Inter, 400, LH 1.6em
3. **Button preset:** bg `#C20E1A`, text `#FFFFFF`, Space Mono uppercase, LS 1.4px, radius 0, border 1px `#C20E1A`, hover bg `#E11622`.
4. **Website Settings → Custom CSS:** vlož blok níže.

---

## 4) Custom CSS pro Divi 5 (zkopíruj celé)

```css
:root{
  --graphite:#16181B;
  --graphite-deep:#0E0F11;
  --graphite-soft:#1D2025;
  --red:#C20E1A;
  --red-bright:#E11622;
  --gold:#CAA75F;
  --gold-deep:#B8954A;
  --offwhite:#F4F2F0;
  --paper:#F4F2F0;
  --ink:#16181B;
  --grey:#B9B7B9;
  --grey-dim:#7A797B;
  --ink-muted:#5B5F66;
  --green:#2ECC71;
  --line-dark:rgba(255,255,255,.11);
  --line-light:rgba(20,22,25,.12);
  --f-head:'Saira Condensed',sans-serif;
  --f-mono:'Space Mono',monospace;
  --f-body:'Inter',sans-serif;
}

/* Karbonová textura pro tmavé sekce — přidej sekci CSS třídu .carbon */
.carbon{
  background-color:#0d0f12;
  background-image:
    linear-gradient(27deg,#16181c 5px,transparent 5px) 0 5px,
    linear-gradient(207deg,#16181c 5px,transparent 5px) 10px 0,
    linear-gradient(27deg,#1b1e22 5px,transparent 5px) 0 10px,
    linear-gradient(207deg,#1b1e22 5px,transparent 5px) 10px 5px,
    linear-gradient(90deg,#14161a 10px,transparent 10px),
    linear-gradient(#191c20 25%,#141619 25%,#141619 50%,transparent 50%,transparent 75%,#1d2024 75%,#1d2024);
  background-size:20px 20px;
}

/* Tlačítko ve stylu GRID */
.grid-btn{
  font-family:var(--f-mono);font-size:.74rem;letter-spacing:.14em;text-transform:uppercase;
  background:var(--red);color:#fff;padding:11px 20px;border:1px solid var(--red);
  transition:background .25s,transform .2s,box-shadow .25s;
}
.grid-btn:hover{background:var(--red-bright);box-shadow:0 0 22px rgba(194,14,26,.5);transform:translateY(-1px)}

/* Kicker (červený popisek nad nadpisem) */
.kicker{font-family:var(--f-mono);font-size:.72rem;letter-spacing:.32em;text-transform:uppercase;color:var(--red)}
```

---

## 5) Import do Divi — možnosti

- **Přímý import HTML NELZE** — Divi importuje jen vlastní `.json` (Portability), ne surové HTML/CSS.
- Přenosné je: fonty (výběrem), barvy (ručně jako Global Colors), CSS (vložením bloku výše).
- Cesty realizace:
  - **A** – poskládat z Divi modulů + tyto tokeny (plně editovatelné) ✅ doporučeno
  - **B** – celé HTML do **Code modulu** (rychlé, ne vizuálně editovatelné)
  - **C** – vygenerovat Divi `.json` layout (importovatelné, ale nutná kontrola)
