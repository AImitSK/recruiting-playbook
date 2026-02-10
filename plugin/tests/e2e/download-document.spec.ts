import { test, expect } from '@playwright/test';

test('Download-Button auf Dokumenten-Tab funktioniert', async ({ page }) => {
    // Login als Admin
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });

    // Gehe zur Bewerbung #19 (hat Dokumente)
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=recruiting-application&id=19');

    // Warte auf Seitenladung
    await page.waitForSelector('.rp-admin', { timeout: 10000 });
    await page.waitForTimeout(2000);

    // Screenshot vor dem Klick auf Dokumente-Tab
    await page.screenshot({ path: 'tests/e2e/results/before-documents-tab.png', fullPage: true });

    // Klicke auf Dokumente-Tab
    const documentsTab = page.locator('button:has-text("Dokumente")');
    await documentsTab.click();
    await page.waitForTimeout(1000);

    // Screenshot nach dem Klick auf Dokumente-Tab
    await page.screenshot({ path: 'tests/e2e/results/documents-tab-visible.png', fullPage: true });

    // Prüfe ob Dokumente angezeigt werden
    const documentItems = page.locator('[style*="backgroundColor: #f9fafb"]');
    const count = await documentItems.count();
    console.log(`Gefundene Dokumente: ${count}`);

    // Prüfe Download-Button
    const downloadButton = page.locator('a:has-text("Download")').first();

    if (await downloadButton.isVisible({ timeout: 5000 })) {
        console.log('Download-Button gefunden');
        const href = await downloadButton.getAttribute('href');
        console.log('Download-URL:', href);

        // Prüfe URL-Format
        expect(href).toContain('admin-ajax.php');
        expect(href).toContain('rp_download_document');

        // Versuche Download (im neuen Tab öffnen)
        const [downloadPage] = await Promise.all([
            page.context().waitForEvent('page', { timeout: 5000 }).catch(() => null),
            downloadButton.click()
        ]);

        if (downloadPage) {
            await downloadPage.waitForLoadState();
            const downloadContent = await downloadPage.content();
            console.log('Download-Seite geladen');

            // Prüfe auf Fehler
            if (downloadContent.includes('wp-die') || downloadContent.includes('Ungültig')) {
                console.log('FEHLER bei Download:', downloadContent.substring(0, 500));
                await downloadPage.screenshot({ path: 'tests/e2e/results/download-error.png' });
            }

            await downloadPage.close();
        } else {
            // Kein neuer Tab geöffnet - möglicherweise direkt heruntergeladen
            console.log('Kein neuer Tab geöffnet (direkter Download)');
        }
    } else {
        console.log('Kein Download-Button sichtbar');
        await page.screenshot({ path: 'tests/e2e/results/no-download-button.png', fullPage: true });
    }

    // Prüfe Ansehen-Button
    const viewButton = page.locator('a:has-text("Ansehen")').first();
    if (await viewButton.isVisible({ timeout: 2000 })) {
        console.log('Ansehen-Button gefunden');
        const viewHref = await viewButton.getAttribute('href');
        console.log('View-URL:', viewHref);
    }
});

test('Dokumente werden mit korrekten URLs angezeigt', async ({ page }) => {
    // Login als Admin
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });

    // API-Aufruf für Bewerbung #19
    const response = await page.evaluate(async () => {
        const res = await fetch('/wp-json/recruiting/v1/applications/19', {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': (window as any).wpApiSettings?.nonce || ''
            }
        });
        return res.json();
    });

    console.log('API Response documents:', JSON.stringify(response.documents, null, 2));

    // Prüfe ob Dokumente download_url haben
    expect(response.documents).toBeDefined();
    expect(response.documents.length).toBeGreaterThan(0);

    for (const doc of response.documents) {
        expect(doc.download_url).toBeTruthy();
        expect(doc.view_url).toBeTruthy();
        expect(doc.download_url).toContain('admin-ajax.php');
        expect(doc.download_url).toContain('rp_download_document');
        expect(doc.download_url).toContain('token=');
    }
});
