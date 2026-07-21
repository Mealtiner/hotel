#!/usr/bin/env python3
"""Doladění přeložených chunků: viditelné texty v data-* atributech.

- data-l="…" v tabulce pokojů = mobilní labely (CSS content:attr(data-l))
- data-ev="…" + <option value="…"> v sekci Sezóna = JS titulky čekacího listu
"""
import re
from pathlib import Path

BASE = Path(__file__).parent
FIX = {
    "en": {
        'data-l="Typ pokoje"': 'data-l="Room type"',
        'data-l="Velikost"': 'data-l="Size"',
        'data-l="Kapacita"': 'data-l="Capacity"',
        'data-l="Počet pokojů"': 'data-l="Rooms"',
        'data-ev="MotoGP víkend"': 'data-ev="MotoGP weekend"',
        'value="MotoGP víkend"': 'value="MotoGP weekend"',
        'data-ev="Endurance 8h Brno"': 'data-ev="Endurance 8h Brno"',
    },
    "de": {
        'data-l="Typ pokoje"': 'data-l="Zimmertyp"',
        'data-l="Velikost"': 'data-l="Größe"',
        'data-l="Kapacita"': 'data-l="Kapazität"',
        'data-l="Počet pokojů"': 'data-l="Zimmer"',
        'data-ev="MotoGP víkend"': 'data-ev="MotoGP-Wochenende"',
        'value="MotoGP víkend"': 'value="MotoGP-Wochenende"',
    },
}

for lang, table in FIX.items():
    d = BASE / f"chunks-{lang}"
    n = 0
    for f in d.glob("*.html"):
        s = orig = f.read_text()
        for a, b in table.items():
            s = s.replace(a, b)
        if s != orig:
            f.write_text(s); n += 1
    print(f"{lang}: upraveno {n} souborů")

# kontrola: sladit data-ev s přeloženým textem ev-name (musí si odpovídat kvůli JS)
for lang in ("en", "de"):
    for f in (BASE / f"chunks-{lang}").glob("grid_season__*.html"):
        s = f.read_text()
        evs = set(re.findall(r'data-ev="([^"]+)"', s))
        opts = set(re.findall(r'<option value="([^"]+)"', s))
        if evs != opts:
            print(f"⚠️ {lang}/{f.name}: data-ev {evs} != option values {opts}")
        else:
            print(f"OK {lang}/{f.name}: {sorted(evs)}")
