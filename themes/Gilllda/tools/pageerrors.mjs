// usage: node tools/pageerrors.mjs <url> → JS errors with source location
import puppeteer from 'puppeteer-core';
const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
const p = await b.newPage();
if (process.argv[3] === 'mobile') { await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true }); await p.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'); }
p.on('pageerror', e => console.log('PAGEERROR', e.message, '\n', (e.stack || '').split('\n').slice(0, 4).join('\n')));
p.on('console', m => { if (m.type() === 'error') console.log('CONSOLE', m.text(), JSON.stringify(m.location())); });
await p.goto(process.argv[2], { waitUntil: 'networkidle2', timeout: 90000 });
await new Promise(r => setTimeout(r, 2000));
await b.close();
