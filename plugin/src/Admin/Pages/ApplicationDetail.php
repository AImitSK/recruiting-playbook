<?php
/**
 * Detailansicht einer Bewerbung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Constants\DocumentType;
use RecruitingPlaybook\Services\DocumentDownloadService;

/**
 * Detailansicht einer Bewerbung
 */
class ApplicationDetail {

	/**
	 * Seite rendern
	 */
	public function render(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id ) {
			wp_die( esc_html__( 'Keine Bewerbung angegeben.', 'recruiting-playbook' ) );
		}

		$application = $this->getApplication( $id );

		if ( ! $application ) {
			wp_die( esc_html__( 'Bewerbung nicht gefunden.', 'recruiting-playbook' ) );
		}

		$this->processStatusUpdate( $id );

		$candidate    = $this->getCandidate( (int) $application['candidate_id'] );
		$job          = get_post( $application['job_id'] );
		$documents    = $this->getDocuments( $id );
		$activity_log = $this->getActivityLog( $id );

		?>
		<div class="wrap rp-application-detail">
			<h1>
				<?php
				printf(
					/* translators: %s: Applicant name */
					esc_html__( 'Bewerbung von %s', 'recruiting-playbook' ),
					esc_html( $candidate['first_name'] . ' ' . $candidate['last_name'] )
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-applications' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Zurück zur Liste', 'recruiting-playbook' ); ?>
				</a>
			</h1>

			<div class="rp-detail-grid">
				<!-- Hauptbereich -->
				<div class="rp-detail-main">
					<!-- Kontaktdaten -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Kontaktdaten', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></th>
									<td><?php echo esc_html( $candidate['first_name'] . ' ' . $candidate['last_name'] ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?></th>
									<td>
										<a href="mailto:<?php echo esc_attr( $candidate['email'] ); ?>">
											<?php echo esc_html( $candidate['email'] ); ?>
										</a>
									</td>
								</tr>
								<?php if ( ! empty( $candidate['phone'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></th>
									<td>
										<a href="tel:<?php echo esc_attr( $candidate['phone'] ); ?>">
											<?php echo esc_html( $candidate['phone'] ); ?>
										</a>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</div>
					</div>

					<!-- Anschreiben -->
					<?php if ( ! empty( $application['cover_letter'] ) ) : ?>
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Anschreiben', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<div class="rp-cover-letter">
								<?php echo wp_kses_post( nl2br( $application['cover_letter'] ) ); ?>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<!-- Dokumente -->
					<div class="postbox">
						<h2 class="hndle">
							<?php esc_html_e( 'Dokumente', 'recruiting-playbook' ); ?>
							<span class="count">(<?php echo count( $documents ); ?>)</span>
						</h2>
						<div class="inside">
							<?php if ( empty( $documents ) ) : ?>
								<p class="description">
									<?php esc_html_e( 'Keine Dokumente hochgeladen.', 'recruiting-playbook' ); ?>
								</p>
							<?php else : ?>
								<table class="widefat striped">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Dokument', 'recruiting-playbook' ); ?></th>
											<th><?php esc_html_e( 'Typ', 'recruiting-playbook' ); ?></th>
											<th><?php esc_html_e( 'Größe', 'recruiting-playbook' ); ?></th>
											<th><?php esc_html_e( 'Aktion', 'recruiting-playbook' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $documents as $doc ) : ?>
											<?php
											$download_url = DocumentDownloadService::generateDownloadUrl( (int) $doc['id'] );
											$type_labels  = DocumentType::getAll();
											?>
											<tr>
												<td>
													<span class="dashicons dashicons-media-document"></span>
													<?php echo esc_html( $doc['original_name'] ); ?>
												</td>
												<td><?php echo esc_html( $type_labels[ $doc['type'] ] ?? $doc['type'] ); ?></td>
												<td><?php echo esc_html( size_format( (int) $doc['size'] ) ); ?></td>
												<td>
													<a href="<?php echo esc_url( $download_url ); ?>" class="button button-small">
														<?php esc_html_e( 'Download', 'recruiting-playbook' ); ?>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>

					<!-- Aktivitäts-Log -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Verlauf', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<?php if ( empty( $activity_log ) ) : ?>
								<p class="description">
									<?php esc_html_e( 'Keine Aktivitäten aufgezeichnet.', 'recruiting-playbook' ); ?>
								</p>
							<?php else : ?>
								<ul class="rp-activity-log">
									<?php foreach ( $activity_log as $entry ) : ?>
										<li>
											<span class="rp-log-time">
												<?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $entry['created_at'] ) ) ); ?>
											</span>
											<span class="rp-log-user">
												<?php echo esc_html( $entry['user_name'] ?: __( 'System', 'recruiting-playbook' ) ); ?>
											</span>
											<span class="rp-log-action">
												<?php echo esc_html( $this->formatLogAction( $entry ) ); ?>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Sidebar -->
				<div class="rp-detail-sidebar">
					<!-- Status -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<form method="post">
								<?php wp_nonce_field( 'rp_update_status_' . $id ); ?>
								<input type="hidden" name="application_id" value="<?php echo esc_attr( $id ); ?>" />

								<p>
									<select name="status" id="rp-status-select" style="width: 100%;">
										<?php foreach ( ApplicationStatus::getAll() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $application['status'], $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>

								<p>
									<button type="submit" name="update_status" class="button button-primary" style="width: 100%;">
										<?php esc_html_e( 'Status aktualisieren', 'recruiting-playbook' ); ?>
									</button>
								</p>
							</form>
						</div>
					</div>

					<!-- Stelle -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Stelle', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<?php if ( $job ) : ?>
								<p>
									<strong><?php echo esc_html( $job->post_title ); ?></strong>
								</p>
								<p>
									<a href="<?php echo esc_url( get_edit_post_link( $job->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Bearbeiten', 'recruiting-playbook' ); ?>
									</a>
									<a href="<?php echo esc_url( get_permalink( $job->ID ) ); ?>" class="button button-small" target="_blank">
										<?php esc_html_e( 'Ansehen', 'recruiting-playbook' ); ?>
									</a>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'Stelle wurde gelöscht.', 'recruiting-playbook' ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Meta-Daten -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Details', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<p>
								<strong><?php esc_html_e( 'Eingegangen:', 'recruiting-playbook' ); ?></strong><br>
								<?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $application['created_at'] ) ) ); ?>
							</p>
							<p>
								<strong><?php esc_html_e( 'Letzte Änderung:', 'recruiting-playbook' ); ?></strong><br>
								<?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $application['updated_at'] ) ) ); ?>
							</p>
							<?php if ( ! empty( $application['source_url'] ) ) : ?>
							<p>
								<strong><?php esc_html_e( 'Quelle:', 'recruiting-playbook' ); ?></strong><br>
								<small><?php echo esc_url( $application['source_url'] ); ?></small>
							</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- DSGVO-Aktionen -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'DSGVO', 'recruiting-playbook' ); ?></h2>
						<div class="inside">
							<p>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rp-applications&action=export_data&id=' . $id ), 'rp_export_data_' . $id ) ); ?>" class="button button-small" style="width: 100%; text-align: center;">
									<?php esc_html_e( 'Daten exportieren', 'recruiting-playbook' ); ?>
								</a>
							</p>
							<p>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rp-applications&action=delete&id=' . $id ), 'rp_delete_' . $id ) ); ?>" class="button button-small button-link-delete" style="width: 100%; text-align: center;" onclick="return confirm('<?php esc_attr_e( 'Diese Bewerbung wirklich löschen?', 'recruiting-playbook' ); ?>');">
									<?php esc_html_e( 'Bewerbung löschen', 'recruiting-playbook' ); ?>
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
			.rp-detail-grid {
				display: grid;
				grid-template-columns: 1fr 300px;
				gap: 20px;
				margin-top: 20px;
			}
			.rp-detail-main .postbox,
			.rp-detail-sidebar .postbox {
				margin-bottom: 20px;
			}
			.rp-cover-letter {
				background: #f6f7f7;
				padding: 15px;
				border-radius: 4px;
				white-space: pre-wrap;
			}
			.rp-activity-log {
				margin: 0;
				padding: 0;
				list-style: none;
			}
			.rp-activity-log li {
				padding: 8px 0;
				border-bottom: 1px solid #eee;
			}
			.rp-activity-log li:last-child {
				border-bottom: none;
			}
			.rp-log-time {
				color: #666;
				font-size: 12px;
				display: block;
			}
			.rp-log-user {
				font-weight: 500;
			}
			.button-link-delete {
				color: #b32d2e !important;
				border-color: #b32d2e !important;
			}
			.button-link-delete:hover {
				background: #b32d2e !important;
				color: white !important;
			}
			@media screen and (max-width: 960px) {
				.rp-detail-grid {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php
	}

	/**
	 * Bewerbung laden
	 *
	 * @param int $id Application ID.
	 * @return array|null
	 */
	private function getApplication( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Kandidat laden
	 *
	 * @param int $id Candidate ID.
	 * @return array|null
	 */
	private function getCandidate( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_candidates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Dokumente laden
	 *
	 * @param int $application_id Application ID.
	 * @return array
	 */
	private function getDocuments( int $application_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE application_id = %d ORDER BY created_at ASC",
				$application_id
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Aktivitäts-Log laden
	 *
	 * @param int $application_id Application ID.
	 * @return array
	 */
	private function getActivityLog( int $application_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_activity_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE object_type = 'application' AND object_id = %d
				 ORDER BY created_at DESC
				 LIMIT 50",
				$application_id
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Status-Update verarbeiten
	 *
	 * @param int $id Application ID.
	 */
	private function processStatusUpdate( int $id ): void {
		if ( ! isset( $_POST['update_status'] ) ) {
			return;
		}

		check_admin_referer( 'rp_update_status_' . $id );

		$new_status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! array_key_exists( $new_status, ApplicationStatus::getAll() ) ) {
			return;
		}

		global $wpdb;

		$table      = $wpdb->prefix . 'rp_applications';
		$old_status = $wpdb->get_var(
			$wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id )
		);

		if ( $old_status === $new_status ) {
			return;
		}

		$wpdb->update( $table, [ 'status' => $new_status ], [ 'id' => $id ] );

		$log_table    = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();

		$wpdb->insert(
			$log_table,
			[
				'object_type' => 'application',
				'object_id'   => $id,
				'action'      => 'status_changed',
				'user_id'     => $current_user->ID,
				'user_name'   => $current_user->display_name,
				'old_value'   => $old_status,
				'new_value'   => $new_status,
				'created_at'  => current_time( 'mysql' ),
			]
		);

		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success is-dismissible"><p>';
				esc_html_e( 'Status wurde aktualisiert.', 'recruiting-playbook' );
				echo '</p></div>';
			}
		);
	}

	/**
	 * Log-Aktion formatieren
	 *
	 * @param array $entry Log entry.
	 * @return string
	 */
	private function formatLogAction( array $entry ): string {
		$action = $entry['action'];

		switch ( $action ) {
			case 'status_changed':
				$statuses  = ApplicationStatus::getAll();
				$old_label = $statuses[ $entry['old_value'] ] ?? $entry['old_value'];
				$new_label = $statuses[ $entry['new_value'] ] ?? $entry['new_value'];
				return sprintf(
					/* translators: 1: Old status, 2: New status */
					__( 'Status geändert: %1$s → %2$s', 'recruiting-playbook' ),
					$old_label,
					$new_label
				);

			case 'created':
				return __( 'Bewerbung eingegangen', 'recruiting-playbook' );

			case 'document_downloaded':
				return __( 'Dokument heruntergeladen', 'recruiting-playbook' );

			case 'email_sent':
				return __( 'E-Mail gesendet', 'recruiting-playbook' );

			case 'soft_deleted':
				return __( 'Bewerbung gelöscht', 'recruiting-playbook' );

			default:
				return $action;
		}
	}
}
