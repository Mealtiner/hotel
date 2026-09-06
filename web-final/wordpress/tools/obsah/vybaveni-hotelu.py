# -*- coding: utf-8 -*-
"""Vybavení hotelu podle Booking.com — CZ/EN/DE, jako HTML pro Divi text modul."""

TOP = {
 "cz": ["Parkování zdarma","Wi-Fi zdarma","Restaurace","Bar","Nekuřácké pokoje",
        "Recepce 24 hodin denně","Bezbariérový přístup","Pokojová služba"],
 "en": ["Free parking","Free Wi-Fi","Restaurant","Bar","Non-smoking rooms",
        "24-hour front desk","Wheelchair accessible","Room service"],
 "de": ["Kostenlose Parkplätze","Kostenloses WLAN","Restaurant","Bar","Nichtraucherzimmer",
        "24-Stunden-Rezeption","Barrierefreier Zugang","Zimmerservice"],
}

SKUPINY = [
 (("Koupelna","Bathroom","Badezimmer"), [
   ("Soukromá koupelna","Private bathroom","Eigenes Badezimmer"),
   ("Sprchový kout","Shower","Duschkabine"),
   ("Toaleta","Toilet","Toilette"),
   ("Ručníky","Towels","Handtücher"),
   ("Toaletní papír","Toilet paper","Toilettenpapier"),
   ("Toaletní potřeby zdarma","Free toiletries","Kostenlose Pflegeprodukte"),
   ("Fén","Hairdryer","Föhn")]),
 (("Ložnice a pokoj","Bedroom and room","Schlaf- und Wohnbereich"), [
   ("Ložní prádlo","Bed linen","Bettwäsche"),
   ("Skříň nebo šatna","Wardrobe or closet","Kleiderschrank oder Garderobe"),
   ("Věšák na oblečení","Clothes rack","Kleiderständer"),
   ("Zásuvka u postele","Socket near the bed","Steckdose am Bett"),
   ("Prostor pro posezení","Seating area","Sitzecke"),
   ("Psací stůl","Desk","Schreibtisch"),
   ("Koberec","Carpet","Teppich")]),
 (("Média a technologie","Media and technology","Medien und Technik"), [
   ("TV s plochou obrazovkou","Flat-screen TV","Flachbild-TV"),
   ("Satelitní programy","Satellite channels","Satellitenprogramme"),
   ("Telefon","Telephone","Telefon"),
   ("Wi-Fi zdarma v celém hotelu","Free Wi-Fi throughout the hotel","Kostenloses WLAN im gesamten Hotel")]),
 (("Jídlo a pití","Food and drink","Essen und Trinken"), [
   ("Restaurace","Restaurant","Restaurant"),
   ("Bar","Bar","Bar"),
   ("Snídaně","Breakfast","Frühstück"),
   ("Minibar","Minibar","Minibar"),
   ("Pokojová služba","Room service","Zimmerservice")]),
 (("Exteriér","Outdoors","Außenbereich"), [
   ("Sluneční terasa","Sun terrace","Sonnenterrasse"),
   ("Terasa","Terrace","Terrasse"),
   ("Venkovní nábytek","Outdoor furniture","Gartenmöbel")]),
 (("Parkování","Parking","Parken"), [
   ("Soukromé parkoviště zdarma v areálu hotelu","Free private parking on site","Kostenlose Privatparkplätze am Hotel"),
   ("Rezervace parkování není nutná","No parking reservation needed","Keine Parkplatzreservierung nötig"),
   ("Dobíjecí stanice pro elektromobily","Electric vehicle charging station","Ladestation für Elektrofahrzeuge"),
   ("Parkování pro handicapované","Accessible parking","Behindertenparkplatz")]),
 (("Služby","Services","Services"), [
   ("Recepce 24 hodin denně","24-hour front desk","24-Stunden-Rezeption"),
   ("Denní úklid","Daily housekeeping","Tägliche Reinigung"),
   ("Individuální přihlášení a odhlášení","Private check-in and check-out","Privates Ein- und Auschecken"),
   ("Úschova zavazadel","Luggage storage","Gepäckaufbewahrung"),
   ("Služba probuzení / budík","Wake-up service","Weckservice"),
   ("Služba praní (za poplatek)","Laundry service (surcharge)","Wäscheservice (gegen Gebühr)"),
   ("Společenské prostory (za poplatek)","Function rooms (surcharge)","Veranstaltungsräume (gegen Gebühr)"),
   ("Možnost vystavení faktury","Invoice on request","Rechnungsstellung möglich")]),
 (("Zabezpečení","Safety and security","Sicherheit"), [
   ("Hasicí přístroje","Fire extinguishers","Feuerlöscher"),
   ("Venkovní kamerový systém","CCTV outside the property","Videoüberwachung außerhalb des Gebäudes"),
   ("Kamerový systém ve společných prostorách","CCTV in common areas","Videoüberwachung in Gemeinschaftsbereichen"),
   ("Trezor","Safe","Tresor")]),
 (("Obecné","General","Allgemein"), [
   ("Klimatizace","Air conditioning","Klimaanlage"),
   ("Topení","Heating","Heizung"),
   ("Výtah","Lift","Aufzug"),
   ("Všechny prostory nekuřácké","Entirely non-smoking","Komplett Nichtraucher")]),
 (("Bezbariérovost","Accessibility","Barrierefreiheit"), [
   ("Bezbariérový přístup","Wheelchair accessible","Barrierefreier Zugang"),
   ("Bezbariérové pokoje","Accessible rooms","Barrierefreie Zimmer"),
   ("Vyšší patra dostupná výtahem","Upper floors accessible by lift","Obere Etagen mit dem Aufzug erreichbar")]),
 (("Zvířata","Pets","Haustiere"), [
   ("Domácí zvířata povolena za případný poplatek","Pets allowed, charges may apply","Haustiere erlaubt, ggf. gegen Gebühr")]),
 (("Jazyky personálu","Staff languages","Sprachen des Personals"), [
   ("Čeština","Czech","Tschechisch"),
   ("Angličtina","English","Englisch")]),
]

# Piktogramy ke skupinám. Booking odlišuje skupiny ikonou, ne popiskem —
# u seznamu o dvanácti skupinách to výrazně zrychlí orientaci. Kreslené
# jednou čarou, aby držely váhu i v malé velikosti.
IKONY = {
 "Koupelna":              '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4zM7 12V6a2 2 0 0 1 4 0M7 20l-1 2M17 20l1 2"/>',
 "Ložnice a pokoj":       '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 14h18M7 9V7h5v2M3 18v2M21 18v2"/>',
 "Média a technologie":   '<path d="M3 5h18v11H3zM9 20h6M12 16v4"/>',
 "Jídlo a pití":          '<path d="M5 3v8a2 2 0 0 0 4 0V3M7 11v10M15 3c-1.5 1.5-2 3-2 5s.5 3 2 3h2V3zM17 11v10"/>',
 "Exteriér":              '<path d="M12 3v2M12 19v2M3 12h2M19 12h2M6 6l1.5 1.5M16.5 16.5L18 18M18 6l-1.5 1.5M7.5 16.5L6 18M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"/>',
 "Parkování":             '<path d="M9 18V6h4a3.5 3.5 0 0 1 0 7H9M4 3h16v18H4z"/>',
 "Služby":                '<path d="M3 18h18M12 6a7 7 0 0 1 7 7H5a7 7 0 0 1 7-7zM12 6V4"/>',
 "Zabezpečení":           '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
 "Obecné":                '<path d="M4 6h16v9H4zM8 19h8M6 9h5M6 12h3"/>',
 "Bezbariérovost":        '<path d="M13 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM11 8v5h4l2 6M11 11H8a4.5 4.5 0 1 0 5 6"/>',
 "Zvířata":               '<path d="M5 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM19 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM9 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM15 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM12 12c-3 0-5 2.5-5 5a3 3 0 0 0 4 2.8 4 4 0 0 1 2 0A3 3 0 0 0 17 17c0-2.5-2-5-5-5z"/>',
 "Jazyky personálu":      '<path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20zM21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9z"/>',
}

IDX = {"cz": 0, "en": 1, "de": 2}

def top_html(l):
    return "<ul class=\"amenity-grid\">" + "".join(f"<li>{x}</li>" for x in TOP[l]) + "</ul>"

def skupiny_html(l):
    i = IDX[l]
    ven = []
    for nazev, polozky in SKUPINY:
        li = "".join(f"<li>{p[i]}</li>" for p in polozky)
        # Ikona se hledá podle českého názvu skupiny — ten je ve všech jazycích
        # klíčem, protože překlady se mění, zatímco skupina zůstává táž.
        kresba = IKONY.get(nazev[0], "")
        ikona = (f'<svg class="ag-ikona" viewBox="0 0 24 24" aria-hidden="true" focusable="false">{kresba}</svg>'
                 if kresba else "")
        ven.append(f"<section><h3>{ikona}<span>{nazev[i]}</span></h3><ul>{li}</ul></section>")
    return "<div class=\"amenity-groups\">" + "".join(ven) + "</div>"
