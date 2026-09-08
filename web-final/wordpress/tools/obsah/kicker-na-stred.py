# -*- coding: utf-8 -*-
"""Popisek nad závěrečnou výzvou na střed (obecně, pro libovolnou stránku).

Modul textu s popiskem nemá nastavené zarovnání, takže Divi vykreslí
text-align:start a popisek zůstane vlevo i v sekci, která je jinak na střed.
Poznávacím znakem takového popisku je inline styl `justify-content:center`,
kterým se to kdysi zkoušelo řešit na samotném <span> (kde nic nedělá).

Zapisuje se do atributu modulu, takže hodnota zůstává editovatelná
ve Visual Builderu. Stránky se předávají jako argumenty.
"""
import io, os, subprocess, sys

SC = os.environ.get('SC', '.')
ZNAK  = 'justify-content:center;display:inline-flex'
STARY = '<!-- wp:divi/text {"content":'
NOVY  = ('<!-- wp:divi/text {"module":{"advanced":{"text":{"text":'
         '{"desktop":{"value":{"orientation":"center"}}}}}},"content":')


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for arg in sys.argv[1:]:
    pid   = int(arg)
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
    cesta = os.path.join(SC, 'kicker-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d popisků —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
