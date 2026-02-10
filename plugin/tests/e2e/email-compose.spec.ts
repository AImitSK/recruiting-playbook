import { test, expect } from '@playwright/test';

test('Template-Auswahl füllt Betreff und Nachricht', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });

    // Zur Bewerbung navigieren
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=rp-application-detail&id=19');
    await page.waitForTimeout(2000);

    // E-Mail Tab klicken
    const emailTab = page.locator('button:has-text("E-Mail")');
    await emailTab.click();
    await page.waitForTimeout(1000);

    // "Neue E-Mail" Button klicken
    const newEmailButton = page.locator('button:has-text("Neue E-Mail")');
    await newEmailButton.click();
    await page.waitForTimeout(1000);

    // Screenshot vor Template-Auswahl
    await page.screenshot({ path: 'tests/e2e/results/email-compose-before.png', fullPage: true });

    // Template auswählen (z.B. "Interview-Einladung")
    // Der Template-Selektor ist ein WordPress SelectControl innerhalb des Composer-Formulars
    const templateSelect = page.locator('.components-select-control__input').first();
    await templateSelect.selectOption({ label: 'Interview-Einladung' });
    await page.waitForTimeout(1000);

    // Screenshot nach Template-Auswahl
    await page.screenshot({ path: 'tests/e2e/results/email-compose-after.png', fullPage: true });

    // Prüfe ob Betreff gefüllt wurde
    const subjectInput = page.locator('input[type="text"]').nth(1); // 2. Input nach Empfänger
    const subjectValue = await subjectInput.inputValue();
    console.log('Betreff:', subjectValue);
    expect(subjectValue.length).toBeGreaterThan(0);

    // Prüfe ob Nachricht gefüllt wurde
    const bodyTextarea = page.locator('textarea');
    const bodyValue = await bodyTextarea.inputValue();
    console.log('Nachricht Länge:', bodyValue.length);
    console.log('Nachricht (erste 100 Zeichen):', bodyValue.substring(0, 100));
    expect(bodyValue.length).toBeGreaterThan(10);
});
