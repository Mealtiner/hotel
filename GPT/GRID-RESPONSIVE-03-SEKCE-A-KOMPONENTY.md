# GRID Hotel — specifikace sekcí a komponent

## Hero

Struktura: Section → background media → overlay → container → kicker → H1 → lead → actions.

- Pozadí full bleed, `cover`, minimálně výška viewportu minus header.
- Text pouze v bezpečném koridoru.
- HUD vlevo a rail vpravo jsou nad hero.
- Desktop/landscape: akce v řádku, mohou se zalomit.
- Portrait/mobile: text kratší, CTA pod sebe při nedostatku šířky.
- Žádný H1 ani CTA nesmí ležet pod overlay widgetem.

## Rezervační lišta

- Je nad obsahem, ale pod fixed headerem.
- Desktop: 6 polí v jednom řádku.
- Tablet landscape/portrait: 3 × 2.
- Mobil: 1 × 6.
- Datum, hosté, typ pokoje a CTA jsou samostatné moduly/komponenty.
- Pole mají stejnou minimální výšku 64/60/56 px.

## T1 — vstupy

- Intro: kicker, H2 a text do 50 % desktopového koridoru.
- Karty přes celý koridor.
- 4 → 2 → 2 → 1 sloupec.
- Výška karet se řídí nejdelším obsahem v řádku.
- Červený hover je vrstva `::before`, ne změna rozměru.
- Na dotykovém zařízení musí být stav čitelný bez hoveru.

## T2/T7 — dělená sekce

- Desktop a landscape: přesně 50/50.
- Fotografie bez horního, levého a vnitřního odsazení.
- Textový sloupec respektuje pravou bezpečnou hranici.
- Portrait/mobile: obrázek nahoře, text dole.
- `.et_pb_row.split` breakpoint musí přebít desktopové `!important`.
- DOM pořadí zůstává obrázek → text, i u vizuálně obrácené varianty.

## T3 — pokoje

- Intro jako T1.
- 2 × 2 na desktopu a obou tabletech, 1 × 4 na mobilu.
- Každá karta: image module, kicker/číslo, H3, parametry, cena/benefit, CTA.
- Overlay nesmí snižovat kontrast pod AA.
- Obsah karty nesmí být vložen jako jedno HTML pole, pokud má být editovatelný.

## T4 — zážitky

- Intro max 50 % na desktopu; na tabletech celý koridor.
- 3 × 2 → 2 × 3 → 2 × 3 → 1 × 6.
- Celá mřížka musí být v koridoru. Aktuální desktopové x přibližně 159–1427 je chybné; cíl 290–1340.
- Jemný hover mění barvu/pozadí, ne geometrii.

## T5 — gastronomie

- Desktop: 3 svislé karty.
- Oba tablety: tři řádky; uvnitř každé karty obrázek 40 %, text 60 %.
- Mobil: tři svislé karty; obrázek nahoře, text dole.
- Responzivní selector musí být `.et_pb_row.gastro`, protože základ má stejnou vysokou specificitu.
- Na mobilu je současný stav tři sloupce přibližně po 15 px kritická chyba.

## T6 — sezóna / formulář

- Intro max 50 %.
- Desktop: levý obsah 3 díly, pravý panel 2 díly.
- Tablety a mobil: panely pod sebou.
- Formulář na tabletu může zůstat dvousloupcový jen při poli ≥220 px.
- Mobilní formulář vždy 1 sloupec.

## T8 — recenze a partneři

- Recenze 3 → 1 → 1 → 1.
- Partneři 4 nebo flexibilní řádek → 2 → 2 → 2/1.
- Vše v koridoru; žádné samostatné viewportové marginy mimo globální výpočet.

## Detail pokoje / zážitku

- Desktop: hlavní obsah + aside 340 px.
- Oba tablety a mobil: aside pod hlavním obsahem.
- Galerie/media mají stabilní poměr.
- Parametry používají grid, který přejde 3 → 2 → 2 → 1 podle skutečné minimální šířky.
- Detail na 1024 nesmí zůstat 302 + 340 px.

## Formulář

- Max šířka 820 px uvnitř koridoru.
- Desktop a tablety 2 sloupce, pokud každý ≥220 px.
- Mobil 1 sloupec.
- Full-width: textarea, souhlas, captcha, odeslání.
- Žádný child nesmí být širší než rodič.

## Kontakt

- Desktop: informace : formulář = 1 : 1.
- Oba tablety: informace, potom formulář.
- Mobil: vše pod sebou; kontakty 1 sloupec; formulář 1 sloupec.
- Mapa pod bloky přes celý koridor.

## Doprava

- Mapa přes celý koridor.
- Desktop a landscape: dvojice tras mohou být vedle sebe.
- Portrait/mobile: trasy i letiště po jedné.
- Dlouhé adresy/odkazy zalamovat.
- Mobilní mapa nesmí kolabovat; min-height 260 px.

## Galerie

- Filtry se zalamují, nepoužívají horizontální scroll.
- Mřížka 4 → 2 → 2 → 2.
- Obrázky 4:3, `object-fit:cover`.
- Testovat s reálnými daty, prázdný stav neověřuje responzivitu galerie.

## Dlouhý text

- Max šířka 760–820 px.
- Zarovnat k levé hraně koridoru.
- Body max 68ch.
- Tabulka má vlastní responsive wrapper; obsah stránky nesmí přetékat.
- URL a e-maily `overflow-wrap:anywhere`.

## Patička

- 4 → 2 → 2 → 1 sloupec.
- Mobil resetuje HUD padding.
- Newsletter: pole + tlačítko v řádku, pod 360 px lze skládat.
- Dlouhé německé odkazy musí zůstat uvnitř sloupce.

