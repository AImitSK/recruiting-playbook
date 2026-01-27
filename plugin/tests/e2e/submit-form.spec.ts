import { test, expect } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';

test('Bewerbung absenden mit Anhängen', async ({ page }) => {
    // Test-PDF erstellen
    const testPdfPath = path.join(__dirname, 'test-cv.pdf');
    if (!fs.existsSync(testPdfPath)) {
        // Einfache PDF-ähnliche Datei erstellen
        fs.writeFileSync(testPdfPath, '%PDF-1.4 Test CV Content');
    }

    // Job-Seite öffnen
    await page.goto('http://localhost:8080/jobs/senior-php-developer/');

    // Warte auf das Formular
    await page.waitForSelector('[data-rp-application-form]');

    // Warte 6 Sekunden wegen Spam-Schutz (MIN_FORM_TIME = 5)
    await page.waitForTimeout(6000);

    // Schritt 1: Persönliche Daten
    await page.fill('input[x-model="formData.first_name"]', 'Max');
    await page.fill('input[x-model="formData.last_name"]', 'Mustermann');
    await page.fill('input[x-model="formData.email"]', 'test' + Date.now() + '@example.com');

    // Weiter zu Schritt 2
    await page.click('button:has-text("Weiter")');
    await page.waitForTimeout(500);

    // Schritt 2: Dokumente hochladen
    // Lebenslauf hochladen
    const resumeInput = page.locator('input[type="file"]').first();
    await resumeInput.setInputFiles(testPdfPath);
    await page.waitForTimeout(500);

    // Anschreiben/Nachricht eingeben
    await page.fill('textarea[x-model="formData.cover_letter"]', 'Dies ist mein Anschreiben. Ich bewerbe mich hiermit auf die ausgeschriebene Stelle.');
    await page.waitForTimeout(500);

    // Weiter zu Schritt 3
    await page.click('button:has-text("Weiter")');
    await page.waitForTimeout(500);

    // Schritt 3: Datenschutz
    await page.click('input[x-model="formData.privacy_consent"]');

    // Console-Logs abfangen
    page.on('console', msg => {
        console.log('BROWSER:', msg.type(), msg.text());
    });

    // Request abfangen
    page.on('response', response => {
        if (response.url().includes('/wp-json/recruiting/')) {
            console.log('API Response:', response.status(), response.url());
        }
    });

    // Absenden
    await page.click('button:has-text("Bewerbung absenden")');

    // Warte auf Ergebnis
    await page.waitForTimeout(3000);

    // Screenshot machen
    await page.screenshot({ path: 'tests/e2e/results/submit-result.png', fullPage: true });

    // Prüfe auf Erfolg oder Fehler
    const successMessage = page.locator('text=erfolgreich');
    const errorMessage = page.locator('[x-show="error"]');

    if (await errorMessage.isVisible()) {
        const errorText = await errorMessage.textContent();
        console.log('FEHLER:', errorText);
    }

    // Erwarte Erfolg
    await expect(successMessage).toBeVisible({ timeout: 10000 });
});
