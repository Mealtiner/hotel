import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
await p.goto(ORIGIN + '/ubytovani/', { waitUntil: 'load', timeout: 60000 });
const r = await p.evaluate(() => {
  const w = [...document.querySelectorAll('.sec .wrap')].filter(e=>!e.classList.contains('split'))[0];
  const out=[]; let e=w;
  for(let i=0;i<5&&e;i++,e=e.parentElement){
    const cs=getComputedStyle(e), bb=e.getBoundingClientRect();
    out.push({t:e.tagName,c:(e.className.toString()||'').slice(0,52),L:Math.round(bb.left),R:Math.round(bb.right),W:Math.round(bb.width),
      w:cs.width,mw:cs.maxWidth,pl:cs.paddingLeft,pr:cs.paddingRight,ml:cs.marginLeft,mr:cs.marginRight});
  }
  const cssVar = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
  return {chain:out, vars:{maxw:cssVar('--maxw'), hudPad:cssVar('--hud-pad'), railPad:cssVar('--rail-pad'), wrapInset:cssVar('--wrap-inset'), contentWidth:cssVar('--content-width')}};
});
console.log('PROMENNE:', JSON.stringify(r.vars, null, 1));
console.log('\nRETEZEC:');
for(const x of r.chain) console.log(' ', JSON.stringify(x));
await b.close();
