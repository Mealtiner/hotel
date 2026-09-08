# -*- coding: utf-8 -*-
"""Zalomení dlouhých kickerů na úzkém displeji.

„T1 · Ubytování · 60 pokojů & 4 apartmá" se na mobilu láme za slovem
uprostřed. Vloží se <span class="zlom-uzky">, který je na širokém rozlišení
mezera a na tabletu na výšku a na mobilu zalomí řádek.
"""
import io, sys

SP = sys.argv[1]
B = chr(92)
ZLOM = (B + 'u003cspan class=' + B + 'u0022zlom-uzky' + B + 'u0022' + B + 'u003e '
        + B + 'u003c/span' + B + 'u003e')

# id stránky → text, před kterým se má zalomit (uvnitř kickeru)
ZADANI = {
    220: "60 pokojů", 380: "60 rooms", 381: "60 Zimmer",
    226: "Restaurace", 384: "Restaurant", 385: "Restaurant",
}

for pid, pred in ZADANI.items():
    p = f"{SP}/kicker-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()
    hledej = "· " + pred
    if hledej not in s:
        print(f"  #{pid}: „{hledej}" + "“ nenalezeno — přeskočeno")
        continue
    if ZLOM in s:
        print(f"  #{pid}: už zalomeno")
        continue
    s = s.replace(hledej, "·" + ZLOM + pred, 1)
    io.open(p, "w", encoding="utf-8").write(s)
    print(f"  #{pid}: zalomeno před „{pred}" + "“")
