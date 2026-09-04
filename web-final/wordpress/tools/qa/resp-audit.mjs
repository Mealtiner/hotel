/**
 * GRID Hotel — responzivní audit (QA protokol GRID-RESPONSIVE-04)
 *
 * Měří pro každou stránku × jazyk × viewport:
 *   - skutečný horizontální overflow (dočasně vypne overflow-x:hidden, které ho maskuje)
 *   - prvky přetékající vpravo za viewport
 *   - obsahový koridor (levý/pravý okraj sdílených kontejnerů)
 *   - viditelnost pravé sekční navigace a levého HUD
 *   - počet sloupců klíčových mřížek
 *
 * Použití:
 *   node resp-audit.mjs                      # vše
 *   node resp-audit.mjs --pages=domov,kontakt
 *   node resp-audit.mjs --vp=390,1024 --lang=cs
 *   node resp-audit.mjs --shots               # + fullpage screenshoty
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { PAGES, VIEWPORTS, CORRIDOR, GRIDS, ORIGIN, RESOLVER_RULE } from './qa-config.mjs';

const argv = Object.fromEntries(process.argv.slice(2).map(a => {
  const [k, v] = a.replace(/^--/, '').split('='); return [k, v ?? true];
}));
const OUT = argv.out || path.join(path.dirname(new URL(import.meta.url).pathname), 'out');
fs.mkdirSync(OUT, { recursive: true });

const pick = (list, key, field = 'id') =>
  key === undefined || key === true ? list : list.filter(x => String(key).split(',').includes(String(x[field])));

const pages = pick(PAGES, argv.pages);
const viewports = pick(VIEWPORTS, argv.vp, 'w');
const langs = argv.lang ? String(argv.lang).split(',') : ['cs', 'en', 'de'];

/* Animace a reveal efekty vypnout — jinak měříme prvky uprostřed přechodu. */
const FREEZE = `*,*::before,*::after{animation:none !important;transition:none !important}
  .reveal{opacity:1 !important;transform:none !important}html{scroll-behavior:auto !important}`;

/** Vše, co se měří uvnitř stránky. Běží v kontextu prohlížeče. */
function measure(cfg) {
  const { vw, corridor, grids } = cfg;
  const R = e => { const b = e.getBoundingClientRect(); return { L: Math.round(b.left), R: Math.round(b.right), W: Math.round(b.width) }; };
  const vis = e => { const cs = getComputedStyle(e); return cs.display !== 'none' && cs.visibility !== 'hidden' && e.getBoundingClientRect().width > 0; };

  /* Skutečný overflow: overflow-x:hidden na body ho schová, proto ho na okamžik vypneme. */
  const prev = [document.body.style.overflowX, document.documentElement.style.overflowX];
  document.body.style.overflowX = 'visible'; document.documentElement.style.overflowX = 'visible';
  const trueScrollWidth = document.documentElement.scrollWidth;
  document.body.style.overflowX = prev[0]; document.documentElement.style.overflowX = prev[1];

  /* Prvky za pravým okrajem. Fixed overlaye (HUD, navigace) se nepočítají — ty tam patří. */
  const overflowers = [];
  document.querySelectorAll('body *').forEach(e => {
    const cs = getComputedStyle(e);
    if (cs.position === 'fixed' || cs.display === 'none') return;
    const b = e.getBoundingClientRect();
    if (b.width > 0 && b.right > vw + 1) {
      overflowers.push({ tag: e.tagName, cls: (e.className.toString() || '').slice(0, 60), R: Math.round(b.right), over: Math.round(b.right - vw) });
    }
  });
  overflowers.sort((a, b) => b.over - a.over);

  /* Koridor: levý a pravý okraj každého sdíleného kontejneru. */
  const corr = {};
  for (const sel of corridor) {
    const els = [...document.querySelectorAll(sel)].filter(vis);
    if (!els.length) { corr[sel] = null; continue; }
    corr[sel] = els.map(R);
  }

  /* Počet sloupců mřížek — z gridTemplateColumns, ne z odhadu. */
  const cols = {};
  for (const sel of grids) {
    const e = document.querySelector(sel);
    if (!e || !vis(e)) { cols[sel] = null; continue; }
    const t = getComputedStyle(e).gridTemplateColumns;
    cols[sel] = { n: t && t !== 'none' ? t.trim().split(/\s+/).length : 0, tpl: (t || '').slice(0, 80), rect: R(e) };
  }

  const one = sel => { const e = document.querySelector(sel); return e ? { vis: vis(e), ...R(e) } : null; };

  return {
    trueScrollWidth, clientWidth: document.documentElement.clientWidth,
    overflow: trueScrollWidth - document.documentElement.clientWidth,
    rail: one('.track-progress'), hud: one('.telemetry-hud, .garry-hud, #hud'),
    corridor: corr, cols, overflowers: overflowers.slice(0, 15),
    h1: [...document.querySelectorAll('h1')].map(e => (e.textContent || '').trim().slice(0, 60)),
  };
}

const browser = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const report = [];
let done = 0; const total = pages.length * langs.length * viewports.length;

for (const vp of viewports) {
  const ctx = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, ignoreHTTPSErrors: true, deviceScaleFactor: 1 });
  for (const pg of pages) {
    for (const lang of langs) {
      const url = pg.url[lang];
      if (!url) continue;
      const page = await ctx.newPage();
      let rec = { page: pg.id, lang, vw: vp.w, vh: vp.h, url };
      try {
        const resp = await page.goto(ORIGIN + url, { waitUntil: 'domcontentloaded', timeout: 45000 });
        rec.status = resp ? resp.status() : 0;
        await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
        await page.addStyleTag({ content: FREEZE });
        await page.evaluate(async () => { // lazy obrázky
          for (let y = 0; y < document.body.scrollHeight; y += 900) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 25)); }
          window.scrollTo(0, 0);
        });
        await page.waitForTimeout(250);
        Object.assign(rec, await page.evaluate(measure, { vw: vp.w, corridor: CORRIDOR, grids: GRIDS }));
        if (argv.shots) {
          const d = path.join(OUT, 'shots', `${vp.w}`); fs.mkdirSync(d, { recursive: true });
          await page.screenshot({ path: path.join(d, `${pg.id}-${lang}.png`), fullPage: true });
        }
      } catch (e) { rec.error = String(e).slice(0, 200); }
      report.push(rec);
      await page.close();
      process.stderr.write(`\r  ${++done}/${total}  ${vp.w}px ${pg.id}/${lang}          `);
    }
  }
  await ctx.close();
}
await browser.close();
process.stderr.write('\n');
fs.writeFileSync(path.join(OUT, 'resp-audit.json'), JSON.stringify(report, null, 1));
console.log(`Zapsano: ${path.join(OUT, 'resp-audit.json')}  (${report.length} mereni)`);
