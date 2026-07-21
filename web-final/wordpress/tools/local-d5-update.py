#!/usr/bin/env python3
"""Vymění v Divi 5 blocích obsahové [grid_*] shortcody za statické HTML.

Vstup:  d5-dump/<ID>.txt  (aktuální post_content z Localu)
        tools out/<page>.html (vyrenderované HTML s markery <!--GRID-SC:name-->)
Výstup: d5-new/<ID>.txt  (nový post_content)

Widget shortcody ([grid_telemetry], [grid_galerie]) zůstávají beze změny.
"""
import json, re
from pathlib import Path

BASE = Path(__file__).parent
OUT = BASE / "out"
DUMP = BASE / "d5-dump"
NEW = BASE / "d5-new"
NEW.mkdir(exist_ok=True)

# post ID -> zdrojový soubor HTML
PAGE_MAP = {
    99: "page-domov", 220: "page-pokoje", 226: "page-gastronomie", 229: "page-sezona-2026",
    232: "page-firemni-svatby", 235: "page-kontakt", 238: "page-doprava", 241: "page-dotaznik",
    244: "page-cookies", 247: "page-disclaimer", 250: "page-privacy-statement",
    258: "page-ochrana-udaju", 261: "page-o-nas", 264: "page-kariera", 270: "page-video",
    293: "page-podminky", 340: "page-podminky", 324: "page-rezervace", 337: "page-zazitky",
    11: "header-global", 106: "footer-global",
}
KEEP = {"grid_telemetry", "grid_tracknav", "grid_galerie"}

def minify(h: str) -> str:
    h = re.sub(r"<!--(?!GRID-SC).*?-->", "", h, flags=re.S)
    return re.sub(r"\s*\n\s*", " ", h).strip()

def load_chunks(page: str) -> dict:
    """shortcode -> minifikované HTML (vše mezi jeho markerem a dalším)."""
    src = (OUT / f"{page}.html").read_text(encoding="utf-8")
    parts = re.split(r"<!--GRID-SC:([a-z_]+)-->", src)
    chunks = {}
    for i in range(1, len(parts), 2):
        name, body = parts[i], minify(parts[i + 1])
        chunks[name] = (chunks.get(name, "") + " " + body).strip()
    return chunks

def esc_block_json(s: str) -> str:
    return (s.replace("&", "\\u0026").replace("<", "\\u003c")
             .replace(">", "\\u003e").replace("--", "\\u002d\\u002d"))

BLOCK_RE = re.compile(r"<!-- wp:divi/text (\{.*?\}) /-->", re.S)
SC_RE = re.compile(r"^\[(grid_[a-z_]+)[\] ]")

report = []
for pid, page in PAGE_MAP.items():
    src_file = DUMP / f"{pid}.txt"
    content = src_file.read_text(encoding="utf-8")
    chunks = load_chunks(page)
    replaced, kept = [], []

    def sub(m):
        js = json.loads(m.group(1))
        try:
            val = js["content"]["innerContent"]["desktop"]["value"]
        except (KeyError, TypeError):
            return m.group(0)
        mm = SC_RE.match(val.strip())
        if not mm:
            return m.group(0)
        name = mm.group(1)
        if name in KEEP or name not in chunks:
            kept.append(name)
            return m.group(0)
        js["content"]["innerContent"]["desktop"]["value"] = chunks[name]
        # class grid-shortcode -> grid-html
        try:
            for attr in js["module"]["decoration"]["attributes"]["desktop"]["value"]["attributes"]:
                if attr.get("name") == "class" and "grid-shortcode" in attr.get("value", ""):
                    attr["value"] = attr["value"].replace("grid-shortcode", "grid-html")
        except (KeyError, TypeError):
            pass
        replaced.append(name)
        blob = esc_block_json(json.dumps(js, ensure_ascii=False, separators=(",", ":")))
        return f"<!-- wp:divi/text {blob} /-->"

    new = BLOCK_RE.sub(sub, content)
    (NEW / f"{pid}.txt").write_text(new, encoding="utf-8")
    report.append(f"{pid} ({page}): vyměněno {replaced or '—'}, ponecháno {kept or '—'}")

print("\n".join(report))
