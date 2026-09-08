import { chromium } from 'playwright';
const b = await chromium.launch();
const p = await b.newPage();
const nalezy = [];
p.on('request', r => {
  const u = r.url();
  if (/fonts\.(gstatic|googleapis)\.com/.test(u)) {
    nalezy.push({ url: u, typ: r.resourceType(), odkud: r.frame().url(), initiator: (r.headers()['referer'] || '-') });
  }
});
await p.goto('http://gridhotel.local/', { waitUntil: 'networkidle' });
console.log('=== požadavky na Google Fonts ===');
nalezy.forEach(n => console.log('  ' + n.typ + '  ' + n.url.slice(0, 160)));
const v_html = await p.evaluate(() => {
  const out = [];
  document.querySelectorAll('link[href*="fonts.googleapis"], style').forEach(el => {
    if (el.tagName === 'LINK') out.push({ kde: 'link', id: el.id || '(bez id)', href: el.href.slice(0,140) });
    else if (/fonts\.googleapis|@import/.test(el.textContent)) {
      const m = el.textContent.match(/@import[^;]*;|fonts\.googleapis[^"')]*/);
      if (m) out.push({ kde: 'inline <style>', id: el.id || '(bez id)', href: m[0].slice(0,140) });
    }
  });
  return out;
});
console.log('\n=== kde je to v HTML ===');
console.log(v_html.length ? v_html.map(x => '  ' + x.kde + ' id=' + x.id + '  ' + x.href).join('\n') : '  nic v DOM (načteno až JS)');
await b.close();
