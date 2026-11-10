// Real-viewport screenshots of the live site with the installed Chrome (no Chromium download).
// one-time:  cd tools && npm i --no-save puppeteer-core@23.11.1
// usage:     node tools/shot.mjs <url> <out-prefix> [mobile|desktop] [sliceHeight]
// prints JSON {loadMs, h, overflowX, errors[]} and writes <out-prefix>-N.png slices.
// (Headless Chrome windows are >=500px wide, so --window-size=390 screenshots lie — use this.)
import puppeteer from 'puppeteer-core';
const [url, out, mode = 'desktop', slice = '1400'] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--hide-scrollbars'] });
const page = await browser.newPage();
const mobile = mode === 'mobile';
await page.setViewport(mobile ? { width: 390, height: 844, deviceScaleFactor: 1, isMobile: true, hasTouch: true } : { width: 1440, height: 900, deviceScaleFactor: 1 });
if (mobile) await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
const errors = [];
page.on('pageerror', e => errors.push('pageerror: ' + e.message));
page.on('console', m => { if (m.type() === 'error') errors.push('console: ' + m.text()); });
const t0 = Date.now();
await page.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
const loadMs = Date.now() - t0;
// scroll through to trigger lazy/reveal
const H = await page.evaluate(() => document.documentElement.scrollHeight);
await page.evaluate(() => { document.documentElement.style.scrollBehavior = 'auto'; });
for (let y = 0; y < H; y += 500) { await page.evaluate(v => window.scrollTo(0, v), y); await new Promise(r => setTimeout(r, 120)); }
await page.addStyleTag({ content: '*{content-visibility:visible!important}' });
await page.evaluate(() => { document.documentElement.style.scrollBehavior = 'auto'; window.scrollTo({ top: 0, behavior: 'instant' }); document.querySelectorAll('[data-reveal]').forEach(e => e.classList.add('is-in')); });
await new Promise(r => setTimeout(r, 1500));
await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
const info = await page.evaluate(() => ({ w: document.documentElement.scrollWidth, h: document.documentElement.scrollHeight, vw: innerWidth }));
const sh = +slice; let i = 0;
for (let y = 0; y < info.h && i < 12; y += sh, i++) {
  await page.screenshot({ path: `${out}-${i}.png`, clip: { x: 0, y, width: info.vw, height: Math.min(sh, info.h - y) }, captureBeyondViewport: true });
}
console.log(JSON.stringify({ loadMs, ...info, slices: i, overflowX: info.w > info.vw, errors: errors.slice(0, 8) }));
await browser.close();
