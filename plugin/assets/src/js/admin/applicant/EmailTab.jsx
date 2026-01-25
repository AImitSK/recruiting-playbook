/**
 * EmailTab - E-Mail-Tab für Bewerber-Detailseite
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { plus } from '@wordpress/icons';

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
			<div className="rp-email-tab rp-email-tab--loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="rp-email-tab">
			{ notification && (
				<Notice
					status={ notification.type }
					isDismissible={ true }
					onRemove={ () => setNotification( null ) }
				>
					{ notification.message }
				</Notice>
			) }

			{ view === 'history' ? (
				<>
					<div className="rp-email-tab__header">
						<h3 className="rp-email-tab__title">
							<span className="dashicons dashicons-email-alt"></span>
							{ i18n.emailHistory || 'E-Mail-Verlauf' }
						</h3>
						<Button
							variant="primary"
							icon={ plus }
							onClick={ () => setView( 'compose' ) }
						>
							{ i18n.newEmail || 'Neue E-Mail' }
						</Button>
					</div>

					<EmailHistory
						emails={ emails }
						loading={ emailsLoading }
						error={ emailsError }
						pagination={ pagination }
						onResend={ handleResend }
						onCancel={ handleCancel }
						onPageChange={ goToPage }
					/>
				</>
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
