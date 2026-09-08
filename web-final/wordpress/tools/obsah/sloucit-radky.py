# -*- coding: utf-8 -*-
"""Sloučí dva Divi řádky se stejnou třídou do jednoho.

Karty byly vysázené jako dva řádky po třech (resp. po dvou), takže se
nechovaly jako jedna mřížka — na užších rozlišeních se lámaly po blocích
místo plynule. Sloučením do jednoho řádku se o rozvržení stará jen CSS grid.
"""
import io, re, sys

SP = sys.argv[1]
TRIDA = sys.argv[2]
IDS = [int(x) for x in sys.argv[3].split(",")]

OTEVRENI = "<!-- wp:divi/row "
ZAVRENI = "<!-- /wp:divi/row -->"


def konec_radku(s, od):
    """Najde uzavírací značku řádku, který začíná na `od` — počítá vnořené řádky.

    Karty (např. .dp-airport) jsou samy vnořené Divi řádky, takže první
    nalezené `/wp:divi/row` patří kartě, ne mřížce. Bez počítání zanoření
    se sloupce druhé mřížky vložily dovnitř první karty.
    """
    hloubka = 1          # řádek, od kterého hledáme, je sám otevřený
    i = od
    while True:
        dalsi_o = s.find(OTEVRENI, i)
        dalsi_z = s.find(ZAVRENI, i)
        if dalsi_z < 0:
            return -1
        if 0 <= dalsi_o < dalsi_z:
            hloubka += 1
            i = dalsi_o + len(OTEVRENI)
            continue
        hloubka -= 1
        if hloubka == 0:
            return dalsi_z
        i = dalsi_z + len(ZAVRENI)


def radky_s_tridou(s, trida):
    """Vrátí (start, konec_otviraciho_tagu, start_zaviraciho, konec) pro každý řádek."""
    ven = []
    for m in re.finditer(re.escape(OTEVRENI) + r'\{.*?\} -->', s):
        blok = m.group(0)
        if f'"value":"{trida}"' not in blok:
            continue
        konec = konec_radku(s, m.start() + len(OTEVRENI))
        if konec < 0:
            continue
        ven.append((m.start(), m.end(), konec, konec + len(ZAVRENI)))
    return ven


for pid in IDS:
    p = f"{SP}/slouc-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()
    r = radky_s_tridou(s, TRIDA)
    if len(r) < 2:
        print(f"  #{pid}: řádků s třídou {TRIDA}: {len(r)} — nic ke slučování")
        continue
    prvni, druhy = r[0], r[1]
    vnitrek_druheho = s[druhy[1]:druhy[2]]
    # nejdřív se odstraní druhý řádek (je dál v textu, indexy prvního zůstanou platné)
    s = s[:druhy[0]] + s[druhy[3]:]
    s = s[:prvni[2]] + vnitrek_druheho + s[prvni[2]:]
    io.open(p, "w", encoding="utf-8").write(s)
    sloupcu = s[prvni[1]:prvni[2] + len(vnitrek_druheho)].count("<!-- wp:divi/column")
    print(f"  #{pid}: sloučeno, řádek má teď {sloupcu} sloupců")
