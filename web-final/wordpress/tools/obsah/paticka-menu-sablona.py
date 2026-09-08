# -*- coding: utf-8 -*-
"""Sloupce „Hotel" a „Informace" v zápatí → menu WordPressu.

Seznamy byly napsané natvrdo přímo v šabloně zápatí (Divi Theme Builder),
takže se nedaly editovat jinak než v kódu. Nahrazují se shortcodem, který
vypíše menu přiřazené k pozici — pro každý jazyk to menu může být jiné.

Nahrazuje se jen samotný <ul>…</ul>, nadpis sloupce zůstává v šabloně.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT = B + 'u003c', B + 'u003e'
SABLONA = 106

# nadpis sloupce v jednotlivých jazycích → parametr shortcodu
SLOUPCE = (
    ('Hotel', 'hotel'),
    ('Informace', 'informace'),
    ('Information', 'informace'),
    ('Informationen', 'informace'),
)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


obsah = wp('post', 'get', str(SABLONA), '--field=content')
if 'grid_paticka_menu' in obsah:
    print('šablona už shortcode používá'); raise SystemExit

zmen = 0
for nadpis, sloupec in SLOUPCE:
    znacka = LT + 'h2' + GT + nadpis + LT + '/h2' + GT
    i = obsah.find(znacka)
    while i != -1:
        zac = obsah.find(LT + 'ul' + GT, i)
        kon = obsah.find(LT + '/ul' + GT, zac)
        if zac == -1 or kon == -1 or zac > i + 40:
            break
        kon += len(LT + '/ul' + GT)
        obsah = obsah[:zac] + '[grid_paticka_menu sloupec=' + sloupec + ']' + obsah[kon:]
        zmen += 1
        i = obsah.find(znacka, i + 1)

if not zmen:
    print('nenalezeno nic k nahrazení'); raise SystemExit

cesta = os.path.join(SC, 'paticka-sablona.txt')
io.open(cesta, 'w', encoding='utf-8').write(obsah)
print('%d sloupců —' % zmen, wp('post', 'update', str(SABLONA), cesta).strip())
