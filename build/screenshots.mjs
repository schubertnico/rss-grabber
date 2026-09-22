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

/** Hoechste Bildhoehe; laengere Seiten werden oben abgeschnitten. */
const MAX_HOEHE = 1400;
/** Rand um den Inhalt herum, damit das Bild nicht an der Kante klebt. */
const RAND = 12;

/**
 * Bildet den Inhaltsbereich ab statt der ganzen Seite. Ohne das steht unter
 * kurzen Seiten bis zur halben Bildhoehe Leerraum, was die Bilder fuer die
 * Anleitung und die Produktseite unbrauchbar macht.
 */
async function shot(name) {
    const ziel = `${outDir}/${name}.png`;
    const box = await page.locator('.layout').first().boundingBox().catch(() => null);

    if (!box) {
        await page.screenshot({ path: ziel, fullPage: true });
        console.log('  ' + name + '.png (ganze Seite)');
        return;
    }

    const breite = await page.evaluate(() => document.documentElement.scrollWidth);
    const x = Math.max(0, box.x - RAND);
    const y = Math.max(0, box.y - RAND);

    await page.screenshot({
        path: ziel,
        fullPage: true,
        clip: {
            x,
            y,
            width: Math.min(box.width + RAND * 2, breite - x),
            height: Math.min(box.height + RAND * 2, MAX_HOEHE),
        },
    });
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

/*
 * Einmal synchronisieren, bevor die Bilder entstehen. Ohne diesen Lauf zeigt
 * die Feed-Uebersicht einen leeren Status und die Beitragsanzeige nichts,
 * was als Demonstration wenig taugt.
 */
await page.goto(`${BASE}/feeds_synchronisieren.php`, { waitUntil: 'load' });
await page.click('[data-sync-trigger]');
await page.waitForFunction(
    () => (document.getElementById('update')?.textContent || '').trim().length > 0,
    null,
    { timeout: 120000 },
).catch(() => console.log('  (Synchronisierung ohne Rueckmeldung, fahre fort)'));
await page.waitForTimeout(1500);

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
