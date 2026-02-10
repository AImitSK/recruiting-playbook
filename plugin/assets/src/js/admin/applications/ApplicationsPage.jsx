/**
 * Applications List Page Component
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Search,
	FileText,
	ChevronLeft,
	ChevronRight,
	ChevronsLeft,
	ChevronsRight,
	Eye,
	Mail,
	MoreHorizontal,
	Download,
} from 'lucide-react';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
} from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Badge } from '../components/ui/badge';

/**
 * Status configuration with colors
 */
const STATUS_CONFIG = {
	new: { label: 'Neu', color: '#2271b1', bg: '#e6f3ff' },
	screening: { label: 'In Prüfung', color: '#dba617', bg: '#fff8e6' },
	interview: { label: 'Interview', color: '#9b59b6', bg: '#f5e6ff' },
	offer: { label: 'Angebot', color: '#1e8cbe', bg: '#e6f5ff' },
	hired: { label: 'Eingestellt', color: '#2fac66', bg: '#e6f5ec' },
	rejected: { label: 'Abgelehnt', color: '#d63638', bg: '#ffe6e6' },
	withdrawn: { label: 'Zurückgezogen', color: '#787c82', bg: '#f0f0f0' },
};

/**
 * Status Tab Component
 */
function StatusTab( { status, label, count, isActive, onClick } ) {
	const config = STATUS_CONFIG[ status ] || { color: '#787c82' };

	return (
		<button
			type="button"
			onClick={ onClick }
			style={ {
				padding: '0.5rem 1rem',
				border: 'none',
				background: isActive ? config.bg || '#f3f4f6' : 'transparent',
				borderBottom: isActive ? `2px solid ${ config.color }` : '2px solid transparent',
				color: isActive ? config.color : '#6b7280',
				fontWeight: isActive ? 600 : 400,
				fontSize: '0.875rem',
				cursor: 'pointer',
				display: 'flex',
				alignItems: 'center',
				gap: '0.5rem',
				transition: 'all 0.15s ease',
			} }
		>
			{ label }
			<span
				style={ {
					backgroundColor: isActive ? config.color : '#e5e7eb',
					color: isActive ? '#fff' : '#6b7280',
					padding: '0.125rem 0.5rem',
					borderRadius: '9999px',
					fontSize: '0.75rem',
					fontWeight: 500,
				} }
			>
				{ count }
			</span>
		</button>
	);
}

/**
 * Status Badge Component
 */
function StatusBadge( { status } ) {
	const config = STATUS_CONFIG[ status ] || { label: status, color: '#787c82', bg: '#f0f0f0' };

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				padding: '0.25rem 0.625rem',
				borderRadius: '9999px',
				fontSize: '0.75rem',
				fontWeight: 500,
				backgroundColor: config.bg,
				color: config.color,
			} }
		>
			{ config.label }
		</span>
	);
}

/**
 * Table Header Cell
 */
function TableHead( { children, sortable, sorted, direction, onClick, style = {} } ) {
	return (
		<th
			onClick={ sortable ? onClick : undefined }
			style={ {
				padding: '0.75rem 1rem',
				textAlign: 'left',
				fontWeight: 500,
				fontSize: '0.75rem',
				color: '#6b7280',
				textTransform: 'uppercase',
				letterSpacing: '0.05em',
				borderBottom: '1px solid #e5e7eb',
				backgroundColor: '#f9fafb',
				cursor: sortable ? 'pointer' : 'default',
				userSelect: 'none',
				...style,
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
				{ children }
				{ sortable && sorted && (
					<span style={ { fontSize: '0.625rem' } }>
						{ direction === 'asc' ? '▲' : '▼' }
					</span>
				) }
			</div>
		</th>
	);
}

/**
 * Table Cell
 */
function TableCell( { children, style = {} } ) {
	return (
		<td
			style={ {
				padding: '0.75rem 1rem',
				borderBottom: '1px solid #e5e7eb',
				fontSize: '0.875rem',
				color: '#1f2937',
				...style,
			} }
		>
			{ children }
		</td>
	);
}

/**
 * Pagination Component
 */
function Pagination( { currentPage, totalPages, totalItems, perPage, onPageChange } ) {
	const start = ( currentPage - 1 ) * perPage + 1;
	const end = Math.min( currentPage * perPage, totalItems );

	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				padding: '0.75rem 1rem',
				borderTop: '1px solid #e5e7eb',
				backgroundColor: '#f9fafb',
			} }
		>
			<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>
				{ totalItems > 0
					? `${ start }-${ end } von ${ totalItems } Bewerbungen`
					: __( 'Keine Bewerbungen', 'recruiting-playbook' ) }
			</span>

			<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
				<button
					type="button"
					onClick={ () => onPageChange( 1 ) }
					disabled={ currentPage === 1 }
					style={ {
						padding: '0.375rem',
						border: '1px solid #e5e7eb',
						borderRadius: '0.375rem',
						background: '#fff',
						cursor: currentPage === 1 ? 'not-allowed' : 'pointer',
						opacity: currentPage === 1 ? 0.5 : 1,
					} }
					title={ __( 'Erste Seite', 'recruiting-playbook' ) }
				>
					<ChevronsLeft style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
				</button>
				<button
					type="button"
					onClick={ () => onPageChange( currentPage - 1 ) }
					disabled={ currentPage === 1 }
					style={ {
						padding: '0.375rem',
						border: '1px solid #e5e7eb',
						borderRadius: '0.375rem',
						background: '#fff',
						cursor: currentPage === 1 ? 'not-allowed' : 'pointer',
						opacity: currentPage === 1 ? 0.5 : 1,
					} }
					title={ __( 'Vorherige Seite', 'recruiting-playbook' ) }
				>
					<ChevronLeft style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
				</button>

				<span
					style={ {
						padding: '0.375rem 0.75rem',
						fontSize: '0.875rem',
						color: '#1f2937',
					} }
				>
					{ currentPage } / { totalPages || 1 }
				</span>

				<button
					type="button"
					onClick={ () => onPageChange( currentPage + 1 ) }
					disabled={ currentPage >= totalPages }
					style={ {
						padding: '0.375rem',
						border: '1px solid #e5e7eb',
						borderRadius: '0.375rem',
						background: '#fff',
						cursor: currentPage >= totalPages ? 'not-allowed' : 'pointer',
						opacity: currentPage >= totalPages ? 0.5 : 1,
					} }
					title={ __( 'Nächste Seite', 'recruiting-playbook' ) }
				>
					<ChevronRight style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
				</button>
				<button
					type="button"
					onClick={ () => onPageChange( totalPages ) }
					disabled={ currentPage >= totalPages }
					style={ {
						padding: '0.375rem',
						border: '1px solid #e5e7eb',
						borderRadius: '0.375rem',
						background: '#fff',
						cursor: currentPage >= totalPages ? 'not-allowed' : 'pointer',
						opacity: currentPage >= totalPages ? 0.5 : 1,
					} }
					title={ __( 'Letzte Seite', 'recruiting-playbook' ) }
				>
					<ChevronsRight style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
				</button>
			</div>
		</div>
	);
}

/**
 * Main Applications Page Component
 */
export function ApplicationsPage() {
	const initialData = window.rpApplicationsData || {};
	const [ applications, setApplications ] = useState( initialData.applications || [] );
	const [ statusCounts, setStatusCounts ] = useState( initialData.statusCounts || {} );
	const [ jobs, setJobs ] = useState( initialData.jobs || [] );
	const [ isLoading, setIsLoading ] = useState( false );

	// Filters
	const [ activeStatus, setActiveStatus ] = useState( '' );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ selectedJob, setSelectedJob ] = useState( '' );

	// Pagination
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ totalItems, setTotalItems ] = useState( initialData.total || 0 );
	const [ perPage ] = useState( 20 );

	// Sorting
	const [ orderBy, setOrderBy ] = useState( 'created_at' );
	const [ order, setOrder ] = useState( 'desc' );

	const totalPages = Math.ceil( totalItems / perPage );

	const logoUrl = initialData.logoUrl;
	const adminUrl = initialData.adminUrl || '';

	// Calculate total count (excluding deleted)
	const totalCount = Object.entries( statusCounts )
		.filter( ( [ key ] ) => key !== 'deleted' )
		.reduce( ( sum, [ , count ] ) => sum + count, 0 );

	/**
	 * Fetch applications from API or use PHP-rendered data
	 */
	const fetchApplications = async () => {
		setIsLoading( true );

		try {
			// Build URL with current filters
			const params = new URLSearchParams( {
				page: currentPage.toString(),
				per_page: perPage.toString(),
				orderby: orderBy === 'created_at' ? 'date' : orderBy,
				order,
			} );

			if ( activeStatus ) {
				params.append( 'status', activeStatus );
			}
			if ( searchTerm ) {
				params.append( 'search', searchTerm );
			}
			if ( selectedJob ) {
				params.append( 'job_id', selectedJob );
			}

			// Try API first (Pro feature), fallback to page reload for Free
			try {
				const response = await apiFetch( {
					path: `/recruiting/v1/applications?${ params.toString() }`,
				} );

				setApplications( response.items || [] );
				setTotalItems( response.total || 0 );
			} catch ( apiError ) {
				// API not available (Free version) - reload page with filters
				const url = new URL( window.location.href );
				if ( activeStatus ) {
					url.searchParams.set( 'status', activeStatus );
				} else {
					url.searchParams.delete( 'status' );
				}
				if ( searchTerm ) {
					url.searchParams.set( 's', searchTerm );
				}
				if ( selectedJob ) {
					url.searchParams.set( 'job_id', selectedJob );
				}
				window.location.href = url.toString();
				return;
			}
		} catch ( error ) {
			console.error( 'Error fetching applications:', error );
		} finally {
			setIsLoading( false );
		}
	};

	// Track if this is the initial mount
	const [ isInitialMount, setIsInitialMount ] = useState( true );

	// For now, always use page reload method (PHP-rendered data)
	// API access can be enabled later when REST API is fully implemented
	const hasApiAccess = false;

	// Fetch on filter/page change (but not on initial mount - we already have data from PHP)
	useEffect( () => {
		if ( isInitialMount ) {
			setIsInitialMount( false );
			return;
		}

		// Only fetch if we have API access, otherwise use initial data
		if ( hasApiAccess ) {
			fetchApplications();
		}
	}, [ activeStatus, currentPage, orderBy, order, selectedJob ] );

	/**
	 * Handle search submit
	 */
	const handleSearch = ( e ) => {
		e.preventDefault();
		setCurrentPage( 1 );
		if ( hasApiAccess ) {
			fetchApplications();
		} else {
			// Reload page with search param
			const url = new URL( window.location.href );
			if ( searchTerm ) {
				url.searchParams.set( 's', searchTerm );
			} else {
				url.searchParams.delete( 's' );
			}
			window.location.href = url.toString();
		}
	};

	/**
	 * Handle status tab click
	 */
	const handleStatusClick = ( status ) => {
		setActiveStatus( status );
		setCurrentPage( 1 );

		if ( ! hasApiAccess ) {
			// Reload page with status filter
			const url = new URL( window.location.href );
			if ( status ) {
				url.searchParams.set( 'status', status );
			} else {
				url.searchParams.delete( 'status' );
			}
			url.searchParams.delete( 'paged' );
			window.location.href = url.toString();
		}
	};

	/**
	 * Handle sort click
	 */
	const handleSort = ( column ) => {
		if ( orderBy === column ) {
			setOrder( order === 'asc' ? 'desc' : 'asc' );
		} else {
			setOrderBy( column );
			setOrder( 'desc' );
		}
		setCurrentPage( 1 );
	};

	/**
	 * Format relative date
	 */
	const formatRelativeDate = ( dateString ) => {
		const date = new Date( dateString );
		const now = new Date();
		const diffMs = now - date;
		const diffDays = Math.floor( diffMs / ( 1000 * 60 * 60 * 24 ) );
		const diffHours = Math.floor( diffMs / ( 1000 * 60 * 60 ) );
		const diffMinutes = Math.floor( diffMs / ( 1000 * 60 ) );

		if ( diffMinutes < 60 ) {
			return `vor ${ diffMinutes } Min.`;
		}
		if ( diffHours < 24 ) {
			return `vor ${ diffHours } Std.`;
		}
		if ( diffDays < 7 ) {
			return `vor ${ diffDays } Tagen`;
		}
		return date.toLocaleDateString( 'de-DE' );
	};

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1400px' } }>
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
						{ __( 'Bewerbungen', 'recruiting-playbook' ) }
					</h1>
				</div>

				<Card>
					{ /* Status Tabs */ }
					<div
						style={ {
							display: 'flex',
							borderBottom: '1px solid #e5e7eb',
							overflowX: 'auto',
						} }
					>
						<StatusTab
							status=""
							label={ __( 'Alle', 'recruiting-playbook' ) }
							count={ totalCount }
							isActive={ activeStatus === '' }
							onClick={ () => handleStatusClick( '' ) }
						/>
						{ Object.entries( STATUS_CONFIG )
							.filter( ( [ key ] ) => key !== 'deleted' )
							.map( ( [ key, config ] ) => (
								<StatusTab
									key={ key }
									status={ key }
									label={ config.label }
									count={ statusCounts[ key ] || 0 }
									isActive={ activeStatus === key }
									onClick={ () => handleStatusClick( key ) }
								/>
							) ) }
					</div>

					{ /* Filters */ }
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							gap: '1rem',
							padding: '1rem',
							borderBottom: '1px solid #e5e7eb',
							flexWrap: 'wrap',
						} }
					>
						{ /* Search */ }
						<form
							onSubmit={ handleSearch }
							style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }
						>
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
									placeholder={ __( 'Name oder E-Mail suchen...', 'recruiting-playbook' ) }
									value={ searchTerm }
									onChange={ ( e ) => setSearchTerm( e.target.value ) }
									style={ {
										paddingLeft: '2.5rem',
										paddingRight: '1rem',
										paddingTop: '0.5rem',
										paddingBottom: '0.5rem',
										border: '1px solid #e5e7eb',
										borderRadius: '0.375rem',
										fontSize: '0.875rem',
										width: '280px',
										outline: 'none',
									} }
								/>
							</div>
							<Button type="submit" style={ { padding: '0.5rem 1rem' } }>
								{ __( 'Suchen', 'recruiting-playbook' ) }
							</Button>
						</form>

						{ /* Job Filter */ }
						<select
							value={ selectedJob }
							onChange={ ( e ) => {
								setSelectedJob( e.target.value );
								setCurrentPage( 1 );
								if ( ! hasApiAccess ) {
									const url = new URL( window.location.href );
									if ( e.target.value ) {
										url.searchParams.set( 'job_id', e.target.value );
									} else {
										url.searchParams.delete( 'job_id' );
									}
									window.location.href = url.toString();
								}
							} }
							style={ {
								padding: '0.5rem 2rem 0.5rem 0.75rem',
								border: '1px solid #e5e7eb',
								borderRadius: '0.375rem',
								fontSize: '0.875rem',
								backgroundColor: '#fff',
								cursor: 'pointer',
							} }
						>
							<option value="">{ __( 'Alle Stellen', 'recruiting-playbook' ) }</option>
							{ jobs.map( ( job ) => (
								<option key={ job.id } value={ job.id }>
									{ job.title }
								</option>
							) ) }
						</select>

						{ /* Spacer */ }
						<div style={ { flexGrow: 1 } } />

						{ /* Export Button */ }
						<a
							href={ `${ adminUrl }admin.php?page=rp-export` }
							style={ {
								display: 'inline-flex',
								alignItems: 'center',
								gap: '0.5rem',
								padding: '0.5rem 1rem',
								backgroundColor: '#fff',
								color: '#1d71b8',
								border: '1px solid #1d71b8',
								borderRadius: '0.375rem',
								fontSize: '0.875rem',
								fontWeight: 500,
								textDecoration: 'none',
							} }
						>
							<Download style={ { width: '1rem', height: '1rem' } } />
							{ __( 'Exportieren', 'recruiting-playbook' ) }
						</a>
					</div>

					{ /* Table */ }
					<div style={ { overflowX: 'auto' } }>
						<table style={ { width: '100%', borderCollapse: 'collapse' } }>
							<thead>
								<tr>
									<TableHead
										sortable
										sorted={ orderBy === 'last_name' }
										direction={ order }
										onClick={ () => handleSort( 'last_name' ) }
									>
										{ __( 'Bewerber', 'recruiting-playbook' ) }
									</TableHead>
									<TableHead>{ __( 'Stelle', 'recruiting-playbook' ) }</TableHead>
									<TableHead
										sortable
										sorted={ orderBy === 'status' }
										direction={ order }
										onClick={ () => handleSort( 'status' ) }
									>
										{ __( 'Status', 'recruiting-playbook' ) }
									</TableHead>
									<TableHead>
										{ __( 'Dokumente', 'recruiting-playbook' ) }
									</TableHead>
									<TableHead
										sortable
										sorted={ orderBy === 'created_at' }
										direction={ order }
										onClick={ () => handleSort( 'created_at' ) }
									>
										{ __( 'Eingegangen', 'recruiting-playbook' ) }
									</TableHead>
									<TableHead>
										{ __( 'Aktionen', 'recruiting-playbook' ) }
									</TableHead>
								</tr>
							</thead>
							<tbody>
								{ isLoading ? (
									<tr>
										<TableCell
											colSpan={ 6 }
											style={ { textAlign: 'center', padding: '3rem' } }
										>
											<div
												style={ {
													display: 'inline-block',
													width: '1.5rem',
													height: '1.5rem',
													border: '2px solid #e5e7eb',
													borderTopColor: '#1d71b8',
													borderRadius: '50%',
													animation: 'spin 0.8s linear infinite',
												} }
											/>
											<style>
												{ `@keyframes spin { to { transform: rotate(360deg); } }` }
											</style>
										</TableCell>
									</tr>
								) : applications.length === 0 ? (
									<tr>
										<TableCell
											colSpan={ 6 }
											style={ {
												textAlign: 'center',
												padding: '3rem',
												color: '#6b7280',
											} }
										>
											{ __( 'Keine Bewerbungen gefunden.', 'recruiting-playbook' ) }
										</TableCell>
									</tr>
								) : (
									applications.map( ( app ) => (
										<tr
											key={ app.id }
											style={ {
												transition: 'background-color 0.15s ease',
											} }
											onMouseEnter={ ( e ) => {
												e.currentTarget.style.backgroundColor = '#f9fafb';
											} }
											onMouseLeave={ ( e ) => {
												e.currentTarget.style.backgroundColor = 'transparent';
											} }
										>
											<TableCell>
												<a
													href={ `${ adminUrl }admin.php?page=rp-application-detail&id=${ app.id }` }
													style={ {
														fontWeight: 600,
														color: '#1f2937',
														textDecoration: 'none',
													} }
												>
													{ app.first_name } { app.last_name }
												</a>
											</TableCell>
											<TableCell>
												{ app.job_title || (
													<em style={ { color: '#9ca3af' } }>
														{ __( 'Gelöscht', 'recruiting-playbook' ) }
													</em>
												) }
											</TableCell>
											<TableCell>
												<StatusBadge status={ app.status } />
											</TableCell>
											<TableCell>
												<div
													style={ {
														display: 'flex',
														alignItems: 'center',
														gap: '0.25rem',
														color: app.documents_count > 0 ? '#1d71b8' : '#d1d5db',
													} }
												>
													<FileText style={ { width: '1rem', height: '1rem' } } />
													<span style={ { fontSize: '0.875rem' } }>
														{ app.documents_count || 0 }
													</span>
												</div>
											</TableCell>
											<TableCell>
												<span title={ app.created_at }>
													{ formatRelativeDate( app.created_at ) }
												</span>
											</TableCell>
											<TableCell>
												<div
													style={ {
														display: 'flex',
														alignItems: 'center',
														gap: '0.5rem',
													} }
												>
													<a
														href={ `${ adminUrl }admin.php?page=rp-application-detail&id=${ app.id }` }
														style={ {
															display: 'inline-flex',
															alignItems: 'center',
															gap: '0.25rem',
															padding: '0.375rem 0.75rem',
															backgroundColor: '#1d71b8',
															color: '#fff',
															borderRadius: '0.375rem',
															fontSize: '0.75rem',
															fontWeight: 500,
															textDecoration: 'none',
														} }
													>
														<Eye style={ { width: '0.875rem', height: '0.875rem' } } />
														{ __( 'Ansehen', 'recruiting-playbook' ) }
													</a>
													{ app.status === 'new' && (
														<a
															href={ `${ adminUrl }admin.php?page=rp-applications&action=set_status&id=${ app.id }&status=screening&_wpnonce=${ initialData.nonce }` }
															style={ {
																display: 'inline-flex',
																alignItems: 'center',
																padding: '0.375rem 0.75rem',
																backgroundColor: '#dba617',
																color: '#fff',
																borderRadius: '0.375rem',
																fontSize: '0.75rem',
																fontWeight: 500,
																textDecoration: 'none',
															} }
														>
															{ __( 'Prüfen', 'recruiting-playbook' ) }
														</a>
													) }
												</div>
											</TableCell>
										</tr>
									) )
								) }
							</tbody>
						</table>
					</div>

					{ /* Pagination */ }
					<Pagination
						currentPage={ currentPage }
						totalPages={ totalPages }
						totalItems={ totalItems }
						perPage={ perPage }
						onPageChange={ ( page ) => {
							setCurrentPage( page );
							if ( ! hasApiAccess ) {
								const url = new URL( window.location.href );
								url.searchParams.set( 'paged', page.toString() );
								window.location.href = url.toString();
							}
						} }
					/>
				</Card>
			</div>
		</div>
	);
}

export default ApplicationsPage;
