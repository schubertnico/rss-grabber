/**
 * Erzeugt aus einer Markdown-Datei ein gestyltes PDF.
 *
 * Aufruf (im Playwright-Image, Projekt nach /work gemountet):
 *   node build/md-to-pdf.mjs INSTALLATION.md Installationsanleitung_3.0.pdf "RSS Grabber free v3.0"
 *
 * Nutzt `marked` (Markdown -> HTML) und das in der Playwright-Umgebung
 * vorhandene Chromium (HTML -> PDF).
 */
import { readFileSync } from 'node:fs';
import { marked } from 'marked';
import { chromium } from '@playwright/test';

const [, , inFile = 'INSTALLATION.md', outFile = 'Installationsanleitung_3.0.pdf', title = 'RSS Grabber'] = process.argv;

const bodyHtml = marked.parse(readFileSync(inFile, 'utf8'));

const html = `<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>${title}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1a202c;
         line-height: 1.55; margin: 0; padding: 0 8px; font-size: 11pt; }
  h1 { color: #2b6cb0; border-bottom: 3px solid #2b6cb0; padding-bottom: 6px; font-size: 22pt; }
  h2 { margin-top: 1.5em; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; font-size: 15pt; }
  h3 { font-size: 12.5pt; }
  table { border-collapse: collapse; width: 100%; margin: 12px 0; }
  th, td { border: 1px solid #cbd5e0; padding: 5px 9px; text-align: left; font-size: 10pt; vertical-align: top; }
  th { background: #edf2f7; }
  code { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 9.5pt; }
  pre { background: #1a202c; color: #e2e8f0; padding: 12px; border-radius: 6px; overflow-x: auto; }
  pre code { background: none; color: inherit; padding: 0; }
  blockquote { border-left: 4px solid #f6ad55; background: #fffaf0; margin: 12px 0; padding: 6px 14px; }
  a { color: #2b6cb0; }
  h2, h3 { page-break-after: avoid; }
  table, pre, blockquote { page-break-inside: avoid; }
</style>
</head>
<body>${bodyHtml}</body>
</html>`;

const browser = await chromium.launch();
const page = await browser.newPage();
await page.setContent(html, { waitUntil: 'load' });
await page.pdf({
    path: outFile,
    format: 'A4',
    printBackground: true,
    margin: { top: '18mm', bottom: '18mm', left: '16mm', right: '16mm' },
    displayHeaderFooter: true,
    headerTemplate: '<span></span>',
    footerTemplate: '<div style="width:100%; font-size:8pt; color:#718096; text-align:center;">'
        + 'RSS Grabber free v3.0 – Installationsanleitung &nbsp;·&nbsp; Seite '
        + '<span class="pageNumber"></span> / <span class="totalPages"></span></div>',
});
await browser.close();
console.log('PDF erzeugt: ' + outFile);
