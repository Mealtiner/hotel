# -*- coding: utf-8 -*-
"""Doplní na stránky Ubytování kompletní vybavení hotelu z Booking.com
   a opraví počet kategorií pokojů (čtyři -> pět)."""
import io, re, sys, json
SP = sys.argv[1]
sys.path.insert(0, SP)
import vybaveni as V

STRANKY = {220: "cz", 380: "en", 381: "de"}
NADPIS = {
 "cz": ("64 pokojů a apartmá ve čtyřech kategoriích", "64 pokojů a apartmá v pěti kategoriích"),
 "en": ("64 rooms and apartments in four categories", "64 rooms and apartments in five categories"),
 "de": ("64 Zimmer und Appartements in vier Kategorien", "64 Zimmer und Appartements in fünf Kategorien"),
}
KICKER = {"cz": "Vybavení hotelu podrobně", "en": "Hotel amenities in detail",
          "de": "Hotelausstattung im Detail"}

def esc(html):
    """Divi ukládá HTML v JSON atributu — uvozovky musí být \\u0022, jinak se blok rozbije."""
    return html.replace('"', '\\u0022').replace('<', '\\u003c').replace('>', '\\u003e')

def text_modul(html):
    return ('<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
            + esc(html) + '"}}},"builderVersion":"5.9.0"} /-->')

vysledky = {}
for pid, lang in STRANKY.items():
    s = io.open(f"{SP}/vstup-{pid}.txt", encoding="utf-8").read()
    puv = s

    # 1) počet kategorií
    st, no = NADPIS[lang]
    if st in s:
        s = s.replace(st, no)

    # 2) přehledový výčet nahradit seznamem z Bookingu
    m = re.search(r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
                  r'\\u003cul class=\\u0022amenity-grid\\u0022[^"]*"\}\}\},'
                  r'"builderVersion":"[^"]*"\} /-->', s)
    if not m:
        print(f"  {pid}: VAROVANI — amenity-grid modul nenalezen")
        continue
    novy = text_modul(V.top_html(lang))

    # 3) za něj podskupiny s mezinadpisem
    kicker = ('<span class="kicker" style="margin-top:34px;display:inline-flex">'
              + KICKER[lang] + '</span>')
    blok = novy + text_modul(kicker) + text_modul(V.skupiny_html(lang))

    # pokud už tam skupiny jsou, jen je přepiš
    s = re.sub(r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
               r'\\u003cspan class=\\u0022kicker\\u0022[^"]*' + re.escape(esc(KICKER[lang])) +
               r'[^"]*"\}\}\},"builderVersion":"[^"]*"\} /-->'
               r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
               r'\\u003cdiv class=\\u0022amenity-groups\\u0022[^"]*"\}\}\},'
               r'"builderVersion":"[^"]*"\} /-->', '', s)
    m = re.search(r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
                  r'\\u003cul class=\\u0022amenity-grid\\u0022[^"]*"\}\}\},'
                  r'"builderVersion":"[^"]*"\} /-->', s)
    s = s[:m.start()] + blok + s[m.end():]

    io.open(f"{SP}/vystup-{pid}.txt", "w", encoding="utf-8").write(s)
    print(f"  {pid} ({lang}): {len(puv)} -> {len(s)} znaku, "
          f"skupin={s.count('amenity-groups')}, nadpis={'OK' if no in s else 'BEZE ZMENY'}")
