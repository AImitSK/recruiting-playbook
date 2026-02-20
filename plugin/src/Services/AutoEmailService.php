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
		'new' => [
			'enabled'     => false,
			'template_id' => 0,
			'delay'       => 0, // Minuten
		],
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

		// Hook für verzögerten E-Mail-Versand (Action Scheduler Callback).
		add_action( 'rp_send_auto_email', [ $this, 'processScheduledEmail' ], 10, 3 );
	}

	/**
	 * Verzögerte E-Mail verarbeiten (Action Scheduler Callback)
	 *
	 * @param int    $template_id    Template-ID.
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $trigger        Auslöser.
	 */
	public function processScheduledEmail( int $template_id, int $application_id, string $trigger = 'scheduled' ): void {

		if ( ! $template_id || ! $application_id ) {
			return;
		}

		// E-Mail senden.
		$result = $this->email_service->sendWithTemplate(
			$template_id,
			$application_id,
			[ '_trigger' => $trigger ],
			false // Direkt senden, nicht erneut in Queue.
		);

		// Status aus Trigger extrahieren für Log.
		$status = str_replace( 'status_change_', '', $trigger );

		// Log.
		$this->logAutoEmail(
			$application_id,
			$status,
			$result ? 'sent' : 'failed',
			$template_id
		);
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
				$template_id = absint( $settings[ $status ]['template_id'] ?? 0 );
				$sanitized[ $status ] = [
					// Automatisch aktiviert wenn Template ausgewählt (bessere UX).
					'enabled'     => ! empty( $settings[ $status ]['enabled'] ) || $template_id > 0,
					'template_id' => $template_id,
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
			'new'       => __( 'New Application', 'recruiting-playbook' ),
			'rejected'  => __( 'Rejected', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Offer', 'recruiting-playbook' ),
			'hired'     => __( 'Hired', 'recruiting-playbook' ),
		];
	}

	/**
	 * Prüft ob für neue Bewerbungen ein Auto-E-Mail-Template konfiguriert ist
	 *
	 * @return array{enabled: bool, template_id: int}|null Null wenn nicht Pro oder nicht konfiguriert
	 */
	public function getNewApplicationSettings(): ?array {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return null;
		}

		$settings = $this->getSettings();

		if ( ! isset( $settings['new'] ) ) {
			return null;
		}

		$new_settings = $settings['new'];

		if ( empty( $new_settings['enabled'] ) || empty( $new_settings['template_id'] ) ) {
			return null;
		}

		// Prüfen ob Template existiert und aktiv ist.
		global $wpdb;
		$template_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}rp_email_templates WHERE id = %d AND is_active = 1",
				(int) $new_settings['template_id']
			)
		);

		if ( ! $template_exists ) {
			return null;
		}

		return [
			'enabled'     => true,
			'template_id' => (int) $new_settings['template_id'],
			'delay'       => (int) ( $new_settings['delay'] ?? 0 ),
		];
	}

	/**
	 * Sendet die Eingangsbestätigung für neue Bewerbung (wenn konfiguriert)
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return bool|null True/False für Erfolg/Fehler, Null wenn nicht konfiguriert
	 */
	public function sendNewApplicationEmail( int $application_id ): ?bool {
		$settings = $this->getNewApplicationSettings();

		if ( ! $settings ) {
			return null; // Nicht konfiguriert - Caller muss entscheiden was passiert.
		}

		$template_id = $settings['template_id'];
		$delay       = $settings['delay'];

		// E-Mail senden (sofort oder verzögert).
		if ( $delay > 0 && function_exists( 'as_schedule_single_action' ) ) {
			// Verzögerter Versand via Action Scheduler.
			as_schedule_single_action(
				time() + ( $delay * 60 ),
				'rp_send_auto_email',
				[
					'template_id'    => $template_id,
					'application_id' => $application_id,
					'trigger'        => 'new_application',
				],
				'recruiting-playbook'
			);

			$this->logAutoEmail( $application_id, 'new', 'scheduled', $template_id, $delay );
			return true;
		} else {
			// Sofortiger Versand.
			$result = $this->email_service->sendWithTemplate(
				$template_id,
				$application_id,
				[ '_trigger' => 'auto_new_application' ],
				false
			);

			$this->logAutoEmail( $application_id, 'new', $result ? 'sent' : 'failed', $template_id );
			return $result;
		}
	}
}
