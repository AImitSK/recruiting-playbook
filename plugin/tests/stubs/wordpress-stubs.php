<?php
/**
 * WordPress Stubs für Unit Tests
 *
 * Minimale Stubs für WordPress-Funktionen die nicht von Brain Monkey abgedeckt werden.
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

// WP_Error Stub wenn nicht bereits definiert.
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * WordPress Error Stub
	 */
	class WP_Error {
		private array $errors = [];
		private array $error_data = [];

		public function __construct( string $code = '', string $message = '', $data = '' ) {
			if ( empty( $code ) ) {
				return;
			}
			$this->add( $code, $message, $data );
		}

		public function add( string $code, string $message, $data = '' ): void {
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		public function get_error_code(): string {
			$codes = $this->get_error_codes();
			return $codes[0] ?? '';
		}

		public function get_error_codes(): array {
			return array_keys( $this->errors );
		}

		public function get_error_message( string $code = '' ): string {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			$messages = $this->errors[ $code ] ?? [];
			return $messages[0] ?? '';
		}

		public function get_error_messages( string $code = '' ): array {
			if ( empty( $code ) ) {
				$all_messages = [];
				foreach ( $this->errors as $messages ) {
					$all_messages = array_merge( $all_messages, $messages );
				}
				return $all_messages;
			}
			return $this->errors[ $code ] ?? [];
		}

		public function get_error_data( string $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return $this->error_data[ $code ] ?? null;
		}

		public function has_errors(): bool {
			return ! empty( $this->errors );
		}
	}
}

// is_wp_error Stub.
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

// WP_REST_Request Stub.
if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * WordPress REST Request Stub
	 */
	class WP_REST_Request {
		private array $params = [];
		private array $headers = [];

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		public function get_header( string $key ): ?string {
			return $this->headers[ strtolower( $key ) ] ?? null;
		}

		public function set_header( string $key, string $value ): void {
			$this->headers[ strtolower( $key ) ] = $value;
		}

		public function get_params(): array {
			return $this->params;
		}
	}
}

// HOUR_IN_SECONDS Konstante.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// MINUTE_IN_SECONDS Konstante.
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// ARRAY_A Konstante (für $wpdb->get_row()).
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// ARRAY_N Konstante.
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

// OBJECT Konstante.
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

// Plugin-Konstanten für Tests.
if ( ! defined( 'RP_VERSION' ) ) {
	define( 'RP_VERSION', '1.0.0' );
}

if ( ! defined( 'RP_PLUGIN_DIR' ) ) {
	define( 'RP_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'RP_PLUGIN_URL' ) ) {
	define( 'RP_PLUGIN_URL', 'http://example.com/wp-content/plugins/recruiting-playbook/' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/var/www/html/' );
}
