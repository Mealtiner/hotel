# -*- coding: utf-8 -*-
"""Stáhne písma z Google Fonts a uloží je do child motivu k lokálnímu hostování.

Důvod: načítání z fonts.googleapis.com odesílá IP adresu návštěvníka Googlu
ještě před souhlasem s cookies (GDPR/ePrivacy). Bereme jen podmnožiny latin
a latin-ext — jiné písmo web nepoužívá.

Výstup: grid-divi5-child/assets/fonts/*.woff2 + assets/css/pisma.css
Spouští se ručně, když se mění řezy písem.
"""
import io, os, re, urllib.request

CSS_URL = ('https://fonts.googleapis.com/css2'
           '?family=Saira+Condensed:wght@500;600;700;800'
           '&family=JetBrains+Mono:wght@400;500;700'
           '&family=Inter:wght@300;400;500;600;700&display=swap')
UA = {'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 '
                    '(KHTML, like Gecko) Chrome/126.0 Safari/537.36'}
PODMNOZINY = ('latin', 'latin-ext')

KOREN = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'grid-divi5-child', 'assets')
DIR_FONTS = os.path.normpath(os.path.join(KOREN, 'fonts'))
DIR_CSS   = os.path.normpath(os.path.join(KOREN, 'css'))

HLAVICKA = ("/* Písma hostovaná lokálně (GDPR/ePrivacy — bez volání na fonts.gstatic.com).\n"
            "   Zdroj: Google Fonts, licence SIL OFL 1.1 (Saira Condensed, Inter),\n"
            "   Apache License 2.0 (JetBrains Mono).\n"
            "   Generuje tools/obsah/pisma-lokalne.py; obsahuje jen latin a latin-ext. */\n\n")


def stahni(url):
    return urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=30).read()


def main():
    os.makedirs(DIR_FONTS, exist_ok=True)
    os.makedirs(DIR_CSS, exist_ok=True)
    css = stahni(CSS_URL).decode('utf-8')
    bloky, ven = re.split(r'(?=/\* [a-z-]+ \*/)', css), []
    for b in bloky:
        m = re.match(r'/\* ([a-z-]+) \*/', b.strip())
        if not m or m.group(1) not in PODMNOZINY:
            continue
        rodina = re.search(r"font-family: '([^']+)'", b).group(1)
        vaha   = re.search(r'font-weight: (\d+)', b).group(1)
        url    = re.search(r'url\((https://fonts\.gstatic\.com[^)]+)\)', b).group(1)
        jmeno  = '%s-%s-%s.woff2' % (rodina.lower().replace(' ', '-'), vaha, m.group(1))
        cesta  = os.path.join(DIR_FONTS, jmeno)
        if not os.path.exists(cesta):
            io.open(cesta, 'wb').write(stahni(url))
        ven.append(b.replace(url, '../fonts/' + jmeno).strip())
    io.open(os.path.join(DIR_CSS, 'pisma.css'), 'w', encoding='utf-8').write(
        HLAVICKA + '\n\n'.join(ven) + '\n')
    print('řezů: %d, souborů: %d' % (len(ven), len(os.listdir(DIR_FONTS))))


if __name__ == '__main__':
    main()
