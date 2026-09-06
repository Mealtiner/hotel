# -*- coding: utf-8 -*-
"""Sestaví novou sadu 5 kategorií pokojů podle pojmenování a výbavy z Booking.com."""
import json, sys

SP = sys.argv[1]
puv = json.load(open(SP + "/pokoje-puvodni.json"))
stare = {r["key"]: r for r in puv["rooms"]}

# ---------- štítky ----------
labels = list(puv["labels"])
def pridej(key, cz, en, de):
    if any(l["key"] == key for l in labels):
        return
    labels.append({"key": key, "cz": cz, "en": en, "de": de})

pridej("47-m2", "47 m²", "47 m²", "47 m²")
pridej("58-m2", "58 m²", "58 m²", "58 m²")
pridej("vana", "vana", "bath", "Badewanne")
pridej("kavovar", "kávovar", "coffee machine", "Kaffeemaschine")
pridej("konvice", "rychlovarná konvice", "kettle", "Wasserkocher")
pridej("osoby-2-4", "2–4 osoby", "2–4 guests", "2–4 Personen")

# ---------- výbava z Bookingu ----------
KOUP = {
    "zaklad": {
        "cz": "Sprchový kout|Toaleta|Ručníky|Fén|Toaletní potřeby zdarma|Toaletní papír",
        "en": "Shower|Toilet|Towels|Hairdryer|Free toiletries|Toilet paper",
        "de": "Duschkabine|Toilette|Handtücher|Föhn|Kostenlose Pflegeprodukte|Toilettenpapier",
    },
    "zupan": {
        "cz": "Sprchový kout|Toaleta|Ručníky|Fén|Župan|Pantofle|Toaletní potřeby zdarma|Toaletní papír",
        "en": "Shower|Toilet|Towels|Hairdryer|Bathrobe|Slippers|Free toiletries|Toilet paper",
        "de": "Duschkabine|Toilette|Handtücher|Föhn|Bademantel|Pantoffeln|Kostenlose Pflegeprodukte|Toilettenpapier",
    },
    "vana": {
        "cz": "Vana|Sprchový kout|Toaleta|Ručníky|Fén|Župan|Pantofle|Toaletní potřeby zdarma|Toaletní papír",
        "en": "Bath|Shower|Toilet|Towels|Hairdryer|Bathrobe|Slippers|Free toiletries|Toilet paper",
        "de": "Badewanne|Duschkabine|Toilette|Handtücher|Föhn|Bademantel|Pantoffeln|Kostenlose Pflegeprodukte|Toilettenpapier",
    },
}

# Společný základ zařízení podle výpisu na Bookingu, ve stejném pořadí pro všechny kategorie.
ZAR_ZAKLAD = {
    "cz": ["Klimatizace", "Trezor", "Minibar", "TV s plochou obrazovkou", "Satelitní programy",
           "Telefon", "Wi-Fi zdarma", "Topení", "Psací stůl", "Prostor pro posezení",
           "Ložní prádlo", "Zásuvka u postele", "Koberec", "Skříň nebo šatna",
           "Věšák na oblečení", "Služba probuzení / budík", "Vyšší patra dostupná výtahem"],
    "en": ["Air conditioning", "Safe", "Minibar", "Flat-screen TV", "Satellite channels",
           "Telephone", "Free Wi-Fi", "Heating", "Desk", "Seating area",
           "Bed linen", "Socket near the bed", "Carpet", "Wardrobe or closet",
           "Clothes rack", "Wake-up service", "Upper floors accessible by lift"],
    "de": ["Klimaanlage", "Tresor", "Minibar", "Flachbild-TV", "Satellitenprogramme",
           "Telefon", "Kostenloses WLAN", "Heizung", "Schreibtisch", "Sitzecke",
           "Bettwäsche", "Steckdose am Bett", "Teppich", "Kleiderschrank oder Garderobe",
           "Kleiderständer", "Weckservice", "Obere Etagen mit dem Aufzug erreichbar"],
}
RADIO   = {"cz": "Rádio", "en": "Radio", "de": "Radio"}
KONVICE = {"cz": "Rychlovarná konvice", "en": "Kettle", "de": "Wasserkocher"}
TERASA  = {"cz": "Terasa", "en": "Terrace", "de": "Terrasse"}
KAVOVAR = {"cz": "Kávovar", "en": "Coffee machine", "de": "Coffee machine"}
KAVOVAR["de"] = "Kaffeemaschine"
POHOVKA = {"cz": "Pohovka", "en": "Sofa", "de": "Sofa"}

def zarizeni(l, pred=(), radio=True, konvice=False):
    """Poskládá seznam zařízení: nejdřív odlišující prvky, pak společný základ."""
    polozky = [p[l] for p in pred] + list(ZAR_ZAKLAD[l])
    if konvice:
        polozky.insert(polozky.index(ZAR_ZAKLAD[l][2]) + 1, KONVICE[l])
    if radio:
        polozky.insert(polozky.index(ZAR_ZAKLAD[l][4]) + 1, RADIO[l])
    return "|".join(polozky)

# ---------- pokoje ----------
def z(key, klic_stary=None):
    """Vezme původní pokoj jako základ, ať se nezahodí ruční texty."""
    return dict(stare.get(klic_stary or key, stare["standard"]))

rooms = []

# 1) Standard
r = z("standard")
r.update({
    "key": "standard", "home": 1, "pocet": "30", "kapacita": "1–2", "velikost": "24",
    "stitky": ["osoby-1-2", "klimatizace", "tv-40-hdmi", "trezor-minibar"],
    "koupelna_cz": KOUP["zaklad"]["cz"], "koupelna_en": KOUP["zaklad"]["en"], "koupelna_de": KOUP["zaklad"]["de"],
    "zarizeni_cz": zarizeni("cz"), "zarizeni_en": zarizeni("en"), "zarizeni_de": zarizeni("de"),
})
rooms.append(r)

# 2) Superior
r = z("superior")
r.update({
    "key": "superior", "home": 1, "pocet": "20", "kapacita": "2", "velikost": "24",
    "stitky": ["osoby-2", "track-view", "zupan-set", "konvice"],
    "koupelna_cz": KOUP["zupan"]["cz"], "koupelna_en": KOUP["zupan"]["en"], "koupelna_de": KOUP["zupan"]["de"],
    "zarizeni_cz": zarizeni("cz", konvice=True),
    "zarizeni_en": zarizeni("en", konvice=True),
    "zarizeni_de": zarizeni("de", konvice=True),
})
rooms.append(r)

# 3) Superior s terasou (dřív Superior Plus)
r = z("superior-s-terasou", "superior-plus")
r.update({
    "key": "superior-s-terasou", "home": 1, "pocet": "10", "kapacita": "2–3", "velikost": "24",
    "stitky": ["osoby-2-3", "terasa", "track-view", "zupan-set"],
    "kod_cz": "3.3 / SUPERIOR S TERASOU",
    "kod_en": "3.3 / SUPERIOR WITH TERRACE",
    "kod_de": "3.3 / SUPERIOR MIT TERRASSE",
    "kod": "3.3 / SUPERIOR S TERASOU",
    "nazev_cz": "Superior s terasou",
    "nazev_en": "Superior with Terrace",
    "nazev_de": "Superior mit Terrasse",
    "koupelna_cz": KOUP["zupan"]["cz"], "koupelna_en": KOUP["zupan"]["en"], "koupelna_de": KOUP["zupan"]["de"],
    "zarizeni_cz": zarizeni("cz", pred=[TERASA], konvice=True),
    "zarizeni_en": zarizeni("en", pred=[TERASA], konvice=True),
    "zarizeni_de": zarizeni("de", pred=[TERASA], konvice=True),
})
rooms.append(r)

# 4) Apartmá (47 m²)
r = z("apartma", "apartma-a-apartma-plus")
r.update({
    "key": "apartma", "home": 1, "pocet": "2", "kapacita": "2–4", "velikost": "47",
    "stitky": ["osoby-2-4", "47-m2", "terasa", "king-size"],
    "kod_cz": "3.4 / APARTMÁ", "kod_en": "3.4 / APARTMENT", "kod_de": "3.4 / APPARTEMENT",
    "kod": "3.4 / APARTMÁ",
    "nazev_cz": "Apartmá", "nazev_en": "Apartment", "nazev_de": "Appartement",
    "kratky_cz": "Apartmá 47 m² s výhledem na město i závodní okruh. Ložnice s postelí King Size, obývací pokoj, terasa a vana.",
    "kratky_en": "A 47 m² apartment overlooking the town and the racing circuit. A bedroom with a King Size bed, a living room, a terrace and a bath.",
    "kratky_de": "Appartement mit 47 m² und Aussicht auf die Stadt und die Rennstrecke. Schlafzimmer mit King-Size-Bett, Wohnzimmer, Terrasse und Badewanne.",
    "koupelna_cz": KOUP["vana"]["cz"], "koupelna_en": KOUP["vana"]["en"], "koupelna_de": KOUP["vana"]["de"],
    "zarizeni_cz": zarizeni("cz", pred=[TERASA, POHOVKA, KAVOVAR]),
    "zarizeni_en": zarizeni("en", pred=[TERASA, POHOVKA, KAVOVAR]),
    "zarizeni_de": zarizeni("de", pred=[TERASA, POHOVKA, KAVOVAR]),
    "popis_cz": ("<p>Apartmá o celkové velikosti 47 m² s výhledem na město a na závodní okruh.</p>"
        "<p>Ložnice je vybavena extra velkou manželskou postelí King Size, na ni navazuje samostatný obývací pokoj s pohovkou a posezením. Součástí je terasa.</p>"
        "<p>Apartmá je vybaveno dvěma TV 43\" s HDMI vstupem a dvěma telefony s přímou předvolbou. K dispozici je kávovar, minibar a individuálně nastavitelná klimatizace.</p>"
        "<p>Koupelna má vanu i sprchový kout, kosmetické zrcátko a vysoušeč vlasů. Hostům je zdarma k dispozici denně doplňovaný kávový a čajový set, hotelový župan, pantofle a minerální voda na pokoji.</p>"
        "<p>Apartmá má bezpečnostní kartový zámkový systém, pokojový trezor a možnost plného zatemnění. Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>"),
    "popis_en": ("<p>A 47 m² apartment overlooking the town and the racing circuit.</p>"
        "<p>The bedroom has an extra-large King Size double bed and adjoins a separate living room with a sofa and a seating area. A terrace is included.</p>"
        "<p>The apartment is equipped with two 43\" TVs with HDMI input and two direct-dial telephones. A coffee machine, a minibar and individually adjustable air conditioning are available.</p>"
        "<p>The bathroom has both a bath and a shower, a cosmetic mirror and a hairdryer. A daily replenished coffee and tea set, a hotel bathrobe, slippers and mineral water in the room are provided free of charge.</p>"
        "<p>The apartment features a key-card security lock system, an in-room safe and a full blackout option. On request, an extra pillow (or an orthopaedic pillow) and an ironing board with an iron are available free of charge.</p>"),
    "popis_de": ("<p>Appartement mit einer Gesamtgröße von 47 m² und Aussicht auf die Stadt und die Rennstrecke.</p>"
        "<p>Das Schlafzimmer ist mit einem extragroßen King-Size-Doppelbett ausgestattet, daran schließt ein separates Wohnzimmer mit Sofa und Sitzecke an. Eine Terrasse gehört dazu.</p>"
        "<p>Das Appartement verfügt über zwei 43\"-Fernseher mit HDMI-Eingang und zwei Telefone mit Direktwahl. Eine Kaffeemaschine, eine Minibar und eine individuell regulierbare Klimaanlage stehen zur Verfügung.</p>"
        "<p>Das Badezimmer hat sowohl eine Badewanne als auch eine Duschkabine, einen Kosmetikspiegel und einen Föhn. Den Gästen stehen kostenlos ein täglich aufgefülltes Kaffee- und Tee-Set, ein Hotelbademantel, Pantoffeln und Mineralwasser im Zimmer zur Verfügung.</p>"
        "<p>Das Appartement verfügt über ein Kartenschließsystem, einen Zimmertresor und die Möglichkeit der vollständigen Verdunkelung. Auf Anfrage erhalten Sie kostenlos ein zusätzliches Kissen (oder ein orthopädisches Kissen) sowie ein Bügelbrett mit Bügeleisen.</p>"),
})
rooms.append(r)

# 5) Apartmá Superior (58 m²) — bez rádia, jinak shodné s Apartmá
r = z("apartma-superior", "apartma-a-apartma-plus")
r.update({
    "key": "apartma-superior", "home": 1, "pocet": "2", "kapacita": "2–4", "velikost": "58",
    "stitky": ["osoby-2-4", "58-m2", "terasa", "kavovar"],
    "kod_cz": "3.5 / APARTMÁ SUPERIOR", "kod_en": "3.5 / SUPERIOR APARTMENT",
    "kod_de": "3.5 / SUPERIOR-APPARTEMENT", "kod": "3.5 / APARTMÁ SUPERIOR",
    "nazev_cz": "Apartmá Superior", "nazev_en": "Superior Apartment", "nazev_de": "Superior-Appartement",
    "kratky_cz": "Největší apartmá hotelu — 58 m² s výhledem na závodní okruh. Ložnice King Size, obývací pokoj, terasa a vana.",
    "kratky_en": "The largest apartment in the hotel — 58 m² overlooking the racing circuit. A King Size bedroom, a living room, a terrace and a bath.",
    "kratky_de": "Das größte Appartement des Hotels — 58 m² mit Aussicht auf die Rennstrecke. King-Size-Schlafzimmer, Wohnzimmer, Terrasse und Badewanne.",
    "koupelna_cz": KOUP["vana"]["cz"], "koupelna_en": KOUP["vana"]["en"], "koupelna_de": KOUP["vana"]["de"],
    "zarizeni_cz": zarizeni("cz", pred=[TERASA, POHOVKA, KAVOVAR], radio=False),
    "zarizeni_en": zarizeni("en", pred=[TERASA, POHOVKA, KAVOVAR], radio=False),
    "zarizeni_de": zarizeni("de", pred=[TERASA, POHOVKA, KAVOVAR], radio=False),
    "popis_cz": ("<p>Nejprostornější ubytování hotelu — apartmá o celkové velikosti 58 m² s výhledem na závodní okruh.</p>"
        "<p>Ložnice je vybavena extra velkou manželskou postelí King Size, na ni navazuje samostatný obývací pokoj s pohovkou a posezením. Součástí je nadstandardně řešená terasa.</p>"
        "<p>Apartmá je vybaveno dvěma TV 43\" s HDMI vstupem a dvěma telefony s přímou předvolbou. K dispozici je kávovar, minibar a individuálně nastavitelná klimatizace.</p>"
        "<p>Koupelna má vanu i sprchový kout, kosmetické zrcátko a vysoušeč vlasů. Hostům je zdarma k dispozici denně doplňovaný kávový a čajový set, hotelový župan, pantofle a minerální voda na pokoji.</p>"
        "<p>Apartmá má bezpečnostní kartový zámkový systém, pokojový trezor a možnost plného zatemnění. Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>"),
    "popis_en": ("<p>The most spacious accommodation in the hotel — an apartment with a total size of 58 m² overlooking the racing circuit.</p>"
        "<p>The bedroom has an extra-large King Size double bed and adjoins a separate living room with a sofa and a seating area. A generously designed terrace is included.</p>"
        "<p>The apartment is equipped with two 43\" TVs with HDMI input and two direct-dial telephones. A coffee machine, a minibar and individually adjustable air conditioning are available.</p>"
        "<p>The bathroom has both a bath and a shower, a cosmetic mirror and a hairdryer. A daily replenished coffee and tea set, a hotel bathrobe, slippers and mineral water in the room are provided free of charge.</p>"
        "<p>The apartment features a key-card security lock system, an in-room safe and a full blackout option. On request, an extra pillow (or an orthopaedic pillow) and an ironing board with an iron are available free of charge.</p>"),
    "popis_de": ("<p>Die geräumigste Unterkunft des Hotels — ein Appartement mit einer Gesamtgröße von 58 m² und Aussicht auf die Rennstrecke.</p>"
        "<p>Das Schlafzimmer ist mit einem extragroßen King-Size-Doppelbett ausgestattet, daran schließt ein separates Wohnzimmer mit Sofa und Sitzecke an. Eine großzügig gestaltete Terrasse gehört dazu.</p>"
        "<p>Das Appartement verfügt über zwei 43\"-Fernseher mit HDMI-Eingang und zwei Telefone mit Direktwahl. Eine Kaffeemaschine, eine Minibar und eine individuell regulierbare Klimaanlage stehen zur Verfügung.</p>"
        "<p>Das Badezimmer hat sowohl eine Badewanne als auch eine Duschkabine, einen Kosmetikspiegel und einen Föhn. Den Gästen stehen kostenlos ein täglich aufgefülltes Kaffee- und Tee-Set, ein Hotelbademantel, Pantoffeln und Mineralwasser im Zimmer zur Verfügung.</p>"
        "<p>Das Appartement verfügt über ein Kartenschließsystem, einen Zimmertresor und die Möglichkeit der vollständigen Verdunkelung. Auf Anfrage erhalten Sie kostenlos ein zusätzliches Kissen (oder ein orthopädisches Kissen) sowie ein Bügelbrett mit Bügeleisen.</p>"),
})
rooms.append(r)

# ---------- srovnávací tabulka ----------
K = ["standard", "superior", "superior-s-terasou", "apartma", "apartma-superior"]
def radek(cz, en, de, vals):
    return {"cz": cz, "en": en, "de": de,
            "vals": {k: {"cz": v[0], "en": v[1], "de": v[2]} for k, v in zip(K, vals)}}

T3 = ("✓", "✓", "✓")
NE = ("–", "–", "–")
compare = [
    radek("Postele", "Beds", "Betten", [
        ("TWIN / DOUBLE",) * 3, ("TWIN / DOUBLE",) * 3, ("TWIN / DOUBLE",) * 3,
        ("King Size",) * 3, ("King Size",) * 3]),
    radek("Velikost", "Size", "Größe", [
        ("24 m²",) * 3, ("24 m²",) * 3, ("24 m²",) * 3, ("47 m²",) * 3, ("58 m²",) * 3]),
    radek("Kapacita", "Capacity", "Kapazität", [
        ("1–2 osoby", "1–2 guests", "1–2 Personen"), ("2 osoby", "2 guests", "2 Personen"),
        ("2–3 osoby", "2–3 guests", "2–3 Personen"), ("2–4 osoby", "2–4 guests", "2–4 Personen"),
        ("2–4 osoby", "2–4 guests", "2–4 Personen")]),
    radek("Výhled", "View", "Aussicht", [
        ("areál / klid", "grounds / quiet", "Areal / Ruhe"),
        ("okruh + lesy", "circuit + forests", "Rennstrecke + Wälder"),
        ("okruh + terasa", "circuit + terrace", "Rennstrecke + Terrasse"),
        ("město · okruh", "town · circuit", "Stadt · Rennstrecke"),
        ("okruh · paddock", "circuit · paddock", "Rennstrecke · Paddock")]),
    radek("Terasa", "Terrace", "Terrasse", [NE, NE, T3, T3, T3]),
    radek("Obývací pokoj · pohovka", "Living room · sofa", "Wohnzimmer · Sofa", [NE, NE, NE, T3, T3]),
    radek("TV", "TV", "TV", [
        ("40&quot; HDMI",) * 3, ("40&quot; HDMI",) * 3, ("40&quot; HDMI",) * 3,
        ("2× 43&quot; HDMI",) * 3, ("2× 43&quot; HDMI",) * 3]),
    radek("Telefon s předvolbou", "Direct-dial telephone", "Telefon mit Direktwahl", [
        T3, T3, T3, ("2×",) * 3, ("2×",) * 3]),
    radek("Klimatizace", "Air conditioning", "Klimaanlage", [T3] * 5),
    radek("Wi-Fi · pracovní stůl", "Wi-Fi · desk", "Wi-Fi · Schreibtisch", [T3] * 5),
    radek("Trezor · minibar · zatemnění", "Safe · minibar · blackout", "Tresor · Minibar · Verdunkelung", [T3] * 5),
    radek("Sprchový kout", "Shower", "Duschkabine", [T3] * 5),
    radek("Vana", "Bath", "Badewanne", [NE, NE, NE, T3, T3]),
    radek("Rychlovarná konvice · kávovar", "Kettle · coffee machine", "Wasserkocher · Kaffeemaschine", [
        NE,
        ("konvice", "kettle", "Wasserkocher"),
        ("konvice", "kettle", "Wasserkocher"),
        ("kávovar", "coffee machine", "Kaffeemaschine"),
        ("kávovar", "coffee machine", "Kaffeemaschine")]),
    radek("Kosmetika · vysoušeč vlasů", "Cosmetics · hairdryer", "Kosmetik · Föhn", [T3] * 5),
    radek("Kávový/čajový set · župan · pantofle · minerálka",
          "Coffee/tea set · bathrobe · slippers · mineral water",
          "Kaffee-/Tee-Set · Bademantel · Pantoffeln · Mineralwasser", [
        ("za poplatek", "for a fee", "gegen Gebühr"),
        ("zdarma denně", "free daily", "täglich kostenlos"),
        ("zdarma denně", "free daily", "täglich kostenlos"),
        ("zdarma denně", "free daily", "täglich kostenlos"),
        ("zdarma denně", "free daily", "täglich kostenlos")]),
    radek("Bezbariérový", "Wheelchair accessible", "Barrierefrei", [
        ("✓ (vybrané)", "✓ (selected rooms)", "✓ (ausgewählte Zimmer)"), NE, NE, NE, NE]),
]

json.dump({"labels": labels, "rooms": rooms, "compare": compare},
          open(SP + "/pokoje-nove.json", "w", encoding="utf-8"),
          ensure_ascii=False, indent=1)
print("pokoju:", len(rooms), "| stitku:", len(labels), "| radku srovnani:", len(compare))
for r in rooms:
    print(f"  {r['key']:20s} {r['nazev_cz']:22s} {r['velikost']:>5s} m2  pocet={r['pocet']:>3s}  zarizeni={r['zarizeni_cz'].count('|')+1}")
