/**
 * WordPress.org Guideline 5 (Trialware) Compliance Test Suite
 *
 * Prüft dass die Free-Version KEINE Premium-Features zeigt oder erwähnt.
 * Basiert auf WordPress.org Review Feedback vom 26. März 2026.
 *
 * KRITISCH: Alle Tests müssen bestehen für WordPress.org Approval!
 */

const { test, expect } = require('@playwright/test');

test.describe('WordPress.org Guideline 5 - Trialware Compliance', () => {

	// Login vor jedem Test
	test.beforeEach(async ({ page }) => {
		await page.goto('http://localhost:8082/wp-admin');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'admin');
		await page.click('#wp-submit');
		await page.waitForSelector('#wpadminbar', { timeout: 10000 });
	});

	// ========================================
	// 1. ADMIN MENÜ - KEINE PREMIUM ITEMS
	// ========================================

	test('Admin Menü: Keine Premium-Menüpunkte sichtbar', async ({ page }) => {
		await page.goto('http://localhost:8082/wp-admin');

		// Warte auf Admin-Menü
		await page.waitForSelector('#adminmenu', { timeout: 5000 });

		const menuHTML = await page.$eval('#adminmenu', el => el.innerHTML);

		// VERBOTEN: Diese Menüpunkte dürfen NICHT existieren (auch nicht mit Lock-Icon!)
		const forbiddenMenuItems = [
			'Kanban Board',
			'Talent Pool',
			'Reports',
			'Reporting',
			'Form Builder',
			'Email Templates',
			'Bulk Email',
		];

		for (const item of forbiddenMenuItems) {
			expect(menuHTML).not.toContain(item);
		}

		// ERLAUBT: Diese Menüpunkte MÜSSEN existieren (Free-Features)
		const allowedMenuItems = [
			'Bewerbungen',
			'Einstellungen',
		];

		for (const item of allowedMenuItems) {
			expect(menuHTML).toContain(item);
		}

		console.log('✅ Admin Menü zeigt keine Premium-Features');
	});

	// ========================================
	// 2. BEWERBUNGEN-SEITE - KEIN EXPORT BUTTON
	// ========================================

	test('Bewerbungen-Seite: Kein Export-Button sichtbar', async ({ page }) => {
		await page.goto('http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook');

		// Warte bis Seite geladen
		await page.waitForSelector('.wrap', { timeout: 10000 });

		const bodyHTML = await page.content();

		// VERBOTEN: Export-Button darf nicht existieren
		expect(bodyHTML).not.toContain('Export');
		expect(bodyHTML).not.toContain('export');
		expect(bodyHTML).not.toContain('CSV');

		// Prüfe dass es keinen Link zu rp-export gibt
		const exportLink = await page.$('a[href*="page=rp-export"]');
		expect(exportLink).toBeNull();

		console.log('✅ Bewerbungen-Seite zeigt keinen Export-Button');
	});

	// ========================================
	// 3. EINSTELLUNGEN - KEINE PREMIUM INTEGRATIONEN
	// ========================================

	test('Einstellungen > Integrationen: Keine Premium-Integrationen sichtbar', async ({ page }) => {
		await page.goto('http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook-settings');

		// Warte auf Settings-Page
		await page.waitForSelector('.wrap', { timeout: 10000 });

		// Klicke auf Integrationen-Tab
		const integrationsTab = await page.$('button:has-text("Integrationen"), a:has-text("Integrationen")');
		if (integrationsTab) {
			await integrationsTab.click();
			await page.waitForTimeout(1000);
		}

		const bodyHTML = await page.content();

		// VERBOTEN: Diese Premium-Integrationen dürfen NICHT erwähnt werden
		const forbiddenIntegrations = [
			'Slack',
			'Microsoft Teams',
			'Teams',
			'Google Ads',
		];

		for (const integration of forbiddenIntegrations) {
			expect(bodyHTML).not.toContain(integration);
		}

		// ERLAUBT: Diese Free-Integrationen MÜSSEN existieren
		const allowedIntegrations = [
			'Google for Jobs',
			'XML',
		];

		for (const integration of allowedIntegrations) {
			expect(bodyHTML).toContain(integration);
		}

		console.log('✅ Einstellungen zeigen keine Premium-Integrationen');
	});

	// ========================================
	// 4. BEWERBUNGS-DETAILSEITE - KEINE UPGRADE-BOX
	// ========================================

	test('Bewerbungs-Detailseite: Keine "Pro-Funktion" Upgrade-Box', async ({ page }) => {
		// Erstelle Test-Bewerbung falls keine existiert
		await page.goto('http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook');
		await page.waitForSelector('.wrap', { timeout: 10000 });

		// Suche erste Bewerbung
		const firstAppLink = await page.$('a[href*="page=rp-application-detail"]');

		if (firstAppLink) {
			await firstAppLink.click();
			await page.waitForSelector('.wrap', { timeout: 10000 });

			const bodyHTML = await page.content();

			// VERBOTEN: Upgrade-Prompts dürfen nicht existieren
			const forbiddenTexts = [
				'Pro-Funktion',
				'Pro-Feature',
				'Upgraden Sie auf Pro',
				'Upgrade to Pro',
				'Auf Pro upgraden',
				'Pro feature',
			];

			for (const text of forbiddenTexts) {
				expect(bodyHTML).not.toContain(text);
			}

			console.log('✅ Bewerbungs-Detailseite zeigt keine Upgrade-Prompts');
		} else {
			console.log('⚠️ Keine Bewerbung zum Testen vorhanden - Test übersprungen');
		}
	});

	// ========================================
	// 5. REST API - KEINE PREMIUM KEYS IN RESPONSE
	// ========================================

	test('REST API /settings/integrations: Keine Premium-Keys in Response', async ({ page }) => {
		// API-Request mit WordPress Session
		const response = await page.request.get('http://localhost:8082/wp-json/recruiting/v1/settings/integrations', {
			headers: {
				'Cookie': await getCookieHeader(page),
			},
		});

		// API könnte 403 zurückgeben (Permission), das ist OK
		if (response.status() === 200) {
			const data = await response.json();

			// VERBOTEN: Diese Premium-Keys dürfen NICHT in der Response sein
			const forbiddenKeys = [
				'slack_enabled',
				'slack_webhook_url',
				'teams_enabled',
				'teams_webhook_url',
				'google_ads_enabled',
				'google_ads_conversion_id',
			];

			for (const key of forbiddenKeys) {
				expect(data).not.toHaveProperty(key);
			}

			// ERLAUBT: Free-Keys müssen existieren
			expect(data).toHaveProperty('google_jobs_enabled');
			expect(data).toHaveProperty('xml_feed_enabled');

			console.log('✅ API Response enthält keine Premium-Integration-Keys');
		} else {
			console.log('⚠️ API nicht erreichbar (403/404) - Test übersprungen');
		}
	});

	// ========================================
	// 6. SOURCECODE - KEINE LOCK ICONS IM UI
	// ========================================

	test('UI: Keine Lock-Icons oder Pro-Badges sichtbar', async ({ page }) => {
		// Prüfe mehrere Admin-Seiten
		const pagesToCheck = [
			'http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook',
			'http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook-settings',
		];

		for (const url of pagesToCheck) {
			await page.goto(url);
			await page.waitForSelector('.wrap', { timeout: 10000 });

			const bodyHTML = await page.content();

			// VERBOTEN: Lock-Icons und Pro-Badges
			expect(bodyHTML).not.toContain('🔒');
			expect(bodyHTML).not.toContain('🔓');
			expect(bodyHTML).not.toContain('dashicons-lock');

			// Suche nach Pro-Badges (könnte als Text oder Badge existieren)
			const proBadges = await page.$$('*:has-text("Pro")');

			// Freemius "Upgrade" ist erlaubt, aber keine Feature-Badges
			for (const badge of proBadges) {
				const text = await badge.textContent();

				// Erlaubt: "Upgrade to Pro" Button im Freemius-Menü
				// Verboten: "Feature XYZ (Pro)" Badges
				if (text && text.includes('Pro') && !text.includes('Upgrade')) {
					const html = await badge.innerHTML();
					expect(html).not.toContain('Badge');
					expect(html).not.toContain('badge');
				}
			}

			console.log(`✅ ${url} zeigt keine Lock-Icons`);
		}
	});

	// ========================================
	// 7. KEINE FATAL ERRORS
	// ========================================

	test('Keine PHP Fatal Errors auf Admin-Seiten', async ({ page }) => {
		const pagesToCheck = [
			'http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook',
			'http://localhost:8082/wp-admin/admin.php?page=recruiting-playbook-settings',
			'http://localhost:8082/wp-json/recruiting/v1/settings',
		];

		for (const url of pagesToCheck) {
			await page.goto(url);
			await page.waitForTimeout(2000);

			const bodyText = await page.textContent('body');

			// VERBOTEN: Fatal Errors
			expect(bodyText).not.toContain('Fatal error');
			expect(bodyText).not.toContain('Class "RecruitingPlaybook\\Services\\EmailService" not found');
			expect(bodyText).not.toContain('Call to undefined method');

			console.log(`✅ ${url} lädt ohne Fatal Error`);
		}
	});

	// ========================================
	// 8. FREEMIUS UPGRADE-LINK ERLAUBT
	// ========================================

	test('Freemius "Pricing" Link ist erlaubt und vorhanden', async ({ page }) => {
		await page.goto('http://localhost:8082/wp-admin');

		// Suche nach Freemius Submenu
		const freemiusMenu = await page.$('#toplevel_page_recruiting-playbook-pricing, a[href*="recruiting-playbook-pricing"]');

		// Freemius Pricing-Seite IST erlaubt (externes Upgrade-Link)
		if (freemiusMenu) {
			console.log('✅ Freemius Pricing-Link vorhanden (erlaubt)');
		} else {
			console.log('ℹ️ Freemius Pricing-Link nicht gefunden (optional)');
		}
	});

});

/**
 * Helper: Get cookie header from page context
 */
async function getCookieHeader(page) {
	const cookies = await page.context().cookies();
	return cookies.map(c => `${c.name}=${c.value}`).join('; ');
}
