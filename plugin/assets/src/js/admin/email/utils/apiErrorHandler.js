/**
 * API Error Handler Utility
 *
 * Zentrales Error-Handling für API-Calls
 *
 * @package RecruitingPlaybook
 */

/**
 * Prüft ob ein Fehler ein Authentifizierungsfehler ist
 *
 * @param {Error} error Fehler-Objekt
 * @return {boolean} True wenn Auth-Fehler
 */
export function isAuthError( error ) {
	return error?.code === 'rest_cookie_invalid_nonce' ||
		error?.code === 'rest_forbidden' ||
		error?.data?.status === 401 ||
		error?.data?.status === 403;
}

/**
 * Behandelt API-Fehler zentral
 *
 * Bei Authentifizierungsfehlern wird die Seite neu geladen,
 * um eine neue Nonce zu erhalten.
 *
 * @param {Error}    error       Fehler-Objekt
 * @param {Function} setError    State-Setter für Fehlermeldung
 * @param {string}   defaultMsg  Standard-Fehlermeldung
 * @return {boolean} True wenn der Fehler behandelt wurde (Auth-Reload)
 */
export function handleApiError( error, setError, defaultMsg = 'Ein Fehler ist aufgetreten' ) {
	// Aborted requests ignorieren
	if ( error?.name === 'AbortError' ) {
		return true;
	}

	// Auth-Fehler: Seite neu laden
	if ( isAuthError( error ) ) {
		console.warn( 'Authentication error, reloading page...' );
		window.location.reload();
		return true;
	}

	// Andere Fehler: Fehlermeldung setzen
	if ( setError ) {
		setError( error?.message || defaultMsg );
	}

	return false;
}

/**
 * Erstellt einen AbortController für API-Requests
 *
 * @return {AbortController} Neuer AbortController
 */
export function createAbortController() {
	return new AbortController();
}
