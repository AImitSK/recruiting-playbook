/**
 * RolesList Component
 *
 * Capability-Matrix für Rollen-Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Switch } from '../../components/ui/switch';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { useRoles } from '../hooks/useRoles';
import { Shield, AlertCircle, Check } from 'lucide-react';

/**
 * Capability-Labels (Deutsch)
 */
const CAPABILITY_LABELS = {
	rp_manage_recruiting: 'Recruiting verwalten',
	rp_view_applications: 'Bewerbungen anzeigen',
	rp_edit_applications: 'Bewerbungen bearbeiten',
	rp_delete_applications: 'Bewerbungen löschen',
	rp_view_notes: 'Notizen lesen',
	rp_create_notes: 'Notizen erstellen',
	rp_edit_own_notes: 'Eigene Notizen bearbeiten',
	rp_edit_others_notes: 'Fremde Notizen bearbeiten',
	rp_delete_notes: 'Notizen löschen',
	rp_rate_applications: 'Bewerbungen bewerten',
	rp_manage_talent_pool: 'Talent-Pool verwalten',
	rp_view_email_templates: 'E-Mail-Templates lesen',
	rp_create_email_templates: 'E-Mail-Templates erstellen',
	rp_edit_email_templates: 'E-Mail-Templates bearbeiten',
	rp_send_emails: 'E-Mails senden',
	rp_view_email_log: 'E-Mail-Log einsehen',
	rp_view_activity: 'Aktivitäten einsehen',
	rp_export_data: 'Daten exportieren',
	rp_manage_roles: 'Rollen verwalten',
	rp_assign_jobs: 'Stellen zuweisen',
};

/**
 * RolesList Component
 *
 * @return {JSX.Element} Component
 */
export function RolesList() {
	const {
		roles,
		capabilityGroups,
		loading,
		saving,
		error,
		setError,
		saveRoleCapabilities,
	} = useRoles();

	const [ editedCaps, setEditedCaps ] = useState( {} );
	const [ notification, setNotification ] = useState( null );

	/**
	 * Capability Toggle
	 */
	const handleToggle = useCallback( ( roleSlug, cap, value ) => {
		setEditedCaps( ( prev ) => ( {
			...prev,
			[ roleSlug ]: {
				...( prev[ roleSlug ] || {} ),
				[ cap ]: value,
			},
		} ) );
	}, [] );

	/**
	 * Änderungen speichern
	 */
	const handleSave = useCallback( async ( roleSlug ) => {
		const role = roles.find( ( r ) => r.slug === roleSlug );
		if ( ! role ) {
			return;
		}

		const merged = {
			...role.capabilities,
			...( editedCaps[ roleSlug ] || {} ),
		};

		const success = await saveRoleCapabilities( roleSlug, merged );

		if ( success ) {
			setEditedCaps( ( prev ) => {
				const next = { ...prev };
				delete next[ roleSlug ];
				return next;
			} );
			setNotification( __( 'Berechtigungen gespeichert.', 'recruiting-playbook' ) );
			setTimeout( () => setNotification( null ), 3000 );
		}
	}, [ roles, editedCaps, saveRoleCapabilities ] );

	/**
	 * Aktuellen Wert einer Capability ermitteln
	 */
	const getCapValue = useCallback( ( role, cap ) => {
		if ( editedCaps[ role.slug ]?.hasOwnProperty( cap ) ) {
			return editedCaps[ role.slug ][ cap ];
		}
		return role.capabilities[ cap ] || false;
	}, [ editedCaps ] );

	/**
	 * Prüfen ob Rolle ungespeicherte Änderungen hat
	 */
	const hasChanges = useCallback( ( roleSlug ) => {
		return Object.keys( editedCaps[ roleSlug ] || {} ).length > 0;
	}, [ editedCaps ] );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	// Admin-only Caps die nicht bearbeitet werden können.
	const adminOnlyCaps = [ 'rp_manage_roles', 'rp_assign_jobs' ];

	// Editierbare Rollen (keine Standard-WP-Rollen).
	const editableRoles = roles.filter( ( r ) =>
		[ 'rp_recruiter', 'rp_hiring_manager' ].includes( r.slug )
	);

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
			{ error && (
				<Alert variant="destructive">
					<AlertCircle style={ { width: '1rem', height: '1rem' } } />
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			{ notification && (
				<Alert style={ { backgroundColor: '#e6f5ec', borderColor: '#2fac66' } }>
					<Check style={ { width: '1rem', height: '1rem', color: '#2fac66' } } />
					<AlertDescription>{ notification }</AlertDescription>
				</Alert>
			) }

			{ /* Capability-Matrix */ }
			{ capabilityGroups.map( ( group ) => (
				<Card key={ group.label }>
					<CardHeader>
						<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							<Shield style={ { width: '1.25rem', height: '1.25rem', color: '#1d71b8' } } />
							{ group.label }
						</CardTitle>
					</CardHeader>
					<CardContent>
						<table style={ { width: '100%', borderCollapse: 'collapse' } }>
							<thead>
								<tr style={ { borderBottom: '2px solid #e5e7eb' } }>
									<th style={ { textAlign: 'left', padding: '0.75rem 1rem', fontWeight: 600, color: '#374151' } }>
										{ __( 'Berechtigung', 'recruiting-playbook' ) }
									</th>
									<th style={ { textAlign: 'center', padding: '0.75rem 1rem', fontWeight: 600, color: '#374151', width: '100px' } }>
										{ __( 'Admin', 'recruiting-playbook' ) }
									</th>
									{ editableRoles.map( ( role ) => (
										<th
											key={ role.slug }
											style={ { textAlign: 'center', padding: '0.75rem 1rem', fontWeight: 600, color: '#374151', width: '140px' } }
										>
											{ role.name }
										</th>
									) ) }
								</tr>
							</thead>
							<tbody>
								{ group.capabilities.map( ( cap, index ) => (
									<tr
										key={ cap }
										style={ {
											borderBottom: '1px solid #f3f4f6',
											backgroundColor: index % 2 === 0 ? '#fafafa' : '#ffffff',
										} }
									>
										<td style={ { padding: '0.75rem 1rem', color: '#4b5563' } }>
											{ CAPABILITY_LABELS[ cap ] || cap }
										</td>
										{ /* Admin: Immer aktiv */ }
										<td style={ { textAlign: 'center', padding: '0.75rem 1rem' } }>
											<Check style={ { width: '1.25rem', height: '1.25rem', color: '#2fac66', margin: '0 auto' } } />
										</td>
										{ /* Editierbare Rollen */ }
										{ editableRoles.map( ( role ) => (
											<td key={ role.slug } style={ { textAlign: 'center', padding: '0.75rem 1rem' } }>
												{ adminOnlyCaps.includes( cap ) ? (
													<span style={ { color: '#d1d5db', fontSize: '0.875rem' } }>—</span>
												) : (
													<div style={ { display: 'flex', justifyContent: 'center' } }>
														<Switch
															checked={ getCapValue( role, cap ) }
															onCheckedChange={ ( value ) => handleToggle( role.slug, cap, value ) }
															disabled={ saving }
														/>
													</div>
												) }
											</td>
										) ) }
									</tr>
								) ) }
							</tbody>
						</table>
					</CardContent>
				</Card>
			) ) }

			{ /* Speichern-Buttons pro Rolle */ }
			{ editableRoles.length > 0 && (
				<div style={ { display: 'flex', gap: '1rem', justifyContent: 'flex-end' } }>
					{ editableRoles.map( ( role ) => (
						<Button
							key={ role.slug }
							onClick={ () => handleSave( role.slug ) }
							disabled={ saving || ! hasChanges( role.slug ) }
						>
							{ saving
								? __( 'Speichern…', 'recruiting-playbook' )
								: `${ role.name } ${ __( 'speichern', 'recruiting-playbook' ) }`
							}
						</Button>
					) ) }
				</div>
			) }
		</div>
	);
}
