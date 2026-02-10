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
use RecruitingPlaybook\Services\DesignService;

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
		// All settings (for Settings page).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_all_settings' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_all_settings' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
			]
		);

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

		// Auto-Email settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/auto-email',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_auto_email' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_auto_email' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
			]
		);

		// Design & Branding settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/design',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_design_settings' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_design_settings' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'reset_design_settings' ],
					'permission_callback' => [ $this, 'update_company_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get all settings for Settings page
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_all_settings( WP_REST_Request $request ) {
		$settings = get_option( self::OPTION_NAME, $this->get_defaults() );

		// Privacy URL zu Page ID konvertieren für das Dropdown.
		$privacy_page_id = 0;
		if ( ! empty( $settings['privacy_url'] ) ) {
			$privacy_page_id = url_to_postid( $settings['privacy_url'] );
		}

		return rest_ensure_response( [
			// Allgemeine Einstellungen.
			'notification_email'  => $settings['notification_email'] ?? '',
			'privacy_page_id'     => $privacy_page_id,
			'jobs_per_page'       => (int) ( $settings['jobs_per_page'] ?? 10 ),
			'jobs_slug'           => $settings['jobs_slug'] ?? 'jobs',
			'enable_schema'       => (bool) ( $settings['enable_schema'] ?? true ),

			// Firmendaten.
			'company_name'        => $settings['company_name'] ?? '',
			'company_street'      => $settings['company_street'] ?? '',
			'company_zip'         => $settings['company_zip'] ?? '',
			'company_city'        => $settings['company_city'] ?? '',
			'company_phone'       => $settings['company_phone'] ?? '',
			'company_website'     => $settings['company_website'] ?? '',
			'company_email'       => $settings['company_email'] ?? '',

			// Absender.
			'sender_name'         => $settings['sender_name'] ?? '',
			'sender_email'        => $settings['sender_email'] ?? '',

			// Pro-Features.
			'hide_email_branding' => (bool) ( $settings['hide_email_branding'] ?? false ),
		] );
	}

	/**
	 * Update all settings from Settings page
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_all_settings( WP_REST_Request $request ) {
		$settings = get_option( self::OPTION_NAME, $this->get_defaults() );
		$old_slug = $settings['jobs_slug'] ?? 'jobs';

		// Allgemeine Einstellungen.
		if ( $request->has_param( 'notification_email' ) ) {
			$email = $request->get_param( 'notification_email' );
			if ( ! empty( $email ) && ! is_email( $email ) ) {
				return new WP_Error(
					'invalid_notification_email',
					__( 'Ungültige Benachrichtigungs-E-Mail-Adresse.', 'recruiting-playbook' ),
					[ 'status' => 400 ]
				);
			}
			$settings['notification_email'] = sanitize_email( $email );
		}

		if ( $request->has_param( 'privacy_page_id' ) ) {
			$page_id                 = absint( $request->get_param( 'privacy_page_id' ) );
			$settings['privacy_url'] = $page_id ? get_permalink( $page_id ) : '';
		}

		if ( $request->has_param( 'jobs_per_page' ) ) {
			$settings['jobs_per_page'] = max( 1, min( 50, absint( $request->get_param( 'jobs_per_page' ) ) ) );
		}

		if ( $request->has_param( 'jobs_slug' ) ) {
			$settings['jobs_slug'] = sanitize_title( $request->get_param( 'jobs_slug' ) );
		}

		if ( $request->has_param( 'enable_schema' ) ) {
			$settings['enable_schema'] = (bool) $request->get_param( 'enable_schema' );
		}

		// Firmendaten.
		if ( $request->has_param( 'company_name' ) ) {
			$settings['company_name'] = sanitize_text_field( $request->get_param( 'company_name' ) );
		}

		if ( $request->has_param( 'company_street' ) ) {
			$settings['company_street'] = sanitize_text_field( $request->get_param( 'company_street' ) );
		}

		if ( $request->has_param( 'company_zip' ) ) {
			$settings['company_zip'] = sanitize_text_field( $request->get_param( 'company_zip' ) );
		}

		if ( $request->has_param( 'company_city' ) ) {
			$settings['company_city'] = sanitize_text_field( $request->get_param( 'company_city' ) );
		}

		if ( $request->has_param( 'company_phone' ) ) {
			$settings['company_phone'] = sanitize_text_field( $request->get_param( 'company_phone' ) );
		}

		if ( $request->has_param( 'company_website' ) ) {
			$settings['company_website'] = esc_url_raw( $request->get_param( 'company_website' ) );
		}

		if ( $request->has_param( 'company_email' ) ) {
			$email = $request->get_param( 'company_email' );
			if ( ! empty( $email ) && ! is_email( $email ) ) {
				return new WP_Error(
					'invalid_company_email',
					__( 'Ungültige Kontakt-E-Mail-Adresse.', 'recruiting-playbook' ),
					[ 'status' => 400 ]
				);
			}
			$settings['company_email'] = sanitize_email( $email );
		}

		// Absender.
		if ( $request->has_param( 'sender_name' ) ) {
			$settings['sender_name'] = sanitize_text_field( $request->get_param( 'sender_name' ) );
		}

		if ( $request->has_param( 'sender_email' ) ) {
			$email = $request->get_param( 'sender_email' );
			if ( ! empty( $email ) && ! is_email( $email ) ) {
				return new WP_Error(
					'invalid_sender_email',
					__( 'Ungültige Absender-E-Mail-Adresse.', 'recruiting-playbook' ),
					[ 'status' => 400 ]
				);
			}
			$settings['sender_email'] = sanitize_email( $email );
		}

		// Pro-Features.
		if ( $request->has_param( 'hide_email_branding' ) ) {
			$settings['hide_email_branding'] = (bool) $request->get_param( 'hide_email_branding' );
		}

		update_option( self::OPTION_NAME, $settings );

		// Slug-Änderung erfordert Rewrite-Flush.
		if ( $old_slug !== $settings['jobs_slug'] ) {
			set_transient( 'rp_flush_rewrite_rules', true, 60 );
		}

		return rest_ensure_response( [
			'success' => true,
			'message' => __( 'Einstellungen wurden gespeichert.', 'recruiting-playbook' ),
		] );
	}

	/**
	 * Standard-Werte
	 *
	 * @return array Default settings.
	 */
	private function get_defaults(): array {
		return [
			'notification_email'   => get_option( 'admin_email' ),
			'privacy_url'          => get_privacy_policy_url(),
			'company_name'         => get_bloginfo( 'name' ),
			'company_street'       => '',
			'company_zip'          => '',
			'company_city'         => '',
			'company_phone'        => '',
			'company_website'      => home_url(),
			'company_email'        => get_option( 'admin_email' ),
			'sender_name'          => __( 'Personalabteilung', 'recruiting-playbook' ),
			'sender_email'         => get_option( 'admin_email' ),
			'jobs_per_page'        => 10,
			'jobs_slug'            => 'jobs',
			'enable_schema'        => true,
			'hide_email_branding'  => false,
		];
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
	 * Get auto-email settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_auto_email( WP_REST_Request $request ) {
		$settings = get_option( 'rp_auto_email_settings', [] );
		return rest_ensure_response( [ 'settings' => $settings ] );
	}

	/**
	 * Update auto-email settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_auto_email( WP_REST_Request $request ) {
		$settings = $request->get_param( 'settings' );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		// Sanitize each status setting.
		$sanitized = [];
		$allowed_statuses = [ 'new', 'rejected', 'withdrawn' ];

		foreach ( $allowed_statuses as $status ) {
			if ( isset( $settings[ $status ] ) && is_array( $settings[ $status ] ) ) {
				$sanitized[ $status ] = [
					'enabled'     => ! empty( $settings[ $status ]['enabled'] ),
					'template_id' => absint( $settings[ $status ]['template_id'] ?? 0 ),
					'delay'       => absint( $settings[ $status ]['delay'] ?? 0 ),
				];
			}
		}

		update_option( 'rp_auto_email_settings', $sanitized );

		return rest_ensure_response( [
			'settings' => $sanitized,
			'message'  => __( 'Einstellungen wurden gespeichert.', 'recruiting-playbook' ),
		] );
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

	// =========================================================================
	// Design & Branding Settings
	// =========================================================================

	/**
	 * Get Design & Branding settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_design_settings( WP_REST_Request $request ) {
		$design_service = new DesignService();
		$settings       = $design_service->get_design_settings();
		$schema         = $design_service->get_schema();
		$defaults       = $design_service->get_defaults();

		// Zusätzliche Metadaten für das Frontend.
		$meta = [
			'primary_color_computed' => $design_service->get_primary_color(),
			'logo_url_computed'      => $design_service->get_logo_url(),
			'theme_has_primary'      => (bool) get_theme_mod( 'primary_color', '' ),
			'theme_has_logo'         => (bool) get_theme_mod( 'custom_logo', 0 ),
		];

		return rest_ensure_response( [
			'settings' => $settings,
			'schema'   => $schema,
			'defaults' => $defaults,
			'meta'     => $meta,
		] );
	}

	/**
	 * Update Design & Branding settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_design_settings( WP_REST_Request $request ) {
		$design_service = new DesignService();
		$new_settings   = $request->get_json_params();

		if ( empty( $new_settings ) || ! is_array( $new_settings ) ) {
			return new WP_Error(
				'invalid_settings',
				__( 'Ungültige Einstellungen.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$success = $design_service->save_design_settings( $new_settings );

		if ( ! $success ) {
			return new WP_Error(
				'save_failed',
				__( 'Einstellungen konnten nicht gespeichert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response( [
			'success'  => true,
			'message'  => __( 'Design-Einstellungen wurden gespeichert.', 'recruiting-playbook' ),
			'settings' => $design_service->get_design_settings(),
		] );
	}

	/**
	 * Reset Design & Branding settings to defaults
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function reset_design_settings( WP_REST_Request $request ) {
		$design_service = new DesignService();
		$design_service->reset_to_defaults();

		return rest_ensure_response( [
			'success'  => true,
			'message'  => __( 'Design-Einstellungen wurden zurückgesetzt.', 'recruiting-playbook' ),
			'settings' => $design_service->get_design_settings(),
		] );
	}
}
