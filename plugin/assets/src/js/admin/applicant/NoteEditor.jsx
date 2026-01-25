/**
 * Note Editor Component
 *
 * Rich-Text-Editor für Notizen mit Formatierungsoptionen
 *
 * @package RecruitingPlaybook
 */

import { useState, useRef, useEffect } from '@wordpress/element';

/**
 * Notiz-Editor Komponente
 *
 * @param {Object}   props             Props
 * @param {string}   props.initialContent Initialer Inhalt
 * @param {boolean}  props.initialPrivate Initial privat?
 * @param {Function} props.onSave      Callback beim Speichern
 * @param {Function} props.onCancel    Callback beim Abbrechen
 * @param {boolean}  props.saving      Speichervorgang aktiv?
 * @param {boolean}  props.showPrivate Private-Toggle anzeigen?
 * @param {string}   props.saveLabel   Label für Speichern-Button
 * @return {JSX.Element} Editor-Komponente
 */
export function NoteEditor( {
	initialContent = '',
	initialPrivate = false,
	onSave,
	onCancel,
	saving = false,
	showPrivate = true,
	saveLabel,
} ) {
	const [ content, setContent ] = useState( initialContent );
	const [ isPrivate, setIsPrivate ] = useState( initialPrivate );
	const textareaRef = useRef( null );

	const i18n = window.rpApplicant?.i18n || {};

	// Fokus auf Textarea setzen
	useEffect( () => {
		if ( textareaRef.current ) {
			textareaRef.current.focus();
		}
	}, [] );

	/**
	 * Speichern-Handler
	 */
	const handleSave = () => {
		if ( ! content.trim() || saving ) {
			return;
		}
		onSave( content, isPrivate );
	};

	/**
	 * Keyboard-Handler für Tastenkombinationen
	 *
	 * @param {KeyboardEvent} e Keyboard-Event
	 */
	const handleKeyDown = ( e ) => {
		// Cmd/Ctrl + Enter zum Speichern
		if ( ( e.metaKey || e.ctrlKey ) && e.key === 'Enter' ) {
			e.preventDefault();
			handleSave();
		}
		// Escape zum Abbrechen
		if ( e.key === 'Escape' && onCancel ) {
			e.preventDefault();
			onCancel();
		}
	};

	const canSave = content.trim().length > 0 && ! saving;

	return (
		<div className="rp-note-editor">
			<textarea
				ref={ textareaRef }
				className="rp-note-editor__textarea"
				value={ content }
				onChange={ ( e ) => setContent( e.target.value ) }
				onKeyDown={ handleKeyDown }
				placeholder={ i18n.notePlaceholder || 'Notiz eingeben...' }
				rows={ 4 }
				disabled={ saving }
			/>

			<div className="rp-note-editor__footer">
				<div className="rp-note-editor__options">
					{ showPrivate && (
						<label className="rp-note-editor__private">
							<input
								type="checkbox"
								checked={ isPrivate }
								onChange={ ( e ) => setIsPrivate( e.target.checked ) }
								disabled={ saving }
							/>
							<span className="dashicons dashicons-lock"></span>
							{ i18n.privateNote || 'Nur für mich sichtbar' }
						</label>
					) }
				</div>

				<div className="rp-note-editor__actions">
					{ onCancel && (
						<button
							type="button"
							className="button"
							onClick={ onCancel }
							disabled={ saving }
						>
							{ i18n.cancel || 'Abbrechen' }
						</button>
					) }
					<button
						type="button"
						className="button button-primary"
						onClick={ handleSave }
						disabled={ ! canSave }
					>
						{ saving ? (
							<>
								<span className="spinner is-active"></span>
								{ i18n.saving || 'Speichern...' }
							</>
						) : (
							saveLabel || i18n.save || 'Speichern'
						) }
					</button>
				</div>
			</div>

			<div className="rp-note-editor__hint">
				<kbd>Ctrl</kbd> + <kbd>Enter</kbd> { i18n.toSave || 'zum Speichern' }
			</div>
		</div>
	);
}
