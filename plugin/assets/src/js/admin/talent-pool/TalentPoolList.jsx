/**
 * Talent Pool List Component
 *
 * Main component for the talent pool overview page
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Search,
	Users,
	ChevronLeft,
	ChevronRight,
	Info,
	X,
} from 'lucide-react';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { useTalentPoolList } from '../applicant/hooks/useTalentPool';
import { TalentPoolCard } from './TalentPoolCard';

/**
 * Talent Pool List Component
 */
export function TalentPoolList() {
	const [ allTags, setAllTags ] = useState( [] );
	const [ selectedTag, setSelectedTag ] = useState( '' );
	const [ searchInput, setSearchInput ] = useState( '' );

	const config = window.rpTalentPool || {};
	const logoUrl = config.logoUrl || '';

	const {
		items,
		loading,
		error,
		total,
		totalPages,
		page,
		setSearch,
		setTags,
		setPage,
		refetch,
	} = useTalentPoolList();

	/**
	 * Load available tags
	 */
	const loadTags = useCallback( async () => {
		try {
			const response = await fetch(
				`${ config.apiUrl }talent-pool/tags`,
				{
					headers: {
						'X-WP-Nonce': config.nonce,
					},
				}
			);
			if ( response.ok ) {
				const tags = await response.json();
				setAllTags( tags || [] );
			}
		} catch ( err ) {
			console.error( 'Error loading tags:', err );
		}
	}, [ config.apiUrl, config.nonce ] );

	// Load tags initially
	useEffect( () => {
		loadTags();
	}, [ loadTags ] );

	/**
	 * Search with debounce
	 */
	useEffect( () => {
		const timeoutId = setTimeout( () => {
			setSearch( searchInput );
		}, 300 );

		return () => clearTimeout( timeoutId );
	}, [ searchInput, setSearch ] );

	/**
	 * Change tag filter
	 */
	const handleTagChange = ( e ) => {
		const tag = e.target.value;
		setSelectedTag( tag );
		setTags( tag );
	};

	/**
	 * Candidate removed handler
	 */
	const handleCandidateRemoved = () => {
		refetch();
		loadTags();
	};

	// Error State (Loading is handled by PHP placeholder)
	if ( error && items.length === 0 ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<Card>
					<CardContent style={ { padding: '3rem', textAlign: 'center' } }>
						<p style={ { color: '#d63638', marginBottom: '1.5rem' } }>{ error }</p>
						<Button onClick={ refetch }>
							{ __( 'Retry', 'recruiting-playbook' ) }
						</Button>
					</CardContent>
				</Card>
			</div>
		);
	}

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1400px' } }>
				{ /* Header: Logo left, title right */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img
							src={ logoUrl }
							alt="Recruiting Playbook"
							style={ { width: '150px', height: 'auto' } }
						/>
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ __( 'Talent Pool', 'recruiting-playbook' ) }
					</h1>
				</div>

				{ /* Filter Card */ }
				<Card style={ { marginBottom: '1rem' } }>
					<CardContent style={ { padding: '0.75rem 1rem' } }>
						<div style={ { display: 'flex', alignItems: 'center', gap: '1rem', flexWrap: 'wrap' } }>
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
									type="text"
									placeholder={ __( 'Search candidates\u2026', 'recruiting-playbook' ) }
									value={ searchInput }
									onChange={ ( e ) => setSearchInput( e.target.value ) }
									style={ {
										paddingLeft: '2.5rem',
										paddingRight: searchInput ? '2rem' : '1rem',
										paddingTop: '0.5rem',
										paddingBottom: '0.5rem',
										border: '1px solid #e5e7eb',
										borderRadius: '0.375rem',
										fontSize: '0.875rem',
										width: '280px',
										outline: 'none',
									} }
								/>
								{ searchInput && (
									<button
										type="button"
										onClick={ () => setSearchInput( '' ) }
										style={ {
											position: 'absolute',
											right: '0.5rem',
											top: '50%',
											transform: 'translateY(-50%)',
											background: 'none',
											border: 'none',
											cursor: 'pointer',
											padding: '0.25rem',
											color: '#9ca3af',
										} }
									>
										<X style={ { width: '0.875rem', height: '0.875rem' } } />
									</button>
								) }
							</div>

							{ /* Tag Filter */ }
							<select
								value={ selectedTag }
								onChange={ handleTagChange }
								style={ {
									padding: '0.5rem 2rem 0.5rem 0.75rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
									backgroundColor: '#fff',
									cursor: 'pointer',
									minWidth: '150px',
								} }
							>
								<option value="">{ __( 'All tags', 'recruiting-playbook' ) }</option>
								{ allTags.map( ( tag ) => (
									<option key={ tag } value={ tag }>
										{ tag }
									</option>
								) ) }
							</select>

							{ /* Spacer */ }
							<div style={ { flexGrow: 1 } } />

							{ /* Total Count */ }
							<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>
								{ sprintf( _n( '%d candidate', '%d candidates', total, 'recruiting-playbook' ), total ) }
							</span>
						</div>
					</CardContent>
				</Card>

				{ /* GDPR Notice */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '0.5rem',
						padding: '0.75rem 1rem',
						backgroundColor: '#fef3c7',
						borderRadius: '0.375rem',
						marginBottom: '1.5rem',
						fontSize: '0.875rem',
						color: '#92400e',
					} }
				>
					<Info style={ { width: '1rem', height: '1rem', flexShrink: 0 } } />
					{ __( 'GDPR notice: Candidates are automatically removed from the pool after expiry.', 'recruiting-playbook' ) }
				</div>

				{ /* Content */ }
				{ items.length === 0 ? (
					<Card>
						<CardContent style={ { padding: '4rem 2rem', textAlign: 'center' } }>
							<Users style={ { width: '4rem', height: '4rem', color: '#d1d5db', marginBottom: '1rem' } } />
							<h2 style={ { margin: '0 0 0.5rem 0', fontSize: '1.25rem', fontWeight: 600, color: '#1f2937' } }>
								{ __( 'The talent pool is still empty.', 'recruiting-playbook' ) }
							</h2>
							<p style={ { margin: '0 0 1.5rem 0', color: '#6b7280' } }>
								{ __( 'Add promising candidates from the application detail page to the talent pool.', 'recruiting-playbook' ) }
							</p>
							<Button asChild>
								<a href={ config.applicationsUrl || '#' }>
									{ __( 'Go to Applications', 'recruiting-playbook' ) }
								</a>
							</Button>
						</CardContent>
					</Card>
				) : (
					<>
						{ /* Candidates Grid */ }
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))',
								gap: '1rem',
								marginBottom: '1.5rem',
							} }
						>
							{ items.map( ( entry ) => (
								<TalentPoolCard
									key={ entry.id }
									entry={ entry }
									onRemoved={ handleCandidateRemoved }
								/>
							) ) }
						</div>

						{ /* Pagination */ }
						{ totalPages > 1 && (
							<Card>
								<CardContent style={ { padding: '0.75rem 1rem' } }>
									<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
										<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>
											{ __( 'Page', 'recruiting-playbook' ) } { page } { __( 'of', 'recruiting-playbook' ) } { totalPages }
										</span>
										<div style={ { display: 'flex', gap: '0.5rem' } }>
											<Button
												variant="outline"
												size="sm"
												onClick={ () => setPage( page - 1 ) }
												disabled={ page <= 1 || loading }
											>
												<ChevronLeft style={ { width: '1rem', height: '1rem', marginRight: '0.25rem' } } />
												{ __( 'Previous', 'recruiting-playbook' ) }
											</Button>
											<Button
												variant="outline"
												size="sm"
												onClick={ () => setPage( page + 1 ) }
												disabled={ page >= totalPages || loading }
											>
												{ __( 'Next', 'recruiting-playbook' ) }
												<ChevronRight style={ { width: '1rem', height: '1rem', marginLeft: '0.25rem' } } />
											</Button>
										</div>
									</div>
								</CardContent>
							</Card>
						) }
					</>
				) }
			</div>
		</div>
	);
}
