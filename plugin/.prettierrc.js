/**
 * Prettier Configuration
 *
 * Extends @wordpress/prettier-config for consistency with wp-scripts.
 */

// Use WordPress prettier config from @wordpress/scripts
const wpPrettierConfig = require( '@wordpress/scripts/config/.prettierrc.js' );

module.exports = {
	...wpPrettierConfig,
	endOfLine: 'lf',
};
