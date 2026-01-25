/**
 * Custom Hook für E-Mail-Historie
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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

	const i18n = window.rpEmailData?.i18n || window.rpApplicant?.i18n || {};

	/**
	 * E-Mails vom Server laden
	 *
	 * @param {number} page Seitennummer
	 */
	const fetchEmails = useCallback( async ( page = 1 ) => {
		try {
			setLoading( true );
			setError( null );

			let path;

			if ( options.applicationId ) {
				path = `/recruiting/v1/applications/${ options.applicationId }/emails`;
			} else if ( options.candidateId ) {
				path = `/recruiting/v1/candidates/${ options.candidateId }/emails`;
			} else {
				path = '/recruiting/v1/emails/log';
			}

			const params = new URLSearchParams();
			params.append( 'page', page.toString() );
			params.append( 'per_page', pagination.perPage.toString() );

			path += '?' + params.toString();

			const data = await apiFetch( { path } );

			setEmails( data.items || [] );
			setPagination( {
				total: data.total || 0,
				pages: data.pages || 1,
				page: page,
				perPage: pagination.perPage,
			} );
		} catch ( err ) {
			console.error( 'Error fetching emails:', err );
			setError( err.message || i18n.errorLoading || 'Fehler beim Laden der E-Mails' );
		} finally {
			setLoading( false );
		}
	}, [ options.applicationId, options.candidateId, pagination.perPage, i18n.errorLoading ] );

	// Initial laden
	useEffect( () => {
		fetchEmails();
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
					application_id: options.applicationId,
					...data,
				},
			} );

			// Nach erfolgreichem Senden neu laden
			await fetchEmails();

			return result;
		} catch ( err ) {
			console.error( 'Error sending email:', err );
			setError( err.message || i18n.errorSending || 'Fehler beim Senden' );
			return null;
		} finally {
			setSending( false );
		}
	}, [ options.applicationId, fetchEmails, i18n.errorSending ] );

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

			// Nach erfolgreichem Senden neu laden
			await fetchEmails();

			return true;
		} catch ( err ) {
			console.error( 'Error resending email:', err );
			setError( err.message || i18n.errorSending || 'Fehler beim erneuten Senden' );
			return false;
		} finally {
			setSending( false );
		}
	}, [ fetchEmails, i18n.errorSending ] );

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
			console.error( 'Error cancelling email:', err );
			setError( err.message || i18n.errorCancelling || 'Fehler beim Stornieren' );
			return false;
		} finally {
			setSending( false );
		}
	}, [ i18n.errorCancelling ] );

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
					application_id: options.applicationId,
					...data,
				},
			} );

			return result;
		} catch ( err ) {
			console.error( 'Error previewing email:', err );
			setError( err.message || i18n.errorPreview || 'Fehler bei der Vorschau' );
			return null;
		}
	}, [ options.applicationId, i18n.errorPreview ] );

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
