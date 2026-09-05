/**
 * GRID Hotel — vyhodnocení responzivního auditu proti akceptačním kritériím.
 *
 * Čte out/resp-audit.json a hlásí porušení pravidel z GRID-RESPONSIVE-02/04:
 *   OVERFLOW   horizontální přetečení dokumentu (tolerance 0 px)
 *   PRVEK      konkrétní prvek přesahující pravý okraj viewportu
 *   RAIL       pravá sekční navigace viditelná/skrytá proti pravidlu (hranice 960 px)
 *   KORIDOR    kontejner mimo bezpečné hranice (tolerance ±2 px)
 *   ROZJEZD    sdílené kontejnery nemají shodný levý/pravý okraj
 *   SLOUPCE    počet sloupců mřížky neodpovídá matici (bez tolerance)
 *
 * Použití: node qa-report.mjs [--json] [--page=kontakt] [--rule=SLOUPCE]
 */
import fs from 'fs';
import path from 'path';
import { EXPECTED_COLS, SAFE, RAIL_VISIBLE_FROM, VIEWPORTS, CORRIDOR_ANCHORED, NARROW_CORRIDOR, CORRIDOR_EXEMPT } from './qa-config.mjs';

const argv = Object.fromEntries(process.argv.slice(2).map(a => { const [k, v] = a.replace(/^--/, '').split('='); return [k, v ?? true]; }));
const dir = path.join(path.dirname(new URL(import.meta.url).pathname), 'out');
const data = JSON.parse(fs.readFileSync(path.join(dir, 'resp-audit.json'), 'utf8'));
const modeOf = w => (VIEWPORTS.find(v => v.w === w) || {}).mode;

const findings = [];
const add = (r, rec, msg, detail) => findings.push({ rule: r, page: rec.page, lang: rec.lang, vw: rec.vw, msg, detail });

for (const rec of data) {
  if (rec.error) { add('CHYBA', rec, rec.error); continue; }
  if (rec.status && rec.status >= 400 && rec.page !== '404') { add('CHYBA', rec, `HTTP ${rec.status}`); continue; }
  let mode = modeOf(rec.vw);
  /* V úzkém koridoru se karty, T2/T7 a formulářové dvojice skládají jako na
     mobilu — matice se tam hodnotí podle toho, ne podle tabletu na šířku. */
  const narrow = rec.vw >= NARROW_CORRIDOR.from && rec.vw <= NARROW_CORRIDOR.to;
  const narrowSel = ['.entries', '.rooms', '.exp', '.split', '.foot-top'];

  if (rec.overflow > 0) add('OVERFLOW', rec, `dokument pretekaji o ${rec.overflow} px`);

  for (const o of (rec.overflowers || []).slice(0, 3)) {
    add('PRVEK', rec, `${o.tag}.${o.cls || '(bez tridy)'} presahuje o ${o.over} px`);
  }

  if (rec.rail) {
    const should = rec.vw >= RAIL_VISIBLE_FROM;
    if (rec.rail.vis !== should) add('RAIL', rec, should ? 'sekcni navigace ma byt VIDITELNA, je skryta' : 'sekcni navigace ma byt SKRYTA, je videt');
  }

  const safeDef = SAFE[rec.vw];
  /* Pravá hranice se liší podle toho, jestli je na stránce sekční navigace. */
  const hasRail = !!(rec.rail && rec.rail.vis);
  const safe = safeDef ? { left: safeDef.left, right: hasRail ? safeDef.right : safeDef.rightNoRail } : null;
  if (safe && rec.corridor) {
    const edges = [];
    for (const [sel, list] of Object.entries(rec.corridor)) {
      if (!list) continue;
      for (const b of list) {
        if ((b.boxR - b.boxL) >= rec.vw - 2 && b.W >= rec.vw - 2) continue;  // full-bleed prvek, koridor se ho netyka
        if (b.exempt) continue;                      // schvalena vyjimka z koridoru
        if (CORRIDOR_ANCHORED.includes(sel)) edges.push({ sel, ...b });
        if (b.L < safe.left - 2)  add('KORIDOR', rec, `${sel} zacina na ${b.L}, bezpecna hranice je ${safe.left}`);
        if (b.R > safe.right + 2) add('KORIDOR', rec, `${sel} konci na ${b.R}, bezpecna hranice je ${safe.right}`);
      }
    }
    for (const side of ['L', 'R']) {
      const vals = [...new Set(edges.map(e => e[side]))];
      if (vals.length > 1 && Math.max(...vals) - Math.min(...vals) > 2) {
        const lo = Math.min(...vals), hi = Math.max(...vals);
        const names = s => [...new Set(edges.filter(e => e[side] === s).map(e => e.sel))].join('/');
        add('ROZJEZD', rec, `${side === 'L' ? 'leve' : 'prave'} okraje se lisi o ${hi - lo} px`,
            `${names(lo)}=${lo} vs ${names(hi)}=${hi}`);
      }
    }
  }

  for (const [sel, exp] of Object.entries(EXPECTED_COLS)) {
    const got = rec.cols && rec.cols[sel];
    const m = (narrow && narrowSel.includes(sel) && sel !== '.foot-top') ? 'mobil' : mode;
    if (!got || !exp[m]) continue;
    if (got.n !== exp[m]) add('SLOUPCE', rec, `${sel} ma ${got.n} sloupcu, ocekava se ${exp[m]}`, got.tpl);
  }
}

if (argv.json) { console.log(JSON.stringify(findings, null, 1)); process.exit(0); }

let f = findings;
if (argv.page) f = f.filter(x => String(argv.page).split(',').includes(x.page));
if (argv.rule) f = f.filter(x => String(argv.rule).split(',').includes(x.rule));

const byRule = {}; for (const x of f) (byRule[x.rule] ||= []).push(x);
console.log(`\nMERENI: ${data.length}   NALEZY: ${f.length}\n` + '='.repeat(78));
for (const rule of ['CHYBA', 'OVERFLOW', 'RAIL', 'SLOUPCE', 'KORIDOR', 'ROZJEZD', 'PRVEK']) {
  const list = byRule[rule]; if (!list) continue;
  console.log(`\n### ${rule}  (${list.length})`);
  const byPage = {}; for (const x of list) (byPage[`${x.page}`] ||= []).push(x);
  for (const [pg, items] of Object.entries(byPage)) {
    const msgs = {}; for (const i of items) (msgs[i.msg] ||= []).push(`${i.vw}/${i.lang}`);
    console.log(`  ${pg}`);
    for (const [m, where] of Object.entries(msgs)) {
      const vws = [...new Set(where.map(w => w.split('/')[0]))].sort((a,b)=>a-b);
      const langs = [...new Set(where.map(w => w.split('/')[1]))];
      console.log(`     ${m}\n        @ ${vws.join(',')} px  [${langs.join(',')}]`);
    }
  }
}
console.log('');
