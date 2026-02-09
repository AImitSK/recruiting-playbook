<?php
/**
 * REST API Controller für Stellenanzeigen (Jobs)
 *
 * CRUD-Endpoints unter /recruiting/v1/jobs mit strukturiertem
 * Response-Format (Salary-, Location-, Contact-Objekte).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\CapabilityService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WP_Query;

/**
 * REST API Controller für Stellenanzeigen
 */
class JobController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Resource base
	 *
	 * @var string
	 */
	protected $rest_base = 'jobs';

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// Liste aller Jobs.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_create_item_args(),
				],
			]
		);

		// Einzelner Job.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Job-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_update_item_args(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id'    => [
							'description' => __( 'Job-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'force' => [
							'description' => __( 'Endgültig löschen statt archivieren', 'recruiting-playbook' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigung für Lesen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen anzuzeigen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Erstellen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen zu erstellen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Aktualisieren prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen zu bearbeiten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Löschen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'delete_posts' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen zu löschen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Jobs auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$per_page = $request->get_param( 'per_page' ) ?: 10;
		$page     = $request->get_param( 'page' ) ?: 1;
		$status   = $request->get_param( 'status' ) ?: 'publish';
		$orderby  = $request->get_param( 'orderby' ) ?: 'date';
		$order    = $request->get_param( 'order' ) ?: 'desc';

		// Archiviert = draft.
		if ( 'archived' === $status ) {
			$status = 'draft';
		}

		$query_args = [
			'post_type'      => 'job_listing',
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		// Suche.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Standort-Filter (Taxonomie).
		$location = $request->get_param( 'location' );
		if ( ! empty( $location ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'job_location',
				'field'    => 'slug',
				'terms'    => $location,
			];
		}

		// Beschäftigungsart-Filter (Taxonomie).
		$employment_type = $request->get_param( 'employment_type' );
		if ( ! empty( $employment_type ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'employment_type',
				'field'    => 'slug',
				'terms'    => $employment_type,
			];
		}

		// Rollen-basierter Filter: Nicht-Admins sehen nur zugewiesene Stellen.
		if ( ! current_user_can( 'manage_options' ) ) {
			$capability_service = new CapabilityService();
			$assigned_job_ids   = $capability_service->getAssignedJobIds( get_current_user_id() );

			if ( ! empty( $assigned_job_ids ) ) {
				$query_args['post__in'] = $assigned_job_ids;
			} else {
				// Keine zugewiesenen Jobs → leeres Ergebnis.
				return new WP_REST_Response(
					[
						'data' => [],
						'meta' => [
							'total'        => 0,
							'per_page'     => $per_page,
							'current_page' => $page,
							'total_pages'  => 0,
						],
					],
					200
				);
			}
		}

		$query   = new WP_Query( $query_args );
		$job_ids = wp_list_pluck( $query->posts, 'ID' );

		// Application Counts batch-laden (vermeidet N+1).
		$app_counts = ! empty( $job_ids ) ? $this->get_application_counts( $job_ids ) : [];

		$jobs = [];
		foreach ( $query->posts as $post ) {
			$jobs[] = $this->prepare_job_response( $post, $app_counts );
		}

		return new WP_REST_Response(
			[
				'data' => $jobs,
				'meta' => [
					'total'        => (int) $query->found_posts,
					'per_page'     => (int) $per_page,
					'current_page' => (int) $page,
					'total_pages'  => (int) $query->max_num_pages,
				],
			],
			200
		);
	}

	/**
	 * Einzelnen Job abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return new WP_Error(
				'rest_job_not_found',
				__( 'Stelle nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Rollen-Filter: Nicht-Admins dürfen nur zugewiesene Jobs sehen.
		if ( ! current_user_can( 'manage_options' ) ) {
			$capability_service = new CapabilityService();
			$assigned_job_ids   = $capability_service->getAssignedJobIds( get_current_user_id() );

			if ( ! empty( $assigned_job_ids ) && ! in_array( $id, $assigned_job_ids, true ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sie haben keine Berechtigung, diese Stelle anzuzeigen.', 'recruiting-playbook' ),
					[ 'status' => 403 ]
				);
			}
		}

		$app_counts = $this->get_application_counts( [ $id ] );

		return new WP_REST_Response(
			$this->prepare_job_response( $post, $app_counts ),
			200
		);
	}

	/**
	 * Neuen Job erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$title = $request->get_param( 'title' );

		if ( empty( $title ) ) {
			return new WP_Error(
				'rest_missing_title',
				__( 'Der Titel ist ein Pflichtfeld.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$post_data = [
			'post_type'   => 'job_listing',
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => sanitize_text_field( $request->get_param( 'status' ) ?: 'draft' ),
		];

		$description = $request->get_param( 'description' );
		if ( null !== $description ) {
			$post_data['post_content'] = wp_kses_post( $description );
		}

		$excerpt = $request->get_param( 'excerpt' );
		if ( null !== $excerpt ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $excerpt );
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->save_job_meta( $post_id, $request );
		$this->save_job_taxonomies( $post_id, $request );

		$post       = get_post( $post_id );
		$app_counts = $this->get_application_counts( [ $post_id ] );

		return new WP_REST_Response(
			$this->prepare_job_response( $post, $app_counts ),
			201
		);
	}

	/**
	 * Job aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return new WP_Error(
				'rest_job_not_found',
				__( 'Stelle nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Post-Daten nur updaten wenn übergeben.
		$post_data       = [ 'ID' => $id ];
		$has_post_update = false;

		$title = $request->get_param( 'title' );
		if ( null !== $title ) {
			$post_data['post_title'] = sanitize_text_field( $title );
			$has_post_update         = true;
		}

		$description = $request->get_param( 'description' );
		if ( null !== $description ) {
			$post_data['post_content'] = wp_kses_post( $description );
			$has_post_update           = true;
		}

		$excerpt = $request->get_param( 'excerpt' );
		if ( null !== $excerpt ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $excerpt );
			$has_post_update           = true;
		}

		$status = $request->get_param( 'status' );
		if ( null !== $status ) {
			// Archiviert = draft.
			if ( 'archived' === $status ) {
				$status = 'draft';
			}
			$post_data['post_status'] = sanitize_text_field( $status );
			$has_post_update          = true;
		}

		if ( $has_post_update ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$this->save_job_meta( $id, $request );
		$this->save_job_taxonomies( $id, $request );

		$post       = get_post( $id );
		$app_counts = $this->get_application_counts( [ $id ] );

		return new WP_REST_Response(
			$this->prepare_job_response( $post, $app_counts ),
			200
		);
	}

	/**
	 * Job löschen oder archivieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$force = (bool) $request->get_param( 'force' );
		$post  = get_post( $id );

		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return new WP_Error(
				'rest_job_not_found',
				__( 'Stelle nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		if ( $force ) {
			$deleted = wp_delete_post( $id, true );

			if ( ! $deleted ) {
				return new WP_Error(
					'rest_delete_failed',
					__( 'Stelle konnte nicht gelöscht werden.', 'recruiting-playbook' ),
					[ 'status' => 500 ]
				);
			}

			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Stelle wurde endgültig gelöscht.', 'recruiting-playbook' ),
				],
				200
			);
		}

		// Archivieren: Status auf draft setzen.
		$result = wp_update_post(
			[
				'ID'          => $id,
				'post_status' => 'draft',
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Stelle wurde archiviert.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Job-Response aufbereiten
	 *
	 * Konvertiert einen WP_Post in das Spec-Format mit strukturierten
	 * Salary-, Location- und Contact-Objekten.
	 *
	 * @param \WP_Post $post       Post-Objekt.
	 * @param array    $app_counts Application Counts (job_id => count).
	 * @return array
	 */
	private function prepare_job_response( \WP_Post $post, array $app_counts = [] ): array {
		$post_id = $post->ID;

		// Meta-Daten laden.
		$salary_min      = get_post_meta( $post_id, '_rp_salary_min', true );
		$salary_max      = get_post_meta( $post_id, '_rp_salary_max', true );
		$salary_currency = get_post_meta( $post_id, '_rp_salary_currency', true ) ?: 'EUR';
		$salary_period   = get_post_meta( $post_id, '_rp_salary_period', true ) ?: 'month';
		$hide_salary     = get_post_meta( $post_id, '_rp_hide_salary', true );
		$contact_person  = get_post_meta( $post_id, '_rp_contact_person', true );
		$contact_email   = get_post_meta( $post_id, '_rp_contact_email', true );
		$contact_phone   = get_post_meta( $post_id, '_rp_contact_phone', true );
		$deadline        = get_post_meta( $post_id, '_rp_application_deadline', true );
		$start_date      = get_post_meta( $post_id, '_rp_start_date', true );
		$remote_option   = get_post_meta( $post_id, '_rp_remote_option', true );

		// Taxonomien.
		$locations        = get_the_terms( $post_id, 'job_location' );
		$types            = get_the_terms( $post_id, 'employment_type' );
		$categories_terms = get_the_terms( $post_id, 'job_category' );

		// Location-Objekt.
		$city = '';
		if ( $locations && ! is_wp_error( $locations ) ) {
			$city = $locations[0]->name;
		}

		$location = [
			'city'        => $city,
			'postal_code' => null, // Kein Meta-Feld vorhanden.
			'country'     => 'DE',
			'remote'      => ! empty( $remote_option ) && 'no' !== $remote_option,
		];

		// Employment Type (Slug).
		$employment_type = '';
		if ( $types && ! is_wp_error( $types ) ) {
			$employment_type = $types[0]->slug;
		}

		// Categories (Array von Namen).
		$categories = [];
		if ( $categories_terms && ! is_wp_error( $categories_terms ) ) {
			$categories = wp_list_pluck( $categories_terms, 'name' );
		}

		// Salary-Objekt mit Display-Formatierung.
		$salary_display = '';
		if ( ! $hide_salary && ( $salary_min || $salary_max ) ) {
			$period_labels = [
				'hour'  => __( '/Std.', 'recruiting-playbook' ),
				'month' => __( '/Monat', 'recruiting-playbook' ),
				'year'  => __( '/Jahr', 'recruiting-playbook' ),
			];
			$period_label  = $period_labels[ $salary_period ] ?? '';

			if ( $salary_min && $salary_max ) {
				$salary_display = number_format( (float) $salary_min, 0, ',', '.' )
					. ' - '
					. number_format( (float) $salary_max, 0, ',', '.' )
					. ' ' . $salary_currency . $period_label;
			} elseif ( $salary_min ) {
				$salary_display = __( 'Ab ', 'recruiting-playbook' )
					. number_format( (float) $salary_min, 0, ',', '.' )
					. ' ' . $salary_currency . $period_label;
			} elseif ( $salary_max ) {
				$salary_display = __( 'Bis ', 'recruiting-playbook' )
					. number_format( (float) $salary_max, 0, ',', '.' )
					. ' ' . $salary_currency . $period_label;
			}
		}

		$salary = [
			'min'      => $salary_min ? (float) $salary_min : null,
			'max'      => $salary_max ? (float) $salary_max : null,
			'currency' => $salary_currency,
			'period'   => $salary_period,
			'display'  => $salary_display,
		];

		// Contact-Objekt.
		$contact = [
			'name'  => $contact_person ?: null,
			'email' => $contact_email ?: null,
			'phone' => $contact_phone ?: null,
		];

		return [
			'id'                   => $post_id,
			'title'                => $post->post_title,
			'slug'                 => $post->post_name,
			'status'               => $post->post_status,
			'description'          => $post->post_content,
			'description_plain'    => wp_strip_all_tags( $post->post_content ),
			'excerpt'              => $post->post_excerpt,
			'location'             => $location,
			'employment_type'      => $employment_type,
			'salary'               => $salary,
			'contact'              => $contact,
			'application_deadline' => $deadline ?: null,
			'start_date'           => $start_date ?: null,
			'categories'           => $categories,
			'tags'                 => [],
			'application_count'    => $app_counts[ $post_id ] ?? 0,
			'created_at'           => mysql2date( 'c', $post->post_date_gmt ),
			'updated_at'           => mysql2date( 'c', $post->post_modified_gmt ),
			'published_at'         => 'publish' === $post->post_status
				? mysql2date( 'c', $post->post_date_gmt )
				: null,
			'url'                  => get_permalink( $post_id ),
			'apply_url'            => get_permalink( $post_id ) . '#apply',
		];
	}

	/**
	 * Meta-Felder speichern (shared zwischen create/update)
	 *
	 * Speichert nur übergebene Felder (partielle Updates möglich).
	 *
	 * @param int             $post_id Post-ID.
	 * @param WP_REST_Request $request Request.
	 */
	private function save_job_meta( int $post_id, WP_REST_Request $request ): void {
		// Salary-Objekt.
		$salary = $request->get_param( 'salary' );
		if ( is_array( $salary ) ) {
			if ( isset( $salary['min'] ) ) {
				update_post_meta( $post_id, '_rp_salary_min', sanitize_text_field( (string) $salary['min'] ) );
			}
			if ( isset( $salary['max'] ) ) {
				update_post_meta( $post_id, '_rp_salary_max', sanitize_text_field( (string) $salary['max'] ) );
			}
			if ( isset( $salary['currency'] ) ) {
				update_post_meta( $post_id, '_rp_salary_currency', sanitize_text_field( $salary['currency'] ) );
			}
			if ( isset( $salary['period'] ) ) {
				update_post_meta( $post_id, '_rp_salary_period', sanitize_text_field( $salary['period'] ) );
			}
		}

		// Hide Salary.
		$hide_salary = $request->get_param( 'hide_salary' );
		if ( null !== $hide_salary ) {
			update_post_meta( $post_id, '_rp_hide_salary', $hide_salary ? '1' : '' );
		}

		// Contact-Objekt.
		$contact = $request->get_param( 'contact' );
		if ( is_array( $contact ) ) {
			if ( isset( $contact['name'] ) ) {
				update_post_meta( $post_id, '_rp_contact_person', sanitize_text_field( $contact['name'] ) );
			}
			if ( isset( $contact['email'] ) ) {
				update_post_meta( $post_id, '_rp_contact_email', sanitize_email( $contact['email'] ) );
			}
			if ( isset( $contact['phone'] ) ) {
				update_post_meta( $post_id, '_rp_contact_phone', sanitize_text_field( $contact['phone'] ) );
			}
		}

		// Datum-Felder.
		$deadline = $request->get_param( 'application_deadline' );
		if ( null !== $deadline ) {
			update_post_meta( $post_id, '_rp_application_deadline', sanitize_text_field( $deadline ) );
		}

		$start_date = $request->get_param( 'start_date' );
		if ( null !== $start_date ) {
			update_post_meta( $post_id, '_rp_start_date', sanitize_text_field( $start_date ) );
		}

		// Remote-Option.
		$remote = $request->get_param( 'remote' );
		if ( null !== $remote ) {
			$remote_value = $remote ? 'full' : 'no';
			update_post_meta( $post_id, '_rp_remote_option', sanitize_text_field( $remote_value ) );
		}

		// Location remote via location-Objekt (überschreibt standalone remote).
		$location = $request->get_param( 'location' );
		if ( is_array( $location ) && isset( $location['remote'] ) ) {
			$remote_value = $location['remote'] ? 'full' : 'no';
			update_post_meta( $post_id, '_rp_remote_option', sanitize_text_field( $remote_value ) );
		}
	}

	/**
	 * Taxonomy-Terme zuweisen (shared zwischen create/update)
	 *
	 * @param int             $post_id Post-ID.
	 * @param WP_REST_Request $request Request.
	 */
	private function save_job_taxonomies( int $post_id, WP_REST_Request $request ): void {
		// Categories.
		$categories = $request->get_param( 'categories' );
		if ( is_array( $categories ) ) {
			wp_set_object_terms( $post_id, $categories, 'job_category' );
		}

		// Employment Type.
		$employment_type = $request->get_param( 'employment_type' );
		if ( null !== $employment_type ) {
			wp_set_object_terms( $post_id, [ sanitize_text_field( $employment_type ) ], 'employment_type' );
		}

		// Location (aus location.city oder standalone).
		$location = $request->get_param( 'location' );
		if ( is_array( $location ) && isset( $location['city'] ) ) {
			wp_set_object_terms( $post_id, [ sanitize_text_field( $location['city'] ) ], 'job_location' );
		}
	}

	/**
	 * Application Counts per Batch-Query laden
	 *
	 * Vermeidet N+1-Problem: Eine Query für alle Job-IDs.
	 *
	 * @param array $job_ids Array von Job-IDs.
	 * @return array Assoziatives Array (job_id => count).
	 */
	private function get_application_counts( array $job_ids ): array {
		global $wpdb;

		if ( empty( $job_ids ) ) {
			return [];
		}

		$table = $wpdb->prefix . 'rp_applications';

		// Prüfen ob die Tabelle existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);

		if ( ! $table_exists ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $job_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared
		$sql     = "SELECT job_id, COUNT(*) as count FROM {$table}"
			. " WHERE job_id IN ({$placeholders}) AND deleted_at IS NULL"
			. ' GROUP BY job_id';
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$job_ids ),
			OBJECT
		);
		// phpcs:enable

		$counts = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				$counts[ (int) $row->job_id ] = (int) $row->count;
			}
		}

		return $counts;
	}

	/**
	 * Collection-Parameter für GET /jobs
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'status'          => [
				'description' => __( 'Nach Status filtern', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'draft', 'publish', 'archived' ],
				'default'     => 'publish',
			],
			'per_page'        => [
				'description' => __( 'Ergebnisse pro Seite', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'page'            => [
				'description' => __( 'Seitennummer', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'search'          => [
				'description' => __( 'Volltextsuche in Titel/Beschreibung', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'location'        => [
				'description' => __( 'Filter nach Standort (Slug)', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'employment_type' => [
				'description' => __( 'Filter nach Beschäftigungsart (Slug)', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'orderby'         => [
				'description' => __( 'Sortierfeld', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'date', 'title', 'modified' ],
				'default'     => 'date',
			],
			'order'           => [
				'description' => __( 'Sortierrichtung', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'asc', 'desc' ],
				'default'     => 'desc',
			],
		];
	}

	/**
	 * Argumente für POST /jobs
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'title'                => [
				'description'       => __( 'Stellentitel', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'description'          => [
				'description'       => __( 'Stellenbeschreibung (HTML)', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			],
			'excerpt'              => [
				'description'       => __( 'Kurzbeschreibung', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_textarea_field',
			],
			'status'               => [
				'description' => __( 'Post-Status', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'draft', 'publish', 'archived' ],
				'default'     => 'draft',
			],
			'location'             => [
				'description' => __( 'Standort-Objekt', 'recruiting-playbook' ),
				'type'        => 'object',
				'required'    => false,
				'properties'  => [
					'city'   => [
						'type' => 'string',
					],
					'remote' => [
						'type' => 'boolean',
					],
				],
			],
			'employment_type'      => [
				'description'       => __( 'Beschäftigungsart (Slug)', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'salary'               => [
				'description' => __( 'Gehalt-Objekt', 'recruiting-playbook' ),
				'type'        => 'object',
				'required'    => false,
				'properties'  => [
					'min'      => [
						'type' => 'number',
					],
					'max'      => [
						'type' => 'number',
					],
					'currency' => [
						'type'    => 'string',
						'default' => 'EUR',
					],
					'period'   => [
						'type'    => 'string',
						'enum'    => [ 'hour', 'month', 'year' ],
						'default' => 'month',
					],
				],
			],
			'contact'              => [
				'description' => __( 'Ansprechpartner-Objekt', 'recruiting-playbook' ),
				'type'        => 'object',
				'required'    => false,
				'properties'  => [
					'name'  => [
						'type' => 'string',
					],
					'email' => [
						'type'   => 'string',
						'format' => 'email',
					],
					'phone' => [
						'type' => 'string',
					],
				],
			],
			'application_deadline' => [
				'description'       => __( 'Bewerbungsfrist (YYYY-MM-DD)', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'start_date'           => [
				'description'       => __( 'Eintrittsdatum (YYYY-MM-DD)', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'categories'           => [
				'description' => __( 'Kategorien (Array von Namen)', 'recruiting-playbook' ),
				'type'        => 'array',
				'required'    => false,
				'items'       => [
					'type' => 'string',
				],
			],
			'hide_salary'          => [
				'description' => __( 'Gehalt ausblenden', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'required'    => false,
			],
			'remote'               => [
				'description' => __( 'Remote-Arbeit möglich', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'required'    => false,
			],
		];
	}

	/**
	 * Argumente für PUT/PATCH /jobs/{id}
	 *
	 * Wie create, aber nichts ist required.
	 *
	 * @return array
	 */
	private function get_update_item_args(): array {
		$args = $this->get_create_item_args();

		// ID-Parameter hinzufügen.
		$args['id'] = [
			'description' => __( 'Job-ID', 'recruiting-playbook' ),
			'type'        => 'integer',
			'required'    => true,
		];

		// Alle Felder optional machen.
		foreach ( $args as $key => &$arg ) {
			if ( 'id' !== $key ) {
				$arg['required'] = false;
			}
		}

		return $args;
	}
}
