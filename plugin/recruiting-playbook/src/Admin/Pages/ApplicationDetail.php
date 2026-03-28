<?php

/**
 * Detailansicht einer Bewerbung
 *
 * @package RecruitingPlaybook
 */
declare (strict_types = 1);
namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;
use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Constants\DocumentType;
use RecruitingPlaybook\Services\DocumentDownloadService;
use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Services\EmailTemplateService;
use RecruitingPlaybook\Repositories\EmailLogRepository;
use RecruitingPlaybook\Repositories\SignatureRepository;
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.DB.PreparedSQL
// phpcs:disable PluginCheck.Security.DirectDB
/**
 * Detailansicht einer Bewerbung
 */
class ApplicationDetail {
    /**
     * Prüft ob Pro-Features verfügbar sind
     *
     * @return bool
     */
    private function hasProFeatures() : bool {
        return function_exists( 'rp_can' ) && rp_can( 'advanced_applicant_management' );
    }

    /**
     * Assets für Pro-Features laden
     *
     * @param int $application_id Bewerbungs-ID.
     */
    public function enqueue_assets( int $application_id ) : void {
        // CSS für Bewerber-Detailseite.
        $css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin-applicant.css';
        if ( file_exists( $css_file ) ) {
            wp_enqueue_style(
                'rp-applicant',
                RP_PLUGIN_URL . 'assets/dist/css/admin-applicant.css',
                ['rp-admin'],
                RP_VERSION
            );
        }
        // Pro-Features: React-Komponenten.
        if ( $this->hasProFeatures() ) {
            $js_file = RP_PLUGIN_DIR . 'assets/dist/js/admin.js';
            $asset_file = RP_PLUGIN_DIR . 'assets/dist/js/admin.asset.php';
            if ( file_exists( $js_file ) && file_exists( $asset_file ) ) {
                $assets = (include $asset_file);
                wp_enqueue_script(
                    'rp-applicant',
                    RP_PLUGIN_URL . 'assets/dist/js/admin.js',
                    $assets['dependencies'] ?? ['wp-element', 'wp-api-fetch', 'wp-i18n'],
                    $assets['version'] ?? RP_VERSION,
                    true
                );
                wp_set_script_translations( 'rp-applicant', 'recruiting-playbook', RP_PLUGIN_DIR . 'languages' );
                // Konfiguration für React.
                wp_localize_script( 'rp-applicant', 'rpApplicant', [
                    'applicationId' => $application_id,
                    'apiUrl'        => rest_url( 'recruiting/v1/' ),
                    'nonce'         => wp_create_nonce( 'wp_rest' ),
                    'listUrl'       => admin_url( 'admin.php?page=recruiting-playbook' ),
                    'logoUrl'       => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
                    'canSendEmails' => function_exists( 'rp_can' ) && rp_can( 'email_templates' ),
                    'i18n'          => [
                        'loadingApplication'      => __( 'Loading application...', 'recruiting-playbook' ),
                        'errorLoadingApplication' => __( 'Error loading application', 'recruiting-playbook' ),
                        'applicationNotFound'     => __( 'Application not found.', 'recruiting-playbook' ),
                        'errorChangingStatus'     => __( 'Error changing status', 'recruiting-playbook' ),
                        'backToList'              => __( 'Back to list', 'recruiting-playbook' ),
                        'application'             => __( 'Application', 'recruiting-playbook' ),
                        'status'                  => __( 'Status', 'recruiting-playbook' ),
                        'rating'                  => __( 'Rating', 'recruiting-playbook' ),
                        'documents'               => __( 'Documents', 'recruiting-playbook' ),
                        'view'                    => __( 'View', 'recruiting-playbook' ),
                        'download'                => __( 'Download', 'recruiting-playbook' ),
                        'appliedOn'               => __( 'Applied on', 'recruiting-playbook' ),
                        'retry'                   => __( 'Retry', 'recruiting-playbook' ),
                    ],
                ] );
            }
        }
    }

    /**
     * Seite rendern
     */
    public function render() : void {
        $id = ( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( !$id ) {
            wp_die( esc_html__( 'No application specified.', 'recruiting-playbook' ) );
        }
        // Assets laden.
        $this->enqueue_assets( $id );
        // Pro-Features: React-basierte Detailseite.
        if ( $this->hasProFeatures() ) {
            $this->renderProVersion( $id );
            return;
        }
        // Free-Version: PHP-basierte Detailseite.
        // WICHTIG: Zuerst Status-Update verarbeiten, DANN Daten laden!
        $this->processStatusUpdate( $id );
        $application = $this->getApplication( $id );
        if ( !$application ) {
            wp_die( esc_html__( 'Application not found.', 'recruiting-playbook' ) );
        }
        $candidate = $this->getCandidate( (int) $application['candidate_id'] );
        $job = get_post( $application['job_id'] );
        $documents = $this->getDocuments( $id );
        $activity_log = $this->getActivityLog( $id );
        $email_history = [];
        $email_templates = [];
        ?>
		<div class="wrap rp-application-detail">
			<h1>
				<?php 
        printf( 
            /* translators: %s: Applicant name */
            esc_html__( 'Application from %s', 'recruiting-playbook' ),
            esc_html( $candidate['first_name'] . ' ' . $candidate['last_name'] )
         );
        ?>
				<a href="<?php 
        echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) );
        ?>" class="page-title-action">
					<?php 
        esc_html_e( 'Back to list', 'recruiting-playbook' );
        ?>
				</a>
			</h1>

			<div class="rp-detail-grid">
				<!-- Hauptbereich -->
				<div class="rp-detail-main">
					<!-- Kontaktdaten -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'Contact information', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php 
        esc_html_e( 'Name', 'recruiting-playbook' );
        ?></th>
									<td><?php 
        echo esc_html( $candidate['first_name'] . ' ' . $candidate['last_name'] );
        ?></td>
								</tr>
								<tr>
									<th><?php 
        esc_html_e( 'Email', 'recruiting-playbook' );
        ?></th>
									<td>
										<a href="mailto:<?php 
        echo esc_attr( $candidate['email'] );
        ?>">
											<?php 
        echo esc_html( $candidate['email'] );
        ?>
										</a>
									</td>
								</tr>
								<?php 
        if ( !empty( $candidate['phone'] ) ) {
            ?>
								<tr>
									<th><?php 
            esc_html_e( 'Phone', 'recruiting-playbook' );
            ?></th>
									<td>
										<a href="tel:<?php 
            echo esc_attr( $candidate['phone'] );
            ?>">
											<?php 
            echo esc_html( $candidate['phone'] );
            ?>
										</a>
									</td>
								</tr>
								<?php 
        }
        ?>
							</table>
						</div>
					</div>

					<!-- Anschreiben -->
					<?php 
        if ( !empty( $application['cover_letter'] ) ) {
            ?>
					<div class="postbox">
						<h2 class="hndle"><?php 
            esc_html_e( 'Cover letter', 'recruiting-playbook' );
            ?></h2>
						<div class="inside">
							<div class="rp-cover-letter">
								<?php 
            echo wp_kses_post( nl2br( $application['cover_letter'] ) );
            ?>
							</div>
						</div>
					</div>
					<?php 
        }
        ?>

					<!-- Dokumente -->
					<div class="postbox">
						<h2 class="hndle">
							<?php 
        esc_html_e( 'Documents', 'recruiting-playbook' );
        ?>
							<span class="count">(<?php 
        echo count( $documents );
        ?>)</span>
						</h2>
						<div class="inside">
							<?php 
        if ( empty( $documents ) ) {
            ?>
								<p class="description">
									<?php 
            esc_html_e( 'No documents uploaded.', 'recruiting-playbook' );
            ?>
								</p>
							<?php 
        } else {
            ?>
								<table class="widefat striped">
									<thead>
										<tr>
											<th><?php 
            esc_html_e( 'Document', 'recruiting-playbook' );
            ?></th>
											<th><?php 
            esc_html_e( 'Type', 'recruiting-playbook' );
            ?></th>
											<th><?php 
            esc_html_e( 'Size', 'recruiting-playbook' );
            ?></th>
											<th><?php 
            esc_html_e( 'Action', 'recruiting-playbook' );
            ?></th>
										</tr>
									</thead>
									<tbody>
										<?php 
            foreach ( $documents as $doc ) {
                ?>
											<?php 
                $download_url = DocumentDownloadService::generateDownloadUrl( (int) $doc['id'] );
                $type_labels = DocumentType::getAll();
                ?>
											<tr>
												<td>
													<span class="dashicons dashicons-media-document"></span>
													<?php 
                echo esc_html( $doc['original_name'] );
                ?>
												</td>
												<td><?php 
                echo esc_html( $type_labels[$doc['type']] ?? $doc['type'] );
                ?></td>
												<td><?php 
                echo esc_html( size_format( (int) $doc['size'] ) );
                ?></td>
												<td>
													<a href="<?php 
                echo esc_url( $download_url );
                ?>" class="button button-small">
														<?php 
                esc_html_e( 'Download', 'recruiting-playbook' );
                ?>
													</a>
												</td>
											</tr>
										<?php 
            }
            ?>
									</tbody>
								</table>
							<?php 
        }
        ?>
						</div>
					</div>

					<!-- Aktivitäts-Log -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'Activity log', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<?php 
        if ( empty( $activity_log ) ) {
            ?>
								<p class="description">
									<?php 
            esc_html_e( 'No activities recorded.', 'recruiting-playbook' );
            ?>
								</p>
							<?php 
        } else {
            ?>
								<ul class="rp-activity-log">
									<?php 
            foreach ( $activity_log as $entry ) {
                ?>
										<li>
											<span class="rp-log-time">
												<?php 
                echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $entry['created_at'] ) ) );
                ?>
											</span>
											<span class="rp-log-user">
												<?php 
                echo esc_html( ( $entry['user_name'] ?: __( 'System', 'recruiting-playbook' ) ) );
                ?>
											</span>
											<span class="rp-log-action">
												<?php 
                echo esc_html( $this->formatLogAction( $entry ) );
                ?>
											</span>
										</li>
									<?php 
            }
            ?>
								</ul>
							<?php 
        }
        ?>
						</div>
					</div>

					<?php 
        ?>
				</div>

				<!-- Sidebar -->
				<div class="rp-detail-sidebar">
					<!-- Status -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'Status', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<form method="post">
								<?php 
        wp_nonce_field( 'rp_update_status_' . $id );
        ?>
								<input type="hidden" name="application_id" value="<?php 
        echo esc_attr( $id );
        ?>" />

								<p>
									<select name="status" id="rp-status-select" style="width: 100%;">
										<?php 
        foreach ( ApplicationStatus::getAll() as $value => $label ) {
            ?>
											<option value="<?php 
            echo esc_attr( $value );
            ?>" <?php 
            selected( $application['status'], $value );
            ?>>
												<?php 
            echo esc_html( $label );
            ?>
											</option>
										<?php 
        }
        ?>
									</select>
								</p>

								<p>
									<button type="submit" name="update_status" class="button button-primary" style="width: 100%;">
										<?php 
        esc_html_e( 'Update status', 'recruiting-playbook' );
        ?>
									</button>
								</p>
							</form>
						</div>
					</div>

					<?php 
        ?>

					<!-- Stelle -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'Job', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<?php 
        if ( $job ) {
            ?>
								<p>
									<strong><?php 
            echo esc_html( $job->post_title );
            ?></strong>
								</p>
								<p>
									<a href="<?php 
            echo esc_url( get_edit_post_link( $job->ID ) );
            ?>" class="button button-small">
										<?php 
            esc_html_e( 'Edit', 'recruiting-playbook' );
            ?>
									</a>
									<a href="<?php 
            echo esc_url( get_permalink( $job->ID ) );
            ?>" class="button button-small" target="_blank">
										<?php 
            esc_html_e( 'View', 'recruiting-playbook' );
            ?>
									</a>
								</p>
							<?php 
        } else {
            ?>
								<p class="description">
									<?php 
            esc_html_e( 'Job was deleted.', 'recruiting-playbook' );
            ?>
								</p>
							<?php 
        }
        ?>
						</div>
					</div>

					<!-- Meta-Daten -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'Details', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<p>
								<strong><?php 
        esc_html_e( 'Received:', 'recruiting-playbook' );
        ?></strong><br>
								<?php 
        echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $application['created_at'] ) ) );
        ?>
							</p>
							<p>
								<strong><?php 
        esc_html_e( 'Last updated:', 'recruiting-playbook' );
        ?></strong><br>
								<?php 
        echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $application['updated_at'] ) ) );
        ?>
							</p>
							<?php 
        if ( !empty( $application['source_url'] ) ) {
            ?>
							<p>
								<strong><?php 
            esc_html_e( 'Source:', 'recruiting-playbook' );
            ?></strong><br>
								<small><?php 
            echo esc_url( $application['source_url'] );
            ?></small>
							</p>
							<?php 
        }
        ?>
						</div>
					</div>

					<!-- DSGVO-Aktionen -->
					<div class="postbox">
						<h2 class="hndle"><?php 
        esc_html_e( 'GDPR', 'recruiting-playbook' );
        ?></h2>
						<div class="inside">
							<p>
								<a href="<?php 
        echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=recruiting-playbook&action=export_data&id=' . $id ), 'rp_export_data_' . $id ) );
        ?>" class="button button-small" style="width: 100%; text-align: center;">
									<?php 
        esc_html_e( 'Export data', 'recruiting-playbook' );
        ?>
								</a>
							</p>
							<p>
								<a href="<?php 
        echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=recruiting-playbook&action=delete&id=' . $id ), 'rp_delete_' . $id ) );
        ?>" class="button button-small button-link-delete" style="width: 100%; text-align: center;" onclick="return confirm('<?php 
        esc_attr_e( 'Really delete this application?', 'recruiting-playbook' );
        ?>');">
									<?php 
        esc_html_e( 'Delete application', 'recruiting-playbook' );
        ?>
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php 
    }

    /**
     * Bewerbung laden
     *
     * @param int $id Application ID.
     * @return array|null
     */
    private function getApplication( int $id ) : ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_applications';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
    }

    /**
     * Kandidat laden
     *
     * @param int $id Candidate ID.
     * @return array|null
     */
    private function getCandidate( int $id ) : ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_candidates';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
    }

    /**
     * Dokumente laden
     *
     * @param int $application_id Application ID.
     * @return array
     */
    private function getDocuments( int $application_id ) : array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_documents';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return ( $wpdb->get_results( $wpdb->prepare( "SELECT id, original_name, document_type as type, file_size as size, file_path, created_at\n\t\t\t\t FROM {$table}\n\t\t\t\t WHERE application_id = %d AND is_deleted = 0\n\t\t\t\t ORDER BY created_at ASC", $application_id ), ARRAY_A ) ?: [] );
    }

    /**
     * Aktivitäts-Log laden
     *
     * @param int $application_id Application ID.
     * @return array
     */
    private function getActivityLog( int $application_id ) : array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_activity_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}\n\t\t\t\t WHERE object_type = 'application' AND object_id = %d\n\t\t\t\t ORDER BY created_at DESC\n\t\t\t\t LIMIT 50", $application_id ), ARRAY_A ) ?: [] );
    }

    /**
     * Status-Update verarbeiten
     *
     * @param int $id Application ID.
     */
    private function processStatusUpdate( int $id ) : void {
        if ( !isset( $_POST['update_status'] ) ) {
            return;
        }
        check_admin_referer( 'rp_update_status_' . $id );
        $new_status = ( isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '' );
        if ( !array_key_exists( $new_status, ApplicationStatus::getAll() ) ) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'rp_applications';
        $old_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id ) );
        if ( $old_status === $new_status ) {
            return;
        }
        $wpdb->update( $table, [
            'status' => $new_status,
        ], [
            'id' => $id,
        ] );
        // Action für Auto-E-Mail und andere Hooks auslösen.
        do_action(
            'recruiting_playbook_application_status_changed',
            $id,
            $old_status,
            $new_status
        );
        $log_table = $wpdb->prefix . 'rp_activity_log';
        $current_user = wp_get_current_user();
        $wpdb->insert( $log_table, [
            'object_type' => 'application',
            'object_id'   => $id,
            'action'      => 'status_changed',
            'user_id'     => $current_user->ID,
            'user_name'   => $current_user->display_name,
            'old_value'   => $old_status,
            'new_value'   => $new_status,
            'created_at'  => current_time( 'mysql' ),
        ] );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e( 'Status was updated.', 'recruiting-playbook' );
            echo '</p></div>';
        } );
    }

    /**
     * Log-Aktion formatieren
     *
     * @param array $entry Log entry.
     * @return string
     */
    private function formatLogAction( array $entry ) : string {
        $action = $entry['action'];
        switch ( $action ) {
            case 'status_changed':
                $statuses = ApplicationStatus::getAll();
                $old_label = $statuses[$entry['old_value']] ?? $entry['old_value'];
                $new_label = $statuses[$entry['new_value']] ?? $entry['new_value'];
                return sprintf( 
                    /* translators: 1: Old status, 2: New status */
                    __( 'Status changed: %1$s → %2$s', 'recruiting-playbook' ),
                    $old_label,
                    $new_label
                 );
            case 'created':
                return __( 'Application received', 'recruiting-playbook' );
            case 'document_downloaded':
                return __( 'Document downloaded', 'recruiting-playbook' );
            case 'email_sent':
                return __( 'Email sent', 'recruiting-playbook' );
            case 'soft_deleted':
                return __( 'Application deleted', 'recruiting-playbook' );
            default:
                return $action;
        }
    }

    /**
     * Pro-Version der Detailseite rendern (React-basiert)
     *
     * @param int $id Application ID.
     */
    private function renderProVersion( int $id ) : void {
        ?>
		<div class="wrap rp-admin">
			<div id="rp-applicant-detail-root" data-application-id="<?php 
        echo esc_attr( $id );
        ?>"></div>
		</div>
		<?php 
    }

    /**
     * E-Mail-Historie laden
     *
     * @param int $application_id Application ID.
     * @return array
     */
    private function getEmailHistory( int $application_id ) : array {
        return [];
    }

    /**
     * E-Mail-Templates laden
     *
     * @return array
     */
    private function getEmailTemplates() : array {
        return [];
    }

    /**
     * E-Mail-Composer rendern
     *
     * @param int   $application_id Application ID.
     * @param array $candidate      Kandidaten-Daten.
     * @param array $templates      Verfügbare Templates.
     */
    private function renderEmailComposer( int $application_id, array $candidate, array $templates ) : void {
        // End is__premium_only.
    }

    /**
     * E-Mail-Historie rendern
     *
     * @param array $emails E-Mail-Einträge.
     */
    private function renderEmailHistory( array $emails ) : void {
        // End is__premium_only.
    }

    /**
     * E-Mail-Status Badge rendern
     *
     * @param string $status        Status.
     * @param string $error_message Fehlermeldung.
     */
    private function renderEmailStatus( string $status, string $error_message = '' ) : void {
        $labels = [
            'pending'   => __( 'Pending', 'recruiting-playbook' ),
            'queued'    => __( 'Queued', 'recruiting-playbook' ),
            'sent'      => __( 'Sent', 'recruiting-playbook' ),
            'failed'    => __( 'Failed', 'recruiting-playbook' ),
            'cancelled' => __( 'Cancelled', 'recruiting-playbook' ),
        ];
        $colors = [
            'pending'   => '#dba617',
            'queued'    => '#2271b1',
            'sent'      => '#00a32a',
            'failed'    => '#d63638',
            'cancelled' => '#787c82',
        ];
        $label = $labels[$status] ?? $status;
        $color = $colors[$status] ?? '#787c82';
        printf(
            '<span class="rp-status-badge" style="background-color: %s; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;" title="%s">%s</span>',
            esc_attr( $color ),
            esc_attr( $error_message ),
            esc_html( $label )
        );
    }

    /**
     * E-Mail-Versand verarbeiten
     *
     * @param int $application_id Application ID.
     */
    private function processEmailSend( int $application_id ) : void {
        // End is__premium_only.
    }

}
