import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
for (const [n,u] of [['domov-de','/de/startseite/'],['firemni-de','/de/firmenevents-hochzeiten/'],['sezona-en','/en/season/'],['poukazy-cs','/zazitky/darkove-poukazy/']]) {
  const p = await b.newPage({ viewport: { width: 320, height: 700 } });
  await p.goto(ORIGIN + u, { waitUntil: 'load', timeout: 60000 });
  const r = await p.evaluate(() => {
    document.body.style.overflowX='visible'; document.documentElement.style.overflowX='visible';
    const sw = document.documentElement.scrollWidth;
    const out=[];
    document.querySelectorAll('body *').forEach(e=>{
      const cs=getComputedStyle(e); if(cs.position==='fixed'||cs.display==='none') return;
      let clip=false;
      for(let a=e.parentElement;a&&a!==document.body;a=a.parentElement){const ox=getComputedStyle(a).overflowX;if(ox==='auto'||ox==='scroll'||ox==='hidden'){clip=true;break}}
      if(clip) return;
      const bb=e.getBoundingClientRect();
      if(bb.width>0&&bb.right>321) out.push({t:e.tagName,c:(e.className.toString()||'').slice(0,40),L:Math.round(bb.left),R:Math.round(bb.right),W:Math.round(bb.width),ws:cs.whiteSpace,txt:(e.textContent||'').trim().slice(0,35)});
    });
    return {sw,out:out.sort((a,b)=>b.R-a.R).slice(0,4)};
  });
  console.log(`\n### ${n}  scrollWidth=${r.sw}`);
  for(const x of r.out) console.log(`   ${x.t}.${x.c} ${x.L}-${x.R} W=${x.W} ws=${x.ws} "${x.txt}"`);
  await p.close();
}
await b.close();
