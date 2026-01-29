/**
 * Reporting Page Component
 *
 * Hauptseite für Berichte und Statistiken
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Users,
	Briefcase,
	TrendingUp,
	Clock,
	BarChart3,
	Lock,
} from 'lucide-react';
import {
	StatsCard,
	TrendChart,
	ConversionFunnel,
	JobStatsTable,
	DateRangePicker,
	ExportButton,
} from './components';
import {
	useStats,
	useTrends,
	useTimeToHire,
	useConversion,
	useExport,
} from './hooks';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../components/ui/tabs';
import { Alert, AlertDescription } from '../components/ui/alert';

/**
 * ReportingPage Component
 */
export function ReportingPage() {
	const [ period, setPeriod ] = useState( '30days' );
	const [ activeTab, setActiveTab ] = useState( 'overview' );

	// Daten aus window laden
	const data = window.rpReportingData || {};
	const { isPro, canViewStats, canViewAdvanced, canExport, upgradeUrl, logoUrl, i18n } = data;

	// Hooks für Statistiken
	const { overview, loading: overviewLoading, error: overviewError } = useStats( period );
	const { trends, loading: trendsLoading } = useTrends( period );
	const { data: timeToHireData, loading: tthLoading } = useTimeToHire( period );
	const { data: conversionData, loading: conversionLoading } = useConversion( period );
	const { exportApplications, exportStats, loading: exportLoading } = useExport();

	// Berechtigungsprüfung
	if ( ! canViewStats ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0', maxWidth: '1200px' } }>
				<Alert
					style={ {
						backgroundColor: '#fef2f2',
						borderColor: '#fecaca',
					} }
				>
					<Lock style={ { width: '1rem', height: '1rem', color: '#ef4444' } } />
					<AlertDescription style={ { color: '#991b1b' } }>
						{ __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'recruiting-playbook' ) }
					</AlertDescription>
				</Alert>
			</div>
		);
	}

	// Pro-Feature Upgrade Banner
	const ProUpgradeBanner = ( { feature } ) => (
		<div
			style={ {
				padding: '2rem',
				backgroundColor: '#fefce8',
				borderRadius: '0.5rem',
				textAlign: 'center',
				border: '1px solid #fef08a',
			} }
		>
			<Lock style={ { width: '2rem', height: '2rem', color: '#ca8a04', marginBottom: '0.5rem' } } />
			<h3 style={ { marginBottom: '0.5rem', color: '#854d0e' } }>
				{ i18n.proFeature || 'Pro-Feature' }
			</h3>
			<p style={ { color: '#a16207', marginBottom: '1rem' } }>
				{ feature } { i18n.proRequired || 'erfordert die Pro-Version.' }
			</p>
			<a
				href={ upgradeUrl }
				style={ {
					display: 'inline-block',
					padding: '0.625rem 1.25rem',
					backgroundColor: '#1d71b8',
					color: '#ffffff',
					borderRadius: '0.375rem',
					textDecoration: 'none',
					fontWeight: 500,
				} }
			>
				{ i18n.upgradeToPro || 'Auf Pro upgraden' }
			</a>
		</div>
	);

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1200px' } }>
				{ /* Header */ }
				<div
					style={ {
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'flex-end',
						marginBottom: '1.5rem',
					} }
				>
					<div style={ { display: 'flex', alignItems: 'flex-end', gap: '1.5rem' } }>
						{ logoUrl && (
							<img
								src={ logoUrl }
								alt="Recruiting Playbook"
								style={ { width: '150px', height: 'auto' } }
							/>
						) }
						<div>
							<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
								{ i18n.pageTitle || 'Berichte & Statistiken' }
							</h1>
							<p style={ { margin: 0, color: '#6b7280', marginTop: '0.25rem' } }>
								{ i18n.pageDescription || 'Übersicht über Bewerbungen und Recruiting-Kennzahlen' }
							</p>
						</div>
					</div>

					{ /* Controls */ }
					<div style={ { display: 'flex', gap: '0.75rem' } }>
						<DateRangePicker value={ period } onChange={ setPeriod } />
						<ExportButton
							onExportApplications={ exportApplications }
							onExportStats={ exportStats }
							period={ period }
							loading={ exportLoading }
							disabled={ ! canExport && ! isPro }
							upgradeUrl={ upgradeUrl }
						/>
					</div>
				</div>

				{ /* Error State */ }
				{ overviewError && (
					<Alert
						style={ {
							marginBottom: '1.5rem',
							backgroundColor: '#fef2f2',
							borderColor: '#fecaca',
						} }
					>
						<AlertDescription style={ { color: '#991b1b' } }>
							{ i18n.error || 'Fehler beim Laden der Daten' }: { overviewError }
						</AlertDescription>
					</Alert>
				) }

				{ /* Stats Cards */ }
				<div
					style={ {
						display: 'grid',
						gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
						gap: '1rem',
						marginBottom: '1.5rem',
					} }
				>
					<StatsCard
						title={ i18n.applications || 'Bewerbungen (gesamt / neu)' }
						value={ `${ overview?.applications?.total || 0 } / ${ overview?.applications?.new || 0 }` }
						loading={ overviewLoading }
						valueColor="#1d71b8"
						icon={ <Users style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title={ i18n.activeJobs || 'Aktive Stellen' }
						value={ overview?.jobs?.active || 0 }
						loading={ overviewLoading }
						valueColor="#1d71b8"
						icon={ <Briefcase style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title={ i18n.hired || 'Eingestellt' }
						value={ overview?.applications?.hired || 0 }
						loading={ overviewLoading }
						valueColor="#22c55e"
						icon={ <TrendingUp style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title={ i18n.avgTimeToHire || 'Ø Time-to-Hire' }
						value={ overview?.time_to_hire?.average_days || '-' }
						suffix={ i18n.days || 'Tage' }
						loading={ overviewLoading }
						valueColor="#f59e0b"
						icon={ <Clock style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title={ i18n.conversionRate || 'Conversion-Rate' }
						value={ overview?.conversion_rate?.rate?.toFixed( 1 ) || '-' }
						suffix="%"
						loading={ overviewLoading }
						valueColor="#8b5cf6"
						icon={ <BarChart3 style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
				</div>

				{ /* Tabs für Detailansichten */ }
				<Tabs value={ activeTab } onValueChange={ setActiveTab }>
					<TabsList>
						<TabsTrigger value="overview">
							{ i18n.tabOverview || 'Übersicht' }
						</TabsTrigger>
						<TabsTrigger value="trends">
							{ i18n.tabTrends || 'Trends' }
						</TabsTrigger>
						<TabsTrigger value="jobs">
							{ i18n.tabJobs || 'Stellen' }
						</TabsTrigger>
						<TabsTrigger value="conversion">
							{ i18n.conversionFunnel || 'Conversion' }
							{ ! isPro && ! canViewAdvanced && (
								<Lock style={ { width: '0.75rem', height: '0.75rem', marginLeft: '0.25rem' } } />
							) }
						</TabsTrigger>
					</TabsList>

					{ /* Übersicht Tab */ }
					<TabsContent value="overview" style={ { marginTop: '1rem' } }>
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
								gap: '1.5rem',
							} }
						>
							{ /* Bewerbungen im Zeitverlauf */ }
							<TrendChart
								title={ i18n.applicationsOverTime || 'Bewerbungen im Zeitverlauf' }
								data={ trends?.timeline || [] }
								series={ [
									{ key: 'total', name: 'Bewerbungen', color: '#1d71b8' },
								] }
								loading={ trendsLoading }
							/>

							{ /* Top-Stellen */ }
							<JobStatsTable
								title={ i18n.topJobs || 'Top-Stellen' }
								description="Stellen nach Bewerbungsanzahl"
								jobs={ overview?.top_jobs || [] }
								loading={ overviewLoading }
								limit={ 5 }
							/>
						</div>
					</TabsContent>

					{ /* Trends Tab */ }
					<TabsContent value="trends" style={ { marginTop: '1rem' } }>
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
								gap: '1.5rem',
							} }
						>
							<TrendChart
								title={ i18n.applicationsOverTime || 'Bewerbungen im Zeitverlauf' }
								data={ trends?.timeline || [] }
								series={ [
									{ key: 'total', name: 'Gesamt', color: '#1d71b8' },
									{ key: 'new', name: 'Neu', color: '#2fac66' },
								] }
								loading={ trendsLoading }
								height={ 350 }
							/>

							{ ( isPro || canViewAdvanced ) ? (
								<TrendChart
									title={ i18n.timeToHireTrend || 'Time-to-Hire Trend' }
									data={ timeToHireData?.trend || [] }
									series={ [
										{ key: 'average_days', name: 'Ø Tage', color: '#f59e0b' },
									] }
									type="line"
									loading={ tthLoading }
									height={ 350 }
								/>
							) : (
								<ProUpgradeBanner feature={ i18n.advancedReporting || 'Erweiterte Berichte' } />
							) }
						</div>
					</TabsContent>

					{ /* Jobs Tab */ }
					<TabsContent value="jobs" style={ { marginTop: '1rem' } }>
						<JobStatsTable
							title={ i18n.topJobs || 'Stellen-Übersicht' }
							description="Alle aktiven Stellen mit Bewerbungs-Statistiken"
							jobs={ overview?.job_stats || overview?.top_jobs || [] }
							loading={ overviewLoading }
							limit={ 20 }
						/>
					</TabsContent>

					{ /* Conversion Tab */ }
					<TabsContent value="conversion" style={ { marginTop: '1rem' } }>
						{ ( isPro || canViewAdvanced ) ? (
							<div
								style={ {
									display: 'grid',
									gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
									gap: '1.5rem',
								} }
							>
								<ConversionFunnel
									title={ i18n.conversionFunnel || 'Conversion-Funnel' }
									description="Vom Stellenaufruf zur Bewerbung"
									data={ conversionData?.funnel || {} }
									loading={ conversionLoading }
								/>

								<JobStatsTable
									title="Top-konvertierende Stellen"
									description="Stellen mit höchster Conversion-Rate"
									jobs={ conversionData?.top_converting_jobs || [] }
									loading={ conversionLoading }
									limit={ 5 }
								/>
							</div>
						) : (
							<ProUpgradeBanner feature={ i18n.conversionFunnel || 'Conversion-Analyse' } />
						) }
					</TabsContent>
				</Tabs>
			</div>
		</div>
	);
}

export default ReportingPage;
