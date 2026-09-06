# -*- coding: utf-8 -*-
"""Titulní strana: rozšířené štítky pobytu a druhý odkaz na srovnávací tabulku.
   Ubytování: přestavba na pět sekcí s kotvami (T1–T5)."""
import io, re, sys, json
SP = sys.argv[1]
sys.path.insert(0, SP)
import ubytovani_obsah as U
import vybaveni as V

DOMU = {99: "cz", 378: "en", 379: "de"}
UBYT = {220: "cz", 380: "en", 381: "de"}


def divi(nazev, atributy, samostatny=True):
    """Divi blok. JSON skládá json.dumps — ruční počítání složených závorek
       ve víceúrovňové struktuře je zdroj tichých chyb: neplatný JSON WordPress
       přeloží na prázdné atributy, blok se vykreslí bez tříd a kotev a na
       stránce to není poznat."""
    j = json.dumps(atributy, ensure_ascii=False, separators=(",", ":"))
    # Divi ukládá HTML uvnitř hodnot jako \u003c / \u003e / \u0022 — jinak by
    # "-->" v obsahu předčasně ukončilo komentář bloku.
    j = j.replace("<", "\\u003c").replace(">", "\\u003e")
    return f"<!-- wp:divi/{nazev} {j} {'/' if samostatny else ''}-->"


def atr(*dvojice):
    """Vlastní atributy elementu (class, id) v Divi 5 tvaru."""
    return {"desktop": {"value": {"attributes": [
        {"id": f"ubyt{i}", "name": jmeno, "value": hodnota,
         "adminLabel": "", "targetElement": "main"}
        for i, (jmeno, hodnota) in enumerate(dvojice)
    ]}}}


def modul_text(html):
    return divi("text", {"content": {"innerContent": {"desktop": {"value": html}}},
                         "builderVersion": "5.9.0"})


def modul_nadpis(text, uroven="h2"):
    return divi("heading", {
        "title": {"innerContent": {"desktop": {"value": text}},
                  "decoration": {"font": {"font": {"desktop": {"value": {"headingLevel": uroven}}}}}},
        "builderVersion": "5.9.0"})


def sekce(kotva, trida, obsah, poradi):
    """Divi sekce s kotvou a třídou, uvnitř jeden řádek s jedním sloupcem."""
    hlavicka = divi("section", {
        "module": {"decoration": {"attributes": atr(("class", trida), ("id", kotva))}},
        "builderVersion": "5.9.0"}, samostatny=False)
    radek = divi("row", {
        "module": {
            "advanced": {"columnStructure": {"desktop": {"value": "4_4"}},
                         "flexColumnStructure": {"desktop": {"value": "equal-columns_1"}}},
            "decoration": {
                "layout": {"desktop": {"value": {"flexWrap": "nowrap", "justifyContent": "center"}}},
                "sizing": {"desktop": {"value": {"width": "100%", "maxWidth": "100%"}}},
                "attributes": atr(("class", "wrap"))}},
        "builderVersion": "5.9.0"}, samostatny=False)
    sloupec = divi("column", {
        "module": {"advanced": {"type": {"desktop": {"value": "4_4"}}},
                   "decoration": {"sizing": {"desktop": {"value": {"flexType": "24_24"}}}}},
        "builderVersion": "5.9.0"}, samostatny=False)
    return (hlavicka + radek + sloupec + obsah
            + "<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->")


# ---------------------------------------------------------------- titulní strana
for pid, lang in DOMU.items():
    s = io.open(f"{SP}/dom-{pid}.txt", encoding="utf-8").read()
    puv = len(s)

    # 1) štítky pobytu
    m = re.search(r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
                  r'\\u003cdiv class=\\u0022pobyt-chips\\u0022[^"]*"\}\}\},'
                  r'"builderVersion":"[^"]*"\} /-->', s)
    if m:
        s = s[:m.start()] + modul_text(U.chips_html(lang)) + s[m.end():]
    else:
        print(f"  {pid}: VAROVANI — pobyt-chips nenalezeny")

    # 2) dvojice odkazů místo jednoho
    m = re.search(r'<!-- wp:divi/text \{"content":\{"innerContent":\{"desktop":\{"value":"'
                  r'(?:\\u003cdiv class=\\u0022sec-more-radek\\u0022)?[^"]*?'
                  r'\\u003ca class=\\u0022sec-more\\u0022[^"]*"\}\}\},'
                  r'"builderVersion":"[^"]*"\} /-->', s)
    if m:
        s = s[:m.start()] + modul_text(U.odkazy_html(lang)) + s[m.end():]
    else:
        print(f"  {pid}: VAROVANI — odkaz sec-more nenalezen")

    io.open(f"{SP}/dom-out-{pid}.txt", "w", encoding="utf-8").write(s)
    print(f"  titulní {pid} ({lang}): {puv} -> {len(s)}, chipsu={s.count('u003cspan')}")


# ---------------------------------------------------------------- Ubytování
for pid, lang in UBYT.items():
    s = io.open(f"{SP}/ub-{pid}.txt", encoding="utf-8").read()
    puv = len(s)
    i = U.I[lang]
    S = U.SEKCE

    # závěrečná sekce #cil zůstává beze změny — najdeme ji a ponecháme
    z = s.find('"value":"sec sec-dark final"')
    if z == -1:
        print(f"  {pid}: CHYBA — závěrečná sekce nenalezena")
        continue
    zacatek_cil = s.rfind('<!-- wp:divi/section', 0, z)
    zaver = s[zacatek_cil:]

    t1 = sekce("pokoje", "sec sec-light sec-pad",
        modul_text(f'<span class="kicker">{S["pokoje"]["kicker"][i]}</span>')
        + modul_nadpis(S["pokoje"]["nadpis"][i])
        + modul_text(U.INTRO[i])
        + modul_text('<div class="amenity-uvod">' + V.top_html(lang) + '</div>')
        + modul_text('[grid_rooms_cards vse="1"]')
        + modul_text(U.chips_html(lang)), 1)

    t2 = sekce("prehled-pokoju", "sec sec-dark carbon sec-pad",
        modul_text(f'<span class="kicker">{S["prehled-pokoju"]["kicker"][i]}</span>')
        + modul_nadpis(S["prehled-pokoju"]["nadpis"][i])
        + modul_text("[grid_rooms_table]"), 2)

    t3 = sekce("srovnani-pokoju", "sec sec-light sec-pad",
        modul_text(f'<span class="kicker">{S["srovnani-pokoju"]["kicker"][i]}</span>')
        + modul_nadpis(S["srovnani-pokoju"]["nadpis"][i])
        + modul_text(f'<p class="sec-lead">{S["srovnani-pokoju"]["perex"][i]}</p>')
        + modul_text("[grid_rooms_compare]"), 3)

    t4 = sekce("vybaveni", "sec sec-dark carbon sec-pad",
        modul_text(f'<span class="kicker">{S["vybaveni"]["kicker"][i]}</span>')
        + modul_nadpis(S["vybaveni"]["nadpis"][i])
        + modul_text(V.skupiny_html(lang)), 4)

    t5 = sekce("dobre-vedet", "sec sec-light sec-pad",
        modul_text(f'<span class="kicker">{S["dobre-vedet"]["kicker"][i]}</span>')
        + modul_nadpis(S["dobre-vedet"]["nadpis"][i])
        + modul_text(U.dobre_vedet_html(lang)), 5)

    nova = t1 + t2 + t3 + t4 + t5 + zaver
    io.open(f"{SP}/ub-out-{pid}.txt", "w", encoding="utf-8").write(nova)
    print(f"  ubytování {pid} ({lang}): {puv} -> {len(nova)}, sekci={nova.count('wp:divi/section {')}, "
          f"kotev={len(re.findall(chr(34)+'name'+chr(34)+':'+chr(34)+'id'+chr(34), nova))}")
