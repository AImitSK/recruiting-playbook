import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E Test Configuration für Recruiting Playbook
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
    // Test-Verzeichnis
    testDir: './tests/e2e',

    // Maximale Zeit für jeden Test
    timeout: 30 * 1000,

    // Assertion Timeout
    expect: {
        timeout: 5000
    },

    // Parallele Tests
    fullyParallel: true,

    // Fehler bei console.error im Browser
    forbidOnly: !!process.env.CI,

    // Wiederholungen bei Fehlern
    retries: process.env.CI ? 2 : 0,

    // Parallele Worker
    workers: process.env.CI ? 1 : undefined,

    // Reporter
    reporter: [
        ['html', { outputFolder: 'tests/e2e/report' }],
        ['list']
    ],

    // Globale Einstellungen
    use: {
        // Base URL für WordPress Dev Container
        baseURL: 'http://localhost:8080',

        // Screenshots bei Fehlern
        screenshot: 'only-on-failure',

        // Video bei Fehlern
        video: 'retain-on-failure',

        // Traces für Debugging
        trace: 'on-first-retry',

        // Viewport
        viewport: { width: 1280, height: 720 },
    },

    // Browser-Konfiguration
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
        // Mobile Tests
        {
            name: 'Mobile Chrome',
            use: { ...devices['Pixel 5'] },
        },
        {
            name: 'Mobile Safari',
            use: { ...devices['iPhone 12'] },
        },
    ],

    // Ausgabe-Verzeichnis für Artefakte
    outputDir: 'tests/e2e/results',
});
