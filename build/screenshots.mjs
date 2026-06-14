/**
 * Erstellt Demonstrations-Screenshots aller Unterseiten der RSS-Grabber-App.
 *
 * Aufruf (im Playwright-Image, Projekt nach /work gemountet):
 *   node build/screenshots.mjs [version]
 *
 * Ablage: Screenshots/v<version>/NN-seite.png
 * Version: Argument oder aus inc/config.php ($script_version), Fallback "3.0".
 *
 * Benoetigt eine laufende App (BASE_URL, Default http://web) und den
 * Standard-Login admin/admin.
 */
import { chromium } from '@playwright/test';
import { mkdirSync, readFileSync } from 'node:fs';

const BASE = process.env.BASE_URL || 'http://web';

let ver = process.argv[2] || '';
if (!ver) {
    try {
        const cfg = readFileSync('inc/config.php', 'utf8');
        const m = cfg.match(/\$script_version\s*=\s*'([^']+)'/);
        ver = m ? m[1] : '3.0';
    } catch {
        ver = '3.0';
    }
}

const outDir = `Screenshots/v${ver}`;
mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1366, height: 900 },
    deviceScaleFactor: 1,
});
const page = await context.newPage();

async function shot(name) {
    await page.screenshot({ path: `${outDir}/${name}.png`, fullPage: true });
    console.log('  ' + name + '.png');
}

console.log('Screenshots -> ' + outDir);

// 1) Login-Seite (vor der Anmeldung)
await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
await shot('01-login');

// Anmelden (Standardzugang)
await page.fill('input[name="username"]', 'admin');
await page.fill('input[name="password"]', 'admin');
await page.click('input[name="login_btn"]');
await page.waitForLoadState('load');

// Geschuetzte + oeffentliche Seiten
const pages = [
    ['ausgabe.php', '02-ausgabe-beitraege'],
    ['feeds_verwalten.php', '03-feeds-verwalten'],
    ['feed_hinzufuegen.php', '04-feed-hinzufuegen'],
    ['feed_bearbeiten.php?id=1', '05-feed-bearbeiten'],
    ['feeds_synchronisieren.php', '06-feeds-synchronisieren'],
    ['premium-version.php', '07-premium-version'],
    ['install/', '08-installation'],
];

for (const [path, name] of pages) {
    await page.goto(`${BASE}/${path}`, { waitUntil: 'load' });
    await page.waitForTimeout(300);
    await shot(name);
}

await browser.close();
console.log('Fertig: ' + pages.length + ' + 1 Screenshots in ' + outDir);
