# -*- coding: utf-8 -*-
"""Šířky textů na stránce Zážitky.

* Nadpis sekce zážitků měl strop 18ch — jde přes celou šířku jako ostatní
  nadpisy sekcí na webu.
* Úvodní text k dárkovým poukazům jde naopak na polovinu šířky, aby se
  dlouhý odstavec dobře četl.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
QT = B + 'u0022'
STRANKY = (337, 382, 383)

# (co hledat, čím nahradit) — pracuje se s escapovaným obsahem Divi
ZMENY = (
    ('font-size:clamp(2rem,4vw,3.6rem);margin-top:16px;max-width:18ch',
     'font-size:clamp(2rem,4vw,3.6rem);margin-top:16px'),
    ('p style=' + QT + 'color:var(' + B + 'u002d' + B + 'u002dmuted)' + QT,
     'p style=' + QT + 'color:var(' + B + 'u002d' + B + 'u002dmuted);max-width:50%' + QT),
)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    for stare, nove in ZMENY:
        if stare in obsah and nove not in obsah:
            pocet = obsah.count(stare)
            obsah = obsah.replace(stare, nove)
            zmen += pocet
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'sirky-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d míst —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
