import { test, expect } from '@playwright/test';

test('E-Mail Nachricht wird im Modal angezeigt', async ({ page }) => {
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

    // Auf Auge-Button klicken (Ansehen)
    const viewButton = page.locator('button[title="Anzeigen"]').first();
    await viewButton.click();
    await page.waitForTimeout(2000);

    // Screenshot vom Modal
    await page.screenshot({ path: 'tests/e2e/results/email-modal.png', fullPage: true });

    // Prüfe ob Modal geöffnet ist
    const modal = page.locator('text=Nachricht');
    await expect(modal).toBeVisible({ timeout: 5000 });

    // Prüfe ob Nachrichteninhalt vorhanden ist (nicht leer)
    const messageContent = page.locator('div[style*="backgroundColor: rgb(249, 250, 251)"]').last();
    const content = await messageContent.innerHTML();

    console.log('Nachrichteninhalt Länge:', content.length);
    console.log('Nachrichteninhalt (erste 200 Zeichen):', content.substring(0, 200));

    // Der Inhalt sollte nicht leer sein
    expect(content.length).toBeGreaterThan(10);
});
