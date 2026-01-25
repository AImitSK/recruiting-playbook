<?php
/**
 * Email Settings Page - Admin-Seite für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-Seite für E-Mail-Templates
 */
class EmailSettingsPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'registerSubmenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}

	/**
	 * Submenu registrieren
	 */
	public function registerSubmenu(): void {
		add_submenu_page(
			'recruiting-playbook',
			__( 'E-Mail-Templates', 'recruiting-playbook' ),
			$this->getMenuLabel(),
			'manage_options',
			'rp-email-templates',
			[ $this, 'render' ]
		);
	}

	/**
	 * Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getMenuLabel(): string {
		$label = __( 'E-Mail-Templates', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Seite rendern
	 */
	public function render(): void {
		// Pro-Feature-Check.
		$is_pro = function_exists( 'rp_can' ) && rp_can( 'email_templates' );

		if ( ! $is_pro ) {
			$this->renderUpgradeNotice();
			return;
		}

		// React-App Container.
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'E-Mail-Templates', 'recruiting-playbook' ) . '</h1>';
		echo '<div id="rp-email-templates-app"></div>';
		echo '</div>';
	}

	/**
	 * Upgrade-Hinweis für Free-Version
	 */
	private function renderUpgradeNotice(): void {
		$upgrade_url = function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '#';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'E-Mail-Templates', 'recruiting-playbook' ) . '</h1>';
		echo '<div class="notice notice-info">';
		echo '<p>';
		echo esc_html__( 'E-Mail-Templates sind ein Pro-Feature. Upgraden Sie, um benutzerdefinierte E-Mail-Vorlagen zu erstellen und zu verwalten.', 'recruiting-playbook' );
		echo '</p>';
		echo '<p>';
		echo '<a href="' . esc_url( $upgrade_url ) . '" class="button button-primary">';
		echo esc_html__( 'Auf Pro upgraden', 'recruiting-playbook' );
		echo '</a>';
		echo '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Assets laden
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueAssets( string $hook ): void {
		// Nur auf unserer Seite laden.
		if ( 'recruiting_page_rp-email-templates' !== $hook ) {
			return;
		}

		// Pro-Check.
		$is_pro = function_exists( 'rp_can' ) && rp_can( 'email_templates' );
		if ( ! $is_pro ) {
			return;
		}

		// CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin-email.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-admin-email',
				RP_PLUGIN_URL . 'assets/dist/css/admin-email.css',
				[ 'wp-components' ],
				RP_VERSION
			);
		}

		// JavaScript.
		$js_file = RP_PLUGIN_DIR . 'assets/dist/js/admin-email.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'rp-admin-email',
				RP_PLUGIN_URL . 'assets/dist/js/admin-email.js',
				[ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
				RP_VERSION,
				true
			);

			wp_localize_script( 'rp-admin-email', 'rpEmailData', $this->getScriptData() );
		}
	}

	/**
	 * Script-Daten für Lokalisierung
	 *
	 * @return array
	 */
	private function getScriptData(): array {
		return [
			'apiBase'     => rest_url( 'recruiting/v1' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'isProActive' => function_exists( 'rp_can' ) && rp_can( 'email_templates' ),
			'i18n'        => [
				'templates'           => __( 'Templates', 'recruiting-playbook' ),
				'newTemplate'         => __( 'Neues Template', 'recruiting-playbook' ),
				'editTemplate'        => __( 'Template bearbeiten', 'recruiting-playbook' ),
				'deleteTemplate'      => __( 'Template löschen', 'recruiting-playbook' ),
				'duplicateTemplate'   => __( 'Template duplizieren', 'recruiting-playbook' ),
				'resetTemplate'       => __( 'Auf Standard zurücksetzen', 'recruiting-playbook' ),
				'save'                => __( 'Speichern', 'recruiting-playbook' ),
				'cancel'              => __( 'Abbrechen', 'recruiting-playbook' ),
				'delete'              => __( 'Löschen', 'recruiting-playbook' ),
				'preview'             => __( 'Vorschau', 'recruiting-playbook' ),
				'name'                => __( 'Name', 'recruiting-playbook' ),
				'subject'             => __( 'Betreff', 'recruiting-playbook' ),
				'category'            => __( 'Kategorie', 'recruiting-playbook' ),
				'status'              => __( 'Status', 'recruiting-playbook' ),
				'active'              => __( 'Aktiv', 'recruiting-playbook' ),
				'inactive'            => __( 'Inaktiv', 'recruiting-playbook' ),
				'default'             => __( 'Standard', 'recruiting-playbook' ),
				'system'              => __( 'System', 'recruiting-playbook' ),
				'placeholders'        => __( 'Platzhalter', 'recruiting-playbook' ),
				'insertPlaceholder'   => __( 'Platzhalter einfügen', 'recruiting-playbook' ),
				'noTemplates'         => __( 'Keine Templates gefunden.', 'recruiting-playbook' ),
				'confirmDelete'       => __( 'Möchten Sie dieses Template wirklich löschen?', 'recruiting-playbook' ),
				'confirmReset'        => __( 'Möchten Sie dieses Template auf den Standard zurücksetzen?', 'recruiting-playbook' ),
				'templateSaved'       => __( 'Template wurde gespeichert.', 'recruiting-playbook' ),
				'templateDeleted'     => __( 'Template wurde gelöscht.', 'recruiting-playbook' ),
				'templateDuplicated'  => __( 'Template wurde dupliziert.', 'recruiting-playbook' ),
				'templateReset'       => __( 'Template wurde zurückgesetzt.', 'recruiting-playbook' ),
				'errorLoading'        => __( 'Fehler beim Laden der Templates.', 'recruiting-playbook' ),
				'errorSaving'         => __( 'Fehler beim Speichern.', 'recruiting-playbook' ),
				'errorDeleting'       => __( 'Fehler beim Löschen.', 'recruiting-playbook' ),
				'categories'          => [
					'system'      => __( 'System', 'recruiting-playbook' ),
					'application' => __( 'Bewerbung', 'recruiting-playbook' ),
					'interview'   => __( 'Interview', 'recruiting-playbook' ),
					'offer'       => __( 'Angebot', 'recruiting-playbook' ),
					'rejection'   => __( 'Absage', 'recruiting-playbook' ),
					'custom'      => __( 'Benutzerdefiniert', 'recruiting-playbook' ),
				],
			],
		];
	}
}
