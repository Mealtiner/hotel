#!/usr/bin/env python3
"""Vyrobí obsah jazykových stránek KLONOVÁNÍM funkční Divi 5 struktury CZ stránek:
v klonu se jen vymění innerContent text modulů za přeložené chunky (v pořadí).

Vstupy:  ../d5-new/<ID>.txt   (aktuální FUNKČNÍ obsah CZ stránek po prvním nasazení)
         ../d5-dump/11.txt, 106.txt (originální TB dumpy s [grid_header]/[grid_footer])
         chunks-en/, chunks-de/, manifest.json, langmap.py
Výstupy: d5patch-<lang>/<page-key>.txt, tb-patched/{header,footer}.txt
"""
import json, re, sys
from pathlib import Path
from langmap import PAGES, GALERIE_SC, url_map

BASE = Path(__file__).parent
SCRATCH = BASE.parent
MANIFEST = json.loads((BASE / "manifest.json").read_text())
BLOCK_RE = re.compile(r"<!-- wp:divi/text (\{.*?\}) /-->", re.S)

def esc(s: str) -> str:
    return (s.replace("&", "\\u0026").replace("<", "\\u003c")
             .replace(">", "\\u003e").replace("--", "\\u002d\\u002d"))

def minify(h: str) -> str:
    h = re.sub(r"<!--.*?-->", "", h, flags=re.S)
    return re.sub(r"\s*\n\s*", " ", h).strip()

def rewrite_urls(html: str, lang: str) -> str:
    for a, b in url_map(lang).items():
        html = html.replace(a, b)
    html = html.replace('href="/#', f'href="/{lang}/#')
    html = re.sub(r'href="/"', f'href="/{lang}/"', html)
    return html

def lang_switch(html: str) -> str:
    html = re.sub(r'<a href="[^"]*"( class="active")?>CZ</a>', r'<a href="/"\1>CZ</a>', html)
    html = re.sub(r'<a href="[^"]*"( class="active")?>EN</a>', r'<a href="/en/"\1>EN</a>', html)
    html = re.sub(r'<a href="[^"]*"( class="active")?>DE</a>', r'<a href="/de/"\1>DE</a>', html)
    return html

def patch_content(src: str, replacements, galerie_sc=None):
    """replacements: fronta nových innerContent hodnot pro grid-html moduly."""
    queue = list(replacements)

    def sub(m):
        js = json.loads(m.group(1))
        try:
            val = js["content"]["innerContent"]["desktop"]["value"]
        except (KeyError, TypeError):
            return m.group(0)
        v = val.strip()
        if v.startswith("[grid_galerie"):
            if galerie_sc is None:
                return m.group(0)
            js["content"]["innerContent"]["desktop"]["value"] = galerie_sc
        elif v.startswith("["):          # jiný widget shortcode — nech
            return m.group(0)
        else:                             # statické HTML → další překlad z fronty
            if not queue:
                raise SystemExit("Fronta překladů je prázdná — nesoulad modulů!")
            js["content"]["innerContent"]["desktop"]["value"] = queue.pop(0)
        blob = esc(json.dumps(js, ensure_ascii=False, separators=(",", ":")))
        return f"<!-- wp:divi/text {blob} /-->"

    out = BLOCK_RE.sub(sub, src)
    if queue:
        raise SystemExit(f"Zbylo {len(queue)} nespotřebovaných překladů — nesoulad modulů!")
    return out

# ---- jazykové stránky (cs = jen refresh obsahu bez přepisu URL) ----
for lang in ("cs", "en", "de"):
    outdir = BASE / f"d5patch-{lang}"
    outdir.mkdir(exist_ok=True)
    for key, (cz_id, langs) in PAGES.items():
        src_f = SCRATCH / "d5-new" / f"{cz_id}.txt"
        if not src_f.exists():
            src_f = SCRATCH / "d5-dump" / f"{cz_id}.txt"   # galerie se v 1. vlně nepatchovala
        src = src_f.read_text()
        if key == "page-galerie":
            body = patch_content(src, [], galerie_sc=GALERIE_SC.get(lang))
        else:
            chunks = []
            for ck in MANIFEST[key]:
                h = minify((BASE / f"chunks-{lang}" / f"{ck}.html").read_text())
                if lang != "cs":
                    h = rewrite_urls(h, lang)
                chunks.append(h)
            body = patch_content(src, chunks)
        (outdir / f"{key}.txt").write_text(body)
    print(f"{lang}: {len(PAGES)} stránek → {outdir}")

# ---- TB hlavička/patička: 3 varianty do PŮVODNÍ struktury ----
tbdir = BASE / "tb-patched"
tbdir.mkdir(exist_ok=True)
for tb_id, part, chunk in ((11, "header", "grid_header__eee6c70506"), (106, "footer", "grid_footer__cfc617614b")):
    src = (SCRATCH / "d5-dump" / f"{tb_id}.txt").read_text()   # originál s [grid_header]
    variants = []
    for lang, d in (("cs", "chunks-cs"), ("en", "chunks-en"), ("de", "chunks-de")):
        html = minify((BASE / d / f"{chunk}.html").read_text())
        if lang != "cs":
            html = rewrite_urls(html, lang)
        html = lang_switch(html)
        variants.append(f'<div class="grid-lang grid-lang-{lang}">{html}</div>')
    combined = " ".join(variants)

    def sub(m):
        js = json.loads(m.group(1))
        js["content"]["innerContent"]["desktop"]["value"] = combined
        try:
            for attr in js["module"]["decoration"]["attributes"]["desktop"]["value"]["attributes"]:
                if attr.get("name") == "class":
                    attr["value"] = attr["value"].replace("grid-shortcode", "grid-html")
        except (KeyError, TypeError):
            pass
        return f"<!-- wp:divi/text {esc(json.dumps(js, ensure_ascii=False, separators=(',', ':')))} /-->"

    (tbdir / f"{part}.txt").write_text(BLOCK_RE.sub(sub, src))
    print(f"TB {part} ({tb_id}) → tb-patched/")
