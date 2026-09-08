# -*- coding: utf-8 -*-
"""Sekce „Co máte na dosah“ → shortcode pluginu Turistické cíle.

Filtr i karty byly napsané natvrdo v obsahu stránky, takže se místa nedala
přidávat ani vypínat bez zásahu do obsahu. Nahrazuje se jedním shortcodem;
data (18 míst ve třech jazycích a 4 štítky) jsou převzatá 1 : 1 a spravují se
v GARRY nastavení → Turistické cíle.

Dopravní karty ve výchozích datech nejsou (podle zadání) — štítek Doprava
zůstává založený, aby šel kdykoli použít.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
STRANKY = (261, 400, 401)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'grid_turisticke_cile' in obsah:
        print('%d: shortcode už tam je' % pid); continue

    zac = obsah.find(LT + 'div class=' + QT + 'okoli-filtr' + QT)
    if zac == -1:
        print('%d: filtr nenalezen' % pid); continue
    # vyjmout od filtru po konec mřížky karet
    kon = obsah.find(LT + 'div class=' + QT + 'okoli-grid' + QT, zac)
    kon = obsah.rfind(LT + '/article' + GT, kon)
    kon = obsah.find(LT + '/div' + GT, kon) + len(LT + '/div' + GT)
    if kon <= zac:
        print('%d: konec mřížky nenalezen' % pid); continue

    obsah = obsah[:zac] + '[grid_turisticke_cile]' + obsah[kon:]
    cesta = os.path.join(SC, 'okoli-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
