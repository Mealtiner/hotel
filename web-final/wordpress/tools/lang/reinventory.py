#!/usr/bin/env python3
"""Přestaví chunk inventuru z čerstvého renderu a zmigruje EN/DE překlady:
- nezměněné bloky (stejný hash) → kopie stávajícího překladu
- grid_vstupy (nová struktura div+overlay) → transformace starého překladu
- grid_rooms (doplněná data tabulky) → oprava tabulky ve starém překladu
"""
import hashlib, json, re, shutil, sys
from pathlib import Path

BASE = Path(__file__).parent
OUT = Path("/Users/mealtiner/GIT/GRIDhotel/web-final/wordpress/tools/out")

old_manifest = json.loads((BASE / "manifest.json").read_text())
old_keys = {k for lst in old_manifest.values() for k in lst}

# --- nová inventura ---
new_chunks, manifest = {}, {}
for f in sorted(OUT.glob("*.html")):
    parts = re.split(r"<!--GRID-SC:([a-z_]+)-->", f.read_text())
    order = []
    for i in range(1, len(parts), 2):
        name, body = parts[i], parts[i + 1].strip()
        if not body: continue
        h = hashlib.md5(body.encode()).hexdigest()[:10]
        key = f"{name}__{h}"
        new_chunks.setdefault(key, body)
        order.append(key)
    manifest[f.stem] = order

(BASE / "manifest.json").write_text(json.dumps(manifest, indent=1))
cs = BASE / "chunks-cs"
for key, body in new_chunks.items():
    (cs / f"{key}.html").write_text(body)

# --- transformace pro změněné bloky ---
ENTRY_RE = re.compile(r'<a href="([^"]*)" class="entry">\s*(.*?)\s*</a>', re.S)
def transform_vstupy(html: str) -> str:
    def sub(m):
        url, inner = m.group(1), m.group(2)
        t = re.search(r"<h3>(.*?)</h3>", inner, re.S)
        label = re.sub(r"<[^>]+>", "", t.group(1)).strip() if t else ""
        return (f'<div class="entry"><a class="entry-link" href="{url}" '
                f'aria-label="{label}"></a>{inner}\n\t      </div>')
    out, n = ENTRY_RE.subn(sub, html)
    assert n == 4, f"vstupy: nahrazeno {n} karet místo 4"
    return out

ROOM_DATA = {  # title(cs/en/de variantně) -> (velikost, kapacita, pocet)
    "Standard": ("24", "1–2", "30"),
    "Superior": ("24", "2", "20"),
    "Superior Plus": ("24", "2–3", "10"),
    "Apartmá": ("47–59", "2–4", "4"), "Apartment": ("47–59", "2–4", "4"), "Appartement": ("47–59", "2–4", "4"),
}
def patch_rooms_table(html: str, os_suffix: str, total_label_ok=True) -> str:
    # řádky: <td data-l="...">TITLE</td><td data-l="...">—</td><td ...>—</td><td ...>—</td>
    row_re = re.compile(
        r'(<td data-l="[^"]*">(?:<a[^>]*>)?([^<]+)(?:</a>)?</td>)\s*'
        r'<td data-l="[^"]*">—</td>\s*<td data-l="[^"]*">—</td>\s*<td data-l="[^"]*">—</td>')
    labels = re.findall(r'<td data-l="([^"]*)">', html)
    l_size, l_cap, l_cnt = labels[1], labels[2], labels[3]
    def sub(m):
        title = m.group(2).strip()
        best = next((v for k, v in ROOM_DATA.items() if title.startswith(k) or k.startswith(title.split()[0])), None)
        if best is None:
            return m.group(0)
        vel, kap, poc = best
        return (f'{m.group(1)} <td data-l="{l_size}">{vel}&nbsp;m²</td> '
                f'<td data-l="{l_cap}">{kap}&nbsp;{os_suffix}</td> <td data-l="{l_cnt}">{poc}</td>')
    out, n = row_re.subn(sub, html)
    assert n == 4, f"rooms: nahrazeno {n} řádků místo 4"
    # tfoot Celkem
    out = re.sub(r'(<tfoot><tr><td>[^<]*</td><td></td><td></td><td><strong>)\d*(</strong>)',
                 r'\g<1>64\g<2>', out)
    if "<tfoot" not in out:
        out = out.replace("</tbody>", "</tbody>")
    return out

OS = {"en": "pers.", "de": "Pers."}

migr = {"copy": 0, "vstupy": 0, "rooms": 0, "chybi": []}
for lang in ("en", "de"):
    d = BASE / f"chunks-{lang}"
    for key in new_chunks:
        target = d / f"{key}.html"
        if key in old_keys:
            continue  # beze změny — soubor už existuje pod stejným názvem
        name = key.rsplit("__", 1)[0]
        # najdi starý klíč stejného shortcodu na stejné pozici
        candidates = [k for k in old_keys if k.startswith(name + "__") and not (d / f"{k}.html").exists() is None]
        # mapuj podle stránky+pozice
        old_key = None
        for page, order in manifest.items():
            if key in order:
                idx = order.index(key)
                old_order = old_manifest.get(page, [])
                if idx < len(old_order) and old_order[idx].startswith(name + "__"):
                    old_key = old_order[idx]; break
        if not old_key:
            migr["chybi"].append(f"{lang}/{key}"); continue
        src = (d / f"{old_key}.html").read_text()
        if name == "grid_vstupy":
            target.write_text(transform_vstupy(src)); migr["vstupy"] += 1
        elif name == "grid_rooms":
            target.write_text(patch_rooms_table(src, OS[lang])); migr["rooms"] += 1
        else:
            shutil.copy(d / f"{old_key}.html", target); migr["copy"] += 1

print("nové/změněné klíče:", sorted(k for k in new_chunks if k not in old_keys))
print("migrace:", migr)
# kontrola úplnosti
for lang in ("en", "de"):
    missing = [k for k in new_chunks if not (BASE / f"chunks-{lang}" / f"{k}.html").exists()]
    print(f"{lang}: chybí {len(missing)}", missing[:5])
