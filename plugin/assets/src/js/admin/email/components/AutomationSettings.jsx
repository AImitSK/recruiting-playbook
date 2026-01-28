/**
 * AutomationSettings - Einstellungen für automatische E-Mails
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import PropTypes from 'prop-types';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Select, SelectOption } from '../../components/ui/select';
import { Switch } from '../../components/ui/switch';
import { Label } from '../../components/ui/label';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';

/**
 * AutomationSettings Komponente
 *
 * @param {Object}   props           Props
 * @param {Array}    props.templates Verfügbare Templates
 * @return {JSX.Element} Komponente
 */
export function AutomationSettings( { templates = [] } ) {
	const [ settings, setSettings ] = useState( {} );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( false );

	const i18n = window.rpEmailData?.i18n || {};

	// Verfügbare Status für automatische E-Mails
	const statuses = [
		{ key: 'new', label: i18n.statusNew || 'Neue Bewerbung (Eingangsbestätigung)' },
		{ key: 'rejected', label: i18n.statusRejected || 'Abgelehnt (Absage)' },
		{ key: 'withdrawn', label: i18n.statusWithdrawn || 'Zurückgezogen' },
	];

	// Verzögerungsoptionen
	const delayOptions = [
		{ value: 0, label: i18n.immediately || 'Sofort' },
		{ value: 5, label: '5 ' + ( i18n.minutes || 'Minuten' ) },
		{ value: 15, label: '15 ' + ( i18n.minutes || 'Minuten' ) },
		{ value: 30, label: '30 ' + ( i18n.minutes || 'Minuten' ) },
		{ value: 60, label: '1 ' + ( i18n.hour || 'Stunde' ) },
		{ value: 120, label: '2 ' + ( i18n.hours || 'Stunden' ) },
		{ value: 1440, label: '24 ' + ( i18n.hours || 'Stunden' ) },
	];

	// Einstellungen laden
	useEffect( () => {
		const fetchSettings = async () => {
			try {
				setLoading( true );
				const data = await apiFetch( {
					path: '/recruiting/v1/settings/auto-email',
				} );
				setSettings( data.settings || data || {} );
			} catch ( err ) {
				console.error( 'Error fetching auto-email settings:', err );
				// Fallback: leere Einstellungen
				setSettings( {} );
			} finally {
				setLoading( false );
			}
		};

		fetchSettings();
	}, [] );

	/**
	 * Einstellung für einen Status aktualisieren
	 */
	const updateSetting = useCallback( ( statusKey, field, value ) => {
		setSettings( ( prev ) => ( {
			...prev,
			[ statusKey ]: {
				...( prev[ statusKey ] || {} ),
				[ field ]: value,
			},
		} ) );
		setSuccess( false );
	}, [] );

	/**
	 * Einstellungen speichern
	 */
	const handleSave = useCallback( async () => {
		try {
			setSaving( true );
			setError( null );
			setSuccess( false );

			await apiFetch( {
				path: '/recruiting/v1/settings/auto-email',
				method: 'POST',
				data: { settings },
			} );

			setSuccess( true );
			setTimeout( () => setSuccess( false ), 3000 );
		} catch ( err ) {
			console.error( 'Error saving auto-email settings:', err );
			setError( i18n.errorSaving || 'Fehler beim Speichern' );
		} finally {
			setSaving( false );
		}
	}, [ settings, i18n.errorSaving ] );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div className="rp-automation-settings">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			{ success && (
				<Alert style={ { marginBottom: '1rem', backgroundColor: '#e6f5ec', borderColor: '#2fac66' } }>
					<AlertDescription>{ i18n.settingsSaved || 'Einstellungen wurden gespeichert.' }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<CardTitle>{ i18n.autoEmailSettings || 'Automatische E-Mails bei Status-Änderungen' }</CardTitle>
				</CardHeader>

				<CardContent>
					<p style={ { color: '#6b7280', marginBottom: '1.5rem' } }>
						{ i18n.autoEmailDescription || 'Konfigurieren Sie, welche E-Mails automatisch gesendet werden, wenn sich der Status einer Bewerbung ändert.' }
					</p>

					<table style={ { width: '100%', borderCollapse: 'collapse' } }>
						<thead>
							<tr style={ { borderBottom: '2px solid #e5e7eb' } }>
								<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 600, width: '60px' } }>
									{ i18n.active || 'Aktiv' }
								</th>
								<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 600 } }>
									{ i18n.status || 'Bei Status' }
								</th>
								<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 600 } }>
									{ i18n.template || 'E-Mail-Template' }
								</th>
								<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 600, width: '180px' } }>
									{ i18n.delay || 'Verzögerung' }
								</th>
							</tr>
						</thead>
						<tbody>
							{ statuses.map( ( status ) => {
								const statusSettings = settings[ status.key ] || {};
								return (
									<tr key={ status.key } style={ { borderBottom: '1px solid #e5e7eb' } }>
										<td style={ { padding: '0.75rem' } }>
											<Switch
												checked={ statusSettings.enabled || false }
												onCheckedChange={ ( value ) => updateSetting( status.key, 'enabled', value ) }
											/>
										</td>
										<td style={ { padding: '0.75rem' } }>
											<Label style={ { fontWeight: 500, marginBottom: 0 } }>{ status.label }</Label>
										</td>
										<td style={ { padding: '0.75rem' } }>
											<Select
												value={ statusSettings.template_id || '' }
												onChange={ ( e ) => updateSetting( status.key, 'template_id', e.target.value ) }
												disabled={ ! statusSettings.enabled }
												style={ { width: '100%' } }
											>
												<SelectOption value="">{ i18n.noTemplate || '— Kein Template —' }</SelectOption>
												{ templates.map( ( template ) => (
													<SelectOption key={ template.id } value={ template.id }>
														{ template.name } ({ template.subject })
													</SelectOption>
												) ) }
											</Select>
										</td>
										<td style={ { padding: '0.75rem' } }>
											<Select
												value={ statusSettings.delay || 0 }
												onChange={ ( e ) => updateSetting( status.key, 'delay', parseInt( e.target.value, 10 ) ) }
												disabled={ ! statusSettings.enabled }
												style={ { width: '100%' } }
											>
												{ delayOptions.map( ( option ) => (
													<SelectOption key={ option.value } value={ option.value }>
														{ option.label }
													</SelectOption>
												) ) }
											</Select>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</table>

					<div style={ { marginTop: '1.5rem', paddingTop: '1.5rem', borderTop: '1px solid #e5e7eb', display: 'flex', justifyContent: 'flex-end' } }>
						<Button onClick={ handleSave } disabled={ saving }>
							{ saving ? (
								<>
									<Spinner size="sm" style={ { marginRight: '0.5rem' } } />
									{ i18n.saving || 'Speichern...' }
								</>
							) : (
								i18n.saveSettings || 'Einstellungen speichern'
							) }
						</Button>
					</div>
				</CardContent>
			</Card>

			{ templates.length === 0 && (
				<Alert style={ { marginTop: '1rem' } }>
					<AlertDescription>
						{ i18n.noTemplatesWarning || 'Keine E-Mail-Templates vorhanden. Erstellen Sie zuerst Templates im Tab "Templates".' }
					</AlertDescription>
				</Alert>
			) }
		</div>
	);
}

AutomationSettings.propTypes = {
	templates: PropTypes.array,
};
