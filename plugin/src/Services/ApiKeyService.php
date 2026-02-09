<?php
/**
 * API-Key Verwaltung
 *
 * Generierung, Validierung und Rate Limiting von API-Keys.
 * Pro-Feature (api_access).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use WP_Error;

/**
 * API-Key Service
 */
class ApiKeyService {

	/**
	 * Erlaubte Permissions
	 *
	 * @var array<string>
	 */
	const VALID_PERMISSIONS = [
		'jobs_read',
		'jobs_write',
		'applications_read',
		'applications_write',
		'candidates_read',
		'candidates_write',
		'documents_read',
		'reports_read',
		'settings_read',
		'settings_write',
	];

	/**
	 * Transient-Prefix für Rate Limiting
	 *
	 * @var string
	 */
	const RATE_LIMIT_PREFIX = 'rp_api_rl_';

	/**
	 * API-Key erstellen
	 *
	 * @param string      $name        Key-Name.
	 * @param array       $permissions Berechtigungen.
	 * @param int         $rate_limit  Anfragen pro Stunde.
	 * @param string|null $expires_at  Ablaufdatum (Y-m-d H:i:s).
	 * @return array|WP_Error Key-Daten mit plain_key (nur einmal!) oder Fehler.
	 */
	public function createKey( string $name, array $permissions, int $rate_limit = 1000, ?string $expires_at = null ) {
		global $wpdb;

		// Name validieren.
		$name = sanitize_text_field( $name );
		if ( empty( $name ) ) {
			return new WP_Error(
				'invalid_name',
				__( 'Name ist ein Pflichtfeld.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Permissions validieren.
		$invalid = array_diff( $permissions, self::VALID_PERMISSIONS );
		if ( ! empty( $invalid ) ) {
			return new WP_Error(
				'invalid_permissions',
				sprintf(
					/* translators: %s: comma-separated list of invalid permissions */
					__( 'Ungültige Berechtigungen: %s', 'recruiting-playbook' ),
					implode( ', ', $invalid )
				),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $permissions ) ) {
			return new WP_Error(
				'no_permissions',
				__( 'Mindestens eine Berechtigung muss ausgewählt werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Key generieren (kryptographisch sicher).
		$plain_key  = 'rp_live_' . bin2hex( random_bytes( 16 ) );
		$key_hash   = hash( 'sha256', $plain_key );
		$key_prefix = 'rp_live_';
		$key_hint   = substr( $plain_key, -4 );

		$table = Schema::getTables()['api_keys'];
		$now   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'name'        => $name,
				'key_prefix'  => $key_prefix,
				'key_hash'    => $key_hash,
				'key_hint'    => $key_hint,
				'permissions' => wp_json_encode( array_values( $permissions ) ),
				'rate_limit'  => $rate_limit,
				'created_by'  => get_current_user_id(),
				'is_active'   => 1,
				'created_at'  => $now,
				'expires_at'  => $expires_at,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ]
		);

		$key_id = (int) $wpdb->insert_id;

		if ( ! $key_id ) {
			return new WP_Error(
				'create_failed',
				__( 'API-Key konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Key-Daten laden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$key_data = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE id = %d",
				$key_id
			)
		);

		return [
			'id'        => $key_id,
			'plain_key' => $plain_key,
			'key_data'  => $key_data,
		];
	}

	/**
	 * API-Key validieren
	 *
	 * @param string $plain_key Klartext-Key.
	 * @return object|null Key-DB-Objekt oder null bei Fehler.
	 */
	public function validateKey( string $plain_key ): ?object {
		global $wpdb;

		// Prefix prüfen.
		if ( ! str_starts_with( $plain_key, 'rp_live_' ) && ! str_starts_with( $plain_key, 'rp_test_' ) ) {
			return null;
		}

		$key_hash = hash( 'sha256', $plain_key );
		$table    = Schema::getTables()['api_keys'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$key_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE key_hash = %s AND is_active = 1 AND revoked_at IS NULL",
				$key_hash
			)
		);

		if ( ! $key_data ) {
			return null;
		}

		// Ablauf prüfen.
		if ( ! empty( $key_data->expires_at ) && strtotime( $key_data->expires_at ) < time() ) {
			return null;
		}

		// last_used_at und request_count aktualisieren.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET last_used_at = %s, request_count = request_count + 1 WHERE id = %d",
				current_time( 'mysql' ),
				$key_data->id
			)
		);
		// phpcs:enable

		return $key_data;
	}

	/**
	 * Rate Limit prüfen
	 *
	 * Transient-basiert mit HOUR_IN_SECONDS TTL.
	 *
	 * @param object $key_data Key-DB-Objekt.
	 * @return array{allowed: bool, limit: int, remaining: int, reset: int}
	 */
	public function checkRateLimit( object $key_data ): array {
		$limit         = (int) $key_data->rate_limit;
		$transient_key = self::RATE_LIMIT_PREFIX . $key_data->id;
		$current       = get_transient( $transient_key );

		if ( false === $current ) {
			// Erster Request in dieser Stunde.
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
			return [
				'allowed'   => true,
				'limit'     => $limit,
				'remaining' => $limit - 1,
				'reset'     => time() + HOUR_IN_SECONDS,
			];
		}

		$current = (int) $current;

		if ( $current >= $limit ) {
			return [
				'allowed'   => false,
				'limit'     => $limit,
				'remaining' => 0,
				'reset'     => time() + HOUR_IN_SECONDS,
			];
		}

		// Inkrementieren.
		set_transient( $transient_key, $current + 1, HOUR_IN_SECONDS );

		return [
			'allowed'   => true,
			'limit'     => $limit,
			'remaining' => $limit - ( $current + 1 ),
			'reset'     => time() + HOUR_IN_SECONDS,
		];
	}

	/**
	 * Alle API-Keys laden
	 *
	 * @return array
	 */
	public function getAll(): array {
		global $wpdb;

		$table = Schema::getTables()['api_keys'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$keys = $wpdb->get_results(
			"SELECT * FROM {$table} ORDER BY created_at DESC"
		);
		// phpcs:enable

		return $keys ?: [];
	}

	/**
	 * Einzelnen API-Key laden
	 *
	 * @param int $id Key-ID.
	 * @return object|null
	 */
	public function getById( int $id ): ?object {
		global $wpdb;

		$table = Schema::getTables()['api_keys'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$key = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable

		return $key ?: null;
	}

	/**
	 * API-Key aktualisieren
	 *
	 * @param int   $id   Key-ID.
	 * @param array $data Felder zum Aktualisieren (name, permissions, rate_limit, is_active).
	 * @return object|WP_Error Aktualisierter Key oder Fehler.
	 */
	public function updateKey( int $id, array $data ) {
		global $wpdb;

		$table = Schema::getTables()['api_keys'];

		$existing = $this->getById( $id );
		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'API-Key nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$update_data    = [];
		$update_formats = [];

		// Name.
		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
			$update_formats[]    = '%s';
		}

		// Permissions.
		if ( isset( $data['permissions'] ) ) {
			$invalid = array_diff( $data['permissions'], self::VALID_PERMISSIONS );
			if ( ! empty( $invalid ) ) {
				return new WP_Error(
					'invalid_permissions',
					sprintf(
						/* translators: %s: comma-separated list of invalid permissions */
						__( 'Ungültige Berechtigungen: %s', 'recruiting-playbook' ),
						implode( ', ', $invalid )
					),
					[ 'status' => 400 ]
				);
			}
			$update_data['permissions'] = wp_json_encode( array_values( $data['permissions'] ) );
			$update_formats[]           = '%s';
		}

		// Rate Limit.
		if ( isset( $data['rate_limit'] ) ) {
			$update_data['rate_limit'] = (int) $data['rate_limit'];
			$update_formats[]          = '%d';
		}

		// Active/Revoke.
		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = $data['is_active'] ? 1 : 0;
			$update_formats[]         = '%d';

			// Revoke-Timestamp setzen wenn deaktiviert.
			if ( ! $data['is_active'] && empty( $existing->revoked_at ) ) {
				$update_data['revoked_at'] = current_time( 'mysql' );
				$update_formats[]          = '%s';
			}

			// Revoke aufheben wenn reaktiviert.
			if ( $data['is_active'] && ! empty( $existing->revoked_at ) ) {
				$update_data['revoked_at'] = null;
				$update_formats[]          = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return new WP_Error(
				'no_changes',
				__( 'Keine Änderungen übergeben.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			$update_data,
			[ 'id' => $id ],
			$update_formats,
			[ '%d' ]
		);

		return $this->getById( $id );
	}

	/**
	 * API-Key endgültig löschen
	 *
	 * @param int $id Key-ID.
	 * @return bool|WP_Error True bei Erfolg oder Fehler.
	 */
	public function deleteKey( int $id ) {
		global $wpdb;

		$table = Schema::getTables()['api_keys'];

		$existing = $this->getById( $id );
		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'API-Key nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			[ 'id' => $id ],
			[ '%d' ]
		);

		// Rate-Limit Transient aufräumen.
		delete_transient( self::RATE_LIMIT_PREFIX . $id );

		return true;
	}

	/**
	 * Permission prüfen
	 *
	 * @param object $key_data   Key-DB-Objekt.
	 * @param string $permission Permission-String.
	 * @return bool
	 */
	public function hasPermission( object $key_data, string $permission ): bool {
		$permissions = json_decode( $key_data->permissions, true );

		if ( ! is_array( $permissions ) ) {
			return false;
		}

		return in_array( $permission, $permissions, true );
	}
}
