import { test, expect } from '@playwright/test';

/**
 * Quick Test für lokale Entwicklung
 */
test.describe('Quick Test - Jobs Page', () => {

    test('Jobs Archive lädt korrekt', async ({ page }) => {
        // Verwende externe URL statt localhost im Container
        await page.goto('http://host.docker.internal:8080/jobs/');

        // Screenshot machen
        await page.screenshot({ path: 'tests/e2e/results/jobs-archive.png', fullPage: true });

        // Prüfe Seitentitel
        await expect(page).toHaveTitle(/Recruiting Playbook Dev/i);

        // Prüfe ob Job angezeigt wird
        await expect(page.getByText('Senior PHP Developer')).toBeVisible();

        // Prüfe ob Karriere-Header existiert
        await expect(page.getByText(/Karriere bei/i)).toBeVisible();
    });

    test('Einzelne Job-Seite lädt korrekt', async ({ page }) => {
        await page.goto('http://host.docker.internal:8080/jobs/senior-php-developer/');

        // Screenshot machen
        await page.screenshot({ path: 'tests/e2e/results/job-single.png', fullPage: true });

        // Prüfe Job-Titel
        await expect(page.locator('h1')).toContainText('Senior PHP Developer');

        // Prüfe "Jetzt bewerben" Button
        const applyButton = page.locator('text=Jetzt bewerben').first();
        await expect(applyButton).toBeVisible();

        console.log('✓ Job-Seite funktioniert korrekt');
    });
});
