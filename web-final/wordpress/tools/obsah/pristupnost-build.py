# -*- coding: utf-8 -*-
"""Sestaví Divi obsah stránky s prohlášením o přístupnosti."""
import io, json, sys, importlib.util

SP = sys.argv[1]
spec = importlib.util.spec_from_file_location("po", __file__.rsplit("/", 1)[0] + "/pristupnost-obsah.py")
O = importlib.util.module_from_spec(spec)
spec.loader.exec_module(O)

B = chr(92)


def divi(nazev, atributy, samostatny=True):
    j = json.dumps(atributy, ensure_ascii=False, separators=(",", ":"))
    j = (j.replace("<", B + "u003c").replace(">", B + "u003e")
          .replace(B + '"', B + "u0022").replace("&", B + "u0026"))
    return f"<!-- wp:divi/{nazev} {j} {'/' if samostatny else ''}-->"


for l in ("cs", "en", "de"):
    vnitrek = (
        '<section class="sec sec-light sec-pad grid-legal" style="padding-top:clamp(120px,16vh,180px)">'
        '<div class="wrap" style="max-width:820px">'
        f'<span class="kicker">{O.KICKER[l]}</span>'
        f'<h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 20px">{O.TITULEK[l]}</h1>'
        f'<div class="legal-body">{O.telo(l)}</div>'
        "</div></section>"
    )
    obsah = (
        divi("section", {"builderVersion": "5.9.0"}, False)
        + divi("row", {"module": {"advanced": {"columnStructure": {"desktop": {"value": "4_4"}}}},
                       "builderVersion": "5.9.0"}, False)
        + divi("column", {"module": {"advanced": {"type": {"desktop": {"value": "4_4"}}}},
                          "builderVersion": "5.9.0"}, False)
        + divi("text", {"content": {"innerContent": {"desktop": {"value": vnitrek}}},
                        "builderVersion": "5.9.0"})
        + "<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->"
    )
    io.open(f"{SP}/pristupnost-{l}.json", "w", encoding="utf-8").write(json.dumps({
        "slug": O.SLUG[l], "titul": O.TITULEK[l], "obsah": obsah,
        "seo_titul": O.SEO[l][0], "seo_popis": O.SEO[l][1],
    }, ensure_ascii=False))
    print(f"  {l}: {len(obsah)} znaků")
