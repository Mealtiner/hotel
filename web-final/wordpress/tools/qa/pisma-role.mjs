import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/zazitky/','/sezona/','/o-nas/','/kontakt/','/masarykuv-okruh/','/jak-se-k-nam-dostanete/','/kariera/','/en/','/de/','/kategorie-pokoje/standard/'];
const b = await chromium.launch();
const p = await b.newPage();
const role = {};
const cizi = {};
for (const s of STRANKY) {
  try { await p.goto('http://gridhotel.local' + s, { waitUntil: 'networkidle', timeout: 25000 }); } catch(e) { continue; }
  const r = await p.evaluate(() => {
    const out = { role: {}, cizi: {} };
    const zname = ['Saira Condensed','JetBrains Mono','Inter'];
    document.querySelectorAll('body *').forEach(el => {
      if (!el.offsetParent) return;
      if (![...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim())) return;
      const f = getComputedStyle(el).fontFamily.split(',')[0].replace(/["']/g,'').trim();
      const t = el.tagName.toLowerCase();
      const skupina = /^h[1-6]$/.test(t) ? 'nadpisy ' + t
        : t === 'p' ? 'odstavce'
        : ['button','a'].includes(t) ? 'tlačítka a odkazy'
        : ['span','small','em','strong','li','td','th','label','div','dt','dd'].includes(t) ? 'ostatní text'
        : 'jiné (' + t + ')';
      const k = skupina + ' → ' + f;
      out.role[k] = (out.role[k] || 0) + 1;
      if (!zname.includes(f)) { out.cizi[f + ' @ ' + t + (el.className ? '.' + String(el.className).split(' ')[0] : '')] = 1; }
    });
    return out;
  });
  for (const [k,v] of Object.entries(r.role)) role[k] = (role[k]||0)+v;
  for (const k of Object.keys(r.cizi)) cizi[k] = (cizi[k]||0)+1;
}
console.log('=== role písem ===');
Object.entries(role).sort().forEach(([k,v]) => console.log('  ' + k.padEnd(46) + v));
console.log('\n=== cizí písma (mimo trojici) ===');
console.log(Object.keys(cizi).length ? Object.keys(cizi).map(x=>'  '+x).join('\n') : '  žádná');
await b.close();
