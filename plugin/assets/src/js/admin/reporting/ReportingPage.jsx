/**
 * Reporting Page Component
 *
 * Main page for reports and statistics
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

	// Load data from window
	const data = window.rpReportingData || {};
	const { isPro, canViewStats, canViewAdvanced, canExport, upgradeUrl, logoUrl, i18n } = data;

	// Hooks for statistics
	const { overview, loading: overviewLoading, error: overviewError } = useStats( period );
	const { trends, loading: trendsLoading } = useTrends( period );
	const { data: timeToHireData, loading: tthLoading } = useTimeToHire( period );
	const { data: conversionData, loading: conversionLoading } = useConversion( period );
	const { exportApplications, exportStats, loading: exportLoading } = useExport();

	// Permission check
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
						{ __( 'You do not have permission to view this page.', 'recruiting-playbook' ) }
					</AlertDescription>
				</Alert>
			</div>
		);
	}

	// Pro feature upgrade banner — consistent design matching PHP rp_require_feature()
	const ProUpgradeBanner = ( { feature } ) => (
		<div
			style={ {
				display: 'flex',
				alignItems: 'flex-start',
				gap: '16px',
				padding: '24px',
				background: 'linear-gradient(135deg, #f0f6fc 0%, #fff 100%)',
				border: '1px solid #c3d9ed',
				borderRadius: '8px',
			} }
		>
			<div
				style={ {
					flexShrink: 0,
					width: '48px',
					height: '48px',
					background: '#2271b1',
					borderRadius: '50%',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
				} }
			>
				<Lock style={ { width: '24px', height: '24px', color: '#fff' } } />
			</div>
			<div>
				<h3 style={ { margin: '0 0 8px 0', fontSize: '16px', color: '#1d2327' } }>
					{ feature } { __( 'is a Pro feature', 'recruiting-playbook' ) }
				</h3>
				<p style={ { margin: '0 0 16px 0', color: '#50575e', fontSize: '14px', lineHeight: 1.5 } }>
					{ __( 'Upgrade to Pro to unlock this feature. You can compare plans and pricing on the upgrade page.', 'recruiting-playbook' ) }
				</p>
				<a
					href={ upgradeUrl }
					className="button button-primary button-hero"
					style={ { textDecoration: 'none' } }
				>
					{ __( 'Upgrade to Pro', 'recruiting-playbook' ) }
				</a>
			</div>
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
						alignItems: 'center',
						marginBottom: '1.5rem',
					} }
				>
					{ logoUrl && (
						<img
							src={ logoUrl }
							alt="Recruiting Playbook"
							style={ { width: '150px', height: 'auto' } }
						/>
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n.pageTitle || 'Reports & Statistics' }
					</h1>
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
							{ i18n.error || 'Error loading data' }: { overviewError }
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
						title="Applications"
						value={ `${ overview?.applications?.total || 0 } / ${ overview?.applications?.new || 0 }` }
						tooltip="Total / New applications in selected period"
						loading={ overviewLoading }
						icon={ <Users style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title="Active Jobs"
						value={ overview?.jobs?.active || 0 }
						tooltip="Number of published job listings"
						loading={ overviewLoading }
						icon={ <Briefcase style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title="Hired"
						value={ overview?.applications?.hired || 0 }
						tooltip="Successfully hired candidates in period"
						loading={ overviewLoading }
						icon={ <TrendingUp style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title="Avg Time-to-Hire"
						value={ overview?.time_to_hire?.average_days || '-' }
						suffix="days"
						tooltip="Average days from application to hire"
						loading={ overviewLoading }
						icon={ <Clock style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
					<StatsCard
						title="Conversion Rate"
						value={ overview?.conversion_rate?.rate?.toFixed( 1 ) || '-' }
						suffix="%"
						tooltip="Ratio of job views to applications"
						loading={ overviewLoading }
						icon={ <BarChart3 style={ { width: '1.25rem', height: '1.25rem' } } /> }
					/>
				</div>

				{ /* Tabs for detail views */ }
				<Tabs value={ activeTab } onValueChange={ setActiveTab }>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
						} }
					>
						<TabsList>
							<TabsTrigger value="overview">
								{ i18n.tabOverview || 'Overview' }
							</TabsTrigger>
							<TabsTrigger value="trends">
								{ i18n.tabTrends || 'Trends' }
							</TabsTrigger>
							<TabsTrigger value="jobs">
								{ i18n.tabJobs || 'Jobs' }
							</TabsTrigger>
							<TabsTrigger value="conversion">
								{ i18n.conversionFunnel || 'Conversion' }
								{ ! isPro && ! canViewAdvanced && (
									<Lock style={ { width: '0.75rem', height: '0.75rem', marginLeft: '0.25rem' } } />
								) }
							</TabsTrigger>
						</TabsList>

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

					{ /* Overview Tab */ }
					<TabsContent value="overview" style={ { marginTop: '1rem' } }>
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
								gap: '1.5rem',
							} }
						>
							{ /* Applications over time */ }
							<TrendChart
								title={ i18n.applicationsOverTime || 'Applications Over Time' }
								data={ trends?.data || [] }
								series={ [
									{ key: 'total', name: 'Applications', color: '#1d71b8' },
								] }
								loading={ trendsLoading }
							/>

							{ /* Top Jobs */ }
							<JobStatsTable
								title={ i18n.topJobs || 'Top Jobs' }
								description="Jobs by application count"
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
								title={ i18n.applicationsOverTime || 'Applications Over Time' }
								data={ trends?.data || [] }
								series={ [
									{ key: 'total', name: 'Total', color: '#1d71b8' },
									{ key: 'new', name: 'New', color: '#2fac66' },
								] }
								loading={ trendsLoading }
								height={ 350 }
							/>

							{ ( isPro || canViewAdvanced ) ? (
								<TrendChart
									title={ i18n.timeToHireTrend || 'Time-to-Hire Trend' }
									data={ timeToHireData?.trend || [] }
									series={ [
										{ key: 'average_days', name: 'Avg Days', color: '#f59e0b' },
									] }
									type="line"
									loading={ tthLoading }
									height={ 350 }
								/>
							) : (
								<ProUpgradeBanner feature={ i18n.advancedReporting || 'Advanced Reports' } />
							) }
						</div>
					</TabsContent>

					{ /* Jobs Tab */ }
					<TabsContent value="jobs" style={ { marginTop: '1rem' } }>
						<JobStatsTable
							title={ i18n.topJobs || 'Job Overview' }
							description="All active jobs with application statistics"
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
									title={ i18n.conversionFunnel || 'Conversion Funnel' }
									description="From job view to application"
									data={ conversionData?.funnel || {} }
									loading={ conversionLoading }
								/>

								<JobStatsTable
									title="Top Converting Jobs"
									description="Jobs with highest conversion rate"
									jobs={ conversionData?.top_converting_jobs || [] }
									loading={ conversionLoading }
									limit={ 5 }
								/>
							</div>
						) : (
							<ProUpgradeBanner feature={ i18n.conversionFunnel || 'Conversion Analysis' } />
						) }
					</TabsContent>
				</Tabs>
			</div>
		</div>
	);
}

export default ReportingPage;
