<?php
/**
 * WordPress Dashboard Widget für Recruiting-Kennzahlen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Constants\ApplicationStatus;

/**
 * Dashboard Widget mit Recruiting-Statistiken und neuesten Bewerbungen.
 */
class DashboardWidget {

	/**
	 * Hook registrieren.
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'addWidget' ] );
	}

	/**
	 * Widget zum Dashboard hinzufügen.
	 */
	public function addWidget(): void {
		if ( ! current_user_can( 'rp_view_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$icon  = '<img src="' . esc_url( RP_PLUGIN_URL . 'assets/images/rp-icon.png' ) . '" alt="" style="height:20px;width:20px;vertical-align:text-bottom;margin-right:6px;">';
		$title = $icon . esc_html__( 'Recruiting Playbook', 'recruiting-playbook' );

		wp_add_dashboard_widget(
			'rp_dashboard_widget',
			$title,
			[ $this, 'render' ]
		);
	}

	/**
	 * Widget-Inhalt rendern.
	 */
	public function render(): void {
		$stats        = $this->getStats();
		$applications = $this->getRecentApplications();
		$status_labels = ApplicationStatus::getAll();

		?>
		<style>
			#rp_dashboard_widget .rp-dw-stats {
				display: flex;
				gap: 12px;
				margin-bottom: 16px;
			}
			#rp_dashboard_widget .rp-dw-stat {
				flex: 1;
				text-align: center;
				padding: 12px 8px;
				background: #f6f7f7;
				border-radius: 4px;
				border: 1px solid #dcdcde;
			}
			#rp_dashboard_widget .rp-dw-stat-number {
				display: block;
				font-size: 28px;
				font-weight: 600;
				line-height: 1.2;
				color: #1d71b8;
			}
			#rp_dashboard_widget .rp-dw-stat-label {
				display: block;
				font-size: 12px;
				color: #646970;
				margin-top: 4px;
			}
			#rp_dashboard_widget .rp-dw-table {
				width: 100%;
				border-collapse: collapse;
			}
			#rp_dashboard_widget .rp-dw-table th {
				text-align: left;
				padding: 6px 8px;
				border-bottom: 1px solid #dcdcde;
				font-size: 12px;
				color: #646970;
				font-weight: 400;
			}
			#rp_dashboard_widget .rp-dw-table td {
				padding: 8px;
				border-bottom: 1px solid #f0f0f1;
				font-size: 13px;
			}
			#rp_dashboard_widget .rp-dw-table tr:last-child td {
				border-bottom: none;
			}
			#rp_dashboard_widget .rp-dw-badge {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 9999px;
				font-size: 11px;
				font-weight: 500;
				line-height: 1.5;
				white-space: nowrap;
			}
			#rp_dashboard_widget .rp-dw-links {
				display: flex;
				gap: 12px;
				margin-top: 16px;
				padding-top: 12px;
				border-top: 1px solid #dcdcde;
			}
			#rp_dashboard_widget .rp-dw-links a {
				text-decoration: none;
				font-weight: 500;
				font-size: 13px;
			}
			#rp_dashboard_widget .rp-dw-empty {
				color: #646970;
				font-style: italic;
				padding: 12px 0;
			}
		</style>

		<div class="rp-dw-stats">
			<div class="rp-dw-stat">
				<span class="rp-dw-stat-number"><?php echo esc_html( (string) $stats['active_jobs'] ); ?></span>
				<span class="rp-dw-stat-label"><?php esc_html_e( 'Aktive Stellen', 'recruiting-playbook' ); ?></span>
			</div>
			<div class="rp-dw-stat">
				<span class="rp-dw-stat-number"><?php echo esc_html( (string) $stats['new_applications'] ); ?></span>
				<span class="rp-dw-stat-label"><?php esc_html_e( 'Neue Bewerbungen', 'recruiting-playbook' ); ?></span>
			</div>
			<div class="rp-dw-stat">
				<span class="rp-dw-stat-number"><?php echo esc_html( (string) $stats['this_week'] ); ?></span>
				<span class="rp-dw-stat-label"><?php esc_html_e( 'Diese Woche', 'recruiting-playbook' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $applications ) ) : ?>
			<table class="rp-dw-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Bewerber', 'recruiting-playbook' ); ?></th>
						<th><?php esc_html_e( 'Stelle', 'recruiting-playbook' ); ?></th>
						<th><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></th>
						<th><?php esc_html_e( 'Datum', 'recruiting-playbook' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $applications as $app ) : ?>
						<?php
						$detail_url   = admin_url( 'admin.php?page=rp-application-detail&id=' . (int) $app->id );
						$color        = ApplicationStatus::getColor( $app->status );
						$bg_color     = $color . '1a'; // 10% opacity hex.
						$status_label = $status_labels[ $app->status ] ?? $app->status;
						$name         = trim( $app->first_name . ' ' . $app->last_name );
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $detail_url ); ?>">
									<strong><?php echo esc_html( $name ); ?></strong>
								</a>
							</td>
							<td><?php echo esc_html( $app->job_title ?: '—' ); ?></td>
							<td>
								<span class="rp-dw-badge" style="color:<?php echo esc_attr( $color ); ?>;background:<?php echo esc_attr( $bg_color ); ?>;">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $app->created_at ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="rp-dw-empty"><?php esc_html_e( 'Noch keine Bewerbungen vorhanden.', 'recruiting-playbook' ); ?></p>
		<?php endif; ?>

		<div class="rp-dw-links">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>">
				<?php esc_html_e( 'Alle Bewerbungen', 'recruiting-playbook' ); ?> &rarr;
			</a>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=job_listing' ) ); ?>">
				<?php esc_html_e( 'Neue Stelle erstellen', 'recruiting-playbook' ); ?> &rarr;
			</a>
		</div>
		<?php
	}

	/**
	 * Statistiken abrufen.
	 *
	 * @return array{active_jobs: int, new_applications: int, this_week: int}
	 */
	private function getStats(): array {
		global $wpdb;

		$active_jobs = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'job_listing' AND post_status = 'publish'"
		);

		$new_applications = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}rp_applications
			 WHERE status = 'new' AND deleted_at IS NULL"
		);

		$this_week = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}rp_applications
			 WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
		);

		return [
			'active_jobs'      => $active_jobs,
			'new_applications' => $new_applications,
			'this_week'        => $this_week,
		];
	}

	/**
	 * Letzte 5 Bewerbungen abrufen.
	 *
	 * @return array<object>
	 */
	private function getRecentApplications(): array {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT a.id, a.status, a.created_at,
			        c.first_name, c.last_name,
			        p.post_title AS job_title
			 FROM {$wpdb->prefix}rp_applications a
			 LEFT JOIN {$wpdb->prefix}rp_candidates c ON a.candidate_id = c.id
			 LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID
			 WHERE a.deleted_at IS NULL
			 ORDER BY a.created_at DESC
			 LIMIT 5"
		);

		return $results ?: [];
	}
}
