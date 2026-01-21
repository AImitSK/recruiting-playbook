import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests für das Bewerbungsformular
 */
test.describe('Bewerbungsformular', () => {

    /**
     * Navigiere zu einer Stellenseite mit Bewerbungsformular
     */
    async function goToJobPage(page: Page): Promise<boolean> {
        await page.goto('/jobs/');

        // Finde einen Link zu einer Stelle
        const jobLink = page.locator('a[href*="/job/"], .rp-job-card a, .type-job_listing a').first();

        if (await jobLink.isVisible({ timeout: 5000 }).catch(() => false)) {
            await jobLink.click();
            await page.waitForLoadState('networkidle');
            return true;
        }

        return false;
    }

    test('Formular wird angezeigt', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        // "Jetzt bewerben" Button finden und klicken
        const applyButton = page.locator('a[href="#rp-apply-form"]').first();
        await expect(applyButton).toBeVisible();
        await applyButton.click();

        // Formular sollte sichtbar sein
        const form = page.locator('#rp-apply-form');
        await expect(form).toBeVisible();

        // Alpine.js Formular sollte initialisiert sein
        const alpineForm = page.locator('[x-data="applicationForm"]');
        await expect(alpineForm).toBeVisible();

        // Schritt 1 sollte aktiv sein
        const step1 = page.locator('text=Persönliche Daten');
        await expect(step1).toBeVisible();
    });

    test('Formular-Navigation funktioniert', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        // Zum Formular scrollen
        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Schritt 1: Pflichtfelder ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
        await page.fill('input[x-model="formData.email"]', 'max@example.com');

        // Weiter zu Schritt 2
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // Schritt 2 sollte sichtbar sein
        await expect(page.locator('text=Bewerbungsunterlagen')).toBeVisible();

        // Weiter zu Schritt 3
        await nextButton.click();

        // Schritt 3 sollte sichtbar sein
        await expect(page.getByRole('heading', { name: 'Datenschutz & Absenden' })).toBeVisible();

        // Zurück zu Schritt 2
        const backButton = page.locator('button:has-text("Zurück")');
        await backButton.click();

        await expect(page.locator('text=Bewerbungsunterlagen')).toBeVisible();
    });

    test('Validierung zeigt Fehler bei leeren Pflichtfeldern', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Versuche ohne Daten weiterzugehen
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // Fehler sollten angezeigt werden
        const errorMessages = page.locator('[x-show*="errors"]');
        await expect(errorMessages.first()).toBeVisible();
    });

    test('E-Mail-Validierung funktioniert', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Namen ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');

        // Ungültige E-Mail
        await page.fill('input[x-model="formData.email"]', 'invalid-email');

        // Weiter klicken
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // E-Mail-Fehler sollte erscheinen
        const emailError = page.locator('span:has-text("gültige E-Mail")');
        await expect(emailError).toBeVisible();
    });

    test('Datenschutz-Checkbox ist erforderlich', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Schritt 1 ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
        await page.fill('input[x-model="formData.email"]', 'max@example.com');
        await page.locator('button:has-text("Weiter")').first().click();

        // Schritt 2 überspringen
        await page.locator('button:has-text("Weiter")').first().click();

        // Schritt 3: Ohne Checkbox absenden
        const submitButton = page.locator('button:has-text("Bewerbung absenden")');
        await submitButton.click();

        // Fehler sollte erscheinen
        const privacyError = page.getByText('Bitte stimmen Sie der Datenschutzerklärung zu');
        await expect(privacyError).toBeVisible();
    });

    test('Fortschrittsanzeige aktualisiert sich', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Schritt 1: Fortschritt ~33%
        await expect(page.getByText('Schritt 1 von 3')).toBeVisible();

        // Schritt 1 ausfüllen und weiter
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
        await page.fill('input[x-model="formData.email"]', 'max@example.com');
        await page.locator('button:has-text("Weiter")').first().click();

        // Schritt 2: Fortschritt ~67%
        await expect(page.getByText('Schritt 2 von 3')).toBeVisible();

        // Schritt 3
        await page.locator('button:has-text("Weiter")').first().click();

        // Schritt 3: Fortschritt 100%
        await expect(page.getByText('Schritt 3 von 3')).toBeVisible();
    });

    test('Zusammenfassung zeigt eingegebene Daten', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Daten eingeben
        const testData = {
            firstName: 'Maria',
            lastName: 'Testperson',
            email: 'maria@test.de',
            phone: '+49 123 456789'
        };

        await page.fill('input[x-model="formData.first_name"]', testData.firstName);
        await page.fill('input[x-model="formData.last_name"]', testData.lastName);
        await page.fill('input[x-model="formData.email"]', testData.email);
        await page.fill('input[x-model="formData.phone"]', testData.phone);

        // Zu Schritt 3 navigieren
        await page.locator('button:has-text("Weiter")').first().click();
        await page.locator('button:has-text("Weiter")').first().click();

        // Zusammenfassung prüfen
        await expect(page.getByRole('heading', { name: 'Ihre Angaben' })).toBeVisible();
        // Prüfe ob Name zusammengesetzt angezeigt wird (im Formular-Bereich, nicht Sidebar)
        const summarySection = page.locator('#rp-apply-form dl');
        await expect(summarySection).toContainText(testData.firstName);
        await expect(summarySection).toContainText(testData.email);
    });

    test('Responsive Design auf Mobile', async ({ page }) => {
        // Viewport auf Mobile setzen
        await page.setViewportSize({ width: 375, height: 667 });

        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Formular sollte sichtbar sein
        const form = page.locator('#rp-apply-form');
        await expect(form).toBeVisible();

        // Eingabefelder sollten nutzbar sein
        const firstNameInput = page.locator('input[x-model="formData.first_name"]');
        await expect(firstNameInput).toBeVisible();

        // E-Mail-Feld (volle Breite) sollte mindestens 250px breit sein auf Mobile
        const emailInput = page.locator('input[x-model="formData.email"]');
        const box = await emailInput.boundingBox();
        expect(box?.width).toBeGreaterThan(250);
    });
});

/**
 * Tests für Datei-Upload
 */
test.describe('Datei-Upload', () => {

    async function goToJobFormStep2(page: Page): Promise<boolean> {
        await page.goto('/jobs/');

        const jobLink = page.locator('a[href*="/job/"], .rp-job-card a').first();
        if (!(await jobLink.isVisible({ timeout: 5000 }).catch(() => false))) {
            return false;
        }

        await jobLink.click();
        await page.waitForLoadState('networkidle');

        await page.locator('a[href="#rp-apply-form"]').first().click();

        // Schritt 1 ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
        await page.fill('input[x-model="formData.email"]', 'max@example.com');
        await page.locator('button:has-text("Weiter")').first().click();

        return true;
    }

    test('Drag & Drop Zone ist sichtbar', async ({ page }) => {
        const ready = await goToJobFormStep2(page);
        test.skip(!ready, 'Keine Stellenanzeigen vorhanden');

        // Lebenslauf Upload-Bereich
        const dropzone = page.locator('text=Datei hierher ziehen');
        await expect(dropzone).toBeVisible();
    });

    test('Datei-Auswahl Button funktioniert', async ({ page }) => {
        const ready = await goToJobFormStep2(page);
        test.skip(!ready, 'Keine Stellenanzeigen vorhanden');

        // File input sollte existieren
        const fileInput = page.locator('input[type="file"]').first();
        await expect(fileInput).toBeAttached();
    });
});

/**
 * Spam-Schutz Tests
 */
test.describe('Spam-Schutz', () => {

    test('Honeypot-Feld ist versteckt', async ({ page }) => {
        await page.goto('/jobs/');

        const jobLink = page.locator('a[href*="/job/"], .rp-job-card a').first();
        if (!(await jobLink.isVisible({ timeout: 5000 }).catch(() => false))) {
            test.skip();
            return;
        }

        await jobLink.click();

        // Honeypot-Feld sollte existieren aber versteckt sein
        const honeypot = page.locator('input[name="_hp_field"]');
        await expect(honeypot).toBeAttached();

        // Prüfe ob das Feld außerhalb des sichtbaren Bereichs ist (left: -9999px)
        const isHiddenOffscreen = await honeypot.evaluate((el) => {
            const parent = el.closest('.rp-website-field');
            if (parent) {
                const style = window.getComputedStyle(parent);
                // Prüfe auf position: absolute + left: -9999px (offscreen hidden)
                return style.position === 'absolute' && parseInt(style.left) < 0;
            }
            return false;
        });
        expect(isHiddenOffscreen).toBe(true);
    });

    test('Timestamp-Feld ist vorhanden', async ({ page }) => {
        await page.goto('/jobs/');

        const jobLink = page.locator('a[href*="/job/"], .rp-job-card a').first();
        if (!(await jobLink.isVisible({ timeout: 5000 }).catch(() => false))) {
            test.skip();
            return;
        }

        await jobLink.click();

        // Timestamp-Feld sollte existieren
        const timestamp = page.locator('input[name="_form_timestamp"]');
        await expect(timestamp).toBeAttached();

        // Wert sollte eine Zahl sein
        const value = await timestamp.inputValue();
        expect(parseInt(value)).toBeGreaterThan(0);
    });
});
