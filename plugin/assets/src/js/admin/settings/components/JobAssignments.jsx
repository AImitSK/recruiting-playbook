/**
 * JobAssignments Component
 *
 * Job assignment for recruiting users
 *
 * @package RecruitingPlaybook
 */

import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { useJobAssignments } from '../hooks/useRoles';
import { UserPlus, X, Users, Briefcase, AlertCircle } from 'lucide-react';

/**
 * JobAssignments Component
 *
 * @return {JSX.Element} Component
 */
export function JobAssignments() {
	const {
		users,
		selectedUser,
		assignedJobs,
		allJobs,
		loading,
		assigning,
		error,
		selectUser,
		assignJob,
		unassignJob,
		assignAllJobs,
	} = useJobAssignments();

	/**
	 * User dropdown handler
	 */
	const handleUserChange = useCallback( ( e ) => {
		const userId = parseInt( e.target.value, 10 );
		selectUser( userId || null );
	}, [ selectUser ] );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	const assignedIds = assignedJobs.map( ( j ) => j.id );
	const unassignedJobs = allJobs.filter( ( j ) => ! assignedIds.includes( j.id ) );

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
			{ error && (
				<Alert variant="destructive">
					<AlertCircle style={ { width: '1rem', height: '1rem' } } />
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			{ /* User selection */ }
			<Card>
				<CardHeader>
					<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Users style={ { width: '1.25rem', height: '1.25rem', color: '#1d71b8' } } />
						{ __( 'Select User', 'recruiting-playbook' ) }
					</CardTitle>
				</CardHeader>
				<CardContent>
					<select
						value={ selectedUser?.id || '' }
						onChange={ handleUserChange }
						style={ {
							width: '100%',
							maxWidth: '400px',
							padding: '0.5rem 0.75rem',
							borderRadius: '0.375rem',
							border: '1px solid #d1d5db',
							fontSize: '0.875rem',
						} }
					>
						<option value="">
							{ __( '— Select User —', 'recruiting-playbook' ) }
						</option>
						{ users.map( ( user ) => (
							<option key={ user.id } value={ user.id }>
								{ user.name } ({ user.role })
							</option>
						) ) }
					</select>
				</CardContent>
			</Card>

			{ /* Show assignments only if user is selected */ }
			{ selectedUser && (
				<>
					{ /* Assigned jobs */ }
					<Card>
						<CardHeader>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
								<Briefcase style={ { width: '1.25rem', height: '1.25rem', color: '#2fac66' } } />
								{ __( 'Assigned Jobs', 'recruiting-playbook' ) }
								<span style={ {
									backgroundColor: '#e6f5ec',
									color: '#2fac66',
									fontSize: '0.75rem',
									fontWeight: 600,
									padding: '0.125rem 0.5rem',
									borderRadius: '9999px',
								} }>
									{ assignedJobs.length }
								</span>
							</CardTitle>
						</CardHeader>
						<CardContent>
							{ assignedJobs.length === 0 ? (
								<p style={ { color: '#9ca3af', fontStyle: 'italic' } }>
									{ __( 'No jobs assigned.', 'recruiting-playbook' ) }
								</p>
							) : (
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									{ assignedJobs.map( ( job ) => (
										<div
											key={ job.id }
											style={ {
												display: 'flex',
												justifyContent: 'space-between',
												alignItems: 'center',
												padding: '0.75rem 1rem',
												backgroundColor: '#f9fafb',
												borderRadius: '0.375rem',
												border: '1px solid #e5e7eb',
											} }
										>
											<div>
												<div style={ { fontWeight: 500, color: '#111827' } }>
													{ job.title }
												</div>
												<div style={ { fontSize: '0.75rem', color: '#6b7280' } }>
													{ job.status === 'publish'
														? __( 'Active', 'recruiting-playbook' )
														: job.status === 'draft'
															? __( 'Draft', 'recruiting-playbook' )
															: job.status
													}
													{ job.assigned_at && ` · ${ __( 'Assigned on', 'recruiting-playbook' ) } ${ new Date( job.assigned_at ).toLocaleDateString( 'de-DE' ) }` }
												</div>
											</div>
											<Button
												variant="ghost"
												size="sm"
												onClick={ () => unassignJob( job.id ) }
												disabled={ assigning }
												style={ { color: '#ef4444' } }
											>
												<X style={ { width: '1rem', height: '1rem' } } />
												{ __( 'Remove', 'recruiting-playbook' ) }
											</Button>
										</div>
									) ) }
								</div>
							) }
						</CardContent>
					</Card>

					{ /* Available jobs */ }
					<Card>
						<CardHeader>
							<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
								<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
									<Briefcase style={ { width: '1.25rem', height: '1.25rem', color: '#6b7280' } } />
									{ __( 'Available Jobs', 'recruiting-playbook' ) }
									<span style={ {
										backgroundColor: '#f3f4f6',
										color: '#6b7280',
										fontSize: '0.75rem',
										fontWeight: 600,
										padding: '0.125rem 0.5rem',
										borderRadius: '9999px',
									} }>
										{ unassignedJobs.length }
									</span>
								</CardTitle>
								{ unassignedJobs.length > 0 && (
									<Button
										variant="outline"
										size="sm"
										onClick={ assignAllJobs }
										disabled={ assigning }
									>
										<UserPlus style={ { width: '1rem', height: '1rem', marginRight: '0.25rem' } } />
										{ __( 'Assign All', 'recruiting-playbook' ) }
									</Button>
								) }
							</div>
						</CardHeader>
						<CardContent>
							{ unassignedJobs.length === 0 ? (
								<p style={ { color: '#9ca3af', fontStyle: 'italic' } }>
									{ __( 'All jobs are already assigned.', 'recruiting-playbook' ) }
								</p>
							) : (
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									{ unassignedJobs.map( ( job ) => (
										<div
											key={ job.id }
											style={ {
												display: 'flex',
												justifyContent: 'space-between',
												alignItems: 'center',
												padding: '0.75rem 1rem',
												backgroundColor: '#ffffff',
												borderRadius: '0.375rem',
												border: '1px solid #e5e7eb',
											} }
										>
											<div>
												<div style={ { fontWeight: 500, color: '#111827' } }>
													{ job.title }
												</div>
												<div style={ { fontSize: '0.75rem', color: '#6b7280' } }>
													{ job.status === 'publish'
														? __( 'Active', 'recruiting-playbook' )
														: job.status === 'draft'
															? __( 'Draft', 'recruiting-playbook' )
															: job.status
													}
												</div>
											</div>
											<Button
												variant="outline"
												size="sm"
												onClick={ () => assignJob( job.id ) }
												disabled={ assigning }
											>
												<UserPlus style={ { width: '1rem', height: '1rem', marginRight: '0.25rem' } } />
												{ __( 'Assign', 'recruiting-playbook' ) }
											</Button>
										</div>
									) ) }
								</div>
							) }
						</CardContent>
					</Card>
				</>
			) }
		</div>
	);
}
