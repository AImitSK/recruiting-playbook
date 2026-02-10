<?php
/**
 * Email Queue Service - Queue-basierter E-Mail-Versand mit Action Scheduler
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\EmailLogRepository;

/**
 * Service für Queue-basierten E-Mail-Versand
 */
class EmailQueueService {

	/**
	 * Action Scheduler Hook für E-Mail-Versand
	 */
	private const HOOK_SEND_EMAIL = 'rp_send_queued_email';

	/**
	 * Action Scheduler Hook für Queue-Verarbeitung
	 */
	private const HOOK_PROCESS_QUEUE = 'rp_process_email_queue';

	/**
	 * Maximale Versuche bei Fehlern
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Batch-Größe für Queue-Verarbeitung
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Email Log Repository
	 *
	 * @var EmailLogRepository
	 */
	private EmailLogRepository $logRepository;

	/**
	 * Constructor
	 *
	 * @param EmailLogRepository|null $logRepository Optional log repository.
	 */
	public function __construct( ?EmailLogRepository $logRepository = null ) {
		$this->logRepository = $logRepository ?? new EmailLogRepository();
	}

	/**
	 * Hooks registrieren
	 */
	public function registerHooks(): void {
		add_action( self::HOOK_SEND_EMAIL, [ $this, 'processSingleEmail' ], 10, 1 );
		add_action( self::HOOK_PROCESS_QUEUE, [ $this, 'processQueue' ] );
	}

	/**
	 * E-Mail zur Queue hinzufügen
	 *
	 * @param array $email_data E-Mail-Daten.
	 * @return int|false Log-ID oder false bei Fehler.
	 */
	public function enqueue( array $email_data ): int|false {
		// Log-Eintrag erstellen.
		$log_id = $this->logRepository->create( [
			'application_id'  => $email_data['application_id'] ?? null,
			'candidate_id'    => $email_data['candidate_id'] ?? null,
			'template_id'     => $email_data['template_id'] ?? null,
			'recipient_email' => $email_data['recipient_email'],
			'recipient_name'  => $email_data['recipient_name'] ?? '',
			'sender_email'    => $email_data['sender_email'],
			'sender_name'     => $email_data['sender_name'] ?? '',
			'subject'         => $email_data['subject'],
			'body_html'       => $email_data['body_html'],
			'body_text'       => $email_data['body_text'] ?? '',
			'status'          => 'pending',
			'scheduled_at'    => $email_data['scheduled_at'] ?? null,
			'metadata'        => $email_data['metadata'] ?? [],
		] );

		if ( false === $log_id ) {
			return false;
		}

		// Action Scheduler Job erstellen.
		$this->scheduleEmail( $log_id, $email_data['scheduled_at'] ?? null );

		return $log_id;
	}

	/**
	 * E-Mail für späteren Versand planen
	 *
	 * @param array  $email_data   E-Mail-Daten.
	 * @param string $scheduled_at Geplanter Zeitpunkt (Y-m-d H:i:s).
	 * @return int|false Log-ID oder false bei Fehler.
	 */
	public function schedule( array $email_data, string $scheduled_at ): int|false {
		$email_data['scheduled_at'] = $scheduled_at;
		return $this->enqueue( $email_data );
	}

	/**
	 * Geplante E-Mail stornieren
	 *
	 * @param int $log_id Log-ID.
	 * @return bool
	 */
	public function cancel( int $log_id ): bool {
		$log = $this->logRepository->find( $log_id );

		if ( ! $log || 'pending' !== $log['status'] ) {
			return false;
		}

		// Action Scheduler Job entfernen.
		$this->unscheduleEmail( $log_id );

		// Status auf cancelled setzen.
		return $this->logRepository->updateStatus( $log_id, 'cancelled' );
	}

	/**
	 * E-Mail erneut senden
	 *
	 * @param int $log_id Log-ID.
	 * @return int|false Neue Log-ID oder false.
	 */
	public function resend( int $log_id ): int|false {
		$log = $this->logRepository->find( $log_id );

		if ( ! $log ) {
			return false;
		}

		// Neue E-Mail mit gleichen Daten erstellen.
		return $this->enqueue( [
			'application_id'  => $log['application_id'],
			'candidate_id'    => $log['candidate_id'],
			'template_id'     => $log['template_id'],
			'recipient_email' => $log['recipient_email'],
			'recipient_name'  => $log['recipient_name'],
			'sender_email'    => $log['sender_email'],
			'sender_name'     => $log['sender_name'],
			'subject'         => $log['subject'],
			'body_html'       => $log['body_html'],
			'body_text'       => $log['body_text'],
			'metadata'        => array_merge(
				$log['metadata'] ?? [],
				[ 'resent_from' => $log_id ]
			),
		] );
	}

	/**
	 * Einzelne E-Mail verarbeiten (Action Scheduler Callback)
	 *
	 * @param int $log_id Log-ID.
	 */
	public function processSingleEmail( int $log_id ): void {
		$log = $this->logRepository->find( $log_id );

		if ( ! $log ) {
			return;
		}

		// Nur pending E-Mails verarbeiten.
		if ( 'pending' !== $log['status'] ) {
			return;
		}

		// Status auf queued setzen.
		$this->logRepository->updateStatus( $log_id, 'queued' );

		// E-Mail senden.
		$result = $this->sendEmail( $log );

		if ( $result ) {
			$this->logRepository->updateStatus( $log_id, 'sent' );

			// Activity Log.
			$this->logActivity( $log, 'sent' );
		} else {
			$retry_count = (int) ( $log['metadata']['retry_count'] ?? 0 );

			if ( $retry_count < self::MAX_RETRIES ) {
				// Erneut planen mit Verzögerung.
				$delay = pow( 2, $retry_count ) * 60; // Exponential backoff: 1, 2, 4 Minuten.
				$this->logRepository->update( $log_id, [
					'status'   => 'pending',
					'metadata' => array_merge(
						$log['metadata'] ?? [],
						[ 'retry_count' => $retry_count + 1 ]
					),
				] );
				$this->scheduleEmail( $log_id, gmdate( 'Y-m-d H:i:s', time() + $delay ) );
			} else {
				// Maximale Versuche erreicht.
				$this->logRepository->updateStatus(
					$log_id,
					'failed',
					__( 'Maximale Versuche erreicht', 'recruiting-playbook' )
				);
			}
		}
	}

	/**
	 * Queue verarbeiten (Batch-Verarbeitung)
	 */
	public function processQueue(): void {
		$pending = $this->logRepository->getPendingForQueue( self::BATCH_SIZE );

		foreach ( $pending as $log ) {
			// Für jede E-Mail einen separaten Job erstellen.
			$this->scheduleEmail( (int) $log['id'] );
		}
	}

	/**
	 * Geplante E-Mails abrufen
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getScheduled( array $args = [] ): array {
		return $this->logRepository->getScheduled( $args );
	}

	/**
	 * Queue-Statistiken
	 *
	 * @return array
	 */
	public function getQueueStats(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_email_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			"SELECT
				COUNT(*) as total,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
				SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as processing,
				SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
				SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
			ARRAY_A
		);

		return [
			'total'      => (int) ( $stats['total'] ?? 0 ),
			'pending'    => (int) ( $stats['pending'] ?? 0 ),
			'processing' => (int) ( $stats['processing'] ?? 0 ),
			'sent'       => (int) ( $stats['sent'] ?? 0 ),
			'failed'     => (int) ( $stats['failed'] ?? 0 ),
		];
	}

	/**
	 * Prüfen ob Action Scheduler verfügbar ist
	 *
	 * @return bool
	 */
	public function isActionSchedulerAvailable(): bool {
		return function_exists( 'as_enqueue_async_action' );
	}

	/**
	 * E-Mail im Action Scheduler planen
	 *
	 * @param int         $log_id       Log-ID.
	 * @param string|null $scheduled_at Geplanter Zeitpunkt.
	 */
	private function scheduleEmail( int $log_id, ?string $scheduled_at = null ): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			// Fallback: Direkt senden.
			$this->processSingleEmail( $log_id );
			return;
		}

		$args = [ $log_id ];

		if ( $scheduled_at ) {
			$timestamp = strtotime( $scheduled_at );
			as_schedule_single_action( $timestamp, self::HOOK_SEND_EMAIL, $args, 'recruiting-playbook' );
		} else {
			as_enqueue_async_action( self::HOOK_SEND_EMAIL, $args, 'recruiting-playbook' );
		}
	}

	/**
	 * Geplante E-Mail aus Scheduler entfernen
	 *
	 * @param int $log_id Log-ID.
	 */
	private function unscheduleEmail( int $log_id ): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			return;
		}

		as_unschedule_all_actions( self::HOOK_SEND_EMAIL, [ $log_id ], 'recruiting-playbook' );
	}

	/**
	 * E-Mail tatsächlich versenden
	 *
	 * @param array $log Log-Daten.
	 * @return bool
	 */
	private function sendEmail( array $log ): bool {
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $log['sender_name'] ?: $log['sender_email'], $log['sender_email'] ),
		];

		// Reply-To wenn vorhanden.
		if ( ! empty( $log['metadata']['reply_to'] ) ) {
			$headers[] = 'Reply-To: ' . $log['metadata']['reply_to'];
		}

		// Filter für Erweiterungen.
		$headers = apply_filters( 'rp_email_headers', $headers, $log );
		$body    = apply_filters( 'rp_email_content', $log['body_html'], $log['recipient_email'], $log['subject'] );

		// E-Mail versenden.
		$sent = wp_mail( $log['recipient_email'], $log['subject'], $body, $headers );

		// Hook für Erweiterungen.
		do_action( 'rp_email_sent', $log['recipient_email'], $log['subject'], $sent, $log );

		return $sent;
	}

	/**
	 * Activity Log Eintrag erstellen
	 *
	 * @param array  $log    Log-Daten.
	 * @param string $action Aktion.
	 */
	private function logActivity( array $log, string $action ): void {
		if ( empty( $log['application_id'] ) ) {
			return;
		}

		$activity_service = new ActivityService();
		$activity_service->log( [
			'object_type' => 'application',
			'object_id'   => $log['application_id'],
			'action'      => 'email_sent',
			'message'     => sprintf(
				/* translators: %s: Email subject */
				__( 'E-Mail gesendet: %s', 'recruiting-playbook' ),
				$log['subject']
			),
			'meta'        => [
				'email_log_id' => $log['id'],
				'recipient'    => $log['recipient_email'],
				'template_id'  => $log['template_id'],
			],
		] );
	}

	/**
	 * Queue-Verarbeitung starten (Cron-Job registrieren)
	 */
	public function scheduleQueueProcessing(): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			return;
		}

		// Recurring Action für Queue-Verarbeitung (alle 5 Minuten).
		if ( false === as_has_scheduled_action( self::HOOK_PROCESS_QUEUE, [], 'recruiting-playbook' ) ) {
			as_schedule_recurring_action(
				time(),
				5 * MINUTE_IN_SECONDS,
				self::HOOK_PROCESS_QUEUE,
				[],
				'recruiting-playbook'
			);
		}
	}

	/**
	 * Queue-Verarbeitung stoppen
	 */
	public function unscheduleQueueProcessing(): void {
		if ( ! $this->isActionSchedulerAvailable() ) {
			return;
		}

		as_unschedule_all_actions( self::HOOK_PROCESS_QUEUE, [], 'recruiting-playbook' );
	}
}
