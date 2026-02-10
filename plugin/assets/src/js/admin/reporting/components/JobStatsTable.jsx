/**
 * JobStatsTable Component
 *
 * Zeigt eine Tabelle mit Stellen-Statistiken
 *
 * @package RecruitingPlaybook
 */

import { ExternalLink } from 'lucide-react';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../../components/ui/card';
import { Badge } from '../../components/ui/badge';

/**
 * JobStatsTable Component
 *
 * @param {Object} props Component props
 * @param {string} props.title Titel
 * @param {string} props.description Beschreibung
 * @param {Array} props.jobs Job-Daten [{id, title, applications, status}]
 * @param {boolean} props.loading Ladezustand
 * @param {number} props.limit Maximale Anzahl anzuzeigender Jobs
 */
export function JobStatsTable( {
	title,
	description,
	jobs = [],
	loading = false,
	limit = 10,
} ) {
	const displayedJobs = jobs.slice( 0, limit );

	if ( loading ) {
		return (
			<Card>
				<CardHeader>
					<div
						style={ {
							width: '50%',
							height: '1.5rem',
							backgroundColor: '#e5e7eb',
							borderRadius: '0.25rem',
							animation: 'pulse 2s infinite',
						} }
					/>
				</CardHeader>
				<CardContent>
					<table style={ { width: '100%' } }>
						<tbody>
							{ [ 1, 2, 3, 4, 5 ].map( ( i ) => (
								<tr key={ i }>
									<td style={ { padding: '0.75rem' } }>
										<div
											style={ {
												height: '1.5rem',
												backgroundColor: '#e5e7eb',
												borderRadius: '0.25rem',
												animation: 'pulse 2s infinite',
											} }
										/>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</CardContent>
			</Card>
		);
	}

	return (
		<Card>
			<CardHeader>
				<CardTitle>{ title }</CardTitle>
				{ description && <CardDescription>{ description }</CardDescription> }
			</CardHeader>
			<CardContent>
				{ displayedJobs.length === 0 ? (
					<div
						style={ {
							padding: '2rem',
							textAlign: 'center',
							color: '#6b7280',
						} }
					>
						Keine Stellen gefunden
					</div>
				) : (
					<div style={ { overflowX: 'auto' } }>
						<table
							style={ {
								width: '100%',
								borderCollapse: 'collapse',
								fontSize: '0.875rem',
							} }
						>
							<thead>
								<tr style={ { backgroundColor: '#f9fafb' } }>
									<th
										style={ {
											padding: '0.75rem 1rem',
											textAlign: 'left',
											fontWeight: 500,
											color: '#6b7280',
											borderBottom: '1px solid #e5e7eb',
										} }
									>
										Stelle
									</th>
									<th
										style={ {
											padding: '0.75rem 1rem',
											textAlign: 'right',
											fontWeight: 500,
											color: '#6b7280',
											borderBottom: '1px solid #e5e7eb',
										} }
									>
										Bewerbungen
									</th>
									<th
										style={ {
											padding: '0.75rem 1rem',
											textAlign: 'center',
											fontWeight: 500,
											color: '#6b7280',
											borderBottom: '1px solid #e5e7eb',
											width: '60px',
										} }
									>
										Status
									</th>
								</tr>
							</thead>
							<tbody>
								{ displayedJobs.map( ( job, index ) => (
									<tr
										key={ job.id || index }
										style={ {
											backgroundColor: index % 2 === 0 ? '#ffffff' : '#fafafa',
										} }
									>
										<td
											style={ {
												padding: '0.75rem 1rem',
												borderBottom: '1px solid #e5e7eb',
											} }
										>
											<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
												<span style={ { fontWeight: 500, color: '#111827' } }>
													{ job.title }
												</span>
												{ job.url && (
													<a
														href={ job.url }
														target="_blank"
														rel="noopener noreferrer"
														style={ { color: '#6b7280' } }
													>
														<ExternalLink style={ { width: '0.875rem', height: '0.875rem' } } />
													</a>
												) }
											</div>
											{ job.location && (
												<div
													style={ {
														fontSize: '0.75rem',
														color: '#6b7280',
														marginTop: '0.125rem',
													} }
												>
													{ job.location }
												</div>
											) }
										</td>
										<td
											style={ {
												padding: '0.75rem 1rem',
												textAlign: 'right',
												borderBottom: '1px solid #e5e7eb',
												fontWeight: 600,
												color: '#1d71b8',
											} }
										>
											{ job.applications?.toLocaleString( 'de-DE' ) || 0 }
										</td>
										<td
											style={ {
												padding: '0.75rem 1rem',
												textAlign: 'center',
												borderBottom: '1px solid #e5e7eb',
											} }
										>
											<Badge
												variant={ job.status === 'publish' ? 'default' : 'secondary' }
												style={ {
													backgroundColor: job.status === 'publish' ? '#dcfce7' : '#f3f4f6',
													color: job.status === 'publish' ? '#166534' : '#6b7280',
												} }
											>
												{ job.status === 'publish' ? 'Aktiv' : 'Entwurf' }
											</Badge>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				) }

				{ jobs.length > limit && (
					<div
						style={ {
							padding: '0.75rem 1rem',
							textAlign: 'center',
							borderTop: '1px solid #e5e7eb',
							color: '#6b7280',
							fontSize: '0.875rem',
						} }
					>
						{ jobs.length - limit } weitere Stellen nicht angezeigt
					</div>
				) }
			</CardContent>
		</Card>
	);
}

export default JobStatsTable;
