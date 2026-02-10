/**
 * Kanban Page Component
 *
 * Page wrapper mit konsistentem Header Layout
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Search, RefreshCw, List } from 'lucide-react';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { KanbanBoard } from './KanbanBoard';

/**
 * Debounce-Funktion für verzögerte Ausführung
 */
function debounce( func, wait ) {
	let timeout;
	return function executedFunction( ...args ) {
		const later = () => {
			clearTimeout( timeout );
			func( ...args );
		};
		clearTimeout( timeout );
		timeout = setTimeout( later, wait );
	};
}

/**
 * Kanban Page Component
 */
export function KanbanPage() {
	const config = window.rpKanban || {};
	const logoUrl = config.logoUrl || '';
	const adminUrl = config.adminUrl || '';
	const jobs = config.jobs || [];

	const [ jobFilter, setJobFilter ] = useState( '' );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ refreshTrigger, setRefreshTrigger ] = useState( 0 );

	// Debounced Search
	const debouncedSetSearchTerm = useMemo(
		() => debounce( ( value ) => setSearchTerm( value ), 300 ),
		[]
	);

	const handleRefresh = () => {
		setIsRefreshing( true );
		setRefreshTrigger( ( prev ) => prev + 1 );
		setTimeout( () => setIsRefreshing( false ), 1000 );
	};

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '100%', paddingRight: '20px' } }>
				{ /* Header: Logo links, Titel rechts */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img
							src={ logoUrl }
							alt="Recruiting Playbook"
							style={ { width: '150px', height: 'auto' } }
						/>
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ __( 'Kanban-Board', 'recruiting-playbook' ) }
					</h1>
				</div>

				{ /* Toolbar */ }
				<Card style={ { marginBottom: '1rem' } }>
					<CardContent style={ { padding: '0.75rem 1rem' } }>
						<div style={ { display: 'flex', alignItems: 'center', gap: '1rem', flexWrap: 'wrap' } }>
							{ /* Job Filter */ }
							<select
								value={ jobFilter }
								onChange={ ( e ) => setJobFilter( e.target.value ) }
								style={ {
									padding: '0.5rem 2rem 0.5rem 0.75rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
									backgroundColor: '#fff',
									cursor: 'pointer',
									minWidth: '200px',
								} }
							>
								<option value="">{ __( 'Alle Stellen', 'recruiting-playbook' ) }</option>
								{ jobs.map( ( job ) => (
									<option key={ job.id } value={ job.id }>
										{ job.title }
									</option>
								) ) }
							</select>

							{ /* Search */ }
							<div style={ { position: 'relative' } }>
								<Search
									style={ {
										position: 'absolute',
										left: '0.75rem',
										top: '50%',
										transform: 'translateY(-50%)',
										width: '1rem',
										height: '1rem',
										color: '#9ca3af',
									} }
								/>
								<input
									type="search"
									placeholder={ __( 'Bewerber suchen...', 'recruiting-playbook' ) }
									onChange={ ( e ) => debouncedSetSearchTerm( e.target.value ) }
									style={ {
										paddingLeft: '2.5rem',
										paddingRight: '1rem',
										paddingTop: '0.5rem',
										paddingBottom: '0.5rem',
										border: '1px solid #e5e7eb',
										borderRadius: '0.375rem',
										fontSize: '0.875rem',
										width: '250px',
										outline: 'none',
									} }
								/>
							</div>

							{ /* Spacer */ }
							<div style={ { flexGrow: 1 } } />

							{ /* Actions */ }
							<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
								<Button
									variant="outline"
									onClick={ handleRefresh }
									disabled={ isRefreshing }
									title={ __( 'Aktualisieren', 'recruiting-playbook' ) }
									style={ { padding: '0.5rem' } }
								>
									<RefreshCw
										style={ {
											width: '1rem',
											height: '1rem',
											animation: isRefreshing ? 'spin 1s linear infinite' : 'none',
										} }
									/>
								</Button>

								<a
									href={ `${ adminUrl }admin.php?page=rp-applications` }
									style={ {
										display: 'inline-flex',
										alignItems: 'center',
										gap: '0.375rem',
										padding: '0.5rem 0.75rem',
										backgroundColor: '#fff',
										color: '#1d71b8',
										border: '1px solid #1d71b8',
										borderRadius: '0.375rem',
										fontSize: '0.875rem',
										fontWeight: 500,
										textDecoration: 'none',
									} }
								>
									<List style={ { width: '1rem', height: '1rem' } } />
									{ __( 'Listen-Ansicht', 'recruiting-playbook' ) }
								</a>
							</div>
						</div>
					</CardContent>
				</Card>

				{ /* Kanban Board */ }
				<Card>
					<CardContent style={ { padding: '1rem', backgroundColor: '#f9fafb', minHeight: '500px' } }>
						<KanbanBoard
							jobFilter={ jobFilter }
							searchTerm={ searchTerm }
							refreshTrigger={ refreshTrigger }
						/>
					</CardContent>
				</Card>
			</div>

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</div>
	);
}

export default KanbanPage;
