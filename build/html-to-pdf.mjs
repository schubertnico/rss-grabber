/**
 * Rendert eine vollständige HTML-Datei zu einem PDF (A4) mit dem in der
 * Playwright-Umgebung vorhandenen Chromium.
 *
 * Aufruf (im Playwright-Image, Projekt nach /work gemountet):
 *   node build/html-to-pdf.mjs build/installation-anleitung.html Installationsanleitung_3.0.pdf
 */
import { readFileSync } from 'node:fs';
import { chromium } from '@playwright/test';

const [, , inFile, outFile = 'out.pdf'] = process.argv;
if (!inFile) {
    console.error('Aufruf: node build/html-to-pdf.mjs <input.html> <output.pdf>');
    process.exit(1);
}

const html = readFileSync(inFile, 'utf8');

const browser = await chromium.launch();
const page = await browser.newPage();
await page.setContent(html, { waitUntil: 'load' });
await page.pdf({
    path: outFile,
    format: 'A4',
    printBackground: true,
    margin: { top: '16mm', bottom: '16mm', left: '18mm', right: '18mm' },
});
await browser.close();
console.log('PDF erzeugt: ' + outFile);
