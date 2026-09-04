# GRID Hotel — Časosběrné video

## Rozsah

- URL: CZ `/casosber-video-stavby/`; EN `/en/time-lapse-video/`; DE `/de/video-bau-des-hotels/`
- Zdroj/komponenty: `grid_video`, `grid_video_embed`.
- Nadřazená pravidla: [globální](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md), [breakpointy](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md), [sekce](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md).
- Úprava musí být identická strukturou ve všech jazycích; liší se pouze obsah.

## Povinné pořadí sekcí

1. Intro
2. Video embed
3. Popis
4. CTA
5. Footer

## Atomická Divi 5 struktura

Úzký wrapper max 960 px zarovnaný do koridoru. Video je samostatný embed s poměrem 16:9; nepoužívat pevnou iframe šířku.

Každá Section dostane stabilní sémantickou třídu. IDs jsou pouze pro kotvy. Všechny řádky s běžným obsahem používají `.gh-container`; full-bleed výjimky musí být explicitní.

## Responzivní skládání

Pořadí hodnot: desktop 1600 / tablet na šířku 1024 / tablet na výšku 768 / mobil 390.

- Video 16:9 a 100 % dostupného wrapperu ve všech režimech.
- Intro max 60ch.
- CTA row → wrap → full-width na mobilu.

## Současné chyby, které je nutné odstranit

- Mobilní stránka přetékala přibližně o 48 px.
- Portrait je zbytečně úzký kvůli dvojitému paddingu.
- Embed musí být testován s reálnou službou, nejen placeholderem.

## Implementační zásahy

1. Nejdřív opravit globální koridor a reset mobilního `--hud-pad`; lokální kompenzace stránky jsou zakázané.
2. Použít stejné nebo vyšší specificity breakpointy pro Divi řádky se základním `!important`.
3. Každému grid/flex child nastavit `min-width:0`.
4. Médiím nastavit `display:block; width:100%; max-width:100%`.
5. Dlouhým textům, e-mailům a odkazům nastavit bezpečné zalamování.
6. Vyčistit Divi static CSS cache a ověřit anonymní i přihlášené zobrazení.
7. Nezasahovat do Frameworku 2.4 ani neměnit datovou odpovědnost pluginů.

## Akceptační checklist

- [ ] Iframe `width:100%; max-width:100%; aspect-ratio:16/9`.
- [ ] Žádné černé okraje způsobené chybným poměrem wrapperu.
- [ ] Video controls jsou dostupné a nejsou pod fixed overlayem.
- [ ] CZ/EN/DE popis bez overflow.
- [ ] Mobilní šířka obsahu 350 px při 390 viewportu.
- [ ] `scrollWidth <= clientWidth` při 320, 390, 640, 641, 768, 959, 960, 1024, 1280 a 1600 px.
- [ ] Full-page screenshot CZ/EN/DE pro 1600, 1024, 768 a 390 px.
- [ ] Klávesnice, focus, reduced motion a dotykový režim bez závislosti na hoveru.

