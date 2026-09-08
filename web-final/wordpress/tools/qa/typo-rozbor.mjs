import { chromium } from 'playwright';
const STRANKY = ['/','/ubytovani/','/gastronomie/','/zazitky/','/sezona/','/o-nas/','/kontakt/','/masarykuv-okruh/','/jak-se-k-nam-dostanete/','/darkove-poukazy/','/kariera/','/kategorie-pokoje/standard/'];
const b = await chromium.launch();
const p = await b.newPage();
const znaky = {}, rezy = {}, velikosti = {}, skala = new Set(), rozpal = {};
for (const s of STRANKY) {
  try { await p.goto('http://gridhotel.local' + s, { waitUntil: 'networkidle', timeout: 25000 }); } catch(e){ continue; }
  const r = await p.evaluate(() => {
    const o = { znaky:{}, rezy:{}, vel:{}, skala:[], rozpal:{} };
    document.querySelectorAll('body *').forEach(el => {
      if (!el.offsetParent) return;
      const txt = [...el.childNodes].filter(n=>n.nodeType===3).map(n=>n.textContent.trim()).join('');
      if (!txt) return;
      const cs = getComputedStyle(el);
      const f = cs.fontFamily.split(',')[0].replace(/["']/g,'').trim();
      o.znaky[f] = (o.znaky[f]||0) + txt.length;
      const k = f + ' ' + cs.fontWeight;
      o.rezy[k] = (o.rezy[k]||0) + 1;
      const px = Math.round(parseFloat(cs.fontSize));
      (o.vel[f] ||= []).push(px);
      o.skala.push(px);
      const ls = cs.letterSpacing;
      if (ls !== 'normal') { const kk = f + ' ' + ls; o.rozpal[kk] = (o.rozpal[kk]||0)+1; }
    });
    return o;
  });
  for (const [k,v] of Object.entries(r.znaky)) znaky[k]=(znaky[k]||0)+v;
  for (const [k,v] of Object.entries(r.rezy)) rezy[k]=(rezy[k]||0)+v;
  for (const [k,v] of Object.entries(r.vel)) (velikosti[k] ||= []).push(...v);
  r.skala.forEach(x=>skala.add(x));
  for (const [k,v] of Object.entries(r.rozpal)) rozpal[k]=(rozpal[k]||0)+v;
}
const celkem = Object.values(znaky).reduce((a,c)=>a+c,0);
console.log('=== objem skutečného textu (znaky) ===');
Object.entries(znaky).sort((a,b)=>b[1]-a[1]).forEach(([f,n]) =>
  console.log('  ' + f.padEnd(20) + String(n).padStart(7) + '  ' + (100*n/celkem).toFixed(1) + ' %'));
console.log('\n=== použité řezy (počet prvků) ===');
Object.entries(rezy).sort().forEach(([k,v]) => console.log('  ' + k.padEnd(28) + v));
console.log('\n=== rozsah velikostí ===');
Object.entries(velikosti).forEach(([f,a]) => {
  const s = [...new Set(a)].sort((x,y)=>x-y);
  console.log('  ' + f.padEnd(20) + 'min ' + s[0] + 'px  max ' + s[s.length-1] + 'px  různých velikostí: ' + s.length);
});
console.log('\n=== typografická škála (všechny velikosti px) ===');
console.log('  ' + [...skala].sort((a,b)=>a-b).join(', '));
console.log('  celkem různých stupňů: ' + skala.size);
console.log('\n=== prokládání ===');
Object.entries(rozpal).sort((a,b)=>b[1]-a[1]).slice(0,10).forEach(([k,v]) => console.log('  ' + k.padEnd(30) + v));
await b.close();
