# -*- coding: utf-8 -*-
"""Fotky na kartách gastro provozů (sekce T5) → náhledový obrázek CPT.

Nahradí natvrdo vloženy Divi obrázek shortcodem [grid_gastro_foto], který si
fotku bere z náhledového obrázku záznamu „Gastro provoz". Původní soubor
zůstává jako záloha pro případ, že náhledový obrázek není vyplněný.
"""
import io, json, re, sys

SP = sys.argv[1]
B = chr(92)

# soubor z původního layoutu → slug provozu (CPT grid_gastro)
PROVOZY = {
    "restaurace-paddock.jpg": "hotelova-restaurace",
    "catering-dezerty.jpg":   "paddock-restaurant",
    "catering-syry.jpg":      "grid-club",
}

STRANKY = (99, 378, 379, 226, 384, 385)


def divi_text(html):
    a = {"content": {"innerContent": {"desktop": {"value": html}}}, "builderVersion": "5.9.0"}
    j = json.dumps(a, ensure_ascii=False, separators=(",", ":"))
    j = (j.replace("<", B + "u003c").replace(">", B + "u003e")
          .replace(B + '"', B + "u0022").replace("&", B + "u0026"))
    return f"<!-- wp:divi/text {j} /-->"


for pid in STRANKY:
    p = f"{SP}/g-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()
    puvodni = s
    for m in list(re.finditer(r'<!-- wp:divi/image (\{.*?\}) /-->', s)):
        blok = m.group(0)
        if "g-img" not in blok:
            continue
        src = re.search(r'"src":"([^"]+)"', blok).group(1)
        soubor = src.rsplit("/", 1)[-1]
        provoz = PROVOZY.get(soubor)
        if not provoz:
            print(f"  #{pid}: neznámý soubor {soubor} — přeskočeno")
            continue
        s = s.replace(blok, divi_text(f'[grid_gastro_foto provoz="{provoz}" foto="{soubor}"]'), 1)
    io.open(p, "w", encoding="utf-8").write(s)
    print(f"  #{pid}: {puvodni.count('wp:divi/image')} obrázků → {s.count('grid_gastro_foto')} shortcodů")
