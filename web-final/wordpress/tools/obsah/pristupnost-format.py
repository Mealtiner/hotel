# -*- coding: utf-8 -*-
"""Prohlášení o přístupnosti naformátovat stejně jako ostatní právní stránky.

Stránka vznikla jako jeden textový modul s vlastním <section> uvnitř, takže
seděla v užším sloupci Divi a pozadí nešlo přes celou šířku. Přebírá se
struktura ze stránky s ochranou osobních údajů: sekce nese třídy a odsazení,
řádek `.wrap.wrap-narrow.legal-body` drží text.
"""
import io, os, re, subprocess

SC = os.environ.get('SC', '.')
B  = chr(92)
LT, GT, QT = B + 'u003c', B + 'u003e', B + 'u0022'
STRANKY = (1198, 1199, 1200)

SEKCE = ('<!-- wp:divi/section {"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":['
         '{"id":"pristup1","name":"class","value":"sec sec-light grid-legal","adminLabel":"","targetElement":"main"}]}}},'
         '"spacing":{"desktop":{"value":{"padding":{"top":"clamp(120px,16vh,180px)","right":"",'
         '"bottom":"clamp(120px,16vh,180px)","left":"","syncVertical":"off","syncHorizontal":"off"}}}}}},'
         '"builderVersion":"5.9.0"} -->')
RADEK = ('<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}},'
         '"flexColumnStructure":{"desktop":{"value":"equal-columns_1"}}},"decoration":{"layout":{"desktop":'
         '{"value":{"flexWrap":"nowrap","justifyContent":"center"}}},"attributes":{"desktop":{"value":{"attributes":['
         '{"id":"pristup2","name":"class","value":"wrap wrap-narrow legal-body","adminLabel":"","targetElement":"main"}]}}}}},'
         '"builderVersion":"5.9.0"} -->')
SLOUPEC = ('<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},'
           '"decoration":{"sizing":{"desktop":{"value":{"flexType":"24_24"}}}}},"builderVersion":"5.9.0"} -->')


def wp(*a):
    return subprocess.run([os.path.join(SC, 'wpg.sh')] + list(a),
                          capture_output=True, text=True).stdout


for pid in STRANKY:
    obsah = wp('post', 'get', str(pid), '--field=content')
    if 'grid-legal' in obsah and '"name":"class","value":"sec sec-light grid-legal"' in obsah:
        print('%d: už naformátováno' % pid); continue

    # vnitřek vlastního <section> … </section> je samotný obsah stránky
    m = re.search(re.escape(LT + 'div class=' + QT + 'wrap' + QT) + r'.*?' + re.escape(GT), obsah)
    if not m:
        print('%d: obal .wrap nenalezen' % pid); continue
    zac = m.end()
    kon = obsah.rfind(LT + '/div' + GT + LT + '/section' + GT)
    if kon == -1:
        kon = obsah.rfind(LT + '/section' + GT)
    text = obsah[zac:kon]

    novy = (SEKCE + RADEK + SLOUPEC
            + '<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"' + text
            + '"}}},"builderVersion":"5.9.0"} /-->'
            + '<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->')

    cesta = os.path.join(SC, 'pristupnost-%d.txt' % pid)
    io.open(cesta, 'w', encoding='utf-8').write(novy)
    print('%d:' % pid, wp('post', 'update', str(pid), cesta).strip())
