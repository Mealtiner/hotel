// Responzivní audit gridhotel.local — screenshoty + detekce overflow
const { chromium } = require('playwright');
const fs = require('fs');

const VIEWPORTS = [
  { name: 'wide-1920', width: 1920, height: 1080 },
  { name: 'mac-1280', width: 1280, height: 800 },
  { name: 'tablet-land-1024', width: 1024, height: 768 },
  { name: 'tablet-port-768', width: 768, height: 1024 },
  { name: 'mobil-390', width: 390, height: 844 },
];
const PAGES = [
  { name: 'domov', url: 'https://gridhotel.local/' },
  { name: 'ubytovani', url: 'https://gridhotel.local/ubytovani/' },
  { name: 'zazitky', url: 'https://gridhotel.local/zazitky/' },
  { name: 'gastronomie', url: 'https://gridhotel.local/gastronomie/' },
  { name: 'sezona', url: 'https://gridhotel.local/sezona-2026/' },
  { name: 'kontakt', url: 'https://gridhotel.local/kontakt/' },
  { name: 'rezervace', url: 'https://gridhotel.local/rezervace/' },
  { name: 'doprava', url: 'https://gridhotel.local/jak-se-k-nam-dostanete/' },
];

const FORCE_CSS = `
  *,*::before,*::after{animation:none !important;transition:none !important}
  .reveal{opacity:1 !important;transform:none !important}
  html{scroll-behavior:auto !important}
`;

(async () => {
  const browser = await chromium.launch();
  const report = {};
  for (const vp of VIEWPORTS) {
    const ctx = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      ignoreHTTPSErrors: true,
      deviceScaleFactor: 1,
    });
    for (const pg of PAGES) {
      const page = await ctx.newPage();
      try {
        await page.goto(pg.url, { waitUntil: 'networkidle', timeout: 30000 });
      } catch (e) { /* networkidle timeout nevadí */ }
      await page.addStyleTag({ content: FORCE_CSS });
      // projet stránku kvůli lazy prvkům
      await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 800) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 30)); }
        window.scrollTo(0, 0);
      });
      await page.waitForTimeout(300);
      // detekce horizontálního přetečení
      const overflow = await page.evaluate(() => {
        const vw = document.documentElement.clientWidth;
        const bad = [];
        const seen = new Set();
        for (const el of document.querySelectorAll('body *')) {
          const r = el.getBoundingClientRect();
          if (r.width === 0 || r.height === 0) continue;
          const cs = getComputedStyle(el);
          if (cs.visibility === 'hidden' || cs.display === 'none') continue;
          if (cs.position === 'fixed') continue; // HUD/track widgety řeší JS zvlášť
          if (r.right > vw + 1 || r.left < -1) {
            // přeskoč potomky už nahlášeného rodiče
            let p = el.parentElement, skip = false;
            while (p) { if (seen.has(p)) { skip = true; break; } p = p.parentElement; }
            if (skip) continue;
            seen.add(el);
            const id = el.id ? '#' + el.id : '';
            const cls = el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '';
            bad.push(`${el.tagName.toLowerCase()}${id}${cls} [${Math.round(r.left)}..${Math.round(r.right)}] šířka:${Math.round(r.width)}`);
            if (bad.length >= 15) break;
          }
        }
        return { vw, scrollW: document.documentElement.scrollWidth, bodyScrollW: document.body.scrollWidth, bad };
      });
      report[`${pg.name} @ ${vp.name}`] = overflow;
      await page.screenshot({ path: `resp/shots/${pg.name}--${vp.name}.png`, fullPage: true });
      await page.close();
    }
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync('resp/overflow-report.json', JSON.stringify(report, null, 1));
  // souhrn do konzole
  for (const [k, v] of Object.entries(report)) {
    const over = v.scrollW > v.vw + 1;
    if (over || v.bad.length) console.log(`${over ? '⚠️ ' : '   '}${k}: scrollW=${v.scrollW}/${v.vw}${v.bad.length ? '\n     ' + v.bad.join('\n     ') : ''}`);
  }
  console.log('HOTOVO');
})();
