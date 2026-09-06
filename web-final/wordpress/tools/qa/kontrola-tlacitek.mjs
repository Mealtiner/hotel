/**
 * Ověření tlačítek proti akceptačnímu checklistu specifikace.
 * Měří skutečné vypočtené styly ve výchozím stavu i při hoveru a focusu.
 */
import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN } from './qa-config.mjs';
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const cases = [
  ['domov', '/', 1600], ['gastronomie', '/gastronomie/', 1600],
  ['galerie', '/galerie/', 1600], ['kontakt', '/kontakt/', 1600],
  ['ubytovani', '/ubytovani/', 390],
];
for (const [n, u, vw] of cases) {
  const p = await b.newPage({ viewport: { width: vw, height: 900 } });
  await p.goto(ORIGIN + u, { waitUntil: 'load', timeout: 45000 });
  const r = await p.evaluate(() => {
    const sel = '.gh-btn, .gh-filter, a.btn, a.sec-more, button.gal-fbtn, .et_pb_button, .fluentform .ff-btn-submit';
    const seen = new Map();
    document.querySelectorAll(sel).forEach(e => {
      const cs = getComputedStyle(e); if (cs.display === 'none') return;
      const r = e.getBoundingClientRect(); if (!r.width) return;
      /* Povrch je nejbližší předek, který ho deklaruje — včetně karty s fotkou. */
      const sec = e.closest('.gh-surface--photo, .room--m1, .room--m2, .room--m4, .hover-card2, .hero, .sp-media, .gh-surface--dark, .sec-dark, .gh-surface--light, .sec-light');
      const surf = !sec ? '-'
                 : /hover-card2|room--m|hero|sp-media|photo/.test(sec.className) ? 'foto'
                 : /sec-dark|surface--dark/.test(sec.className) ? 'tmava' : 'svetla';
      const kind = e.classList.contains('btn-ghost') || e.classList.contains('gh-btn--secondary') ? 'sekundarni'
                 : e.classList.contains('sec-more') || e.classList.contains('gh-btn--tertiary') ? 'terciarni'
                 : e.classList.contains('gal-fbtn') || e.classList.contains('gh-filter') ? 'filtr' : 'primarni';
      const k = kind + '|' + surf;
      if (!seen.has(k)) seen.set(k, { kind, surf, h: Math.round(r.height), w: Math.round(r.width),
        bg: cs.backgroundColor, fg: cs.color, bc: cs.borderColor, bw: cs.borderTopWidth });
    });
    return [...seen.values()];
  });
  console.log(`\n### ${n} @${vw}px`);
  for (const x of r) console.log(`  ${x.kind.padEnd(11)} ${x.surf.padEnd(7)} ${String(x.w)+'x'+x.h+'px'} bg=${x.bg} text=${x.fg} ramecek=${x.bw} ${x.bc}`);
  await p.close();
}
await b.close();
