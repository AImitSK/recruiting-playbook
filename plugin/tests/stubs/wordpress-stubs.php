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
		private string $code;
		private string $message;
		private array $data;

		public function __construct( string $code = '', string $message = '', $data = [] ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = (array) $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): array {
			return $this->data;
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
