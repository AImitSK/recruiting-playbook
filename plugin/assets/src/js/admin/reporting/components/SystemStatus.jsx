/**
 * SystemStatus Component
 *
 * Zeigt den Systemstatus mit Health-Checks und Cleanup-Optionen
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { getWpLocale } from '../../utils/locale';
import {
	CheckCircle,
	XCircle,
	AlertTriangle,
	RefreshCw,
	Trash2,
	Database,
	HardDrive,
	Clock,
	FileWarning,
	Key,
	Zap,
} from 'lucide-react';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { useSystemStatus } from '../hooks/useSystemStatus';

/**
 * Status-Icon basierend auf Status
 */
function StatusIcon( { status, size = 20 } ) {
	const style = { width: size, height: size };

	switch ( status ) {
		case 'ok':
			return <CheckCircle style={ { ...style, color: '#22c55e' } } />;
		case 'warning':
			return <AlertTriangle style={ { ...style, color: '#f59e0b' } } />;
		case 'error':
			return <XCircle style={ { ...style, color: '#ef4444' } } />;
		default:
			return <AlertTriangle style={ { ...style, color: '#6b7280' } } />;
	}
}

/**
 * Check-Icon basierend auf Check-Typ
 */
function CheckIcon( { type, size = 18 } ) {
	const style = { width: size, height: size, color: '#6b7280' };

	switch ( type ) {
		case 'database':
			return <Database style={ style } />;
		case 'uploads':
			return <HardDrive style={ style } />;
		case 'cron':
			return <Clock style={ style } />;
		case 'orphaned_data':
			return <FileWarning style={ style } />;
		case 'license':
			return <Key style={ style } />;
		case 'action_scheduler':
			return <Zap style={ style } />;
		default:
			return <Database style={ style } />;
	}
}

/**
 * Einzelner Check-Eintrag
 */
function CheckItem( { type, check, onCleanup, cleanupLoading } ) {
	const [ expanded, setExpanded ] = useState( false );

	const showCleanup = type === 'orphaned_data' && check.status === 'warning';
	const details = check.details || {};

	return (
		<div
			style={ {
				padding: '1rem',
				borderBottom: '1px solid #e5e7eb',
			} }
		>
			<div
				style={ {
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'space-between',
				} }
			>
				<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem' } }>
					<CheckIcon type={ type } />
					<div>
						<div style={ { fontWeight: 500, color: '#111827' } }>
							{ getCheckLabel( type ) }
						</div>
						<div style={ { fontSize: '0.875rem', color: '#6b7280' } }>
							{ check.message }
						</div>
					</div>
				</div>

				<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem' } }>
					{ showCleanup && (
						<Button
							variant="outline"
							size="sm"
							onClick={ onCleanup }
							disabled={ cleanupLoading }
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.25rem',
								fontSize: '0.75rem',
								padding: '0.25rem 0.5rem',
							} }
						>
							<Trash2 style={ { width: 14, height: 14 } } />
							{ __( 'Clean up', 'recruiting-playbook' ) }
						</Button>
					) }

					<StatusIcon status={ check.status } />

					{ Object.keys( details ).length > 0 && (
						<button
							onClick={ () => setExpanded( ! expanded ) }
							style={ {
								background: 'none',
								border: 'none',
								cursor: 'pointer',
								color: '#6b7280',
								fontSize: '0.75rem',
							} }
						>
							{ expanded ? __( 'Less', 'recruiting-playbook' ) : __( 'Details', 'recruiting-playbook' ) }
						</button>
					) }
				</div>
			</div>

			{ expanded && Object.keys( details ).length > 0 && (
				<div
					style={ {
						marginTop: '0.75rem',
						marginLeft: '2.25rem',
						padding: '0.75rem',
						backgroundColor: '#f9fafb',
						borderRadius: '0.375rem',
						fontSize: '0.75rem',
					} }
				>
					{ Object.entries( details ).map( ( [ key, value ] ) => (
						<div
							key={ key }
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
								padding: '0.25rem 0',
							} }
						>
							<span style={ { color: '#6b7280' } }>{ formatDetailKey( key ) }:</span>
							<span style={ { color: '#111827', fontFamily: 'monospace' } }>
								{ formatDetailValue( value ) }
							</span>
						</div>
					) ) }
				</div>
			) }
		</div>
	);
}

/**
 * Check-Label übersetzen
 */
function getCheckLabel( type ) {
	const labels = {
		database: __( 'Database', 'recruiting-playbook' ),
		uploads: __( 'Upload directory', 'recruiting-playbook' ),
		cron: __( 'Cron jobs', 'recruiting-playbook' ),
		orphaned_data: __( 'Orphaned data', 'recruiting-playbook' ),
		license: __( 'License', 'recruiting-playbook' ),
		action_scheduler: __( 'Action Scheduler', 'recruiting-playbook' ),
	};
	return labels[ type ] || type;
}

/**
 * Detail-Key formatieren
 */
function formatDetailKey( key ) {
	const labels = {
		tables_expected: __( 'Expected tables', 'recruiting-playbook' ),
		tables_found: __( 'Found tables', 'recruiting-playbook' ),
		missing: __( 'Missing', 'recruiting-playbook' ),
		path: __( 'Path', 'recruiting-playbook' ),
		writable: __( 'Writable', 'recruiting-playbook' ),
		files_count: __( 'Files', 'recruiting-playbook' ),
		total_size: __( 'Size', 'recruiting-playbook' ),
		next_cleanup: __( 'Next cleanup', 'recruiting-playbook' ),
		last_run: __( 'Last run', 'recruiting-playbook' ),
		wp_cron_disabled: __( 'WP-Cron disabled', 'recruiting-playbook' ),
		orphaned_documents: __( 'Orphaned documents', 'recruiting-playbook' ),
		orphaned_applications: __( 'Orphaned applications', 'recruiting-playbook' ),
		type: __( 'Type', 'recruiting-playbook' ),
		expires: __( 'Expires', 'recruiting-playbook' ),
		domain: __( 'Domain', 'recruiting-playbook' ),
		available: __( 'Available', 'recruiting-playbook' ),
		pending: __( 'Pending', 'recruiting-playbook' ),
		running: __( 'Running', 'recruiting-playbook' ),
		failed: __( 'Failed', 'recruiting-playbook' ),
	};
	return labels[ key ] || key;
}

/**
 * Detail-Wert formatieren
 */
function formatDetailValue( value ) {
	if ( value === true ) {
		return __( 'Yes', 'recruiting-playbook' );
	}
	if ( value === false ) {
		return __( 'No', 'recruiting-playbook' );
	}
	if ( value === null || value === undefined ) {
		return '-';
	}
	if ( Array.isArray( value ) ) {
		return value.join( ', ' ) || '-';
	}
	return String( value );
}

/**
 * SystemStatus Component
 */
export function SystemStatus() {
	const {
		status,
		loading,
		error,
		cleanupLoading,
		cleanupDocuments,
		cleanupApplications,
		refetch,
	} = useSystemStatus();

	const [ cleanupMessage, setCleanupMessage ] = useState( null );

	const handleCleanup = async () => {
		try {
			setCleanupMessage( null );

			const orphanedDocs = status?.checks?.orphaned_data?.details?.orphaned_documents || 0;
			const orphanedApps = status?.checks?.orphaned_data?.details?.orphaned_applications || 0;

			let totalDeleted = 0;

			if ( orphanedDocs > 0 ) {
				const result = await cleanupDocuments();
				totalDeleted += result.deleted || 0;
			}

			if ( orphanedApps > 0 ) {
				const result = await cleanupApplications();
				totalDeleted += result.deleted || 0;
			}

			setCleanupMessage( {
				type: 'success',
				/* translators: %d: number of deleted orphaned entries */
				text: __( totalDeleted + ' orphaned entries have been deleted.', 'recruiting-playbook' ),
			} );
		} catch ( err ) {
			setCleanupMessage( {
				type: 'error',
				text: __( 'Error cleaning up: ', 'recruiting-playbook' ) + ( err.message || __( 'Unknown error', 'recruiting-playbook' ) ),
			} );
		}
	};

	const getOverallStatusColor = () => {
		switch ( status?.status ) {
			case 'healthy':
				return '#22c55e';
			case 'degraded':
				return '#f59e0b';
			case 'unhealthy':
				return '#ef4444';
			default:
				return '#6b7280';
		}
	};

	const getOverallStatusLabel = () => {
		switch ( status?.status ) {
			case 'healthy':
				return __( 'All OK', 'recruiting-playbook' );
			case 'degraded':
				return __( 'Warnings', 'recruiting-playbook' );
			case 'unhealthy':
				return __( 'Error', 'recruiting-playbook' );
			default:
				return __( 'Unknown', 'recruiting-playbook' );
		}
	};

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
					{ [ 1, 2, 3, 4 ].map( ( i ) => (
						<div
							key={ i }
							style={ {
								height: '3rem',
								backgroundColor: '#f3f4f6',
								borderRadius: '0.25rem',
								marginBottom: '0.5rem',
								animation: 'pulse 2s infinite',
							} }
						/>
					) ) }
				</CardContent>
			</Card>
		);
	}

	if ( error ) {
		return (
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'System status', 'recruiting-playbook' ) }</CardTitle>
				</CardHeader>
				<CardContent>
					<Alert style={ { backgroundColor: '#fef2f2', borderColor: '#fecaca' } }>
						<XCircle style={ { width: 16, height: 16, color: '#ef4444' } } />
						<AlertDescription style={ { color: '#991b1b' } }>
							{ error }
						</AlertDescription>
					</Alert>
					<Button
						onClick={ refetch }
						style={ { marginTop: '1rem' } }
					>
						<RefreshCw style={ { width: 16, height: 16, marginRight: '0.5rem' } } />
						{ __( 'Retry', 'recruiting-playbook' ) }
					</Button>
				</CardContent>
			</Card>
		);
	}

	return (
		<Card>
			<CardHeader>
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
					<div>
						<CardTitle>{ __( 'System status', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ __( 'Last checked:', 'recruiting-playbook' ) } { status?.checked_at ? new Date( status.checked_at ).toLocaleString( getWpLocale() ) : '-' }
						</CardDescription>
					</div>
					<div style={ { display: 'flex', alignItems: 'center', gap: '1rem' } }>
						<div
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.5rem',
								padding: '0.5rem 1rem',
								backgroundColor: `${ getOverallStatusColor() }15`,
								borderRadius: '0.375rem',
							} }
						>
							<StatusIcon status={ status?.status === 'healthy' ? 'ok' : status?.status === 'degraded' ? 'warning' : 'error' } size={ 18 } />
							<span style={ { fontWeight: 600, color: getOverallStatusColor() } }>
								{ getOverallStatusLabel() }
							</span>
						</div>
						<Button
							variant="outline"
							size="sm"
							onClick={ refetch }
							disabled={ loading }
						>
							<RefreshCw style={ { width: 14, height: 14 } } />
						</Button>
					</div>
				</div>
			</CardHeader>
			<CardContent style={ { padding: 0 } }>
				{ /* Cleanup-Nachricht */ }
				{ cleanupMessage && (
					<div style={ { padding: '0 1rem' } }>
						<Alert
							style={ {
								marginBottom: '0.5rem',
								backgroundColor: cleanupMessage.type === 'success' ? '#f0fdf4' : '#fef2f2',
								borderColor: cleanupMessage.type === 'success' ? '#bbf7d0' : '#fecaca',
							} }
						>
							<AlertDescription
								style={ { color: cleanupMessage.type === 'success' ? '#166534' : '#991b1b' } }
							>
								{ cleanupMessage.text }
							</AlertDescription>
						</Alert>
					</div>
				) }

				{ /* Checks */ }
				{ status?.checks && Object.entries( status.checks ).map( ( [ type, check ] ) => (
					<CheckItem
						key={ type }
						type={ type }
						check={ check }
						onCleanup={ handleCleanup }
						cleanupLoading={ cleanupLoading }
					/>
				) ) }

				{ /* Empfehlungen */ }
				{ status?.recommendations?.length > 0 && (
					<div style={ { padding: '1rem', backgroundColor: '#fffbeb' } }>
						<div style={ { fontWeight: 600, color: '#92400e', marginBottom: '0.5rem' } }>
							{ __( 'Recommendations', 'recruiting-playbook' ) }
						</div>
						{ status.recommendations.map( ( rec, index ) => (
							<div
								key={ index }
								style={ {
									display: 'flex',
									alignItems: 'flex-start',
									gap: '0.5rem',
									padding: '0.5rem 0',
									fontSize: '0.875rem',
									color: '#a16207',
								} }
							>
								<AlertTriangle style={ { width: 16, height: 16, flexShrink: 0, marginTop: 2 } } />
								{ rec.message }
							</div>
						) ) }
					</div>
				) }

				{ /* System-Info Footer */ }
				<div
					style={ {
						padding: '0.75rem 1rem',
						backgroundColor: '#f9fafb',
						borderTop: '1px solid #e5e7eb',
						display: 'flex',
						gap: '2rem',
						fontSize: '0.75rem',
						color: '#6b7280',
					} }
				>
					<span>Plugin: v{ status?.plugin_version }</span>
					<span>PHP: { status?.php_version }</span>
					<span>WordPress: { status?.wp_version }</span>
				</div>
			</CardContent>
		</Card>
	);
}

export default SystemStatus;
