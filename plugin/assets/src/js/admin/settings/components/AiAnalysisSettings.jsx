/**
 * AiAnalysisSettings Component
 *
 * AI Analysis tab in Settings with license, usage,
 * health check, settings and analysis history.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Badge } from '../../components/ui/badge';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Switch } from '../../components/ui/switch';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '../../components/ui/table';
import { Spinner } from '../../components/ui/spinner';

import { useAiAnalysis, useSettings } from '../hooks';

/**
 * Progress bar
 */
function ProgressBar( { percentage, warningThreshold } ) {
	const color = percentage >= warningThreshold ? '#ef4444' : '#22c55e';

	return (
		<div style={ { width: '100%', height: '8px', backgroundColor: '#e5e7eb', borderRadius: '4px', overflow: 'hidden' } }>
			<div style={ {
				width: `${ Math.min( 100, percentage ) }%`,
				height: '100%',
				backgroundColor: color,
				borderRadius: '4px',
				transition: 'width 0.3s ease',
			} } />
		</div>
	);
}

/**
 * Score badge by category
 */
function ScoreBadge( { score, category } ) {
	if ( score === null || score === undefined ) {
		return <span style={ { color: '#9ca3af' } }>—</span>;
	}

	const colors = {
		high: { bg: '#dcfce7', color: '#166534' },
		medium: { bg: '#fef9c3', color: '#854d0e' },
		low: { bg: '#fecaca', color: '#991b1b' },
	};
	const c = colors[ category ] || colors.medium;

	return (
		<Badge style={ { backgroundColor: c.bg, color: c.color, border: 'none' } }>
			{ score }%
		</Badge>
	);
}

/**
 * Status badge
 */
function StatusBadge( { status } ) {
	const map = {
		completed: { label: __( 'Completed', 'recruiting-playbook' ), bg: '#dcfce7', color: '#166534' },
		pending: { label: __( 'Pending', 'recruiting-playbook' ), bg: '#fef9c3', color: '#854d0e' },
		failed: { label: __( 'Failed', 'recruiting-playbook' ), bg: '#fecaca', color: '#991b1b' },
	};
	const s = map[ status ] || map.pending;

	return (
		<Badge style={ { backgroundColor: s.bg, color: s.color, border: 'none' } }>
			{ s.label }
		</Badge>
	);
}

/**
 * Type label
 */
function TypeLabel( { type } ) {
	const labels = {
		job_match: __( 'Job-Match', 'recruiting-playbook' ),
		job_finder: __( 'Job-Finder', 'recruiting-playbook' ),
	};
	return <>{ labels[ type ] || type }</>;
}

/**
 * Format date
 */
function formatDate( dateStr ) {
	if ( ! dateStr ) {
		return '—';
	}
	const d = new Date( dateStr );
	return d.toLocaleDateString( 'de-DE', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	} );
}

/**
 * AiAnalysisSettings Component
 *
 * @return {JSX.Element} Component
 */
export function AiAnalysisSettings() {
	const {
		stats,
		health,
		settings,
		history,
		historyFilters,
		historyPage,
		loading,
		saving,
		healthLoading,
		error,
		setError,
		setHistoryPage,
		setHistoryFilters,
		fetchHealth,
		saveSettings,
	} = useAiAnalysis();

	// Global plugin settings (for disable_ai_features toggle).
	const {
		settings: globalSettings,
		saveSettings: saveGlobalSettings,
		loading: globalLoading,
	} = useSettings();

	// Local state for settings form.
	const [ localSettings, setLocalSettings ] = useState( null );
	const [ settingsNotification, setSettingsNotification ] = useState( null );
	const [ disableToggleSaving, setDisableToggleSaving ] = useState( false );

	// Initialize localSettings when settings loaded.
	if ( settings && ! localSettings ) {
		setLocalSettings( { ...settings } );
	}

	/**
	 * Save settings
	 */
	const handleSaveSettings = useCallback( async () => {
		if ( ! localSettings ) {
			return;
		}
		setError( null );
		const success = await saveSettings( localSettings );
		if ( success ) {
			setSettingsNotification( __( 'Settings saved.', 'recruiting-playbook' ) );
			setTimeout( () => setSettingsNotification( null ), 3000 );
		}
	}, [ localSettings, saveSettings, setError ] );

	/**
	 * Update local setting
	 */
	const updateLocalSetting = useCallback( ( key, value ) => {
		setLocalSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	/**
	 * Toggle file format checkbox
	 */
	const toggleFileType = useCallback( ( type ) => {
		setLocalSettings( ( prev ) => {
			const types = prev.allowed_file_types || [];
			const updated = types.includes( type )
				? types.filter( ( t ) => t !== type )
				: [ ...types, type ];
			return { ...prev, allowed_file_types: updated };
		} );
	}, [] );

	/**
	 * Toggle AI features on/off
	 */
	const handleToggleAiFeatures = useCallback( async ( enabled ) => {
		setDisableToggleSaving( true );
		const success = await saveGlobalSettings( { disable_ai_features: ! enabled } );
		setDisableToggleSaving( false );

		if ( success ) {
			setSettingsNotification(
				enabled
					? __( 'AI features enabled.', 'recruiting-playbook' )
					: __( 'AI features disabled.', 'recruiting-playbook' )
			);
			setTimeout( () => setSettingsNotification( null ), 3000 );
		}
	}, [ saveGlobalSettings ] );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	if ( error ) {
		return (
			<Alert variant="destructive" style={ { marginTop: '1rem' } }>
				<AlertDescription>{ error }</AlertDescription>
			</Alert>
		);
	}

	const usage = stats?.usage || {};
	const license = stats?.license || {};
	const warningThreshold = localSettings?.warning_threshold ?? settings?.warning_threshold ?? 80;
	const aiDisabled = globalSettings?.disable_ai_features ?? false;

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>

			{ /* Card 0: Enable/Disable AI Features */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'AI Features', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Enable or disable AI matching features on your website.', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<div>
							<Label htmlFor="ai-features-toggle" style={ { fontWeight: 500 } }>
								{ __( 'Show AI matching buttons', 'recruiting-playbook' ) }
							</Label>
							<p style={ { margin: '0.25rem 0 0', fontSize: '0.8125rem', color: '#6b7280' } }>
								{ __( 'When disabled, AI buttons will be hidden in job listings and cards.', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="ai-features-toggle"
							checked={ ! aiDisabled }
							onCheckedChange={ handleToggleAiFeatures }
							disabled={ globalLoading || disableToggleSaving }
						/>
					</div>
				</CardContent>
			</Card>

			{ /* Card 1: License & Usage */ }
			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<CardTitle>{ __( 'License & Usage', 'recruiting-playbook' ) }</CardTitle>
						<Badge style={ license.active
							? { backgroundColor: '#dcfce7', color: '#166534', border: 'none' }
							: { backgroundColor: '#fecaca', color: '#991b1b', border: 'none' }
						}>
							{ license.active ? __( 'Active', 'recruiting-playbook' ) : __( 'Inactive', 'recruiting-playbook' ) }
						</Badge>
					</div>
				</CardHeader>
				<CardContent>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.75rem' } }>
						<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.875rem' } }>
							<span>{ usage.current_month || 0 } / { usage.limit || 100 } { __( 'analyses', 'recruiting-playbook' ) }</span>
							<span style={ { color: '#6b7280' } }>{ usage.percentage || 0 }%</span>
						</div>
						<ProgressBar percentage={ usage.percentage || 0 } warningThreshold={ warningThreshold } />
						{ usage.percentage >= warningThreshold && (
							<Alert variant="destructive" style={ { marginTop: '0.5rem' } }>
								<AlertDescription>
									{ __( 'Warning: You have already used', 'recruiting-playbook' ) } { usage.percentage }% { __( 'of your monthly budget.', 'recruiting-playbook' ) }
								</AlertDescription>
							</Alert>
						) }
						<p style={ { margin: 0, fontSize: '0.8125rem', color: '#6b7280' } }>
							{ __( 'Next reset:', 'recruiting-playbook' ) } { usage.reset_date ? new Date( usage.reset_date ).toLocaleDateString( 'de-DE' ) : '—' }
						</p>
					</div>
				</CardContent>
			</Card>

			{ /* Card 2: API Status */ }
			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<CardTitle>{ __( 'API Status', 'recruiting-playbook' ) }</CardTitle>
						<Button
							variant="outline"
							size="sm"
							onClick={ fetchHealth }
							disabled={ healthLoading }
						>
							{ healthLoading ? <Spinner size="sm" /> : __( 'Check now', 'recruiting-playbook' ) }
						</Button>
					</div>
				</CardHeader>
				<CardContent>
					{ health ? (
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem', fontSize: '0.875rem' } }>
							<div style={ { display: 'flex', justifyContent: 'space-between' } }>
								<span>{ __( 'Worker', 'recruiting-playbook' ) }</span>
								<Badge style={ health.reachable
									? { backgroundColor: '#dcfce7', color: '#166534', border: 'none' }
									: { backgroundColor: '#fecaca', color: '#991b1b', border: 'none' }
								}>
									{ health.reachable ? __( 'Reachable', 'recruiting-playbook' ) : __( 'Error', 'recruiting-playbook' ) }
								</Badge>
							</div>
							<div style={ { display: 'flex', justifyContent: 'space-between' } }>
								<span>{ __( 'Response time', 'recruiting-playbook' ) }</span>
								<span>{ health.response_time_ms } ms</span>
							</div>
							<div style={ { display: 'flex', justifyContent: 'space-between' } }>
								<span>{ __( 'Last check', 'recruiting-playbook' ) }</span>
								<span>{ formatDate( health.checked_at ) }</span>
							</div>
						</div>
					) : (
						<p style={ { margin: 0, fontSize: '0.875rem', color: '#6b7280' } }>
							{ __( 'No health check performed yet. Click "Check now".', 'recruiting-playbook' ) }
						</p>
					) }
				</CardContent>
			</Card>

			{ /* Card 3: Settings */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Settings', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>{ __( 'Budget and file settings for AI analysis.', 'recruiting-playbook' ) }</CardDescription>
				</CardHeader>
				<CardContent>
					{ settingsNotification && (
						<Alert style={ { marginBottom: '1rem', backgroundColor: '#e6f5ec', borderColor: '#2fac66' } }>
							<AlertDescription>{ settingsNotification }</AlertDescription>
						</Alert>
					) }

					{ localSettings && (
						<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
								<div>
									<Label htmlFor="budget_limit">{ __( 'Budget limit per month', 'recruiting-playbook' ) }</Label>
									<Input
										id="budget_limit"
										type="number"
										min="0"
										value={ localSettings.budget_limit }
										onChange={ ( e ) => updateLocalSetting( 'budget_limit', parseInt( e.target.value, 10 ) || 0 ) }
										style={ { marginTop: '0.25rem' } }
									/>
									<p style={ { margin: '0.25rem 0 0', fontSize: '0.75rem', color: '#6b7280' } }>
										{ __( '0 = no limit', 'recruiting-playbook' ) }
									</p>
								</div>

								<div>
									<Label htmlFor="warning_threshold">{ __( 'Warning threshold (%)', 'recruiting-playbook' ) }</Label>
									<Input
										id="warning_threshold"
										type="number"
										min="0"
										max="100"
										value={ localSettings.warning_threshold }
										onChange={ ( e ) => updateLocalSetting( 'warning_threshold', parseInt( e.target.value, 10 ) || 0 ) }
										style={ { marginTop: '0.25rem' } }
									/>
								</div>
							</div>

							<div>
								<Label>{ __( 'Allowed file formats', 'recruiting-playbook' ) }</Label>
								<div style={ { display: 'flex', gap: '1rem', marginTop: '0.5rem' } }>
									{ [ 'pdf', 'docx', 'jpg', 'png' ].map( ( type ) => (
										<label key={ type } style={ { display: 'flex', alignItems: 'center', gap: '0.375rem', cursor: 'pointer' } }>
											<input
												type="checkbox"
												checked={ ( localSettings.allowed_file_types || [] ).includes( type ) }
												onChange={ () => toggleFileType( type ) }
											/>
											{ type.toUpperCase() }
										</label>
									) ) }
								</div>
							</div>

							<div style={ { maxWidth: '200px' } }>
								<Label htmlFor="max_file_size">{ __( 'Max. file size (MB)', 'recruiting-playbook' ) }</Label>
								<Input
									id="max_file_size"
									type="number"
									min="1"
									max="50"
									value={ localSettings.max_file_size }
									onChange={ ( e ) => updateLocalSetting( 'max_file_size', parseInt( e.target.value, 10 ) || 1 ) }
									style={ { marginTop: '0.25rem' } }
								/>
							</div>

							<div>
								<Button
									onClick={ handleSaveSettings }
									disabled={ saving }
								>
									{ saving ? __( 'Saving...', 'recruiting-playbook' ) : __( 'Save', 'recruiting-playbook' ) }
								</Button>
							</div>
						</div>
					) }
				</CardContent>
			</Card>

			{ /* Card 4: Analysis History */ }
			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem' } }>
						<CardTitle>{ __( 'Analysis History', 'recruiting-playbook' ) }</CardTitle>
						<div style={ { display: 'flex', gap: '0.5rem' } }>
							<select
								value={ historyFilters.type }
								onChange={ ( e ) => {
									setHistoryFilters( ( prev ) => ( { ...prev, type: e.target.value } ) );
									setHistoryPage( 1 );
								} }
								style={ { padding: '0.375rem 0.5rem', borderRadius: '6px', border: '1px solid #d1d5db', fontSize: '0.8125rem' } }
							>
								<option value="">{ __( 'All types', 'recruiting-playbook' ) }</option>
								<option value="job_match">{ __( 'Job-Match', 'recruiting-playbook' ) }</option>
								<option value="job_finder">{ __( 'Job-Finder', 'recruiting-playbook' ) }</option>
							</select>
							<select
								value={ historyFilters.status }
								onChange={ ( e ) => {
									setHistoryFilters( ( prev ) => ( { ...prev, status: e.target.value } ) );
									setHistoryPage( 1 );
								} }
								style={ { padding: '0.375rem 0.5rem', borderRadius: '6px', border: '1px solid #d1d5db', fontSize: '0.8125rem' } }
							>
								<option value="">{ __( 'All statuses', 'recruiting-playbook' ) }</option>
								<option value="completed">{ __( 'Completed', 'recruiting-playbook' ) }</option>
								<option value="pending">{ __( 'Pending', 'recruiting-playbook' ) }</option>
								<option value="failed">{ __( 'Failed', 'recruiting-playbook' ) }</option>
							</select>
						</div>
					</div>
				</CardHeader>
				<CardContent>
					{ history.items.length === 0 ? (
						<p style={ { margin: 0, fontSize: '0.875rem', color: '#6b7280', textAlign: 'center', padding: '2rem 0' } }>
							{ __( 'No analyses available yet.', 'recruiting-playbook' ) }
						</p>
					) : (
						<>
							<Table>
								<TableHeader>
									<TableRow>
										<TableHead>{ __( 'Date', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Type', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Job', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Score', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Status', 'recruiting-playbook' ) }</TableHead>
									</TableRow>
								</TableHeader>
								<TableBody>
									{ history.items.map( ( item ) => (
										<TableRow key={ item.id }>
											<TableCell style={ { whiteSpace: 'nowrap' } }>{ formatDate( item.created_at ) }</TableCell>
											<TableCell><TypeLabel type={ item.analysis_type } /></TableCell>
											<TableCell style={ { maxWidth: '250px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>
												{ item.job_title || '—' }
											</TableCell>
											<TableCell>
												<ScoreBadge score={ item.score } category={ item.category } />
											</TableCell>
											<TableCell>
												<StatusBadge status={ item.status } />
											</TableCell>
										</TableRow>
									) ) }
								</TableBody>
							</Table>

							{ /* Pagination */ }
							{ history.pages > 1 && (
								<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '1rem', fontSize: '0.875rem' } }>
									<span style={ { color: '#6b7280' } }>
										{ __( 'Page', 'recruiting-playbook' ) } { history.page } { __( 'of', 'recruiting-playbook' ) } { history.pages }
									</span>
									<div style={ { display: 'flex', gap: '0.5rem' } }>
										<Button
											variant="outline"
											size="sm"
											disabled={ historyPage <= 1 }
											onClick={ () => setHistoryPage( ( prev ) => Math.max( 1, prev - 1 ) ) }
										>
											{ __( 'Previous', 'recruiting-playbook' ) }
										</Button>
										<Button
											variant="outline"
											size="sm"
											disabled={ historyPage >= history.pages }
											onClick={ () => setHistoryPage( ( prev ) => prev + 1 ) }
										>
											{ __( 'Next', 'recruiting-playbook' ) }
										</Button>
									</div>
								</div>
							) }
						</>
					) }
				</CardContent>
			</Card>
		</div>
	);
}

export default AiAnalysisSettings;
