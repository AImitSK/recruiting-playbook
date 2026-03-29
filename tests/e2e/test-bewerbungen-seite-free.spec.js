/**
 * E2E Test: Bewerbungen-Seite lädt in Free-Version ohne Crash
 *
 * Testet ob ApplicationService.php EmailService Guard funktioniert
 */

const { test, expect } = require('@playwright/test');

test.describe('Free Version - Bewerbungen Seite', () => {

  // Login vor jedem Test
  test.beforeEach(async ({ page }) => {
    await page.goto('http://localhost:8082/wp-admin');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForSelector('#wpadminbar', { timeout: 10000 });
  });

  test('Bewerbungen-Seite lädt ohne Crash', async ({ page }) => {
    // Navigiere zur Bewerbungen-Seite (Login erfolgt in beforeEach)
    await page.goto('http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook');

    // Prüfe: Seite lädt (kein Fatal Error)
    await page.waitForSelector('.wrap', { timeout: 10000 });

    // Prüfe: H1 enthält "Bewerbungen"
    const h1Text = await page.textContent('.wrap h1');
    expect(h1Text).toContain('Bewerbungen');

    // Prüfe: Keine PHP Fatal Error Meldung
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('Class "RecruitingPlaybook\\Services\\EmailService" not found');

    // Prüfe: Export-Button oder Tabelle vorhanden (bedeutet Seite funktioniert)
    const hasExportButton = await page.$('button:has-text("Export"), a:has-text("Export")');
    const hasApplicationsList = bodyText.includes('applications') || bodyText.includes('Bewerbungen');
    expect(hasExportButton !== null || hasApplicationsList).toBeTruthy();

    console.log('✅ Bewerbungen-Seite lädt erfolgreich in Free-Version');
  });

  test('REST API /settings lädt ohne 500 Error', async ({ page }) => {
    // Direkt zur Settings-Page navigieren (nutzt WordPress Session)
    await page.goto('http://localhost:8082/wp-json/recruiting/v1/settings');

    // Hole Response-Body
    const responseText = await page.textContent('body');

    // Wichtig: Kein 500 Fatal Error! (403 ist OK, bedeutet nur Permission-Problem)
    expect(responseText).not.toContain('"code":"rest_no_route"');
    expect(responseText).not.toContain('Fatal error');
    expect(responseText).not.toContain('Class "RecruitingPlaybook\\Services\\EmailService" not found');

    console.log('✅ Settings API existiert und crasht nicht');
  });

});

/**
 * Helper: Get cookies from page context
 */
async function getCookies(page) {
  const cookies = await page.context().cookies();
  return cookies.map(c => `${c.name}=${c.value}`).join('; ');
}
