# -*- coding: utf-8 -*-
"""Vyexportuje obsah zážitků do JSON pro zazitky-vytvorit.php."""
import io, json, sys, importlib.util

SP = sys.argv[1]
spec = importlib.util.spec_from_file_location(
    "zazitky_obsah", __file__.rsplit("/", 1)[0] + "/zazitky-obsah.py")
O = importlib.util.module_from_spec(spec)
spec.loader.exec_module(O)

# zážitky vypsané na titulní stránce (mřížka 3×2 vedle prime karty MotoGP)
HOMEPAGE = {"jizdy-verejnosti", "motokary-pitbike", "motoskola",
            "jizda-v-supersportu", "skola-smyku-polygon-brno", "darkove-poukazy"}

SEO = {
    "cs": ("{} | GRID HOTEL", "{} — {} Ubytování přímo v areálu Masarykova okruhu."),
    "en": ("{} | GRID HOTEL", "{} — {} Accommodation inside the Masaryk Circuit complex."),
    "de": ("{} | GRID HOTEL", "{} — {} Unterkunft direkt am Masaryk-Ring."),
}

ven = []
for poradi, z in enumerate(O.ZAZITKY, start=1):
    for l in ("cs", "en", "de"):
        titul = z["titul"][l]
        popis_seo = z["text"][l]
        if len(popis_seo) > 110:
            popis_seo = popis_seo[:107].rsplit(" ", 1)[0] + "…"
        ven.append({
            "slug": z["slug"] if l == "cs" else f'{z["slug"]}-{l}',
            "lang": l,
            "poradi": poradi,
            "titul": titul,
            "num": z["num"],
            "text": z["text"][l],
            "cta": z["cta"][l],
            "odkaz": z["odkaz"],
            "odkaz_text": z["odkaz_text"][l],
            "perex": z["perex"][l],
            "parametry": z["parametry"][l],
            "popis": O.popis(z, l),
            "doporuceno": 1 if z["slug"] in HOMEPAGE else 0,
            "v_prehledu": 1,
            "seo_titul": SEO[l][0].format(titul),
            "seo_popis": SEO[l][1].format(titul, popis_seo),
            "skupina": z["slug"],
        })

io.open(f"{SP}/zazitky.json", "w", encoding="utf-8").write(
    json.dumps(ven, ensure_ascii=False, indent=1))
print(f"  zapsáno {len(ven)} záznamů ({len(O.ZAZITKY)} zážitků × 3 jazyky)")
