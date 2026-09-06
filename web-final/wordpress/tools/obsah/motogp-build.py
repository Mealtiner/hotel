# -*- coding: utf-8 -*-
"""Vyexportuje obsah zážitku MotoGP do JSON souborů pro motogp-vytvorit.php."""
import io, json, sys, importlib.util

SP = sys.argv[1]
spec = importlib.util.spec_from_file_location(
    "motogp_obsah", __file__.rsplit("/", 1)[0] + "/motogp-obsah.py")
O = importlib.util.module_from_spec(spec)
spec.loader.exec_module(O)

for l in ("cs", "en", "de"):
    d = {
        "slug": O.SLUG[l],
        "titul": O.TITULEK[l],
        "num": O.NUM,
        "text": O.TEXT[l],
        "cta": O.CTA[l],
        "odkaz": O.WEB,
        "odkaz_text": O.ODKAZ_TEXT[l],
        "perex": O.PEREX[l],
        "parametry": O.PARAMETRY[l],
        "popis": O.popis(l),
        "seo_titul": O.YOAST[l][0],
        "seo_popis": O.YOAST[l][1],
    }
    io.open(f"{SP}/motogp-{l}.json", "w", encoding="utf-8").write(
        json.dumps(d, ensure_ascii=False, indent=1))
    print(f"  {l}: popis {len(d['popis'])} znaku")
