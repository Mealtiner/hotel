# -*- coding: utf-8 -*-
"""Obsah pro sekci Ubytování — chipsy pobytu a sekce stránky /ubytovani/."""

I = {"cz": 0, "en": 1, "de": 2}

# --- štítky „součástí pobytu" (řádek pod kartami pokojů) ---
# První šest je původní sada, zbytek doplněn podle výbavy z Booking.com,
# která je shodná u všech kategorií pokojů.
CHIPS = [
    ("Snídaňový GRID Buffet", "GRID breakfast buffet", "GRID Frühstücksbuffet"),
    ("Wi-Fi zdarma", "Free Wi-Fi", "Kostenloses WLAN"),
    ("Parkoviště zdarma", "Free parking", "Kostenlose Parkplätze"),
    ("Klimatizace", "Air conditioning", "Klimaanlage"),
    ("Recepce 24/7", "24/7 front desk", "Rezeption rund um die Uhr"),
    ("Bezbariérový", "Wheelchair accessible", "Barrierefrei"),
    ("Vlastní koupelna", "Private bathroom", "Eigenes Badezimmer"),
    ("Sprchový kout", "Shower", "Duschkabine"),
    ("Fén · toaletní potřeby", "Hairdryer · toiletries", "Föhn · Pflegeprodukte"),
    ("TV se satelitem", "Satellite TV", "TV mit Satellit"),
    ("Trezor", "Safe", "Tresor"),
    ("Minibar", "Minibar", "Minibar"),
    ("Telefon s předvolbou", "Direct-dial phone", "Telefon mit Direktwahl"),
    ("Psací stůl · posezení", "Desk · seating area", "Schreibtisch · Sitzecke"),
    ("Plné zatemnění", "Full blackout", "Vollständige Verdunkelung"),
    ("Nekuřácké pokoje", "Non-smoking rooms", "Nichtraucherzimmer"),
    ("Denní úklid", "Daily housekeeping", "Tägliche Reinigung"),
    ("Výtah do všech pater", "Lift to all floors", "Aufzug in alle Etagen"),
]
CHIPS_POPIS = ("Součástí pobytu", "Included in every stay", "In jedem Aufenthalt enthalten")


def chips_html(l):
    i = I[l]
    polozky = "".join(f"<span>{c[i]}</span>" for c in CHIPS)
    # role="list" — aria-label na obyčejném divu čtečky ignorují (WCAG 4.1.2)
    return f'<div class="pobyt-chips" role="list" aria-label="{CHIPS_POPIS[i]}">{polozky}</div>'


# --- dvojice odkazů pod kartami na titulní stránce ---
ODKAZY = {
    "cz": [("Všechny pokoje a apartmá", "/ubytovani/"),
           ("Tabulka srovnání pokojů", "/ubytovani/#srovnani-pokoju")],
    "en": [("All rooms and apartments", "/en/accommodation/"),
           ("Room comparison table", "/en/accommodation/#srovnani-pokoju")],
    "de": [("Alle Zimmer und Appartements", "/de/unterkunft/"),
           ("Zimmer-Vergleichstabelle", "/de/unterkunft/#srovnani-pokoju")],
}


def odkazy_html(l):
    a = "".join(
        f'<a class="sec-more" href="{url}">{text} <span aria-hidden="true">&rarr;</span></a>'
        for text, url in ODKAZY[l]
    )
    return f'<div class="sec-more-radek">{a}</div>'


# --- „Dobré vědět" s ikonami ---
IKONY = {
    "prichod":  '<path d="M3 21V5l9-2v20M12 21h9V9h-9M8 12h.01"/>',
    "odchod":   '<path d="M14 3h6v18h-6M10 8l-4 4 4 4M6 12h8"/>',
    "deti":     '<path d="M9 11a3 3 0 1 1 6 0M12 3v3M5 21v-5a7 7 0 0 1 14 0v5"/>',
    "zvirata":  '<path d="M5 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM19 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM9 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM15 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM12 12c-3 0-5 2.5-5 5a3 3 0 0 0 4 2.8 4 4 0 0 1 2 0A3 3 0 0 0 17 17c0-2.5-2-5-5-5z"/>',
    "platba":   '<path d="M2 7h20v11H2zM2 11h20M6 15h4"/>',
    "koureni":  '<path d="M3 17h14v3H3zM20 17h1v3h-1M17 8c2 0 3-1 3-2.5S19 3 17 3M20 12c1.5 0 2-1 2-2M4 20L20 4"/>',
    "storno":   '<path d="M12 8v5l3 2M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z"/>',
    "jazyky":   '<path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20zM21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z"/>',
}

DOBRE_VEDET = [
    ("prichod", ("Check-in", "Check-in", "Check-in"),
     ("14:00 – 24:00", "14:00 – 24:00", "14:00 – 24:00")),
    ("odchod", ("Check-out", "Check-out", "Check-out"),
     ("do 10:00", "until 10:00", "bis 10:00")),
    ("deti", ("Děti", "Children", "Kinder"),
     ("Vítány; přistýlky dle typu pokoje a kapacity",
      "Welcome; extra beds depending on room type and capacity",
      "Willkommen; Zustellbetten je nach Zimmertyp und Kapazität")),
    ("zvirata", ("Zvířata", "Pets", "Haustiere"),
     ("Povolena za poplatek (na vyžádání)",
      "Allowed for a fee (on request)",
      "Erlaubt gegen Gebühr (auf Anfrage)")),
    ("platba", ("Platby", "Payment", "Zahlung"),
     ("Platební karty (Visa, Mastercard, Maestro) i v hotovosti",
      "Credit cards (Visa, Mastercard, Maestro) and cash",
      "Kreditkarten (Visa, Mastercard, Maestro) und Bargeld")),
    ("koureni", ("Kouření", "Smoking", "Rauchen"),
     ("Ve všech vnitřních prostorách zakázáno",
      "Prohibited in all indoor areas",
      "In allen Innenräumen verboten")),
    ("storno", ("Storno", "Cancellation", "Stornierung"),
     ("Dle podmínek konkrétní rezervace (viz Obchodní podmínky)",
      "According to the terms of the specific booking (see Terms and Conditions)",
      "Gemäß den Bedingungen der jeweiligen Buchung (siehe AGB)")),
    ("jazyky", ("Jazyky personálu", "Staff languages", "Sprachen des Personals"),
     ("Čeština, angličtina", "Czech, English", "Tschechisch, Englisch")),
]


def dobre_vedet_html(l):
    i = I[l]
    radky = []
    for ikona, nazev, hodnota in DOBRE_VEDET:
        svg = ('<svg class="kl-ikona" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
               + IKONY[ikona] + '</svg>')
        radky.append(f'<div><dt>{svg}<span>{nazev[i]}</span></dt><dd>{hodnota[i]}</dd></div>')
    return '<dl class="know-list know-list--ikony">' + "".join(radky) + '</dl>'


# --- nadpisy sekcí stránky Ubytování ---
SEKCE = {
    "pokoje": {
        "kicker": ("T1 · Ubytování · 60 pokojů & 4 apartmá",
                   "T1 · Accommodation · 60 rooms & 4 apartments",
                   "T1 · Unterkunft · 60 Zimmer & 4 Appartements"),
        "nadpis": ("Kde po jízdě zastavíš", "Where you pull in after the ride",
                   "Wo du nach der Fahrt hältst"),
    },
    "prehled-pokoju": {
        "kicker": ("T2 · Přehled pokojů", "T2 · Room overview", "T2 · Zimmerübersicht"),
        "nadpis": ("64 pokojů a apartmá v pěti kategoriích",
                   "64 rooms and apartments in five categories",
                   "64 Zimmer und Appartements in fünf Kategorien"),
    },
    "srovnani-pokoju": {
        "kicker": ("T3 · Srovnání", "T3 · Comparison", "T3 · Vergleich"),
        "nadpis": ("Srovnání vybavenosti pokojů", "Room amenities compared",
                   "Zimmerausstattung im Vergleich"),
        "perex": ("Co která kategorie nabízí, vedle sebe. Kliknutím v záhlaví přejdete na detail kategorie.",
                  "What each category offers, side by side. Click a column heading for the category detail.",
                  "Was jede Kategorie bietet, nebeneinander. Ein Klick auf die Spaltenüberschrift führt zum Detail."),
    },
    "vybaveni": {
        "kicker": ("T4 · Vybavení & služby", "T4 · Amenities & services",
                   "T4 · Ausstattung & Services"),
        "nadpis": ("Vybavení hotelu a hotelových pokojů",
                   "Hotel and room amenities",
                   "Ausstattung des Hotels und der Zimmer"),
    },
    "galerie": {
        "kicker": ("T6 · Fotogalerie", "T6 · Photo gallery", "T6 · Fotogalerie"),
        "nadpis": ("Všechny pokoje ve fotkách", "Every room in pictures",
                   "Alle Zimmer in Bildern"),
        "perex": ("Snímky ze všech pěti kategorií pohromadě. Kliknutím se otevřou ve velkém.",
                  "Photos from all five categories together. Click to open them large.",
                  "Aufnahmen aus allen fünf Kategorien zusammen. Ein Klick öffnet sie groß."),
    },
    "dobre-vedet": {
        "kicker": ("T5 · Dobré vědět", "T5 · Good to know", "T5 · Gut zu wissen"),
        "nadpis": ("Podmínky pobytu", "Terms of stay", "Aufenthaltsbedingungen"),
    },
}

INTRO = (
    '<p style="max-width:60ch;margin-top:14px;color:var(--muted)">Vyberte si z 64 vysoce komfortních '
    'pokojů a apartmá splňujících veškeré parametry evropského standardu ****. Všechny pokoje mají '
    'klimatizaci, Wi-Fi, TV, trezor, možnost plného zatemnění a jsou vhodné i pro handicapované hosty.</p>',
    '<p style="max-width:60ch;margin-top:14px;color:var(--muted)">Choose from 64 highly comfortable '
    'rooms and apartments meeting every parameter of the European **** standard. All rooms have air '
    'conditioning, Wi-Fi, a TV, a safe, a full blackout option and are suitable for guests with '
    'reduced mobility.</p>',
    '<p style="max-width:60ch;margin-top:14px;color:var(--muted)">Wählen Sie aus 64 äußerst komfortablen '
    'Zimmern und Appartements, die sämtliche Parameter des europäischen ****-Standards erfüllen. Alle '
    'Zimmer verfügen über Klimaanlage, WLAN, TV, Tresor und vollständige Verdunkelung und sind auch '
    'für Gäste mit eingeschränkter Mobilität geeignet.</p>',
)
