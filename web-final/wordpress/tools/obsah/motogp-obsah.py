# -*- coding: utf-8 -*-
"""Obsah zážitku MotoGP — Grand Prix České republiky (CZ/EN/DE).

Fakta pocházejí z oficiálních zdrojů motogpczechia.com a automotodrombrno.cz
(technické parametry trati, popis tribun, pravidla předprodeje). Texty jsou
psané pro web hotelu, nejde o kopii oficiálních stránek — na vhodných místech
vede odkaz zpět na zdroj, který má vždy aktuálnější informace.
"""

WEB      = "https://www.motogpczechia.com/"
VSTUPNE  = "https://vstupenky.motogpczechia.com/"
CENIK    = "https://www.motogpczechia.com/cenik-vstupenek"
TRIBUNY  = "https://www.motogpczechia.com/tribuny"
PLANEK   = "https://www.motogpczechia.com/planek-okruhu"
NAVSTEVA = "https://www.motogpczechia.com/pro-navstevniky"
ZAVOD    = "https://www.motogpczechia.com/zavod"
AMD      = "https://www.automotodrombrno.cz/"
MAPA     = "/wp-content/themes/grid-divi5-child/assets/foto/grid-okruh-mapa.svg"

NUM  = "4.0"
SLUG = {"cs": "moto-gp", "en": "moto-gp-en", "de": "moto-gp-de"}

TITULEK = {
    "cs": "MotoGP — Grand Prix České republiky",
    "en": "MotoGP — Grand Prix of Czechia",
    "de": "MotoGP — Grand Prix von Tschechien",
}

# krátký text na kartu v sekci T4
TEXT = {
    "cs": "Nejrychlejší víkend roku přímo za okny hotelu. Tribuny, paddock i cílová rovinka jsou pár "
          "minut pěšky — bez kolon a bez hledání parkování. Vstupenky a program na oficiálním webu.",
    "en": "The fastest weekend of the year right outside the hotel windows. Grandstands, paddock and "
          "the main straight are minutes away on foot — no queues, no parking hunt. Tickets and "
          "programme on the official site.",
    "de": "Das schnellste Wochenende des Jahres direkt vor den Fenstern des Hotels. Tribünen, Paddock "
          "und Zielgerade sind wenige Gehminuten entfernt — ohne Stau und ohne Parkplatzsuche. "
          "Tickets und Programm auf der offiziellen Website.",
}

CTA = {"cs": "Program a vstupenky →", "en": "Programme and tickets →", "de": "Programm und Tickets →"}

ODKAZ_TEXT = {
    "cs": "Oficiální web Grand Prix →",
    "en": "Official Grand Prix website →",
    "de": "Offizielle Website des Grand Prix →",
}

DETAIL = {"cs": "Detail zážitku", "en": "Experience detail", "de": "Erlebnis im Detail"}
WEB_TXT = {"cs": "Web MotoGP Czechia", "en": "MotoGP Czechia website", "de": "Website MotoGP Czechia"}

PEREX = {
    "cs": "Grand Prix České republiky je vrchol sezóny Masarykova okruhu — tři dny motorek, hluku a "
          "atmosféry, kterou z televize nepoznáte. GRID Hotel stojí přímo v areálu, takže od pokoje "
          "k tribuně to máte pár minut pěšky.",
    "en": "The Grand Prix of Czechia is the peak of the Masaryk Circuit season — three days of bikes, "
          "noise and an atmosphere television never captures. GRID Hotel stands inside the complex, so "
          "it is a few minutes on foot from your room to the grandstand.",
    "de": "Der Grand Prix von Tschechien ist der Höhepunkt der Saison auf dem Masaryk-Ring — drei Tage "
          "Motorräder, Lärm und eine Atmosphäre, die das Fernsehen nicht einfängt. Das GRID Hotel liegt "
          "direkt auf dem Gelände: vom Zimmer zur Tribüne sind es wenige Gehminuten.",
}

PARAMETRY = {
    "cs": "Kde=Masarykův okruh, Brno|Od hotelu=pěšky, hotel je v areálu|Délka=závodní víkend (3 dny)|"
          "Vstupenky=motogpczechia.com",
    "en": "Where=Masaryk Circuit, Brno|From the hotel=on foot, the hotel is inside the complex|"
          "Length=race weekend (3 days)|Tickets=motogpczechia.com",
    "de": "Wo=Masaryk-Ring, Brünn|Vom Hotel=zu Fuß, das Hotel liegt auf dem Gelände|"
          "Dauer=Rennwochenende (3 Tage)|Tickets=motogpczechia.com",
}

YOAST = {
    "cs": ("MotoGP Brno — Grand Prix České republiky | GRID HOTEL",
           "Grand Prix České republiky na Masarykově okruhu: tribuny, vstupenky, program víkendu a "
           "ubytování přímo v areálu okruhu. Od pokoje k tribuně pěšky."),
    "en": ("MotoGP Brno — Grand Prix of Czechia | GRID HOTEL",
           "The Grand Prix of Czechia at the Masaryk Circuit: grandstands, tickets, race weekend "
           "programme and a hotel inside the circuit complex. Walk from your room to the stands."),
    "de": ("MotoGP Brünn — Grand Prix von Tschechien | GRID HOTEL",
           "Der Grand Prix von Tschechien auf dem Masaryk-Ring: Tribünen, Tickets, Programm des "
           "Rennwochenendes und ein Hotel direkt auf dem Gelände. Vom Zimmer zu Fuß zur Tribüne."),
}

# interní odkazy podle jazyka
INTERNI = {
    "cs": {"sezona": "/sezona/", "cekaci": "/sezona/#cekaci-list", "ubytovani": "/ubytovani/",
           "rezervace": "/rezervace/", "okruh": "/masarykuv-okruh/"},
    "en": {"sezona": "/en/season/", "cekaci": "/en/season/#cekaci-list", "ubytovani": "/en/accommodation/",
           "rezervace": "/en/reservation/", "okruh": "/en/masaryk-circuit/"},
    "de": {"sezona": "/de/saison/", "cekaci": "/de/saison/#cekaci-list", "ubytovani": "/de/unterkunft/",
           "rezervace": "/de/reservierung/", "okruh": "/de/masaryk-ring/"},
}


def _mapa(alt):
    return (f'<figure class="okruh-mapa"><img src="{MAPA}" alt="{alt}" loading="lazy" '
            f'width="760" height="475"></figure>')


def _text_popisu(l):
    o = INTERNI[l]
    if l == "cs":
        return f"""<h2>Proč sledovat MotoGP právě z GRID Hotelu</h2>
<p>Hotel stojí uvnitř areálu Automotodromu Brno. O závodním víkendu to znamená jediné: vyjdete z pokoje, projdete branou a jste u trati. Žádné ranní kolony na příjezdových silnicích, žádné hledání parkovacího místa, žádný spěch na poslední autobus. A když se dojede, jste zpátky na pokoji dřív, než se areál stihne vyprázdnit.</p>
<p>Kapacita hotelu je omezená a na Grand Prix bývá obsazená s velkým předstihem. Rezervujte proto co nejdřív — a pokud je už plno, přihlaste se do <a href="{o['cekaci']}">čekacího listu</a>, kam hlásíme uvolněné pokoje jako prvním.</p>

<h2>Co se na Masarykově okruhu jede</h2>
<p>Grand Prix České republiky je podnikem mistrovství světa silničních motocyklů. Závodní víkend má tři dny: v pátek se jezdí tréninky, v sobotu kvalifikace a sprint, v neděli hlavní závody všech tříd. Přesný program, startovní listiny i časy najdete vždy na <a href="{ZAVOD}" target="_blank" rel="noopener">oficiálním webu Grand Prix</a>.</p>
<p>Poslední ročník má i domácí příchuť: v roce 2026 vyhrál brněnský závod MotoGP Marc Márquez, přičemž na stupně vítězů se probojoval i český jezdec Filip Salač.</p>

<h2>Trať v číslech</h2>
<ul><li>délka <strong>5 403,19 m</strong>, šířka 15 m</li><li><strong>14 zatáček</strong> — 6 levých a 8 pravých</li><li>13 rovinek, nejdelší <strong>636,56 m</strong></li><li>převýšení: stoupání až 7,5 % na 917 metrech, klesání 5 % na 410 metrech</li><li>traťový rekord <strong>1:54,596</strong></li></ul>
{_mapa('Plánek Masarykova okruhu')}
<p>Delší povídání o historii trati — od okruhu dlouhého devětadvacet kilometrů po dnešní podobu — najdete na naší stránce <a href="{o['okruh']}">Masarykův okruh</a>.</p>

<h2>Kde sedět: tribuny a přírodní tribuny</h2>
<p>Krytá <strong>T3</strong> je nejoblíbenější sedací tribuna — schová vás před sluncem i deštěm a vidíte z ní rošt, boxy i pódium. <strong>T5</strong> stojí naproti boxové uličce, takže sledujete trať i práci týmů. <strong>T2</strong> je na startu a cíli s výhledem do první zatáčky. <strong>T1</strong>, <strong>T4</strong> a <strong>T6</strong> míří do stadionové části.</p>
<p>Z přírodních tribun je nejoblíbenější <strong>C</strong> — vidíte z ní čtyři zatáčky stadionu naráz. <strong>B</strong> leží u vjezdu do páté zatáčky, kde se tvrdě brzdí. <strong>D</strong> a <strong>E</strong> patří Schwantzově zatáčce v nejnižším bodě trati, <strong>F</strong> nabízí kamenné schody s výhledem na dvě zatáčky před závěrečným stoupáním a <strong>G</strong> lemuje celé stoupání až k cílové rovince.</p>
<p><a href="{TRIBUNY}" target="_blank" rel="noopener">Přehled všech tribun na oficiálním webu <span aria-hidden="true">↗</span></a> · <a href="{PLANEK}" target="_blank" rel="noopener">Plánek okruhu <span aria-hidden="true">↗</span></a></p>

<h2>Vstupenky</h2>
<ul><li>Vstupenky se prodávají výhradně přes <a href="{VSTUPNE}" target="_blank" rel="noopener">oficiální prodej</a>.</li><li>Návštěvníci předchozího ročníku mají věrnostní předprodej — stačí číslo vstupenky pod QR kódem.</li><li>Limit jsou 4 vstupenky na osobu.</li><li>Děti do 6 let mají vstup zdarma, pro děti do 14 let jsou juniorské vstupenky na tribuny T2, T4 a T6.</li></ul>
<p>Aktuální <a href="{CENIK}" target="_blank" rel="noopener">ceník vstupenek</a> se každý ročník mění — ceny i podmínky si vždy ověřte na oficiálním webu.</p>

<h2>Praktické informace k víkendu</h2>
<p>Dopravu, parkování, občerstvení i pravidla vstupu shrnuje sekce <a href="{NAVSTEVA}" target="_blank" rel="noopener">pro návštěvníky</a>. Hosté GRID Hotelu parkují zdarma přímo v areálu a snídani mají dřív, než se brány otevřou veřejnosti. Recepce je vám k dispozici nepřetržitě a poradí i s tím, kterou branou to máte k vaší tribuně nejblíž.</p>

<h2>Kde zážitek pořídíte</h2>
<ul><li><a href="{VSTUPNE}" target="_blank" rel="noopener">vstupenky.motogpczechia.com</a> — vstupenky na Grand Prix</li><li><a href="{WEB}" target="_blank" rel="noopener">motogpczechia.com</a> — program, novinky, informace pro návštěvníky</li><li><a href="{AMD}" target="_blank" rel="noopener">automotodrombrno.cz</a> — provozovatel okruhu a zbytek závodní sezóny</li><li><a href="{o['rezervace']}">Rezervace pobytu v GRID Hotelu</a> — ubytování přímo v areálu</li></ul>
<p class="okruh-zdroje">Zdroje faktů: oficiální web Grand Prix a provozovatele okruhu. Termíny, ceny i program se mohou měnit — rozhodující jsou vždy oficiální stránky.</p>"""

    if l == "en":
        return f"""<h2>Why watch MotoGP from GRID Hotel</h2>
<p>The hotel stands inside the Brno Circuit complex. On a race weekend that means one thing: you leave your room, walk through the gate and you are trackside. No morning queues on the approach roads, no hunting for a parking space, no rush for the last bus. And when the race is over you are back in your room before the place has emptied.</p>
<p>The hotel is small and Grand Prix weekends sell out far in advance. Book as early as you can — and if we are full, join the <a href="{o['cekaci']}">waiting list</a>, where released rooms are offered first.</p>

<h2>What happens at the Masaryk Circuit</h2>
<p>The Grand Prix of Czechia is a round of the road racing world championship. The weekend runs over three days: practice on Friday, qualifying and the sprint on Saturday, the main races of every class on Sunday. The exact programme, entry lists and session times are always on the <a href="{ZAVOD}" target="_blank" rel="noopener">official Grand Prix website</a>.</p>
<p>The most recent edition had a home flavour: in 2026 Marc Márquez won the MotoGP race in Brno, and Czech rider Filip Salač made it onto the podium.</p>

<h2>The track in numbers</h2>
<ul><li>length <strong>5,403.19 m</strong>, width 15 m</li><li><strong>14 corners</strong> — 6 left and 8 right</li><li>13 straights, the longest <strong>636.56 m</strong></li><li>elevation: up to 7.5% climbing over 917 metres, 5% descent over 410 metres</li><li>lap record <strong>1:54.596</strong></li></ul>
{_mapa('Map of the Masaryk Circuit')}
<p>For the longer story of the track — from a twenty-nine-kilometre road circuit to today's layout — see our page on <a href="{o['okruh']}">the Masaryk Circuit</a>.</p>

<h2>Where to sit: grandstands and natural stands</h2>
<p>Covered <strong>T3</strong> is the most popular seated stand — shelter from sun and rain, with a view of the grid, the pit boxes and the podium. <strong>T5</strong> faces the pit lane, so you follow both the track and the teams at work. <strong>T2</strong> sits at start and finish looking into turn one. <strong>T1</strong>, <strong>T4</strong> and <strong>T6</strong> face the stadium section.</p>
<p>Among the natural stands, <strong>C</strong> is the favourite — four stadium corners in a single view. <strong>B</strong> overlooks the heavy braking into turn five. <strong>D</strong> and <strong>E</strong> belong to the Schwantz corner at the lowest point of the track, <strong>F</strong> offers stone steps facing the two corners before the final climb, and <strong>G</strong> follows the whole climb up to the finishing straight.</p>
<p><a href="{TRIBUNY}" target="_blank" rel="noopener">All grandstands on the official site <span aria-hidden="true">↗</span></a> · <a href="{PLANEK}" target="_blank" rel="noopener">Circuit map <span aria-hidden="true">↗</span></a></p>

<h2>Tickets</h2>
<ul><li>Tickets are sold only through the <a href="{VSTUPNE}" target="_blank" rel="noopener">official ticket shop</a>.</li><li>Visitors of the previous edition get a loyalty pre-sale — you need the ticket number under the QR code.</li><li>The limit is 4 tickets per person.</li><li>Children under 6 enter free; juniors up to 14 have their own tickets for stands T2, T4 and T6.</li></ul>
<p>The <a href="{CENIK}" target="_blank" rel="noopener">ticket price list</a> changes every year — always check prices and conditions on the official site.</p>

<h2>Practical information</h2>
<p>Travel, parking, catering and entry rules are summed up in the <a href="{NAVSTEVA}" target="_blank" rel="noopener">visitor section</a>. GRID Hotel guests park free inside the complex and have breakfast before the gates open to the public. Reception is staffed around the clock and will tell you which gate is closest to your stand.</p>

<h2>Where to arrange it</h2>
<ul><li><a href="{VSTUPNE}" target="_blank" rel="noopener">vstupenky.motogpczechia.com</a> — Grand Prix tickets</li><li><a href="{WEB}" target="_blank" rel="noopener">motogpczechia.com</a> — programme, news, visitor information</li><li><a href="{AMD}" target="_blank" rel="noopener">automotodrombrno.cz</a> — the circuit operator and the rest of the racing season</li><li><a href="{o['rezervace']}">Book a stay at GRID Hotel</a> — accommodation inside the complex</li></ul>
<p class="okruh-zdroje">Sources: the official Grand Prix and circuit websites. Dates, prices and the programme may change — the official sites always take precedence.</p>"""

    return f"""<h2>Warum MotoGP vom GRID Hotel aus</h2>
<p>Das Hotel steht auf dem Gelände des Automotodrom Brünn. An einem Rennwochenende bedeutet das: Sie verlassen Ihr Zimmer, gehen durch das Tor und stehen an der Strecke. Keine morgendlichen Staus auf den Zufahrtsstraßen, keine Parkplatzsuche, keine Hetze zum letzten Bus. Und nach dem Rennen sind Sie zurück im Zimmer, bevor sich das Gelände geleert hat.</p>
<p>Das Hotel ist klein und zum Grand Prix lange im Voraus ausgebucht. Buchen Sie deshalb so früh wie möglich — und wenn alles belegt ist, tragen Sie sich in die <a href="{o['cekaci']}">Warteliste</a> ein: frei gewordene Zimmer bieten wir dort zuerst an.</p>

<h2>Was auf dem Masaryk-Ring gefahren wird</h2>
<p>Der Grand Prix von Tschechien ist ein Lauf der Motorrad-Weltmeisterschaft. Das Wochenende dauert drei Tage: freitags Trainings, samstags Qualifying und Sprint, sonntags die Hauptrennen aller Klassen. Das genaue Programm, Starterlisten und Zeiten finden Sie stets auf der <a href="{ZAVOD}" target="_blank" rel="noopener">offiziellen Website des Grand Prix</a>.</p>
<p>Die letzte Ausgabe hatte auch eine heimische Note: 2026 gewann Marc Márquez das MotoGP-Rennen in Brünn, und der tschechische Fahrer Filip Salač schaffte es aufs Podium.</p>

<h2>Die Strecke in Zahlen</h2>
<ul><li>Länge <strong>5.403,19 m</strong>, Breite 15 m</li><li><strong>14 Kurven</strong> — 6 links und 8 rechts</li><li>13 Geraden, die längste <strong>636,56 m</strong></li><li>Höhenunterschied: bis zu 7,5 % Steigung über 917 Meter, 5 % Gefälle über 410 Meter</li><li>Streckenrekord <strong>1:54,596</strong></li></ul>
{_mapa('Streckenplan des Masaryk-Rings')}
<p>Die längere Geschichte der Strecke — vom neunundzwanzig Kilometer langen Kurs bis zur heutigen Form — lesen Sie auf unserer Seite <a href="{o['okruh']}">Der Masaryk-Ring</a>.</p>

<h2>Wo sitzen: Tribünen und Naturtribünen</h2>
<p>Die überdachte <strong>T3</strong> ist die beliebteste Sitztribüne — Schutz vor Sonne und Regen, dazu Blick auf Startaufstellung, Boxen und Podium. <strong>T5</strong> liegt gegenüber der Boxengasse, Sie verfolgen also Strecke und Teamarbeit zugleich. <strong>T2</strong> steht an Start und Ziel mit Blick in die erste Kurve. <strong>T1</strong>, <strong>T4</strong> und <strong>T6</strong> zeigen in den Stadionbereich.</p>
<p>Unter den Naturtribünen ist <strong>C</strong> die beliebteste — vier Stadionkurven auf einen Blick. <strong>B</strong> liegt an der Anbremszone der fünften Kurve. <strong>D</strong> und <strong>E</strong> gehören zur Schwantz-Kurve am tiefsten Punkt der Strecke, <strong>F</strong> bietet Steinstufen mit Blick auf die zwei Kurven vor dem Schlussanstieg, und <strong>G</strong> säumt den gesamten Anstieg bis zur Zielgeraden.</p>
<p><a href="{TRIBUNY}" target="_blank" rel="noopener">Alle Tribünen auf der offiziellen Website <span aria-hidden="true">↗</span></a> · <a href="{PLANEK}" target="_blank" rel="noopener">Streckenplan <span aria-hidden="true">↗</span></a></p>

<h2>Tickets</h2>
<ul><li>Tickets gibt es ausschließlich über den <a href="{VSTUPNE}" target="_blank" rel="noopener">offiziellen Verkauf</a>.</li><li>Besucher der vorherigen Ausgabe haben einen Treue-Vorverkauf — nötig ist die Ticketnummer unter dem QR-Code.</li><li>Das Limit liegt bei 4 Tickets pro Person.</li><li>Kinder unter 6 Jahren haben freien Eintritt, für Kinder bis 14 gibt es Junior-Tickets für die Tribünen T2, T4 und T6.</li></ul>
<p>Die <a href="{CENIK}" target="_blank" rel="noopener">Preisliste</a> ändert sich jedes Jahr — prüfen Sie Preise und Bedingungen immer auf der offiziellen Website.</p>

<h2>Praktisches zum Wochenende</h2>
<p>Anreise, Parken, Verpflegung und Einlassregeln fasst der <a href="{NAVSTEVA}" target="_blank" rel="noopener">Besucherbereich</a> zusammen. Gäste des GRID Hotels parken kostenlos auf dem Gelände und frühstücken, bevor die Tore für das Publikum öffnen. Die Rezeption ist rund um die Uhr besetzt und sagt Ihnen, welches Tor Ihrer Tribüne am nächsten liegt.</p>

<h2>Wo Sie das Erlebnis buchen</h2>
<ul><li><a href="{VSTUPNE}" target="_blank" rel="noopener">vstupenky.motogpczechia.com</a> — Tickets für den Grand Prix</li><li><a href="{WEB}" target="_blank" rel="noopener">motogpczechia.com</a> — Programm, Neuigkeiten, Besucherinfos</li><li><a href="{AMD}" target="_blank" rel="noopener">automotodrombrno.cz</a> — Betreiber der Strecke und die übrige Rennsaison</li><li><a href="{o['rezervace']}">Aufenthalt im GRID Hotel buchen</a> — Unterkunft direkt auf dem Gelände</li></ul>
<p class="okruh-zdroje">Quellen: die offiziellen Websites des Grand Prix und der Rennstrecke. Termine, Preise und Programm können sich ändern — maßgeblich sind stets die offiziellen Seiten.</p>"""


def popis(l):
    """Rozloží text detailu do matice 2 sloupců × 4 řádků (na úzkém displeji pod sebe).

    Bloky vznikají rozdělením textu podle nadpisů h2; plánek okruhu dostane
    vlastní buňku hned vedle bloku „Trať v číslech" a odstavec se zdroji zůstává
    pod mřížkou přes celou šířku.
    """
    import re

    html = _text_popisu(l)

    # odstavec se zdroji patří pod mřížku, ne do buňky
    zdroje = ""
    m = re.search(r'<p class="okruh-zdroje">.*?</p>', html, re.S)
    if m:
        zdroje = m.group(0)
        html = html.replace(zdroje, "")

    casti = [c.strip() for c in re.split(r'(?=<h2>)', html) if c.strip()]

    bloky = []
    for c in casti:
        m = re.search(r'<figure class="okruh-mapa">(.*?)</figure>', c, re.S)
        if m:
            # plánek okruhu je samostatná buňka matice
            bloky.append(c.replace(m.group(0), "").strip())
            vnitrek = m.group(1)
            bloky.append('<figure class="rd-blok rd-mapa okruh-mapa">' + vnitrek + '</figure>')
        else:
            bloky.append(c)

    bunky = "".join(b if b.startswith("<figure") else f'<section class="rd-blok">{b}</section>'
                    for b in bloky)
    return f'<div class="rd-mrizka">{bunky}</div>{zdroje}'
