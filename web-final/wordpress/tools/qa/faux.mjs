import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/sezona/','/o-nas/','/kontakt/'];
const NACTENO = { 'Inter':[300,400,500,600,700], 'JetBrains Mono':[400,500,700], 'Saira Condensed':[500,600,700,800] };
const b = await chromium.launch(); const p = await b.newPage();
const chybi = {};
for (const s of STRANKY) {
  try { await p.goto('http://gridhotel.local'+s, {waitUntil:'domcontentloaded', timeout:20000}); } catch(e){ continue; }
  const r = await p.evaluate((NACTENO) => {
    const o = {};
    document.querySelectorAll('body *').forEach(el => {
      if (!el.offsetParent) return;
      if (![...el.childNodes].some(n=>n.nodeType===3 && n.textContent.trim())) return;
      const cs = getComputedStyle(el);
      const f = cs.fontFamily.split(',')[0].replace(/["']/g,'').trim();
      const w = parseInt(cs.fontWeight,10);
      if (!NACTENO[f]) return;
      if (NACTENO[f].includes(w)) return;
      const k = f + ' ' + w;
      (o[k] ||= { n:0, kde:[] });
      o[k].n++;
      if (o[k].kde.length < 3) o[k].kde.push(el.tagName.toLowerCase() + (el.className ? '.'+String(el.className).split(' ')[0] : '') + ' „' + el.textContent.trim().slice(0,18) + '"');
    });
    return o;
  }, NACTENO);
  for (const [k,v] of Object.entries(r)) {
    (chybi[k] ||= { n:0, kde:new Set() });
    chybi[k].n += v.n;
    v.kde.forEach(x => chybi[k].kde.add(x));
  }
}
console.log('=== řezy použité, ale NENAČTENÉ (prohlížeč je dopočítává) ===');
Object.entries(chybi).sort((a,b)=>b[1].n-a[1].n).forEach(([k,v]) =>
  console.log('  ' + k.padEnd(24) + String(v.n).padStart(4) + ' prvků   ' + [...v.kde].slice(0,2).join(' | ')));
if (!Object.keys(chybi).length) console.log('  žádné');
await b.close();
