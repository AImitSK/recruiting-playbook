/**
 * Custom Hook für E-Mail-Historie
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { handleApiError } from '../utils';

/**
 * Hook zum Laden und Verwalten der E-Mail-Historie
 *
 * @param {Object} options               Optionen
 * @param {number} options.applicationId Bewerbungs-ID
 * @param {number} options.candidateId   Kandidaten-ID
 * @return {Object} Email history state und Funktionen
 */
export function useEmailHistory( options = {} ) {
	const [ emails, setEmails ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ sending, setSending ] = useState( false );
	const [ pagination, setPagination ] = useState( {
		total: 0,
		pages: 1,
		page: 1,
		perPage: 20,
	} );

	// Stabile Referenzen
	const applicationId = options?.applicationId;
	const candidateId = options?.candidateId;
	const perPage = pagination.perPage;
	const i18n = window.rpEmailData?.i18n || window.rpApplicant?.i18n || {};
	const errorLoadingMsg = i18n.errorLoading || 'Fehler beim Laden der E-Mails';
	const errorSendingMsg = i18n.errorSending || 'Fehler beim Senden';
	const errorCancellingMsg = i18n.errorCancelling || 'Fehler beim Stornieren';
	const errorPreviewMsg = i18n.errorPreview || 'Fehler bei der Vorschau';

	// Refs für Cleanup und Mount-Status
	const abortControllerRef = useRef( null );
	const isMountedRef = useRef( true );

	/**
	 * E-Mails vom Server laden
	 *
	 * @param {number} page Seitennummer
	 */
	const fetchEmails = useCallback( async ( page = 1 ) => {
		// Early return wenn keine ID vorhanden
		if ( ! applicationId && ! candidateId ) {
			setEmails( [] );
			setLoading( false );
			return;
		}

		// Vorherigen Request abbrechen
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
		abortControllerRef.current = new AbortController();

		try {
			setLoading( true );
			setError( null );

			let path;

			if ( applicationId ) {
				path = `/recruiting/v1/applications/${ applicationId }/emails`;
			} else if ( candidateId ) {
				path = `/recruiting/v1/candidates/${ candidateId }/emails`;
			} else {
				path = '/recruiting/v1/emails/log';
			}

			const params = new URLSearchParams();
			params.append( 'page', page.toString() );
			params.append( 'per_page', perPage.toString() );

			path += '?' + params.toString();

			const data = await apiFetch( {
				path,
				signal: abortControllerRef.current.signal,
			} );

			// Nur State setzen wenn noch mounted
			if ( isMountedRef.current ) {
				setEmails( data.items || [] );
				setPagination( {
					total: data.total || 0,
					pages: data.pages || 1,
					page,
					perPage,
				} );
			}
		} catch ( err ) {
			// AbortError explizit ignorieren
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current && ! handleApiError( err, setError, errorLoadingMsg ) ) {
				console.error( 'Error fetching emails:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [ applicationId, candidateId, perPage, errorLoadingMsg ] );

	// Initial laden und Cleanup
	useEffect( () => {
		isMountedRef.current = true;
		fetchEmails();

		return () => {
			isMountedRef.current = false;
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchEmails ] );

	/**
	 * E-Mail senden
	 *
	 * @param {Object} data E-Mail-Daten
	 * @return {Object|null} Ergebnis oder null
	 */
	const sendEmail = useCallback( async ( data ) => {
		try {
			setSending( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/emails/send',
				method: 'POST',
				data: {
					application_id: applicationId,
					...data,
				},
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			// Nach erfolgreichem Senden neu laden
			await fetchEmails();

			return result;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSendingMsg ) ) {
				console.error( 'Error sending email:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSending( false );
			}
		}
	}, [ applicationId, fetchEmails, errorSendingMsg ] );

	/**
	 * E-Mail erneut senden
	 *
	 * @param {number} emailLogId E-Mail-Log-ID
	 * @return {boolean} Erfolg
	 */
	const resendEmail = useCallback( async ( emailLogId ) => {
		try {
			setSending( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/emails/log/${ emailLogId }/resend`,
				method: 'POST',
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			// Nach erfolgreichem Senden neu laden
			await fetchEmails();

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}
			if ( ! handleApiError( err, setError, errorSendingMsg ) ) {
				console.error( 'Error resending email:', err );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSending( false );
			}
		}
	}, [ fetchEmails, errorSendingMsg ] );

	/**
	 * Geplante E-Mail stornieren
	 *
	 * @param {number} emailLogId E-Mail-Log-ID
	 * @return {boolean} Erfolg
	 */
	const cancelEmail = useCallback( async ( emailLogId ) => {
		try {
			setSending( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/emails/${ emailLogId }/cancel`,
				method: 'POST',
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			// Optimistic Update
			setEmails( ( prev ) =>
				prev.map( ( email ) =>
					email.id === emailLogId
						? { ...email, status: 'cancelled', can_cancel: false }
						: email
				)
			);

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}
			if ( ! handleApiError( err, setError, errorCancellingMsg ) ) {
				console.error( 'Error cancelling email:', err );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSending( false );
			}
		}
	}, [ errorCancellingMsg ] );

	/**
	 * Vorschau generieren
	 *
	 * @param {Object} data Preview-Daten
	 * @return {Object|null} Preview oder null
	 */
	const previewEmail = useCallback( async ( data ) => {
		try {
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/emails/preview',
				method: 'POST',
				data: {
					application_id: applicationId,
					...data,
				},
			} );

			return result;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorPreviewMsg ) ) {
				console.error( 'Error previewing email:', err );
			}
			return null;
		}
	}, [ applicationId, errorPreviewMsg ] );

	/**
	 * Seite wechseln
	 *
	 * @param {number} page Neue Seitennummer
	 */
	const goToPage = useCallback( ( page ) => {
		fetchEmails( page );
	}, [ fetchEmails ] );

	return {
		emails,
		loading,
		error,
		sending,
		pagination,
		fetchEmails,
		sendEmail,
		resendEmail,
		cancelEmail,
		previewEmail,
		goToPage,
		refetch: () => fetchEmails( pagination.page ),
	};
}
