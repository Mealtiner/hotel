import { chromium } from 'playwright';
const b = await chromium.launch(); const p = await b.newPage();
for (const s of ['/o-nas/','/en/about-the-hotel/','/de/ueber-uns/']) {
  try { await p.goto('http://gridhotel.local' + s, { waitUntil:'domcontentloaded', timeout:20000 }); } catch(e){ console.log('  ' + s + '  nedostupné'); continue; }
  const r = await p.evaluate(() => {
    const k = document.querySelector('.okoli-card h3');
    return {
      karet: document.querySelectorAll('.okoli-card').length,
      h4zbylo: document.querySelectorAll('.okoli-card h4').length,
      ukazka: k ? k.textContent.trim().slice(0,24) : '-',
      pismo: k ? getComputedStyle(k).fontFamily.split(',')[0].replace(/"/g,'') : '-'
    };
  });
  console.log('  ' + s.padEnd(26) + 'karet ' + r.karet + ' | h4 zbylo ' + r.h4zbylo + ' | ' + r.pismo + ' | „' + r.ukazka + '"');
}
await b.close();
