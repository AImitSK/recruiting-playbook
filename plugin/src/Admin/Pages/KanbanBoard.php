<?php
/**
 * Kanban-Board Admin-Seite (Pro-Feature)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Constants\ApplicationStatus;

/**
 * Kanban-Board Seite
 */
class KanbanBoard {

	/**
	 * Seite rendern
	 */
	public function render(): void {
		// Feature-Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'kanban_board' ) ) {
			$this->render_upgrade_notice();
			return;
		}

		// Assets laden.
		$this->enqueue_assets();

		echo '<div class="wrap rp-kanban-wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Kanban-Board', 'recruiting-playbook' ) . '</h1>';

		// Link zur Listen-Ansicht.
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=rp-applications' ) ) . '" class="page-title-action">';
		echo esc_html__( 'Listen-Ansicht', 'recruiting-playbook' );
		echo '</a>';

		echo '<hr class="wp-header-end">';

		// Filter-Toolbar.
		$this->render_toolbar();

		// React-Mount-Point.
		echo '<div id="rp-kanban-root" class="rp-kanban-container">';
		echo '<div class="rp-kanban-loading">';
		echo '<span class="spinner is-active"></span> ';
		echo esc_html__( 'Lade Kanban-Board...', 'recruiting-playbook' );
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Upgrade-Hinweis für Free-User
	 */
	private function render_upgrade_notice(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Kanban-Board', 'recruiting-playbook' ) . '</h1>';

		if ( function_exists( 'rp_require_feature' ) ) {
			rp_require_feature( 'kanban_board', 'Kanban-Board', 'PRO' );
		} else {
			// Fallback wenn Helper nicht geladen.
			echo '<div class="notice notice-warning">';
			echo '<p>' . esc_html__( 'Das Kanban-Board ist ein Pro-Feature.', 'recruiting-playbook' ) . '</p>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Filter-Toolbar rendern
	 */
	private function render_toolbar(): void {
		$jobs = get_posts(
			[
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		?>
		<div class="rp-kanban-toolbar" id="rp-kanban-toolbar">
			<div class="rp-kanban-toolbar-left">
				<select id="rp-kanban-job-filter" class="rp-select">
					<option value=""><?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?></option>
					<?php foreach ( $jobs as $job ) : ?>
						<option value="<?php echo esc_attr( $job->ID ); ?>">
							<?php echo esc_html( $job->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input
					type="search"
					id="rp-kanban-search"
					class="rp-search-input"
					placeholder="<?php esc_attr_e( 'Bewerber suchen...', 'recruiting-playbook' ); ?>"
				/>
			</div>

			<div class="rp-kanban-toolbar-right">
				<button type="button" id="rp-kanban-refresh" class="button" title="<?php esc_attr_e( 'Aktualisieren', 'recruiting-playbook' ); ?>">
					<span class="dashicons dashicons-update"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Assets laden
	 */
	private function enqueue_assets(): void {
		// CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin-kanban.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-kanban',
				RP_PLUGIN_URL . 'assets/dist/css/admin-kanban.css',
				[],
				RP_VERSION
			);
		} else {
			// Inline-Styles als Fallback bis CSS-Datei erstellt wird.
			wp_add_inline_style( 'wp-admin', $this->get_inline_styles() );
		}

		// JavaScript.
		$js_file = RP_PLUGIN_DIR . 'assets/dist/js/kanban.js';
		if ( file_exists( $js_file ) ) {
			$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/kanban.asset.php';
			$asset      = file_exists( $asset_file )
				? require $asset_file
				: [ 'dependencies' => [], 'version' => RP_VERSION ];

			wp_enqueue_script(
				'rp-kanban',
				RP_PLUGIN_URL . 'assets/dist/js/kanban.js',
				array_merge( $asset['dependencies'], [ 'wp-element', 'wp-api-fetch', 'wp-i18n' ] ),
				$asset['version'],
				true
			);

			wp_set_script_translations( 'rp-kanban', 'recruiting-playbook' );
		}

		// Lokalisierung (immer laden für zukünftiges JS).
		wp_localize_script(
			file_exists( $js_file ) ? 'rp-kanban' : 'wp-api-fetch',
			'rpKanban',
			[
				'apiUrl'    => rest_url( 'recruiting/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'adminUrl'  => admin_url(),
				'detailUrl' => admin_url( 'admin.php?page=rp-application-detail&id=' ),
				'statuses'  => $this->get_statuses(),
				'i18n'      => $this->get_i18n_strings(),
			]
		);
	}

	/**
	 * Status-Konfiguration
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_statuses(): array {
		$colors = ApplicationStatus::getColors();

		return [
			[
				'id'    => 'new',
				'label' => __( 'Neu', 'recruiting-playbook' ),
				'color' => $colors['new'] ?? '#2271b1',
			],
			[
				'id'    => 'screening',
				'label' => __( 'In Prüfung', 'recruiting-playbook' ),
				'color' => $colors['screening'] ?? '#dba617',
			],
			[
				'id'    => 'interview',
				'label' => __( 'Interview', 'recruiting-playbook' ),
				'color' => $colors['interview'] ?? '#9b59b6',
			],
			[
				'id'    => 'offer',
				'label' => __( 'Angebot', 'recruiting-playbook' ),
				'color' => $colors['offer'] ?? '#1e8cbe',
			],
			[
				'id'    => 'hired',
				'label' => __( 'Eingestellt', 'recruiting-playbook' ),
				'color' => $colors['hired'] ?? '#00a32a',
			],
			[
				'id'        => 'rejected',
				'label'     => __( 'Abgelehnt', 'recruiting-playbook' ),
				'color'     => $colors['rejected'] ?? '#d63638',
				'collapsed' => true,
			],
			[
				'id'        => 'withdrawn',
				'label'     => __( 'Zurückgezogen', 'recruiting-playbook' ),
				'color'     => $colors['withdrawn'] ?? '#787c82',
				'collapsed' => true,
			],
		];
	}

	/**
	 * Übersetzungs-Strings für JavaScript
	 *
	 * @return array<string, string>
	 */
	private function get_i18n_strings(): array {
		return [
			'loading'        => __( 'Lade Bewerbungen...', 'recruiting-playbook' ),
			'error'          => __( 'Fehler beim Laden', 'recruiting-playbook' ),
			'retry'          => __( 'Erneut versuchen', 'recruiting-playbook' ),
			'noApplications' => __( 'Keine Bewerbungen', 'recruiting-playbook' ),
			'today'          => __( 'Heute', 'recruiting-playbook' ),
			'yesterday'      => __( 'Gestern', 'recruiting-playbook' ),
			'daysAgo'        => __( 'vor %d Tagen', 'recruiting-playbook' ),
			'expand'         => __( 'Aufklappen', 'recruiting-playbook' ),
			'collapse'       => __( 'Zuklappen', 'recruiting-playbook' ),
			'dragHint'       => __( 'Ziehen um Status zu ändern', 'recruiting-playbook' ),
			'statusChanged'  => __( 'Status geändert', 'recruiting-playbook' ),
			'updateFailed'   => __( 'Aktualisierung fehlgeschlagen', 'recruiting-playbook' ),
		];
	}

	/**
	 * Inline-Styles als Fallback
	 *
	 * @return string CSS.
	 */
	private function get_inline_styles(): string {
		return '
			.rp-kanban-wrap { max-width: 100%; overflow-x: auto; }

			.rp-kanban-toolbar {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 15px 0;
				gap: 15px;
				flex-wrap: wrap;
			}

			.rp-kanban-toolbar-left {
				display: flex;
				gap: 10px;
				align-items: center;
			}

			.rp-kanban-toolbar .rp-select {
				min-width: 200px;
			}

			.rp-kanban-toolbar .rp-search-input {
				min-width: 250px;
				padding: 5px 10px;
			}

			.rp-kanban-container {
				min-height: 500px;
				background: #f0f0f1;
				border-radius: 4px;
				padding: 20px;
			}

			.rp-kanban-loading {
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 40px;
				color: #646970;
			}

			.rp-kanban-loading .spinner {
				float: none;
				margin-right: 10px;
			}

			.rp-kanban-board {
				display: flex;
				gap: 15px;
				overflow-x: auto;
				padding-bottom: 20px;
			}

			.rp-kanban-column {
				flex: 0 0 280px;
				background: #fff;
				border-radius: 4px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}

			.rp-kanban-column.is-collapsed {
				flex: 0 0 50px;
			}

			.rp-kanban-column.is-over {
				box-shadow: 0 0 0 2px var(--column-color, #2271b1);
			}

			.rp-kanban-column-header {
				display: flex;
				align-items: center;
				padding: 12px 15px;
				border-bottom: 1px solid #dcdcde;
				cursor: pointer;
				gap: 10px;
			}

			.rp-kanban-column-color {
				width: 12px;
				height: 12px;
				border-radius: 50%;
				flex-shrink: 0;
			}

			.rp-kanban-column-title {
				margin: 0;
				font-size: 14px;
				font-weight: 600;
				flex-grow: 1;
			}

			.rp-kanban-column-count {
				background: #f0f0f1;
				padding: 2px 8px;
				border-radius: 10px;
				font-size: 12px;
				font-weight: 600;
			}

			.rp-kanban-column-content {
				padding: 10px;
				min-height: 100px;
				max-height: calc(100vh - 350px);
				overflow-y: auto;
			}

			.rp-kanban-empty {
				text-align: center;
				padding: 30px 15px;
				color: #646970;
				font-style: italic;
			}

			.rp-kanban-card {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				padding: 12px;
				margin-bottom: 10px;
				cursor: grab;
				transition: box-shadow 0.2s, transform 0.2s;
			}

			.rp-kanban-card:hover {
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}

			.rp-kanban-card.is-dragging {
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
				transform: rotate(3deg);
				cursor: grabbing;
			}

			.rp-kanban-card-header {
				display: flex;
				gap: 10px;
				margin-bottom: 8px;
			}

			.rp-kanban-card-avatar {
				width: 36px;
				height: 36px;
				border-radius: 50%;
				background: #2271b1;
				color: #fff;
				display: flex;
				align-items: center;
				justify-content: center;
				font-weight: 600;
				font-size: 14px;
				flex-shrink: 0;
			}

			.rp-kanban-card-info {
				flex-grow: 1;
				min-width: 0;
			}

			.rp-kanban-card-name {
				font-weight: 600;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.rp-kanban-card-email {
				font-size: 12px;
				color: #646970;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.rp-kanban-card-meta {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				font-size: 12px;
				color: #646970;
			}

			.rp-kanban-card-meta .dashicons {
				font-size: 14px;
				width: 14px;
				height: 14px;
				margin-right: 3px;
			}

			.rp-kanban-card-job {
				flex: 1;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.rp-kanban-card-documents {
				display: flex;
				align-items: center;
				margin-top: 8px;
				padding-top: 8px;
				border-top: 1px solid #f0f0f1;
				font-size: 12px;
				color: #646970;
			}
		';
	}
}
