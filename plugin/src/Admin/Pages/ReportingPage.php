<?php
/**
 * Reporting Admin Page
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Reporting Page Klasse
 */
class ReportingPage {

	/**
	 * Render the reporting page
	 */
	public function render(): void {
		// Pro-Feature Check.
		$is_pro = function_exists( 'rp_is_pro' ) && rp_is_pro();
		$can_view_stats = current_user_can( 'rp_view_stats' );
		$can_view_advanced = current_user_can( 'rp_view_advanced_stats' );
		$can_export = current_user_can( 'rp_export_data' );

		// Localize data for React component.
		wp_localize_script(
			'rp-admin',
			'rpReportingData',
			[
				'isPro'           => $is_pro,
				'canViewStats'    => $can_view_stats,
				'canViewAdvanced' => $can_view_advanced,
				'canExport'       => $can_export,
				'upgradeUrl'      => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				'logoUrl'         => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
				'i18n'            => $this->get_translations(),
			]
		);

		?>
		<div class="wrap rp-admin">
			<div id="rp-reporting-root"></div>
		</div>
		<?php
	}

	/**
	 * Get translations for JavaScript
	 *
	 * @return array<string, string>
	 */
	private function get_translations(): array {
		return [
			// Page.
			'pageTitle'            => __( 'Reports & Statistics', 'recruiting-playbook' ),
			'pageDescription'      => __( 'Overview of applications, jobs and recruiting metrics', 'recruiting-playbook' ),

			// Tabs.
			'tabOverview'          => __( 'Overview', 'recruiting-playbook' ),
			'tabApplications'      => __( 'Applications', 'recruiting-playbook' ),
			'tabJobs'              => __( 'Jobs', 'recruiting-playbook' ),
			'tabTrends'            => __( 'Trends', 'recruiting-playbook' ),
			'tabExport'            => __( 'Export', 'recruiting-playbook' ),

			// Stats Cards.
			'totalApplications'    => __( 'Total applications', 'recruiting-playbook' ),
			'newApplications'      => __( 'New applications', 'recruiting-playbook' ),
			'inProgress'           => __( 'In progress', 'recruiting-playbook' ),
			'hired'                => __( 'Hired', 'recruiting-playbook' ),
			'rejected'             => __( 'Rejected', 'recruiting-playbook' ),
			'activeJobs'           => __( 'Active jobs', 'recruiting-playbook' ),
			'avgTimeToHire'        => __( 'Avg. time-to-hire', 'recruiting-playbook' ),
			'conversionRate'       => __( 'Conversion rate', 'recruiting-playbook' ),
			'days'                 => __( 'days', 'recruiting-playbook' ),

			// Time Periods.
			'today'                => __( 'Today', 'recruiting-playbook' ),
			'last7days'            => __( 'Last 7 days', 'recruiting-playbook' ),
			'last30days'           => __( 'Last 30 days', 'recruiting-playbook' ),
			'last90days'           => __( 'Last 90 days', 'recruiting-playbook' ),
			'thisYear'             => __( 'This year', 'recruiting-playbook' ),
			'allTime'              => __( 'All time', 'recruiting-playbook' ),
			'customRange'          => __( 'Custom range', 'recruiting-playbook' ),

			// Charts.
			'applicationsOverTime' => __( 'Applications over time', 'recruiting-playbook' ),
			'statusDistribution'   => __( 'Status distribution', 'recruiting-playbook' ),
			'sourceDistribution'   => __( 'Source distribution', 'recruiting-playbook' ),
			'topJobs'              => __( 'Top jobs', 'recruiting-playbook' ),
			'conversionFunnel'     => __( 'Conversion funnel', 'recruiting-playbook' ),
			'timeToHireTrend'      => __( 'Time-to-hire trend', 'recruiting-playbook' ),

			// Funnel Steps.
			'jobViews'             => __( 'Job views', 'recruiting-playbook' ),
			'formStarts'           => __( 'Form started', 'recruiting-playbook' ),
			'formSubmitted'        => __( 'Form submitted', 'recruiting-playbook' ),
			'screening'            => __( 'Screening', 'recruiting-playbook' ),
			'interview'            => __( 'Interview', 'recruiting-playbook' ),
			'offer'                => __( 'Offer', 'recruiting-playbook' ),

			// Export.
			'exportApplications'   => __( 'Export applications', 'recruiting-playbook' ),
			'exportStats'          => __( 'Export statistics report', 'recruiting-playbook' ),
			'selectColumns'        => __( 'Select columns', 'recruiting-playbook' ),
			'selectPeriod'         => __( 'Select period', 'recruiting-playbook' ),
			'selectStatus'         => __( 'Filter by status', 'recruiting-playbook' ),
			'selectJob'            => __( 'Filter by job', 'recruiting-playbook' ),
			'allStatuses'          => __( 'All statuses', 'recruiting-playbook' ),
			'allJobs'              => __( 'All jobs', 'recruiting-playbook' ),
			'downloadCsv'          => __( 'Download CSV', 'recruiting-playbook' ),

			// Pro Features.
			'proFeature'           => __( 'Pro feature', 'recruiting-playbook' ),
			'proRequired'          => __( 'This feature requires Pro.', 'recruiting-playbook' ),
			'upgradeToPro'         => __( 'Upgrade to Pro', 'recruiting-playbook' ),
			'advancedReporting'    => __( 'Advanced reports', 'recruiting-playbook' ),
			'csvExport'            => __( 'CSV export', 'recruiting-playbook' ),

			// Loading / Error States.
			'loading'              => __( 'Loading...', 'recruiting-playbook' ),
			'error'                => __( 'Error loading data', 'recruiting-playbook' ),
			'noData'               => __( 'No data available', 'recruiting-playbook' ),
			'retry'                => __( 'Retry', 'recruiting-playbook' ),

			// Comparison.
			'comparedToPrevious'   => __( 'Compared to previous month', 'recruiting-playbook' ),
			'increase'             => __( 'Increase', 'recruiting-playbook' ),
			'decrease'             => __( 'Decrease', 'recruiting-playbook' ),
			'noChange'             => __( 'No change', 'recruiting-playbook' ),
		];
	}
}
