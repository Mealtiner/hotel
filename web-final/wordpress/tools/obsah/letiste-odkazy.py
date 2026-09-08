# -*- coding: utf-8 -*-
"""Odkazy v kartách letišť: navigace do hotelu a web letiště.

Karty jsou poskládané z modulů Divi přímo v obsahu stránky. Do textového
modulu s dálniční známkou se doplní dvojice odkazů; navigace vede z daného
letiště k hotelu, druhý odkaz na oficiální web letiště.
"""
import io, os, subprocess, urllib.parse

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
HOTEL = 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín'

LETISTE = (
    ('25 km',      'Letiště Brno-Tuřany, Brno',         'https://www.brno-airport.cz/'),
    ('210 km',     'Letiště Václava Havla Praha',       'https://www.prg.aero/'),
    ('160–200 km', 'Flughafen Wien-Schwechat',          'https://www.viennaairport.com/'),
    ('150 km',     'Letisko M. R. Štefánika Bratislava', 'https://www.bts.aero/'),
)

POPISKY = {
    238: ('Navigovat do hotelu', 'Web letiště'),
    392: ('Navigate to the hotel', 'Airport website'),
    393: ('Zum Hotel navigieren', 'Website des Flughafens'),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def odkazy(origin, web, popisky):
    nav = ('https://www.google.com/maps/dir/?api=1&origin='
           + urllib.parse.quote(origin) + '&destination=' + urllib.parse.quote(HOTEL))
    def odkaz(url, text, sipka):
        return (LT + 'a class=' + QT + 'sec-more' + QT + ' href=' + QT + url + QT
                + ' target=' + QT + '_blank' + QT + ' rel=' + QT + 'noopener' + QT + GT
                + text + ' ' + LT + 'span aria-hidden=' + QT + 'true' + QT + GT + sipka
                + LT + '/span' + GT + LT + '/a' + GT)
    return (LT + 'span class=' + QT + 'dp-akce' + QT + GT
            + odkaz(nav, popisky[0], '&nearr;') + odkaz(web, popisky[1], '&nearr;')
            + LT + '/span' + GT)


for pid, popisky in POPISKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'dp-akce' in obsah:
        print('%d: odkazy už tam jsou' % pid); continue

    zmen = 0
    for vzdalenost, origin, web in LETISTE:
        i = obsah.find(GT + vzdalenost + LT)          # štítek se vzdáleností
        if i == -1:
            print('%d: karta %s nenalezena' % (pid, vzdalenost)); continue
        j = obsah.find('dp-vign', i)
        if j == -1:
            continue
        k = obsah.find(LT + '/span' + GT, j) + len(LT + '/span' + GT)
        obsah = obsah[:k] + odkazy(origin, web, popisky) + obsah[k:]
        zmen += 1

    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'letiste-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d karet —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
