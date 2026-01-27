/**
 * EmailPreview - Vorschau einer E-Mail
 *
 * @package RecruitingPlaybook
 */

import PropTypes from 'prop-types';
import DOMPurify from 'dompurify';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';

// DOMPurify-Konfiguration: Sichere Tags für E-Mail-Vorschau
const DOMPURIFY_CONFIG = {
	ALLOWED_TAGS: [ 'p', 'br', 'strong', 'em', 'b', 'i', 'a', 'ul', 'ol', 'li', 'div', 'span' ],
	ALLOWED_ATTR: [ 'href', 'target', 'rel', 'style' ],
};

/**
 * Prüft ob der Text bereits HTML enthält
 *
 * @param {string} text Text
 * @return {boolean} True wenn HTML-Tags enthalten sind
 */
function isHtml( text ) {
	if ( ! text ) {
		return false;
	}
	return /<[a-z][\s\S]*>/i.test( text );
}

/**
 * EmailPreview Komponente
 *
 * @param {Object} props           Props
 * @param {string} props.subject   Betreff
 * @param {string} props.body      Inhalt (kann HTML oder Plain Text sein)
 * @param {string} props.recipient Empfänger (optional)
 * @return {JSX.Element} Komponente
 */
export function EmailPreview( { subject = '', body = '', recipient = '' } ) {
	const i18n = window.rpEmailData?.i18n || {};

	/**
	 * Body-Content aufbereiten
	 * - Wenn bereits HTML: Nur sanitizen
	 * - Wenn Plain Text: In HTML konvertieren
	 *
	 * @param {string} text Text
	 * @return {string} Sanitized HTML
	 */
	const prepareBody = ( text ) => {
		if ( ! text ) {
			return '';
		}

		// Wenn bereits HTML-Tags vorhanden sind, nur sanitizen
		if ( isHtml( text ) ) {
			return DOMPurify.sanitize( text, DOMPURIFY_CONFIG );
		}

		// Plain Text: Markdown-ähnliche Formatierung + Zeilenumbrüche
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
					<CardTitle>{ i18n.preview || 'Vorschau' }</CardTitle>
				</CardHeader>
				<CardContent>
					<div
						className="rp-email-preview__envelope"
						style={ {
							marginBottom: '1rem',
							paddingBottom: '1rem',
							borderBottom: '1px solid #e5e7eb',
						} }
					>
						{ recipient && (
							<div
								className="rp-email-preview__to"
								style={ {
									display: 'flex',
									gap: '0.5rem',
									marginBottom: '0.5rem',
								} }
							>
								<span
									className="rp-email-preview__label"
									style={ {
										fontWeight: 500,
										color: '#6b7280',
									} }
								>
									{ i18n.to || 'An' }:
								</span>
								<span className="rp-email-preview__value">{ recipient }</span>
							</div>
						) }
						<div
							className="rp-email-preview__subject"
							style={ {
								display: 'flex',
								gap: '0.5rem',
							} }
						>
							<span
								className="rp-email-preview__label"
								style={ {
									fontWeight: 500,
									color: '#6b7280',
								} }
							>
								{ i18n.subject || 'Betreff' }:
							</span>
							<span className="rp-email-preview__value">
								{ subject || (
									<em
										className="rp-email-preview__placeholder"
										style={ { color: '#9ca3af' } }
									>
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
								style={ {
									lineHeight: 1.6,
									color: '#374151',
								} }
								dangerouslySetInnerHTML={ { __html: prepareBody( body ) } }
							/>
						) : (
							<p
								className="rp-email-preview__placeholder"
								style={ { color: '#9ca3af', fontStyle: 'italic' } }
							>
								{ i18n.noContent || '(Kein Inhalt)' }
							</p>
						) }
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

EmailPreview.propTypes = {
	subject: PropTypes.string,
	body: PropTypes.string,
	recipient: PropTypes.string,
};
