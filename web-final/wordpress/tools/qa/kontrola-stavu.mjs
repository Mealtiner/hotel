import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const p = await b.newPage({ viewport: { width: 1600, height: 900 } });
await p.goto(ORIGIN + '/kontakt/', { waitUntil: 'load', timeout: 45000 });

async function stav(sel, popis) {
  const el = p.locator(sel).first();
  if (!await el.count()) { console.log(`  ${popis}: nenalezeno`); return; }
  const klid = await el.evaluate(e => { const c = getComputedStyle(e); return { bg: c.backgroundColor, bc: c.borderColor, t: c.transform }; });
  await el.hover();
  await p.waitForTimeout(320);
  const hov = await el.evaluate(e => { const c = getComputedStyle(e); return { bg: c.backgroundColor, bc: c.borderColor, t: c.transform }; });
  await p.evaluate(s => document.querySelector(s).focus(), sel);
  await p.waitForTimeout(120);
  const foc = await el.evaluate(e => getComputedStyle(e).boxShadow);
  console.log(`  ${popis}`);
  console.log(`     klid : bg=${klid.bg} ramecek=${klid.bc}`);
  console.log(`     hover: bg=${hov.bg} ramecek=${hov.bc} pohyb=${hov.t === 'none' ? 'zadny' : 'ano'}`);
  console.log(`     focus: ${foc.slice(0, 78)}`);
}
await stav('a.btn:not(.btn-ghost)', 'PRIMARNI (svetla sekce)');
await stav('a.btn.btn-ghost', 'SEKUNDARNI (svetla sekce)');
await p.goto(ORIGIN + '/', { waitUntil: 'load', timeout: 45000 });
await stav('a.sec-more', 'TERCIARNI');
await b.close();
