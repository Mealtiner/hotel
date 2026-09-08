# -*- coding: utf-8 -*-
"""Sekce „Automobilem" na stránce Jak se k nám dostanete → řádky s kartami.

Původně to byl jeden dlouhý sloupec s trasami pod sebou. Nově:
  řádek 1 — nadpis a čtyři karty tras autem,
  řádek 2 — dva bloky vedle sebe (hromadná doprava, parkování a taxi),
            každý s vlastním nadpisem a dvěma kartami.
Na širokém rozlišení jsou tedy v obou řádcích čtyři karty vedle sebe.

Karta autobusu 402 dostává odkaz na jízdní řády IDS JMK (ověřená adresa,
konkrétní stránka linky na webu dopravce neexistuje).

Texty se přebírají z původního obsahu, nic se nepřepisuje.
"""
import io, json, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT, AMP = B + 'u003c', B + 'u003e', B + 'u0022', B + 'u0026'
JR = 'https://www.idsjmk.cz/zastavkove-jizdni-rady.html'

STRANKY = {
    'cs': (238, 'Parkování & taxi', 'Jízdní řády IDS JMK'),
    'en': (392, 'Parking & taxi',   'IDS JMK timetables'),
    'de': (393, 'Parken & Taxi',    'Fahrpläne IDS JMK'),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def esc(s):
    """HTML z původního obsahu zpět do escapovaného tvaru Divi."""
    return (s.replace('&', AMP).replace('<', LT).replace('>', GT).replace('"', QT))


def karta(k, odkaz=None, popisek=''):
    out = LT + 'article class=' + QT + 'dp-karta' + QT + GT
    out += LT + 'h3' + GT + esc(k['titul']) + LT + '/h3' + GT
    for odstavec in k['text']:
        out += LT + 'p' + GT + esc(odstavec) + LT + '/p' + GT
    if odkaz:
        out += (LT + 'a class=' + QT + 'sec-more' + QT + ' href=' + QT + odkaz + QT
                + ' target=' + QT + '_blank' + QT + ' rel=' + QT + 'noopener' + QT + GT
                + esc(popisek) + ' ' + LT + 'span aria-hidden=' + QT + 'true' + QT + GT
                + AMP + 'nearr;' + LT + '/span' + GT + LT + '/a' + GT)
    return out + LT + '/article' + GT


def blok(nadpis, karty, sloupcu):
    out = LT + 'div class=' + QT + 'dp-blok' + QT + GT
    out += LT + 'h2' + GT + esc(nadpis) + LT + '/h2' + GT
    out += LT + 'div class=' + QT + 'dp-mrizka dp-mrizka--' + str(sloupcu) + QT + GT
    out += ''.join(karty)
    return out + LT + '/div' + GT + LT + '/div' + GT


texty = json.load(io.open(os.path.join(SC, 'doprava-texty.json'), encoding='utf-8'))

for lang, (pid, nadpis_parkovani, popisek_jr) in STRANKY.items():
    bloky = texty[lang]
    auto  = bloky[0]                      # 4 trasy autem
    zbytek = bloky[1]['karty']            # 2× bus + parkování + taxi
    if len(auto['karty']) != 4 or len(zbytek) != 4:
        print('%d: nečekaný počet karet' % pid); continue

    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'dp-bloky' in obsah:
        print('%d: už přestavěno' % pid); continue

    html = (LT + 'div class=' + QT + 'dp-bloky' + QT + GT
            + blok(auto['nadpis'], [karta(k) for k in auto['karty']], 4)
            + LT + 'div class=' + QT + 'dp-radek2' + QT + GT
            + blok(bloky[1]['nadpis'],
                   [karta(zbytek[0], JR, popisek_jr), karta(zbytek[1])], 2)
            + blok(nadpis_parkovani, [karta(zbytek[2]), karta(zbytek[3])], 2)
            + LT + '/div' + GT + LT + '/div' + GT)

    # nahradí se vnitřek sekce; hlavička sekce i vodoznak T2 zůstávají
    zac = obsah.find('automobilem')
    zac = obsah.rfind('<!-- wp:divi/section', 0, zac)
    hlavicka_konec = obsah.find('-->', zac) + 3
    znacka_konec = obsah.find('/-->', hlavicka_konec) + 4      # modul s vodoznakem
    kon = obsah.find('<!-- /wp:divi/section -->', znacka_konec) + len('<!-- /wp:divi/section -->')

    novy = (obsah[:znacka_konec]
            + '<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}}},'
              '"decoration":{"attributes":{"desktop":{"value":{"attributes":[{"id":"dopr1","name":"class",'
              '"value":"wrap","adminLabel":"","targetElement":"main"}]}}}}},"builderVersion":"5.9.0"} -->'
            + '<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}}},"builderVersion":"5.9.0"} -->'
            + '<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"' + html + '"}}},'
              '"builderVersion":"5.9.0"} /-->'
            + '<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->'
            + obsah[kon:])

    cesta = os.path.join(SC, 'doprava-karty-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(novy)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
