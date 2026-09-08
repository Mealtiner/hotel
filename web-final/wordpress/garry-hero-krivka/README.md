# GARRY – Hero křivka

Dekorativní křivka trati v hero sekci — SVG, animace při vstupu do viewportu.

Plugin vlastní celý render i animaci; `[grid_hero]` z GRID Hotel Components
tenhle renderer jen volá. Vykresluje se shortcodem `[garry_hero_curve]` nebo
přímo funkcí `garry_hero_curve_render()`.

## Historie změn

### 1.3.0 — 2026-09-06
- Odstraněna poslední kopie starého sdíleného rámce `Garry_Promotion_Registry` z těla pluginu — registrace jde výhradně přes namespacovaný framework 2.4 v `includes/framework-v23/`.

### 1.2.0 — 2026-09-01
- Zásadní oprava: plugin dřív jen publikoval `window.gridHeroCurve` a skutečné vykreslení dělal child motiv, takže samostatně nefungoval. Teď vlastní render i animaci (`assets/hero-curve.js` — IntersectionObserver, `prefers-reduced-motion`, víc instancí, bez jQuery).
- `window.gridHeroCurve` zůstává jako zastaralý read-only export kvůli zpětné kompatibilitě.

### 1.1.1 — 2026-08-31
- Obnovena implementační dokumentace na sdíleném Přehledu.

### 1.1.0 — 2026-08-30
- Migrace frameworku 2.3 → 2.4 (vlastní PHP namespace), oprava zdvojeného vykreslení Přehledu a nadměrného zápisu do logu.
