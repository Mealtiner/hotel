import { chromium } from 'playwright';
const b = await chromium.launch(); const p = await b.newPage();
await p.goto('http://gridhotel.local/', { waitUntil:'networkidle' });
const r = await p.evaluate(async () => {
  const rodiny = ["'JetBrains Mono'", "'Saira Condensed'", "'Inter'"];
  const vahy = [300,400,500,600,700,750,780,800];
  // nejdrive vynutit nacteni vsech kombinaci
  for (const f of rodiny) for (const v of vahy) { try { await document.fonts.load(v + ' 40px ' + f); } catch(e){} }
  await document.fonts.ready;
  const out = [];
  for (const f of rodiny) {
    const radek = { rodina: f.replace(/'/g,''), dostupne: [] };
    for (const v of vahy) {
      const ok = document.fonts.check(v + ' 40px ' + f);
      radek.dostupne.push(ok ? v : null);
    }
    // ktere @font-face jsou skutecne nactene
    radek.nactene = [...document.fonts].filter(ff => ff.family === f.replace(/'/g,'')).map(ff => ff.weight + '/' + ff.status).join(' ');
    out.push(radek);
  }
  return out;
});
console.log('=== které váhy prohlížeč potvrdí jako dostupné ===');
r.forEach(x => {
  console.log('  ' + x.rodina);
  console.log('      dostupné: ' + x.dostupne.filter(Boolean).join(', '));
  console.log('      @font-face: ' + (x.nactene || '(žádné registrované)'));
});
await b.close();
