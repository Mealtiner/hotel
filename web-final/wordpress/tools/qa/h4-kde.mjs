import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/zazitky/','/sezona/','/o-nas/','/kontakt/','/masarykuv-okruh/','/jak-se-k-nam-dostanete/','/darkove-poukazy/','/kariera/'];
const b = await chromium.launch();
const p = await b.newPage();
for (const s of STRANKY) {
  try { await p.goto('http://gridhotel.local' + s, { waitUntil: 'domcontentloaded', timeout: 20000 }); } catch(e){ continue; }
  const r = await p.evaluate(() => {
    const o = [];
    document.querySelectorAll('h4,h5,h6').forEach(el => {
      if (!el.offsetParent) return;
      o.push(el.tagName + ' „' + el.textContent.trim().slice(0,24) + '" → ' + getComputedStyle(el).fontFamily.split(',')[0].replace(/"/g,'') + '  [' + (el.parentElement.className.split(' ')[0]||'-') + ']');
    });
    return o;
  });
  if (r.length) { console.log('  ' + s); r.slice(0,6).forEach(x => console.log('      ' + x)); }
}
await b.close();
