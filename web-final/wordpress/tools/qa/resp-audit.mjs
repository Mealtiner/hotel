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
import { PAGES, VIEWPORTS, CORRIDOR, GRIDS, ORIGIN, RESOLVER_RULE, CORRIDOR_EXEMPT } from './qa-config.mjs';

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
  /* Koridor je dán vnitřní hranou obsahu, ne vnějším rámečkem: .wrap je
     vycentrovaný box, který si odstup od HUD drží vlastním paddingem.
     Měřit border-box by hlásilo chybu tam, kde je obsah správně odsazený. */
  const R = e => {
    const b = e.getBoundingClientRect(), cs = getComputedStyle(e);
    const pl = parseFloat(cs.paddingLeft) || 0, pr = parseFloat(cs.paddingRight) || 0;
    return { L: Math.round(b.left + pl), R: Math.round(b.right - pr), W: Math.round(b.width - pl - pr),
             boxL: Math.round(b.left), boxR: Math.round(b.right) };
  };
  const vis = e => { const cs = getComputedStyle(e); return cs.display !== 'none' && cs.visibility !== 'hidden' && e.getBoundingClientRect().width > 0; };

  /* Skutečný overflow: overflow-x:hidden na body ho schová, proto ho na okamžik vypneme. */
  const prev = [document.body.style.overflowX, document.documentElement.style.overflowX];
  document.body.style.overflowX = 'visible'; document.documentElement.style.overflowX = 'visible';
  const trueScrollWidth = document.documentElement.scrollWidth;
  document.body.style.overflowX = prev[0]; document.documentElement.style.overflowX = prev[1];

  /* Prvky za pravým okrajem.
     Nezapočítávají se: fixed overlaye (HUD, navigace — tam patří) a prvky,
     které leží uvnitř kontejneru s vlastním ořezem nebo posuvem
     (overflow-x: auto/scroll/hidden). Takový prvek šířku dokumentu rozšířit
     nemůže — široká srovnávací tabulka ve vlastním posuvném rámu je záměr,
     ne chyba. */
  const clipped = e => {
    /* body/html se přeskakují schválně — právě jejich overflow-x:hidden je to,
       co vadu maskuje, a měříme ji navzdory němu. */
    for (let a = e.parentElement; a && a !== document.body && a !== document.documentElement; a = a.parentElement) {
      const ox = getComputedStyle(a).overflowX;
      if (ox === 'auto' || ox === 'scroll' || ox === 'hidden') return true;
    }
    return false;
  };
  const overflowers = [];
  document.querySelectorAll('body *').forEach(e => {
    const cs = getComputedStyle(e);
    if (cs.position === 'fixed' || cs.display === 'none') return;
    const b = e.getBoundingClientRect();
    if (b.width > 0 && b.right > vw + 1 && !clipped(e)) {
      overflowers.push({ tag: e.tagName, cls: (e.className.toString() || '').slice(0, 60), R: Math.round(b.right), over: Math.round(b.right - vw) });
    }
  });
  overflowers.sort((a, b) => b.over - a.over);

  /* Koridor: levý a pravý okraj každého sdíleného kontejneru. */
  const corr = {};
  for (const sel of corridor) {
    const els = [...document.querySelectorAll(sel)].filter(vis);
    if (!els.length) { corr[sel] = null; continue; }
    corr[sel] = els.map(e => {
      const r = R(e);
      /* Schválené výjimky se označí už při měření — report je pak jen přeskočí. */
      r.exempt = cfg.exempt.some(x => e.matches(x));
      return r;
    });
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

/* Jednotky práce: každá kombinace stránka × jazyk × viewport. Běží po
   dávkách paralelně — jeden průchod je jinak na 29 stránkách × 3 jazycích
   × 11 viewportech desítky minut. */
const jobs = [];
for (const vp of viewports) for (const pg of pages) for (const lang of langs) {
  if (pg.url[lang]) jobs.push({ vp, pg, lang, url: pg.url[lang] });
}
const CONC = Number(argv.conc || 6);
let done = 0;

async function run(job) {
  const { vp, pg, lang, url } = job;
  const ctx = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, ignoreHTTPSErrors: true, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  const rec = { page: pg.id, lang, vw: vp.w, vh: vp.h, url };
  try {
    const resp = await page.goto(ORIGIN + url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    rec.status = resp ? resp.status() : 0;
    await page.waitForLoadState('load', { timeout: 20000 }).catch(() => {});
    await page.addStyleTag({ content: FREEZE });
    await page.evaluate(async () => { // lazy obrázky
      for (let y = 0; y < document.body.scrollHeight; y += 1200) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 15)); }
      window.scrollTo(0, 0);
    });
    await page.waitForTimeout(150);
    Object.assign(rec, await page.evaluate(measure, { vw: vp.w, corridor: CORRIDOR, grids: GRIDS, exempt: CORRIDOR_EXEMPT }));
    if (argv.shots) {
      const d = path.join(OUT, 'shots', `${vp.w}`); fs.mkdirSync(d, { recursive: true });
      await page.screenshot({ path: path.join(d, `${pg.id}-${lang}.png`), fullPage: true });
    }
  } catch (e) { rec.error = String(e).slice(0, 200); }
  report.push(rec);
  await ctx.close();
  process.stderr.write(`\r  ${++done}/${jobs.length}  ${vp.w}px ${pg.id}/${lang}            `);
}

for (let i = 0; i < jobs.length; i += CONC) {
  await Promise.all(jobs.slice(i, i + CONC).map(run));
}
await browser.close();
process.stderr.write('\n');
fs.writeFileSync(path.join(OUT, 'resp-audit.json'), JSON.stringify(report, null, 1));
console.log(`Zapsano: ${path.join(OUT, 'resp-audit.json')}  (${report.length} mereni)`);
