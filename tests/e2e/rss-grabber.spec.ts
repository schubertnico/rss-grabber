import { test, expect, Page } from '@playwright/test';

/**
 * E2E-Tests gegen die laufende RSS-Grabber-Docker-Instanz.
 *
 * Geprüft wird: keine 4xx/5xx (auch für Assets), keine PHP-Fehlerausgabe im
 * HTML, keine ungefangenen JS-Fehler, funktionierende Navigation, korrekte
 * Umlaut-Darstellung und der CRUD-Fluss (anlegen → anzeigen → löschen).
 */

const PHP_ERROR = /(Fatal error|Parse error|Deprecated:|Warning:|Notice:|Uncaught)/;

const PAGES = [
    'ausgabe.php',
    'feeds_verwalten.php',
    'feed_hinzufuegen.php',
    'feed_bearbeiten.php?id=1',
    'feeds_synchronisieren.php',
    'premium-version.php',
];

/** Hängt Listener an, die fehlerhafte Responses und JS-Fehler sammeln. */
function watch(page: Page) {
    const badResponses: string[] = [];
    const jsErrors: string[] = [];
    page.on('response', (res) => {
        const url = res.url();
        if (res.status() >= 400 && !url.endsWith('favicon.ico')) {
            badResponses.push(`${res.status()} ${url}`);
        }
    });
    page.on('pageerror', (err) => jsErrors.push(err.message));
    return { badResponses, jsErrors };
}

for (const path of PAGES) {
    test(`Seite ${path} lädt fehlerfrei`, async ({ page }) => {
        const { badResponses, jsErrors } = watch(page);
        const response = await page.goto(path, { waitUntil: 'load' });

        expect(response, `keine Response für ${path}`).not.toBeNull();
        expect(response!.status(), `HTTP-Status für ${path}`).toBeLessThan(400);

        const body = await page.content();
        expect(body, `PHP-Fehler in ${path}`).not.toMatch(PHP_ERROR);
        await expect(page.locator('h1')).toContainText('RSS Grabber free v2.0');

        expect(badResponses, `fehlerhafte Responses auf ${path}`).toEqual([]);
        expect(jsErrors, `JS-Fehler auf ${path}`).toEqual([]);
    });
}

test('Navigation verlinkt alle Bereiche', async ({ page }) => {
    await page.goto('ausgabe.php');
    for (const [text, href] of [
        ['Alle Feed anzeigen', 'ausgabe.php'],
        ['Neuen Feed eintragen', 'feed_hinzufuegen.php'],
        ['Feed verwalten', 'feeds_verwalten.php'],
        ['Feeds synchronisieren', 'feeds_synchronisieren.php'],
        ['Premium-Version', 'premium-version.php'],
    ] as const) {
        await expect(page.locator(`.menue a[href="${href}"]`)).toContainText(text);
    }
});

test('Umlaute werden korrekt gespeichert und angezeigt (kein Mojibake)', async ({ page }) => {
    // Vollständig über den App-Pfad: Feed mit Umlauten anlegen, in der
    // Verwaltung anzeigen, Mojibake ausschließen, wieder löschen.
    const umlautHomepage = 'https://exämple.tld/grüße-äöüß';
    const feedUrl = 'https://e2e-umlaut.example/feed.xml';

    await page.goto('feed_hinzufuegen.php');
    await page.fill('input[name="url"]', umlautHomepage);
    await page.fill('input[name="feed_url"]', feedUrl);
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('erfolgreich gespeichert');

    await page.goto('feeds_verwalten.php');
    await expect(page.getByText(umlautHomepage, { exact: false })).toBeVisible();
    const body = await page.content();
    expect(body).not.toContain('Ã¶'); // klassisches Doppel-Encoding-Muster
    expect(body).not.toContain('Ã¤');
    expect(body).not.toContain('Ã¼');

    // Aufräumen (neuer Feed hat die höchste id -> letzter Löschen-Link).
    await page.getByRole('link', { name: 'Löschen' }).last().click();
    await expect(page.locator('body')).toContainText('wurde gelöscht');
});

test('CRUD: Feed anlegen, anzeigen und löschen', async ({ page }) => {
    const { jsErrors } = watch(page);
    const feedUrl = 'https://e2e-test.example/feed.xml';
    const homepage = 'https://e2e-test.example/';

    // Vorher: Anzahl Löschen-Links merken.
    await page.goto('feeds_verwalten.php');
    const before = await page.getByRole('link', { name: 'Löschen' }).count();

    // Anlegen.
    await page.goto('feed_hinzufuegen.php');
    await page.fill('input[name="url"]', homepage);
    await page.fill('input[name="feed_url"]', feedUrl);
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('erfolgreich gespeichert');

    // Anzeigen in der Verwaltung.
    await page.goto('feeds_verwalten.php');
    await expect(page.locator(`a[href="${feedUrl}"]`)).toBeVisible();
    const afterAdd = await page.getByRole('link', { name: 'Löschen' }).count();
    expect(afterAdd).toBe(before + 1);

    // Löschen (neuer Feed hat die höchste id -> letzter Löschen-Link).
    await page.getByRole('link', { name: 'Löschen' }).last().click();
    await expect(page.locator('body')).toContainText('wurde gelöscht');
    await expect(page.locator(`a[href="${feedUrl}"]`)).toHaveCount(0);

    expect(jsErrors).toEqual([]);
});

test('Feed bearbeiten: Formular lädt und speichert', async ({ page }) => {
    await page.goto('feed_bearbeiten.php?id=1');
    const feedUrl = await page.inputValue('input[name="feed_url"]');
    const homepage = await page.inputValue('input[name="url"]');
    expect(feedUrl.length).toBeGreaterThan(0);
    expect(homepage.length).toBeGreaterThan(0);

    // Mit unveränderten Werten speichern (testet den UPDATE-Pfad ohne Datenänderung).
    await page.check('input[name="status"][value="1"]');
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('geändert');
});
