# -*- coding: utf-8 -*-
"""Text tlačítka u gastro karet (T5) podle provozu: HOTEL MENU / PADDOCK MENU / CLUB MENU.

Dřív byl na všech třech kartách stejný text (Jídelní lístek / View menu / Speisekarte).
Název provozu je mezinárodní, takže se ve všech jazycích používá stejný.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
STRANKY = (99, 378, 379)           # titulní strana CZ / EN / DE
PODLE_KOTVY = (
    ('#jidelnicek-hotelova-restaurace', 'HOTEL MENU'),
    ('#jidelnicek-paddock-restaurant',  'PADDOCK MENU'),
    ('#jidelnicek-grid-club',           'CLUB MENU'),
)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    for kotva, text in PODLE_KOTVY:
        # {"text":"…","linkUrl":"…#kotva"} — text stojí těsně před odkazem
        klic = '","linkUrl":"'
        i = obsah.find(kotva)
        while i != -1:
            j = obsah.rfind('{"text":"', 0, i)
            k = obsah.find(klic, j)
            if j != -1 and k != -1 and k < i:
                stary = obsah[j + len('{"text":"'):k]
                if stary != text:
                    obsah = obsah[:j + len('{"text":"')] + text + obsah[k:]
                    zmen += 1
            i = obsah.find(kotva, i + 1)
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'gastro-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d tlačítek —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
