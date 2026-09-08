# -*- coding: utf-8 -*-
"""Horní odsazení sekce „Letecky" na stránce Jak se k nám dostanete.

Sekce měla nastavené `padding-top: 0` a jen spodní odsazení, takže se nadpis
lepil na předchozí sekci. Dostává stejnou hodnotu jako ostatní sekce webu.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
STRANKY = (238, 392, 393)
STARE = '"padding":{"top":"0px","right":"","bottom":"clamp(120px,16vh,180px)"'
NOVE  = '"padding":{"top":"clamp(120px,16vh,180px)","right":"","bottom":"clamp(120px,16vh,180px)"'


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    if STARE not in obsah:
        print('%d: beze změny' % pid); continue
    obsah = obsah.replace(STARE, NOVE)
    cesta = os.path.join(SC, 'doprava-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
