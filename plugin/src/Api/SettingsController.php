<?php
/**
 * Settings REST Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST Controller für Plugin-Einstellungen
 */
class SettingsController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Option name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'rp_settings';

	/**
	 * Routes registrieren
	 */
	public function register_routes(): void {
		// Company settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/company',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_company' ],
					'permission_callback' => [ $this, 'get_company_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_company' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
					'args'                => $this->get_company_args(),
				],
				'schema' => [ $this, 'get_company_schema' ],
			]
		);
	}

	/**
	 * Get company settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_company( WP_REST_Request $request ) {
		$settings = get_option( self::OPTION_NAME, [] );

		$company_data = [
			'name'         => $settings['company_name'] ?? '',
			'street'       => $settings['company_street'] ?? '',
			'zip'          => $settings['company_zip'] ?? '',
			'city'         => $settings['company_city'] ?? '',
			'phone'        => $settings['company_phone'] ?? '',
			'website'      => $settings['company_website'] ?? '',
			'email'        => $settings['company_email'] ?? '',
			'sender_name'  => $settings['sender_name'] ?? '',
			'sender_email' => $settings['sender_email'] ?? '',
		];

		return rest_ensure_response( [ 'company' => $company_data ] );
	}

	/**
	 * Update company settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_company( WP_REST_Request $request ) {
		$settings = get_option( self::OPTION_NAME, [] );

		// Validate required fields.
		$name  = $request->get_param( 'name' );
		$email = $request->get_param( 'email' );

		if ( empty( $name ) ) {
			return new WP_Error(
				'missing_name',
				__( 'Firmenname ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Eine gültige Kontakt-E-Mail ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Update settings.
		$settings['company_name']    = sanitize_text_field( $name );
		$settings['company_street']  = sanitize_text_field( $request->get_param( 'street' ) ?? '' );
		$settings['company_zip']     = sanitize_text_field( $request->get_param( 'zip' ) ?? '' );
		$settings['company_city']    = sanitize_text_field( $request->get_param( 'city' ) ?? '' );
		$settings['company_phone']   = sanitize_text_field( $request->get_param( 'phone' ) ?? '' );
		$settings['company_website'] = esc_url_raw( $request->get_param( 'website' ) ?? '' );
		$settings['company_email']   = sanitize_email( $email );

		// Sender settings (optional).
		$sender_name = $request->get_param( 'sender_name' );
		if ( null !== $sender_name ) {
			$settings['sender_name'] = sanitize_text_field( $sender_name );
		}

		$sender_email = $request->get_param( 'sender_email' );
		if ( null !== $sender_email ) {
			if ( ! empty( $sender_email ) && ! is_email( $sender_email ) ) {
				return new WP_Error(
					'invalid_sender_email',
					__( 'Ungültige Absender-E-Mail-Adresse.', 'recruiting-playbook' ),
					[ 'status' => 400 ]
				);
			}
			$settings['sender_email'] = sanitize_email( $sender_email );
		}

		update_option( self::OPTION_NAME, $settings );

		// Return updated data.
		return $this->get_company( $request );
	}

	/**
	 * Check if request has permission to read company settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permission granted.
	 */
	public function get_company_permissions_check( WP_REST_Request $request ) {
		// Any logged-in user with edit_posts can read company settings.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, die Firmendaten zu lesen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Check if request has permission to update company settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permission granted.
	 */
	public function update_company_permissions_check( WP_REST_Request $request ) {
		// Only admins can update company settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, die Firmendaten zu ändern.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get company endpoint arguments
	 *
	 * @return array Arguments.
	 */
	private function get_company_args(): array {
		return [
			'name'         => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Firmenname', 'recruiting-playbook' ),
			],
			'street'       => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Straße und Hausnummer', 'recruiting-playbook' ),
			],
			'zip'          => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Postleitzahl', 'recruiting-playbook' ),
			],
			'city'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Stadt', 'recruiting-playbook' ),
			],
			'phone'        => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Telefonnummer', 'recruiting-playbook' ),
			],
			'website'      => [
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Website URL', 'recruiting-playbook' ),
			],
			'email'        => [
				'type'              => 'string',
				'format'            => 'email',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
				'description'       => __( 'Kontakt-E-Mail', 'recruiting-playbook' ),
			],
			'sender_name'  => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Standard-Absender Name', 'recruiting-playbook' ),
			],
			'sender_email' => [
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
				'description'       => __( 'Standard-Absender E-Mail', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * Get company schema
	 *
	 * @return array Schema.
	 */
	public function get_company_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'company-settings',
			'type'       => 'object',
			'properties' => [
				'company' => [
					'type'       => 'object',
					'properties' => [
						'name'         => [
							'type'        => 'string',
							'description' => __( 'Firmenname', 'recruiting-playbook' ),
						],
						'street'       => [
							'type'        => 'string',
							'description' => __( 'Straße und Hausnummer', 'recruiting-playbook' ),
						],
						'zip'          => [
							'type'        => 'string',
							'description' => __( 'Postleitzahl', 'recruiting-playbook' ),
						],
						'city'         => [
							'type'        => 'string',
							'description' => __( 'Stadt', 'recruiting-playbook' ),
						],
						'phone'        => [
							'type'        => 'string',
							'description' => __( 'Telefonnummer', 'recruiting-playbook' ),
						],
						'website'      => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => __( 'Website URL', 'recruiting-playbook' ),
						],
						'email'        => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => __( 'Kontakt-E-Mail', 'recruiting-playbook' ),
						],
						'sender_name'  => [
							'type'        => 'string',
							'description' => __( 'Standard-Absender Name', 'recruiting-playbook' ),
						],
						'sender_email' => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => __( 'Standard-Absender E-Mail', 'recruiting-playbook' ),
						],
					],
				],
			],
		];
	}
}
