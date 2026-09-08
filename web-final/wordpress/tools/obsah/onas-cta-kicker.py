# -*- coding: utf-8 -*-
"""Popisek nad závěrečnou výzvou na stránce „O nás" na střed.

Stejný případ jako sekce CÍL na titulní straně: modul textu neměl nastavené
zarovnání, takže Divi vykreslilo text-align:start a popisek („Rezervace“)
seděl vlevo, i když je celá sekce na střed. Zapisuje se do atributu modulu,
takže hodnota zůstává editovatelná ve Visual Builderu.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
ZNAK  = 'justify-content:center;display:inline-flex'
STARY = '<!-- wp:divi/text {"content":'
NOVY  = ('<!-- wp:divi/text {"module":{"advanced":{"text":{"text":'
         '{"desktop":{"value":{"orientation":"center"}}}}}},"content":')
STRANKY = (261, 400, 401)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    i = obsah.find(ZNAK)
    while i != -1:
        j = obsah.rfind(STARY, 0, i)
        if j != -1 and 'orientation' not in obsah[j:i]:
            obsah = obsah[:j] + NOVY + obsah[j + len(STARY):]
            zmen += 1
            i += len(NOVY) - len(STARY)
        i = obsah.find(ZNAK, i + 1)
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'onas-cta-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d popisků —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
