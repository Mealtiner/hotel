# -*- coding: utf-8 -*-
"""Obsah stránky Masarykův okruh.

Fakta pocházejí z oficiálních zdrojů (automotodrombrno.cz, motogpczechia.com),
texty jsou psané pro web hotelu — nejde o kopii oficiálních stránek. Na vhodných
místech vede odkaz zpět na oficiální zdroj, který má vždy aktuálnější informace.
"""

I = {"cz": 0, "en": 1, "de": 2}
AMD = "https://www.automotodrombrno.cz/"
GP  = "https://www.motogpczechia.com/"

TITULEK = ("Masarykův okruh", "The Masaryk Circuit", "Der Masaryk-Ring")

# ---------------------------------------------------------------- T1 úvod
T1 = {
    "kicker": ("T1 · Masarykův okruh", "T1 · The Masaryk Circuit", "T1 · Der Masaryk-Ring"),
    "nadpis": ("Legenda, na kterou se díváte z okna",
               "A legend you can see from your window",
               "Eine Legende, die Sie aus dem Fenster sehen"),
    "text": (
      ["<p class=\"sec-lead\">GRID Hotel stojí přímo v areálu Automotodromu Brno. Trať, o které je tahle stránka, "
       "začíná pár desítek metrů od recepce — a její příběh se píše od roku 1930.</p>",
       "<p>Dnešní okruh měří 5 403 metrů a je pátou podobou trati, která kdysi vedla lesy a vesnicemi západně "
       "od Brna v délce přes devětadvacet kilometrů. Z původního okruhu se dodnes jezdí po fragmentech: ať "
       "přijedete k hotelu z kterékoli strany, kus té staré trati máte pod koly.</p>",
       "<p>Následující shrnutí je psané pro hosty hotelu. Aktuální program závodů, vstupenky a oficiální "
       "informace najdete na stránkách provozovatele okruhu a pořadatele Grand Prix.</p>"],
      ["<p class=\"sec-lead\">GRID Hotel stands right inside the Brno Circuit complex. The track this page is about "
       "begins a few dozen metres from reception — and its story goes back to 1930.</p>",
       "<p>Today's circuit is 5,403 metres long and is the fifth incarnation of a track that once ran through "
       "forests and villages west of Brno for more than twenty-nine kilometres. Parts of the original layout are "
       "still public roads: whichever way you approach the hotel, you drive on a piece of that old circuit.</p>",
       "<p>The summary below is written for hotel guests. For the current race programme, tickets and official "
       "information, see the circuit operator's and the Grand Prix promoter's websites.</p>"],
      ["<p class=\"sec-lead\">Das GRID Hotel steht direkt auf dem Gelände des Automotodrom Brno. Die Strecke, um die es "
       "auf dieser Seite geht, beginnt wenige Dutzend Meter von der Rezeption entfernt — und ihre Geschichte "
       "reicht bis 1930 zurück.</p>",
       "<p>Der heutige Kurs misst 5 403 Meter und ist die fünfte Gestalt einer Strecke, die einst über mehr als "
       "neunundzwanzig Kilometer durch Wälder und Dörfer westlich von Brünn führte. Teile der ursprünglichen "
       "Streckenführung sind bis heute öffentliche Straßen: aus welcher Richtung Sie auch zum Hotel kommen, "
       "Sie fahren ein Stück des alten Kurses.</p>",
       "<p>Die folgende Zusammenfassung ist für Hotelgäste geschrieben. Das aktuelle Rennprogramm, Tickets und "
       "offizielle Informationen finden Sie auf den Seiten des Streckenbetreibers und des Grand-Prix-Veranstalters.</p>"],
    ),
    "odkazy": (
      [("Automotodrom Brno", AMD), ("Grand Prix České republiky", GP)],
      [("Brno Circuit", AMD), ("Czech Republic Grand Prix", GP)],
      [("Automotodrom Brno", AMD), ("Großer Preis der Tschechischen Republik", GP)],
    ),
}

# ---------------------------------------------------------------- T2 dráha v číslech
T2 = {
    "kicker": ("T2 · Dráha", "T2 · The track", "T2 · Die Strecke"),
    "nadpis": ("Okruh v číslech", "The circuit in numbers", "Die Strecke in Zahlen"),
    "perex": ("Parametry dnešní podoby trati podle provozovatele okruhu.",
              "The specifications of the current layout, as published by the circuit operator.",
              "Die Daten der heutigen Streckenführung laut Streckenbetreiber."),
}

CISLA = [
    (("Délka dráhy", "Track length", "Streckenlänge"), "5 403,19 m"),
    (("Šířka dráhy", "Track width", "Streckenbreite"), "15 m"),
    (("Zatáček", "Corners", "Kurven"), ("14 — 6 levých, 8 pravých", "14 — 6 left, 8 right", "14 — 6 links, 8 rechts")),
    (("Přímých úseků", "Straights", "Geraden"), ("13 — od 35 m do 636,56 m", "13 — from 35 m to 636.56 m", "13 — von 35 m bis 636,56 m")),
    (("Poloměr zatáček", "Corner radius", "Kurvenradius"), ("min. 50 m, max. 300 m", "min. 50 m, max. 300 m", "min. 50 m, max. 300 m")),
    (("Převýšení", "Elevation change", "Höhenunterschied"), "73,75 m"),
    (("Nadmořská výška", "Altitude", "Seehöhe"), "450 m"),
    (("Největší stoupání", "Steepest climb", "Stärkste Steigung"), ("7,5 % na délce 917 m", "7.5% over 917 m", "7,5 % auf 917 m")),
    (("Největší klesání", "Steepest descent", "Stärkstes Gefälle"), ("5 % na délce 410 m", "5% over 410 m", "5 % auf 410 m")),
    (("Traťový rekord", "Lap record", "Streckenrekord"), "1:36,065"),
]

# ---------------------------------------------------------------- T3 historie
T3 = {
    "kicker": ("T3 · Historie", "T3 · History", "T3 · Geschichte"),
    "nadpis": ("Od žulových kostek k MotoGP", "From granite setts to MotoGP", "Vom Granitpflaster zur MotoGP"),
    "perex": ("Devět desetiletí v osmi zastaveních. Podrobnou historii vypráví oficiální web okruhu.",
              "Nine decades in eight stops. The circuit's own website tells the full story.",
              "Neun Jahrzehnte in acht Stationen. Die ausführliche Geschichte erzählt die offizielle Website."),
}

MILNIKY = [
    ("1930", ("První závod", "The first race", "Das erste Rennen"),
     ("Trať dlouhá 29,1 km vede lesy i zástavbou západně od Brna. Na vytyčení se podílí Eliška Junková. "
      "Do Brna přijíždí Scuderia Ferrari a s ní jména jako Nuvolari, Caracciola nebo Chiron.",
      "A 29.1 km track through forests and villages west of Brno. Eliška Junková helps lay it out. "
      "Scuderia Ferrari comes to Brno, and with it names like Nuvolari, Caracciola and Chiron.",
      "Eine 29,1 km lange Strecke durch Wälder und Ortschaften westlich von Brünn. Eliška Junková hilft bei der "
      "Streckenführung. Die Scuderia Ferrari kommt nach Brünn, dazu Namen wie Nuvolari, Caracciola und Chiron.")),
    ("1937", ("Nevědomá rozlučka", "An unwitting farewell", "Ein unwissender Abschied"),
     ("Stříbrné šípy Auto Unionu a Mercedesu vládnou Brnu od roku 1934. Rok 1937 je na dlouho posledním "
      "závodním víkendem — přerušila ho válka.",
      "The Silver Arrows of Auto Union and Mercedes rule Brno from 1934. 1937 is the last race weekend for a long "
      "time — the war interrupted everything.",
      "Die Silberpfeile von Auto Union und Mercedes beherrschen Brünn ab 1934. 1937 ist für lange Zeit das letzte "
      "Rennwochenende — der Krieg unterbrach alles.")),
    ("1949", ("Formule 1 v Brně", "Formula 1 in Brno", "Formel 1 in Brünn"),
     ("Na zkráceném okruhu o délce 17,8 km se jede Velká cena Československa vozů formule 1 — jediný závod "
      "F1 v historii země. Mistrovství světa vzniklo až o rok později.",
      "On a shortened 17.8 km layout, the Czechoslovak Grand Prix for Formula 1 cars is held — the only F1 race "
      "in the country's history. The world championship was founded a year later.",
      "Auf der verkürzten 17,8-km-Variante findet der Große Preis der Tschechoslowakei für Formel-1-Wagen statt — "
      "das einzige F1-Rennen in der Geschichte des Landes. Die Weltmeisterschaft entstand erst ein Jahr später.")),
    ("1950", ("Nástup motocyklů", "Motorcycles arrive", "Die Motorräder kommen"),
     ("První motocyklový závod na okruhu. Zpočátku má národní charakter, na konci desetiletí se sem "
      "vracejí světová jména i domácí hvězdy v čele s Františkem Šťastným.",
      "The first motorcycle race at the circuit. National in character at first; by the end of the decade "
      "international names return, alongside home stars led by František Šťastný.",
      "Das erste Motorradrennen auf der Strecke. Zunächst national geprägt; gegen Ende des Jahrzehnts kehren "
      "internationale Namen zurück, neben heimischen Stars um František Šťastný.")),
    ("1965", ("Mistrovský debut", "World championship debut", "WM-Debüt"),
     ("Po zkrácení na 13,9 km a rekonstrukci povrchu vítá Brno mistrovství světa silničních motocyklů. "
      "V roce 1966 tu Mike Hailwood vyhrává hned tři kubatury.",
      "After being shortened to 13.9 km and resurfaced, Brno welcomes the road racing world championship. "
      "In 1966 Mike Hailwood wins three classes here in one weekend.",
      "Nach der Verkürzung auf 13,9 km und einer neuen Fahrbahndecke empfängt Brünn die Motorrad-WM. "
      "1966 gewinnt Mike Hailwood hier gleich drei Klassen.")),
    ("1968–1973", ("Éra Agostiniho", "The Agostini era", "Die Ära Agostini"),
     ("Giacomo Agostini vyhrává v Brně šest let po sobě. Nejúspěšnějším českým jezdcem velké ceny zůstává "
      "Bohumil Staša se dvěma umístěními na stupních vítězů.",
      "Giacomo Agostini wins at Brno six years in a row. The most successful Czech rider of the Grand Prix "
      "remains Bohumil Staša, with two podium finishes.",
      "Giacomo Agostini gewinnt in Brünn sechs Jahre in Folge. Erfolgreichster tschechischer Fahrer des Grand "
      "Prix bleibt Bohumil Staša mit zwei Podestplätzen.")),
    ("1987", ("Nový autodrom", "The new circuit", "Das neue Autodrom"),
     ("Městská trať se stává neudržitelnou a vzniká dnešní uzavřený areál — symbolicky uvnitř původního "
      "okruhu. Vrací se mistrovství světa silničních motocyklů i cestovních automobilů.",
      "The street circuit becomes unsustainable and today's closed venue is built — symbolically inside the "
      "original layout. Both the motorcycle and touring car world championships return.",
      "Der Stadtkurs wird untragbar, und die heutige geschlossene Anlage entsteht — symbolisch innerhalb der "
      "ursprünglichen Streckenführung. Sowohl die Motorrad- als auch die Tourenwagen-WM kehren zurück.")),
    ("2025", ("Návrat MotoGP", "MotoGP returns", "Rückkehr der MotoGP"),
     ("Po pauze od roku 2020 se Grand Prix České republiky vrací. Areál prošel rozsáhlou rekonstrukcí — "
      "nový asfalt, LED signalizace, širší bezpečnostní zóny. Obnovená premiéra proběhla 18.–20. července 2025.",
      "After a break since 2020, the Czech Republic Grand Prix returns. The venue underwent a major "
      "reconstruction — new asphalt, LED signalling, wider run-off areas. The comeback ran on 18–20 July 2025.",
      "Nach einer Pause seit 2020 kehrt der Große Preis der Tschechischen Republik zurück. Die Anlage wurde "
      "umfassend saniert — neuer Asphalt, LED-Signalanlagen, breitere Auslaufzonen. Die Neuauflage fand vom "
      "18.–20. Juli 2025 statt.")),
]

# ---------------------------------------------------------------- T4 vítězové
T4 = {
    "kicker": ("T4 · Síň vítězů", "T4 · Hall of winners", "T4 · Halle der Sieger"),
    "nadpis": ("Kdo tady vyhrával", "Who has won here", "Wer hier gewonnen hat"),
    "perex": ("Vítězové královské třídy Grand Prix České republiky na dnešním okruhu. Kompletní listinu včetně "
              "nižších kubatur vede pořadatel.",
              "Premier class winners of the Czech Republic Grand Prix on today's circuit. The promoter keeps the "
              "complete list, including the smaller classes.",
              "Sieger der Königsklasse des Großen Preises der Tschechischen Republik auf der heutigen Strecke. "
              "Die vollständige Liste inklusive der kleineren Klassen führt der Veranstalter."),
}

VITEZOVE = [
    ("2025", "Marc Márquez"), ("2020", "Brad Binder"), ("2019", "Marc Márquez"),
    ("2018", "Andrea Dovizioso"), ("2017", "Marc Márquez"), ("2016", "Cal Crutchlow"),
    ("2015", "Jorge Lorenzo"), ("2014", "Dani Pedrosa"), ("2013", "Marc Márquez"),
    ("2012", "Dani Pedrosa"), ("2011", "Casey Stoner"), ("2010", "Jorge Lorenzo"),
    ("2009", "Valentino Rossi"), ("2008", "Valentino Rossi"), ("2007", "Casey Stoner"),
    ("2006", "Loris Capirossi"), ("2005", "Valentino Rossi"), ("2004", "Sete Gibernau"),
    ("2003", "Valentino Rossi"), ("2002", "Max Biaggi"),
]

# ---------------------------------------------------------------- T5 hotel u trati
T5 = {
    "kicker": ("T5 · Hotel u trati", "T5 · The hotel at the track", "T5 · Das Hotel an der Strecke"),
    "nadpis": ("Metr od startovního roštu", "A metre from the grid", "Einen Meter vom Startrost"),
    "text": (
      ["<p>GRID Hotel je jediný hotel přímo v areálu. Do paddocku, k tribunám i k depu dojdete pěšky — bez "
       "přejíždění a bez hledání parkování. O závodních víkendech to znamená, že ráno vyjdete z pokoje a jste "
       "na místě dřív, než se otevřou brány pro veřejnost.</p>",
       "<p>Mimo závodní kalendář je okruh v provozu prakticky celoročně: jízdy veřejnosti, testy týmů, firemní "
       "akce, školy smyku a kurzy bezpečné jízdy.</p>"],
      ["<p>GRID Hotel is the only hotel inside the complex. The paddock, the grandstands and the pit lane are all "
       "within walking distance — no driving, no hunting for a parking space. On a race weekend that means you "
       "step out of your room and are trackside before the public gates open.</p>",
       "<p>Outside the racing calendar the circuit runs almost all year round: public track days, team testing, "
       "corporate events, skid schools and safe-driving courses.</p>"],
      ["<p>Das GRID Hotel ist das einzige Hotel direkt auf dem Gelände. Paddock, Tribünen und Boxengasse erreichen "
       "Sie zu Fuß — ohne Anfahrt und ohne Parkplatzsuche. An einem Rennwochenende heißt das: Sie treten aus dem "
       "Zimmer und stehen an der Strecke, bevor die Tore für das Publikum öffnen.</p>",
       "<p>Außerhalb des Rennkalenders ist die Strecke praktisch ganzjährig in Betrieb: Touristenfahrten, "
       "Teamtests, Firmenveranstaltungen, Schleuderkurse und Fahrsicherheitstrainings.</p>"],
    ),
}

ZDROJE = (
    "Zdroje faktů: oficiální web okruhu a pořadatele Grand Prix. Údaje se mohou měnit — pro aktuální program, "
    "vstupenky a provozní informace se řiďte oficiálními stránkami.",
    "Sources: the circuit's and the Grand Prix promoter's official websites. Details may change — for the current "
    "programme, tickets and visitor information, please refer to the official sites.",
    "Quellen: die offiziellen Websites der Rennstrecke und des Grand-Prix-Veranstalters. Angaben können sich "
    "ändern — für Programm, Tickets und Besucherinformationen gelten die offiziellen Seiten.",
)
