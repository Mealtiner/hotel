# -*- coding: utf-8 -*-
"""Pole „Typ pokoje" v rezervační liště → shortcode [grid_vyber_pokoje].

V obsahu titulních stránek byly možnosti napsané natvrdo a neodpovídaly
skutečným kategoriím pokojů. Nahradíme celý blok .bk-field shortcodem, který
si nabídku bere z taxonomie grid_room_cat v jazyce stránky.

Escape sekvence Divi obsahu se skládají přes chr(92), aby je výstup nepřevedl.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
STRANKY = (99, 378, 379)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    if '[grid_vyber_pokoje]' in obsah:
        print('%d: shortcode už tam je' % pid); continue

    i = obsah.find('bk-pokoj')
    if i == -1:
        print('%d: pole nenalezeno' % pid); continue

    # celý blok <div class="bk-field"> … </div> kolem výběru
    zacatek = obsah.rfind(LT + 'div class=' + QT + 'bk-field' + QT + GT, 0, i)
    konec   = obsah.find(LT + '/select' + GT, i)
    konec   = obsah.find(LT + '/div' + GT, konec) + len(LT + '/div' + GT)
    if zacatek == -1 or konec <= 0:
        print('%d: hranice bloku nenalezeny' % pid); continue

    obsah = obsah[:zacatek] + '[grid_vyber_pokoje]' + obsah[konec:]
    cesta = os.path.join(SC, 'rezervace-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
