#!/usr/bin/env python3
"""Složí vyrenderované HTML sekce do Divi JSON layoutů.

Každý top-level element (section/header/footer/div…) = jedna Divi sekce
s jedním Text modulem (module_class=grid-html, admin_label dle obsahu).
"""
import json, re, sys, html as htmllib
from pathlib import Path

SRC = Path(__file__).parent / "out"
DST = Path(__file__).parent.parent / "divi-json"

VOID = {"img","input","br","hr","meta","source","link","area","base","col","embed","track","wbr","path","circle","rect","line","polyline","polygon","ellipse","stop","use"}

GLOBAL_COLORS = [
    ["gcid-red",   {"color": "#C20E1A", "active": "yes"}],
    ["gcid-gold",  {"color": "#CAA75F", "active": "yes"}],
    ["gcid-graph", {"color": "#0E0F11", "active": "yes"}],
    ["gcid-card",  {"color": "#16181B", "active": "yes"}],
    ["gcid-paper", {"color": "#F4F2F0", "active": "yes"}],
    ["gcid-grey",  {"color": "#B9B7B9", "active": "yes"}],
]

TAG_RE = re.compile(r"<(/?)([a-zA-Z][a-zA-Z0-9-]*)((?:\"[^\"]*\"|'[^']*'|[^>\"'])*)(/?)>")
COMMENT_RE = re.compile(r"<!--.*?-->", re.S)

# Hezká jména sekcí podle id / class / obsahu
ID_LABELS = {
    "start": "HERO — úvod", "booking": "Rezervační lišta", "vstupy": "T1 — Vstupy podle motivace",
    "pribeh": "T2 — Příběh místa", "pokoje": "T3 — Pokoje", "zazitky": "T4 — Zážitky",
    "restaurace": "T5 — Gastronomie", "sezona": "T6 — Sezóna 2026 + čekací list",
    "firemni": "T7 — Firemní akce & svatby", "duvera": "T8 — Reference",
    "cil": "CÍL — závěrečné CTA", "kontakt": "Patička — kontakty",
    "topbar": "Hlavička — logo & menu", "poukazy": "Dárkové poukazy — ceník",
    "catering": "Catering na míru", "jidelnicek": "Týdenní jídelníček",
}

def split_top_level(src: str):
    """Rozdělí HTML na top-level chunky + zdrojový shortcode (z komentářů)."""
    chunks, cur, depth, origin = [], [], 0, ""
    pos = 0
    marker = re.compile(r"<!--GRID-SC:([a-z_]+)-->")
    while pos < len(src):
        m_c = marker.search(src, pos)
        m_t = TAG_RE.search(src, pos)
        if m_c and (not m_t or m_c.start() <= m_t.start()):
            if depth == 0 and cur and "".join(cur).strip():
                chunks.append((origin, "".join(cur))); cur = []
            origin = m_c.group(1); pos = m_c.end(); continue
        if not m_t:
            cur.append(src[pos:]); break
        cur.append(src[pos:m_t.end()])
        closing, tag, _, selfclose = m_t.group(1), m_t.group(2).lower(), m_t.group(3), m_t.group(4)
        if tag not in VOID and not selfclose:
            if closing:
                depth -= 1
                if depth == 0:
                    chunks.append((origin, "".join(cur))); cur = []
            else:
                depth += 1
        pos = m_t.end()
    if "".join(cur).strip():
        chunks.append((origin, "".join(cur)))
    if depth != 0:
        raise SystemExit(f"Neuzavřené tagy (depth={depth})")
    return chunks

def minify(h: str) -> str:
    h = COMMENT_RE.sub("", h)
    h = re.sub(r"\s*\n\s*", " ", h)
    return h.strip()

def text_of(fragment: str) -> str:
    t = re.sub(r"<[^>]+>", "", fragment)
    return htmllib.unescape(re.sub(r"\s+", " ", t)).strip()

def label_for(chunk: str, origin: str, idx: int) -> str:
    m = re.search(r'<(?:section|footer|header|aside|div)[^>]*\bid="([^"]+)"', chunk)
    if m and m.group(1) in ID_LABELS:
        return ID_LABELS[m.group(1)]
    m = re.search(r"<h[123][^>]*>(.*?)</h[123]>", chunk, re.S)
    if m:
        t = text_of(m.group(1))
        if t: return (t[:48] + "…") if len(t) > 48 else t
    m = re.search(r'<span class="kicker"[^>]*>(.*?)</span>', chunk, re.S)
    if m:
        t = text_of(m.group(1))
        if t: return (t[:48] + "…") if len(t) > 48 else t
    return f"{origin or 'sekce'} #{idx}"

def divi_section(inner_modules: str) -> str:
    return ('[et_pb_section fb_built="1" _builder_version="4.27.4" custom_padding="0px||0px||true|false" global_colors_info="{}"]'
            '[et_pb_row _builder_version="4.27.4" width="100%" max_width="100%" use_custom_gutter="on" gutter_width="1" '
            'custom_padding="0px||0px||true|false" module_class="grid-fullrow" global_colors_info="{}"]'
            '[et_pb_column type="4_4" _builder_version="4.27.4" global_colors_info="{}"]'
            + inner_modules +
            '[/et_pb_column][/et_pb_row][/et_pb_section]')

def text_module(content: str, label: str) -> str:
    label = label.replace('"', "'")
    return (f'[et_pb_text admin_label="{label}" _builder_version="4.27.4" module_class="grid-html" '
            f'global_colors_info="{{}}"]{content}[/et_pb_text]')

def build(name: str, single_module: bool = False):
    src = (SRC / f"{name}.html").read_text(encoding="utf-8")
    chunks = split_top_level(src)
    if single_module:
        content = " ".join(minify(c) for _, c in chunks if minify(c))
        label = label_for(chunks[0][1], chunks[0][0], 1) if chunks else name
        body = divi_section(text_module(content, label))
    else:
        parts = []
        for i, (origin, chunk) in enumerate(chunks, 1):
            mc = minify(chunk)
            if not mc: continue
            parts.append(divi_section(text_module(mc, label_for(chunk, origin, i))))
        body = "".join(parts)
    data = {"context": "et_builder", "data": {"1": body}, "presets": [],
            "global_colors": GLOBAL_COLORS, "images": {}, "thumbnails": []}
    out = DST / f"{name}.json"
    out.write_text(json.dumps(data, ensure_ascii=False), encoding="utf-8")
    n = body.count("[et_pb_text")
    print(f"{name}.json  ({n} modulů, {len(body)} B)")

PAGES = ["page-domov","page-pokoje","page-zazitky","page-gastronomie","page-sezona-2026",
         "page-firemni-svatby","page-kontakt","page-doprava","page-dotaznik","page-podminky",
         "page-ochrana-udaju","page-o-nas","page-kariera","page-video","page-rezervace",
         "page-cookies","page-disclaimer","page-privacy-statement"]

for p in PAGES:
    build(p)
build("header-global", single_module=True)
build("footer-global", single_module=True)
print("HOTOVO — page-galerie.json ponechán (dynamická ACF galerie)")
