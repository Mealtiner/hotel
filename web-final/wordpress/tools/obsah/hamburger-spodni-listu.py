# -*- coding: utf-8 -*-
"""Rozbalené hamburger menu: logo, spodní lišta s akcemi, jazyky uprostřed.

Menu mělo tlačítko Rezervovat a přepínač jazyků volně za položkami, takže se
při delším menu ztratily pod ohybem. Nová struktura je pevná lišta u spodního
okraje (REZERVOVAT přes celou šířku, pod ním NAVIGOVAT + jazyky + VOLAT) a
logo v levém horním rohu. Menu se roluje pod lištou.
"""
import io, re, sys

SP = sys.argv[1]
B = chr(92)
Q = B + "u0022"
L = B + "u003c"
G = B + "u003e"

LOGO = "/wp-content/themes/grid-divi5-child/assets/logo/grid-hotel-negativ.png"
MAPA = ("https://www.google.com/maps/dir/?api=1" + B + "u0026destination="
        "GRID%20HOTEL%2C%20Ostrova%C4%8Dick%C3%A1%20936%2F65%2C%20641%2000%20Brno-%C5%BDeb%C4%9Bt%C3%ADn")
TEL = "+420775877721"

JAZYKY = {
    "cs": {"domu": "/", "rezervace": "/rezervace/", "rez": "Rezervovat", "nav": "Navigovat",
           "volat": "Volat", "logo_alt": "GRID HOTEL — domů"},
    "en": {"domu": "/en/", "rezervace": "/en/reservation/", "rez": "Book now", "nav": "Navigate",
           "volat": "Call", "logo_alt": "GRID HOTEL — home"},
    "de": {"domu": "/de/", "rezervace": "/de/reservierung/", "rez": "Buchen", "nav": "Navigation",
           "volat": "Anrufen", "logo_alt": "GRID HOTEL — Startseite"},
}


def novy_zaver(j):
    """Obsah menu od [grid_menu_hlavni] po konec — položky + spodní lišta."""
    return (
        f'{L}div class={Q}mm-obsah{Q}{G}[grid_menu_hlavni]{L}/div{G} '
        f'{L}div class={Q}mm-spodek{Q}{G}'
        f'{L}a href={Q}{j["rezervace"]}{Q} class={Q}btn mm-rezervovat{Q}{G}{j["rez"]}{L}/a{G} '
        f'{L}div class={Q}mm-radek{Q}{G}'
        f'{L}a href={Q}{MAPA}{Q} class={Q}btn btn-ghost mm-navigovat{Q} target={Q}_blank{Q} '
        f'rel={Q}noopener{Q}{G}{j["nav"]}{L}/a{G}'
        f'[grid_lang_switch]'
        f'{L}a href={Q}tel:{TEL}{Q} class={Q}btn btn-ghost mm-volat{Q}{G}{j["volat"]}{L}/a{G}'
        f'{L}/div{G}{L}/div{G}'
    )


def logo(j):
    return (f'{L}a class={Q}mm-logo{Q} href={Q}{j["domu"]}{Q} aria-label={Q}{j["logo_alt"]}{Q}{G}'
            f'{L}img src={Q}{LOGO}{Q} alt={Q}GRID HOTEL{Q}{G}{L}/a{G} ')


p = f"{SP}/header-tb.txt"
s = io.open(p, encoding="utf-8").read()

zacatky = [m.start() for m in re.finditer(re.escape(f'{L}div class={Q}mobile-menu'), s)]
if len(zacatky) != 3:
    raise SystemExit(f"čekal jsem tři jazykové varianty, našel jsem {len(zacatky)}")

# odzadu, ať zůstávají platné dřívější indexy
for poradi, zacatek in reversed(list(enumerate(zacatky))):
    j = JAZYKY[["cs", "en", "de"][poradi]]
    i = s.find("[grid_menu_hlavni]", zacatek)
    konec = s.find(f'{L}/div{G}', s.find("[grid_lang_switch]", i)) + len(f'{L}/div{G}')
    s = s[:i] + novy_zaver(j) + s[konec:]
    # logo hned za otevírací značku menu
    otevreni = s.find(G, zacatek) + len(G)
    s = s[:otevreni] + " " + logo(j) + s[otevreni:]
    print(f"  varianta {['cs','en','de'][poradi]}: přestavěno")

io.open(p, "w", encoding="utf-8").write(s)
