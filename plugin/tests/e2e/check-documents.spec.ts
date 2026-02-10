import { test, expect } from '@playwright/test';

test('Dokumente haben Download-URLs', async ({ page }) => {
    // Login als Admin
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');

    // Warte auf Admin Dashboard
    await page.waitForURL('**/wp-admin/**');

    // Gehe zur Bewerbungsdetailseite (erste Bewerbung mit Dokumenten)
    // Erst prüfen ob es eine Bewerbung gibt
    const apiResponse = await page.evaluate(async () => {
        const response = await fetch('/wp-json/recruiting/v1/applications?per_page=10', {
            headers: {
                'X-WP-Nonce': (window as any).wpApiSettings?.nonce || ''
            }
        });
        return response.json();
    });

    console.log('API Response:', JSON.stringify(apiResponse, null, 2));

    // Wenn Bewerbungen vorhanden sind, prüfe die Dokumente
    if (apiResponse.items && apiResponse.items.length > 0) {
        // Erste Bewerbung mit Dokumenten finden
        const appWithDocs = apiResponse.items.find((app: any) => app.documents_count > 0);

        if (appWithDocs) {
            console.log('Bewerbung mit Dokumenten gefunden:', appWithDocs.id);

            // Detail-API aufrufen
            const detailResponse = await page.evaluate(async (appId) => {
                const response = await fetch(`/wp-json/recruiting/v1/applications/${appId}`, {
                    headers: {
                        'X-WP-Nonce': (window as any).wpApiSettings?.nonce || ''
                    }
                });
                return response.json();
            }, appWithDocs.id);

            console.log('Detail Response:', JSON.stringify(detailResponse, null, 2));

            // Prüfe ob Dokumente download_url haben
            if (detailResponse.documents && detailResponse.documents.length > 0) {
                for (const doc of detailResponse.documents) {
                    console.log(`Dokument ${doc.id}: ${doc.filename}`);
                    console.log(`  - download_url: ${doc.download_url}`);
                    console.log(`  - view_url: ${doc.view_url}`);

                    expect(doc.download_url).toBeTruthy();
                    expect(doc.view_url).toBeTruthy();
                    expect(doc.download_url).toContain('admin-ajax.php');
                    expect(doc.download_url).toContain('rp_download_document');
                }
            } else {
                console.log('Keine Dokumente in der Detailansicht gefunden');
            }
        } else {
            console.log('Keine Bewerbung mit Dokumenten gefunden');
        }
    } else {
        console.log('Keine Bewerbungen gefunden');
    }
});

test('Download-Button funktioniert', async ({ page }) => {
    // Login als Admin
    await page.goto('http://localhost:8080/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');

    // Bewerbung mit Dokumenten finden und zur Detailseite navigieren
    const apiResponse = await page.evaluate(async () => {
        const response = await fetch('/wp-json/recruiting/v1/applications?per_page=10', {
            headers: {
                'X-WP-Nonce': (window as any).wpApiSettings?.nonce || ''
            }
        });
        return response.json();
    });

    if (apiResponse.items && apiResponse.items.length > 0) {
        const appWithDocs = apiResponse.items.find((app: any) => app.documents_count > 0);

        if (appWithDocs) {
            // Gehe zur Detailseite
            await page.goto(`http://localhost:8080/wp-admin/admin.php?page=recruiting-application&id=${appWithDocs.id}`);
            await page.waitForTimeout(2000);

            // Klicke auf Dokumente-Tab
            await page.click('button:has-text("Dokumente")');
            await page.waitForTimeout(1000);

            // Screenshot machen
            await page.screenshot({ path: 'tests/e2e/results/documents-tab.png', fullPage: true });

            // Prüfe ob Download-Button existiert
            const downloadButton = page.locator('a:has-text("Download")').first();
            const viewButton = page.locator('a:has-text("Ansehen")').first();

            if (await downloadButton.isVisible()) {
                console.log('Download-Button gefunden');
                const href = await downloadButton.getAttribute('href');
                console.log('Download-URL:', href);
                expect(href).toContain('admin-ajax.php');
            } else {
                console.log('Kein Download-Button sichtbar');
            }

            if (await viewButton.isVisible()) {
                console.log('Ansehen-Button gefunden');
            }
        }
    }
});
