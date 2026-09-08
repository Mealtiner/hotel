# -*- coding: utf-8 -*-
"""Číslování popisků sekcí na stránce „O nás" (T1–T5).

Popisky se sjednocují s vodoznaky sekcí a s body boční lišty, aby na sebe
odkazovaly stejnými čísly. Hledá se podle značky `kicker` v obsahu, takže
se to nedotkne stejných slov jinde na stránce.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
ZNAK = 'kicker' + B + 'u0022'          # class="kicker"
OTEV = B + 'u003e'                     # >
ZAV  = B + 'u003c'                     # <

STRANKY = {
    261: ('O hotelu', 'Proč GRID', 'Časosběr', 'Okolí hotelu', 'Rezervace'),
    400: ('About the hotel', 'Why GRID', 'Time-lapse', 'The area around the hotel', 'Reservations'),
    401: ('Über das Hotel', 'Warum GRID', 'Zeitraffer', 'Die Umgebung des Hotels', 'Reservierung'),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid, popisky in STRANKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    for poradi, popisek in enumerate(popisky, start=1):
        stary = OTEV + popisek + ZAV
        novy  = OTEV + 'T%d · %s' % (poradi, popisek) + ZAV
        i = obsah.find(ZNAK)
        while i != -1:
            j = obsah.find(stary, i, i + 200)
            if j != -1:
                obsah = obsah[:j] + novy + obsah[j + len(stary):]
                zmen += 1
                break
            i = obsah.find(ZNAK, i + 1)
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'onas-kick-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d popisků —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
