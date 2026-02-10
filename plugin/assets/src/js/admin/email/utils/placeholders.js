/**
 * Platzhalter-Utilities
 *
 * @package RecruitingPlaybook
 */

/**
 * HTML-Escape für sichere Ausgabe
 *
 * Verhindert XSS-Angriffe durch Escaping von HTML-Sonderzeichen.
 *
 * @param {*} str Eingabe-String (oder anderer Wert)
 * @return {string} Escaped String
 */
export function escapeHtml( str ) {
	if ( str === null || str === undefined ) {
		return '';
	}

	const string = String( str );

	return string
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#039;' );
}

/**
 * Platzhalter in Text ersetzen (für Vorschau)
 *
 * WICHTIG: Diese Funktion escaped alle Platzhalter-Werte automatisch,
 * um XSS-Angriffe zu verhindern. Die ersetzten Werte sind sicher für
 * die Verwendung in HTML-Kontexten.
 *
 * @param {string} text          Text mit Platzhaltern im Format {placeholder}
 * @param {Object} previewValues Objekt mit Platzhalter-Werten
 * @param {Object} options       Optionen
 * @param {boolean} options.escape HTML-Escape aktivieren (default: true)
 * @return {string} Text mit ersetzten Platzhaltern
 */
export function replacePlaceholders( text, previewValues, options = {} ) {
	const { escape = true } = options;

	if ( ! text ) {
		return '';
	}

	if ( ! previewValues || typeof previewValues !== 'object' ) {
		return text;
	}

	let result = text;

	Object.entries( previewValues ).forEach( ( [ key, value ] ) => {
		// Sicherstellen dass key keine Regex-Sonderzeichen enthält
		const escapedKey = key.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
		const regex = new RegExp( `\\{${ escapedKey }\\}`, 'g' );

		// Wert escapen wenn aktiviert (default)
		const safeValue = escape ? escapeHtml( value ) : String( value );

		result = result.replace( regex, safeValue );
	} );

	return result;
}
