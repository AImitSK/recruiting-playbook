/**
 * Custom Hook für Rollen- und Zuweisungs-Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden und Verwalten der Rollen und Capabilities
 *
 * @return {Object} Roles state und Funktionen
 */
export function useRoles() {
	const [ roles, setRoles ] = useState( [] );
	const [ capabilityGroups, setCapabilityGroups ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	const isMountedRef = useRef( true );

	/**
	 * Rollen und Capability-Gruppen laden
	 */
	const fetchRoles = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const [ rolesData, capsData ] = await Promise.all( [
				apiFetch( { path: '/recruiting/v1/roles' } ),
				apiFetch( { path: '/recruiting/v1/roles/capabilities' } ),
			] );

			if ( isMountedRef.current ) {
				const rolesArr = rolesData?.roles;
				setRoles( Array.isArray( rolesArr ) ? rolesArr : [] );

				const groupsArr = capsData?.groups;
				setCapabilityGroups( Array.isArray( groupsArr ) ? groupsArr : [] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Laden der Rollen' );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

	useEffect( () => {
		isMountedRef.current = true;
		fetchRoles();

		return () => {
			isMountedRef.current = false;
		};
	}, [ fetchRoles ] );

	/**
	 * Capabilities einer Rolle speichern
	 *
	 * @param {string} slug         Rollen-Slug
	 * @param {Object} capabilities Capabilities-Map
	 * @return {boolean} Erfolg
	 */
	const saveRoleCapabilities = useCallback( async ( slug, capabilities ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/roles/${ slug }`,
				method: 'PUT',
				data: { capabilities },
			} );

			if ( isMountedRef.current ) {
				setRoles( ( prev ) =>
					prev.map( ( role ) =>
						role.slug === slug
							? { ...role, capabilities: result.capabilities }
							: role
					)
				);
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Speichern' );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	return {
		roles,
		capabilityGroups,
		loading,
		saving,
		error,
		setError,
		saveRoleCapabilities,
		refetch: fetchRoles,
	};
}

/**
 * Hook zum Verwalten der Stellen-Zuweisungen
 *
 * @return {Object} Assignments state und Funktionen
 */
export function useJobAssignments() {
	const [ users, setUsers ] = useState( [] );
	const [ selectedUser, setSelectedUser ] = useState( null );
	const [ assignedJobs, setAssignedJobs ] = useState( [] );
	const [ allJobs, setAllJobs ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ assigning, setAssigning ] = useState( false );
	const [ error, setError ] = useState( null );

	const isMountedRef = useRef( true );

	/**
	 * Recruiting-User und alle Jobs laden
	 */
	const fetchInitialData = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const config = window.rpSettingsData || {};
			const rawUsers = config.recruitingUsers;
			const rawJobs = config.jobListings;

			if ( isMountedRef.current ) {
				setUsers( Array.isArray( rawUsers ) ? rawUsers : [] );
				setAllJobs( Array.isArray( rawJobs ) ? rawJobs : [] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Laden' );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

	useEffect( () => {
		isMountedRef.current = true;
		fetchInitialData();

		return () => {
			isMountedRef.current = false;
		};
	}, [ fetchInitialData ] );

	/**
	 * Zuweisungen eines Users laden
	 *
	 * @param {number} userId User-ID
	 */
	const fetchUserAssignments = useCallback( async ( userId ) => {
		try {
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/job-assignments/user/${ userId }`,
			} );

			if ( isMountedRef.current ) {
				const jobs = data?.jobs;
				setAssignedJobs( Array.isArray( jobs ) ? jobs : [] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Laden der Zuweisungen' );
				setAssignedJobs( [] );
			}
		}
	}, [] );

	/**
	 * User auswählen und Zuweisungen laden
	 *
	 * @param {number} userId User-ID
	 */
	const selectUser = useCallback( ( userId ) => {
		const user = users.find( ( u ) => u.id === userId );
		setSelectedUser( user || null );

		if ( userId ) {
			fetchUserAssignments( userId );
		} else {
			setAssignedJobs( [] );
		}
	}, [ users, fetchUserAssignments ] );

	/**
	 * Job einem User zuweisen
	 *
	 * @param {number} jobId Job-ID
	 * @return {boolean} Erfolg
	 */
	const assignJob = useCallback( async ( jobId ) => {
		if ( ! selectedUser ) {
			return false;
		}

		try {
			setAssigning( true );
			setError( null );

			await apiFetch( {
				path: '/recruiting/v1/job-assignments',
				method: 'POST',
				data: {
					user_id: selectedUser.id,
					job_id: jobId,
				},
			} );

			if ( isMountedRef.current ) {
				await fetchUserAssignments( selectedUser.id );
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Zuweisen' );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setAssigning( false );
			}
		}
	}, [ selectedUser, fetchUserAssignments ] );

	/**
	 * Job-Zuweisung entfernen
	 *
	 * @param {number} jobId Job-ID
	 * @return {boolean} Erfolg
	 */
	const unassignJob = useCallback( async ( jobId ) => {
		if ( ! selectedUser ) {
			return false;
		}

		try {
			setAssigning( true );
			setError( null );

			await apiFetch( {
				path: '/recruiting/v1/job-assignments',
				method: 'DELETE',
				data: {
					user_id: selectedUser.id,
					job_id: jobId,
				},
			} );

			if ( isMountedRef.current ) {
				setAssignedJobs( ( prev ) =>
					prev.filter( ( job ) => job.id !== jobId )
				);
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Entfernen' );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setAssigning( false );
			}
		}
	}, [ selectedUser ] );

	/**
	 * Alle Jobs zuweisen
	 *
	 * @return {boolean} Erfolg
	 */
	const assignAllJobs = useCallback( async () => {
		if ( ! selectedUser ) {
			return false;
		}

		const assignedIds = assignedJobs.map( ( j ) => j.id );
		const unassignedJobIds = allJobs
			.filter( ( j ) => ! assignedIds.includes( j.id ) )
			.map( ( j ) => j.id );

		if ( unassignedJobIds.length === 0 ) {
			return true;
		}

		try {
			setAssigning( true );
			setError( null );

			await apiFetch( {
				path: '/recruiting/v1/job-assignments/bulk',
				method: 'POST',
				data: {
					user_id: selectedUser.id,
					job_ids: unassignedJobIds,
				},
			} );

			if ( isMountedRef.current ) {
				await fetchUserAssignments( selectedUser.id );
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler bei der Bulk-Zuweisung' );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setAssigning( false );
			}
		}
	}, [ selectedUser, assignedJobs, allJobs, fetchUserAssignments ] );

	return {
		users,
		selectedUser,
		assignedJobs,
		allJobs,
		loading,
		assigning,
		error,
		setError,
		selectUser,
		assignJob,
		unassignJob,
		assignAllJobs,
	};
}
