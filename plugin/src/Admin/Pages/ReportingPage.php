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
				'upgradeUrl'      => admin_url( 'admin.php?page=rp-license' ),
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
			'pageTitle'            => __( 'Berichte & Statistiken', 'recruiting-playbook' ),
			'pageDescription'      => __( 'Übersicht über Bewerbungen, Stellen und Recruiting-Kennzahlen', 'recruiting-playbook' ),

			// Tabs.
			'tabOverview'          => __( 'Übersicht', 'recruiting-playbook' ),
			'tabApplications'      => __( 'Bewerbungen', 'recruiting-playbook' ),
			'tabJobs'              => __( 'Stellen', 'recruiting-playbook' ),
			'tabTrends'            => __( 'Trends', 'recruiting-playbook' ),
			'tabExport'            => __( 'Export', 'recruiting-playbook' ),

			// Stats Cards.
			'totalApplications'    => __( 'Bewerbungen gesamt', 'recruiting-playbook' ),
			'newApplications'      => __( 'Neue Bewerbungen', 'recruiting-playbook' ),
			'inProgress'           => __( 'In Bearbeitung', 'recruiting-playbook' ),
			'hired'                => __( 'Eingestellt', 'recruiting-playbook' ),
			'rejected'             => __( 'Abgelehnt', 'recruiting-playbook' ),
			'activeJobs'           => __( 'Aktive Stellen', 'recruiting-playbook' ),
			'avgTimeToHire'        => __( 'Ø Time-to-Hire', 'recruiting-playbook' ),
			'conversionRate'       => __( 'Conversion-Rate', 'recruiting-playbook' ),
			'days'                 => __( 'Tage', 'recruiting-playbook' ),

			// Time Periods.
			'today'                => __( 'Heute', 'recruiting-playbook' ),
			'last7days'            => __( 'Letzte 7 Tage', 'recruiting-playbook' ),
			'last30days'           => __( 'Letzte 30 Tage', 'recruiting-playbook' ),
			'last90days'           => __( 'Letzte 90 Tage', 'recruiting-playbook' ),
			'thisYear'             => __( 'Dieses Jahr', 'recruiting-playbook' ),
			'allTime'              => __( 'Gesamt', 'recruiting-playbook' ),
			'customRange'          => __( 'Benutzerdefiniert', 'recruiting-playbook' ),

			// Charts.
			'applicationsOverTime' => __( 'Bewerbungen im Zeitverlauf', 'recruiting-playbook' ),
			'statusDistribution'   => __( 'Status-Verteilung', 'recruiting-playbook' ),
			'sourceDistribution'   => __( 'Quellen-Verteilung', 'recruiting-playbook' ),
			'topJobs'              => __( 'Top-Stellen', 'recruiting-playbook' ),
			'conversionFunnel'     => __( 'Conversion-Funnel', 'recruiting-playbook' ),
			'timeToHireTrend'      => __( 'Time-to-Hire Trend', 'recruiting-playbook' ),

			// Funnel Steps.
			'jobViews'             => __( 'Stellen-Aufrufe', 'recruiting-playbook' ),
			'formStarts'           => __( 'Formular gestartet', 'recruiting-playbook' ),
			'formSubmitted'        => __( 'Formular abgesendet', 'recruiting-playbook' ),
			'screening'            => __( 'In Prüfung', 'recruiting-playbook' ),
			'interview'            => __( 'Interview', 'recruiting-playbook' ),
			'offer'                => __( 'Angebot', 'recruiting-playbook' ),

			// Export.
			'exportApplications'   => __( 'Bewerbungen exportieren', 'recruiting-playbook' ),
			'exportStats'          => __( 'Statistik-Report exportieren', 'recruiting-playbook' ),
			'selectColumns'        => __( 'Spalten auswählen', 'recruiting-playbook' ),
			'selectPeriod'         => __( 'Zeitraum auswählen', 'recruiting-playbook' ),
			'selectStatus'         => __( 'Status filtern', 'recruiting-playbook' ),
			'selectJob'            => __( 'Stelle filtern', 'recruiting-playbook' ),
			'allStatuses'          => __( 'Alle Status', 'recruiting-playbook' ),
			'allJobs'              => __( 'Alle Stellen', 'recruiting-playbook' ),
			'downloadCsv'          => __( 'CSV herunterladen', 'recruiting-playbook' ),

			// Pro Features.
			'proFeature'           => __( 'Pro-Feature', 'recruiting-playbook' ),
			'proRequired'          => __( 'Dieses Feature erfordert Pro.', 'recruiting-playbook' ),
			'upgradeToPro'         => __( 'Auf Pro upgraden', 'recruiting-playbook' ),
			'advancedReporting'    => __( 'Erweiterte Berichte', 'recruiting-playbook' ),
			'csvExport'            => __( 'CSV-Export', 'recruiting-playbook' ),

			// Loading / Error States.
			'loading'              => __( 'Laden...', 'recruiting-playbook' ),
			'error'                => __( 'Fehler beim Laden der Daten', 'recruiting-playbook' ),
			'noData'               => __( 'Keine Daten verfügbar', 'recruiting-playbook' ),
			'retry'                => __( 'Erneut versuchen', 'recruiting-playbook' ),

			// Comparison.
			'comparedToPrevious'   => __( 'Im Vergleich zum Vormonat', 'recruiting-playbook' ),
			'increase'             => __( 'Zunahme', 'recruiting-playbook' ),
			'decrease'             => __( 'Abnahme', 'recruiting-playbook' ),
			'noChange'             => __( 'Keine Änderung', 'recruiting-playbook' ),
		];
	}
}
