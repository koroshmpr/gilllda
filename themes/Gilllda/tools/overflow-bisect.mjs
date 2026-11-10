// usage: node tools/overflow-bisect.mjs <url> → finds the element that makes the page wider than 390px
import puppeteer from 'puppeteer-core';
const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
const p = await b.newPage();
await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
await p.goto(process.argv[2], { waitUntil: 'networkidle2', timeout: 90000 });
await new Promise(r => setTimeout(r, 1200));
const out = await p.evaluate(() => {
  const sw = () => document.documentElement.scrollWidth;
  const base = sw(), res = [];
  const walk = (el, depth) => {
    for (const c of [...el.children]) {
      const prev = c.style.display; c.style.display = 'none';
      const w = sw(); c.style.display = prev;
      if (w < base) {
        res.push({ depth, tag: c.tagName, id: c.id, cls: (c.className.baseVal ?? c.className).toString().slice(0, 160), sw_without: w });
        if (depth < 9) walk(c, depth + 1);
        return;
      }
    }
  };
  walk(document.body, 0);
  return { base, res };
});
console.log(JSON.stringify(out, null, 1));
await b.close();
