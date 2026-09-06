# -*- coding: utf-8 -*-
"""Vloží na stránky O nás světlou sekci „Okolí hotelu" mezi časosběr a rezervaci."""
import io, re, sys
SP = sys.argv[1]
sys.path.insert(0, SP)
import okoli as O

STRANKY = {261: "cz", 400: "en", 401: "de"}
ZNACKA = 'name": "class", "value": "sec sec-light final"'
ZNACKA2 = '"name":"class","value":"sec sec-light final"'


def esc(html):
    return html.replace('"', '\\u0022').replace('<', '\\u003c').replace('>', '\\u003e')


def text_modul(html):
    return ('<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"'
            + esc(html) + '"}}},"builderVersion":"5.9.0"} /-->')


def sekce(lang):
    i = O.I[lang]
    kicker = text_modul(f'<span class="kicker">{O.TITULEK[i]}</span>')
    nadpis = ('<!-- wp:divi/heading {"title":{"innerContent":{"desktop":{"value":"'
              + O.NADPIS[i].replace('"', '\\u0022')
              + '"}},"decoration":{"font":{"font":{"desktop":{"value":{"headingLevel":"h2"}}}}}},'
                '"builderVersion":"5.9.0"} /-->')
    perex = text_modul(f'<p class="sec-lead">{O.PERex[i]}</p>')
    return (
      '<!-- wp:divi/section {"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":'
      '[{"id":"okolihotelu1","name":"class","value":"sec sec-light sec-pad","adminLabel":"",'
      '"targetElement":"main"},{"id":"okolihotelu2","name":"id","value":"okoli","adminLabel":"",'
      '"targetElement":"main"}]}}}}},"builderVersion":"5.9.0"} -->'
      '<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}},'
      '"flexColumnStructure":{"desktop":{"value":"equal-columns_1"}}},"decoration":{"layout":'
      '{"desktop":{"value":{"flexWrap":"nowrap","justifyContent":"center"}}},"attributes":'
      '{"desktop":{"value":{"attributes":[{"id":"okolihotelu3","name":"class","value":"wrap",'
      '"adminLabel":"","targetElement":"main"}]}}}}},"builderVersion":"5.9.0"} -->'
      '<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},'
      '"decoration":{"sizing":{"desktop":{"value":{"flexType":"24_24"}}}}},"builderVersion":"5.9.0"} -->'
      + kicker + nadpis + perex
      + text_modul(O.lead_html(lang))
      + text_modul(O.filtr_html(lang))
      + text_modul(O.karty_html(lang))
      + '<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->')


for pid, lang in STRANKY.items():
    s = io.open(f"{SP}/onas-{pid}.txt", encoding="utf-8").read()
    if 'okoli-grid' in s:
        # sekce už existuje — nahradíme ji celou, ať se nezdvojí
        z = s.find('"value":"sec sec-light sec-pad","adminLabel":"","targetElement":"main"},'
                   '{"id":"okolihotelu2"')
        zac = s.rfind('<!-- wp:divi/section', 0, z)
        kon = s.find('<!-- /wp:divi/section -->', s.find('okoli-grid')) + len('<!-- /wp:divi/section -->')
        s = s[:zac] + sekce(lang) + s[kon:]
        print(f"  {pid} ({lang}): sekce nahrazena")
    else:
        idx = -1
        for zn in (ZNACKA, ZNACKA2):
            p = s.find(zn)
            if p != -1:
                idx = s.rfind('<!-- wp:divi/section', 0, p)
                break
        if idx == -1:
            print(f"  {pid}: CHYBA — zaverecna sekce nenalezena")
            continue
        s = s[:idx] + sekce(lang) + s[idx:]
        print(f"  {pid} ({lang}): sekce vlozena na pozici {idx}")
    io.open(f"{SP}/onas-out-{pid}.txt", "w", encoding="utf-8").write(s)
    karet = s.count('class=\\u0022okoli-card\\u0022')
    filtru = s.count('data-filtr')
    print("      karet=%d, filtru=%d, delka=%d" % (karet, filtru, len(s)))
