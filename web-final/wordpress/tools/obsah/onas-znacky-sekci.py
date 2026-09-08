# -*- coding: utf-8 -*-
"""Značky sekcí T1–T4 na stránce „O nás" (a jazykových mutacích).

Podstránky mají stejný design jako titulní strana, ale sekce tu neměly ani
vodoznak (`.sec-tag`), ani kotvu. Doplňuje se obojí:
  * dvěma sekcím chybělo `id` — bez něj nejde ani odkaz, ani boční lišta,
  * do každé ze čtyř obsahových sekcí přibývá textový modul se značkou.

Poslední sekce (závěrečná výzva) značku nedostává — stejně jako CÍL na
titulní straně. Zapisuje se do struktury Divi, takže obojí zůstává
editovatelné ve Visual Builderu.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
STRANKY = (261, 400, 401)

# pořadí sekcí v obsahu → (kotva, značka); None = sekci vynechat
SEKCE = (
    ('o-hotelu',   'T1'),
    ('proc-grid',  'T2'),
    ('casosberne', 'T3'),
    ('okoli',      'T4'),
    (None,         None),   # závěrečná výzva
)


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def modul_znacky(znacka):
    return ('<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
            + LT + 'span class=' + QT + 'sec-tag' + QT + GT + znacka + LT + '/span' + GT
            + '"}}},"builderVersion":"5.9.0"} /-->')


def pridej_id(hlavicka, kotva, poradi):
    """Doplní atribut id do pole attributes v hlavičce sekce."""
    if '"name":"id"' in hlavicka:
        return hlavicka, False
    znak = '"targetElement":"main"}'
    i = hlavicka.find(znak)
    if i == -1:
        return hlavicka, False
    novy = (',{"id":"sec' + str(poradi) + 'id","name":"id","value":"' + kotva
            + '","adminLabel":"","targetElement":"main"}')
    return hlavicka[:i + len(znak)] + novy + hlavicka[i + len(znak):], True


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'sec-tag' in obsah:
        print('%d: značky už tam jsou' % pid); continue

    hlavicky = list(re.finditer(r'<!-- wp:divi/section .*?-->', obsah, re.S))
    if len(hlavicky) != len(SEKCE):
        print('%d: čekám %d sekcí, našel jsem %d — přeskakuji'
              % (pid, len(SEKCE), len(hlavicky))); continue

    # odzadu, ať nesypou offsety
    for poradi in range(len(SEKCE) - 1, -1, -1):
        kotva, znacka = SEKCE[poradi]
        if not znacka:
            continue
        m = hlavicky[poradi]
        hlavicka, doplneno = pridej_id(m.group(0), kotva, poradi)
        obsah = obsah[:m.start()] + hlavicka + modul_znacky(znacka) + obsah[m.end():]

    cesta = os.path.join(SC, 'znacky-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
