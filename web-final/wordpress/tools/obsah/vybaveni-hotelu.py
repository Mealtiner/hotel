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

IDX = {"cz": 0, "en": 1, "de": 2}

def top_html(l):
    return "<ul class=\"amenity-grid\">" + "".join(f"<li>{x}</li>" for x in TOP[l]) + "</ul>"

def skupiny_html(l):
    i = IDX[l]
    ven = []
    for nazev, polozky in SKUPINY:
        li = "".join(f"<li>{p[i]}</li>" for p in polozky)
        ven.append(f"<section><h3>{nazev[i]}</h3><ul>{li}</ul></section>")
    return "<div class=\"amenity-groups\">" + "".join(ven) + "</div>"
