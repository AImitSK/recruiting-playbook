/**
 * Custom Hook für Notizen
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden und Verwalten von Notizen
 *
 * @param {number} applicationId Bewerbungs-ID
 * @return {Object} Notes state und Funktionen
 */
export function useNotes( applicationId ) {
	const [ notes, setNotes ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	/**
	 * Notizen vom Server laden
	 */
	const fetchNotes = useCallback( async () => {
		if ( ! applicationId ) {
			setLoading( false );
			return;
		}

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }/notes`,
			} );

			setNotes( Array.isArray( data ) ? data : [] );
		} catch ( err ) {
			console.error( 'Error fetching notes:', err );
			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorLoadingNotes ||
				__( 'Error loading notes', 'recruiting-playbook' )
			);
		} finally {
			setLoading( false );
		}
	}, [ applicationId ] );

	/**
	 * Neue Notiz erstellen
	 *
	 * @param {string}  content   Notiz-Inhalt
	 * @param {boolean} isPrivate Private Notiz?
	 * @return {Object|null} Erstellte Notiz oder null bei Fehler
	 */
	const createNote = useCallback(
		async ( content, isPrivate = false ) => {
			if ( ! applicationId || ! content.trim() ) {
				return null;
			}

			try {
				setSaving( true );
				setError( null );

				const newNote = await apiFetch( {
					path: `/recruiting/v1/applications/${ applicationId }/notes`,
					method: 'POST',
					data: {
						content,
						is_private: isPrivate,
					},
				} );

				// Optimistic Update: Notiz zur Liste hinzufügen
				setNotes( ( prev ) => [ newNote, ...prev ] );

				return newNote;
			} catch ( err ) {
				console.error( 'Error creating note:', err );
				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorCreatingNote ||
					__( 'Error creating note', 'recruiting-playbook' )
				);
				return null;
			} finally {
				setSaving( false );
			}
		},
		[ applicationId ]
	);

	/**
	 * Notiz aktualisieren
	 *
	 * @param {number} noteId  Notiz-ID
	 * @param {string} content Neuer Inhalt
	 * @return {Object|null} Aktualisierte Notiz oder null bei Fehler
	 */
	const updateNote = useCallback( async ( noteId, content ) => {
		if ( ! noteId || ! content.trim() ) {
			return null;
		}

		try {
			setSaving( true );
			setError( null );

			const updatedNote = await apiFetch( {
				path: `/recruiting/v1/notes/${ noteId }`,
				method: 'PATCH',
				data: { content },
			} );

			// Optimistic Update: Notiz in Liste aktualisieren
			setNotes( ( prev ) =>
				prev.map( ( note ) =>
					note.id === noteId ? updatedNote : note
				)
			);

			return updatedNote;
		} catch ( err ) {
			console.error( 'Error updating note:', err );
			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorUpdatingNote ||
				__( 'Error updating note', 'recruiting-playbook' )
			);
			return null;
		} finally {
			setSaving( false );
		}
	}, [] );

	/**
	 * Notiz löschen
	 *
	 * @param {number} noteId Notiz-ID
	 * @return {boolean} Erfolg
	 */
	const deleteNote = useCallback( async ( noteId ) => {
		if ( ! noteId ) {
			return false;
		}

		// Vorherigen Zustand für Rollback speichern
		const previousNotes = [ ...notes ];

		try {
			setSaving( true );
			setError( null );

			// Optimistic Update: Notiz aus Liste entfernen
			setNotes( ( prev ) => prev.filter( ( note ) => note.id !== noteId ) );

			await apiFetch( {
				path: `/recruiting/v1/notes/${ noteId }`,
				method: 'DELETE',
			} );

			return true;
		} catch ( err ) {
			console.error( 'Error deleting note:', err );

			// Rollback bei Fehler
			setNotes( previousNotes );

			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorDeletingNote ||
				__( 'Error deleting note', 'recruiting-playbook' )
			);
			return false;
		} finally {
			setSaving( false );
		}
	}, [ notes ] );

	return {
		notes,
		loading,
		error,
		saving,
		fetchNotes,
		createNote,
		updateNote,
		deleteNote,
		refetch: fetchNotes,
	};
}
