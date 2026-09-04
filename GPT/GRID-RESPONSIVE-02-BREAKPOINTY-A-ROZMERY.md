# GRID Hotel — breakpointy, koridory a rozměry

## 1. Závazné režimy

| Režim | CSS rozsah | Referenční viewport | Pravý rail | Levý HUD |
|---|---:|---:|---|---|
| Desktop | ≥ 1280 px | 1600 × 1000 | viditelný | 224 px |
| Tablet na šířku | 960–1279 px | 1024 × 768 | viditelný | 224 px |
| Tablet na výšku | 641–959 px | 768 × 1024 | skrytý | 190 px |
| Mobil | ≤ 640 px | 390 × 844 | skrytý | sbalený, bez rezervace v toku |

Breakpoint 960 px je atomický přechod. Při 959 px rail nesmí být vidět; při 960 px musí být vidět a obsah se musí přepočítat do koridoru.

## 2. Přesné referenční souřadnice

### Desktop 1600 px

- viewport: 0–1600;
- pravý okraj HUD: x = 250;
- mezera HUD → obsah: 40 px;
- obsah: x = 290–1340;
- šířka obsahu: 1050 px;
- mezera obsah → osa railu: 45 px;
- osa railu: x = 1385;
- rail overlay zaujímá přibližně pravých 200–215 px.

### Tablet na šířku 1024 px

- viewport: 0–1024;
- pravý okraj HUD: x = 244;
- mezera HUD → obsah: 40 px;
- obsah: x = 284–764;
- šířka obsahu: 480 px;
- mezera obsah → osa railu: 45 px;
- osa railu: x = 809;
- rail musí zůstat viditelný.

### Tablet na výšku 768 px

- viewport: 0–768;
- HUD: x = 15–205, šířka 190 px;
- mezera HUD → obsah: 40 px;
- obsah: x = 245–737;
- šířka obsahu: 492 px;
- pravý inset: 31 px;
- pravý rail je skrytý.

### Mobil 390 px

- viewport: 0–390;
- obsah: x = 20–370;
- šířka obsahu: 350 px;
- HUD je sbalený a nesmí rezervovat `--hud-pad`;
- pravý rail je skrytý.

## 3. Koridorové vzorce

### Desktop a tablet na šířku

```css
--gh-content-left: calc(var(--gh-hud-left) + 224px + 40px);
--gh-rail-axis: calc(100vw - 215px);
--gh-content-right-edge: calc(var(--gh-rail-axis) - 45px);
```

Na 1600 px je výsledkem 290–1340. Na 1024 px 284–764.

### Tablet na výšku

```css
--gh-hud-w: 190px;
--gh-content-left: calc(var(--gh-hud-left) + 190px + 40px);
--gh-content-right-inset: clamp(24px, 4vw, 32px);
```

### Mobil

```css
--gh-content-left: 20px;
--gh-content-right-inset: 20px;
--gh-hud-reserved-space: 0px;
```

Na mobilu se musí `--hud-pad` explicitně resetovat. Nesmí zůstat vypočtených přibližně 278 px jako v současné verzi.

## 4. Sekční vertikální rytmus

| Prvek | Desktop | Tablet šířka | Tablet výška | Mobil |
|---|---:|---:|---:|---:|
| Header | 86 px | 84 px | 72 px | 64 px |
| Běžná sekce top/bottom | 96–150 px | 80–112 px | 72–96 px | 56–72 px |
| Úvod → obsah | 48–64 px | 40–48 px | 32–40 px | 28–36 px |
| Mezera karet | 24–26 px | 20 px | 16–20 px | 16 px |
| Mezera odstavců | 18–24 px | 18–22 px | 16–20 px | 14–18 px |
| CTA řádek | 14–18 px | 12–16 px | 12–16 px | 10–12 px |

Sekce nesmí používat `min-height:100vh`, pokud to není hero. Výška se řídí obsahem a paddingem.

## 5. Typografická škála

| Typ | Desktop | Tablet šířka | Tablet výška | Mobil |
|---|---:|---:|---:|---:|
| Hero H1 | 92–128 px | 68–84 px | 52–64 px | 40–48 px |
| Sekční H2 | 56–72 px | 46–56 px | 38–48 px | 32–40 px |
| H3 karta | 28–34 px | 24–30 px | 23–28 px | 22–26 px |
| Lead | 19–22 px | 18–20 px | 17–19 px | 17–18 px |
| Body | 18 px | 17 px | 16–17 px | 16 px |
| Kicker/metadata | 12–14 px | 12–13 px | 12 px | 11–12 px |

Použít `clamp()` v rámci režimu. Text nesmí být škálován transformací.

## 6. Mřížky podle režimu

| Vzor | Desktop | Tablet šířka | Tablet výška | Mobil |
|---|---|---|---|---|
| T1 | 4 × 1 | 2 × 2 | 2 × 2 | 1 × 4 |
| T2/T7 | 2 sloupce 50/50 | 2 sloupce 50/50 | 2 řádky | 2 řádky |
| T3 | 2 × 2 | 2 × 2 | 2 × 2 | 1 × 4 |
| T4 | 3 × 2 | 2 × 3 | 2 × 3 | 1 × 6 |
| T5 gastro | 3 svislé karty | 3 vodorovné řádky | 3 vodorovné řádky | 3 svislé karty |
| T6 | 3 : 2 | 2 řádky | 2 řádky | 2 řádky |
| T8 recenze | 3 × 1 | 1 × 3 | 1 × 3 | 1 × 3 |
| Galerie | 4 sloupce | 2 sloupce | 2 sloupce | 2 sloupce |
| Akce sezóny | 3 × 2 | 2 × 3 | 2 × 3 | 1 × 6 |
| Footer | 4 sloupce | 2 × 2 | 2 × 2 | 1 × 4 |

## 7. Minimální rozměry

- textová karta: min-inline-size 0, doporučená vnitřní šířka alespoň 220 px;
- formulářové pole ve dvousloupcové mřížce: alespoň 220 px;
- tlačítko: min-height 44 px;
- obrázek karty: min-height 180 px na tabletovém horizontálním provedení;
- mapa: min-height 360/320/300/260 px podle režimu;
- video: poměr 16:9;
- galerie: poměr 4:3;
- karta pokoje: poměr média alespoň 4:3, celý blok bez pevné výšky.

## 8. Hraniční testy

Ověřit nejen referenční viewporty, ale i přechody:

- 1279 a 1280 px;
- 959 a 960 px;
- 640 a 641 px;
- minimálně 320 px;
- 1024 × 768 a 768 × 1024;
- zoom 200 % při viewportu 1280 px.

