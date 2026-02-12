/**
 * Export Settings Component
 *
 * Backup/Export-Funktionalität
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Alert, AlertDescription } from '../../components/ui/alert';

/**
 * ExportSettings Component
 *
 * @return {JSX.Element} Component
 */
export function ExportSettings() {
	const [ downloading, setDownloading ] = useState( false );
	const [ success, setSuccess ] = useState( false );

	const i18n = window.rpSettingsData?.i18n || {};
	const exportUrl = window.rpSettingsData?.exportUrl || '';
	const nonce = window.rpSettingsData?.nonce || '';

	const handleDownload = async () => {
		setDownloading( true );
		setSuccess( false );

		// Create a hidden form and submit it to trigger the download
		const form = document.createElement( 'form' );
		form.method = 'POST';
		form.action = exportUrl;

		// Add nonce
		const nonceInput = document.createElement( 'input' );
		nonceInput.type = 'hidden';
		nonceInput.name = '_wpnonce';
		nonceInput.value = nonce;
		form.appendChild( nonceInput );

		// Add action
		const actionInput = document.createElement( 'input' );
		actionInput.type = 'hidden';
		actionInput.name = 'download_backup';
		actionInput.value = '1';
		form.appendChild( actionInput );

		document.body.appendChild( form );
		form.submit();
		document.body.removeChild( form );

		// Show success after a short delay
		setTimeout( () => {
			setDownloading( false );
			setSuccess( true );
			setTimeout( () => setSuccess( false ), 3000 );
		}, 1000 );
	};

	const exportItems = [
		i18n.settingsExport || __( 'Settings', 'recruiting-playbook' ),
		i18n.jobsExport || __( 'Jobs (including metadata)', 'recruiting-playbook' ),
		i18n.taxonomiesExport || __( 'Taxonomies (categories, locations, etc.)', 'recruiting-playbook' ),
		i18n.candidatesExport || __( 'Candidates', 'recruiting-playbook' ),
		i18n.applicationsExport || __( 'Applications', 'recruiting-playbook' ),
		i18n.documentsExport || __( 'Document metadata', 'recruiting-playbook' ),
		i18n.activityLogExport || __( 'Activity log (last 1000 entries)', 'recruiting-playbook' ),
	];

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
			{ success && (
				<Alert style={ { backgroundColor: '#e6f5ec', borderColor: '#2fac66' } }>
					<AlertDescription>
						{ i18n.downloadStarted || __( 'Download started.', 'recruiting-playbook' ) }
					</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<CardTitle>{ i18n.fullBackup || __( 'Full Backup', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ i18n.fullBackupDesc || __( 'Export all plugin data as JSON file', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
						{ /* Export-Inhalt Liste */ }
						<div style={ {
							backgroundColor: '#f9fafb',
							borderRadius: '8px',
							padding: '1rem',
						} }>
							<p style={ { fontWeight: 500, marginBottom: '0.75rem', color: '#374151' } }>
								{ i18n.exportIncludes || __( 'The export includes:', 'recruiting-playbook' ) }
							</p>
							<ul style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(2, 1fr)',
								gap: '0.5rem',
								listStyle: 'disc',
								margin: 0,
								paddingLeft: '1.25rem',
							} }>
								{ exportItems.map( ( item, index ) => (
									<li
										key={ index }
										style={ {
											fontSize: '0.875rem',
											color: '#4b5563',
										} }
									>
										{ item }
									</li>
								) ) }
							</ul>
						</div>

						{ /* Warnung */ }
						<Alert style={ { backgroundColor: '#fef3c7', borderColor: '#f59e0b' } }>
							<AlertDescription style={ { color: '#92400e' } }>
								<strong>{ i18n.note || __( 'Note:', 'recruiting-playbook' ) }</strong>{ ' ' }
								{ i18n.documentsNotIncluded || __( 'Uploaded documents (PDFs etc.) are not exported for privacy reasons.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>

						{ /* Download Button */ }
						<div>
							<Button
								onClick={ handleDownload }
								disabled={ downloading }
							>
								{ downloading
									? ( i18n.preparing || __( 'Preparing...', 'recruiting-playbook' ) )
									: ( i18n.downloadBackup || __( 'Download Backup', 'recruiting-playbook' ) )
								}
							</Button>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default ExportSettings;
