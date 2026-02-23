<?php
/**
 * Admin-Notices für Marketing-Screenshots ausblenden
 *
 * Blendet störende Admin-Hinweise aus:
 * - Action Scheduler "past-due actions" Warning
 * - WordPress Core Update-Hinweis
 *
 * Installation:
 *   Als MU-Plugin: Kopiere diese Datei nach wp-content/mu-plugins/
 *   cp plugin/tools/hide-admin-notices.php ../../mu-plugins/hide-admin-notices.php
 *
 * Entfernen:
 *   rm wp-content/mu-plugins/hide-admin-notices.php
 *
 * @package RecruitingPlaybook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WordPress Core Update-Hinweis ausblenden
add_action( 'admin_head', function () {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_action( 'admin_notices', 'maintenance_nag', 10 );
} );

// Action Scheduler "past-due actions" Warning per CSS ausblenden
add_action( 'admin_head', function () {
	echo '<style>
		/* Action Scheduler past-due warning */
		.notice a[href*="action-scheduler"],
		.notice:has(a[href*="action-scheduler"]) {
			display: none !important;
		}
		/* WordPress Update Nag (Fallback) */
		.update-nag,
		#wp-admin-bar-updates,
		.notice.notice-warning a[href*="update-core.php"] {
			display: none !important;
		}
		.notice.notice-warning:has(a[href*="update-core.php"]) {
			display: none !important;
		}
	</style>';
} );
