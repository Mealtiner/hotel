# -*- coding: utf-8 -*-
"""Přidá na titulní stránce (T2 · Příběh místa) druhé tlačítko na Masarykův okruh.

V obsahu Divi 5 jsou HTML znaky uložené jako unicode escape sekvence uvnitř JSON
atributů, proto se skládají přes chr(92) ("zpětné lomítko").
"""
SP = "/private/tmp/claude-501/-Users-mealtiner-GIT-GRIDhotel/64e30dd8-57e8-4f0e-8035-9d238fd2adb7/scratchpad"
B = chr(92)
Q = B + "u0022"
L = B + "u003c"
G = B + "u003e"

def odkaz(url, popis):
    return f'{L}a class={Q}btn btn-ghost{Q} href={Q}{url}{Q}{G}{popis}{L}/a{G}'

zadani = [
 (99,  '/o-nas/', 'Celý příběh hotelu →', '/masarykuv-okruh/', 'Masarykův okruh →'),
 (378, '/en/about-the-hotel/', 'The full story of the hotel →', '/en/masaryk-circuit/', 'The Masaryk Circuit →'),
 (379, '/de/ueber-uns/', 'Die ganze Geschichte des Hotels →', '/de/masaryk-ring/', 'Der Masaryk-Ring →'),
]
for pid, u1, t1, u2, t2 in zadani:
    p = f"{SP}/home-{pid}.txt"
    s = open(p, encoding='utf-8').read()
    stare = f'{L}div style={Q}margin-top:26px{Q}{G}' + odkaz(u1, t1) + f'{L}/div{G}'
    nove  = (f'{L}div style={Q}margin-top:26px;display:flex;gap:12px;flex-wrap:wrap{Q}{G}'
             + odkaz(u1, t1) + odkaz(u2, t2) + f'{L}/div{G}')
    assert s.count(stare) == 1, (pid, s.count(stare))
    open(p, 'w', encoding='utf-8').write(s.replace(stare, nove, 1))
    print(pid, "upraveno")
