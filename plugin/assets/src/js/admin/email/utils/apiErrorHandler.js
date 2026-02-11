/**
 * API Error Handler Utility
 *
 * Central error handling for API calls
 *
 * @package RecruitingPlaybook
 */

/**
 * Checks if an error is an authentication error
 *
 * @param {Error} error Error object
 * @return {boolean} True if auth error
 */
export function isAuthError( error ) {
	return error?.code === 'rest_cookie_invalid_nonce' ||
		error?.code === 'rest_forbidden' ||
		error?.data?.status === 401 ||
		error?.data?.status === 403;
}

/**
 * Handles API errors centrally
 *
 * For authentication errors, the page is reloaded
 * to obtain a new nonce.
 *
 * @param {Error}    error       Error object
 * @param {Function} setError    State setter for error message
 * @param {string}   defaultMsg  Default error message
 * @return {boolean} True if the error was handled (Auth reload)
 */
export function handleApiError( error, setError, defaultMsg = 'An error occurred' ) {
	// Ignore aborted requests
	if ( error?.name === 'AbortError' ) {
		return true;
	}

	// Auth error: reload page
	if ( isAuthError( error ) ) {
		console.warn( 'Authentication error, reloading page...' );
		window.location.reload();
		return true;
	}

	// Other errors: set error message
	if ( setError ) {
		setError( error?.message || defaultMsg );
	}

	return false;
}

/**
 * Creates an AbortController for API requests
 *
 * @return {AbortController} New AbortController
 */
export function createAbortController() {
	return new AbortController();
}
