/**
 * Notes Panel Component
 *
 * Panel zur Anzeige und Verwaltung von Notizen - shadcn/ui Style
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Plus, Edit2, Trash2, Lock, X } from 'lucide-react';
import { Button } from '../components/ui/button';
import { useNotes } from './hooks/useNotes';

/**
 * Spinner Component
 */
function Spinner( { size = '1rem' } ) {
	return (
		<div
			style={ {
				width: size,
				height: size,
				border: '2px solid #e5e7eb',
				borderTopColor: '#1d71b8',
				borderRadius: '50%',
				animation: 'spin 0.8s linear infinite',
			} }
		/>
	);
}

/**
 * Format relative time
 */
function formatRelativeTime( dateString ) {
	const date = new Date( dateString );
	const now = new Date();
	const diff = now - date;
	const minutes = Math.floor( diff / 60000 );
	const hours = Math.floor( diff / 3600000 );
	const days = Math.floor( diff / 86400000 );

	if ( minutes < 1 ) return __( 'Gerade eben', 'recruiting-playbook' );
	if ( minutes < 60 ) return `vor ${ minutes } ${ minutes === 1 ? 'Minute' : 'Minuten' }`;
	if ( hours < 24 ) return `vor ${ hours } ${ hours === 1 ? 'Stunde' : 'Stunden' }`;
	if ( days < 7 ) return `vor ${ days } ${ days === 1 ? 'Tag' : 'Tagen' }`;

	return date.toLocaleDateString( 'de-DE', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
	} );
}

/**
 * Note Editor Component
 */
function NoteEditor( { initialContent = '', onSave, onCancel, saving, saveLabel, showPrivate = true } ) {
	const [ content, setContent ] = useState( initialContent );
	const [ isPrivate, setIsPrivate ] = useState( false );
	const textareaRef = useRef( null );

	useEffect( () => {
		if ( textareaRef.current ) {
			textareaRef.current.focus();
		}
	}, [] );

	const handleSave = () => {
		if ( ! content.trim() || saving ) return;
		onSave( content, isPrivate );
	};

	const handleKeyDown = ( e ) => {
		if ( ( e.metaKey || e.ctrlKey ) && e.key === 'Enter' ) {
			e.preventDefault();
			handleSave();
		}
		if ( e.key === 'Escape' && onCancel ) {
			e.preventDefault();
			onCancel();
		}
	};

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '0.75rem' } }>
			<textarea
				ref={ textareaRef }
				value={ content }
				onChange={ ( e ) => setContent( e.target.value ) }
				onKeyDown={ handleKeyDown }
				placeholder={ __( 'Notiz eingeben...', 'recruiting-playbook' ) }
				rows={ 4 }
				disabled={ saving }
				style={ {
					width: '100%',
					padding: '0.75rem',
					border: '1px solid #e5e7eb',
					borderRadius: '0.375rem',
					fontSize: '0.875rem',
					resize: 'vertical',
					fontFamily: 'inherit',
				} }
			/>

			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.75rem' } }>
				{ showPrivate && (
					<label style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.875rem', color: '#6b7280', cursor: 'pointer' } }>
						<input
							type="checkbox"
							checked={ isPrivate }
							onChange={ ( e ) => setIsPrivate( e.target.checked ) }
							disabled={ saving }
						/>
						<Lock style={ { width: '0.875rem', height: '0.875rem' } } />
						{ __( 'Nur für mich sichtbar', 'recruiting-playbook' ) }
					</label>
				) }

				<div style={ { display: 'flex', gap: '0.5rem', marginLeft: 'auto' } }>
					{ onCancel && (
						<Button variant="outline" onClick={ onCancel } disabled={ saving }>
							{ __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
					) }
					<Button onClick={ handleSave } disabled={ ! content.trim() || saving }>
						{ saving ? (
							<>
								<Spinner size="0.875rem" />
								{ __( 'Speichern...', 'recruiting-playbook' ) }
							</>
						) : (
							saveLabel || __( 'Speichern', 'recruiting-playbook' )
						) }
					</Button>
				</div>
			</div>

			<div style={ { fontSize: '0.75rem', color: '#9ca3af' } }>
				<kbd style={ { padding: '0.125rem 0.375rem', backgroundColor: '#f3f4f6', border: '1px solid #e5e7eb', borderRadius: '0.25rem', fontSize: '0.6875rem' } }>Ctrl</kbd>
				{ ' + ' }
				<kbd style={ { padding: '0.125rem 0.375rem', backgroundColor: '#f3f4f6', border: '1px solid #e5e7eb', borderRadius: '0.25rem', fontSize: '0.6875rem' } }>Enter</kbd>
				{ ' ' }{ __( 'zum Speichern', 'recruiting-playbook' ) }
			</div>
		</div>
	);
}

/**
 * Single Note Item
 */
function NoteItem( { note, onUpdate, onDelete, saving } ) {
	const [ isEditing, setIsEditing ] = useState( false );
	const [ showDeleteConfirm, setShowDeleteConfirm ] = useState( false );

	const handleSave = async ( content ) => {
		const success = await onUpdate( note.id, content );
		if ( success ) setIsEditing( false );
	};

	const handleDelete = async () => {
		await onDelete( note.id );
		setShowDeleteConfirm( false );
	};

	if ( isEditing ) {
		return (
			<div
				style={ {
					padding: '1rem',
					backgroundColor: '#fff',
					border: '1px solid #1d71b8',
					borderRadius: '0.375rem',
				} }
			>
				<NoteEditor
					initialContent={ note.content }
					onSave={ handleSave }
					onCancel={ () => setIsEditing( false ) }
					saving={ saving }
					showPrivate={ false }
					saveLabel={ __( 'Änderungen speichern', 'recruiting-playbook' ) }
				/>
			</div>
		);
	}

	return (
		<div
			style={ {
				padding: '1rem',
				backgroundColor: note.is_private ? '#fef3c7' : '#f9fafb',
				borderLeft: `3px solid ${ note.is_private ? '#f59e0b' : '#1d71b8' }`,
				borderRadius: '0.375rem',
			} }
		>
			{ /* Header */ }
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
				{ note.author?.avatar && (
					<img
						src={ note.author.avatar }
						alt={ note.author.name }
						style={ { width: '1.5rem', height: '1.5rem', borderRadius: '50%' } }
					/>
				) }
				<span style={ { fontWeight: 600, fontSize: '0.875rem', color: '#1f2937' } }>
					{ note.author?.name || __( 'Unbekannt', 'recruiting-playbook' ) }
				</span>
				{ note.is_private && (
					<span
						style={ {
							display: 'inline-flex',
							alignItems: 'center',
							gap: '0.25rem',
							padding: '0.125rem 0.5rem',
							backgroundColor: '#f59e0b',
							color: '#fff',
							borderRadius: '0.25rem',
							fontSize: '0.6875rem',
							fontWeight: 500,
						} }
					>
						<Lock style={ { width: '0.625rem', height: '0.625rem' } } />
						{ __( 'Privat', 'recruiting-playbook' ) }
					</span>
				) }
				<span style={ { marginLeft: 'auto', fontSize: '0.75rem', color: '#6b7280' } }>
					{ formatRelativeTime( note.created_at ) }
					{ note.updated_at !== note.created_at && (
						<span style={ { marginLeft: '0.25rem' } }>({ __( 'bearbeitet', 'recruiting-playbook' ) })</span>
					) }
				</span>
			</div>

			{ /* Content */ }
			<div
				style={ { fontSize: '0.875rem', color: '#374151', lineHeight: 1.6 } }
				dangerouslySetInnerHTML={ { __html: note.content } }
			/>

			{ /* Actions */ }
			{ ( note.can_edit || note.can_delete ) && (
				<div style={ { display: 'flex', gap: '0.75rem', marginTop: '0.75rem', paddingTop: '0.75rem', borderTop: '1px solid rgba(0,0,0,0.05)' } }>
					{ note.can_edit && (
						<button
							type="button"
							onClick={ () => setIsEditing( true ) }
							disabled={ saving }
							style={ {
								display: 'inline-flex',
								alignItems: 'center',
								gap: '0.25rem',
								background: 'none',
								border: 'none',
								padding: 0,
								color: '#1d71b8',
								fontSize: '0.75rem',
								cursor: 'pointer',
							} }
						>
							<Edit2 style={ { width: '0.75rem', height: '0.75rem' } } />
							{ __( 'Bearbeiten', 'recruiting-playbook' ) }
						</button>
					) }
					{ note.can_delete && (
						<>
							{ showDeleteConfirm ? (
								<span style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.75rem' } }>
									<span>{ __( 'Wirklich löschen?', 'recruiting-playbook' ) }</span>
									<button
										type="button"
										onClick={ handleDelete }
										disabled={ saving }
										style={ { background: 'none', border: 'none', padding: 0, color: '#d63638', cursor: 'pointer', fontWeight: 600 } }
									>
										{ __( 'Ja', 'recruiting-playbook' ) }
									</button>
									<button
										type="button"
										onClick={ () => setShowDeleteConfirm( false ) }
										disabled={ saving }
										style={ { background: 'none', border: 'none', padding: 0, color: '#6b7280', cursor: 'pointer' } }
									>
										{ __( 'Nein', 'recruiting-playbook' ) }
									</button>
								</span>
							) : (
								<button
									type="button"
									onClick={ () => setShowDeleteConfirm( true ) }
									disabled={ saving }
									style={ {
										display: 'inline-flex',
										alignItems: 'center',
										gap: '0.25rem',
										background: 'none',
										border: 'none',
										padding: 0,
										color: '#d63638',
										fontSize: '0.75rem',
										cursor: 'pointer',
									} }
								>
									<Trash2 style={ { width: '0.75rem', height: '0.75rem' } } />
									{ __( 'Löschen', 'recruiting-playbook' ) }
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
 * Notes Panel Component
 */
export function NotesPanel( { applicationId, showHeader = true } ) {
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

	useEffect( () => {
		fetchNotes();
	}, [ fetchNotes ] );

	const handleCreateNote = async ( content, isPrivate ) => {
		const newNote = await createNote( content, isPrivate );
		if ( newNote ) setShowNewNote( false );
	};

	if ( loading ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '2rem', gap: '0.5rem', color: '#6b7280' } }>
				<Spinner />
				{ __( 'Laden...', 'recruiting-playbook' ) }
			</div>
		);
	}

	return (
		<div>
			{ /* Header */ }
			{ showHeader ? (
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' } }>
					<h3 style={ { margin: 0, fontSize: '1rem', fontWeight: 600, color: '#1f2937', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ __( 'Notizen', 'recruiting-playbook' ) }
						{ notes.length > 0 && (
							<span
								style={ {
									backgroundColor: '#e5e7eb',
									color: '#6b7280',
									padding: '0.125rem 0.5rem',
									borderRadius: '9999px',
									fontSize: '0.75rem',
									fontWeight: 500,
								} }
							>
								{ notes.length }
							</span>
						) }
					</h3>
					{ ! showNewNote && (
						<Button size="sm" onClick={ () => setShowNewNote( true ) }>
							<Plus style={ { width: '1rem', height: '1rem' } } />
							{ __( 'Notiz hinzufügen', 'recruiting-playbook' ) }
						</Button>
					) }
				</div>
			) : (
				! showNewNote && (
					<div style={ { display: 'flex', justifyContent: 'flex-end', marginBottom: '1rem' } }>
						<Button size="sm" onClick={ () => setShowNewNote( true ) }>
							<Plus style={ { width: '1rem', height: '1rem' } } />
							{ __( 'Notiz hinzufügen', 'recruiting-playbook' ) }
						</Button>
					</div>
				)
			) }

			{ /* Error */ }
			{ error && (
				<div style={ { padding: '0.75rem', backgroundColor: '#fee2e2', color: '#dc2626', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' } }>
					{ error }
				</div>
			) }

			{ /* New Note Form */ }
			{ showNewNote && (
				<div style={ { marginBottom: '1.5rem', paddingBottom: '1.5rem', borderBottom: '1px solid #e5e7eb' } }>
					<NoteEditor
						onSave={ handleCreateNote }
						onCancel={ () => setShowNewNote( false ) }
						saving={ saving }
						saveLabel={ __( 'Notiz hinzufügen', 'recruiting-playbook' ) }
					/>
				</div>
			) }

			{ /* Notes List */ }
			<div style={ { display: 'flex', flexDirection: 'column', gap: '0.75rem' } }>
				{ notes.length === 0 ? (
					<div style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }>
						<Edit2 style={ { width: '2.5rem', height: '2.5rem', marginBottom: '0.75rem', opacity: 0.3 } } />
						<p style={ { margin: '0 0 1rem 0' } }>{ __( 'Noch keine Notizen vorhanden.', 'recruiting-playbook' ) }</p>
						{ ! showNewNote && (
							<Button variant="outline" onClick={ () => setShowNewNote( true ) }>
								{ __( 'Erste Notiz hinzufügen', 'recruiting-playbook' ) }
							</Button>
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

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</div>
	);
}
