<?php
/**
 * Automatischer E-Mail-Versand bei Status-Änderungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für automatischen E-Mail-Versand
 */
class AutoEmailService {

	/**
	 * Option-Name für Auto-E-Mail-Einstellungen
	 */
	private const OPTION_NAME = 'rp_auto_email_settings';

	/**
	 * Standard-Einstellungen
	 *
	 * @var array<string, array>
	 */
	private const DEFAULT_SETTINGS = [
		'rejected' => [
			'enabled'     => false,
			'template_id' => 0,
			'delay'       => 0, // Minuten
		],
		'interview' => [
			'enabled'     => false,
			'template_id' => 0,
			'delay'       => 0,
		],
		'offer' => [
			'enabled'     => false,
			'template_id' => 0,
			'delay'       => 0,
		],
		'hired' => [
			'enabled'     => false,
			'template_id' => 0,
			'delay'       => 0,
		],
	];

	/**
	 * E-Mail Service
	 *
	 * @var EmailService
	 */
	private EmailService $email_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->email_service = new EmailService();
	}

	/**
	 * Hooks registrieren
	 */
	public function registerHooks(): void {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return;
		}

		// Hook für Status-Änderungen.
		add_action( 'rp_application_status_changed', [ $this, 'handleStatusChange' ], 10, 3 );
	}

	/**
	 * Status-Änderung verarbeiten
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $old_status     Alter Status.
	 * @param string $new_status     Neuer Status.
	 */
	public function handleStatusChange( int $application_id, string $old_status, string $new_status ): void {
		$settings = $this->getSettings();

		// Prüfen ob Auto-E-Mail für diesen Status aktiviert ist.
		if ( ! isset( $settings[ $new_status ] ) ) {
			return;
		}

		$status_settings = $settings[ $new_status ];

		if ( empty( $status_settings['enabled'] ) || empty( $status_settings['template_id'] ) ) {
			return;
		}

		$template_id = (int) $status_settings['template_id'];
		$delay       = (int) ( $status_settings['delay'] ?? 0 );

		// Prüfen ob Template existiert.
		global $wpdb;
		$template_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}rp_email_templates WHERE id = %d AND is_active = 1",
				$template_id
			)
		);

		if ( ! $template_exists ) {
			return;
		}

		// E-Mail senden (sofort oder verzögert).
		if ( $delay > 0 && function_exists( 'as_schedule_single_action' ) ) {
			// Verzögerter Versand via Action Scheduler.
			as_schedule_single_action(
				time() + ( $delay * 60 ),
				'rp_send_auto_email',
				[
					'template_id'    => $template_id,
					'application_id' => $application_id,
					'trigger'        => 'status_change_' . $new_status,
				],
				'recruiting-playbook'
			);

			// Log.
			$this->logAutoEmail( $application_id, $new_status, 'scheduled', $template_id, $delay );
		} else {
			// Sofortiger Versand.
			$result = $this->email_service->sendWithTemplate(
				$template_id,
				$application_id,
				[ '_trigger' => 'auto_status_change' ],
				false // Nicht in Queue, direkt senden.
			);

			// Log.
			$this->logAutoEmail( $application_id, $new_status, $result ? 'sent' : 'failed', $template_id );
		}
	}

	/**
	 * Auto-E-Mail loggen
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $status         Status der den Versand ausgelöst hat.
	 * @param string $result         Ergebnis (sent, scheduled, failed).
	 * @param int    $template_id    Template-ID.
	 * @param int    $delay          Verzögerung in Minuten.
	 */
	private function logAutoEmail( int $application_id, string $status, string $result, int $template_id, int $delay = 0 ): void {
		global $wpdb;

		$log_table = $wpdb->prefix . 'rp_activity_log';

		$message = sprintf(
			'Auto-E-Mail (Template #%d) bei Status "%s": %s',
			$template_id,
			$status,
			$result
		);

		if ( $delay > 0 ) {
			$message .= sprintf( ' (verzögert um %d Min.)', $delay );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$log_table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
				'action'      => 'auto_email_' . $result,
				'user_id'     => 0, // System.
				'user_name'   => 'System',
				'message'     => $message,
				'created_at'  => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Einstellungen abrufen
	 *
	 * @return array<string, array>
	 */
	public function getSettings(): array {
		$settings = get_option( self::OPTION_NAME, [] );

		// Mit Defaults zusammenführen.
		return array_merge( self::DEFAULT_SETTINGS, $settings );
	}

	/**
	 * Einstellungen speichern
	 *
	 * @param array $settings Einstellungen.
	 * @return bool
	 */
	public function saveSettings( array $settings ): bool {
		$sanitized = [];

		foreach ( self::DEFAULT_SETTINGS as $status => $defaults ) {
			if ( isset( $settings[ $status ] ) ) {
				$sanitized[ $status ] = [
					'enabled'     => ! empty( $settings[ $status ]['enabled'] ),
					'template_id' => absint( $settings[ $status ]['template_id'] ?? 0 ),
					'delay'       => absint( $settings[ $status ]['delay'] ?? 0 ),
				];
			} else {
				$sanitized[ $status ] = $defaults;
			}
		}

		return update_option( self::OPTION_NAME, $sanitized );
	}

	/**
	 * Verfügbare Status für Auto-E-Mail
	 *
	 * @return array<string, string>
	 */
	public static function getAvailableStatuses(): array {
		return [
			'rejected'  => __( 'Abgelehnt', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Angebot', 'recruiting-playbook' ),
			'hired'     => __( 'Eingestellt', 'recruiting-playbook' ),
		];
	}
}
