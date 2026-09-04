---
name: grid-divi-moduly
description: Pravidla atomické struktury obsahu v Divi 5 na webu GRID Hotel — co vlastní Divi, co child theme a co pluginy, a proč nesmí vzniknout monolitický Text/Code modul. Načti při zakládání nebo přestavbě obsahu stránky, při rozhodování kam patří nový kód, a při jakékoli změně, která se dotkne editovatelnosti ve Visual Builderu.
---

# GRID Hotel — atomická struktura v Divi 5

## Zásada, která nemá výjimku

**Všechno na webu musí běžet přes Divi.** Žádná PHP page-šablona jako obejití
problému. Klient chce plnou vizuální editovatelnost; obcházení Divi je
nepřijatelné, i kdyby řešilo chybu Divi. Když něco v Divi nejde, oprav to
v rámci Divi nebo CSS, ne novou šablonou.

Cílový stav: každý nadpis, odstavec, obrázek a tlačítko jde ve Visual Builderu
samostatně vybrat a upravit.

## Dělba odpovědnosti

**Divi 5** vlastní strukturu stránky a editovatelný obsah:

- Section = jedna vizuální sekce;
- Row = layoutový kontejner;
- Column = skutečný sloupec;
- Image modul = každý samostatný obrázek;
- Heading/Text modul = každý samostatně editovatelný nadpis nebo text;
- Button modul = samostatné CTA;
- Form/Code/Shortcode modul **jen** pro komponentu, která je skutečně dynamická.

**Child theme** (`grid-divi5-child/style.css`) vlastní design tokeny, koridor
obsahu, breakpointy, layoutové třídy, obecnou responzivitu a kompatibilní
přepis Divi wrapperů. Viz [[grid-responzivita]].

**Pluginy** vlastní data a dynamické chování, nikoli obecný layout:

| Plugin | Odpovědnost |
|---|---|
| `garry-typografie` | fonty, typografické tokeny, atomické třídy |
| `garry-situace-na-trati` | levý fixní HUD |
| `garry-bocni-posuvnik` | pravá sekční navigace |
| `gridhotel-components` | dynamické komponenty a jejich sémantický markup |
| `gridhotel-core` | data, CPT, ACF, API |
| `garry-default` | GARRY Embedded Framework a bezpečnost |
| ostatní `garry-*` | pouze jejich doménová data a chování |

Plugin nesmí přidat druhý konkurenční layoutový systém a nesmí vykreslit
hlavičku, patičku ani sousední statické sekce.

## Zakázané

- vložit celý HTML obsah stránky do jednoho Text nebo Code modulu;
- řídit stránku inline styly;
- kopírovat responzivní pravidla do každé stránky;
- opravovat layout zápornými marginy odvozenými z konkrétního textu;
- pevná výška u bloku, který obsahuje text.

## Zakládání nové sekce

1. Vlastní Divi Section se **stabilní sémantickou třídou** (`sec sec-light`
   nebo `sec sec-dark`) — nadpisy tím zdědí správnou barvu i velikost
   z globálního nadpisového systému zadarmo.
2. `id` jen jako kotva, nikdy jako stylovací háček pro obecné pravidlo.
3. Řádek s běžným obsahem dostane koridorovou třídu; full-bleed výjimku
   označ explicitně.
4. Uvnitř samostatné moduly pro kicker, nadpis, text, obrázek a CTA.
5. Kotvy potřebují `scroll-margin-top: calc(var(--gh-header-h) + 16px)`.

## Watermarky sekcí T1–T8

Nefungují jako `<span>` uvnitř modulu: Divi obaluje každý modul vlastním
`position: relative`, takže se absolutní pozice ukotví k wrapperu, ne k sekci.
Řeší se jako `::before` přímo na sekci (`.tag-t1` … `.tag-t8`).

## Nadpisový systém

Globální pravidlo `.sec :where(h1..h6)` má díky `:where()` nulovou specificitu —
je to podlaha, ne strop, a každá komponentová třída ho přebije bez boje.
Barevná zásada: světlé pozadí → červený akcent `#C20E1A`, tmavé → zlatý
`#CAA75F`, drobný červený text na tmavém `#FF5A50`.

Sáhnout po H5/H6 jen kvůli menší velikosti je chyba — úroveň nadpisu musí
odpovídat skutečné struktuře dokumentu.

## Editovatelnost — kontrola před uzavřením

- každá sekce má jasně pojmenovanou třídu;
- nadpis, text, obrázek a tlačítko jdou samostatně vybrat;
- přesun modulu nerozbije globální CSS;
- žádná stránka není jedním monolitickým HTML shortcodem;
- globální třídy fungují bez per-page inline oprav;
- dynamické shortcody nevykreslují celý statický obsah stránky.

Nasazení obsahu a pasti Divi bloků viz [[grid-local-nasazeni]].
