# -*- coding: utf-8 -*-
"""Doplní do patičky odkaz na prohlášení o přístupnosti.

Zákon č. 424/2023 Sb. čeká, že prohlášení bude na webu dohledatelné —
patří tedy vedle ostatních právních odkazů, ve všech jazykových mutacích.
"""
import io, sys

SP = sys.argv[1]
B = chr(92)
Q = B + "u0022"
L = B + "u003c"
G = B + "u003e"

ODKAZY = {
    "cs": ("/prohlaseni-o-pristupnosti/", "Přístupnost", "/cookies/"),
    "en": ("/en/accessibility-statement/", "Accessibility", "/en/cookie-policy/"),
    "de": ("/de/erklaerung-zur-barrierefreiheit/", "Barrierefreiheit", "/de/cookie-richtlinie/"),
}

p = f"{SP}/footer-tb.txt"
s = io.open(p, encoding="utf-8").read()
pridano = 0

for lang, (url, popis, po_odkazu) in ODKAZY.items():
    novy = f"{L}a href={Q}{url}{Q}{G}{popis}{L}/a{G}"
    if novy in s:
        print(f"  {lang}: už je tam")
        continue
    kotva = f"{L}a href={Q}{po_odkazu}{Q}{G}"
    i = s.find(kotva)
    if i < 0:
        print(f"  {lang}: kotva {po_odkazu} nenalezena")
        continue
    konec = s.find(f"{L}/a{G}", i) + len(f"{L}/a{G}")
    s = s[:konec] + novy + s[konec:]
    pridano += 1
    print(f"  {lang}: odkaz „{popis}" + "“ doplněn")

io.open(p, "w", encoding="utf-8").write(s)
print("celkem doplněno:", pridano)
