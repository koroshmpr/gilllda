// usage: node tools/overflow.mjs <url>  → lists elements wider than the 390px mobile viewport
import puppeteer from 'puppeteer-core';
const url = process.argv[2];
const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
const p = await b.newPage();
await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
await p.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
await p.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
await new Promise(r => setTimeout(r, 1500));
const res = await p.evaluate(() => {
  const vw = document.documentElement.clientWidth, out = [];
  for (const el of document.querySelectorAll('body *')) {
    const r = el.getBoundingClientRect();
    if (r.width === 0) continue;
    if (r.right > vw + 1 || r.left < -1) {
      const cs = getComputedStyle(el);
      if (cs.position === 'fixed' && (cs.visibility === 'hidden' || cs.opacity === '0')) continue;
      let anc = el.parentElement, clipped = false;
      while (anc && anc !== document.body) { const o = getComputedStyle(anc).overflowX; if (o === 'hidden' || o === 'clip' || o === 'auto' || o === 'scroll') { clipped = true; break; } anc = anc.parentElement; }
      if (!clipped) out.push({ tag: el.tagName, cls: (el.className.baseVal ?? el.className).toString().slice(0, 140), left: Math.round(r.left), right: Math.round(r.right), w: Math.round(r.width) });
    }
  }
  return { vw, sw: document.documentElement.scrollWidth, items: out.slice(0, 15) };
});
console.log(JSON.stringify(res, null, 1));
await b.close();
