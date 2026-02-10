import { test, expect } from '@playwright/test';

/**
 * E2E Tests für Stellenanzeigen
 */
test.describe('Stellenanzeigen', () => {

    test.beforeEach(async ({ page }) => {
        // Warte auf WordPress
        await page.goto('/');
    });

    test('Archiv-Seite zeigt Stellenliste', async ({ page }) => {
        await page.goto('/jobs/');

        // Prüfe ob die Seite lädt
        await expect(page).toHaveTitle(/Jobs|Stellen/i);

        // Prüfe ob mindestens eine Stelle angezeigt wird
        const jobCards = page.locator('.rp-job-card, article.job_listing, .type-job_listing');

        // Falls Stellen existieren, sollten sie sichtbar sein
        const count = await jobCards.count();
        if (count > 0) {
            await expect(jobCards.first()).toBeVisible();
        }
    });

    test('Einzelne Stelle zeigt Details', async ({ page }) => {
        // Gehe zur Archiv-Seite
        await page.goto('/jobs/');

        // Finde einen Link zu einer Stelle
        const jobLink = page.locator('a[href*="/job/"], a[href*="/jobs/"], .rp-job-card a').first();

        if (await jobLink.isVisible()) {
            await jobLink.click();

            // Prüfe Stellenseite (h1 mit Job-Titel)
            const jobTitle = page.locator('h1').first();
            await expect(jobTitle).toBeVisible();

            // Prüfe "Jetzt bewerben" Button
            const applyButton = page.getByText('Jetzt bewerben').first();
            await expect(applyButton).toBeVisible();
        }
    });

    test('Schema-Markup ist vorhanden', async ({ page }) => {
        await page.goto('/jobs/');

        // Finde eine Stelle und öffne sie
        const jobLink = page.locator('a[href*="/job/"], .rp-job-card a').first();

        if (await jobLink.isVisible()) {
            await jobLink.click();

            // Prüfe JSON-LD Schema
            const schema = page.locator('script[type="application/ld+json"]');
            await expect(schema).toBeAttached();

            // Prüfe Schema-Inhalt
            const schemaContent = await schema.textContent();
            if (schemaContent) {
                const parsed = JSON.parse(schemaContent);
                expect(parsed['@type']).toBe('JobPosting');
            }
        }
    });
});
