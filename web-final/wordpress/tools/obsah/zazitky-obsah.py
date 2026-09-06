# -*- coding: utf-8 -*-
"""Obsah zážitků u okruhu (CZ/EN/DE).

Fakta pocházejí z oficiálních stránek provozovatelů — automotodrombrno.cz
(jízdy veřejnosti, motokáry, motoškoly, minibiková akademie, jízdy v supersportu)
a polygonbrno.cz (škola smyku, zážitkové kurzy, odpočet bodů). Texty jsou psané
pro web hotelu, nejde o kopii oficiálních stránek; u každého zážitku vede odkaz
zpět na zdroj, kde se dá zážitek i objednat a kde jsou vždy aktuální termíny a ceny.

Prime zážitek MotoGP má vlastní soubor motogp-obsah.py.
"""

# ---------------------------------------------------------------- odkazy
AMD          = "https://www.automotodrombrno.cz/"
AUTO_JIZDY   = "https://www.automotodrombrno.cz/auto-jizdy-verejnosti/"
MOTO_JIZDY   = "https://www.automotodrombrno.cz/moto-jizdy-verejnosti/"
SEZNAM_JIZD  = "https://www.automotodrombrno.cz/aktivity-a-kurzy/seznam-jizd-verejnosti/"
MOTOKARY     = "https://www.automotodrombrno.cz/motokary/"
MOTOSKOLY    = "https://www.automotodrombrno.cz/aktivity-a-kurzy/seznam-motoskol/"
SILNICNI     = "https://www.automotodrombrno.cz/silnicni-motoskola/"
START        = "https://www.automotodrombrno.cz/motoskola-start/"
PRO_ZENY     = "https://www.automotodrombrno.cz/motoskola-pro-zeny/"
PO_ZADNIM    = "https://www.automotodrombrno.cz/motoskola-po-zadnim/"
POKROCILY    = "https://www.automotodrombrno.cz/pokrocily-trenink-s-instruktory/"
SHOWCARS_AMD = "https://www.automotodrombrno.cz/showcarscz/"
SHOWCARS     = "https://www.showcars.cz/"
MINIBIKE_AMD = "https://www.automotodrombrno.cz/aktivity-a-kurzy/minibike-akademie/"
MINIBIKE     = "https://www.minibikeakademiejrt.cz/"
PG           = "https://www.polygonbrno.cz/"
PG_SMYK      = "https://www.polygonbrno.cz/skola-smyku/"
PG_COMPACT   = "https://www.polygonbrno.cz/compact/"
PG_INTENSIV  = "https://www.polygonbrno.cz/intensiv/"
PG_INTENSIVP = "https://www.polygonbrno.cz/intensiv-plus/"
PG_ADVANCED  = "https://www.polygonbrno.cz/advanced/"
PG_DYNAMIC   = "https://www.polygonbrno.cz/dynamic/"
PG_DRIFT     = "https://www.polygonbrno.cz/drift-kurz/"
PG_GANGSTER  = "https://www.polygonbrno.cz/gangster-kurz/"
PG_BODY      = "https://www.polygonbrno.cz/odpocet-bodu-kurz/"
PG_POUKAZY   = "https://www.polygonbrno.cz/darkove-poukazy/"
PG_TERMINY   = "https://www.polygonbrno.cz/terminy/"

# interní odkazy podle jazyka
INTERNI = {
    "cs": {"rezervace": "/rezervace/", "ubytovani": "/ubytovani/", "okruh": "/masarykuv-okruh/",
           "sezona": "/sezona/", "zazitky": "/zazitky/", "kontakt": "/kontakt/"},
    "en": {"rezervace": "/en/reservation/", "ubytovani": "/en/accommodation/", "okruh": "/en/masaryk-circuit/",
           "sezona": "/en/season/", "zazitky": "/en/experiences/", "kontakt": "/en/contact/"},
    "de": {"rezervace": "/de/reservierung/", "ubytovani": "/de/unterkunft/", "okruh": "/de/masaryk-ring/",
           "sezona": "/de/saison/", "zazitky": "/de/erlebnisse/", "kontakt": "/de/kontakt-de/"},
}

# lokalizované popisky opakujících se prvků
KDE = {"cs": "Kde zážitek pořídíte", "en": "Where to arrange it", "de": "Wo Sie das Erlebnis buchen"}
POBYT = {
    "cs": ("Rezervace pobytu v GRID Hotelu", "ubytování přímo v areálu okruhu"),
    "en": ("Book a stay at GRID Hotel", "accommodation inside the circuit complex"),
    "de": ("Aufenthalt im GRID Hotel buchen", "Unterkunft direkt auf dem Gelände"),
}
ZDROJE = {
    "cs": "Zdroj informací: oficiální stránky provozovatele. Termíny, ceny i podmínky se mohou měnit — "
          "před objednáním je vždy ověřte na webu pořadatele.",
    "en": "Source: the operator's official website. Dates, prices and conditions may change — always check "
          "them with the organiser before booking.",
    "de": "Quelle: die offizielle Website des Betreibers. Termine, Preise und Bedingungen können sich ändern — "
          "prüfen Sie sie vor der Buchung immer beim Veranstalter.",
}


def _odkaz(url, popis, externi=True):
    cil = ' target="_blank" rel="noopener"' if externi else ""
    sipka = " ↗" if externi else " →"
    return f'<a href="{url}"{cil}>{popis}</a>{sipka}'


def popis(z, l):
    """Složí plný popis zážitku do matice dvou sloupců (na úzkém displeji pod sebe).

    Každá sekce s nadpisem je jedna buňka; odstavec se zdrojem zůstává pod
    mřížkou přes celou šířku. Stejné rozložení má i prime zážitek MotoGP.
    """
    bunky = []
    for nadpis, odstavce in z["sekce"][l]:
        bunky.append(f'<section class="rd-blok"><h2>{nadpis}</h2>' + "".join(odstavce) + "</section>")

    polozky = "".join(f"<li>{_odkaz(u, t)} — {p}</li>" for t, u, p in z["kde"][l])
    polozky += (f'<li><a href="{INTERNI[l]["rezervace"]}">{POBYT[l][0]}</a> — {POBYT[l][1]}</li>')
    bunky.append(f'<section class="rd-blok"><h2>{KDE[l]}</h2><ul>{polozky}</ul></section>')

    return ('<div class="rd-mrizka">' + "".join(bunky) + "</div>"
            + f'<p class="okruh-zdroje">{ZDROJE[l]}</p>')


ZAZITKY = []

# ================================================================ 4.1 Jízdy veřejnosti
ZAZITKY.append({
 "slug": "jizdy-verejnosti", "num": "4.1", "odkaz": SEZNAM_JIZD,
 "titul": {"cs": "Jízdy veřejnosti na okruhu",
           "en": "Public track days",
           "de": "Touristenfahrten auf der Rennstrecke"},
 "odkaz_text": {"cs": "Termíny jízd veřejnosti →", "en": "Track day dates →", "de": "Termine der Touristenfahrten →"},
 "cta": {"cs": "Vyjet na trať →", "en": "Get on track →", "de": "Auf die Strecke →"},
 "text": {
   "cs": "Vlastním autem nebo motorkou na Masarykův okruh. Pětadvacet minut na trati, kde se jede MotoGP — "
         "a hotel máte pár desítek metrů od registrace.",
   "en": "Your own car or bike on the Masaryk Circuit. Twenty-five minutes on the track where MotoGP races — "
         "with the hotel a few dozen metres from registration.",
   "de": "Mit dem eigenen Auto oder Motorrad auf den Masaryk-Ring. 25 Minuten auf der Strecke, auf der MotoGP "
         "gefahren wird — das Hotel liegt wenige Meter von der Anmeldung entfernt."},
 "perex": {
   "cs": "Jízdy veřejnosti jsou nejjednodušší způsob, jak se dostat na Masarykův okruh za volantem vlastního "
         "auta nebo v sedle vlastní motorky. Žádná licence, žádný závodní speciál — stačí běžné vozidlo "
         "s registrační značkou a platný řidičský průkaz.",
   "en": "Public track days are the simplest way to get onto the Masaryk Circuit in your own car or on your own "
         "motorcycle. No licence, no race car — an ordinary road-legal vehicle and a valid driving licence are enough.",
   "de": "Touristenfahrten sind der einfachste Weg, mit dem eigenen Auto oder Motorrad auf den Masaryk-Ring zu "
         "kommen. Keine Lizenz, kein Rennwagen — ein normales zugelassenes Fahrzeug und ein gültiger "
         "Führerschein genügen."},
 "parametry": {
   "cs": "Délka jízdy=25 minut|Kdy=zpravidla květen–srpen, 18:00–20:00|Kapacita=45 vozů či jezdců v jedné jízdě|"
         "Vozidlo=vlastní, s registrační značkou|Od hotelu=pěšky",
   "en": "Session=25 minutes|When=usually May–August, 18:00–20:00|Capacity=45 cars or riders per session|"
         "Vehicle=your own, road-registered|From the hotel=on foot",
   "de": "Dauer=25 Minuten|Wann=meist Mai–August, 18:00–20:00 Uhr|Kapazität=45 Autos oder Fahrer pro Turn|"
         "Fahrzeug=eigenes, zugelassen|Vom Hotel=zu Fuß"},
 "sekce": {
  "cs": [
   ("Jak to funguje", [
     "<p>Volné jízdy vypisuje Automotodrom Brno zvlášť pro auta a zvlášť pro motorky. Na trať se jezdí "
     "v pětadvacetiminutových blocích, obvykle od května do srpna v podvečer mezi 18:00 a 20:00; celodenní "
     "jízdy se vypisují jen výjimečně. Do jednoho bloku se vejde 45 vozů nebo jezdců.</p>",
     "<p>Vstoupit můžete jen s běžným vozidlem s přidělenou registrační značkou, které odpovídá technickému "
     "průkazu a homologaci — žádné úpravy mimo homologační list. Značka musí zůstat na voze po celou dobu jízdy. "
     "Potřebujete platný řidičský průkaz a před jízdou odevzdat vyplněnou smlouvu ve dvou vyhotoveních.</p>",
     "<p>U aut smí jet i spolujezdec, ale musí být zletilý — u nezletilých je potřeba předem doložit písemný "
     "souhlas zákonného zástupce. Na motorce spolujezdec povolený není.</p>"]),
   ("Průběh večera", [
     "<p>Brány areálu se otevírají v 16:30, registrace a výdej identifikačních nálepek začíná v 17:00 "
     "na pokladně uvnitř areálu. Nálepku je nutné vyzvednout nejpozději 15 minut před startem jízdy — "
     "jinak místo propadá a nabídne se dalšímu zájemci.</p>",
     "<p>Účastníkům je vyhrazené parkoviště přímo před restaurací GRID, tedy u našeho hotelu. Kdo u nás spí, "
     "má z pokoje k registraci pár desítek metrů a po jízdě nemusí nikam odjíždět.</p>",
     "<p>Na trati platí pokyny traťových komisařů a vlajková signalizace, provoz sleduje dispečink. "
     "Doporučená maximální rychlost je 90 km/h, pohyb v paddocku a u boxové zdi je pro účastníky zakázaný.</p>"]),
   ("Kdy to má smysl spojit s pobytem", [
     "<p>Podvečerní jízda končí ve 20:00 — po ní se dá v klidu povečeřet v hotelové restauraci a rozebrat "
     "kola u baru místo hodinové cesty domů. Termíny se vyprodávají online a kapacita bývá vyčerpaná, "
     "proto se vyplatí koupit jízdu i pokoj současně.</p>",
     "<p>Jízdy se prodávají i jako dárkový poukaz bez termínu; poukaz má omezenou platnost, do které je nutné "
     "termín zaregistrovat, ne odjet. Držitel poukazu nemá místo rezervované automaticky.</p>"]),
  ],
  "en": [
   ("How it works", [
     "<p>Brno Circuit runs separate public sessions for cars and for motorcycles. You go out in 25-minute "
     "blocks, usually from May to August in the early evening between 18:00 and 20:00; full-day sessions are "
     "announced only occasionally. One block takes 45 cars or riders.</p>",
     "<p>Only ordinary road-registered vehicles are allowed, matching their registration documents and type "
     "approval — no modifications beyond the homologation sheet. The number plate stays on the vehicle "
     "throughout. You need a valid driving licence and a completed contract in two copies before going out.</p>",
     "<p>Cars may carry a passenger, but they must be an adult — for minors, written consent from a legal "
     "guardian has to be submitted in advance. No pillion is allowed on a motorcycle.</p>"]),
   ("How the evening runs", [
     "<p>The gates open at 16:30, registration and the handout of identification stickers start at 17:00 at the "
     "box office inside the complex. The sticker must be collected at least 15 minutes before your session — "
     "otherwise the slot is offered to somebody else.</p>",
     "<p>Participants have a dedicated car park right in front of the GRID restaurant, at our hotel. If you stay "
     "with us, it is a few dozen metres from your room to registration — and no drive home afterwards.</p>",
     "<p>On track you follow the marshals and the flag signals, and the session is monitored from race control. "
     "The recommended maximum speed is 90 km/h; participants may not enter the paddock or the pit wall area.</p>"]),
   ("Why combine it with a stay", [
     "<p>An evening session ends at 20:00 — after which you can have a proper dinner in the hotel restaurant and "
     "talk laps at the bar instead of driving home. Sessions sell out online, so it pays to book the track time "
     "and the room together.</p>",
     "<p>Sessions are also sold as an open-dated gift voucher; the voucher has a validity period within which "
     "you must register a date, not necessarily ride it. Voucher holders do not have a guaranteed slot.</p>"]),
  ],
  "de": [
   ("So läuft es ab", [
     "<p>Das Automotodrom Brünn schreibt Touristenfahrten getrennt für Autos und Motorräder aus. Gefahren wird "
     "in 25-Minuten-Blöcken, meist von Mai bis August am frühen Abend zwischen 18:00 und 20:00 Uhr; "
     "Ganztagesfahrten gibt es nur ausnahmsweise. In einen Block passen 45 Autos oder Fahrer.</p>",
     "<p>Zugelassen sind nur normale Straßenfahrzeuge mit Kennzeichen, die den Fahrzeugpapieren und der "
     "Typgenehmigung entsprechen — keine Umbauten außerhalb des Homologationsblatts. Das Kennzeichen bleibt "
     "während der gesamten Fahrt am Fahrzeug. Nötig sind ein gültiger Führerschein und ein ausgefüllter "
     "Vertrag in zwei Ausfertigungen.</p>",
     "<p>Im Auto darf ein Beifahrer mitfahren, muss aber volljährig sein — bei Minderjährigen ist vorab die "
     "schriftliche Zustimmung des gesetzlichen Vertreters vorzulegen. Auf dem Motorrad ist kein Sozius erlaubt.</p>"]),
   ("Der Ablauf des Abends", [
     "<p>Die Tore öffnen um 16:30 Uhr, Anmeldung und Ausgabe der Identifikationsaufkleber beginnen um 17:00 Uhr "
     "an der Kasse auf dem Gelände. Der Aufkleber muss spätestens 15 Minuten vor dem Start abgeholt werden — "
     "sonst verfällt der Platz und wird weitergegeben.</p>",
     "<p>Für Teilnehmer ist der Parkplatz direkt vor dem Restaurant GRID reserviert, also an unserem Hotel. "
     "Wer bei uns übernachtet, hat vom Zimmer nur wenige Meter zur Anmeldung — und muss danach nirgendwohin "
     "fahren.</p>",
     "<p>Auf der Strecke gelten die Anweisungen der Streckenposten und die Flaggensignale, der Betrieb wird von "
     "der Rennleitung überwacht. Empfohlene Höchstgeschwindigkeit sind 90 km/h; Paddock und Boxenmauer sind für "
     "Teilnehmer gesperrt.</p>"]),
   ("Warum mit einem Aufenthalt verbinden", [
     "<p>Eine Abendfahrt endet um 20:00 Uhr — danach lässt sich in Ruhe im Hotelrestaurant essen und an der Bar "
     "über die Runden reden, statt nach Hause zu fahren. Die Termine werden online verkauft und sind oft "
     "ausgebucht, deshalb lohnt es sich, Fahrt und Zimmer zusammen zu buchen.</p>",
     "<p>Fahrten gibt es auch als Gutschein ohne Termin; der Gutschein hat eine Gültigkeit, in der ein Termin "
     "registriert — nicht unbedingt gefahren — werden muss. Gutscheininhaber haben keinen garantierten Platz.</p>"]),
  ]},
 "kde": {
  "cs": [("AUTO jízdy veřejnosti", AUTO_JIZDY, "termíny, ceny a podmínky pro auta"),
         ("MOTO jízdy veřejnosti", MOTO_JIZDY, "termíny, ceny a podmínky pro motorky"),
         ("Přehled jízd veřejnosti", SEZNAM_JIZD, "obě varianty na jednom místě")],
  "en": [("AUTO public track days", AUTO_JIZDY, "dates, prices and rules for cars"),
         ("MOTO public track days", MOTO_JIZDY, "dates, prices and rules for motorcycles"),
         ("Track day overview", SEZNAM_JIZD, "both options in one place")],
  "de": [("AUTO-Touristenfahrten", AUTO_JIZDY, "Termine, Preise und Bedingungen für Autos"),
         ("MOTO-Touristenfahrten", MOTO_JIZDY, "Termine, Preise und Bedingungen für Motorräder"),
         ("Übersicht der Touristenfahrten", SEZNAM_JIZD, "beide Varianten auf einer Seite")]},
})

# ================================================================ 4.2 Motokáry & pitbike
ZAZITKY.append({
 "slug": "motokary-pitbike", "num": "4.2", "odkaz": MOTOKARY,
 "titul": {"cs": "Motokáry & pitbike", "en": "Go-karts & pit bikes", "de": "Go-Karts & Pitbikes"},
 "odkaz_text": {"cs": "Motokárová dráha →", "en": "Kart track →", "de": "Kartbahn →"},
 "cta": {"cs": "Zajet si kolo →", "en": "Do a few laps →", "de": "Ein paar Runden →"},
 "text": {
   "cs": "Ostrá motokára na 423 metrů dlouhé dráze pár desítek metrů od velkého okruhu. Bez rezervace, "
         "bez čekání na termín — ideální program na odpoledne mezi jinými zážitky.",
   "en": "A proper kart on a 423-metre track a stone's throw from the Grand Prix circuit. No booking, no waiting "
         "for a date — the ideal filler between other experiences.",
   "de": "Ein echtes Kart auf einer 423 Meter langen Bahn wenige Meter von der großen Strecke entfernt. Ohne "
         "Reservierung, ohne Wartezeit — das ideale Programm für den Nachmittag."},
 "perex": {
   "cs": "Motokárová dráha leží přímo v areálu Masarykova okruhu, pár desítek metrů od velké trati. Nepotřebujete "
         "rezervaci ani zkušenosti — přijdete, nasadíte helmu a jedete. Je to nejrychlejší způsob, jak si "
         "u okruhu zazávodit s rodinou nebo s kolegy.",
   "en": "The kart track sits inside the Masaryk Circuit complex, a few dozen metres from the main track. "
         "No booking and no experience needed — turn up, put on a helmet and go. It is the quickest way to race "
         "your family or your colleagues at the circuit.",
   "de": "Die Kartbahn liegt direkt auf dem Gelände des Masaryk-Rings, wenige Meter von der großen Strecke. "
         "Weder Reservierung noch Erfahrung nötig — kommen, Helm aufsetzen, losfahren. Der schnellste Weg, "
         "an der Rennstrecke mit Familie oder Kollegen zu fahren."},
 "parametry": {
   "cs": "Dráha=423 metrů|Motokáry=270 ccm|Věk=od 12 let a 160 cm|Rezervace=v otevírací době není potřeba|"
         "Skupiny=pronájem dráhy mimo otevírací dobu",
   "en": "Track=423 metres|Karts=270 cc|Age=from 12 years and 160 cm|Booking=not needed in opening hours|"
         "Groups=track hire outside opening hours",
   "de": "Bahn=423 Meter|Karts=270 ccm|Alter=ab 12 Jahren und 160 cm|Reservierung=während der Öffnungszeiten "
         "nicht nötig|Gruppen=Bahnmiete außerhalb der Öffnungszeiten"},
 "sekce": {
  "cs": [
   ("Dráha a stroje", [
     "<p>Dráha měří 423 metrů a je dost široká na to, aby se dalo předjíždět a taktizovat — nejde o okruh, kde "
     "se jen jezdí za sebou. K dispozici jsou motokáry 270 ccm, které dokážou překvapit i ty, kdo si myslí, "
     "že motokára je pouťová atrakce.</p>",
     "<p>Součástí areálu je prostorné parkoviště, krytý altán, automat s občerstvením a toalety. Motokáry "
     "i helmy se po každé jízdě dezinfikují; pod helmu je potřeba kukla, kterou si můžete koupit na místě "
     "nebo přinést vlastní.</p>"]),
   ("Pro koho to je", [
     "<p>Na dráhu smí děti od 12 let a od 160 cm výšky. Pro mladší jezdce je v areálu okruhu určená spíš "
     "minibiková akademie. Jízda v motokáře je fyzicky náročnější, než vypadá — počítejte s tím, že po pár "
     "kolech budete mít co dělat s rukama i krkem.</p>",
     "<p>Jednotlivé jízdy se nerezervují: v otevírací době stačí přijít. Rezervace se přijímá jen pro "
     "soukromé akce, tedy pronájem dráhy včetně strojů na hodinu a více — to je varianta pro firemní "
     "výjezdy, oslavy a rozlučky.</p>"]),
   ("Jak to spojit s pobytem", [
     "<p>Motokáry jsou ideální doplněk k programu, který stojí na termínu: k jízdám veřejnosti, kurzu na "
     "Polygonu nebo závodnímu víkendu. Odjezdíte je, kdy se vám to hodí, a nezdrží vás cestování — dráha "
     "je v témže areálu jako hotel.</p>",
     "<p>V době větších akcí a závodů může být dráha uzavřená, proto se vyplatí ověřit si dostupnost "
     "v kalendáři motokárové dráhy nebo na naší recepci.</p>"]),
  ],
  "en": [
   ("The track and the karts", [
     "<p>The track is 423 metres long and wide enough for overtaking and tactics — it is not a follow-the-leader "
     "layout. The karts are 270 cc machines that surprise most people who think of karting as a fairground ride.</p>",
     "<p>The site has a large car park, a covered shelter, a refreshment machine and toilets. Karts and helmets "
     "are disinfected after every run; a balaclava under the helmet is required and can be bought on site or "
     "brought with you.</p>"]),
   ("Who it is for", [
     "<p>Children are allowed from 12 years of age and 160 cm in height. Younger riders are better served by the "
     "minibike academy at the circuit. Karting is physically harder than it looks — expect your arms and neck to "
     "complain after a few laps.</p>",
     "<p>Individual runs are not booked: during opening hours you simply turn up. Reservations are only taken "
     "for private events — hiring the track and the karts for an hour or more, which is the option for company "
     "outings, birthdays and stag or hen parties.</p>"]),
   ("Combining it with a stay", [
     "<p>Karting is the perfect complement to anything tied to a fixed date: a public track day, a course at "
     "the Polygon or a race weekend. You do it whenever it suits you and lose no time travelling — the track "
     "is in the same complex as the hotel.</p>",
     "<p>During bigger events and races the kart track can be closed, so it is worth checking availability in "
     "the kart track calendar or with our reception.</p>"]),
  ],
  "de": [
   ("Bahn und Karts", [
     "<p>Die Bahn ist 423 Meter lang und breit genug für Überholmanöver und Taktik — kein Hintereinanderherfahren. "
     "Zur Verfügung stehen Karts mit 270 ccm, die auch jene überraschen, die Karts für eine Jahrmarktattraktion "
     "halten.</p>",
     "<p>Zum Areal gehören ein großer Parkplatz, ein überdachter Pavillon, ein Verpflegungsautomat und Toiletten. "
     "Karts und Helme werden nach jeder Fahrt desinfiziert; unter dem Helm ist eine Sturmhaube nötig, die vor Ort "
     "gekauft oder mitgebracht werden kann.</p>"]),
   ("Für wen es geeignet ist", [
     "<p>Auf die Bahn dürfen Kinder ab 12 Jahren und 160 cm Körpergröße. Für jüngere Fahrer ist eher die "
     "Minibike-Akademie auf dem Gelände gedacht. Kartfahren ist körperlich anstrengender, als es aussieht — "
     "nach ein paar Runden melden sich Arme und Nacken.</p>",
     "<p>Einzelne Fahrten werden nicht reserviert: während der Öffnungszeiten kommt man einfach vorbei. "
     "Reservierungen gibt es nur für private Veranstaltungen, also die Miete der Bahn samt Karts ab einer "
     "Stunde — die Variante für Firmenausflüge und Feiern.</p>"]),
   ("Wie es zum Aufenthalt passt", [
     "<p>Karts sind die ideale Ergänzung zu allem, was an einen Termin gebunden ist: Touristenfahrten, Kurs auf "
     "dem Polygon oder Rennwochenende. Sie fahren, wann es Ihnen passt, und verlieren keine Zeit mit Anfahrt — "
     "die Bahn liegt auf demselben Gelände wie das Hotel.</p>",
     "<p>Bei größeren Veranstaltungen und Rennen kann die Bahn geschlossen sein; prüfen Sie die Verfügbarkeit "
     "im Kalender der Kartbahn oder an unserer Rezeption.</p>"]),
  ]},
 "kde": {
  "cs": [("Motokárová dráha Masarykova okruhu", MOTOKARY, "otevírací doba, ceny a kalendář dráhy"),
         ("Přehled motokár a pitbike", "https://www.automotodrombrno.cz/aktivity-a-kurzy/seznam-motokar-a-pitbike/", "nabídka aktivit na dráze")],
  "en": [("Masaryk Circuit kart track", MOTOKARY, "opening hours, prices and the track calendar"),
         ("Karts and pit bikes overview", "https://www.automotodrombrno.cz/aktivity-a-kurzy/seznam-motokar-a-pitbike/", "what the track offers")],
  "de": [("Kartbahn des Masaryk-Rings", MOTOKARY, "Öffnungszeiten, Preise und Bahnkalender"),
         ("Übersicht Karts und Pitbikes", "https://www.automotodrombrno.cz/aktivity-a-kurzy/seznam-motokar-a-pitbike/", "Angebot auf der Bahn")]},
})

# ================================================================ 4.3 Motoškola
ZAZITKY.append({
 "slug": "motoskola", "num": "4.3", "odkaz": MOTOSKOLY,
 "titul": {"cs": "Motoškola na Masarykově okruhu", "en": "Riding school at the Masaryk Circuit",
           "de": "Motorradschule am Masaryk-Ring"},
 "odkaz_text": {"cs": "Vybrat motoškolu →", "en": "Choose a course →", "de": "Kurs auswählen →"},
 "cta": {"cs": "Vybrat kurz →", "en": "Choose a course →", "de": "Kurs wählen →"},
 "text": {
   "cs": "Šest kurzů od začátečníků po jízdu po zadním. Trénink na polygonu a pak 5,4 km asfaltu, který "
         "chválí i jezdci MotoGP — s instruktory, kteří vás z toho nenechají vyjít stejné.",
   "en": "Six courses, from beginners to wheelie training. Practice on the skid pad, then 5.4 km of asphalt "
         "praised by MotoGP riders — with instructors who send you home a better rider.",
   "de": "Sechs Kurse, vom Anfänger bis zum Wheelie. Training auf dem Polygon, danach 5,4 km Asphalt, den "
         "selbst MotoGP-Fahrer loben — mit Instruktoren, die etwas verändern."},
 "perex": {
   "cs": "Motoškoly Automotodromu Brno spojují trénink na uzavřené ploše Polygonu s jízdou na velkém okruhu. "
         "Instruktoři jezdí tisíce kilometrů ročně a ve skupinách je vždy tolik lidí, aby se dostalo na "
         "individuální zpětnou vazbu.",
   "en": "The riding schools at Brno Circuit combine training on the closed Polygon area with laps on the Grand "
         "Prix track. The instructors ride thousands of kilometres a year, and group sizes are kept small enough "
         "for individual feedback.",
   "de": "Die Motorradschulen des Automotodrom Brünn verbinden Training auf dem abgesperrten Polygon-Gelände mit "
         "Runden auf der großen Strecke. Die Instruktoren fahren jährlich Tausende Kilometer, und die Gruppen "
         "sind klein genug für individuelles Feedback."},
 "parametry": {
   "cs": "Kurzů=6 úrovní a zaměření|Kde=Polygon Brno + Masarykův okruh|Trať=5 403 m|"
         "Pro koho=od nováčků po pokročilé|Motorka=vlastní (u kurzu po zadním zapůjčená)",
   "en": "Courses=6 levels and formats|Where=Polygon Brno + Masaryk Circuit|Track=5,403 m|"
         "For whom=from newcomers to advanced|Bike=your own (loan bikes for the wheelie course)",
   "de": "Kurse=6 Stufen und Formate|Wo=Polygon Brünn + Masaryk-Ring|Strecke=5.403 m|"
         "Für wen=vom Neuling bis Fortgeschrittenen|Motorrad=eigenes (beim Wheelie-Kurs geliehen)"},
 "sekce": {
  "cs": [
   ("Které kurzy si můžete vybrat", [
     "<p><strong>Silniční motoškola</strong> je nejprodávanější kurz: nejdřív pořádný trénink na moderní ploše "
     "Polygonu Brno, pak 5,4 kilometru nejlepšího asfaltu v republice. Instruktor vám ukáže ideální stopu přímo "
     "na dráze a napraví návyky, o kterých často ani nevíte.</p>",
     "<p><strong>Motoškola START</strong> je pro nováčky i pro ty, kdo se do sedla vracejí po pauze. Nezáleží na "
     "stroji — silniční, cestovní enduro, chopper i skútr. <strong>Motoškola pro ženy</strong> vychází ze stejné "
     "náplně, jen v čistě dámské skupině.</p>",
     "<p><strong>Motoškola po zadním</strong> učí jízdu na zadním kole na nových motorkách Yamaha MT-07 "
     "a trenažérech — vaše motorka zůstane zaparkovaná. Kurz je jednodenní, ve skupinách po třech účastnících "
     "na jednoho instruktora. <strong>LEVEL PRO</strong> je jeho pokročilá nadstavba.</p>",
     "<p><strong>Pokročilý trénink s instruktory</strong> je dvouhodinový večerní blok pro absolventy Silniční "
     "motoškoly: pět patnáctiminutových jízd na závodní dráze, maximálně pět účastníků na instruktora.</p>"]),
   ("Proč zrovna tady", [
     "<p>Masarykův okruh má prvotřídní asfalt a moderní bezpečnostní prvky — je to místo, kde se dají hledat "
     "limity, aniž by se hledaly v provozu. Polygon Brno hned vedle umožňuje nejdřív nacvičit brzdění a vyhýbání "
     "v bezpečí a teprve pak vyjet na trať.</p>",
     "<p>Kurzy vedou profesionálové, kteří učí celý rok, a skupiny se drží malé záměrně. Cílem není projet "
     "co nejvíc kol, ale odvézt si návyky, které fungují i na cestě domů.</p>"]),
   ("Motoškola a pobyt", [
     "<p>Většina kurzů zabere celý den nebo končí večer. Ubytování v areálu znamená, že po dni v kombinéze "
     "nemusíte nikam jet — ráno vyjdete z pokoje rovnou na sraz a večer si dáte večeři v hotelové restauraci.</p>",
     "<p>Termíny se vypisují dopředu a nejžádanější kurzy se plní rychle. Když si zamluvíte kurz, ozvěte se nám "
     "kvůli pokoji hned — na závodní víkendy a v hlavní sezóně bývá plno.</p>"]),
  ],
  "en": [
   ("The courses on offer", [
     "<p><strong>The road riding school</strong> is the best seller: first a solid training block on the modern "
     "Polygon Brno area, then 5.4 kilometres of the best asphalt in the country. The instructor shows you the "
     "racing line on the track itself and fixes habits you did not know you had.</p>",
     "<p><strong>Riding school START</strong> is for newcomers and for riders returning after a break. The bike "
     "does not matter — road, adventure, cruiser or scooter. <strong>The women's riding school</strong> follows "
     "the same programme in a women-only group.</p>",
     "<p><strong>The wheelie school</strong> teaches riding on the rear wheel on new Yamaha MT-07 machines and "
     "on trainers — your own bike stays parked. It is a one-day course with three participants per instructor. "
     "<strong>LEVEL PRO</strong> is the advanced follow-up.</p>",
     "<p><strong>Advanced training with instructors</strong> is a two-hour evening block for graduates of the "
     "road riding school: five fifteen-minute track sessions, a maximum of five riders per instructor.</p>"]),
   ("Why here", [
     "<p>The Masaryk Circuit has first-class asphalt and modern safety features — a place to explore limits "
     "without exploring them in traffic. Polygon Brno next door lets you practise braking and avoidance safely "
     "first, and only then go out on track.</p>",
     "<p>The courses are led by professionals who teach all year, and groups are deliberately kept small. The "
     "point is not to bank as many laps as possible, but to take home habits that work on the ride back.</p>"]),
   ("The course and your stay", [
     "<p>Most courses take a whole day or finish in the evening. Staying inside the complex means no drive after "
     "a day in leathers — you walk from your room to the briefing and have dinner in the hotel restaurant "
     "afterwards.</p>",
     "<p>Dates are published in advance and the popular courses fill quickly. Once your course is confirmed, "
     "talk to us about the room straight away — race weekends and the high season sell out.</p>"]),
  ],
  "de": [
   ("Diese Kurse gibt es", [
     "<p><strong>Die Straßen-Motorradschule</strong> ist der meistverkaufte Kurs: zuerst gründliches Training auf "
     "dem modernen Gelände des Polygon Brünn, dann 5,4 Kilometer des besten Asphalts des Landes. Der Instruktor "
     "zeigt die Ideallinie direkt auf der Strecke und korrigiert Gewohnheiten, von denen man oft nichts weiß.</p>",
     "<p><strong>Motorradschule START</strong> ist für Neulinge und Rückkehrer nach einer Pause. Die Maschine "
     "spielt keine Rolle — Straße, Reiseenduro, Chopper oder Roller. <strong>Die Motorradschule für Frauen</strong> "
     "hat dasselbe Programm, nur in reiner Frauengruppe.</p>",
     "<p><strong>Die Wheelie-Schule</strong> lehrt das Fahren auf dem Hinterrad auf neuen Yamaha MT-07 und auf "
     "Trainern — das eigene Motorrad bleibt stehen. Der Kurs dauert einen Tag, mit drei Teilnehmern pro "
     "Instruktor. <strong>LEVEL PRO</strong> ist die Fortsetzung für Fortgeschrittene.</p>",
     "<p><strong>Fortgeschrittenentraining mit Instruktoren</strong> ist ein zweistündiger Abendblock für "
     "Absolventen der Straßen-Motorradschule: fünf Fahrten à 15 Minuten auf der Rennstrecke, maximal fünf "
     "Teilnehmer pro Instruktor.</p>"]),
   ("Warum gerade hier", [
     "<p>Der Masaryk-Ring hat erstklassigen Asphalt und moderne Sicherheitsanlagen — ein Ort, an dem man Grenzen "
     "ausloten kann, ohne es im Straßenverkehr zu tun. Das Polygon Brünn nebenan erlaubt es, Bremsen und "
     "Ausweichen zuerst sicher zu üben und erst dann auf die Strecke zu gehen.</p>",
     "<p>Die Kurse leiten Profis, die das ganze Jahr unterrichten, und die Gruppen bleiben bewusst klein. Es geht "
     "nicht um möglichst viele Runden, sondern um Gewohnheiten, die auch auf der Heimfahrt funktionieren.</p>"]),
   ("Kurs und Aufenthalt", [
     "<p>Die meisten Kurse dauern einen ganzen Tag oder enden abends. Eine Unterkunft auf dem Gelände heißt: nach "
     "einem Tag in der Kombi keine Heimfahrt — morgens vom Zimmer direkt zum Briefing, abends Essen im "
     "Hotelrestaurant.</p>",
     "<p>Die Termine werden im Voraus veröffentlicht, die begehrten Kurse sind schnell voll. Wenn Ihr Kurs steht, "
     "sprechen Sie uns gleich wegen des Zimmers an — an Rennwochenenden und in der Hauptsaison ist es eng.</p>"]),
  ]},
 "kde": {
  "cs": [("Přehled motoškol", MOTOSKOLY, "všech šest kurzů a jejich termíny"),
         ("Silniční motoškola", SILNICNI, "nejprodávanější kurz s jízdou na okruhu"),
         ("Motoškola START", START, "pro nováčky a návrat do sedla"),
         ("Motoškola pro ženy", PRO_ZENY, "stejná náplň v dámské skupině"),
         ("Motoškola po zadním", PO_ZADNIM, "jízda po zadním na zapůjčených Yamahách"),
         ("Pokročilý trénink s instruktory", POKROCILY, "večerní bloky pro absolventy")],
  "en": [("All riding schools", MOTOSKOLY, "the six courses and their dates"),
         ("Road riding school", SILNICNI, "the best seller, with track laps"),
         ("Riding school START", START, "for newcomers and returning riders"),
         ("Women's riding school", PRO_ZENY, "same programme, women-only group"),
         ("Wheelie school", PO_ZADNIM, "rear-wheel riding on loan Yamahas"),
         ("Advanced training with instructors", POKROCILY, "evening track blocks for graduates")],
  "de": [("Übersicht der Motorradschulen", MOTOSKOLY, "alle sechs Kurse und ihre Termine"),
         ("Straßen-Motorradschule", SILNICNI, "der meistverkaufte Kurs mit Streckenrunden"),
         ("Motorradschule START", START, "für Neulinge und Rückkehrer"),
         ("Motorradschule für Frauen", PRO_ZENY, "gleiches Programm in reiner Frauengruppe"),
         ("Wheelie-Schule", PO_ZADNIM, "Hinterradfahren auf geliehenen Yamahas"),
         ("Fortgeschrittenentraining", POKROCILY, "Abendblöcke für Absolventen")]},
})

# ================================================================ 4.4 Jízda v supersportu
ZAZITKY.append({
 "slug": "jizda-v-supersportu", "num": "4.4", "odkaz": SHOWCARS_AMD,
 "titul": {"cs": "Jízda v supersportu", "en": "Supercar driving", "de": "Fahrt im Supersportwagen"},
 "odkaz_text": {"cs": "Vybrat vůz →", "en": "Choose a car →", "de": "Fahrzeug wählen →"},
 "cta": {"cs": "Usednout za volant →", "en": "Take the wheel →", "de": "Ans Steuer →"},
 "text": {
   "cs": "Ferrari, Lamborghini nebo Porsche na závodní dráze Masarykova okruhu. Zážitek, který se dá koupit "
         "i jako poukaz — a termín si vyberete v rezervačním systému.",
   "en": "Ferrari, Lamborghini or Porsche on the Masaryk Circuit race track. Available as a gift voucher, "
         "with the date picked later in the booking system.",
   "de": "Ferrari, Lamborghini oder Porsche auf der Rennstrecke des Masaryk-Rings. Auch als Gutschein — den "
         "Termin wählen Sie später im Buchungssystem."},
 "perex": {
   "cs": "Automotodrom Brno nabízí spolu s agenturou Showcars jízdy v nejmodernějších supersportech přímo na "
         "závodní dráze. Nepotřebujete vlastní vůz ani zkušenost s okruhem — stačí řidičský průkaz a chuť "
         "vyzkoušet, co takové auto umí tam, kde se to smí.",
   "en": "Together with the Showcars agency, Brno Circuit offers drives in current supercars on the race track "
         "itself. You need neither your own car nor track experience — just a driving licence and the urge to "
         "find out what such a car does where it is allowed to.",
   "de": "Gemeinsam mit der Agentur Showcars bietet das Automotodrom Brünn Fahrten in modernen Supersportwagen "
         "direkt auf der Rennstrecke an. Weder ein eigenes Auto noch Streckenerfahrung sind nötig — nur ein "
         "Führerschein und die Lust herauszufinden, was so ein Wagen kann, wo er es darf."},
 "parametry": {
   "cs": "Vozy=Ferrari · Lamborghini · Porsche|Kde=závodní dráha Masarykova okruhu|Forma=balíčky a dárkové "
         "poukazy|Termín=volíte v rezervačním systému|Od hotelu=pěšky",
   "en": "Cars=Ferrari · Lamborghini · Porsche|Where=the Masaryk Circuit race track|Format=packages and gift "
         "vouchers|Date=chosen in the booking system|From the hotel=on foot",
   "de": "Fahrzeuge=Ferrari · Lamborghini · Porsche|Wo=Rennstrecke des Masaryk-Rings|Form=Pakete und Gutscheine|"
         "Termin=im Buchungssystem wählbar|Vom Hotel=zu Fuß"},
 "sekce": {
  "cs": [
   ("Co si vyberete", [
     "<p>Nabídka stojí na vozech, které se na běžné silnici nedají využít ani z poloviny — Ferrari, Lamborghini "
     "a Porsche. Na okruhu jde o něco jiného než o krátkou projížďku městem: máte prostor zjistit, jak takové "
     "auto brzdí, jak drží v zatáčce a co dělá při plném plynu na rovince dlouhé přes šest set metrů.</p>",
     "<p>Konkrétní vozy, délku jízd i ceny drží agentura Showcars na svém webu, kde se také vybírá balíček. "
     "Objednávku zaplatíte online a do e-mailu vám přijde poukaz s číslem kupónu.</p>"]),
   ("Jak to probíhá", [
     "<p>Postup je jednoduchý: na webu Showcars si vyberete balíček, objednáte a zaplatíte, dostanete poukaz "
     "s kódem a v rezervačním systému si zvolíte termín. Pak už stačí dorazit na Masarykův okruh.</p>",
     "<p>Protože se jezdí na závodní dráze, termíny se řídí provozem okruhu — v době závodů a velkých akcí "
     "se nejezdí. Když plánujete pobyt kolem konkrétního data, ověřte si nejdřív dostupnost termínu.</p>"]),
   ("Dobrý dárek i dobrý druhý den", [
     "<p>Poukaz bez pevného termínu je praktický dárek: obdarovaný si vybere datum sám. A protože jízda "
     "netrvá celý den, dá se dobře spojit s dalším programem v areálu — třeba dopoledne supersport, "
     "odpoledne motokáry a večer večeře s výhledem na trať.</p>",
     "<p>Hosté hotelu mají výhodu, že po jízdě nikam nespěchají. Parkování je zdarma přímo v areálu "
     "a od pokoje k dráze je to pár minut pěšky.</p>"]),
  ],
  "en": [
   ("What you choose from", [
     "<p>The fleet is built around cars you cannot use to half their potential on a public road — Ferrari, "
     "Lamborghini and Porsche. On a circuit it is a different proposition from a short drive through town: you "
     "get room to learn how the car brakes, how it holds a corner and what it does flat out on a straight over "
     "six hundred metres long.</p>",
     "<p>The exact cars, session lengths and prices are kept on the Showcars website, where you also pick your "
     "package. You pay online and receive a voucher with a coupon number by e-mail.</p>"]),
   ("How it works", [
     "<p>The process is simple: choose a package on the Showcars site, order and pay, receive a voucher with a "
     "code, then select your date in the booking system. After that all you have to do is turn up at the "
     "Masaryk Circuit.</p>",
     "<p>Because the driving takes place on the race track, dates follow circuit operations — nothing runs "
     "during races and major events. If you are planning a stay around a specific date, check availability "
     "first.</p>"]),
   ("A good gift and a good second day", [
     "<p>An open-dated voucher makes a practical present: the recipient picks the date. And because the drive "
     "does not take a whole day, it combines well with the rest of the complex — a supercar in the morning, "
     "karts in the afternoon, dinner overlooking the track in the evening.</p>",
     "<p>Hotel guests have the advantage of not having to rush off afterwards. Parking inside the complex is "
     "free and the track is a few minutes' walk from your room.</p>"]),
  ],
  "de": [
   ("Die Auswahl", [
     "<p>Das Angebot besteht aus Autos, die sich auf normaler Straße nicht einmal zur Hälfte nutzen lassen — "
     "Ferrari, Lamborghini und Porsche. Auf der Rennstrecke ist das etwas anderes als eine kurze Stadtrunde: "
     "Sie haben Raum herauszufinden, wie so ein Wagen bremst, wie er in der Kurve liegt und was er bei Vollgas "
     "auf einer über sechshundert Meter langen Geraden macht.</p>",
     "<p>Die konkreten Fahrzeuge, Fahrtdauern und Preise führt die Agentur Showcars auf ihrer Website, dort "
     "wählen Sie auch das Paket. Bezahlt wird online, per E-Mail kommt ein Gutschein mit Coupon-Nummer.</p>"]),
   ("Der Ablauf", [
     "<p>Es ist unkompliziert: auf der Showcars-Website ein Paket wählen, bestellen und bezahlen, den Gutschein "
     "mit Code erhalten und im Buchungssystem den Termin wählen. Danach müssen Sie nur noch zum Masaryk-Ring "
     "kommen.</p>",
     "<p>Da auf der Rennstrecke gefahren wird, richten sich die Termine nach dem Streckenbetrieb — bei Rennen "
     "und Großveranstaltungen wird nicht gefahren. Wenn Sie den Aufenthalt um ein bestimmtes Datum planen, "
     "prüfen Sie zuerst die Verfügbarkeit.</p>"]),
   ("Gutes Geschenk, guter zweiter Tag", [
     "<p>Ein Gutschein ohne festen Termin ist ein praktisches Geschenk: den Tag wählt der Beschenkte selbst. Und "
     "weil die Fahrt keinen ganzen Tag dauert, lässt sie sich gut mit dem übrigen Programm verbinden — vormittags "
     "Supersportwagen, nachmittags Karts, abends Essen mit Blick auf die Strecke.</p>",
     "<p>Hotelgäste müssen danach nirgendwohin hetzen. Parken auf dem Gelände ist kostenlos, und vom Zimmer zur "
     "Strecke sind es wenige Gehminuten.</p>"]),
  ]},
 "kde": {
  "cs": [("Jízdy v supersportu na Autodromu", SHOWCARS_AMD, "informace okruhu k aktivitě"),
         ("Showcars.cz", SHOWCARS, "nabídka vozů, balíčky a poukazy")],
  "en": [("Supercar drives at Brno Circuit", SHOWCARS_AMD, "the circuit's information page"),
         ("Showcars.cz", SHOWCARS, "the cars, packages and vouchers")],
  "de": [("Supersportwagen-Fahrten am Automotodrom", SHOWCARS_AMD, "Infoseite der Rennstrecke"),
         ("Showcars.cz", SHOWCARS, "Fahrzeuge, Pakete und Gutscheine")]},
})

# ================================================================ 4.5 Škola smyku
ZAZITKY.append({
 "slug": "skola-smyku-polygon-brno", "num": "4.5", "odkaz": PG_SMYK,
 "titul": {"cs": "Škola smyku — Polygon Brno", "en": "Skid school — Polygon Brno",
           "de": "Schleuderschule — Polygon Brünn"},
 "odkaz_text": {"cs": "Vybrat úroveň kurzu →", "en": "Choose a level →", "de": "Stufe wählen →"},
 "cta": {"cs": "Vybrat úroveň →", "en": "Choose a level →", "de": "Stufe wählen →"},
 "text": {
   "cs": "Pět úrovní kurzu bezpečné jízdy v moderním tréninkovém centru hned vedle okruhu. Od první jistoty "
         "za volantem až po zvládnutý přetáčivý smyk.",
   "en": "Five levels of safe-driving training at a modern centre right next to the circuit — from first "
         "confidence behind the wheel to controlled oversteer.",
   "de": "Fünf Stufen des Fahrsicherheitstrainings in einem modernen Zentrum direkt neben der Rennstrecke — "
         "von der ersten Sicherheit am Steuer bis zum beherrschten Übersteuern."},
 "perex": {
   "cs": "Polygon Brno je moderní tréninkové centrum bezpečné jízdy v areálu Masarykova okruhu. Škola smyku "
         "tu má pět úrovní — a zlepší se na ní každý řidič bez rozdílu, ať jezdí jen do vedlejšího města, "
         "nebo najede stovky tisíc kilometrů ročně.",
   "en": "Polygon Brno is a modern safe-driving training centre inside the Masaryk Circuit complex. Its skid "
         "school has five levels, and every driver improves — whether they only commute to the next town or "
         "cover hundreds of thousands of kilometres a year.",
   "de": "Das Polygon Brünn ist ein modernes Fahrsicherheitszentrum auf dem Gelände des Masaryk-Rings. Die "
         "Schleuderschule hat fünf Stufen, und besser wird jeder Fahrer — ob er nur in die Nachbarstadt pendelt "
         "oder jährlich Hunderttausende Kilometer fährt."},
 "parametry": {
   "cs": "Úrovně=Compact · Intensiv · Intensiv+ · Advanced · Dynamic|Kde=Polygon Brno, areál Masarykova okruhu|"
         "Vozidlo=vlastní|Pozor=jedno auto = jeden řidič|Termíny=vypisují se 2–3 měsíce dopředu",
   "en": "Levels=Compact · Intensiv · Intensiv+ · Advanced · Dynamic|Where=Polygon Brno, inside the circuit "
         "complex|Vehicle=your own|Note=one car = one driver|Dates=published 2–3 months ahead",
   "de": "Stufen=Compact · Intensiv · Intensiv+ · Advanced · Dynamic|Wo=Polygon Brünn, Gelände des Masaryk-Rings|"
         "Fahrzeug=eigenes|Hinweis=ein Auto = ein Fahrer|Termine=2–3 Monate im Voraus"},
 "sekce": {
  "cs": [
   ("Pět úrovní, každá pro někoho jiného", [
     "<p><strong>Compact</strong> je základní úroveň, doporučená hlavně nejistým začátečníkům, kteří si školu "
     "bezpečné jízdy chtějí vyzkoušet. Řidič po ní získá základní sebejistotu za volantem v běžném provozu.</p>",
     "<p><strong>Intensiv</strong> je nejpopulárnější varianta: celodenní trénink zaměřený na komplexní přípravu "
     "řidiče do provozu. <strong>Intensiv+</strong> je jeho nadstavba — dvě a půl hodiny praxe věnované hlavně "
     "nácviku perfektně zvládnutého přetáčivého smyku; podmínkou je absolvovaný Intensiv.</p>",
     "<p><strong>Advanced</strong> je pokročilá celodenní úroveň, která prohlubuje dovednosti z nižších kurzů "
     "a jezdí se od dubna do října. <strong>Dynamic</strong> je nejpokročilejší úroveň pro řidiče, kteří mají "
     "sklony jezdit sportovně a chtějí to natrénovat tam, kde je to bezpečné.</p>"]),
   ("Jak kurz vypadá", [
     "<p>Trénuje se na vlastním voze na uzavřených plochách se sníženou adhezí pod dohledem instruktorů. "
     "V rámci jednoho termínu se dva účastníci nesmí střídat v jednom autě — každý řidič má vlastní vůz, aby "
     "kurz dával smysl.</p>",
     "<p>Termíny se vypisují dvě až tři měsíce dopředu a obsazenost bývá vysoká, proto se vyplatí sledovat "
     "kalendář a rezervovat s předstihem. Kurzy jsou i oblíbený dárek — poukaz platí rok od zakoupení.</p>"]),
   ("Proč to spojit s pobytem", [
     "<p>Celodenní kurz začíná ráno a končí odpoledne; když bydlíte v areálu, odpadá ranní shánění parkování "
     "i cesta domů po dni plném adrenalinu. Volný večer se dá zaplnit motokárami nebo jen večeří s výhledem "
     "na trať.</p>",
     "<p>Polygon i hotel jsou součástí téhož areálu — od recepce k tréninkovým plochám je to pár minut pěšky.</p>"]),
  ],
  "en": [
   ("Five levels, each for someone else", [
     "<p><strong>Compact</strong> is the entry level, recommended above all to hesitant beginners who want to "
     "try safe-driving training. It gives a driver basic confidence behind the wheel in everyday traffic.</p>",
     "<p><strong>Intensiv</strong> is the most popular option: a full-day training focused on preparing a driver "
     "comprehensively for the road. <strong>Intensiv+</strong> builds on it — two and a half hours of practice "
     "devoted mainly to mastering oversteer; completing Intensiv is a prerequisite.</p>",
     "<p><strong>Advanced</strong> is a full-day advanced level that deepens the skills from the lower courses "
     "and runs from April to October. <strong>Dynamic</strong> is the most advanced level, for drivers who like "
     "to drive briskly and want to practise it where it is safe.</p>"]),
   ("What a course looks like", [
     "<p>You train in your own car on closed low-grip surfaces under instructor supervision. Within one session "
     "two participants may not share a car — each driver has their own, or the training makes no sense.</p>",
     "<p>Dates are published two to three months ahead and courses fill up, so watch the calendar and book "
     "early. The courses are also a popular present — a voucher is valid for a year from purchase.</p>"]),
   ("Why combine it with a stay", [
     "<p>A full-day course starts in the morning and ends in the afternoon; staying in the complex removes both "
     "the morning parking hunt and the drive home after a day of adrenaline. The free evening can be filled with "
     "karting or simply dinner overlooking the track.</p>",
     "<p>The Polygon and the hotel are part of the same complex — a few minutes on foot from reception to the "
     "training areas.</p>"]),
  ],
  "de": [
   ("Fünf Stufen, jede für jemand anderen", [
     "<p><strong>Compact</strong> ist die Grundstufe, empfohlen vor allem unsicheren Anfängern, die ein "
     "Fahrsicherheitstraining ausprobieren möchten. Sie gibt die Grundsicherheit am Steuer im Alltagsverkehr.</p>",
     "<p><strong>Intensiv</strong> ist die beliebteste Variante: ein ganztägiges Training zur umfassenden "
     "Vorbereitung auf den Straßenverkehr. <strong>Intensiv+</strong> ist der Aufbau — zweieinhalb Stunden "
     "Praxis vor allem zum perfekten Beherrschen des übersteuernden Schleuderns; Voraussetzung ist Intensiv.</p>",
     "<p><strong>Advanced</strong> ist die ganztägige Fortgeschrittenenstufe, die die Fähigkeiten der unteren "
     "Kurse vertieft und von April bis Oktober läuft. <strong>Dynamic</strong> ist die höchste Stufe für Fahrer, "
     "die sportlich unterwegs sind und das dort üben wollen, wo es sicher ist.</p>"]),
   ("Wie ein Kurs abläuft", [
     "<p>Trainiert wird im eigenen Fahrzeug auf abgesperrten Flächen mit reduzierter Haftung unter Aufsicht der "
     "Instruktoren. Innerhalb eines Termins dürfen sich zwei Teilnehmer kein Auto teilen — jeder Fahrer hat sein "
     "eigenes, sonst ergibt der Kurs keinen Sinn.</p>",
     "<p>Termine werden zwei bis drei Monate im Voraus veröffentlicht und sind stark nachgefragt; den Kalender "
     "also im Auge behalten und früh buchen. Die Kurse sind auch ein beliebtes Geschenk — der Gutschein gilt ein "
     "Jahr ab Kauf.</p>"]),
   ("Warum mit einem Aufenthalt verbinden", [
     "<p>Ein Ganztageskurs beginnt morgens und endet nachmittags; wer auf dem Gelände wohnt, spart die "
     "Parkplatzsuche am Morgen und die Heimfahrt nach einem Tag voller Adrenalin. Der freie Abend lässt sich mit "
     "Karts füllen — oder einfach mit einem Essen mit Blick auf die Strecke.</p>",
     "<p>Polygon und Hotel liegen auf demselben Gelände — von der Rezeption zu den Trainingsflächen sind es "
     "wenige Gehminuten.</p>"]),
  ]},
 "kde": {
  "cs": [("Škola smyku — přehled úrovní", PG_SMYK, "všech pět kurzů a termíny"),
         ("Compact", PG_COMPACT, "základní úroveň pro začátečníky"),
         ("Intensiv", PG_INTENSIV, "nejoblíbenější celodenní trénink"),
         ("Intensiv+", PG_INTENSIVP, "nadstavba zaměřená na přetáčivý smyk"),
         ("Advanced", PG_ADVANCED, "pokročilá úroveň, duben–říjen"),
         ("Dynamic", PG_DYNAMIC, "nejpokročilejší úroveň"),
         ("Termíny kurzů", PG_TERMINY, "aktuální kalendář Polygonu")],
  "en": [("Skid school — all levels", PG_SMYK, "the five courses and their dates"),
         ("Compact", PG_COMPACT, "entry level for beginners"),
         ("Intensiv", PG_INTENSIV, "the most popular full-day training"),
         ("Intensiv+", PG_INTENSIVP, "follow-up focused on oversteer"),
         ("Advanced", PG_ADVANCED, "advanced level, April–October"),
         ("Dynamic", PG_DYNAMIC, "the most advanced level"),
         ("Course dates", PG_TERMINY, "the Polygon calendar")],
  "de": [("Schleuderschule — alle Stufen", PG_SMYK, "die fünf Kurse und ihre Termine"),
         ("Compact", PG_COMPACT, "Grundstufe für Anfänger"),
         ("Intensiv", PG_INTENSIV, "das beliebteste Ganztagestraining"),
         ("Intensiv+", PG_INTENSIVP, "Aufbau mit Fokus auf Übersteuern"),
         ("Advanced", PG_ADVANCED, "Fortgeschrittenenstufe, April–Oktober"),
         ("Dynamic", PG_DYNAMIC, "die höchste Stufe"),
         ("Kurstermine", PG_TERMINY, "aktueller Kalender des Polygons")]},
})

# ================================================================ 4.6 Drift & Gangster kurz
ZAZITKY.append({
 "slug": "drift-gangster-kurz", "num": "4.6", "odkaz": PG_DRIFT,
 "titul": {"cs": "Drift & Gangster kurz", "en": "Drift & Gangster course", "de": "Drift- & Gangster-Kurs"},
 "odkaz_text": {"cs": "Termíny na Polygonu →", "en": "Dates at the Polygon →", "de": "Termine am Polygon →"},
 "cta": {"cs": "Zjistit termíny →", "en": "See the dates →", "de": "Termine ansehen →"},
 "text": {
   "cs": "Řízený drift na kluzných plochách a filmová gangsterská otočka. Dva půldenní kurzy Polygonu Brno "
         "pro ty, koho řízení baví a chtějí umět víc.",
   "en": "Controlled drift on low-grip surfaces and the cinematic gangster turn. Two half-day Polygon Brno "
         "courses for drivers who enjoy driving and want to do more of it.",
   "de": "Kontrolliertes Driften auf rutschigen Flächen und die filmreife Gangster-Wende. Zwei halbtägige "
         "Kurse des Polygon Brünn für alle, denen Fahren Spaß macht."},
 "perex": {
   "cs": "Zážitkové kurzy Polygonu Brno jsou tou zábavnější stranou školy bezpečné jízdy. Drift kurz učí jízdu "
         "v řízeném smyku, Gangster kurz slavnou otočku z filmů — obojí na kluzných plochách, které šetří "
         "vaše auto, a pod dohledem instruktorů.",
   "en": "The experience courses at Polygon Brno are the entertaining side of safe-driving training. The drift "
         "course teaches controlled sliding, the gangster course the famous turn from the movies — both on "
         "low-grip surfaces that spare your car, and under instructor supervision.",
   "de": "Die Erlebniskurse des Polygon Brünn sind die unterhaltsame Seite des Fahrsicherheitstrainings. Der "
         "Driftkurs lehrt das kontrollierte Driften, der Gangster-Kurs die berühmte Wende aus Filmen — beides "
         "auf rutschigen Flächen, die das Auto schonen, und unter Aufsicht der Instruktoren."},
 "parametry": {
   "cs": "Drift kurz=cca 3,5 hodiny (20 min teorie + 3 h praxe)|Gangster kurz=půldenní trénink|"
         "Vozidlo=vlastní, zapůjčení není možné|Kde=Polygon Brno|Předchozí kurz=není podmínkou",
   "en": "Drift course=about 3.5 hours (20 min theory + 3 h practice)|Gangster course=half-day training|"
         "Vehicle=your own, no loan cars|Where=Polygon Brno|Previous course=not required",
   "de": "Driftkurs=ca. 3,5 Stunden (20 Min. Theorie + 3 Std. Praxis)|Gangster-Kurs=halbtägiges Training|"
         "Fahrzeug=eigenes, keine Leihwagen|Wo=Polygon Brünn|Vorkurs=nicht erforderlich"},
 "sekce": {
  "cs": [
   ("Drift kurz", [
     "<p>Intenzivní trénink jízdy v řízeném smyku na kluzných plochách, které minimalizují opotřebení vozu. "
     "Během hravého tréninku se naučíte držet auto ve smyku a do detailu poznáte, jak se chová na hranici "
     "přilnavosti — což je znalost, která se hodí i mimo polygon.</p>",
     "<p>Počítejte zhruba se třemi a půl hodinami: dvacet minut základní teorie a tři hodiny za volantem. "
     "Jezdí se výhradně vlastním vozem, zapůjčení auta na tenhle typ kurzu možné není.</p>"]),
   ("Gangster kurz", [
     "<p>Kurz je pojmenovaný po ikonické gangsterské otočce, kterou znáte z filmů a videoklipů. O překračování "
     "zákona přitom nejde — jde o půldenní trénink za volantem vlastního vozu, kde se pilují dovednosti, na "
     "které v běžném provozu není místo.</p>",
     "<p>Naučíte se gangsterskou otočku na kluzném povrchu v pomalém tempu, tutéž otočku na mokrém asfaltu "
     "ve svižnějším tempu a u aut s mechanickou ruční brzdou i otočku o 360 stupňů. Standardně se otočka učí "
     "až ve třetí úrovni školy smyku, tady se do ní pustíte rovnou.</p>"]),
   ("Pro koho a s čím počítat", [
     "<p>Kurzy jsou pro řidiče, které řízení baví a chtějí umět víc — absolvovaná škola smyku Compact nebo "
     "Intensiv je výhodou, ale podmínkou není. Oba kurzy se dají koupit i jako dárkový poukaz s roční "
     "platností.</p>",
     "<p>Termíny Polygon vypisuje dvě až tři měsíce dopředu a obsazenost je vysoká. Když si vyberete termín, "
     "ozvěte se nám kvůli pokoji — po půldni na kluzné ploše je příjemné mít postel pár minut od auta.</p>"]),
  ],
  "en": [
   ("The drift course", [
     "<p>An intensive training in controlled sliding on low-grip surfaces that minimise wear on your car. The "
     "playful format teaches you to hold the car in a slide and to understand in detail how it behaves at the "
     "limit of grip — knowledge that pays off well beyond the training area.</p>",
     "<p>Allow about three and a half hours: twenty minutes of basic theory and three hours behind the wheel. "
     "You drive your own car; loan cars are not available for this course.</p>"]),
   ("The gangster course", [
     "<p>The course is named after the iconic gangster turn from films and music videos. Nothing about it breaks "
     "the law — it is a half-day of training in your own car, polishing skills that ordinary traffic leaves no "
     "room for.</p>",
     "<p>You learn the gangster turn on a slippery surface at slow speed, the same turn on wet asphalt at a "
     "brisker pace and, in cars with a mechanical handbrake, a full 360-degree spin. Normally the turn is taught "
     "only at the third level of the skid school; here you go straight to it.</p>"]),
   ("Who it is for", [
     "<p>These courses are for drivers who enjoy driving and want to do more — having completed skid school "
     "Compact or Intensiv helps but is not required. Both are available as a gift voucher valid for a year.</p>",
     "<p>The Polygon publishes dates two to three months ahead and they fill up. Once you have a date, talk to "
     "us about a room — after half a day on a slippery surface it is good to have a bed a few minutes from the "
     "car.</p>"]),
  ],
  "de": [
   ("Der Driftkurs", [
     "<p>Intensives Training des kontrollierten Driftens auf rutschigen Flächen, die den Verschleiß am Fahrzeug "
     "gering halten. Im spielerischen Training lernen Sie, das Auto im Drift zu halten, und verstehen im Detail, "
     "wie es sich an der Haftgrenze verhält — das nützt auch außerhalb des Polygons.</p>",
     "<p>Rechnen Sie mit etwa dreieinhalb Stunden: zwanzig Minuten Theorie und drei Stunden am Steuer. Gefahren "
     "wird ausschließlich im eigenen Auto, Leihfahrzeuge gibt es für diesen Kurs nicht.</p>"]),
   ("Der Gangster-Kurs", [
     "<p>Der Kurs ist nach der ikonischen Gangster-Wende aus Filmen und Videoclips benannt. Mit Gesetzesbruch hat "
     "das nichts zu tun — es ist ein halber Tag Training im eigenen Auto, bei dem Fähigkeiten geschliffen werden, "
     "für die im Alltagsverkehr kein Platz ist.</p>",
     "<p>Sie lernen die Gangster-Wende auf rutschigem Untergrund im langsamen Tempo, dieselbe Wende auf nassem "
     "Asphalt im flotteren Tempo und bei Autos mit mechanischer Handbremse auch die 360-Grad-Drehung. Normalerweise "
     "wird die Wende erst in der dritten Stufe der Schleuderschule unterrichtet, hier geht es gleich los.</p>"]),
   ("Für wen und was zu beachten ist", [
     "<p>Die Kurse sind für Fahrer, denen Fahren Spaß macht und die mehr können wollen — eine absolvierte "
     "Schleuderschule Compact oder Intensiv ist von Vorteil, aber keine Bedingung. Beide gibt es auch als "
     "Gutschein mit einem Jahr Gültigkeit.</p>",
     "<p>Das Polygon veröffentlicht Termine zwei bis drei Monate im Voraus, die Nachfrage ist hoch. Wenn Ihr "
     "Termin steht, sprechen Sie uns wegen des Zimmers an — nach einem halben Tag auf rutschiger Fläche ist ein "
     "Bett wenige Minuten vom Auto entfernt angenehm.</p>"]),
  ]},
 "kde": {
  "cs": [("Drift kurz", PG_DRIFT, "obsah kurzu, délka a termíny"),
         ("Gangster kurz", PG_GANGSTER, "gangsterská otočka krok za krokem"),
         ("Termíny kurzů", PG_TERMINY, "aktuální kalendář Polygonu"),
         ("Dárkové poukazy Polygonu", PG_POUKAZY, "poukaz s roční platností")],
  "en": [("Drift course", PG_DRIFT, "content, length and dates"),
         ("Gangster course", PG_GANGSTER, "the gangster turn step by step"),
         ("Course dates", PG_TERMINY, "the Polygon calendar"),
         ("Polygon gift vouchers", PG_POUKAZY, "voucher valid for a year")],
  "de": [("Driftkurs", PG_DRIFT, "Inhalt, Dauer und Termine"),
         ("Gangster-Kurs", PG_GANGSTER, "die Gangster-Wende Schritt für Schritt"),
         ("Kurstermine", PG_TERMINY, "aktueller Kalender des Polygons"),
         ("Gutscheine des Polygons", PG_POUKAZY, "Gutschein mit einem Jahr Gültigkeit")]},
})

# ================================================================ 4.7 Odpočet trestných bodů
ZAZITKY.append({
 "slug": "odpocet-trestnych-bodu", "num": "4.7", "odkaz": PG_BODY,
 "titul": {"cs": "Odpočet trestných bodů", "en": "Penalty point deduction", "de": "Abbau von Strafpunkten"},
 "odkaz_text": {"cs": "Podmínky a termíny →", "en": "Conditions and dates →", "de": "Bedingungen und Termine →"},
 "cta": {"cs": "Zjistit podmínky →", "en": "Check the conditions →", "de": "Bedingungen prüfen →"},
 "text": {
   "cs": "Certifikovaný kurz na Polygonu Brno, po kterém si lze odečíst čtyři trestné body. Praktický důvod "
         "přijet — a den, ze kterého si odvezete i lepší jízdu.",
   "en": "A certified course at Polygon Brno that lets you deduct four penalty points. A practical reason to "
         "come — and a day that also makes you a better driver.",
   "de": "Ein zertifizierter Kurs am Polygon Brünn, nach dem sich vier Strafpunkte abbauen lassen. Ein "
         "praktischer Grund zu kommen — und ein Tag, der auch das Fahren verbessert."},
 "perex": {
   "cs": "Hrozí vám ztráta řidičského oprávnění kvůli nasbíraným bodům? Polygon Brno pořádá certifikovaný kurz "
         "bezpečné jízdy, po jehož absolvování si můžete v registru řidičů odečíst až čtyři trestné body — "
         "a vyhnout se ročnímu odebrání řidičáku.",
   "en": "Facing the loss of your licence over accumulated penalty points? Polygon Brno runs a certified "
         "safe-driving course after which up to four penalty points can be deducted from the drivers' register — "
         "avoiding a year without a licence.",
   "de": "Droht Ihnen der Verlust der Fahrerlaubnis wegen gesammelter Punkte? Das Polygon Brünn veranstaltet "
         "einen zertifizierten Fahrsicherheitskurs, nach dem bis zu vier Strafpunkte im Fahrerregister abgebaut "
         "werden können — und damit ein Jahr ohne Führerschein vermieden wird."},
 "parametry": {
   "cs": "Odečet=až 4 trestné body|Kde=Polygon Brno|Podmínka=žádný přestupek za 6 nebo 7 bodů|"
         "Četnost=1× za kalendářní rok|Objednání=termín na webu Polygonu",
   "en": "Deduction=up to 4 penalty points|Where=Polygon Brno|Condition=no offence worth 6 or 7 points|"
         "Frequency=once per calendar year|Booking=pick a date on the Polygon website",
   "de": "Abbau=bis zu 4 Strafpunkte|Wo=Polygon Brünn|Bedingung=kein Verstoß mit 6 oder 7 Punkten|"
         "Häufigkeit=1× pro Kalenderjahr|Buchung=Termin auf der Polygon-Website"},
 "sekce": {
  "cs": [
   ("Co kurz řeší", [
     "<p>Účastí na certifikovaném školení si můžete odečíst čtyři trestné body v registru řidičů a vyhnout se "
     "hrozbě ročního odebrání řidičského oprávnění. Vedle papírového efektu má kurz i ten praktický: odvezete "
     "si větší jistotu při řešení krizových situací za volantem.</p>",
     "<p>Školení nemůže absolvovat každý. Podmínkou je, že žádný z vašich přestupků nebyl ohodnocen šesti nebo "
     "sedmi trestnými body, a kurz lze absolvovat jen jednou za kalendářní rok.</p>"]),
   ("Jak se přihlásit", [
     "<p>Termín si vyberete a zakoupíte přímo na stránkách Polygonu Brno v nabídce odpočtu bodů. Termíny se "
     "vypisují dopředu a bývají obsazené, takže s výběrem nečekejte na poslední chvíli — zvlášť když vám běží "
     "lhůta.</p>",
     "<p>Trénuje se vlastním vozem na uzavřených plochách polygonu; v rámci jednoho termínu se dva řidiči "
     "nemohou střídat v jednom autě.</p>"]),
   ("Firemní varianta a pobyt", [
     "<p>Stejné zázemí se používá i pro firemní školení řidičů na míru — pro flotilu i pro obchodní tým. "
     "Pro firmy je praktické, že hotel, jednací prostory i tréninkové plochy jsou v jednom areálu: dopoledne "
     "školení, odpoledne polygon, večer společná večeře.</p>",
     "<p>Kurz zabere velkou část dne. Pokud jedete zdaleka, dává smysl přijet už večer předtím a ráno jít "
     "z pokoje rovnou na sraz.</p>"]),
  ],
  "en": [
   ("What the course does", [
     "<p>Completing the certified training lets you remove four penalty points from the drivers' register and "
     "avoid the threat of losing your licence for a year. Beyond the paperwork it has a practical effect too: "
     "you leave with more confidence in handling emergencies behind the wheel.</p>",
     "<p>Not everyone is eligible. None of your offences may have been rated at six or seven penalty points, and "
     "the course can be taken only once per calendar year.</p>"]),
   ("How to sign up", [
     "<p>You choose and buy a date directly on the Polygon Brno website under the penalty-point section. Dates "
     "are published ahead and do fill up, so do not leave it to the last minute — especially if a deadline is "
     "running.</p>",
     "<p>Training takes place in your own car on the closed Polygon areas; within one session two drivers may "
     "not share a car.</p>"]),
   ("The corporate version and your stay", [
     "<p>The same facilities are used for tailored corporate driver training — for a fleet or a sales team. For "
     "companies it is practical that the hotel, the meeting rooms and the training areas sit in one complex: "
     "training in the morning, the Polygon in the afternoon, dinner together in the evening.</p>",
     "<p>The course takes most of the day. If you are travelling far, it makes sense to arrive the evening "
     "before and walk from your room straight to the briefing.</p>"]),
  ],
  "de": [
   ("Was der Kurs bringt", [
     "<p>Mit der Teilnahme am zertifizierten Training lassen sich vier Strafpunkte im Fahrerregister abbauen und "
     "der drohende einjährige Entzug der Fahrerlaubnis vermeiden. Neben dem Papiereffekt hat der Kurs auch einen "
     "praktischen: mehr Sicherheit im Umgang mit kritischen Situationen am Steuer.</p>",
     "<p>Teilnehmen darf nicht jeder. Voraussetzung ist, dass keiner Ihrer Verstöße mit sechs oder sieben "
     "Strafpunkten bewertet wurde, und der Kurs ist nur einmal pro Kalenderjahr möglich.</p>"]),
   ("Anmeldung", [
     "<p>Den Termin wählen und kaufen Sie direkt auf der Website des Polygon Brünn im Bereich Punkteabbau. Die "
     "Termine werden im Voraus veröffentlicht und sind oft belegt — warten Sie nicht bis zuletzt, besonders wenn "
     "eine Frist läuft.</p>",
     "<p>Trainiert wird im eigenen Fahrzeug auf den abgesperrten Flächen des Polygons; innerhalb eines Termins "
     "dürfen sich zwei Fahrer kein Auto teilen.</p>"]),
   ("Firmenvariante und Aufenthalt", [
     "<p>Dieselbe Infrastruktur dient auch maßgeschneiderten Fahrertrainings für Firmen — für den Fuhrpark oder "
     "das Vertriebsteam. Praktisch ist, dass Hotel, Tagungsräume und Trainingsflächen auf einem Gelände liegen: "
     "vormittags Schulung, nachmittags Polygon, abends gemeinsames Essen.</p>",
     "<p>Der Kurs nimmt einen großen Teil des Tages in Anspruch. Bei weiter Anreise lohnt es sich, schon am "
     "Vorabend zu kommen und morgens vom Zimmer direkt zum Treffpunkt zu gehen.</p>"]),
  ]},
 "kde": {
  "cs": [("Odpočet bodů na Polygonu Brno", PG_BODY, "podmínky, průběh a objednání termínu"),
         ("Termíny kurzů", PG_TERMINY, "aktuální kalendář Polygonu"),
         ("Polygon Brno", PG, "tréninkové centrum bezpečné jízdy")],
  "en": [("Penalty point course at Polygon Brno", PG_BODY, "conditions, format and booking"),
         ("Course dates", PG_TERMINY, "the Polygon calendar"),
         ("Polygon Brno", PG, "the safe-driving training centre")],
  "de": [("Punkteabbau am Polygon Brünn", PG_BODY, "Bedingungen, Ablauf und Buchung"),
         ("Kurstermine", PG_TERMINY, "aktueller Kalender des Polygons"),
         ("Polygon Brünn", PG, "Zentrum für Fahrsicherheitstraining")]},
})

# ================================================================ 4.8 Minibike akademie
ZAZITKY.append({
 "slug": "minibike-akademie", "num": "4.8", "odkaz": MINIBIKE_AMD,
 "titul": {"cs": "Minibiková akademie pro děti", "en": "Minibike academy for children",
           "de": "Minibike-Akademie für Kinder"},
 "odkaz_text": {"cs": "Informace o akademii →", "en": "About the academy →", "de": "Über die Akademie →"},
 "cta": {"cs": "Pro malé jezdce →", "en": "For young riders →", "de": "Für junge Fahrer →"},
 "text": {
   "cs": "„Z odrážedla třeba až do MotoGP.“ Akademie Masarykova okruhu učí děti od 4 do 15 let jezdit na "
         "minibiku — bezpečně, s vybavením i trenérem.",
   "en": "“From a balance bike all the way to MotoGP.” The circuit's academy teaches children aged 4 to 15 "
         "to ride a minibike — safely, with gear and a coach provided.",
   "de": "„Vom Laufrad bis in die MotoGP.“ Die Akademie des Masaryk-Rings bringt Kindern von 4 bis 15 "
         "Jahren das Minibike-Fahren bei — sicher, mit Ausrüstung und Trainer."},
 "perex": {
   "cs": "Minibiková akademie funguje v areálu Masarykova okruhu od roku 2010 a je ideální branou do světa "
         "motocyklového sportu. Děti se pod dohledem profesionálních instruktorů učí správné návyky pro "
         "bezpečnou jízdu — a rodiče mají o program postaráno.",
   "en": "The minibike academy has run inside the Masaryk Circuit complex since 2010 and is an ideal gateway "
         "into motorcycle sport. Children learn safe riding habits under professional instructors — and parents "
         "have their programme sorted.",
   "de": "Die Minibike-Akademie besteht auf dem Gelände des Masaryk-Rings seit 2010 und ist das ideale Tor zum "
         "Motorradsport. Kinder lernen unter professionellen Instruktoren die richtigen Gewohnheiten für "
         "sicheres Fahren — und die Eltern haben ein Programm."},
 "parametry": {
   "cs": "Věk=4–15 let|Kde=areál Masarykova okruhu (v zimě v hale)|Sezóna=léto duben–říjen, zima listopad–únor|"
         "Stroje=čtyřkolka nebo minibike 40 ccm|Vybavení=zapůjčíme (helma, chrániče, rukavice)",
   "en": "Age=4–15 years|Where=inside the Masaryk Circuit complex (indoors in winter)|Season=summer April–October, "
         "winter November–February|Machines=quad or 40 cc minibike|Gear=provided (helmet, protectors, gloves)",
   "de": "Alter=4–15 Jahre|Wo=Gelände des Masaryk-Rings (im Winter in der Halle)|Saison=Sommer April–Oktober, "
         "Winter November–Februar|Maschinen=Quad oder 40-ccm-Minibike|Ausrüstung=wird gestellt"},
 "sekce": {
  "cs": [
   ("Co se v akademii děje", [
     "<p>Hlavním cílem je výchova a rozvoj mladých jezdců. Na pravidelných trénincích se děti v bezpečných "
     "podmínkách učí obratnosti, rovnováze, jízdě ve správné stopě a základním pravidlům. Tréninky jsou "
     "rozdělené podle výkonnosti a nácvik vyšších rychlostí přichází až po zvládnutí návyků.</p>",
     "<p>Kromě jízdy patří k akademii i mimosezónní soustředění s fyzickou a technickou přípravou a výlety. "
     "Členové mohou startovat v seriálu Minimotoracing na minibikových drahách v Česku a na Slovensku.</p>",
     "<p>Na akademii navazuje Brno Circuit Junior Racing Team, který nadějné jezdce vede dál ke kariéře. "
     "Akademie funguje od roku 2010 a vychovala řadu jezdců, kteří závodí i v evropských šampionátech.</p>"]),
   ("Jak začít", [
     "<p>Začít mohou děvčata i chlapci ve věku od 4 do 15 let. Každé dítě projde začátečnickým kurzem, který "
     "se dá absolvovat i individuálně — nastoupit jde tedy i v půlce sezóny.</p>",
     "<p>Kurz probíhá na čtyřkolce nebo na dvoutaktním minibiku 40 ccm, u kterého je pro začátečníky výkon "
     "omezený na plynové rukojeti. Dítě jistí profesionální trenér bezpečnostním popruhem. Akademie zapůjčí "
     "helmu s kuklou, rukavice i chrániče páteře, loktů a kolen — z domova stačí kotníková obuv a pevné "
     "volnější kalhoty.</p>",
     "<p>Letní sezóna běží od dubna do října převážně na minibikové dráze, zimní od listopadu do února v hale "
     "pod střechou.</p>"]),
   ("Rodinný pobyt u okruhu", [
     "<p>Pro rodiny je akademie tím, co promění výlet k okruhu ve smysluplný program: dítě jezdí pod dohledem "
     "trenéra, rodiče mají výhled na dráhu a kávu na dosah. Starší sourozenci si mezitím mohou zajet motokáry "
     "— na ty se smí od 12 let a 160 cm.</p>",
     "<p>Aktuální informace o fungování akademie a Junior Racing Teamu vede samostatný web "
     "minibikeakademiejrt.cz, kde najdete i kontakt na přihlášení.</p>"]),
  ],
  "en": [
   ("What happens at the academy", [
     "<p>The aim is to bring up and develop young riders. In regular training sessions children learn agility, "
     "balance, the right line and the basic rules in safe conditions. Groups are split by ability and higher "
     "speeds come only once the habits are in place.</p>",
     "<p>Besides riding, the academy runs off-season camps with physical and technical preparation and trips. "
     "Members can race in the Minimotoracing series on minibike tracks in Czechia and Slovakia.</p>",
     "<p>The academy feeds into the Brno Circuit Junior Racing Team, which guides promising riders towards a "
     "career. Running since 2010, it has produced riders who now compete in European championships.</p>"]),
   ("How to start", [
     "<p>Girls and boys can start between the ages of 4 and 15. Every child goes through a beginners' course, "
     "which can also be taken individually — so joining mid-season is possible.</p>",
     "<p>The course uses a quad or a two-stroke 40 cc minibike whose power is limited at the throttle for "
     "beginners. A professional coach secures the child with a safety strap. The academy lends a helmet with "
     "a balaclava, gloves and back, elbow and knee protectors — bring ankle boots and loose sturdy trousers.</p>",
     "<p>The summer season runs from April to October, mostly on the minibike track; the winter season from "
     "November to February indoors.</p>"]),
   ("A family stay at the circuit", [
     "<p>For families the academy turns a trip to the circuit into a proper programme: the child rides under a "
     "coach's eye while the parents have a view of the track and a coffee within reach. Older siblings can go "
     "karting in the meantime — allowed from 12 years and 160 cm.</p>",
     "<p>Current information about the academy and the Junior Racing Team is kept on a separate site, "
     "minibikeakademiejrt.cz, which also has the contact for signing up.</p>"]),
  ],
  "de": [
   ("Was in der Akademie passiert", [
     "<p>Ziel ist die Ausbildung und Entwicklung junger Fahrer. In regelmäßigen Trainings lernen Kinder unter "
     "sicheren Bedingungen Geschicklichkeit, Balance, die richtige Linie und die Grundregeln. Die Gruppen sind "
     "nach Leistung geteilt, höhere Geschwindigkeiten kommen erst, wenn die Grundlagen sitzen.</p>",
     "<p>Neben dem Fahren gehören Trainingslager außerhalb der Saison mit körperlicher und technischer "
     "Vorbereitung sowie Ausflügen dazu. Mitglieder können in der Serie Minimotoracing auf Minibike-Strecken in "
     "Tschechien und der Slowakei starten.</p>",
     "<p>An die Akademie schließt das Brno Circuit Junior Racing Team an, das talentierte Fahrer weiter zur "
     "Karriere führt. Seit 2010 hat die Akademie zahlreiche Fahrer hervorgebracht, die auch in europäischen "
     "Meisterschaften antreten.</p>"]),
   ("Wie man anfängt", [
     "<p>Mädchen und Jungen können im Alter von 4 bis 15 Jahren beginnen. Jedes Kind absolviert einen "
     "Anfängerkurs, der auch individuell möglich ist — der Einstieg mitten in der Saison geht also auch.</p>",
     "<p>Gefahren wird auf einem Quad oder einem 40-ccm-Zweitakt-Minibike, dessen Leistung für Anfänger am "
     "Gasgriff begrenzt ist. Ein professioneller Trainer sichert das Kind mit einem Sicherheitsgurt. Die "
     "Akademie stellt Helm mit Sturmhaube, Handschuhe sowie Rücken-, Ellbogen- und Knieschützer — mitbringen "
     "sollten Sie knöchelhohe Schuhe und feste, weite Hosen.</p>",
     "<p>Die Sommersaison läuft von April bis Oktober überwiegend auf der Minibike-Strecke, die Wintersaison von "
     "November bis Februar in der Halle.</p>"]),
   ("Familienaufenthalt an der Rennstrecke", [
     "<p>Für Familien macht die Akademie aus dem Ausflug zur Rennstrecke ein sinnvolles Programm: Das Kind fährt "
     "unter Aufsicht eines Trainers, die Eltern haben Blick auf die Strecke und einen Kaffee in Reichweite. "
     "Ältere Geschwister können in der Zwischenzeit Kart fahren — erlaubt ab 12 Jahren und 160 cm.</p>",
     "<p>Aktuelle Informationen zur Akademie und zum Junior Racing Team führt die eigene Website "
     "minibikeakademiejrt.cz, dort steht auch der Kontakt zur Anmeldung.</p>"]),
  ]},
 "kde": {
  "cs": [("Minibiková akademie na Autodromu", MINIBIKE_AMD, "informace okruhu o akademii"),
         ("minibikeakademiejrt.cz", MINIBIKE, "aktuální fungování akademie a JRT")],
  "en": [("Minibike academy at Brno Circuit", MINIBIKE_AMD, "the circuit's information page"),
         ("minibikeakademiejrt.cz", MINIBIKE, "current academy and JRT information")],
  "de": [("Minibike-Akademie am Automotodrom", MINIBIKE_AMD, "Infoseite der Rennstrecke"),
         ("minibikeakademiejrt.cz", MINIBIKE, "aktuelle Infos zu Akademie und JRT")]},
})

# ================================================================ 4.9 Dárkové poukazy
ZAZITKY.append({
 "slug": "darkove-poukazy", "num": "4.9", "odkaz": PG_POUKAZY,
 "titul": {"cs": "Dárkové poukazy", "en": "Gift vouchers", "de": "Geschenkgutscheine"},
 "odkaz_text": {"cs": "Poukazy Polygonu →", "en": "Polygon vouchers →", "de": "Gutscheine des Polygons →"},
 "cta": {"cs": "Koupit poukaz →", "en": "Buy a voucher →", "de": "Gutschein kaufen →"},
 "text": {
   "cs": "Zážitek u okruhu jako dárek — pobyt v hotelu, škola smyku, drift, jízda v supersportu nebo volná "
         "jízda na trati. Poukaz pošleme elektronicky i tištěně.",
   "en": "A trackside experience as a present — a hotel stay, skid school, drift course, supercar drive or a "
         "public track session. We send vouchers electronically or printed.",
   "de": "Ein Erlebnis an der Rennstrecke als Geschenk — Hotelaufenthalt, Schleuderschule, Driftkurs, "
         "Supersportwagen oder Touristenfahrt. Den Gutschein senden wir digital oder gedruckt."},
 "perex": {
   "cs": "Poukaz na zážitek u Masarykova okruhu je dárek, který se nedá zaměnit s ničím jiným. Vybrat se dá "
         "pobyt v GRID Hotelu, nebo některý z kurzů a jízd, které se u okruhu pořádají — a obdarovaný si sám "
         "zvolí termín, který mu vyhovuje.",
   "en": "A voucher for an experience at the Masaryk Circuit is a present that is hard to confuse with anything "
         "else. Choose a stay at GRID Hotel, or one of the courses and drives held at the circuit — and let the "
         "recipient pick the date that suits them.",
   "de": "Ein Gutschein für ein Erlebnis am Masaryk-Ring ist ein Geschenk, das man mit nichts verwechselt. "
         "Wählbar sind ein Aufenthalt im GRID Hotel oder einer der Kurse und Fahrten an der Rennstrecke — den "
         "Termin sucht sich der Beschenkte selbst aus."},
 "parametry": {
   "cs": "Pobytový poukaz=v libovolné hodnotě|Forma=elektronicky i tištěně|Objednávka=e-mailem na recepci|"
         "Poukazy Polygonu=platnost 1 rok od zakoupení|Jízdy veřejnosti=poukaz bez termínu",
   "en": "Stay voucher=any value|Format=electronic or printed|Order=by e-mail to reception|"
         "Polygon vouchers=valid 1 year from purchase|Track days=open-dated voucher",
   "de": "Aufenthaltsgutschein=beliebiger Wert|Form=digital oder gedruckt|Bestellung=per E-Mail an die Rezeption|"
         "Polygon-Gutscheine=1 Jahr ab Kauf gültig|Touristenfahrten=Gutschein ohne Termin"},
 "sekce": {
  "cs": [
   ("Poukaz na pobyt v GRID Hotelu", [
     "<p>Poukaz na ubytování vystavíme v libovolné hodnotě nebo přímo na konkrétní typ pokoje — od Standardu "
     "po Apartmá Superior s výhledem na trať. Pošleme ho elektronicky i tištěný a obdarovaný si termín "
     "domluví přímo s recepcí.</p>",
     "<p>Nejčastěji se poukaz kupuje k závodnímu víkendu nebo k některému z kurzů: dárek pak není jen jízda, "
     "ale celý víkend u okruhu — s večeří a bez nočního návratu domů.</p>"]),
   ("Poukazy na zážitky u okruhu", [
     "<p>Polygon Brno prodává poukazy na všechny své kurzy — školu smyku od úrovně Compact po Dynamic, drift "
     "i gangster kurz. Poukaz platí rok od zakoupení; obdarovaný si na nákupním portálu zadá číslo poukazu "
     "a vybere termín.</p>",
     "<p>Jízdy veřejnosti se prodávají také jako poukaz bez pevného termínu. Pozor na jednu věc: poukaz má "
     "uvedenou platnost, do které je nutné termín <em>zaregistrovat</em>, ne odjet — a držitel poukazu nemá "
     "místo automaticky rezervované, takže se nevyplácí nechávat výběr na poslední chvíli.</p>",
     "<p>Jízdy v supersportu si obdarovaný objednává přes agenturu Showcars: po zaplacení dostane poukaz "
     "s číslem kupónu a termín si zvolí v rezervačním systému.</p>"]),
   ("Co s poukazem, který jste dostali", [
     "<p>Máte-li poukaz na pobyt, ozvěte se recepci — domluvíme termín a doporučíme, co se ve zvoleném týdnu "
     "na okruhu jede. U poukazů na kurzy pokračujte na web pořadatele, kde poukaz uplatníte a vyberete termín.</p>",
     "<p>A pokud chcete zážitek a pobyt spojit, řekněte nám to při rezervaci: podle kalendáře okruhu poradíme "
     "termín, kdy se dá stihnout obojí.</p>"]),
  ],
  "en": [
   ("A voucher for a stay at GRID Hotel", [
     "<p>We issue accommodation vouchers for any value, or for a specific room type — from Standard to the "
     "Superior Apartment overlooking the track. We send them electronically or printed, and the recipient "
     "arranges the date directly with reception.</p>",
     "<p>Most often a voucher is bought for a race weekend or alongside one of the courses: the present is then "
     "not just a drive but a whole weekend at the circuit — dinner included, no late drive home.</p>"]),
   ("Vouchers for experiences at the circuit", [
     "<p>Polygon Brno sells vouchers for all its courses — skid school from Compact to Dynamic, the drift and "
     "gangster courses. A voucher is valid for a year from purchase; the recipient enters its number in the "
     "purchase portal and picks a date.</p>",
     "<p>Public track days are also sold as open-dated vouchers. One caveat: the voucher's validity is the "
     "period within which a date must be <em>registered</em>, not ridden — and holders have no automatic slot, "
     "so leaving it to the last minute does not pay off.</p>",
     "<p>Supercar drives are ordered through the Showcars agency: after payment the recipient gets a voucher "
     "with a coupon number and selects a date in the booking system.</p>"]),
   ("What to do with a voucher you received", [
     "<p>If you hold a voucher for a stay, contact reception — we will agree a date and tell you what is "
     "happening on track that week. For course vouchers, continue to the organiser's website, where you redeem "
     "the voucher and choose a date.</p>",
     "<p>And if you want to combine the experience with a stay, tell us when booking: we will suggest a date "
     "when both fit, based on the circuit calendar.</p>"]),
  ],
  "de": [
   ("Gutschein für einen Aufenthalt im GRID Hotel", [
     "<p>Übernachtungsgutscheine stellen wir über einen beliebigen Wert aus oder direkt für eine Zimmerkategorie "
     "— vom Standard bis zum Superior-Appartement mit Blick auf die Strecke. Wir senden sie digital oder "
     "gedruckt, den Termin vereinbart der Beschenkte direkt mit der Rezeption.</p>",
     "<p>Am häufigsten wird der Gutschein zum Rennwochenende oder zu einem Kurs gekauft: Das Geschenk ist dann "
     "nicht nur eine Fahrt, sondern ein ganzes Wochenende an der Strecke — mit Abendessen und ohne nächtliche "
     "Heimfahrt.</p>"]),
   ("Gutscheine für Erlebnisse an der Rennstrecke", [
     "<p>Das Polygon Brünn verkauft Gutscheine für alle seine Kurse — Schleuderschule von Compact bis Dynamic, "
     "Drift- und Gangster-Kurs. Der Gutschein gilt ein Jahr ab Kauf; der Beschenkte gibt im Portal die "
     "Gutscheinnummer ein und wählt einen Termin.</p>",
     "<p>Touristenfahrten gibt es ebenfalls als Gutschein ohne festen Termin. Ein Hinweis: Die Gültigkeit ist "
     "der Zeitraum, in dem ein Termin <em>registriert</em> — nicht gefahren — werden muss, und Gutscheininhaber "
     "haben keinen automatisch reservierten Platz.</p>",
     "<p>Fahrten im Supersportwagen bestellt der Beschenkte über die Agentur Showcars: Nach der Zahlung erhält "
     "er einen Gutschein mit Coupon-Nummer und wählt den Termin im Buchungssystem.</p>"]),
   ("Was tun mit einem geschenkten Gutschein", [
     "<p>Haben Sie einen Gutschein für einen Aufenthalt, melden Sie sich bei der Rezeption — wir stimmen den "
     "Termin ab und sagen Ihnen, was in dieser Woche auf der Strecke los ist. Bei Kursgutscheinen geht es auf "
     "der Website des Veranstalters weiter, wo Sie den Gutschein einlösen und einen Termin wählen.</p>",
     "<p>Und wenn Sie Erlebnis und Aufenthalt verbinden möchten, sagen Sie es uns bei der Buchung: Wir schlagen "
     "anhand des Streckenkalenders einen Termin vor, an dem beides zusammenpasst.</p>"]),
  ]},
 "kde": {
  "cs": [("Dárkové poukazy Polygonu Brno", PG_POUKAZY, "poukazy na školu smyku a zážitkové kurzy"),
         ("Jízdy veřejnosti — poukazy", SEZNAM_JIZD, "poukaz na volnou jízdu bez termínu"),
         ("Showcars.cz", SHOWCARS, "poukazy na jízdu v supersportu")],
  "en": [("Polygon Brno gift vouchers", PG_POUKAZY, "vouchers for the skid school and experience courses"),
         ("Public track days — vouchers", SEZNAM_JIZD, "open-dated track session voucher"),
         ("Showcars.cz", SHOWCARS, "supercar driving vouchers")],
  "de": [("Geschenkgutscheine des Polygon Brünn", PG_POUKAZY, "Gutscheine für Schleuderschule und Erlebniskurse"),
         ("Touristenfahrten — Gutscheine", SEZNAM_JIZD, "Gutschein für eine Fahrt ohne Termin"),
         ("Showcars.cz", SHOWCARS, "Gutscheine für Supersportwagen")]},
})
