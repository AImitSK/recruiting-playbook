/**
 * EmailPreview - Vorschau einer E-Mail
 *
 * @package RecruitingPlaybook
 */

import { Card, CardBody, CardHeader } from '@wordpress/components';
import PropTypes from 'prop-types';
import DOMPurify from 'dompurify';

// DOMPurify-Konfiguration: Nur sichere Tags für E-Mail-Vorschau
const DOMPURIFY_CONFIG = {
	ALLOWED_TAGS: [ 'p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li' ],
	ALLOWED_ATTR: [ 'href', 'target', 'rel' ],
};

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
	 * @return {string} Sanitized HTML
	 */
	const textToHtml = ( text ) => {
		if ( ! text ) {
			return '';
		}

		// Einfache Markdown-ähnliche Formatierung
		let html = text
			// Escapen von HTML-Zeichen zuerst
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			// Fett (vor Kursiv, da ** auch * enthält)
			.replace( /\*\*(.+?)\*\*/g, '<strong>$1</strong>' )
			// Kursiv
			.replace( /\*(.+?)\*/g, '<em>$1</em>' )
			// Links mit URL-Validierung UND Link-Text-Escaping (XSS-Schutz)
			.replace(
				/\[([^\]]+)\]\(([^)]+)\)/g,
				( match, linkText, url ) => {
					// Link-Text escapen (bereits escaped durch vorherige Replacements,
					// aber sicherheitshalber nochmal prüfen)
					const escapedLinkText = linkText
						.replace( /&/g, '&amp;' )
						.replace( /</g, '&lt;' )
						.replace( />/g, '&gt;' )
						.replace( /"/g, '&quot;' );
					// Nur sichere URLs erlauben (http/https)
					const safeUrl = /^https?:\/\//.test( url ) ? url : '#';
					return `<a href="${ safeUrl }" target="_blank" rel="noopener noreferrer">${ escapedLinkText }</a>`;
				}
			)
			// Zeilenumbrüche zuletzt
			.replace( /\n\n/g, '</p><p>' )
			.replace( /\n/g, '<br>' );

		// DOMPurify sanitiert das Ergebnis mit restriktiver Konfiguration
		return DOMPurify.sanitize( `<p>${ html }</p>`, DOMPURIFY_CONFIG );
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

EmailPreview.propTypes = {
	subject: PropTypes.string,
	body: PropTypes.string,
	recipient: PropTypes.string,
};
