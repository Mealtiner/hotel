# -*- coding: utf-8 -*-
"""Sestaví obsah stránky Masarykův okruh (CZ/EN/DE) jako granulární Divi 5 bloky."""
import io, json, sys
SP = sys.argv[1]
sys.path.insert(0, SP)
import okruh_obsah as O

SVG = "/wp-content/themes/grid-divi5-child/assets/foto/"


def divi(nazev, atributy, samostatny=True):
    j = json.dumps(atributy, ensure_ascii=False, separators=(",", ":"))
    j = j.replace("<", "\\u003c").replace(">", "\\u003e")
    return f"<!-- wp:divi/{nazev} {j} {'/' if samostatny else ''}-->"


def atr(*dvojice):
    return {"desktop": {"value": {"attributes": [
        {"id": f"okr{i}", "name": jm, "value": h, "adminLabel": "", "targetElement": "main"}
        for i, (jm, h) in enumerate(dvojice)]}}}


def text(html):
    return divi("text", {"content": {"innerContent": {"desktop": {"value": html}}},
                         "builderVersion": "5.9.0"})


def nadpis(t, uroven="h2"):
    return divi("heading", {
        "title": {"innerContent": {"desktop": {"value": t}},
                  "decoration": {"font": {"font": {"desktop": {"value": {"headingLevel": uroven}}}}}},
        "builderVersion": "5.9.0"})


def sekce(kotva, trida, obsah, znacka=None, odsazeni=None):
    modul = {"decoration": {"attributes": atr(("class", trida), ("id", kotva))}}
    if odsazeni:
        modul["decoration"]["spacing"] = {"desktop": {"value": {"padding": {
            "top": odsazeni, "right": "", "bottom": "", "left": "",
            "syncVertical": "off", "syncHorizontal": "off"}}}}
    h = divi("section", {"module": modul, "builderVersion": "5.9.0"}, False)
    tag = text(f'<span class="sec-tag">{znacka}</span>') if znacka else ""
    r = divi("row", {"module": {
        "advanced": {"columnStructure": {"desktop": {"value": "4_4"}},
                     "flexColumnStructure": {"desktop": {"value": "equal-columns_1"}}},
        "decoration": {"layout": {"desktop": {"value": {"flexWrap": "nowrap", "justifyContent": "center"}}},
                       "sizing": {"desktop": {"value": {"width": "100%", "maxWidth": "100%"}}},
                       "attributes": atr(("class", "wrap"))}},
        "builderVersion": "5.9.0"}, False)
    s = divi("column", {"module": {"advanced": {"type": {"desktop": {"value": "4_4"}}},
                                   "decoration": {"sizing": {"desktop": {"value": {"flexType": "24_24"}}}}},
                        "builderVersion": "5.9.0"}, False)
    return (h + tag + r + s + obsah
            + "<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->")


def obsah_stranky(l):
    i = O.I[l]

    odkazy = "".join(
        f'<a class="sec-more" href="{u}" target="_blank" rel="noopener">{t} <span aria-hidden="true">&nearr;</span></a>'
        for t, u in O.T1["odkazy"][i])

    t1 = sekce("okruh", "sec sec-dark carbon sec-pad",
        text(f'<span class="kicker">{O.T1["kicker"][i]}</span>')
        + nadpis(O.TITULEK[i], "h1")
        + text('<div class="okruh-uvod">'
               + '<div class="okruh-uvod__text">' + "".join(O.T1["text"][i])
               + f'<div class="sec-more-radek">{odkazy}</div></div>'
               + f'<figure class="okruh-obrys"><img src="{SVG}grid-okruh-obrys.svg" alt="{O.TITULEK[i]}" loading="lazy" width="600" height="375"></figure>'
               + '</div>'), "T1", "clamp(120px,16vh,180px)")

    radky = "".join(
        f'<div><dt>{n[i]}</dt><dd>{h[i] if isinstance(h, tuple) else h}</dd></div>'
        for n, h in O.CISLA)
    t2 = sekce("draha", "sec sec-light sec-pad",
        text(f'<span class="kicker">{O.T2["kicker"][i]}</span>')
        + nadpis(O.T2["nadpis"][i])
        + text(f'<p class="sec-lead">{O.T2["perex"][i]}</p>')
        + text('<div class="okruh-draha">'
               + f'<dl class="okruh-cisla">{radky}</dl>'
               + f'<figure class="okruh-mapa"><img src="{SVG}grid-okruh-mapa.svg" alt="{O.T2["nadpis"][i]}" loading="lazy" width="760" height="475">'
               + '</figure></div>'), "T2")

    milniky = "".join(
        f'<li><span class="ok-rok">{rok}</span><h3>{nz[i]}</h3><p>{tx[i]}</p></li>'
        for rok, nz, tx in O.MILNIKY)
    t3 = sekce("historie", "sec sec-dark carbon sec-pad",
        text(f'<span class="kicker">{O.T3["kicker"][i]}</span>')
        + nadpis(O.T3["nadpis"][i])
        + text(f'<p class="sec-lead">{O.T3["perex"][i]}</p>')
        + text(f'<ol class="okruh-milniky">{milniky}</ol>'), "T3")

    vitezove = "".join(f'<li><span class="ok-rok">{r}</span><span>{j}</span></li>' for r, j in O.VITEZOVE)
    t4 = sekce("vitezove", "sec sec-light sec-pad",
        text(f'<span class="kicker">{O.T4["kicker"][i]}</span>')
        + nadpis(O.T4["nadpis"][i])
        + text(f'<p class="sec-lead">{O.T4["perex"][i]}</p>')
        + text(f'<ol class="okruh-vitezove">{vitezove}</ol>')
        + text(f'<div class="sec-more-radek"><a class="sec-more" href="{O.GP}sin-vitezu" target="_blank" rel="noopener">'
               + O.T4["kicker"][i].split("· ")[-1] + ' <span aria-hidden="true">&nearr;</span></a></div>'), "T4")

    t5 = sekce("hotel-u-trati", "sec sec-dark carbon sec-pad",
        text(f'<span class="kicker">{O.T5["kicker"][i]}</span>')
        + nadpis(O.T5["nadpis"][i])
        + text("".join(O.T5["text"][i]))
        + text(f'<p class="okruh-zdroje">{O.ZDROJE[i]}</p>'), "T5")

    return t1 + t2 + t3 + t4 + t5


for lang in ("cz", "en", "de"):
    s = obsah_stranky(lang)
    bad = tot = 0
    import re
    for m in re.finditer(r'<!-- wp:divi/[a-z-]+ (\{.*?\}) /?-->', s):
        tot += 1
        try: json.loads(m.group(1))
        except Exception: bad += 1
    io.open(f"{SP}/okruh-{lang}.txt", "w", encoding="utf-8").write(s)
    print(f"  {lang}: {len(s)} znaku, bloku {tot}, nevalidnich {bad}, sekci {s.count('wp:divi/section {')}")
