/**
 * EmailTab - E-Mail-Tab für Bewerber-Detailseite
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Plus } from 'lucide-react';
import { Button } from '../components/ui/button';
import { EmailComposer, EmailHistory } from '../email/components';
import { useTemplates, useEmailHistory, usePlaceholders } from '../email/hooks';

/**
 * EmailTab Komponente
 *
 * @param {Object} props               Props
 * @param {number} props.applicationId Bewerbungs-ID
 * @param {Object} props.recipient     Empfänger-Daten (email, name)
 * @return {JSX.Element} Komponente
 */
export function EmailTab( { applicationId, recipient = {} } ) {
	const [ view, setView ] = useState( 'history' ); // 'history' | 'compose'
	const [ notification, setNotification ] = useState( null );

	const i18n = window.rpApplicant?.i18n || window.rpEmailData?.i18n || {};

	// Hooks
	const { templates, loading: templatesLoading } = useTemplates();
	const {
		emails,
		loading: emailsLoading,
		error: emailsError,
		sending,
		pagination,
		sendEmail,
		resendEmail,
		cancelEmail,
		goToPage,
		refetch: refetchEmails,
	} = useEmailHistory( { applicationId } );
	const { placeholders, previewValues, loading: placeholdersLoading } = usePlaceholders();

	/**
	 * Benachrichtigung anzeigen
	 *
	 * @param {string} message Nachricht
	 * @param {string} type    Typ
	 */
	const showNotification = useCallback( ( message, type = 'success' ) => {
		setNotification( { message, type } );
		setTimeout( () => setNotification( null ), 3000 );
	}, [] );

	/**
	 * E-Mail senden
	 *
	 * @param {Object} data E-Mail-Daten
	 */
	const handleSend = useCallback( async ( data ) => {
		const result = await sendEmail( data );

		if ( result ) {
			showNotification( i18n.emailSent || 'E-Mail wurde gesendet.' );
			setView( 'history' );
		}
	}, [ sendEmail, showNotification, i18n.emailSent ] );

	/**
	 * E-Mail erneut senden
	 *
	 * @param {number} emailId E-Mail-ID
	 */
	const handleResend = useCallback( async ( emailId ) => {
		const success = await resendEmail( emailId );

		if ( success ) {
			showNotification( i18n.emailResent || 'E-Mail wurde erneut gesendet.' );
		}
	}, [ resendEmail, showNotification, i18n.emailResent ] );

	/**
	 * E-Mail stornieren
	 *
	 * @param {number} emailId E-Mail-ID
	 */
	const handleCancel = useCallback( async ( emailId ) => {
		const success = await cancelEmail( emailId );

		if ( success ) {
			showNotification( i18n.emailCancelled || 'E-Mail wurde storniert.' );
		}
	}, [ cancelEmail, showNotification, i18n.emailCancelled ] );

	// Loading
	const isLoading = templatesLoading || emailsLoading || placeholdersLoading;

	if ( isLoading && view === 'history' && emails.length === 0 ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '3rem', color: '#6b7280' } }>
				<div style={ { width: '1.5rem', height: '1.5rem', border: '2px solid #e5e7eb', borderTopColor: '#1d71b8', borderRadius: '50%', animation: 'spin 0.8s linear infinite' } } />
				<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
			</div>
		);
	}

	return (
		<div>
			{ notification && (
				<div
					style={ {
						padding: '0.75rem 1rem',
						marginBottom: '1rem',
						borderRadius: '0.375rem',
						backgroundColor: notification.type === 'success' ? '#e6f5ec' : '#ffe6e6',
						color: notification.type === 'success' ? '#2fac66' : '#d63638',
						fontSize: '0.875rem',
					} }
				>
					{ notification.message }
				</div>
			) }

			{ view === 'history' ? (
				<EmailHistory
					emails={ emails }
					loading={ emailsLoading }
					error={ emailsError }
					pagination={ pagination }
					onResend={ handleResend }
					onCancel={ handleCancel }
					onPageChange={ goToPage }
					action={
						<Button onClick={ () => setView( 'compose' ) }>
							<Plus style={ { width: '1rem', height: '1rem', marginRight: '0.375rem' } } />
							{ i18n.newEmail || 'Neue E-Mail' }
						</Button>
					}
				/>
			) : (
				<EmailComposer
					templates={ templates }
					placeholders={ placeholders }
					previewValues={ previewValues }
					recipient={ recipient }
					applicationId={ applicationId }
					sending={ sending }
					onSend={ handleSend }
					onCancel={ () => setView( 'history' ) }
				/>
			) }
		</div>
	);
}
