# -*- coding: utf-8 -*-
"""Přestavba stránky Gastronomie.

* Popisky sekcí se přečíslují podle skutečného pořadí (T1, T2, … , T6) —
  stránka je převzala z titulní strany, kde je gastro až pátá sekce.
* Do každé sekce přibývá vodoznak (`.sec-tag`) jako na ostatních podstránkách.
* Tlačítka u karet provozů dostávají název provozu místo obecného „Jídelní
  lístek", stejně jako na titulní straně.
* Jedna sekce s jídelníčky všech provozů se rozpadá na tři samostatné sekce
  (hotel / paddock / club), prostřední na tmavém podkladu. Nadpis nese sekce,
  proto se vypíná vlastní nadpis shortcodu (`nadpis="ne"`).

Kotva `#jidelnicek` zůstává na první z nových sekcí, aby fungovaly odkazy
z titulní strany. Kotvy jednotlivých provozů (`#jidelnicek-<slug>`) vykresluje
dál sám shortcode.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'

PROVOZY = (
    ('hotelova-restaurace', 'jidelnicek',   'sec sec-light sec-pad'),
    ('paddock-restaurant',  'menu-paddock', 'sec sec-dark carbon sec-pad'),
    ('grid-club',           'menu-club',    'sec sec-light sec-pad'),
)

STRANKY = {
    226: {
        'kickery': (('T5 · Gastronomie ·', 'T1 · Gastronomie ·'),
                    ('Služba · Catering', 'T2 · Služba · Catering')),
        'cil':     ('CÍL · Cílová rovinka', 'T6 · CÍL · Cílová rovinka'),
        'menu':    (('T3 · Hotel menu', 'Hotelová restaurace · MENU'),
                    ('T4 · Paddock menu', 'Paddock restaurant · MENU'),
                    ('T5 · Club menu', 'GRID CLUB · MENU')),
        'tlacitka': (('hotelova-restaurace', 'HOTEL MENU'),
                     ('paddock-restaurant', 'PADDOCK MENU'),
                     ('grid-club', 'CLUB MENU')),
    },
    384: {
        'kickery': (('T5 · Gastronomy ·', 'T1 · Gastronomy ·'),
                    ('Service · Catering', 'T2 · Service · Catering')),
        'cil':     ('FINISH · The final straight', 'T6 · FINISH · The final straight'),
        'menu':    (('T3 · Hotel menu', 'Hotelová restaurace · MENU'),
                    ('T4 · Paddock menu', 'Paddock restaurant · MENU'),
                    ('T5 · Club menu', 'GRID CLUB · MENU')),
        'tlacitka': (('hotelova-restaurace', 'HOTEL MENU'),
                     ('paddock-restaurant', 'PADDOCK MENU'),
                     ('grid-club', 'CLUB MENU')),
    },
    385: {
        'kickery': (('T5 · Gastronomie ·', 'T1 · Gastronomie ·'),
                    ('Service · Catering', 'T2 · Service · Catering')),
        'cil':     ('ZIEL · Zielgerade', 'T6 · ZIEL · Zielgerade'),
        'menu':    (('T3 · Hotel menu', 'Hotelová restaurace · MENÜ'),
                    ('T4 · Paddock menu', 'Paddock restaurant · MENÜ'),
                    ('T5 · Club menu', 'GRID CLUB · MENÜ')),
        'tlacitka': (('hotelova-restaurace', 'HOTEL MENU'),
                     ('paddock-restaurant', 'PADDOCK MENU'),
                     ('grid-club', 'CLUB MENU')),
    },
}


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


def sekce(trida, kotva, znacka, kicker, nadpis, slug, poradi):
    """Celý blok jedné sekce s jídelníčkem jednoho provozu."""
    atr = ('{"id":"gm%da","name":"class","value":"%s","adminLabel":"","targetElement":"main"},'
           '{"id":"gm%db","name":"id","value":"%s","adminLabel":"","targetElement":"main"}'
           % (poradi, trida, poradi, kotva))
    return (
        '<!-- wp:divi/section {"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":['
        + atr + ']}}}}},"builderVersion":"5.9.0"} -->'
        + '<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
        + LT + 'span class=' + QT + 'sec-tag' + QT + GT + znacka + LT + '/span' + GT
        + '"}}},"builderVersion":"5.9.0"} /-->'
        + '<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}}},'
          '"decoration":{"attributes":{"desktop":{"value":{"attributes":[{"id":"gm%dr","name":"class",'
          '"value":"wrap","adminLabel":"","targetElement":"main"}]}}}}},"builderVersion":"5.9.0"} -->' % poradi
        + '<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}}},"builderVersion":"5.9.0"} -->'
        + '<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
        + LT + 'span class=' + QT + 'kicker' + QT + GT + kicker + LT + '/span' + GT
        + '"}}},"builderVersion":"5.9.0"} /-->'
        + '<!-- wp:divi/heading {"title":{"innerContent":{"desktop":{"value":"' + nadpis + '"}},'
          '"decoration":{"font":{"font":{"desktop":{"value":{"headingLevel":"h2"}}}}}},"builderVersion":"5.9.0"} /-->'
        + '<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":'
          '"[grid_menu_provozy provozy=' + QT + slug + QT + ' nadpis=' + QT + 'ne' + QT + ']"}}},'
          '"builderVersion":"5.9.0"} /-->'
        + '<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->'
    )


for pid, cfg in STRANKY.items():
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'nadpis=' + QT + 'ne' + QT in obsah:
        print('%d: už přestavěno' % pid); continue

    # 1) popisky sekcí
    for stary, novy in cfg['kickery'] + (cfg['cil'],):
        obsah = obsah.replace(stary, novy, 1)

    # 2) texty tlačítek u karet provozů
    for kotva, text in cfg['tlacitka']:
        i = obsah.find('#jidelnicek-' + kotva)
        while i != -1:
            j = obsah.rfind('{"text":"', 0, i)
            k = obsah.find('","linkUrl":"', j)
            if j != -1 and k != -1 and k < i:
                obsah = obsah[:j + len('{"text":"')] + text + obsah[k:]
                break
            i = obsah.find('#jidelnicek-' + kotva, i + 1)

    # 3) rozdělení sekce s jídelníčky na tři
    zacatek = obsah.find('<!-- wp:divi/section', obsah.find('[grid_menu_provozy]') - 4000)
    konec   = obsah.find('<!-- /wp:divi/section -->', obsah.find('[grid_menu_provozy]'))
    if zacatek == -1 or konec == -1:
        print('%d: sekci s jídelníčky se nepodařilo najít' % pid); continue
    konec += len('<!-- /wp:divi/section -->')

    nove = ''
    for poradi, ((slug, kotva, trida), (kicker, nadpis)) in enumerate(zip(PROVOZY, cfg['menu']), start=1):
        nove += sekce(trida, kotva, 'T%d' % (poradi + 2), kicker, nadpis, slug, poradi)
    obsah = obsah[:zacatek] + nove + obsah[konec:]

    cesta = os.path.join(SC, 'gastro-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(obsah)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
