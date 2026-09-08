# -*- coding: utf-8 -*-
"""Číslování sekcí na stránce Zážitky.

Stránka převzala popisky z titulní strany, kde jsou zážitky čtvrtou sekcí —
tady jsou první. Přečíslováno na T1 a T2 podle skutečného pořadí.
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
ZMENY = {
    337: (('T4 · Zážitky u okruhu', 'T1 · Zážitky u okruhu'),
          ('Dárkové poukazy · Sezóna', 'T2 · Dárkové poukazy · Sezóna')),
    382: (('T4 · Trackside experiences', 'T1 · Trackside experiences'),
          ('Gift Vouchers · Season', 'T2 · Gift Vouchers · Season')),
    383: (('T4 · Erlebnisse an der Rennstrecke', 'T1 · Erlebnisse an der Rennstrecke'),
          ('Geschenkgutscheine · Saison', 'T2 · Geschenkgutscheine · Saison')),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid, dvojice in ZMENY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    for stare, nove in dvojice:
        if nove in obsah:
            continue
        if stare in obsah:
            obsah = obsah.replace(stare, nove, 1)
            zmen += 1
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'zaz-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d popisků —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
