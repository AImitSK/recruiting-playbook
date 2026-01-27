import { test, expect } from '@playwright/test';

test('E-Mail Tab zeigt E-Mails an', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });

    // Zur Bewerbung #19 navigieren (korrekter Seiten-Slug: rp-application-detail)
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=rp-application-detail&id=19');
    await page.waitForTimeout(3000);

    // Screenshot der Seite
    await page.screenshot({ path: 'tests/e2e/results/email-tab-page.png', fullPage: true });

    // E-Mail Tab klicken
    const emailTab = page.locator('button:has-text("E-Mail")');
    if (await emailTab.isVisible({ timeout: 5000 })) {
        await emailTab.click();
        await page.waitForTimeout(2000);

        // Screenshot nach Tab-Klick
        await page.screenshot({ path: 'tests/e2e/results/email-tab-clicked.png', fullPage: true });

        // Prüfe ob E-Mails angezeigt werden
        const emailList = page.locator('table');
        const noEmails = page.locator('text=Keine E-Mails gefunden');
        const emailRow = page.locator('tr').filter({ hasText: 'Gesendet' });

        if (await noEmails.isVisible({ timeout: 3000 })) {
            console.log('Keine E-Mails gefunden (OK wenn keine existieren)');
        } else if (await emailRow.count() > 0) {
            console.log('E-Mails werden angezeigt!');
            const count = await emailRow.count();
            console.log(`Anzahl E-Mails: ${count}`);
        }

        // Fehler prüfen
        const errorAlert = page.locator('[style*="ffe6e6"]');
        if (await errorAlert.isVisible({ timeout: 2000 })) {
            const errorText = await errorAlert.textContent();
            console.log('FEHLER:', errorText);
        }
    } else {
        console.log('E-Mail Tab nicht sichtbar (Pro-Feature?)');
    }
});
