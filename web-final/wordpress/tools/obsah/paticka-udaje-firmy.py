# -*- coding: utf-8 -*-
"""Údaje o společnosti přesunout do prvního sloupce patičky.

Blok „GRH s.r.o. / IČ / spisová značka" visel pod newsletterem ve čtvrtém
sloupci, kam obsahově nepatří — patří ke značce a kontaktu v prvním sloupci.
Zpracovává všechny tři jazykové varianty v šabloně zápatí.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT = B + 'u003c', B + 'u003e'
SABLONA = 106
ZNACKA_KONEC = LT + '/p' + GT     # konec přesouvaného odstavce
KOTVA        = '[grid_socials]'   # konec prvního sloupce


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


obsah = wp('post', 'get', str(SABLONA), '--field=content')
bloky = []
for m in re.finditer(r'GRH s\.r\.o\.', obsah):
    zac = obsah.rfind(LT + 'p style=', 0, m.start())
    kon = obsah.find(ZNACKA_KONEC, m.start())
    if zac == -1 or kon == -1:
        continue
    bloky.append((zac, kon + len(ZNACKA_KONEC)))

if not bloky:
    print('nic k přesunu'); raise SystemExit

# odzadu, ať nesypou offsety: vyjmout a vložit za [grid_socials] téhož jazyka
for zac, kon in reversed(bloky):
    blok  = obsah[zac:kon]
    obsah = obsah[:zac] + obsah[kon:]
    cil = obsah.rfind(KOTVA, 0, zac)
    if cil == -1:
        print('první sloupec pro jeden z jazyků nenalezen'); raise SystemExit
    cil += len(KOTVA)
    obsah = obsah[:cil] + ' ' + blok + obsah[cil:]

cesta = os.path.join(SC, 'paticka-udaje.txt')
io.open(cesta, 'w', encoding='utf-8').write(obsah)
print('%d bloků —' % len(bloky), wp('post', 'update', str(SABLONA), cesta).strip())
