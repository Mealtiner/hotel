# -*- coding: utf-8 -*-
"""Čísla karet v sekci T1 → značky sekcí, na které karta odkazuje.

Karty vedly pořadová čísla 01–04, ale míří na sekce T3, T4, T6 a T7. Popisek
teď odpovídá cíli. Mění se jen prefix uvnitř .e-num, ať to nesáhne na stejně
vypadající čísla jinde v obsahu.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
ZNACKA = 'e-num' + B + 'u0022' + B + 'u003e'
STRANKY = (99, 378, 379)
PREVOD  = (('01 / ', 'T3 / '), ('02 / ', 'T4 / '), ('03 / ', 'T6 / '), ('04 / ', 'T7 / '))


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    i = obsah.find(ZNACKA)
    while i != -1:
        j = i + len(ZNACKA)
        for staré, nové in PREVOD:
            if obsah[j:j + len(staré)] == staré:
                obsah = obsah[:j] + nové + obsah[j + len(staré):]
                zmen += 1
                break
        i = obsah.find(ZNACKA, j)
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 't1-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d karet —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
