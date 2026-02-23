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
		// Capability-Check MUSS zuerst kommen (Sicherheit).
		// Prüfe rp_view_applications ODER manage_options (Admin-Fallback).
		if ( ! current_user_can( 'rp_view_applications' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to view this page.', 'recruiting-playbook' ),
				esc_html__( 'Access denied', 'recruiting-playbook' ),
				[ 'response' => 403 ]
			);
		}

		// Feature-Check (Pro-Lizenz).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'kanban_board' ) ) {
			$this->render_upgrade_notice();
			return;
		}

		// Assets laden.
		$this->enqueue_assets();

		// Wrapper mit Notice-Boundary für WordPress Admin Notices.
		echo '<div class="wrap">';
		echo '<h1 class="screen-reader-text">' . esc_html__( 'Kanban Board', 'recruiting-playbook' ) . '</h1>';
		echo '<hr class="wp-header-end">';

		// React-Mount-Point (React übernimmt das gesamte Layout).
		echo '<div id="rp-kanban-root">';
		echo '<div style="display: flex; align-items: center; justify-content: center; min-height: 300px; color: #6b7280;">';
		echo '<span class="spinner is-active" style="float: none; margin-right: 10px;"></span> ';
		echo esc_html__( 'Loading Kanban board...', 'recruiting-playbook' );
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Upgrade-Hinweis für Free-User
	 */
	private function render_upgrade_notice(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Kanban Board', 'recruiting-playbook' ) . '</h1>';

		if ( function_exists( 'rp_require_feature' ) ) {
			rp_require_feature( 'kanban_board', 'Kanban-Board', 'PRO' );
		} else {
			// Fallback wenn Helper nicht geladen.
			echo '<div class="notice notice-warning">';
			echo '<p>' . esc_html__( 'The Kanban board is a Pro feature.', 'recruiting-playbook' ) . '</p>';
			echo '</div>';
		}

		echo '</div>';
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

		// JavaScript wird bereits von Plugin.php geladen (rp-admin).
		// Hier nur die Kanban-spezifische Lokalisierung hinzufügen.
		wp_localize_script(
			'rp-admin',
			'rpKanban',
			[
				'apiUrl'    => rest_url( 'recruiting/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'adminUrl'  => admin_url(),
				'detailUrl' => admin_url( 'admin.php?page=rp-application-detail&id=' ),
				'logoUrl'   => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
				'jobs'      => $this->get_jobs(),
				'statuses'  => $this->get_statuses(),
				'i18n'      => $this->get_i18n_strings(),
			]
		);
	}

	/**
	 * Jobs für Filter
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_jobs(): array {
		$posts = get_posts(
			[
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$jobs = [];
		foreach ( $posts as $post ) {
			$jobs[] = [
				'id'    => $post->ID,
				'title' => $post->post_title,
			];
		}

		return $jobs;
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
				'label' => __( 'New', 'recruiting-playbook' ),
				'color' => $colors['new'] ?? '#2271b1',
			],
			[
				'id'    => 'screening',
				'label' => __( 'Screening', 'recruiting-playbook' ),
				'color' => $colors['screening'] ?? '#dba617',
			],
			[
				'id'    => 'interview',
				'label' => __( 'Interview', 'recruiting-playbook' ),
				'color' => $colors['interview'] ?? '#9b59b6',
			],
			[
				'id'    => 'offer',
				'label' => __( 'Offer', 'recruiting-playbook' ),
				'color' => $colors['offer'] ?? '#1e8cbe',
			],
			[
				'id'    => 'hired',
				'label' => __( 'Hired', 'recruiting-playbook' ),
				'color' => $colors['hired'] ?? '#00a32a',
			],
			[
				'id'        => 'rejected',
				'label'     => __( 'Rejected', 'recruiting-playbook' ),
				'color'     => $colors['rejected'] ?? '#d63638',
				'collapsed' => true,
			],
			[
				'id'        => 'withdrawn',
				'label'     => __( 'Withdrawn', 'recruiting-playbook' ),
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
			'loading'        => __( 'Loading applications...', 'recruiting-playbook' ),
			'error'          => __( 'Error loading', 'recruiting-playbook' ),
			'retry'          => __( 'Try again', 'recruiting-playbook' ),
			'noApplications' => __( 'No applications', 'recruiting-playbook' ),
			'today'          => __( 'Today', 'recruiting-playbook' ),
			'yesterday'      => __( 'Yesterday', 'recruiting-playbook' ),
			'daysAgo'        => __( '%d days ago', 'recruiting-playbook' ),
			'expand'         => __( 'Expand', 'recruiting-playbook' ),
			'collapse'       => __( 'Collapse', 'recruiting-playbook' ),
			'dragHint'       => __( 'Drag to change status', 'recruiting-playbook' ),
			'statusChanged'  => __( 'Status changed', 'recruiting-playbook' ),
			'updateFailed'   => __( 'Update failed', 'recruiting-playbook' ),
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
