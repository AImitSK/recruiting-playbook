/**
 * EmailPreview - Vorschau einer E-Mail
 *
 * @package RecruitingPlaybook
 */

import { Card, CardBody, CardHeader } from '@wordpress/components';

/**
 * EmailPreview Komponente
 *
 * @param {Object} props           Props
 * @param {string} props.subject   Betreff
 * @param {string} props.body      Inhalt
 * @param {string} props.recipient Empfänger (optional)
 * @return {JSX.Element} Komponente
 */
export function EmailPreview( { subject = '', body = '', recipient = '' } ) {
	const i18n = window.rpEmailData?.i18n || {};

	/**
	 * Text in HTML mit Zeilenumbrüchen umwandeln
	 *
	 * @param {string} text Text
	 * @return {string} HTML
	 */
	const textToHtml = ( text ) => {
		if ( ! text ) {
			return '';
		}

		// Einfache Markdown-ähnliche Formatierung
		let html = text
			// Escapen
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			// Zeilenumbrüche
			.replace( /\n\n/g, '</p><p>' )
			.replace( /\n/g, '<br>' )
			// Fett
			.replace( /\*\*(.+?)\*\*/g, '<strong>$1</strong>' )
			// Kursiv
			.replace( /\*(.+?)\*/g, '<em>$1</em>' )
			// Links
			.replace(
				/\[([^\]]+)\]\(([^)]+)\)/g,
				'<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
			);

		return `<p>${ html }</p>`;
	};

	return (
		<div className="rp-email-preview">
			<Card>
				<CardHeader>
					<h3>{ i18n.preview || 'Vorschau' }</h3>
				</CardHeader>
				<CardBody>
					<div className="rp-email-preview__envelope">
						{ recipient && (
							<div className="rp-email-preview__to">
								<span className="rp-email-preview__label">
									{ i18n.to || 'An' }:
								</span>
								<span className="rp-email-preview__value">{ recipient }</span>
							</div>
						) }
						<div className="rp-email-preview__subject">
							<span className="rp-email-preview__label">
								{ i18n.subject || 'Betreff' }:
							</span>
							<span className="rp-email-preview__value">
								{ subject || (
									<em className="rp-email-preview__placeholder">
										{ i18n.noSubject || '(Kein Betreff)' }
									</em>
								) }
							</span>
						</div>
					</div>

					<div className="rp-email-preview__body">
						{ body ? (
							<div
								className="rp-email-preview__content"
								dangerouslySetInnerHTML={ { __html: textToHtml( body ) } }
							/>
						) : (
							<p className="rp-email-preview__placeholder">
								{ i18n.noContent || '(Kein Inhalt)' }
							</p>
						) }
					</div>
				</CardBody>
			</Card>
		</div>
	);
}
