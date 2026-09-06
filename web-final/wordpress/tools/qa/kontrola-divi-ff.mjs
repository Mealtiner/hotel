import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
for (const [n,u,sel,popis] of [
  ['kontakt','/kontakt/','.fluentform .ff-btn-submit','Fluent Forms odeslani'],
  ['dotaznik','/dotaznik-spokojenosti/','.fluentform .ff-btn-submit','Fluent Forms odeslani'],
  ['podminky','/ubytovaci-a-reklamacni-rad/','.et_pb_button','nativni Divi tlacitko'],
  ['domov','/','.et_pb_button','nativni Divi tlacitko'],
]) {
  const p = await b.newPage({ viewport: { width: 1600, height: 900 } });
  await p.goto(ORIGIN + u, { waitUntil: 'load', timeout: 45000 });
  const el = p.locator(sel).first();
  if (!await el.count()) { console.log(`### ${n}: ${popis} nenalezeno`); await p.close(); continue; }
  const klid = await el.evaluate(e => { const c=getComputedStyle(e), r=e.getBoundingClientRect();
    return {h:Math.round(r.height), bg:c.backgroundColor, fg:c.color, pad:c.padding, bw:c.borderTopWidth, after:getComputedStyle(e,'::after').content}; });
  await el.hover(); await p.waitForTimeout(320);
  const hov = await el.evaluate(e => { const c=getComputedStyle(e), r=e.getBoundingClientRect();
    return {h:Math.round(r.height), bg:c.backgroundColor, pad:c.padding, bw:c.borderTopWidth}; });
  console.log(`### ${n} — ${popis}`);
  console.log(`   klid : h=${klid.h} bg=${klid.bg} text=${klid.fg} padding=${klid.pad} ramecek=${klid.bw} sipka::after=${klid.after}`);
  console.log(`   hover: h=${hov.h} bg=${hov.bg} padding=${hov.pad} ramecek=${hov.bw}  ${klid.h===hov.h && klid.pad===hov.pad ? 'rozmer stabilni' : 'ROZMER SE MENI!'}`);
  await p.close();
}
await b.close();
