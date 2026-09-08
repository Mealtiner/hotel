# -*- coding: utf-8 -*-
"""Textová alternativa k plánku okruhu (WCAG 1.1.1 – složitý obrázek).

Plánek grid-okruh-mapa.svg nese čísla zatáček jen jako kresbu. Doplníme proto
popisný alt a pod obrázek figcaption, který obsah plánku říká slovy.
Escape sekvence v obsahu Divi se skládají přes chr(92), aby se nepřevedly.
"""
import io, os, subprocess, sys

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'

STRANKY = {
    1018: dict(
        alt='Plánek Masarykova okruhu s očíslovanými zatáčkami 1 až 14',
        popis='Zatáčky jsou na plánku očíslované 1 až 14 ve směru jízdy. Vyznačený je '
              'také start (S), cílová rovinka (FL) a tři měřicí body mezičasů (i1, i2, i3). '
              'Číselné parametry trati najdete v přehledu vedle plánku.'),
    1019: dict(
        alt='Plan of the Masaryk Circuit with turns numbered 1 to 14',
        popis='Turns are numbered 1 to 14 in the direction of travel. The plan also marks '
              'the start (S), the finish line (FL) and three intermediate timing points '
              '(i1, i2, i3). The figures for the track are in the list next to the plan.'),
    1020: dict(
        alt='Plan des Masaryk-Rings mit den Kurven 1 bis 14',
        popis='Die Kurven sind in Fahrtrichtung von 1 bis 14 nummeriert. Eingezeichnet sind '
              'außerdem der Start (S), die Zielgerade (FL) und drei Zwischenzeitpunkte '
              '(i1, i2, i3). Die Streckendaten stehen in der Übersicht neben dem Plan.'),
}

def wp(*args):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(args),
                          capture_output=True, text=True).stdout

for pid, d in STRANKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'grid-okruh-mapa' not in obsah:
        print('%d: plánek nenalezen' % pid); continue
    if 'okruh-mapa-popis' in obsah:
        print('%d: popis už tam je' % pid); continue

    stary_alt = 'alt=' + QT
    i = obsah.find('grid-okruh-mapa.svg')
    j = obsah.find(stary_alt, i)
    k = obsah.find(QT, j + len(stary_alt))
    obsah = obsah[:j + len(stary_alt)] + d['alt'] + obsah[k:]

    zavrit = LT + '/figure' + GT
    m = obsah.find(zavrit, i)
    popis = (LT + 'figcaption class=' + QT + 'okruh-mapa-popis' + QT + GT
             + d['popis'] + LT + '/figcaption' + GT)
    obsah = obsah[:m] + popis + obsah[m:]

    cesta = os.path.join(SC, 'okruh-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
