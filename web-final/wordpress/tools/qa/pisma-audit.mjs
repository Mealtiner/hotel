import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/zazitky/','/sezona/','/o-nas/','/kontakt/','/masarykuv-okruh/','/jak-se-k-nam-dostanete/','/darkove-poukazy/','/kariera/','/prohlaseni-o-pristupnosti/','/en/','/de/','/kategorie-pokoje/standard/'];
const b = await chromium.launch();
const p = await b.newPage();
const souhrn = {}, kdePodle = {};
const externi = new Set();
p.on('request', r => { const u = r.url(); if (/fonts\.(gstatic|googleapis)\.com|use\.typekit|fonts\.bunny/.test(u)) externi.add(u.split('?')[0]); });
for (const s of STRANKY) {
  try { await p.goto('http://gridhotel.local' + s, { waitUntil: 'networkidle', timeout: 25000 }); } catch (e) { console.log('  přeskočeno ' + s); continue; }
  const r = await p.evaluate(() => {
    const out = {};
    document.querySelectorAll('body *').forEach(el => {
      if (!el.offsetParent && el.tagName !== 'BODY') return;
      if (![...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim())) return;
      const f = getComputedStyle(el).fontFamily.split(',')[0].replace(/["']/g,'').trim();
      (out[f] ||= { n: 0, ukazka: [] });
      out[f].n++;
      if (out[f].ukazka.length < 3) out[f].ukazka.push(el.tagName.toLowerCase() + (el.className ? '.' + String(el.className).split(' ')[0] : ''));
    });
    return out;
  });
  for (const [f, v] of Object.entries(r)) {
    souhrn[f] = (souhrn[f] || 0) + v.n;
    (kdePodle[f] ||= new Set());
    v.ukazka.forEach(u => kdePodle[f].add(u));
    kdePodle[f].add('str:' + s);
  }
}
console.log('=== písma na viditelném textu (' + STRANKY.length + ' stránek) ===');
for (const [f, n] of Object.entries(souhrn).sort((a,b)=>b[1]-a[1])) {
  const u = [...kdePodle[f]].filter(x=>!x.startsWith('str:')).slice(0,4).join(', ');
  console.log('  ' + f.padEnd(24) + String(n).padStart(5) + ' prvků   ' + u);
}
console.log('\n=== externí požadavky na písma ===');
console.log(externi.size ? [...externi].map(u=>'  ' + u).join('\n') : '  žádné');
await b.close();
