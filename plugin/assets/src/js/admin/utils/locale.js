/**
 * Locale utilities
 *
 * Provides dynamic locale detection from WordPress instead of hardcoded 'de-DE'.
 *
 * @package RecruitingPlaybook
 */

/**
 * Get the current WordPress locale from the HTML lang attribute.
 *
 * WordPress sets `<html lang="de-DE">` (or the active locale) automatically.
 * This avoids hardcoding 'de-DE' in JavaScript files.
 *
 * @return {string} BCP 47 locale string (e.g. 'de-DE', 'en-US')
 */
export function getWpLocale() {
	return document.documentElement.lang || 'en';
}
