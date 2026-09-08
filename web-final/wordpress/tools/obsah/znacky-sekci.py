# -*- coding: utf-8 -*-
"""Vodoznaky sekcí (T1…Tx) na podstránky, které je ještě nemají.

Značka je textový modul se `span.sec-tag` na začátku sekce — stejně jako na
titulní straně a na už převedených podstránkách. Závěrečná výzva (`final`)
značku nedostává, drží se konvence zbytku webu.

Sekcím, které nemají kotvu (`id`), se zároveň doplní — bez ní na ně nejde
odkázat ani je vypsat v boční liště.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'

# stránka → kotvy pro sekce bez id (v pořadí výskytu); None = kotva už je
STRANKY = {
    232: (None, None, None),                    # Firemní akce & svatby (CZ)
    388: (None, None, None),
    389: (None, None, None),
    229: (None, None),                          # Sezóna
    386: (None, None),
    387: (None, None),
    337: (None, None),                          # Zážitky
    382: (None, None),
    383: (None, None),
    238: ('prijezd', 'automobilem', 'letecky'), # Jak se k nám dostanete
    392: ('prijezd', 'automobilem', 'letecky'),
    393: ('prijezd', 'automobilem', 'letecky'),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def modul(znacka):
    return ('<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
            + LT + 'span class=' + QT + 'sec-tag' + QT + GT + znacka + LT + '/span' + GT
            + '"}}},"builderVersion":"5.9.0"} /-->')


def doplnit_id(hlavicka, kotva, poradi):
    if kotva is None or '"name":"id"' in hlavicka:
        return hlavicka
    znak = '"targetElement":"main"}'
    i = hlavicka.find(znak)
    if i == -1:
        return hlavicka
    novy = (',{"id":"sek' + str(poradi) + 'id","name":"id","value":"' + kotva
            + '","adminLabel":"","targetElement":"main"}')
    return hlavicka[:i + len(znak)] + novy + hlavicka[i + len(znak):]


for pid, kotvy in STRANKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'sec-tag' in obsah:
        print('%d: značky už tam jsou' % pid); continue

    hlavicky = list(re.finditer(r'<!-- wp:divi/section .*?-->', obsah, re.S))
    # závěrečná výzva se pozná podle třídy „final" a značku nedostává
    obsahove = [m for m in hlavicky if '"value":"sec' in m.group(0) and ' final' not in m.group(0)]
    if len(obsahove) != len(kotvy):
        print('%d: čekám %d sekcí, našel jsem %d — přeskakuji' % (pid, len(kotvy), len(obsahove)))
        continue

    for poradi in range(len(obsahove) - 1, -1, -1):
        m = obsahove[poradi]
        hlavicka = doplnit_id(m.group(0), kotvy[poradi], poradi)
        obsah = obsah[:m.start()] + hlavicka + modul('T%d' % (poradi + 1)) + obsah[m.end():]

    cesta = os.path.join(SC, 'znacky-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d značek —' % (pid, len(obsahove)), wp('post', 'update', str(pid), cesta).strip())
