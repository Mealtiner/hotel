# -*- coding: utf-8 -*-
"""Přečíslování popisků sekcí na podstránkách.

Podstránky převzaly popisky z titulní strany, kde má každá sekce jiné pořadí
(zážitky jsou T4, gastro T5, sezóna T6, firemní akce T7). Na podstránce má
číslování odpovídat skutečnému pořadí sekcí. Mění se jen text uvnitř
`class="kicker"`, takže se to nedotkne stejných slov jinde na stránce.

Tabulka je psaná v pořadí sekcí; každý řádek je (starý text, nový text).
"""
import io, os, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
ZNAK = 'kicker' + B + 'u0022'
OTEV = B + 'u003e'
ZAV  = B + 'u003c'

STRANKY = {
    # Sezóna
    229: (('T6 · Sezóna · Čekací list', 'T1 · Sezóna · Čekací list'),
          ('Rezervace ' + B + 'u0026amp; čekací list', 'T2 · Rezervace ' + B + 'u0026amp; čekací list'),
          ('CÍL · Cílová rovinka', 'T3 · CÍL · Cílová rovinka')),
    386: (('T6 · Season · Waiting list', 'T1 · Season · Waiting list'),
          ('Booking ' + B + 'u0026amp; waiting list', 'T2 · Booking ' + B + 'u0026amp; waiting list'),
          ('FINISH · The final straight', 'T3 · FINISH · The final straight')),
    387: (('T6 · Saison · Warteliste', 'T1 · Saison · Warteliste'),
          ('Buchung ' + B + 'u0026amp; Warteliste', 'T2 · Buchung ' + B + 'u0026amp; Warteliste'),
          ('ZIEL · Zielgerade', 'T3 · ZIEL · Zielgerade')),
    # Firemní akce & svatby
    232: (('T7 · Firemní akce ' + B + 'u0026amp; svatby', 'T1 · Firemní akce ' + B + 'u0026amp; svatby'),
          ('Pro jakou akci', 'T2 · Pro jakou akci'),
          ('Co u nás najdete', 'T3 · Co u nás najdete'),
          ('CÍL · Cílová rovinka', 'T4 · CÍL · Cílová rovinka')),
    388: (('T7 · Corporate events ' + B + 'u0026amp; weddings', 'T1 · Corporate events ' + B + 'u0026amp; weddings'),
          ('For your occasion', 'T2 · For your occasion'),
          ('What you will find here', 'T3 · What you will find here'),
          ('FINISH · The final straight', 'T4 · FINISH · The final straight')),
    389: (('T7 · Firmenveranstaltungen ' + B + 'u0026amp; Hochzeiten', 'T1 · Firmenveranstaltungen ' + B + 'u0026amp; Hochzeiten'),
          ('Für Ihre Veranstaltung', 'T2 · Für Ihre Veranstaltung'),
          ('Was Sie bei uns finden', 'T3 · Was Sie bei uns finden'),
          ('ZIEL · Zielgerade', 'T4 · ZIEL · Zielgerade')),
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid, dvojice in STRANKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    zmen  = 0
    for stary, novy in dvojice:
        hledej  = OTEV + stary + ZAV
        nahrada = OTEV + novy + ZAV
        if nahrada in obsah:
            continue
        i = obsah.find(ZNAK)
        while i != -1:
            j = obsah.find(hledej, i, i + 220)
            if j != -1:
                obsah = obsah[:j] + nahrada + obsah[j + len(hledej):]
                zmen += 1
                break
            i = obsah.find(ZNAK, i + 1)
    if not zmen:
        print('%d: beze změny' % pid); continue
    cesta = os.path.join(SC, 'kick-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d: %d popisků —' % (pid, zmen), wp('post', 'update', str(pid), cesta).strip())
