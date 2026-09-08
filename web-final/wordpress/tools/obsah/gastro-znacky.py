# -*- coding: utf-8 -*-
"""Vodoznaky T1 a T2 do prvních dvou sekcí Gastronomie.

Sekce s jídelníčky (T3–T5) si vodoznak přinesly už při rozdělení, závěrečná
výzva ho — stejně jako na ostatních stránkách — nemá.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
STRANKY = (226, 384, 385)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def modul(znacka):
    return ('<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
            + LT + 'span class=' + QT + 'sec-tag' + QT + GT + znacka + LT + '/span' + GT
            + '"}}},"builderVersion":"5.9.0"} /-->')


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    hlavicky = list(re.finditer(r'<!-- wp:divi/section .*?-->', obsah, re.S))
    zmen = 0
    for poradi in (1, 0):          # odzadu, ať nesypou offsety
        m = hlavicky[poradi]
        if obsah[m.end():m.end() + 200].find('sec-tag') != -1:
            continue
        obsah = obsah[:m.end()] + modul('T%d' % (poradi + 1)) + obsah[m.end():]
        zmen += 1
    if not zmen:
        print('%d: značky už tam jsou' % pid); continue
    cesta = os.path.join(SC, 'gz-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d značek —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
