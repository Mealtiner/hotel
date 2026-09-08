# -*- coding: utf-8 -*-
"""Popisek sekce CÍL na střed — nastavením zarovnání samotného modulu Divi.

Modul textu neměl zarovnání nastavené, takže Divi vykreslovalo text-align:start
a popisek („CÍL · Cílová rovinka") seděl vlevo, i když zbytek sekce je na střed.
Zapisuje se do atributu modulu (module.advanced.text.text.desktop.value.orientation),
takže hodnota zůstává vidět i editovatelná ve Visual Builderu — neřeší se to
přebitím v CSS.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
ZNAK = 'justify-content:center;display:inline-flex'
STARY = '<!-- wp:divi/text {"content":'
NOVY  = ('<!-- wp:divi/text {"module":{"advanced":{"text":{"text":'
         '{"desktop":{"value":{"orientation":"center"}}}}}},"content":')
STRANKY = (99, 378, 379)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    i = obsah.find(ZNAK)
    if i == -1:
        print('%d: popisek nenalezen' % pid); continue
    j = obsah.rfind(STARY, 0, i)
    if j == -1:
        print('%d: blok modulu nenalezen' % pid); continue
    if 'orientation' in obsah[j:i]:
        print('%d: zarovnání už nastavené' % pid); continue

    obsah = obsah[:j] + NOVY + obsah[j + len(STARY):]
    cesta = os.path.join(SC, 'cil-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
