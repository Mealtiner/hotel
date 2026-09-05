import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
for (const [n,u,sel] of [['domov','/de/startseite/','.ff-el-group'],['poukazy','/zazitky/darkove-poukazy/','.rd-main'],['sezona','/en/season/','.ev-row']]) {
  const p = await b.newPage({ viewport: { width: 320, height: 700 } });
  await p.goto(ORIGIN + u, { waitUntil: 'load', timeout: 60000 });
  const r = await p.evaluate((sel) => {
    const el = document.querySelector(sel); if(!el) return null;
    const out=[]; let e=el;
    for(let i=0;i<6&&e;i++,e=e.parentElement){
      const cs=getComputedStyle(e), bb=e.getBoundingClientRect();
      out.push({t:e.tagName,c:(e.className.toString()||'').slice(0,45),L:Math.round(bb.left),R:Math.round(bb.right),W:Math.round(bb.width),w:cs.width,mw:cs.minWidth,pl:cs.paddingLeft,pr:cs.paddingRight,disp:cs.display,cols:cs.gridTemplateColumns.slice(0,45)});
    }
    return out;
  }, sel);
  console.log(`\n### ${n} ${sel}`);
  for(const x of (r||[])) console.log('  ', JSON.stringify(x));
  await p.close();
}
await b.close();
