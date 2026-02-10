/**
 * ConversionFunnel Component
 *
 * Zeigt einen Recruiting-Funnel mit Conversion-Raten
 *
 * @package RecruitingPlaybook
 */

import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../../components/ui/card';

/**
 * ConversionFunnel Component
 *
 * @param {Object} props Component props
 * @param {string} props.title Titel
 * @param {string} props.description Beschreibung
 * @param {Object} props.data Funnel-Daten
 * @param {boolean} props.loading Ladezustand
 */
export function ConversionFunnel( {
	title,
	description,
	data = {},
	loading = false,
} ) {
	const i18n = window.rpReportingData?.i18n || {};

	// Funnel-Stufen definieren
	const stages = [
		{
			key: 'job_list_views',
			label: i18n.jobViews || 'Stellen-Aufrufe',
			value: data.job_list_views || 0,
			color: '#1d71b8',
		},
		{
			key: 'job_detail_views',
			label: i18n.jobDetailViews || 'Detail-Aufrufe',
			value: data.job_detail_views || 0,
			color: '#36a9e1',
		},
		{
			key: 'form_starts',
			label: i18n.formStarts || 'Formular gestartet',
			value: data.form_starts || 0,
			color: '#2fac66',
		},
		{
			key: 'form_completions',
			label: i18n.formSubmitted || 'Bewerbung eingereicht',
			value: data.form_completions || 0,
			color: '#22c55e',
		},
	];

	// Maximaler Wert für Skalierung
	const maxValue = Math.max( ...stages.map( ( s ) => s.value ), 1 );

	// Conversion-Raten aus data.rates
	const rates = data.rates || {};

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
								borderRadius: '0.5rem',
								marginBottom: '0.75rem',
								animation: 'pulse 2s infinite',
							} }
						/>
					) ) }
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
				<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
					{ stages.map( ( stage, index ) => {
						const width = ( stage.value / maxValue ) * 100;
						const nextStage = stages[ index + 1 ];
						const conversionRate = nextStage
							? ( stage.value > 0 ? ( nextStage.value / stage.value * 100 ).toFixed( 1 ) : 0 )
							: null;

						return (
							<div key={ stage.key }>
								{ /* Funnel-Balken */ }
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '1rem',
									} }
								>
									<div
										style={ {
											width: '140px',
											flexShrink: 0,
											fontSize: '0.875rem',
											color: '#374151',
											textAlign: 'right',
										} }
									>
										{ stage.label }
									</div>
									<div
										style={ {
											flex: 1,
											height: '2.5rem',
											backgroundColor: '#f3f4f6',
											borderRadius: '0.375rem',
											overflow: 'hidden',
											position: 'relative',
										} }
									>
										<div
											style={ {
												width: `${ width }%`,
												height: '100%',
												backgroundColor: stage.color,
												borderRadius: '0.375rem',
												transition: 'width 0.5s ease-in-out',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'flex-end',
												paddingRight: '0.75rem',
											} }
										>
											<span
												style={ {
													color: '#ffffff',
													fontWeight: 600,
													fontSize: '0.875rem',
												} }
											>
												{ stage.value.toLocaleString( 'de-DE' ) }
											</span>
										</div>
									</div>
								</div>

								{ /* Conversion-Rate zur nächsten Stufe */ }
								{ conversionRate !== null && (
									<div
										style={ {
											display: 'flex',
											alignItems: 'center',
											marginLeft: '156px',
											paddingTop: '0.25rem',
											paddingBottom: '0.25rem',
										} }
									>
										<div
											style={ {
												display: 'flex',
												alignItems: 'center',
												gap: '0.5rem',
											} }
										>
											<svg
												width="16"
												height="16"
												viewBox="0 0 16 16"
												fill="none"
												style={ { color: '#9ca3af' } }
											>
												<path
													d="M8 3v10M5 10l3 3 3-3"
													stroke="currentColor"
													strokeWidth="1.5"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
											<span
												style={ {
													fontSize: '0.75rem',
													color: '#6b7280',
												} }
											>
												{ conversionRate }%
											</span>
										</div>
									</div>
								) }
							</div>
						);
					} ) }
				</div>

				{ /* Gesamte Conversion-Rate */ }
				{ rates.overall !== undefined && (
					<div
						style={ {
							marginTop: '1.5rem',
							padding: '1rem',
							backgroundColor: '#f0f9ff',
							borderRadius: '0.5rem',
							textAlign: 'center',
						} }
					>
						<div
							style={ {
								fontSize: '0.875rem',
								color: '#0369a1',
								marginBottom: '0.25rem',
							} }
						>
							Gesamt-Conversion
						</div>
						<div
							style={ {
								fontSize: '1.5rem',
								fontWeight: 700,
								color: '#0c4a6e',
							} }
						>
							{ rates.overall.toFixed( 1 ) }%
						</div>
					</div>
				) }
			</CardContent>
		</Card>
	);
}

export default ConversionFunnel;
