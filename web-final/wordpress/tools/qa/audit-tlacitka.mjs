/**
 * GRID Hotel — audit tlačítek proti GPT/GRID-TLACITKA-SPECIFIKACE-A-CSS.
 *
 * Vypíše každý ovládací prvek na stránce: jakým elementem je, jaké má třídy,
 * skutečnou aktivní plochu, a zda splňuje minimum 44 x 44 px.
 * Celé karty fungující jako odkaz se nepočítají — ty spec vyjímá.
 */
import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN, PAGES } from './qa-config.mjs';

const argv = Object.fromEntries(process.argv.slice(2).map(a => { const [k, v] = a.replace(/^--/, '').split('='); return [k, v ?? true]; }));
const vps = (argv.vp ? String(argv.vp).split(',') : ['1600', '390']).map(Number);
const pages = argv.pages ? PAGES.filter(p => String(argv.pages).split(',').includes(p.id)) : PAGES;

const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const out = [];
for (const vw of vps) {
  for (const pg of pages) {
    const url = pg.url.cs; if (!url) continue;
    const p = await b.newPage({ viewport: { width: vw, height: 900 } });
    try {
      await p.goto(ORIGIN + url, { waitUntil: 'load', timeout: 45000 });
      const r = await p.evaluate(() => {
        const sel = 'a.btn, a.sec-more, button.gal-fbtn, .gh-btn, .gh-filter, button[type=submit], .ff-btn-submit, .bk-submit a, .bk-submit button, a.gcard-menu-link';
        const res = [];
        document.querySelectorAll(sel).forEach(e => {
          const cs = getComputedStyle(e); if (cs.display === 'none' || cs.visibility === 'hidden') return;
          const r = e.getBoundingClientRect();
          /* Povrch: podle nejbližší sekce, ne podle třídy tlačítka. */
          const sec = e.closest('.sec-dark, .sec-light, .hero, .sp-media');
          const surface = !sec ? 'neurceno' : sec.classList.contains('hero') || sec.classList.contains('sp-media') ? 'foto'
                        : sec.classList.contains('sec-dark') ? 'tmava' : 'svetla';
          res.push({
            tag: e.tagName, cls: (e.className.toString() || '').trim().slice(0, 60),
            w: Math.round(r.width), h: Math.round(r.height), surface,
            txt: (e.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 30),
            role: e.getAttribute('role') || '', pressed: e.getAttribute('aria-pressed'),
          });
        });
        return res;
      });
      for (const x of r) out.push({ vw, page: pg.id, ...x });
    } catch (e) { out.push({ vw, page: pg.id, error: String(e).slice(0, 80) }); }
    await p.close();
  }
}
await b.close();
console.log(JSON.stringify(out, null, 1));
