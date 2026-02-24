import { test, expect } from '@playwright/test';

test.describe('i18n - Admin Übersetzungen', () => {

    test('Bewerbungsseite Übersetzungs-Diagnose', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8082/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin');
        await page.click('#wp-submit');
        await page.waitForLoadState('networkidle');

        // Bewerbungsseite öffnen
        await page.goto('http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);

        // Screenshot
        await page.screenshot({ path: 'tests/e2e/results/i18n-applications.png', fullPage: true });

        // 1. Prüfe ob wp.i18n existiert
        const i18nInfo = await page.evaluate(() => {
            const wp = (window as any).wp;
            return {
                wpExists: !!wp,
                i18nExists: !!wp?.i18n,
                hasSetLocaleData: typeof wp?.i18n?.setLocaleData === 'function',
                // Check if translations are loaded for our domain
                testTranslation: wp?.i18n?.__?.('Applications', 'recruiting-playbook') || 'NOT_AVAILABLE',
                testTranslation2: wp?.i18n?.__?.('New', 'recruiting-playbook') || 'NOT_AVAILABLE',
                testTranslation3: wp?.i18n?.__?.('Screening', 'recruiting-playbook') || 'NOT_AVAILABLE',
            };
        });
        console.log('wp.i18n info:', JSON.stringify(i18nInfo, null, 2));

        // 2. Suche nach setLocaleData Aufrufen im HTML
        const html = await page.content();
        const setLocaleMatches = html.match(/setLocaleData/g);
        console.log('setLocaleData calls in HTML:', setLocaleMatches?.length || 0);

        // 3. Suche nach recruiting-playbook in Inline-Scripts
        const inlineScripts = await page.evaluate(() => {
            const scripts = document.querySelectorAll('script:not([src])');
            const results: string[] = [];
            scripts.forEach(s => {
                const text = s.textContent || '';
                if (text.includes('recruiting-playbook') || text.includes('setLocaleData') || text.includes('rp-admin')) {
                    results.push(text.substring(0, 500));
                }
            });
            return results;
        });
        console.log('Relevant inline scripts:', inlineScripts.length);
        inlineScripts.forEach((s, i) => console.log(`  Script ${i}:`, s.substring(0, 300)));

        // 4. Prüfe welche Scripts geladen sind
        const rpScripts = await page.evaluate(() => {
            const scripts = document.querySelectorAll('script[src]');
            const results: string[] = [];
            scripts.forEach(s => {
                const src = (s as HTMLScriptElement).src;
                if (src.includes('recruiting') || src.includes('rp-') || src.includes('admin.js') || src.includes('wp-i18n')) {
                    results.push(src);
                }
            });
            return results;
        });
        console.log('Loaded RP scripts:', rpScripts);
    });
});
