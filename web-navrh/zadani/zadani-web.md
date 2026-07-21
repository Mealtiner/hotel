# GRID HOTEL — Základní zadání nového webu

**Platforma:** WordPress + Divi 5
**Datum:** 15. 6. 2026
**Vstupy:** 3 rešerše ([současný web](../reserse/01-soucasny-web.md), [konkurence](../reserse/02-konkurence.md), [nejlepší weby](../reserse/03-inspirace-nejlepsi-weby.md)), data klienta (foto + loga + logomanuál 2017).

---

## 1. Proč nový web (jedna věta)

GRID Hotel neprodává pokoj — prodává zážitek **„spím přímo uprostřed Masarykova okruhu“**. Web to musí říct v prvních třech sekundách a celou cestou klienta to potvrzovat.

## 2. Pozicování

> **Ne hotel u Brna. Hotel uprostřed trati.**

4hvězdičkový hotel a restaurace přímo v areálu Automotodromu Brno (Masarykův okruh) — 60 pokojů + 4 apartmány, výhled na trať, gastronomie, motorsport zážitky a firemní eventy. Kombinace **hotelového klidu a energie okruhu** („Trackside Calm“). Prémiové, technické, klidné — nikdy karnevalové.

## 3. Cílové skupiny a jejich cesta (customer journey)

Web musí v heru rozdělit návštěvníky podle motivace — každá skupina má najít „svůj“ vstup, ne skládat nabídku sama.

| Persona | Motivace | Vstupní volba | Cílová konverze |
|---|---|---|---|
| 🏁 **Fanoušek motorsportu** | Být u závodů, race weekend, atmosféra. | „Jedu na závody“ | Rezervace na termín závodu, čekací list, balíček. |
| 🏢 **Firemní klient** | Teambuilding, konference, catering, parkování. | „Plánuji firemní akci“ | Poptávkový formulář B2B. |
| 🎁 **Zážitkový host / dárce** | Simulátor, motokáry, motoškola, dárek. | „Chci zážitek u okruhu“ | Koupě zážitku / dárkového poukazu. |
| 🛏️ **Běžný host / turista** | Kvalitní 4* hotel u Brna, parkování, klid. | „Hledám pobyt u Brna“ | Přímá rezervace pokoje. |

**Princip cesty klienta:** Emoce (hero) → Orientace (3–4 vstupy) → Důkaz hodnoty (track view, foto, příběh místa) → Důvěra (recenze, partneři, kalendář) → Konverze (sticky booking / poptávka) → Servis (patička).

## 4. Nová struktura webu a sitemap

### Hlavní menu — JEN hlavní prvky (max 6 + CTA)

```
[ LOGO ]   Pokoje  ·  Zážitky u okruhu  ·  Firemní akce  ·  Restaurace & bar  ·  Race weekendy   [ REZERVOVAT ]  [CZ/EN/DE]
```

1. **Pokoje & apartmány** — kategorie, track view, terasa, srovnání, galerie, rezervace.
2. **Zážitky u okruhu** — simulátor, motokáry, motoškola, polygon, dárkové poukazy, balíčky, napojení na Automotodrom.
3. **Firemní akce** — kapacity, scénáře dne, catering, ubytování, parkování, poptávka (B2B).
4. **Restaurace & bar** — GRID Club, Paddock Restaurant, terasa, menu, catering.
5. **Race weekendy / Kalendář** — velké závody, track days, MotoGP, balíčky, dostupnost.
6. **Kontakt** — mapa, příjezd, parkování, kontakty podle oddělení.

> Trvale viditelné CTA **Rezervovat** + přepínač jazyka. Booking (sticky lišta / panel) je přítomný napříč webem.

### Sitemap (úroveň 2)

```
Home
├── Pokoje & apartmány
│     ├── Standard
│     ├── Superior
│     ├── Superior Plus (track view + terasa)
│     └── Apartmá / Apartmá Plus
├── Zážitky u okruhu
│     ├── Závodní simulátor
│     ├── Motokáry / pitbike
│     ├── Motoškola & bezpečná jízda
│     ├── Autopolygon
│     └── Dárkové poukazy & balíčky
├── Firemní akce
│     ├── Konference & školení
│     ├── Teambuilding na okruhu
│     ├── Catering
│     └── Poptávkový formulář
├── Restaurace & bar
│     ├── Paddock Restaurant
│     ├── GRID Club & terasa
│     └── Menu / nápojový lístek
├── Race weekendy / Kalendář
│     └── Eventové landing pages (MotoGP, track days, …)
└── Kontakt
```

### Patička — servisní centrum (méně důležité a provozní věci)

Patička přebírá vše, co NEPATŘÍ do hlavního menu:

- **O hotelu / příběh & tým**
- **Kariéra / Práce na hotelu** *(záměrně NE v hlavním menu)*
- **Dárkové poukazy** (rychlý odkaz)
- **Časté dotazy (FAQ)**
- **Ke stažení** (nápojový lístek, prezentace, ceník)
- **Newsletter / Event alert** (upozornění na termíny závodů)
- **Sociální sítě**
- **Partneři** — Automotodrom Brno, WTC, …
- **Příjezd, parkování, shuttle, GPS**
- **Provozní a fakturační údaje, IČO/DIČ, provozovatel**
- **Ochrana osobních údajů, Cookies**

## 5. Funkční backlog (priority)

**MUST**
- Hero foto/video okruhu + hotelu + jasný claim „ubytování přímo na Masarykově okruhu“.
- Sticky booking lišta + rychlá B2B poptávka.
- Produktové stránky pokojů s rozlišením track-view / terasa.
- Landing page „Firemní akce na okruhu“.
- Eventové landing pages + kalendář napojený na dění okruhu.
- Nová fotobanka a krátká videa.

**SHOULD**
- Recenze, loga partnerů, příběhy hostů a firem.
- Vícejazyčnost CZ / EN / DE (případně PL).
- Design systém v Divi 5 (karty pokojů, event bloky, CTA pásy, FAQ, galerie, formuláře).
- Měření GA4 / Tag Manager — eventy rezervace, poptávka, telefon, e-mail, poukaz.

## 6. Co tyto návrhy obsahují

- **Rozcestník** (`index.html`) — přehled 4 grafických verzí.
- **4 grafické verze** — každá jiná **strukturou a kompozicí**, ne jen barvou a fontem. Viz [skills.md](skills.md), sekce „4 koncepty“.

## 7. Pravidla práce se značkou (závazné)

- **Loga GRID se používají 100 %** z `assets/logo/` — na světlém pozadí barevné horizontální logo, na tmavém negativní/bílé. Monogram „G“ jako favicon a grafický akcent. Dodržet ochrannou zónu z manuálu (B = 2× výška „A“).
- **Barvy z logomanuálu 2017** — viz [skills.md](skills.md). Žádné barvy mimo definovanou paletu bez schválení.
- **Foto:** reálné místo, reálný okruh. Žádné generické stock auto v kouři.

## 8. Otevřené body před produkcí

- Doplnit **profesionální foto pokojů „z pohledu hosta“** a track-view (současná banka je silná na okruh a catering, slabší na pokoje).
- Natočit **hero film 15–30 s**.
- Ověřit rezervační systém a jeho napojení (booking engine).
- Sjednotit obchodní priority (přímé rezervace vs. B2B vs. eventy vs. poukazy).
