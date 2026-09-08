# -*- coding: utf-8 -*-
"""Zalomení v nadpisech: <br> → <span class="zlom"> </span>.

Natvrdo vložené <br> je psané pro široký layout. Na mobilu se nadpis má lámat
po slovech přes celou šířku, jenže samotné skrytí <br> slepí slova dohromady
(„Jediný hoteluvnitř trati"). Span se na širokém rozlišení chová jako zalomení
(display:block) a na úzkém jako obyčejná mezera — bez zásahu do textu.
"""
import io, re, sys

SP = sys.argv[1]
IDS = [int(x) for x in sys.argv[2].split(",")]
B = chr(92)
BR = B + "u003cbr" + B + "u003e"
BR2 = B + "u003cbr /" + B + "u003e"
ZLOM = (B + 'u003cspan class=' + B + 'u0022zlom' + B + 'u0022' + B + 'u003e '
        + B + 'u003c/span' + B + 'u003e')

for pid in IDS:
    p = f"{SP}/nadpis-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()
    puvodni = s
    # jen uvnitř nadpisových bloků, ne v textech
    def uprav(m):
        return m.group(0).replace(BR2, ZLOM).replace(BR, ZLOM)
    s = re.sub(r'<!-- wp:divi/heading \{.*?\} /-->', uprav, s)
    n = puvodni.count(BR) + puvodni.count(BR2) - (s.count(BR) + s.count(BR2))
    io.open(p, "w", encoding="utf-8").write(s)
    print(f"  #{pid}: nahrazeno {n} zalomení")
