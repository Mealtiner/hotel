import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
console.log('DOMOV (lista je zapnuta) — cil dle zadani: 1600 -> 290..1340, 1024 -> 284..764');
for (const vw of [1600, 1280, 1024, 960]) {
  const p = await b.newPage({ viewport: { width: vw, height: 900 } });
  await p.goto(ORIGIN + '/', { waitUntil: 'load', timeout: 60000 });
  const r = await p.evaluate(() => {
    const w = [...document.querySelectorAll('.sec .wrap')].filter(e=>!e.classList.contains('split')&&getComputedStyle(e).display!=='none')[0];
    if(!w) return null;
    const bb=w.getBoundingClientRect(), cs=getComputedStyle(w);
    const rail=document.querySelector('.track-progress');
    const rb=rail&&getComputedStyle(rail).display!=='none'?rail.getBoundingClientRect():null;
    return {L:Math.round(bb.left+(parseFloat(cs.paddingLeft)||0)), R:Math.round(bb.right-(parseFloat(cs.paddingRight)||0)), railL: rb?Math.round(rb.left):null};
  });
  console.log(`  ${String(vw).padStart(5)}px  obsah ${r.L}..${r.R}   osa listy ~${r.railL!==null?r.railL+15:'—'}   lista od ${r.railL ?? '—'}`);
  await p.close();
}
await b.close();
