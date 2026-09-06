# -*- coding: utf-8 -*-
"""Vloží prime kartu MotoGP na první místo do sekce T4 (mřížka .exp).

Používá se na titulní stránce i na stránce Zážitky ve všech třech jazycích.
HTML uvnitř Divi 5 bloků je uložené jako unicode escape sekvence, proto se
skládá přes chr(92).
"""
import io, json, sys, importlib.util

SP = sys.argv[1]
spec = importlib.util.spec_from_file_location(
    "motogp_obsah", __file__.rsplit("/", 1)[0] + "/motogp-obsah.py")
O = importlib.util.module_from_spec(spec)
spec.loader.exec_module(O)

B = chr(92)


def divi(nazev, atributy, samostatny=True):
    j = json.dumps(atributy, ensure_ascii=False, separators=(",", ":"))
    j = (j.replace("<", B + "u003c").replace(">", B + "u003e")
          .replace(B + '"', B + "u0022").replace("&", B + "u0026"))
    return f"<!-- wp:divi/{nazev} {j} {'/' if samostatny else ''}-->"


def atr(*dvojice):
    return {"desktop": {"value": {"attributes": [
        {"id": f"mgp{i}", "name": jm, "value": h, "adminLabel": "", "targetElement": "main"}
        for i, (jm, h) in enumerate(dvojice)]}}}


def text(html):
    return divi("text", {"content": {"innerContent": {"desktop": {"value": html}}},
                         "builderVersion": "5.9.0"})


def nadpis(t, uroven="h3"):
    return divi("heading", {
        "title": {"innerContent": {"desktop": {"value": t}},
                  "decoration": {"font": {"font": {"desktop": {"value": {"headingLevel": uroven}}}}}},
        "builderVersion": "5.9.0"})


def karta(lang, detail_url, s_odkazy=True):
    """Sloupec .exp-item--prime s odkazem na detail a na oficiální web MotoGP."""
    titul = O.TITULEK[lang]
    sl = divi("column", {"module": {
        "advanced": {"type": {"desktop": {"value": "1_6"}}},
        "decoration": {"sizing": {"desktop": {"value": {"flexType": "24_24"}}},
                       "attributes": atr(("class", "exp-item exp-item--prime"))}},
        "builderVersion": "5.9.0"}, False)
    hlava = (f'<a class="entry-link" href="{detail_url}" aria-label="{titul}"></a>'
             f'<span class="x-num">{O.NUM}</span><span class="x-prime">Prime</span>')
    telo = sl + text(hlava) + nadpis(titul) + text(f'<p>{O.TEXT[lang]}</p>')
    if s_odkazy:
        telo += text('<div class="exp-links">'
                     f'<a class="sec-more" href="{detail_url}">{O.DETAIL[lang]} '
                     '<span aria-hidden="true">&rarr;</span></a> '
                     f'<a class="sec-more" href="{O.WEB}" target="_blank" rel="noopener">'
                     f'{O.WEB_TXT[lang]} <span aria-hidden="true">&nearr;</span></a></div>')
    else:
        telo += text(f'<span class="x-link">{O.CTA[lang]}</span>')
    return telo + "<!-- /wp:divi/column -->"


def vloz(obsah, karta_html):
    """Vloží kartu hned za otevírací tag řádku s třídou exp."""
    znacka = B + 'u0022value' + B + 'u0022'   # jen pro čitelnost, nepoužito
    i = obsah.find('"name":"class","value":"exp"')
    if i < 0:
        i = obsah.find('"value":"exp",')
    if i < 0:
        raise SystemExit("řádek .exp nenalezen")
    j = obsah.find("-->", i)
    if j < 0:
        raise SystemExit("konec bloku řádku nenalezen")
    j += 3
    if 'exp-item--prime' in obsah:
        raise SystemExit("prime karta už tam je")
    return obsah[:j] + "\n" + karta_html + obsah[j:]


STRANKY = [
    # (id, jazyk, url detailu, jsou na kartách odkazy exp-links)
    (99,  "cs", "/zazitky/moto-gp/", True),
    (378, "en", "/en/zazitky/moto-gp-en/", True),
    (379, "de", "/de/zazitky/moto-gp-de/", True),
    (337, "cs", "/zazitky/moto-gp/", True),
    (382, "en", "/en/zazitky/moto-gp-en/", True),
    (383, "de", "/de/zazitky/moto-gp-de/", True),
]

for pid, lang, url, odkazy in STRANKY:
    p = f"{SP}/patch-{pid}.txt"
    s = io.open(p, encoding="utf-8").read()
    s2 = vloz(s, karta(lang, url, odkazy))
    io.open(p, "w", encoding="utf-8").write(s2)
    print(f"  #{pid} ({lang}): +{len(s2) - len(s)} znaku")
