import { chromium } from 'playwright';
const b = await chromium.launch(); const p = await b.newPage();
await p.goto('http://gridhotel.local/', { waitUntil:'networkidle' });
const r = await p.evaluate(() => {
  const out = [];
  const mer = (rodina, vaha) => {
    const s = document.createElement('span');
    s.textContent = 'REZERVOVAT POBYT';
    s.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;font-size:40px;letter-spacing:0;font-family:' + rodina + ';font-weight:' + vaha;
    document.body.appendChild(s);
    const w = s.getBoundingClientRect().width;
    s.remove();
    return Math.round(w * 100) / 100;
  };
  for (const rodina of ["'JetBrains Mono'", "'Saira Condensed'", "'Inter'"]) {
    const radek = { rodina: rodina.replace(/'/g,'') };
    for (const v of [300,400,500,600,700,750,780,800]) radek['w' + v] = mer(rodina, v);
    out.push(radek);
  }
  return out;
});
console.log('=== šířka stejného textu v různých vahách (40px, bez prokládání) ===');
console.log('  rodina              300     400     500     600     700     750     780     800');
r.forEach(x => console.log('  ' + x.rodina.padEnd(19) + [300,400,500,600,700,750,780,800].map(v=>String(x['w'+v]).padStart(7)).join(' ')));
await b.close();
