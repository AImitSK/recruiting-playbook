/**
 * Screenshot test for Email UI inspection
 */

const { test, expect } = require( '@playwright/test' );

test.describe( 'Email UI Screenshots', () => {
	test( 'capture email composer', async ( { page } ) => {
		// Login to WordPress
		await page.goto( 'http://localhost:8080/wp-login.php' );
		await page.locator( '#user_login' ).fill( 'admin' );
		await page.locator( '#user_pass' ).fill( 'admin' );
		await page.locator( '#wp-submit' ).click();
		await page.waitForURL( '**/wp-admin/**', { timeout: 10000 } );

		// Go to applications list
		await page.goto( 'http://localhost:8080/wp-admin/admin.php?page=rp-applications' );
		await page.waitForTimeout( 3000 );

		// Click on first "Ansehen" button using a more flexible selector
		// The buttons have an eye icon and "Ansehen" text
		const viewBtn = page.locator( '.rp-admin button' ).filter( { hasText: 'Ansehen' } ).first();
		console.log( 'Found view buttons:', await viewBtn.count() );

		if ( await viewBtn.count() > 0 ) {
			await viewBtn.click();
			await page.waitForTimeout( 3000 );

			// Screenshot of detail page
			await page.screenshot( { path: 'screenshots/detail-page.png', fullPage: true } );

			// Click on Email tab
			const emailTab = page.locator( '.rp-admin button' ).filter( { hasText: 'E-Mail' } );
			console.log( 'Found email tabs:', await emailTab.count() );

			if ( await emailTab.count() > 0 ) {
				await emailTab.click();
				await page.waitForTimeout( 2000 );
				await page.screenshot( { path: 'screenshots/email-tab-view.png', fullPage: true } );

				// Click on "Neue E-Mail" button
				const newEmailBtn = page.locator( 'button' ).filter( { hasText: 'Neue E-Mail' } );
				console.log( 'Found new email buttons:', await newEmailBtn.count() );

				if ( await newEmailBtn.count() > 0 ) {
					await newEmailBtn.click();
					await page.waitForTimeout( 2000 );
					await page.screenshot( { path: 'screenshots/email-composer.png', fullPage: true } );
				}
			}
		} else {
			// Fallback: click on the name link
			const nameLink = page.locator( 'a' ).filter( { hasText: 'Stefi Kühne' } ).first();
			if ( await nameLink.count() > 0 ) {
				await nameLink.click();
				await page.waitForTimeout( 3000 );
				await page.screenshot( { path: 'screenshots/detail-page.png', fullPage: true } );
			}
		}
	} );
} );
