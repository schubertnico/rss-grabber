import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright-Konfiguration für die RSS-Grabber-E2E-Tests.
 *
 * BASE_URL wird beim Lauf im Playwright-Container auf http://rss-grabber_web
 * gesetzt; lokal vom Host greift der Default auf den gemappten Port 8340.
 */
export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    fullyParallel: false,
    forbidOnly: false,
    retries: 0,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.BASE_URL || 'http://localhost:8340',
        trace: 'off',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});
