import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/zazitky/','/sezona/','/o-nas/','/kontakt/','/masarykuv-okruh/','/jak-se-k-nam-dostanete/','/darkove-poukazy/','/kariera/','/prohlaseni-o-pristupnosti/','/en/','/de/','/kategorie-pokoje/standard/'];
const b = await chromium.launch();
for (const s of STRANKY) {
  const p = await b.newPage();
  const hits = [];
  p.on('request', r => { if (/fonts\.(gstatic|googleapis)\.com/.test(r.url())) hits.push(r.url()); });
  try { await p.goto('http://gridhotel.local' + s, { waitUntil: 'networkidle', timeout: 25000 }); } catch(e) {}
  if (hits.length) {
    console.log('  ' + s);
    hits.forEach(h => console.log('      ' + h.slice(0,170)));
    const src = await p.evaluate(() => {
      const o = [];
      document.querySelectorAll('link[href*="googleapis"]').forEach(l => o.push('link#' + (l.id||'?') + ' ' + l.href.slice(0,150)));
      return o;
    });
    src.forEach(x => console.log('      DOM: ' + x));
  }
  await p.close();
}
console.log('hotovo');
await b.close();
