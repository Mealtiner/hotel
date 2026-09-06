# -*- coding: utf-8 -*-
"""Mřížka zážitků v sekci T4 → shortcode řízený přepínači v administraci.

Natvrdo vypsané karty (Divi sloupce s třídou exp-item) nahradí jedním textovým
modulem se shortcodem [grid_zazitky_karty]. Které zážitky se vypíšou, se pak
řídí u zážitku v administraci: „Zobrazit na homepage" pro titulní stránku
a „Zobrazit na stránce přehledu zážitků" pro stránku /zazitky/.
"""
import io, json, re, sys

SP = sys.argv[1]
B = chr(92)

STRANKY = [
    (99,  "homepage"), (378, "homepage"), (379, "homepage"),
    (337, "prehled"),  (382, "prehled"),  (383, "prehled"),
]


def divi_text(html):
    a = {"content": {"innerContent": {"desktop": {"value": html}}}, "builderVersion": "5.9.0"}
    j = json.dumps(a, ensure_ascii=False, separators=(",", ":"))
    j = (j.replace("<", B + "u003c").replace(">", B + "u003e")
          .replace(B + '"', B + "u0022").replace("&", B + "u0026"))
    return f"<!-- wp:divi/text {j} /-->"


for pid, misto in STRANKY:
    p = f"{SP}/karty-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()

    i = s.find('"name":"class","value":"exp"')
    if i < 0:
        print(f"  #{pid}: mřížka .exp nenalezena — přeskočeno")
        continue
    zacatek = s.rfind("<!-- wp:divi/row ", 0, i)
    konec = s.find("<!-- /wp:divi/row -->", i)
    if zacatek < 0 or konec < 0:
        print(f"  #{pid}: hranice řádku nenalezeny — přeskočeno")
        continue
    konec += len("<!-- /wp:divi/row -->")

    puvodni = s[zacatek:konec]
    if "exp-item" not in puvodni:
        print(f"  #{pid}: v nalezeném řádku nejsou karty — přeskočeno")
        continue
    karet = puvodni.count("exp-item")

    s = s[:zacatek] + divi_text(f'[grid_zazitky_karty misto="{misto}"]') + s[konec:]
    io.open(p, "w", encoding="utf-8").write(s)
    print(f"  #{pid} ({misto}): {karet} natvrdo vypsaných karet → shortcode")
