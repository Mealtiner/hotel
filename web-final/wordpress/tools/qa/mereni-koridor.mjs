import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
console.log('viewport | levá mezera | pravá mezera | šířka obsahu | rozdíl L-P | HUD končí na');
console.log('---------+-------------+--------------+--------------+------------+-------------');
for (const vw of [2560, 1920, 1600, 1440, 1366, 1280, 1279, 1024, 960, 959, 768, 641, 640, 390, 320]) {
  const p = await b.newPage({ viewport: { width: vw, height: 900 } });
  await p.goto(ORIGIN + '/ubytovani/', { waitUntil: 'load', timeout: 60000 });
  const r = await p.evaluate((vw) => {
    // hlavni obsahovy kontejner sekce (ne rezervacni lista, ne hero)
    const wraps = [...document.querySelectorAll('.sec .wrap')].filter(e => {
      const cs = getComputedStyle(e); return cs.display !== 'none' && !e.classList.contains('split');
    });
    const w = wraps[0]; if (!w) return null;
    const bb = w.getBoundingClientRect(), cs = getComputedStyle(w);
    const L = Math.round(bb.left + (parseFloat(cs.paddingLeft)||0));
    const R = Math.round(bb.right - (parseFloat(cs.paddingRight)||0));
    const hud = document.querySelector('.telemetry-hud');
    const hb = hud ? hud.getBoundingClientRect() : null;
    return { L, R, W: R - L, hudR: hb && getComputedStyle(hud).display !== 'none' ? Math.round(hb.right) : null };
  }, vw);
  if (!r) { console.log(`${String(vw).padStart(8)} | (nenalezeno)`); await p.close(); continue; }
  const right = vw - r.R;
  const diff = r.L - right;
  console.log(`${String(vw).padStart(8)} |${String(r.L).padStart(12)} |${String(right).padStart(13)} |${String(r.W).padStart(13)} |${String(diff>0?'+'+diff:diff).padStart(11)} |${String(r.hudR ?? '—').padStart(12)}`);
  await p.close();
}
await b.close();
