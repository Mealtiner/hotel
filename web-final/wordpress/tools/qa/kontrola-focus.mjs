import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
for (const [n,u] of [['kontakt','/kontakt/'],['domov','/'],['galerie','/galerie/']]) {
  const p = await b.newPage({ viewport: { width: 1600, height: 900 } });
  await p.goto(ORIGIN + u, { waitUntil: 'load', timeout: 45000 });
  /* Cookie lišta drží focus, dokud se neodbaví — jinak se Tab k obsahu nedostane. */
  const accept = p.locator('.cmplz-btn.cmplz-accept').first();
  if (await accept.count()) { await accept.click({ timeout: 3000 }).catch(() => {}); await p.waitForTimeout(400); }
  const found = new Map();
  for (let i = 0; i < 40; i++) {
    await p.keyboard.press('Tab');
    await p.waitForTimeout(320);   /* box-shadow se přechodem rozsvěcí 250 ms */
    const r = await p.evaluate(() => {
      const e = document.activeElement; if (!e || e === document.body) return null;
      const cls = (e.className.toString() || '');
      if (/cmplz/.test(cls)) return null;
      if (!/\b(btn|sec-more|gal-fbtn|gh-btn|gh-filter|ff-btn-submit|et_pb_button)\b/.test(cls)) return null;
      const cs = getComputedStyle(e);
      const kind = /btn-ghost|gh-btn--secondary/.test(cls) ? 'sekundarni'
                 : /sec-more|gh-btn--tertiary/.test(cls) ? 'terciarni'
                 : /gal-fbtn|gh-filter/.test(cls) ? 'filtr' : 'primarni';
      return { kind, shadow: cs.boxShadow, outline: cs.outlineStyle, txt: (e.textContent||'').trim().slice(0,22) };
    });
    if (r && !found.has(r.kind)) found.set(r.kind, r);
  }
  console.log(`\n### ${n}`);
  if (!found.size) { console.log('  zadne tlacitko v poradi tabulatoru'); }
  for (const [k, v] of found) {
    /* Spec: bílý vnitřní kroužek 3 px a modrý vnější 6 px, čitelné na každém podkladu. */
    const ok = /rgb\(255, 255, 255\) 0px 0px 0px 3px/.test(v.shadow) && /rgb\(0, 95, 204\) 0px 0px 0px 6px/.test(v.shadow);
    console.log(`  ${k.padEnd(11)} "${v.txt}"  focus ${ok ? 'OK (bily + modry kruh)' : 'CHYBI: ' + v.shadow.slice(0,60)}`);
  }
  await p.close();
}
await b.close();
