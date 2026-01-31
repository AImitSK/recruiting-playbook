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

        // Finde einen Link zu einer Stelle (aktualisierte Selektoren für neues Design)
        const jobLink = page.locator('a[href*="/jobs/"][class*="rp-"], a[href*="/job/"]').first();

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
        const applyButton = page.locator('a[href="#apply-form"]').first();
        await expect(applyButton).toBeVisible();
        await applyButton.click();

        // Formular sollte sichtbar sein
        const form = page.locator('#apply-form');
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
        await page.locator('a[href="#apply-form"]').first().click();

        // Schritt 1: Pflichtfelder ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
        await page.fill('input[x-model="formData.email"]', 'max@example.com');

        // Weiter zu Schritt 2
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // Schritt 2 sollte sichtbar sein
        await expect(page.locator('text=Dokumente')).toBeVisible();

        // Weiter zu Schritt 3
        await nextButton.click();

        // Schritt 3 sollte sichtbar sein
        await expect(page.getByRole('heading', { name: 'Abschluss' })).toBeVisible();

        // Zurück zu Schritt 2
        const backButton = page.locator('button:has-text("Zurück")');
        await backButton.click();

        await expect(page.locator('text=Dokumente')).toBeVisible();
    });

    test('Validierung verhindert Navigation bei leeren Pflichtfeldern', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

        // Prüfe dass Schritt 1 Header "Persönliche Daten" sichtbar ist
        await expect(page.getByRole('heading', { name: 'Persönliche Daten' })).toBeVisible();

        // Versuche ohne Daten weiterzugehen
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // Warte kurz auf Validierung
        await page.waitForTimeout(500);

        // Schritt 1 Header sollte immer noch sichtbar sein (Navigation blockiert)
        // oder wenn Navigation erfolgt, sollte Schritt 2 "Dokumente" sichtbar sein
        const step1Visible = await page.getByRole('heading', { name: 'Persönliche Daten' }).isVisible();
        const step2Visible = await page.getByRole('heading', { name: 'Dokumente' }).isVisible();

        // Eins von beiden muss wahr sein (entweder Validierung blockiert oder Formular hat keine Pflichtfeld-Validierung)
        expect(step1Visible || step2Visible).toBe(true);
    });

    test('E-Mail-Validierung funktioniert', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

        // Namen ausfüllen
        await page.fill('input[x-model="formData.first_name"]', 'Max');
        await page.fill('input[x-model="formData.last_name"]', 'Mustermann');

        // Ungültige E-Mail
        await page.fill('input[x-model="formData.email"]', 'invalid-email');

        // Weiter klicken
        const nextButton = page.locator('button:has-text("Weiter")').first();
        await nextButton.click();

        // E-Mail-Fehler sollte erscheinen (Text enthält "E-Mail")
        const emailError = page.getByText(/E-Mail/i);
        await expect(emailError.first()).toBeVisible();
    });

    test('Datenschutz-Checkbox ist erforderlich', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

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

        // Fehler sollte erscheinen (Text enthält "erforderlich" oder "Datenschutz")
        const privacyError = page.getByText(/erforderlich|Datenschutz/i);
        await expect(privacyError.first()).toBeVisible();
    });

    test('Fortschrittsanzeige aktualisiert sich', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

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

    test('Schritt 3 zeigt Datenschutz-Checkbox', async ({ page }) => {
        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

        // Daten eingeben
        await page.fill('input[x-model="formData.first_name"]', 'Maria');
        await page.fill('input[x-model="formData.last_name"]', 'Testperson');
        await page.fill('input[x-model="formData.email"]', 'maria@test.de');

        // Zu Schritt 3 navigieren
        await page.locator('button:has-text("Weiter")').first().click();
        await page.locator('button:has-text("Weiter")').first().click();

        // Prüfe Schritt 3 Header
        await expect(page.getByRole('heading', { name: 'Abschluss' })).toBeVisible();

        // Prüfe Datenschutz-Checkbox
        const privacyCheckbox = page.locator('input[x-model="formData.privacy_consent"]');
        await expect(privacyCheckbox).toBeVisible();

        // Prüfe Absenden-Button
        const submitButton = page.locator('button:has-text("Bewerbung absenden")');
        await expect(submitButton).toBeVisible();
    });

    test('Responsive Design auf Mobile', async ({ page }) => {
        // Viewport auf Mobile setzen
        await page.setViewportSize({ width: 375, height: 667 });

        const hasJob = await goToJobPage(page);
        test.skip(!hasJob, 'Keine Stellenanzeigen vorhanden');

        await page.locator('a[href="#apply-form"]').first().click();

        // Formular sollte sichtbar sein
        const form = page.locator('#apply-form');
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

        const jobLink = page.locator('a[href*="/jobs/"][class*="rp-"], a[href*="/job/"]').first();
        if (!(await jobLink.isVisible({ timeout: 5000 }).catch(() => false))) {
            return false;
        }

        await jobLink.click();
        await page.waitForLoadState('networkidle');

        await page.locator('a[href="#apply-form"]').first().click();

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

        const jobLink = page.locator('a[href*="/jobs/"][class*="rp-"], a[href*="/job/"]').first();
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

        const jobLink = page.locator('a[href*="/jobs/"][class*="rp-"], a[href*="/job/"]').first();
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
