#!/usr/bin/env python3
"""Generátor GRANULÁRNÍCH Divi 5.9 nativních modulů (Section/Row/Column/Heading/
Text/Image/Button) — náhrada za starý přístup "jedna Divi sekce = jeden Text
modul s HTML blobem".

Přesný JSON tvar KAŽDÉHO modulu byl získán EMPIRICKY přes živý Visual Builder
(vytvořen skutečný modul v prohlížeči, uložen, přečten post_content z DB) —
NIKDY negenerovat Divi 5 bloky od nuly z hlavy, tiše je zahodí nebo (hůř)
vyrenderují prázdně beze zjevné chyby. Viz DIVI-NATIVNI-OBSAH.md.

Custom CSS třídy/id (aby granulární moduly vypadaly identicky jako dnešní
design, který je celý postavený na vlastních CSS třídách, ne na Divi
vzhledových nastaveních): každý modul může nést vlastní HTML atributy přes
"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[...]}}}}}
— ověřeno v živém builderu (Vlastnosti → Přidat Atribut → class), stejný
mechanismus platí pro všechny moduly (Section/Row/Column i Heading/Image/
Button), jde o generickou vlastnost společnou všem Divi 5 modulům.
"""
import random
import string

BUILDER_VERSION = "5.9.0"


def esc(s: str) -> str:
    """Escapování HTML pro vložení do Divi 5 JSON string hodnoty (stejná
    konvence jako tools/lang/d5-patch.py — nikdy neupravovat jinak)."""
    return (
        str(s)
        .replace("\\", "\\u005c")
        .replace("&", "\\u0026")
        .replace("<", "\\u003c")
        .replace(">", "\\u003e")
        .replace('"', "\\u0022")
        .replace("--", "\\u002d\\u002d")
    )


def _attr_id() -> str:
    """Náhodné ID atributu — Divi si je generuje samo, nemusí být kryptograficky
    unikátní, jen uvnitř jedné stránky nekolidovat."""
    return "".join(random.choices(string.ascii_lowercase + string.digits, k=10))


def _raw_attrs(cls: str, el_id: str, target: str) -> str:
    """Fragment položek pole 'attributes' (bez vnějšího obalu) — class/id."""
    attrs = []
    if cls:
        attrs.append(
            '{"id":"%s","name":"class","value":"%s","adminLabel":"","targetElement":"%s"}'
            % (_attr_id(), esc(cls), target)
        )
    if el_id:
        attrs.append(
            '{"id":"%s","name":"id","value":"%s","adminLabel":"","targetElement":"%s"}'
            % (_attr_id(), esc(el_id), target)
        )
    return ",".join(attrs)


def _combine(*parts) -> str:
    parts = [p for p in parts if p]
    return ",".join(parts)


# ------------------------------------------------------------------
# Sekce / řádek / sloupec — struktura
# ------------------------------------------------------------------

def section(inner_blocks: str, cls: str = "", el_id: str = "") -> str:
    attrs = _raw_attrs(cls, el_id, "main")
    module = '"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[%s]}}}}}' % attrs if attrs else ""
    attrs_str = _combine(module, '"builderVersion":"%s"' % BUILDER_VERSION)
    return "<!-- wp:divi/section {%s} -->\n%s\n<!-- /wp:divi/section -->" % (attrs_str, inner_blocks)


def row(inner_blocks: str, columns: str = "4_4", cls: str = "", el_id: str = "") -> str:
    """columns: '4_4' (1 sloupec), '1_2,1_2' (2 stejné), '2_3,1_3' apod. —
    stejná syntax jako Divi 4 column_structure; flexColumnStructure se
    dopočítá jako 'equal-columns_N' pro N sloupců."""
    n = len(columns.split(","))
    flex = "equal-columns_%d" % n if n > 1 else "equal-columns_1"
    attrs = _raw_attrs(cls, el_id, "main")
    decoration = '"layout":{"desktop":{"value":{"flexWrap":"nowrap"}}}'
    if attrs:
        decoration += ',"attributes":{"desktop":{"value":{"attributes":[%s]}}}' % attrs
    module = (
        '"module":{"advanced":{"columnStructure":{"desktop":{"value":"%s"}},'
        '"flexColumnStructure":{"desktop":{"value":"%s"}}},"decoration":{%s}}'
    ) % (columns, flex, decoration)
    attrs_str = _combine(module, '"builderVersion":"%s"' % BUILDER_VERSION)
    return "<!-- wp:divi/row {%s} -->\n%s\n<!-- /wp:divi/row -->" % (attrs_str, inner_blocks)


def column(inner_blocks: str, col_type: str = "4_4", cls: str = "", el_id: str = "") -> str:
    attrs = _raw_attrs(cls, el_id, "main")
    decoration = '"sizing":{"desktop":{"value":{"flexType":"24_24"}}}'
    if attrs:
        decoration += ',"attributes":{"desktop":{"value":{"attributes":[%s]}}}' % attrs
    module = (
        '"module":{"advanced":{"type":{"desktop":{"value":"%s"}}},"decoration":{%s}}'
        % (col_type, decoration)
    )
    attrs_str = _combine(module, '"builderVersion":"%s"' % BUILDER_VERSION)
    return "<!-- wp:divi/column {%s} -->\n%s\n<!-- /wp:divi/column -->" % (attrs_str, inner_blocks)


# ------------------------------------------------------------------
# Moduly obsahu
# ------------------------------------------------------------------

def heading(text_: str, tag: str = "h1", cls: str = "", el_id: str = "") -> str:
    """tag: h1..h6. POZOR — bez explicitního nastavení Divi 5 Heading modul
    defaultně vykresluje <h1> (ověřeno empiricky, apply_filters('the_content')
    render testem). Pro jiný stupeň je cesta
    title.decoration.font.font.desktop.value.headingLevel — NE top-level
    'headingLevel'/'level'/'tag'/'htmlTag' (ty se tiše ignorují, modul
    vždy spadne na h1). Cesta objevena v Divi zdroji
    _all_modules_conversion_outline.php (mapování 'title_level') a ověřena."""
    attrs = _raw_attrs(cls, el_id, "main")
    module = ',"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[%s]}}}}}' % attrs if attrs else ""
    level = "" if tag == "h1" else (
        ',"decoration":{"font":{"font":{"desktop":{"value":{"headingLevel":"%s"}}}}}' % tag
    )
    body = '"title":{"innerContent":{"desktop":{"value":"%s"}}%s}' % (esc(text_), level)
    attrs_str = _combine(body + module, '"builderVersion":"%s"' % BUILDER_VERSION)
    return '<!-- wp:divi/heading {%s} /-->' % attrs_str


def text(html: str, cls: str = "", el_id: str = "") -> str:
    """html: libovolný vnořený HTML fragment (p/ul/li/a/strong…) — vloží se
    přesně tak, jak je, jen escapovaný pro JSON string."""
    attrs = _raw_attrs(cls, el_id, "main")
    module = ',"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[%s]}}}}}' % attrs if attrs else ""
    body = '"content":{"innerContent":{"desktop":{"value":"%s"}}}' % esc(html)
    attrs_str = _combine(body + module, '"builderVersion":"%s"' % BUILDER_VERSION)
    return '<!-- wp:divi/text {%s} /-->' % attrs_str


def image(src: str, alt: str = "", media_id: str = "0", width: str = "", height: str = "",
          cls: str = "", el_id: str = "") -> str:
    attrs = _raw_attrs(cls, el_id, "main")
    module = '"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[%s]}}}}},' % attrs if attrs else ""
    dims = ""
    if width:
        dims += ',"width":"%s"' % width
    if height:
        dims += ',"height":"%s"' % height
    body = (
        '%s"image":{"innerContent":{"desktop":{"value":{"src":"%s","id":"%s","alt":"%s","titleText":"%s"%s}}}}'
        % (module, esc(src), media_id, esc(alt), esc(alt), dims)
    )
    attrs_str = _combine(body, '"builderVersion":"%s"' % BUILDER_VERSION)
    return '<!-- wp:divi/image {%s} /-->' % attrs_str


def button(text_: str, url: str, cls: str = "", el_id: str = "", target_blank: bool = False) -> str:
    attrs = _raw_attrs(cls, el_id, "main")
    module = ',"module":{"decoration":{"attributes":{"desktop":{"value":{"attributes":[%s]}}}}}' % attrs if attrs else ""
    link_target = ',"linkTarget":"on"' if target_blank else ""
    body = (
        '"button":{"innerContent":{"desktop":{"value":{"text":"%s","linkUrl":"%s"%s}}}}%s'
        % (esc(text_), esc(url), link_target, module)
    )
    attrs_str = _combine(body, '"builderVersion":"%s"' % BUILDER_VERSION)
    return '<!-- wp:divi/button {%s} /-->' % attrs_str


def page_wrap(sections: str) -> str:
    """Obal celé stránky — Divi 5 vždy začíná/končí placeholder blokem."""
    return "<!-- wp:divi/placeholder -->%s<!-- /wp:divi/placeholder -->" % sections


if __name__ == "__main__":
    demo = page_wrap(
        section(
            row(
                column(
                    heading("Testovací nadpis", "h1")
                    + text("<p>Ukázkový text.</p>")
                    + button("Klikni", "/kontakt/"),
                    cls="wrap",
                ),
                cls="sec sec-light",
            )
        )
    )
    print(demo)
