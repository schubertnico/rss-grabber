import { test, expect, Page, Browser } from '@playwright/test';

/**
 * E2E-Tests gegen die laufende RSS-Grabber-Docker-Instanz.
 *
 * Geprüft wird: Login-Schutz, CSRF, korrekte 200/keine 4xx-5xx, keine
 * PHP-Fehler im HTML, keine JS-Fehler, Navigation, korrekte Umlaut-Darstellung,
 * XSS-Escaping und der CRUD-Fluss.
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

async function login(page: Page) {
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.click('input[name="login_btn"]');
    await page.waitForLoadState('load');
}

test.beforeEach(async ({ page }) => {
    await login(page);
});

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
        ['Logout', 'logout.php'],
    ] as const) {
        await expect(page.locator(`.menue a[href="${href}"]`)).toContainText(text);
    }
});

test('Geschützte Seite ohne Login leitet auf login.php', async ({ browser }: { browser: Browser }) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto('feeds_verwalten.php');
    await expect(page).toHaveURL(/login\.php$/);
    await expect(page.locator('h2')).toContainText('Anmeldung');
    await ctx.close();
});

test('Login mit falschem Passwort schlägt fehl', async ({ browser }: { browser: Browser }) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'falsch');
    await page.click('input[name="login_btn"]');
    await expect(page.locator('body')).toContainText('Benutzername oder Passwort ist falsch');
    await ctx.close();
});

test('Umlaute werden korrekt gespeichert und angezeigt (kein Mojibake)', async ({ page }) => {
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
    expect(body).not.toContain('Ã¶');
    expect(body).not.toContain('Ã¤');
    expect(body).not.toContain('Ã¼');

    await page.getByRole('link', { name: 'Löschen' }).last().click();
    await expect(page.locator('body')).toContainText('wurde gelöscht');
});

test('XSS: Feed-URL mit HTML wird escaped ausgegeben', async ({ page }) => {
    const evil = 'https://xss.example/x"><img src=x onerror="alert(1)">';

    await page.goto('feed_hinzufuegen.php');
    await page.fill('input[name="url"]', 'https://xss.example/');
    await page.fill('input[name="feed_url"]', evil);
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('erfolgreich gespeichert');

    await page.goto('feeds_verwalten.php');
    // Kein per Injection erzeugtes img-Element mit onerror.
    expect(await page.locator('img[onerror]').count()).toBe(0);

    await page.getByRole('link', { name: 'Löschen' }).last().click();
    await expect(page.locator('body')).toContainText('wurde gelöscht');
});

test('CRUD: Feed anlegen, anzeigen und löschen', async ({ page }) => {
    const feedUrl = 'https://e2e-test.example/feed.xml';
    const homepage = 'https://e2e-test.example/';

    await page.goto('feeds_verwalten.php');
    const before = await page.getByRole('link', { name: 'Löschen' }).count();

    await page.goto('feed_hinzufuegen.php');
    await page.fill('input[name="url"]', homepage);
    await page.fill('input[name="feed_url"]', feedUrl);
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('erfolgreich gespeichert');

    await page.goto('feeds_verwalten.php');
    await expect(page.locator(`a[href="${feedUrl}"]`)).toBeVisible();
    const afterAdd = await page.getByRole('link', { name: 'Löschen' }).count();
    expect(afterAdd).toBe(before + 1);

    await page.getByRole('link', { name: 'Löschen' }).last().click();
    await expect(page.locator('body')).toContainText('wurde gelöscht');
    await expect(page.locator(`a[href="${feedUrl}"]`)).toHaveCount(0);
});

test('Feed bearbeiten: Formular lädt und speichert', async ({ page }) => {
    await page.goto('feed_bearbeiten.php?id=1');
    const feedUrl = await page.inputValue('input[name="feed_url"]');
    const homepage = await page.inputValue('input[name="url"]');
    expect(feedUrl.length).toBeGreaterThan(0);
    expect(homepage.length).toBeGreaterThan(0);

    await page.check('input[name="status"][value="1"]');
    await page.click('input[name="senden"]');
    await expect(page.locator('body')).toContainText('geändert');
});

test('Synchronisierung: Klick aktualisiert den Status (Vanilla-JS)', async ({ page }) => {
    const { jsErrors } = watch(page);
    await page.goto('feeds_synchronisieren.php');
    await expect(page.locator('[data-sync-trigger]')).toBeVisible();
    await page.click('[data-sync-trigger]');
    // Das #update-Feld erhält einen Synchronisierungs-Status.
    await expect(page.locator('#update')).toContainText(/Feed|synchronisiert|Fertig|warten/i, { timeout: 15000 });
    expect(jsErrors).toEqual([]);
});

test('CSRF: Sync-Aufruf ohne Token wird abgewiesen', async ({ page }) => {
    // Session ist via beforeEach vorhanden, aber kein CSRF-Token -> 403.
    const res = await page.context().request.post('graber_ajax.php', { form: {} });
    expect(res.status()).toBe(403);
    expect(await res.text()).toContain('Sicherheits-Token');
});

test('Logout beendet die Session', async ({ page }) => {
    await page.goto('feeds_verwalten.php');
    await expect(page.locator('h1')).toContainText('RSS Grabber free v2.0');
    await page.goto('logout.php');
    // Nach Logout führt der Zugriff auf eine geschützte Seite zur Anmeldung.
    await page.goto('feeds_verwalten.php');
    await expect(page).toHaveURL(/login\.php$/);
});
