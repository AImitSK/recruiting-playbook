/**
 * Notes Panel Component
 *
 * Panel zur Anzeige und Verwaltung von Notizen
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect } from '@wordpress/element';
import { NoteEditor } from './NoteEditor';
import { useNotes } from './hooks/useNotes';

/**
 * Einzelne Notiz-Komponente
 *
 * @param {Object}   props          Props
 * @param {Object}   props.note     Notiz-Daten
 * @param {Function} props.onUpdate Update-Callback
 * @param {Function} props.onDelete Delete-Callback
 * @param {boolean}  props.saving   Speichervorgang aktiv?
 * @return {JSX.Element} Notiz-Komponente
 */
function NoteItem( { note, onUpdate, onDelete, saving } ) {
	const [ isEditing, setIsEditing ] = useState( false );
	const [ showDeleteConfirm, setShowDeleteConfirm ] = useState( false );

	const i18n = window.rpApplicant?.i18n || {};

	/**
	 * Bearbeitung speichern
	 *
	 * @param {string} content Neuer Inhalt
	 */
	const handleSave = async ( content ) => {
		const success = await onUpdate( note.id, content );
		if ( success ) {
			setIsEditing( false );
		}
	};

	/**
	 * Notiz löschen
	 */
	const handleDelete = async () => {
		await onDelete( note.id );
		setShowDeleteConfirm( false );
	};

	/**
	 * Datum formatieren
	 *
	 * @param {string} dateString ISO-Datum
	 * @return {string} Formatiertes Datum
	 */
	const formatDate = ( dateString ) => {
		const date = new Date( dateString );
		const now = new Date();
		const diff = now - date;
		const minutes = Math.floor( diff / 60000 );
		const hours = Math.floor( diff / 3600000 );
		const days = Math.floor( diff / 86400000 );

		if ( minutes < 1 ) {
			return i18n.justNow || 'Gerade eben';
		}
		if ( minutes < 60 ) {
			return `${ i18n.beforeMinutes || 'vor' } ${ minutes } ${ minutes === 1 ? ( i18n.minute || 'Minute' ) : ( i18n.minutes || 'Minuten' ) }`;
		}
		if ( hours < 24 ) {
			return `${ i18n.beforeHours || 'vor' } ${ hours } ${ hours === 1 ? ( i18n.hour || 'Stunde' ) : ( i18n.hours || 'Stunden' ) }`;
		}
		if ( days < 7 ) {
			return `${ i18n.beforeDays || 'vor' } ${ days } ${ days === 1 ? ( i18n.day || 'Tag' ) : ( i18n.days || 'Tagen' ) }`;
		}

		return date.toLocaleDateString( 'de-DE', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		} );
	};

	if ( isEditing ) {
		return (
			<div className="rp-note rp-note--editing">
				<NoteEditor
					initialContent={ note.content }
					onSave={ handleSave }
					onCancel={ () => setIsEditing( false ) }
					saving={ saving }
					showPrivate={ false }
					saveLabel={ i18n.saveChanges || 'Änderungen speichern' }
				/>
			</div>
		);
	}

	return (
		<div className={ `rp-note${ note.is_private ? ' rp-note--private' : '' }` }>
			<div className="rp-note__header">
				<div className="rp-note__author">
					{ note.author?.avatar && (
						<img
							src={ note.author.avatar }
							alt={ note.author.name }
							className="rp-note__avatar"
						/>
					) }
					<span className="rp-note__author-name">
						{ note.author?.name || i18n.unknownUser || 'Unbekannt' }
					</span>
					{ note.is_private && (
						<span className="rp-note__private-badge" title={ i18n.privateNoteHint || 'Nur für Sie sichtbar' }>
							<span className="dashicons dashicons-lock"></span>
							{ i18n.private || 'Privat' }
						</span>
					) }
				</div>
				<div className="rp-note__meta">
					<time
						className="rp-note__date"
						dateTime={ note.created_at }
						title={ new Date( note.created_at ).toLocaleString( 'de-DE' ) }
					>
						{ formatDate( note.created_at ) }
					</time>
					{ note.updated_at !== note.created_at && (
						<span className="rp-note__edited" title={ new Date( note.updated_at ).toLocaleString( 'de-DE' ) }>
							({ i18n.edited || 'bearbeitet' })
						</span>
					) }
				</div>
			</div>

			<div
				className="rp-note__content"
				dangerouslySetInnerHTML={ { __html: note.content } }
			/>

			{ ( note.can_edit || note.can_delete ) && (
				<div className="rp-note__actions">
					{ note.can_edit && (
						<button
							type="button"
							className="button-link"
							onClick={ () => setIsEditing( true ) }
							disabled={ saving }
						>
							<span className="dashicons dashicons-edit"></span>
							{ i18n.edit || 'Bearbeiten' }
						</button>
					) }
					{ note.can_delete && (
						<>
							{ showDeleteConfirm ? (
								<span className="rp-note__delete-confirm">
									<span>{ i18n.confirmDelete || 'Wirklich löschen?' }</span>
									<button
										type="button"
										className="button-link button-link-delete"
										onClick={ handleDelete }
										disabled={ saving }
									>
										{ i18n.yes || 'Ja' }
									</button>
									<button
										type="button"
										className="button-link"
										onClick={ () => setShowDeleteConfirm( false ) }
										disabled={ saving }
									>
										{ i18n.no || 'Nein' }
									</button>
								</span>
							) : (
								<button
									type="button"
									className="button-link button-link-delete"
									onClick={ () => setShowDeleteConfirm( true ) }
									disabled={ saving }
								>
									<span className="dashicons dashicons-trash"></span>
									{ i18n.delete || 'Löschen' }
								</button>
							) }
						</>
					) }
				</div>
			) }
		</div>
	);
}

/**
 * Notizen-Panel Komponente
 *
 * @param {Object} props               Props
 * @param {number} props.applicationId Bewerbungs-ID
 * @return {JSX.Element} Panel-Komponente
 */
export function NotesPanel( { applicationId } ) {
	const [ showNewNote, setShowNewNote ] = useState( false );
	const {
		notes,
		loading,
		error,
		saving,
		fetchNotes,
		createNote,
		updateNote,
		deleteNote,
	} = useNotes( applicationId );

	const i18n = window.rpApplicant?.i18n || {};

	// Notizen beim Laden der Komponente abrufen
	useEffect( () => {
		fetchNotes();
	}, [ fetchNotes ] );

	/**
	 * Neue Notiz speichern
	 *
	 * @param {string}  content   Notiz-Inhalt
	 * @param {boolean} isPrivate Private Notiz?
	 */
	const handleCreateNote = async ( content, isPrivate ) => {
		const newNote = await createNote( content, isPrivate );
		if ( newNote ) {
			setShowNewNote( false );
		}
	};

	if ( loading ) {
		return (
			<div className="rp-notes-panel rp-notes-panel--loading">
				<div className="rp-notes-panel__header">
					<h3>{ i18n.notes || 'Notizen' }</h3>
				</div>
				<div className="rp-notes-panel__loading">
					<span className="spinner is-active"></span>
					{ i18n.loading || 'Laden...' }
				</div>
			</div>
		);
	}

	return (
		<div className="rp-notes-panel">
			<div className="rp-notes-panel__header">
				<h3>
					{ i18n.notes || 'Notizen' }
					{ notes.length > 0 && (
						<span className="rp-notes-panel__count">{ notes.length }</span>
					) }
				</h3>
				{ ! showNewNote && (
					<button
						type="button"
						className="button button-primary"
						onClick={ () => setShowNewNote( true ) }
					>
						<span className="dashicons dashicons-plus-alt2"></span>
						{ i18n.addNote || 'Notiz hinzufügen' }
					</button>
				) }
			</div>

			{ error && (
				<div className="notice notice-error">
					<p>{ error }</p>
				</div>
			) }

			{ showNewNote && (
				<div className="rp-notes-panel__new">
					<NoteEditor
						onSave={ handleCreateNote }
						onCancel={ () => setShowNewNote( false ) }
						saving={ saving }
						saveLabel={ i18n.addNote || 'Notiz hinzufügen' }
					/>
				</div>
			) }

			<div className="rp-notes-panel__list">
				{ notes.length === 0 ? (
					<div className="rp-notes-panel__empty">
						<span className="dashicons dashicons-edit"></span>
						<p>{ i18n.noNotes || 'Noch keine Notizen vorhanden.' }</p>
						{ ! showNewNote && (
							<button
								type="button"
								className="button"
								onClick={ () => setShowNewNote( true ) }
							>
								{ i18n.addFirstNote || 'Erste Notiz hinzufügen' }
							</button>
						) }
					</div>
				) : (
					notes.map( ( note ) => (
						<NoteItem
							key={ note.id }
							note={ note }
							onUpdate={ updateNote }
							onDelete={ deleteNote }
							saving={ saving }
						/>
					) )
				) }
			</div>
		</div>
	);
}
