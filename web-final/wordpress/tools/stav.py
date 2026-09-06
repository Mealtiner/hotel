#!/usr/bin/env python3
"""
GRID Hotel — generátor STAVU projektu (jediné místo pravdy).

Nic nezapisuje ručně. Všechno čte ze skutečnosti: verze z hlaviček souborů,
rozdíly mezi repem a nasazeným webem z porovnání souborů, stav gitu z gitu
a stav obsahu z databáze. Ručně psaný přehled by se rozešel — přesně to se
na tomhle projektu už dvakrát stalo.

Použití:
    python3 web-final/wordpress/tools/stav.py          # vypíše a zapíše STAV.md
    python3 web-final/wordpress/tools/stav.py --tisk   # jen vypíše
"""
import json, os, pathlib, re, subprocess, sys, datetime

KOREN = pathlib.Path(__file__).resolve().parents[3]
REPO = KOREN / 'web-final' / 'wordpress'
WEB = pathlib.Path.home() / 'Local Sites' / 'grid-hotel' / 'app' / 'public' / 'wp-content'
PORT = 10018

def bezi(cmd, **kw):
    try:
        return subprocess.run(cmd, capture_output=True, text=True, timeout=60, **kw).stdout.strip()
    except Exception:
        return ''

def verze_z_hlavicky(p):
    if not p.exists(): return None
    m = re.search(r'Version:\s*([0-9][0-9.]*)', p.read_text(errors='replace')[:4000])
    return m.group(1) if m else None

def slozky_pluginu():
    return sorted(d.name for d in REPO.iterdir()
                  if d.is_dir() and (d.name.startswith('garry-') or d.name.startswith('gridhotel-')))

def wp(*args):
    """wp-cli přes PHP a socket Localu. Vrátí prázdno, když web neběží."""
    php = pathlib.Path.home() / 'Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php'
    sock = pathlib.Path.home() / 'Library/Application Support/Local/run/BGQr9NWq9/mysql/mysqld.sock'
    phar = next((p for p in (KOREN / 'web-final/wordpress/tools').glob('wp-cli.phar')), None) \
        or pathlib.Path('/private/tmp/claude-501/-Users-mealtiner-GIT-GRIDhotel/64e30dd8-57e8-4f0e-8035-9d238fd2adb7/scratchpad/wp-cli.phar')
    if not (php.exists() and sock.exists() and phar.exists()): return ''
    return bezi([str(php), '-d', f'mysqli.default_socket={sock}', str(phar),
                 f'--path={WEB.parent}', *args])

def sekce_verze():
    r = ['| Součást | Repo | Nasazeno | Manifest | Shoda |', '|---|---|---|---|---|']
    ct_repo = verze_z_hlavicky(REPO / 'grid-divi5-child' / 'style.css')
    ct_web = verze_z_hlavicky(WEB / 'themes' / 'grid-divi5-child' / 'style.css')
    shoda = 'ano' if ct_repo == ct_web else '**NE**'
    r.append(f'| grid-divi5-child (šablona) | {ct_repo or "?"} | {ct_web or "neběží"} | — | {shoda} |')
    for pl in slozky_pluginu():
        vr = verze_z_hlavicky(REPO / pl / f'{pl}.php')
        vw = verze_z_hlavicky(WEB / 'plugins' / pl / f'{pl}.php')
        mf = REPO / pl / 'garry-plugin-manifest.json'
        mv = '—'
        if mf.exists():
            try: mv = json.loads(mf.read_text()).get('plugin_version', '?')
            except Exception: mv = 'neplatný'
        ok = (vr == vw) and (mv in ('—', vr))
        r.append(f'| {pl} | {vr or "?"} | {vw or "chybí"} | {mv} | {"ano" if ok else "**NE**"} |')
    return r

def sekce_rozdilu():
    r = []
    dvojice = [('grid-divi5-child', REPO / 'grid-divi5-child', WEB / 'themes' / 'grid-divi5-child')]
    dvojice += [(pl, REPO / pl, WEB / 'plugins' / pl) for pl in slozky_pluginu()]
    rozdilne = []
    for nazev, a, b in dvojice:
        if not b.exists(): rozdilne.append(f'{nazev} (na webu chybí)'); continue
        out = bezi(['diff', '-rq', '--exclude=.DS_Store', str(a), str(b)])
        if out: rozdilne.append(nazev)
    if rozdilne:
        r.append('**Repo a nasazený web se ROZCHÁZEJÍ** u: ' + ', '.join(rozdilne) + '.')
        r.append('')
        r.append('Než cokoli změníš, zjisti která strana je novější a tu druhou dorovnej.')
        r.append('Nikdy neřeš rozpor nasazením staršího souboru z repa.')
    else:
        r.append('Repo a nasazený web jsou shodné.')
    return r

def sekce_git():
    v = bezi(['git', 'branch', '--show-current'], cwd=KOREN)
    posl = bezi(['git', 'log', '-1', '--format=%h %ad %s', '--date=short'], cwd=KOREN)
    bezi(['git', 'fetch', '--quiet', 'origin'], cwd=KOREN)
    ab = bezi(['git', 'rev-list', '--left-right', '--count', f'origin/{v}...HEAD'], cwd=KOREN)
    zmeny = [l for l in bezi(['git', 'status', '--porcelain'], cwd=KOREN).splitlines()
             if not l.endswith('foto/')]
    r = [f'- Větev: `{v}`', f'- Poslední commit: {posl}']
    if ab:
        za, pred = (ab.split() + ['?', '?'])[:2]
        r.append(f'- Proti origin: {pred} commitů neodeslaných, {za} nestažených')
    r.append(f'- Necommitnutých změn: {len(zmeny)}')
    for l in zmeny[:12]: r.append(f'    - `{l.strip()}`')
    return r

def sekce_web():
    kod = bezi(['curl', '-s', '-o', '/dev/null', '-w', '%{http_code}', '--max-time', '8',
                '-H', 'Host: gridhotel.local', f'http://127.0.0.1:{PORT}/'])
    if kod != '200':
        return [f'- Web na `127.0.0.1:{PORT}` neodpovídá (HTTP {kod or "žádná odpověď"}).',
                '- Postup spuštění je ve skillu `grid-local-nasazeni`.']
    r = [f'- Web běží, HTTP {kod}.']
    bloky = wp('eval', '''
$bad=0;$tot=0;
foreach (get_posts(array("post_type"=>array("page"),"post_status"=>"any","posts_per_page"=>-1,"lang"=>"")) as $p) {
  preg_match_all("~<!--\\s*wp:divi/[a-z-]+\\s*(\\{.*?\\})\\s*/?-->~s", $p->post_content, $m);
  foreach ($m[1] as $j) { $tot++; json_decode($j,true); if (json_last_error()!==JSON_ERROR_NONE) $bad++; }
}
echo "$tot|$bad";''')
    if '|' in bloky:
        tot, bad = bloky.split('|')[:2]
        r.append(f'- Divi bloků v obsahu: {tot}, z toho neplatných: **{bad}**'
                 + ('' if bad == '0' else '  ← rozbité bloky se vykreslí prázdné'))
    obsah = wp('eval', '''
$out=[];
if (function_exists("garry_menu_venue_options")) {
  foreach (garry_menu_venue_options() as $s=>$n) {
    $v = garry_menu_get_venue($s); $d=0;
    foreach ((array)($v["days"]??[]) as $den) $d += count(array_filter((array)$den, fn($x)=>is_array($x)&&array_filter($x)));
    $out[] = "$n:".($d ? "vyplněno" : "PRÁZDNÉ");
  }
}
if (function_exists("gridhotel_get_gallery_blocks")) $out[] = "Galerie:".count((array)gridhotel_get_gallery_blocks())." bloků";
echo implode(", ", $out);''')
    if obsah: r.append(f'- Obsah v pluginech: {obsah}')
    return r

def main():
    d = datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
    t = [f'# GRID Hotel — stav projektu', '',
         f'*Vygenerováno {d} skriptem `web-final/wordpress/tools/stav.py`. Needituj ručně —',
         'při dalším spuštění se přepíše. Všechny údaje jsou čtené ze skutečnosti.*', '',
         '## Kam co patří', '',
         '| Vrstva | Kde | Co tam patří |', '|---|---|---|',
         '| Vzhled a responzivita | `grid-divi5-child` | design tokeny, koridor obsahu, breakpointy, tlačítka, layoutové třídy |',
         '| Obsah a data | pluginy, stránky, příspěvky | texty, fotky, jídelníček, pokoje, zážitky, formuláře |',
         '| Struktura stránky | Divi 5 moduly v obsahu | sekce, řádky, sloupce, nadpisy, tlačítka |', '',
         'Vzhled se **nikdy** neřeší v pluginu ani inline v obsahu. Obsah se **nikdy**',
         'nezadrátuje do CSS. Strukturu vlastní Divi, ne PHP šablona.', '',
         '## Verze', ''] + sekce_verze() + ['', '## Repo proti nasazenému webu', ''] + sekce_rozdilu() \
        + ['', '## Git', ''] + sekce_git() + ['', '## Nasazený web', ''] + sekce_web() + ['']
    text = '\n'.join(t)
    if '--tisk' not in sys.argv:
        (KOREN / 'STAV.md').write_text(text)
    print(text)

if __name__ == '__main__':
    main()
