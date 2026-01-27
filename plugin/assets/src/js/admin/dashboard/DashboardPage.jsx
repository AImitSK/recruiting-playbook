/**
 * Dashboard Page Component
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { CheckCircle, XCircle } from 'lucide-react';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../components/ui/card';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Button } from '../components/ui/button';

/**
 * Stat Card Component - shadcn/ui Dashboard Style
 *
 * @param {Object} props - Component props
 * @param {string} props.title - Card title/label
 * @param {string|number} props.value - Main metric value
 * @param {string} props.description - Optional description text
 * @param {string} props.valueColor - Value color
 */
function StatCard( { title, value, description, valueColor = '#1f2937' } ) {
	return (
		<Card>
			<CardHeader
				style={ {
					padding: '1.5rem',
					paddingBottom: '0.5rem',
				} }
			>
				<span
					style={ {
						fontSize: '0.875rem',
						fontWeight: 500,
						color: '#6b7280',
					} }
				>
					{ title }
				</span>
			</CardHeader>
			<CardContent style={ { padding: '1.5rem', paddingTop: '0' } }>
				<div
					style={ {
						fontSize: '2rem',
						fontWeight: 700,
						color: valueColor,
						lineHeight: 1.2,
					} }
				>
					{ value }
				</div>
				{ description && (
					<p
						style={ {
							fontSize: '0.75rem',
							color: '#6b7280',
							marginTop: '0.25rem',
						} }
					>
						{ description }
					</p>
				) }
			</CardContent>
		</Card>
	);
}

/**
 * Warning Notice Component
 *
 * @param {Object} props - Component props
 * @param {string} props.title - Notice title
 * @param {string} props.message - Notice message
 * @param {string} props.actionUrl - Action button URL
 * @param {string} props.actionLabel - Action button label
 */
function WarningNotice( { title, message, actionUrl, actionLabel } ) {
	return (
		<Alert
			style={ {
				backgroundColor: '#fef3c7',
				borderLeft: '4px solid #f59e0b',
				borderTop: '1px solid #fcd34d',
				borderRight: '1px solid #fcd34d',
				borderBottom: '1px solid #fcd34d',
				marginBottom: '1rem',
			} }
		>
			<div>
				<div
					style={ {
						fontWeight: 600,
						color: '#92400e',
						marginBottom: '0.25rem',
					} }
				>
					{ title }
				</div>
				<AlertDescription style={ { color: '#a16207' } }>
					{ message }
				</AlertDescription>
				{ actionUrl && actionLabel && (
					<div style={ { marginTop: '0.75rem' } }>
						<a
							href={ actionUrl }
							target="_blank"
							rel="noopener noreferrer"
							style={ {
								display: 'inline-flex',
								alignItems: 'center',
								padding: '0.375rem 0.75rem',
								backgroundColor: '#ffffff',
								color: '#92400e',
								border: '1px solid #fcd34d',
								borderRadius: '0.375rem',
								fontSize: '0.875rem',
								fontWeight: 500,
								textDecoration: 'none',
							} }
						>
							{ actionLabel }
						</a>
					</div>
				) }
			</div>
		</Alert>
	);
}

/**
 * Database Table Status Row
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Table name
 * @param {string} props.label - Table label
 * @param {boolean} props.exists - Whether table exists
 */
function TableStatusRow( { name, label, exists } ) {
	return (
		<tr>
			<td
				style={ {
					padding: '0.75rem 1rem',
					borderBottom: '1px solid #e5e7eb',
				} }
			>
				<code
					style={ {
						backgroundColor: '#f3f4f6',
						padding: '0.125rem 0.375rem',
						borderRadius: '0.25rem',
						fontSize: '0.8125rem',
					} }
				>
					{ name }
				</code>
			</td>
			<td
				style={ {
					padding: '0.75rem 1rem',
					borderBottom: '1px solid #e5e7eb',
				} }
			>
				{ label }
			</td>
			<td
				style={ {
					padding: '0.75rem 1rem',
					borderBottom: '1px solid #e5e7eb',
					textAlign: 'center',
				} }
			>
				{ exists ? (
					<CheckCircle
						style={ {
							width: '1.25rem',
							height: '1.25rem',
							color: '#2fac66',
						} }
					/>
				) : (
					<XCircle
						style={ {
							width: '1.25rem',
							height: '1.25rem',
							color: '#ef4444',
						} }
					/>
				) }
			</td>
		</tr>
	);
}

/**
 * System Info Row
 *
 * @param {Object} props - Component props
 * @param {string} props.label - Row label
 * @param {string} props.value - Row value
 */
function SystemInfoRow( { label, value } ) {
	return (
		<tr>
			<td
				style={ {
					padding: '0.75rem 1rem',
					borderBottom: '1px solid #e5e7eb',
					fontWeight: 500,
				} }
			>
				{ label }
			</td>
			<td
				style={ {
					padding: '0.75rem 1rem',
					borderBottom: '1px solid #e5e7eb',
				} }
			>
				<code
					style={ {
						backgroundColor: '#f3f4f6',
						padding: '0.125rem 0.375rem',
						borderRadius: '0.25rem',
						fontSize: '0.8125rem',
					} }
				>
					{ value }
				</code>
			</td>
		</tr>
	);
}

/**
 * Main Dashboard Page Component
 */
export function DashboardPage() {
	const data = window.rpDashboardData || {};
	const stats = data.stats || {};
	const notices = data.notices || [];
	const tables = data.tables || {};
	const systemInfo = data.systemInfo || {};
	const logoUrl = data.logoUrl;

	const allTablesExist = Object.values( tables ).every( ( t ) => t.exists );

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1200px' } }>
				{ /* Header: Logo links, Titel rechts, Unterkante ausgerichtet */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ __( 'Dashboard', 'recruiting-playbook' ) }
					</h1>
				</div>

				{ /* Warnings */ }
				{ notices.length > 0 && (
					<div style={ { marginBottom: '1.5rem' } }>
						{ notices.map( ( notice, index ) => (
							<WarningNotice
								key={ index }
								title={ notice.title }
								message={ notice.message }
								actionUrl={ notice.actionUrl }
								actionLabel={ notice.actionLabel }
							/>
						) ) }
					</div>
				) }

				{ /* Stats Cards */ }
				<div
					style={ {
						display: 'grid',
						gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))',
						gap: '1.5rem',
						marginBottom: '2rem',
					} }
				>
					<StatCard
						title={ __( 'Aktive Stellen', 'recruiting-playbook' ) }
						value={ stats.activeJobs || 0 }
						description={ __( 'Veröffentlichte Stellenanzeigen', 'recruiting-playbook' ) }
						valueColor="#1d71b8"
					/>
					<StatCard
						title={ __( 'Neue Bewerbungen', 'recruiting-playbook' ) }
						value={ stats.newApplications || 0 }
						description={ __( 'Noch nicht bearbeitet', 'recruiting-playbook' ) }
						valueColor="#2fac66"
					/>
					<StatCard
						title={ __( 'Gesamt Bewerbungen', 'recruiting-playbook' ) }
						value={ stats.totalApplications || 0 }
						description={ __( 'Alle eingegangenen Bewerbungen', 'recruiting-playbook' ) }
					/>
				</div>

				{ /* Two Column Layout */ }
				<div
					style={ {
						display: 'grid',
						gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
						gap: '1.5rem',
					} }
				>
					{ /* Database Integrity */ }
					<Card>
						<CardHeader>
							<CardTitle>
								{ __( 'Datenbank-Integrität', 'recruiting-playbook' ) }
							</CardTitle>
							<CardDescription>
								{ allTablesExist
									? __( 'Alle Tabellen sind vorhanden', 'recruiting-playbook' )
									: __( 'Einige Tabellen fehlen', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent>
							{ /* Status Badge */ }
							<div
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '0.5rem',
									marginBottom: '1rem',
									padding: '0.5rem 0.75rem',
									backgroundColor: allTablesExist ? '#e6f5ec' : '#fef2f2',
									borderRadius: '0.375rem',
									width: 'fit-content',
								} }
							>
								{ allTablesExist ? (
									<CheckCircle
										style={ { width: '1rem', height: '1rem', color: '#2fac66' } }
									/>
								) : (
									<XCircle
										style={ { width: '1rem', height: '1rem', color: '#ef4444' } }
									/>
								) }
								<span
									style={ {
										fontSize: '0.875rem',
										fontWeight: 500,
										color: allTablesExist ? '#166534' : '#991b1b',
									} }
								>
									{ allTablesExist
										? __( 'Alles OK', 'recruiting-playbook' )
										: __( 'Fehler gefunden', 'recruiting-playbook' ) }
								</span>
							</div>

							{ /* Table List */ }
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
											{ __( 'Tabelle', 'recruiting-playbook' ) }
										</th>
										<th
											style={ {
												padding: '0.75rem 1rem',
												textAlign: 'left',
												fontWeight: 500,
												color: '#6b7280',
												borderBottom: '1px solid #e5e7eb',
											} }
										>
											{ __( 'Beschreibung', 'recruiting-playbook' ) }
										</th>
										<th
											style={ {
												padding: '0.75rem 1rem',
												textAlign: 'center',
												fontWeight: 500,
												color: '#6b7280',
												borderBottom: '1px solid #e5e7eb',
											} }
										>
											{ __( 'Status', 'recruiting-playbook' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ Object.entries( tables ).map( ( [ key, table ] ) => (
										<TableStatusRow
											key={ key }
											name={ table.name }
											label={ table.label }
											exists={ table.exists }
										/>
									) ) }
								</tbody>
							</table>

							{ ! allTablesExist && (
								<p
									style={ {
										marginTop: '1rem',
										fontSize: '0.875rem',
										color: '#ef4444',
									} }
								>
									{ __(
										'Bitte deaktivieren und reaktivieren Sie das Plugin, um die fehlenden Tabellen zu erstellen.',
										'recruiting-playbook'
									) }
								</p>
							) }
						</CardContent>
					</Card>

					{ /* System Info */ }
					<Card>
						<CardHeader>
							<CardTitle>
								{ __( 'System-Info', 'recruiting-playbook' ) }
							</CardTitle>
							<CardDescription>
								{ __( 'Technische Informationen', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent>
							<table
								style={ {
									width: '100%',
									borderCollapse: 'collapse',
									fontSize: '0.875rem',
								} }
							>
								<tbody>
									<SystemInfoRow
										label={ __( 'Plugin Version', 'recruiting-playbook' ) }
										value={ systemInfo.pluginVersion || '-' }
									/>
									<SystemInfoRow
										label={ __( 'PHP Version', 'recruiting-playbook' ) }
										value={ systemInfo.phpVersion || '-' }
									/>
									<SystemInfoRow
										label={ __( 'WordPress Version', 'recruiting-playbook' ) }
										value={ systemInfo.wpVersion || '-' }
									/>
									<SystemInfoRow
										label={ __( 'Datenbank-Version', 'recruiting-playbook' ) }
										value={ systemInfo.dbVersion || '-' }
									/>
								</tbody>
							</table>
						</CardContent>
					</Card>
				</div>
			</div>
		</div>
	);
}

export default DashboardPage;
