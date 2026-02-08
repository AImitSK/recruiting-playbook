import { test, expect } from '@playwright/test';

test.describe('Avada Fusion Builder Elements', () => {
	test.beforeEach(async ({ page }) => {
		// Login to WordPress
		await page.goto('http://localhost:8082/wp-login.php');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'admin');
		await page.click('#wp-submit');
		await page.waitForURL(/wp-admin/);
	});

	test('RP Elemente sollten im Fusion Builder verfügbar sein', async ({ page }) => {
		// Create new page to access Fusion Builder
		await page.goto('http://localhost:8082/wp-admin/post-new.php?post_type=page');

		// Wait for page to load
		await page.waitForLoadState('networkidle');

		// Screenshot initial state
		await page.screenshot({ path: 'test-results/avada-1-initial.png', fullPage: true });

		// Look for Fusion Builder button
		const fusionBuilderButton = page.locator('text=Fusion Builder').first();
		const hasFusionBuilder = await fusionBuilderButton.isVisible().catch(() => false);

		console.log('Fusion Builder Button visible:', hasFusionBuilder);

		if (hasFusionBuilder) {
			await fusionBuilderButton.click();
			await page.waitForTimeout(2000);
			await page.screenshot({ path: 'test-results/avada-2-builder-open.png', fullPage: true });
		}

		// Look for Add Element button
		const addElementButton = page.locator('[data-type="fusion_element"], .fusion-builder-add-element, text=Element hinzufügen, text=Add Element').first();
		const hasAddElement = await addElementButton.isVisible().catch(() => false);

		console.log('Add Element Button visible:', hasAddElement);

		if (hasAddElement) {
			await addElementButton.click();
			await page.waitForTimeout(2000);
			await page.screenshot({ path: 'test-results/avada-3-element-picker.png', fullPage: true });
		}

		// Search for RP elements
		const searchInput = page.locator('input[type="search"], input[placeholder*="Search"], .fusion-builder-modal-search input').first();
		const hasSearch = await searchInput.isVisible().catch(() => false);

		console.log('Search input visible:', hasSearch);

		if (hasSearch) {
			await searchInput.fill('RP');
			await page.waitForTimeout(1000);
			await page.screenshot({ path: 'test-results/avada-4-search-rp.png', fullPage: true });

			// Also search for "Recruiting"
			await searchInput.fill('Recruiting');
			await page.waitForTimeout(1000);
			await page.screenshot({ path: 'test-results/avada-5-search-recruiting.png', fullPage: true });

			// Search for "Stellenliste" (German name)
			await searchInput.fill('Stellenliste');
			await page.waitForTimeout(1000);
			await page.screenshot({ path: 'test-results/avada-6-search-stellenliste.png', fullPage: true });
		}

		// Check for categories panel
		const categories = page.locator('.fusion-builder-categories, [class*="category"]');
		const categoryCount = await categories.count();
		console.log('Category elements found:', categoryCount);

		// Get all visible text for debugging
		const bodyText = await page.locator('body').innerText();
		const hasRecruitingPlaybook = bodyText.includes('Recruiting Playbook') || bodyText.includes('recruiting_playbook');
		console.log('Text contains "Recruiting Playbook":', hasRecruitingPlaybook);

		// Final screenshot
		await page.screenshot({ path: 'test-results/avada-7-final.png', fullPage: true });
	});

	test('Debug: Liste alle registrierten Fusion Builder Elemente', async ({ page }) => {
		// Go to a page with Fusion Builder active
		await page.goto('http://localhost:8082/wp-admin/post-new.php?post_type=page');
		await page.waitForLoadState('networkidle');

		// Wait a bit for Fusion Builder to initialize
		await page.waitForTimeout(3000);

		// Look for Fusion Builder and click it to activate
		const fbButton = page.locator('a:has-text("Fusion Builder"), button:has-text("Fusion Builder"), .fusion-builder-live-editor').first();
		if (await fbButton.isVisible()) {
			await fbButton.click();
			await page.waitForTimeout(2000);
		}

		// Execute JavaScript to check for Fusion Builder elements
		const debugInfo = await page.evaluate(() => {
			const result: any = {
				fusionBuilderExists: typeof (window as any).FusionPageBuilder !== 'undefined',
				fusionAppExists: typeof (window as any).fusionAllElements !== 'undefined',
				allElements: [],
				rpElements: [],
				pageTitle: document.title,
				fusionBuilderActive: !!document.querySelector('.fusion-builder-live-toolbar, #fusion-builder-container, .fusion_builder_container')
			};

			// Check window.fusionAllElements
			if ((window as any).fusionAllElements) {
				result.allElements = Object.keys((window as any).fusionAllElements);
				result.rpElements = result.allElements.filter((name: string) => name.includes('rp_'));
			}

			// Check other possible locations
			if ((window as any).FusionPageBuilderApp && (window as any).FusionPageBuilderApp.elements) {
				result.appElements = Object.keys((window as any).FusionPageBuilderApp.elements);
			}

			// Look for the element in the DOM
			const scripts = Array.from(document.querySelectorAll('script'));
			result.hasInlineScript = scripts.some(s => s.textContent?.includes('fusionAllElements'));

			return result;
		});

		console.log('Debug Info:', JSON.stringify(debugInfo, null, 2));

		// Check if RP elements are present
		if (debugInfo.rpElements.length > 0) {
			console.log('✅ RP Elements found:', debugInfo.rpElements);
		} else {
			console.log('❌ No RP Elements found in fusionAllElements');
		}

		// Check global PHP variables via AJAX
		await page.screenshot({ path: 'test-results/avada-debug.png', fullPage: true });
	});
});
