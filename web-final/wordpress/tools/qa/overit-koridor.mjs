import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
console.log('stranka s listou (/gastronomie/) — zmereny obsahovy koridor');
for (const vw of [1600, 1440, 1366, 1280, 1024, 960, 959, 768, 641, 640, 390, 320]) {
  const p = await b.newPage({ viewport: { width: vw, height: 900 } });
  await p.goto(ORIGIN + '/gastronomie/', { waitUntil: 'load', timeout: 45000 });
  const r = await p.evaluate(() => {
    const w = [...document.querySelectorAll('.sec .wrap')].filter(e => !e.classList.contains('split') && getComputedStyle(e).display !== 'none')[0];
    if (!w) return null;
    const bb = w.getBoundingClientRect(), cs = getComputedStyle(w);
    const hud = document.querySelector('.telemetry-hud');
    const hb = hud && getComputedStyle(hud).display !== 'none' ? hud.getBoundingClientRect() : null;
    return { L: Math.round(bb.left + (parseFloat(cs.paddingLeft) || 0)), R: Math.round(bb.right - (parseFloat(cs.paddingRight) || 0)),
             hud: hb ? `${Math.round(hb.left)}..${Math.round(hb.right)}` : 'skryty' };
  });
  console.log(`  ${String(vw).padStart(5)}px  obsah ${String(r.L).padStart(4)}..${String(r.R).padStart(4)}  sirka ${String(r.R - r.L).padStart(4)}   HUD ${r.hud}`);
  await p.close();
}
await b.close();
