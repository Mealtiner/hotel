/**
 * Přímý test pravidla „HUD nesmí překrývat text, formuláře ani karty".
 * Projede stránku po obrazovkách a hlásí prvky, jejichž obdélník se protíná
 * s obdélníkem HUD widgetu (a s pravou lištou, kde se vykresluje).
 */
import { chromium } from 'playwright';
import { RESOLVER_RULE, ORIGIN, PAGES } from './qa-config.mjs';
const vps = [1600, 1024, 768];
const b = await chromium.launch({ args: [`--host-resolver-rules=${RESOLVER_RULE}`] });
const nalezy = [];
for (const vw of vps) {
  for (const pg of PAGES) {
    const url = pg.url.cs; if (!url) continue;
    const p = await b.newPage({ viewport: { width: vw, height: 900 } });
    try {
      await p.goto(ORIGIN + url, { waitUntil: 'load', timeout: 45000 });
      const r = await p.evaluate(async () => {
        const overlay = sel => { const e = document.querySelector(sel); if (!e || getComputedStyle(e).display === 'none') return null; return e.getBoundingClientRect(); };
        const zajimave = 'p, h1, h2, h3, h4, li, a.btn, button, input, select, textarea, label, .room, .exp-item, .gastro-card, .entry, .sez-card, .rev';
        const hits = new Map();
        const H = document.documentElement.scrollHeight;
        for (let y = 0; y < H; y += Math.floor(innerHeight * 0.25)) {
          window.scrollTo(0, y);
          await new Promise(r => requestAnimationFrame(r));
          for (const [name, sel] of [['HUD', '.telemetry-hud'], ['LISTA', '.track-progress']]) {
            const o = overlay(sel); if (!o) continue;
            document.querySelectorAll(zajimave).forEach(e => {
              const cs = getComputedStyle(e);
              if (cs.display === 'none' || cs.visibility === 'hidden' || cs.position === 'fixed') return;
              /* Vlastní vnitřek overlayů a cookie lišta se nepočítají — to nejsou
                 prvky stránky, které by HUD nebo lišta „překrývaly". */
              if (e.closest('.telemetry-hud, .track-progress, #cmplz-cookiebanner-container, .cmplz-cookiebanner, #topbar, header')) return;
              if (!(e.textContent || '').trim() && !['INPUT','SELECT','TEXTAREA','BUTTON'].includes(e.tagName)) return;
              const r = e.getBoundingClientRect();
              if (r.width < 4 || r.height < 4) return;
              if (r.right > o.left && r.left < o.right && r.bottom > o.top && r.top < o.bottom) {
                const k = name + '|' + e.tagName + '.' + (e.className.toString() || '').slice(0, 34) + '|' + (e.closest('.sec')?.id || '-');
                if (!hits.has(k)) hits.set(k, (e.textContent || '').trim().slice(0, 40));
              }
            });
          }
        }
        window.scrollTo(0, 0);
        return [...hits.entries()];
      });
      for (const [k, txt] of r) { const [ov, el, sek] = k.split('|'); nalezy.push({ vw, page: pg.id, ov, el, sek, txt }); }
    } catch (e) { /* preskocit */ }
    await p.close();
  }
}
await b.close();
console.log(JSON.stringify(nalezy, null, 1));
