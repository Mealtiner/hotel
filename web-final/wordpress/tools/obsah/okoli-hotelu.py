# -*- coding: utf-8 -*-
"""Sekce „Okolí hotelu" pro stránku O nás — data z Booking.com doplněná o popisky."""

I = {"cz": 0, "en": 1, "de": 2}

TITULEK = ("Okolí hotelu", "The area around the hotel", "Die Umgebung des Hotels")
NADPIS = ("Co máte na dosah", "What is within reach", "Was in der Nähe liegt")
PERex = (
 "Hotel stojí přímo v areálu Automotodromu Brno. Z terasy je to pár kroků k okruhu, "
 "z pokojů je Brno vzdálené čtvrt hodiny autem.",
 "The hotel stands directly inside the Brno Circuit complex. The track is a few steps from the terrace, "
 "and the centre of Brno is a quarter of an hour away by car.",
 "Das Hotel steht direkt auf dem Gelände des Automotodrom Brno. Von der Terrasse sind es wenige Schritte "
 "zur Rennstrecke, und das Zentrum von Brünn ist eine Viertelstunde mit dem Auto entfernt.")

# ---- vedoucí blok: Motodrom ----
MOTO_KICK = ("0 km · přímo v areálu", "0 km · on the grounds", "0 km · direkt auf dem Gelände")
MOTO_NAZEV = ("Automotodrom Brno — Masarykův okruh",
              "Brno Circuit — the Masaryk Circuit",
              "Automotodrom Brno — der Masaryk-Ring")
MOTO_TEXT = (
 ["Masarykův okruh je důvod, proč hotel stojí právě tady. Trať měří 5,403 km, má "
  "čtrnáct zatáček a převýšení přes 70 metrů — patří k technicky nejnáročnějším "
  "okruhům v Evropě a po desetiletí hostila Grand Prix silničních motocyklů.",
  "Hotel je součástí areálu. Do paddocku, k tribunám i k depu dojdete pěšky, bez "
  "přejíždění a bez hledání parkování. V závodních víkendech to znamená, že ráno "
  "vyjdete z pokoje a jste na místě dřív, než se otevřou brány pro veřejnost.",
  "Mimo závodní kalendář je okruh v provozu prakticky celoročně: jízdy veřejnosti, "
  "testy týmů, firemní akce, školy smyku a kurzy bezpečné jízdy. Aktuální program "
  "najdete na stránkách Automotodromu."],
 ["The Masaryk Circuit is the reason the hotel stands where it does. The track is "
  "5.403 km long, has fourteen corners and more than 70 metres of elevation change — "
  "one of the most technically demanding circuits in Europe, and for decades the home "
  "of the motorcycle Grand Prix.",
  "The hotel is part of the complex. The paddock, the grandstands and the pit lane are "
  "all within walking distance, with no driving and no hunting for a parking space. On a "
  "race weekend that means you step out of your room and are trackside before the public "
  "gates even open.",
  "Outside the racing calendar the circuit runs almost all year round: public track days, "
  "team testing, corporate events, skid schools and safe-driving courses. The current "
  "programme is published on the circuit's own website."],
 ["Der Masaryk-Ring ist der Grund, warum das Hotel genau hier steht. Die Strecke ist "
  "5,403 km lang, hat vierzehn Kurven und über 70 Meter Höhenunterschied — sie zählt zu "
  "den technisch anspruchsvollsten Rennstrecken Europas und war jahrzehntelang Austragungsort "
  "des Motorrad-Grand-Prix.",
  "Das Hotel ist Teil des Geländes. Paddock, Tribünen und Boxengasse erreichen Sie zu Fuß, "
  "ohne Anfahrt und ohne Parkplatzsuche. An einem Rennwochenende heißt das: Sie treten aus "
  "dem Zimmer und stehen an der Strecke, bevor die Tore für das Publikum öffnen.",
  "Außerhalb des Rennkalenders ist die Strecke praktisch ganzjährig in Betrieb: Touristenfahrten, "
  "Teamtests, Firmenveranstaltungen, Schleuderkurse und Fahrsicherheitstrainings. Das aktuelle "
  "Programm finden Sie auf den Seiten des Automotodrom."])

MOTO_FOTO = "/wp-content/themes/grid-divi5-child/assets/foto/okruh-letecky-2.jpg"

# ---- filtr ----
TYPY = [
 ("vse",     ("Vše", "All", "Alle")),
 ("pamatky", ("Památky a architektura", "Landmarks and architecture", "Sehenswürdigkeiten und Architektur")),
 ("priroda", ("Příroda a volný čas", "Nature and leisure", "Natur und Freizeit")),
 ("jidlo",   ("Jídlo a pití", "Food and drink", "Essen und Trinken")),
 ("doprava", ("Doprava", "Getting around", "Verkehrsanbindung")),
]

# (typ, vzdálenost, název cz/en/de, popis cz/en/de)
MISTA = [
 ("jidlo", "3,6 km",
  ("Restaurace U Richarda", "U Richarda restaurant", "Restaurant U Richarda"),
  ("Nejbližší restaurace mimo hotel. Česká kuchyně, poledních menu, deset minut autem.",
   "The closest restaurant outside the hotel. Czech cooking, lunch menus, ten minutes by car.",
   "Das nächstgelegene Restaurant außerhalb des Hotels. Tschechische Küche, Mittagsmenüs, zehn Autominuten.")),
 ("jidlo", "4 km",
  ("Pizza Žebětín", "Pizza Žebětín", "Pizza Žebětín"),
  ("Pizzerie na okraji Brna. Rychlá volba na večeři, když se nechcete nikam vypravovat.",
   "A pizzeria on the edge of Brno. A quick dinner option when you do not want to travel far.",
   "Eine Pizzeria am Stadtrand von Brünn. Die schnelle Wahl zum Abendessen.")),
 ("jidlo", "5 km",
  ("Hajenka Kamechy", "Hajenka Kamechy", "Hajenka Kamechy"),
  ("Kavárna a bar v brněnských Kamechách, na půl cesty do města.",
   "A café and bar in the Kamechy district, halfway into the city.",
   "Café und Bar im Brünner Stadtteil Kamechy, auf halbem Weg in die Stadt.")),
 ("pamatky", "7 km",
  ("Státní hrad Veveří", "Veveří Castle", "Burg Veveří"),
  ("Jeden z největších hradních areálů u nás, na ostrohu nad Brněnskou přehradou. "
   "Prohlídkové okruhy, nádvoří s kavárnou a v sezóně lodní spojení z Bystrce.",
   "One of the largest castle complexes in the country, on a spur above the Brno reservoir. "
   "Guided tours, a courtyard café and, in season, a boat connection from Bystrc.",
   "Eine der größten Burganlagen des Landes, auf einem Sporn über der Brünner Talsperre. "
   "Führungen, Hofcafé und in der Saison eine Schiffsverbindung ab Bystrc.")),
 ("pamatky", "7 km",
  ("Rosice", "Rosice", "Rosice"),
  ("Nejbližší město se zámkem a klidným náměstím. Dobrý cíl na dopoledne, když chcete "
   "z okruhu ven, ale ne až do Brna.",
   "The nearest town, with a chateau and a quiet square. A good half-day trip when you want "
   "to leave the circuit but not go all the way into Brno.",
   "Die nächstgelegene Stadt mit Schloss und ruhigem Platz. Ein gutes Ziel für den Vormittag.")),
 ("pamatky", "8 km",
  ("Rahnův palác", "Rahn Palace", "Rahn-Palais"),
  ("Historická budova v Rosicích, dnes využívaná pro výstavy a kulturní program.",
   "A historic building in Rosice, today used for exhibitions and cultural events.",
   "Ein historisches Gebäude in Rosice, heute für Ausstellungen und Kulturprogramm genutzt.")),
 ("jidlo", "8 km",
  ("Starý Pivovár", "Starý Pivovár brewery", "Brauerei Starý Pivovár"),
  ("Pivovarská restaurace s vlastním pivem a poctivou českou kuchyní.",
   "A brewery restaurant with its own beer and hearty Czech food.",
   "Brauereigasthaus mit eigenem Bier und deftiger tschechischer Küche.")),
 ("priroda", "9 km",
  ("ZOO Brno", "Brno Zoo", "Zoo Brünn"),
  ("Zoo na svahu Mniší hory nad přehradou. Celodenní program pro rodiny s dětmi, "
   "výběhy vedené po vrstevnici svahu.",
   "A zoo on the slope of Mniší hora above the reservoir. A full day out for families, "
   "with enclosures laid out along the hillside.",
   "Zoo am Hang des Mniší hora über der Talsperre. Ein Ganztagesprogramm für Familien.")),
 ("priroda", "10 km",
  ("Koupaliště Střelice", "Střelice open-air pool", "Freibad Střelice"),
  ("Přírodní koupaliště s trávníkem a stínem. V létě nejbližší místo na odpolední koupání.",
   "A natural open-air pool with lawns and shade. In summer, the closest place for an afternoon swim.",
   "Naturfreibad mit Liegewiese und Schatten. Im Sommer die nächste Bademöglichkeit.")),
 ("pamatky", "14 km",
  ("Starobrněnský klášter", "Old Brno Abbey", "Abtei Altbrünn"),
  ("Augustiniánské opatství na Mendlově náměstí, kde Gregor Mendel prováděl své pokusy s hrachem. "
   "Součástí je bazilika Nanebevzetí Panny Marie a Mendelovo muzeum.",
   "The Augustinian abbey on Mendel Square, where Gregor Mendel ran his experiments with peas. "
   "It includes the Basilica of the Assumption and the Mendel Museum.",
   "Die Augustinerabtei am Mendel-Platz, wo Gregor Mendel seine Erbsenversuche durchführte. "
   "Dazu gehören die Basilika Mariä Himmelfahrt und das Mendel-Museum.")),
 ("priroda", "15 km",
  ("Botanická zahrada", "Botanical Garden", "Botanischer Garten"),
  ("Botanická zahrada Masarykovy univerzity se skleníky a sbírkami sukulentů. Klidná zastávka "
   "kousek od centra.",
   "The Masaryk University botanical garden, with glasshouses and succulent collections. "
   "A quiet stop close to the centre.",
   "Der Botanische Garten der Masaryk-Universität mit Gewächshäusern und Sukkulentensammlungen.")),
 ("pamatky", "15 km",
  ("Nová radnice", "New Town Hall", "Neues Rathaus"),
  ("Sídlo brněnského magistrátu na Dominikánském náměstí, s renesančním nádvořím přístupným veřejnosti.",
   "The seat of the city administration on Dominican Square, with a Renaissance courtyard open to the public.",
   "Sitz der Stadtverwaltung am Dominikanerplatz, mit öffentlich zugänglichem Renaissancehof.")),
 ("pamatky", "16 km",
  ("Zemská sněmovna", "Moravian Diet building", "Mährischer Landtag"),
  ("Novorenesanční budova dnešního krajského úřadu, jedna z výrazných brněnských staveb 19. století.",
   "A neo-Renaissance building, today the regional authority, and one of Brno's landmark 19th-century structures.",
   "Ein Neorenaissancebau, heute Sitz der Kreisbehörde, und eines der markanten Brünner Gebäude des 19. Jahrhunderts.")),
 ("pamatky", "16 km",
  ("Kapucínská hrobka", "Capuchin Crypt", "Kapuzinergruft"),
  ("Hrobka kapucínského kláštera s přirozeně mumifikovanými ostatky mnichů a šlechticů. "
   "Nejneobvyklejší brněnská památka.",
   "The crypt of the Capuchin monastery, with the naturally mummified remains of monks and nobles. "
   "Brno's most unusual monument.",
   "Die Gruft des Kapuzinerklosters mit den natürlich mumifizierten Überresten von Mönchen und Adeligen.")),
 ("pamatky", "16 km",
  ("Hrad Špilberk", "Špilberk Castle", "Burg Spielberg"),
  ("Hrad a pevnost nad centrem Brna. Muzeum města Brna, kasematy a vyhlídka na celé město.",
   "The castle and fortress above the centre of Brno. The city museum, the casemates and a view over the whole city.",
   "Burg und Festung über dem Zentrum von Brünn. Stadtmuseum, Kasematten und Aussicht über die ganze Stadt.")),
 ("pamatky", "16 km",
  ("Náměstí Svobody", "Freedom Square", "Platz der Freiheit"),
  ("Hlavní brněnské náměstí a přirozený výchozí bod pro procházku historickým centrem.",
   "Brno's main square and the natural starting point for a walk through the historic centre.",
   "Der Hauptplatz von Brünn und der natürliche Ausgangspunkt für einen Spaziergang durch die Altstadt.")),
 ("pamatky", "16 km",
  ("Stará radnice", "Old Town Hall", "Altes Rathaus"),
  ("Nejstarší světská stavba v Brně. Pilgramův portál, brněnský drak a kolo, "
   "vyhlídková věž nad střechami města.",
   "The oldest secular building in Brno. Pilgram's portal, the Brno dragon and wheel, "
   "and a viewing tower above the rooftops.",
   "Das älteste weltliche Gebäude Brünns. Pilgram-Portal, Brünner Drache und Rad sowie ein Aussichtsturm.")),
 ("pamatky", "17 km",
  ("Vila Tugendhat", "Villa Tugendhat", "Villa Tugendhat"),
  ("Funkcionalistická vila Miese van der Rohe zapsaná na seznamu UNESCO. "
   "Vstup pouze na rezervaci, obvykle několik týdnů dopředu.",
   "Mies van der Rohe's functionalist villa, a UNESCO World Heritage site. "
   "Entry by advance booking only, usually several weeks ahead.",
   "Die funktionalistische Villa von Mies van der Rohe, UNESCO-Welterbe. "
   "Eintritt nur mit Voranmeldung, meist mehrere Wochen im Voraus.")),
 ("doprava", "8 km",
  ("Vlaková stanice Tetčice", "Tetčice railway station", "Bahnhof Tetčice"),
  ("Nejbližší železniční zastávka. Přímé spojení na brněnské hlavní nádraží.",
   "The nearest railway stop, with a direct connection to Brno main station.",
   "Der nächstgelegene Bahnhof, mit direkter Verbindung zum Brünner Hauptbahnhof.")),
 ("doprava", "10 km",
  ("Vlaková stanice Omice", "Omice railway station", "Bahnhof Omice"),
  ("Druhá zastávka na trati směr Brno, alternativa k Tetčicím.",
   "The second stop on the line towards Brno, an alternative to Tetčice.",
   "Die zweite Haltestelle an der Strecke Richtung Brünn, eine Alternative zu Tetčice.")),
 ("doprava", "25 km",
  ("Letiště Brno-Tuřany", "Brno-Tuřany Airport", "Flughafen Brünn-Tuřany"),
  ("Nejbližší mezinárodní letiště. Transfer z hotelu na vyžádání, za poplatek.",
   "The nearest international airport. A hotel transfer is available on request, for a fee.",
   "Der nächstgelegene internationale Flughafen. Hoteltransfer auf Anfrage, gegen Gebühr.")),
]


def lead_html(l):
    i = I[l]
    odstavce = "".join(f"<p>{t}</p>" for t in MOTO_TEXT[i])
    return (
      '<div class="okoli-lead">'
      '<div class="okoli-lead__text">'
      f'<span class="okoli-lead__dist">{MOTO_KICK[i]}</span>'
      f'<h3>{MOTO_NAZEV[i]}</h3>{odstavce}'
      '</div>'
      f'<figure class="okoli-lead__foto"><img src="{MOTO_FOTO}" alt="{MOTO_NAZEV[i]}" loading="lazy"></figure>'
      '</div>')


def filtr_html(l):
    i = I[l]
    b = []
    for klic, nazev in TYPY:
        pocet = len(MISTA) if klic == "vse" else sum(1 for m in MISTA if m[0] == klic)
        stisk = "true" if klic == "vse" else "false"
        b.append(f'<button type="button" class="gh-filter" data-filtr="{klic}" '
                 f'aria-pressed="{stisk}">{nazev[i]} <span>{pocet}</span></button>')
    popis = ("Filtr míst v okolí", "Filter nearby places", "Filter für Orte in der Umgebung")[i]
    return f'<div class="okoli-filtr" role="group" aria-label="{popis}">' + "".join(b) + '</div>'


def karty_html(l):
    i = I[l]
    k = []
    for typ, vzdal, nazev, popis in MISTA:
        k.append(f'<article class="okoli-card" data-typ="{typ}">'
                 f'<span class="okoli-card__dist">{vzdal}</span>'
                 f'<h4>{nazev[i]}</h4><p>{popis[i]}</p></article>')
    return '<div class="okoli-grid">' + "".join(k) + '</div>'
