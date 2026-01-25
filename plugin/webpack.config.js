/**
 * Custom Webpack Configuration
 *
 * Extends @wordpress/scripts default config with multiple entry points
 *
 * @package RecruitingPlaybook
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Haupt-Admin Bundle (Kanban, Applicant Detail, Talent Pool)
		admin: path.resolve( __dirname, 'assets/src/js/admin/index.js' ),
		// E-Mail-Templates App (separate Seite)
		'admin-email': path.resolve( __dirname, 'assets/src/js/admin/email/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets/dist/js' ),
	},
};
