/**
 * Export & Import Settings Component
 *
 * Backup/Export + Import-Funktionalität
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Alert, AlertDescription } from '../../components/ui/alert';

/**
 * Import Result Table
 *
 * @param {Object} props Props.
 * @param {Object} props.result Import-Ergebnis.
 * @param {Object} props.i18n I18n-Strings.
 * @return {JSX.Element} Component
 */
function ImportResultTable( { result, i18n } ) {
	const { counts = {}, warnings = [], errors = [] } = result;
	const typeLabels = {
		settings: i18n.settingsExport || __( 'Settings', 'recruiting-playbook' ),
		jobs: i18n.jobsExport || __( 'Jobs', 'recruiting-playbook' ),
		taxonomies: i18n.taxonomiesExport || __( 'Taxonomies', 'recruiting-playbook' ),
		candidates: i18n.candidatesExport || __( 'Candidates', 'recruiting-playbook' ),
		applications: i18n.applicationsExport || __( 'Applications', 'recruiting-playbook' ),
		documents: i18n.documentsExport || __( 'Documents', 'recruiting-playbook' ),
		notes: i18n.notesExport || __( 'Notes', 'recruiting-playbook' ),
		ratings: i18n.ratingsExport || __( 'Ratings', 'recruiting-playbook' ),
		talent_pool: i18n.talentPoolExport || __( 'Talent Pool', 'recruiting-playbook' ),
		email_templates: i18n.emailTemplatesExport || __( 'Email templates', 'recruiting-playbook' ),
		signatures: i18n.signaturesExport || __( 'Signatures', 'recruiting-playbook' ),
		field_definitions: i18n.fieldDefinitionsExport || __( 'Field definitions', 'recruiting-playbook' ),
		form_templates: i18n.formTemplatesExport || __( 'Form templates', 'recruiting-playbook' ),
		form_config: i18n.formConfigExport || __( 'Form configuration', 'recruiting-playbook' ),
		webhooks: i18n.webhooksExport || __( 'Webhooks', 'recruiting-playbook' ),
		job_assignments: i18n.jobAssignmentsExport || __( 'Job assignments', 'recruiting-playbook' ),
		activity_log: i18n.activityLogExport || __( 'Activity log', 'recruiting-playbook' ),
		email_log: i18n.emailLogExport || __( 'Email log', 'recruiting-playbook' ),
		ai_analyses: i18n.aiAnalysesExport || __( 'AI analyses', 'recruiting-playbook' ),
	};

	const hasData = Object.keys( counts ).length > 0;
	const hasErrors = errors.length > 0;

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
			{ /* Fehler */ }
			{ hasErrors && (
				<Alert style={ { backgroundColor: '#fef2f2', borderColor: '#ef4444' } }>
					<AlertDescription style={ { color: '#991b1b' } }>
						<strong>{ i18n.importErrors || __( 'Errors', 'recruiting-playbook' ) }:</strong>
						<ul style={ { margin: '0.5rem 0 0', paddingLeft: '1.25rem' } }>
							{ errors.map( ( err, idx ) => (
								<li key={ idx }>{ err }</li>
							) ) }
						</ul>
					</AlertDescription>
				</Alert>
			) }

			{ /* Ergebnis-Tabelle */ }
			{ hasData && (
				<>
					{ ! hasErrors && (
						<Alert style={ { backgroundColor: '#e6f5ec', borderColor: '#2fac66' } }>
							<AlertDescription>
								{ i18n.importSuccess || __( 'Import completed successfully.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					) }
					<table className="widefat striped" style={ { fontSize: '0.875rem' } }>
						<thead>
							<tr>
								<th>{ i18n.importType || __( 'Type', 'recruiting-playbook' ) }</th>
								<th style={ { textAlign: 'center' } }>{ i18n.importCreated || __( 'Created', 'recruiting-playbook' ) }</th>
								<th style={ { textAlign: 'center' } }>{ i18n.importSkipped || __( 'Skipped', 'recruiting-playbook' ) }</th>
								<th style={ { textAlign: 'center' } }>{ i18n.importUpdated || __( 'Updated', 'recruiting-playbook' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ Object.entries( counts ).map( ( [ type, stats ] ) => (
								<tr key={ type }>
									<td>{ typeLabels[ type ] || type }</td>
									<td style={ { textAlign: 'center' } }>{ stats.created || 0 }</td>
									<td style={ { textAlign: 'center' } }>{ stats.skipped || 0 }</td>
									<td style={ { textAlign: 'center' } }>{ stats.updated || 0 }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</>
			) }

			{ /* Warnungen */ }
			{ warnings.length > 0 && (
				<Alert style={ { backgroundColor: '#fef3c7', borderColor: '#f59e0b' } }>
					<AlertDescription style={ { color: '#92400e' } }>
						<strong>{ i18n.importWarnings || __( 'Warnings', 'recruiting-playbook' ) }:</strong>
						<ul style={ { margin: '0.5rem 0 0', paddingLeft: '1.25rem' } }>
							{ warnings.map( ( w, idx ) => (
								<li key={ idx }>{ w }</li>
							) ) }
						</ul>
					</AlertDescription>
				</Alert>
			) }
		</div>
	);
}

/**
 * ExportSettings Component
 *
 * @return {JSX.Element} Component
 */
export function ExportSettings() {
	const [ downloading, setDownloading ] = useState( false );
	const [ success, setSuccess ] = useState( false );

	const config = window.rpSettingsData || {};
	const i18n = config.i18n || {};
	const exportUrl = config.exportUrl || '';
	const nonce = config.nonce || '';
	const importNonce = config.importNonce || '';
	const serverImportResult = config.importResult || null;

	const [ importResult, setImportResult ] = useState( serverImportResult );

	// Server-Result übernehmen (nach Redirect).
	useEffect( () => {
		if ( serverImportResult ) {
			setImportResult( serverImportResult );
		}
	}, [ serverImportResult ] );

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
		i18n.notesExport || __( 'Notes', 'recruiting-playbook' ),
		i18n.ratingsExport || __( 'Ratings', 'recruiting-playbook' ),
		i18n.talentPoolExport || __( 'Talent Pool', 'recruiting-playbook' ),
		i18n.emailTemplatesExport || __( 'Email templates', 'recruiting-playbook' ),
		i18n.signaturesExport || __( 'Email signatures', 'recruiting-playbook' ),
		i18n.fieldDefinitionsExport || __( 'Field definitions', 'recruiting-playbook' ),
		i18n.formTemplatesExport || __( 'Form templates', 'recruiting-playbook' ),
		i18n.formConfigExport || __( 'Form configuration', 'recruiting-playbook' ),
		i18n.webhooksExport || __( 'Webhooks', 'recruiting-playbook' ),
		i18n.jobAssignmentsExport || __( 'Job assignments', 'recruiting-playbook' ),
		i18n.emailLogExport || __( 'Email log (last 5000 entries)', 'recruiting-playbook' ),
		i18n.aiAnalysesExport || __( 'AI analyses', 'recruiting-playbook' ),
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

			{ /* Export Card */ }
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

			{ /* Import Card */ }
			<Card>
				<CardHeader>
					<CardTitle>{ i18n.importBackup || __( 'Import Backup', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ i18n.importBackupDesc || __( 'Restore plugin data from a JSON backup file', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent>
					{ /* Import-Ergebnis anzeigen */ }
					{ importResult && (
						<div style={ { marginBottom: '1.5rem' } }>
							<h4 style={ { margin: '0 0 0.75rem', fontWeight: 600, color: '#374151' } }>
								{ i18n.importResult || __( 'Import Result', 'recruiting-playbook' ) }
							</h4>
							<ImportResultTable result={ importResult } i18n={ i18n } />
						</div>
					) }

					<form
						method="post"
						action={ exportUrl }
						encType="multipart/form-data"
					>
						<input type="hidden" name="_wpnonce" value={ importNonce } />
						<input type="hidden" name="upload_backup" value="1" />

						<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							{ /* Datei-Upload */ }
							<div>
								<label
									htmlFor="backup_file"
									style={ { display: 'block', fontWeight: 500, marginBottom: '0.5rem', color: '#374151' } }
								>
									{ i18n.selectFile || __( 'Select backup file', 'recruiting-playbook' ) }
								</label>
								<input
									type="file"
									id="backup_file"
									name="backup_file"
									accept=".json"
									required
									style={ { display: 'block' } }
								/>
							</div>

							{ /* Optionen */ }
							<div style={ {
								backgroundColor: '#f9fafb',
								borderRadius: '8px',
								padding: '1rem',
								display: 'flex',
								flexDirection: 'column',
								gap: '0.75rem',
							} }>
								{ /* Settings-Modus */ }
								<div style={ { display: 'flex', alignItems: 'center', gap: '1rem' } }>
									<label htmlFor="settings_mode" style={ { minWidth: '200px', fontWeight: 500, color: '#374151' } }>
										{ i18n.settingsMode || __( 'Settings', 'recruiting-playbook' ) }
									</label>
									<select id="settings_mode" name="settings_mode" style={ { flex: 1 } }>
										<option value="skip">{ i18n.settingsModeSkip || __( 'Skip (keep current)', 'recruiting-playbook' ) }</option>
										<option value="merge">{ i18n.settingsModeMerge || __( 'Merge (add missing)', 'recruiting-playbook' ) }</option>
										<option value="overwrite">{ i18n.settingsModeOverwrite || __( 'Overwrite', 'recruiting-playbook' ) }</option>
									</select>
								</div>

								{ /* Kandidaten-Duplikate */ }
								<div style={ { display: 'flex', alignItems: 'center', gap: '1rem' } }>
									<label htmlFor="duplicate_candidates" style={ { minWidth: '200px', fontWeight: 500, color: '#374151' } }>
										{ i18n.duplicateCandidates || __( 'Duplicate candidates', 'recruiting-playbook' ) }
									</label>
									<select id="duplicate_candidates" name="duplicate_candidates" style={ { flex: 1 } }>
										<option value="skip">{ i18n.duplicateSkip || __( 'Skip', 'recruiting-playbook' ) }</option>
										<option value="update">{ i18n.duplicateUpdate || __( 'Update existing', 'recruiting-playbook' ) }</option>
									</select>
								</div>

								{ /* Jobs-Duplikate */ }
								<div style={ { display: 'flex', alignItems: 'center', gap: '1rem' } }>
									<label htmlFor="duplicate_jobs" style={ { minWidth: '200px', fontWeight: 500, color: '#374151' } }>
										{ i18n.duplicateJobs || __( 'Duplicate jobs', 'recruiting-playbook' ) }
									</label>
									<select id="duplicate_jobs" name="duplicate_jobs" style={ { flex: 1 } }>
										<option value="skip">{ i18n.duplicateSkip || __( 'Skip', 'recruiting-playbook' ) }</option>
										<option value="create">{ __( 'Create new', 'recruiting-playbook' ) }</option>
									</select>
								</div>

								{ /* Checkboxen */ }
								<div style={ { display: 'flex', gap: '2rem', marginTop: '0.25rem' } }>
									<label style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#374151' } }>
										<input type="checkbox" name="import_activity_log" value="1" />
										{ i18n.importActivityLog || __( 'Import activity log', 'recruiting-playbook' ) }
									</label>
									<label style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#374151' } }>
										<input type="checkbox" name="import_email_log" value="1" />
										{ i18n.importEmailLog || __( 'Import email log', 'recruiting-playbook' ) }
									</label>
								</div>
							</div>

							{ /* Warnung */ }
							<Alert style={ { backgroundColor: '#fef3c7', borderColor: '#f59e0b' } }>
								<AlertDescription style={ { color: '#92400e' } }>
									<strong>{ i18n.note || __( 'Note:', 'recruiting-playbook' ) }</strong>{ ' ' }
									{ i18n.importWarning || __( 'It is recommended to create a backup before importing.', 'recruiting-playbook' ) }
								</AlertDescription>
							</Alert>

							{ /* Submit Button */ }
							<div>
								<Button type="submit">
									{ i18n.startImport || __( 'Start Import', 'recruiting-playbook' ) }
								</Button>
							</div>
						</div>
					</form>
				</CardContent>
			</Card>
		</div>
	);
}

export default ExportSettings;
