import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const cil = { 1600:[290,1340], 1440:[290,1180], 1366:[290,1106], 1280:[290,1020], 1024:[284,764], 960:[283,700], 768:[245,737], 641:[244,615], 390:[20,370] };
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
console.log('stranka s listou (/gastronomie/) — zmereno vs zadani');
for (const vw of Object.keys(cil).map(Number)) {
  const p = await b.newPage({ viewport: { width: vw, height: 900 } });
  await p.goto(ORIGIN + '/gastronomie/', { waitUntil: 'load', timeout: 60000 });
  const r = await p.evaluate(() => {
    const w=[...document.querySelectorAll('.sec .wrap')].filter(e=>!e.classList.contains('split')&&getComputedStyle(e).display!=='none')[0];
    if(!w) return null; const bb=w.getBoundingClientRect(), cs=getComputedStyle(w);
    return {L:Math.round(bb.left+(parseFloat(cs.paddingLeft)||0)), R:Math.round(bb.right-(parseFloat(cs.paddingRight)||0))};
  });
  const [cl,cr]=cil[vw];
  const okL=Math.abs(r.L-cl)<=2, okR=Math.abs(r.R-cr)<=2;
  console.log(`  ${String(vw).padStart(5)}px  zmereno ${String(r.L).padStart(4)}..${String(r.R).padStart(4)}   zadani ${String(cl).padStart(4)}..${String(cr).padStart(4)}   ${okL?'OK':'L!'} ${okR?'OK':'P!'}`);
  await p.close();
}
await b.close();
