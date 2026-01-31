<?php
/**
 * Form Builder Workflow Integration Tests
 *
 * Testet den kompletten Workflow des Form Builders:
 * - Free/Pro Version Verhalten
 * - System-Felder Konfiguration
 * - Pflichtfeld-Validierung
 * - Platzhalter-Ersetzung
 *
 * @package RecruitingPlaybook\Tests\Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\FormConfigService;
use RecruitingPlaybook\Services\FormRenderService;
use RecruitingPlaybook\Services\PlaceholderService;
use RecruitingPlaybook\Services\FormValidationService;
use RecruitingPlaybook\Repositories\FormConfigRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Integration Tests für Form Builder Workflow
 */
class FormBuilderWorkflowTest extends TestCase {

	/**
	 * @var FormConfigService
	 */
	private FormConfigService $config_service;

	/**
	 * @var FormConfigRepository|Mockery\MockInterface
	 */
	private $config_repository;

	/**
	 * @var FieldDefinitionRepository|Mockery\MockInterface
	 */
	private $field_repository;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->returnArg( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-21 12:00:00' );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'get_privacy_policy_url' )->justReturn( '/datenschutz' );
		Functions\when( 'is_email' )->alias( function( $email ) {
			return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
		} );

		// Repositories mocken.
		$this->config_repository = Mockery::mock( FormConfigRepository::class );
		$this->field_repository  = Mockery::mock( FieldDefinitionRepository::class );

		// Standard System-Felder.
		$this->field_repository
			->shouldReceive( 'findAll' )
			->andReturn( $this->getSystemFields() );

		$this->config_service = new FormConfigService(
			$this->config_repository,
			$this->field_repository
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	// =========================================================================
	// Test: Default-Konfiguration (Free Version)
	// =========================================================================

	/**
	 * @test
	 */
	public function default_config_has_required_fields(): void {
		$config = $this->config_service->getDefaultConfig();

		$this->assertEquals( 2, $config['version'] );
		$this->assertCount( 3, $config['steps'] );

		// Pflichtfelder finden.
		$all_fields = [];
		foreach ( $config['steps'] as $step ) {
			foreach ( $step['fields'] ?? [] as $field ) {
				$all_fields[ $field['field_key'] ] = $field;
			}
			foreach ( $step['system_fields'] ?? [] as $sf ) {
				$all_fields[ $sf['field_key'] ] = $sf;
			}
		}

		// Prüfe Pflichtfelder vorhanden.
		$this->assertArrayHasKey( 'first_name', $all_fields );
		$this->assertArrayHasKey( 'last_name', $all_fields );
		$this->assertArrayHasKey( 'email', $all_fields );
		$this->assertArrayHasKey( 'privacy_consent', $all_fields );

		// Prüfe is_removable: false für Pflichtfelder.
		$this->assertFalse( $all_fields['first_name']['is_removable'] );
		$this->assertFalse( $all_fields['last_name']['is_removable'] );
		$this->assertFalse( $all_fields['email']['is_removable'] );
		$this->assertFalse( $all_fields['privacy_consent']['is_removable'] );
	}

	/**
	 * @test
	 */
	public function default_config_has_system_fields_in_correct_steps(): void {
		$config = $this->config_service->getDefaultConfig();

		// Dokumente-Step hat file_upload.
		$documents_step = null;
		$finale_step    = null;

		foreach ( $config['steps'] as $step ) {
			if ( 'step_documents' === $step['id'] ) {
				$documents_step = $step;
			}
			if ( ! empty( $step['is_finale'] ) ) {
				$finale_step = $step;
			}
		}

		$this->assertNotNull( $documents_step );
		$this->assertNotNull( $finale_step );

		// File-Upload im Dokumente-Step.
		$doc_system_keys = array_column( $documents_step['system_fields'] ?? [], 'field_key' );
		$this->assertContains( 'file_upload', $doc_system_keys );

		// Summary und Privacy im Finale-Step.
		$finale_system_keys = array_column( $finale_step['system_fields'] ?? [], 'field_key' );
		$this->assertContains( 'summary', $finale_system_keys );
		$this->assertContains( 'privacy_consent', $finale_system_keys );
	}

	// =========================================================================
	// Test: Validierung verhindert Entfernen von Pflichtfeldern
	// =========================================================================

	/**
	 * @test
	 */
	public function save_draft_rejects_missing_first_name(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						// first_name fehlt!
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$result = $this->config_service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function save_draft_rejects_missing_last_name(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						// last_name fehlt!
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$result = $this->config_service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function save_draft_rejects_missing_email(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						// email fehlt!
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$result = $this->config_service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function save_draft_rejects_missing_privacy_consent(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						// privacy_consent fehlt!
						[ 'field_key' => 'summary' ],
					],
				],
			],
		];

		$result = $this->config_service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_privacy_consent', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function save_draft_succeeds_with_all_required_fields(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'summary' ],
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$this->config_repository
			->shouldReceive( 'saveDraft' )
			->once()
			->andReturn( true );

		$result = $this->config_service->saveDraft( $config );

		$this->assertTrue( $result );
	}

	// =========================================================================
	// Test: Migration v1 → v2
	// =========================================================================

	/**
	 * @test
	 */
	public function migration_converts_v1_to_v2(): void {
		$v1_config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_documents',
					'title'  => 'Dokumente',
					'fields' => [
						[ 'field_key' => 'resume', 'is_visible' => true ],
					],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'is_finale' => true,
					'fields'    => [
						[ 'field_key' => 'privacy_consent', 'is_visible' => true ],
					],
				],
			],
		];

		$result = $this->config_service->migrateConfig( $v1_config );

		$this->assertEquals( 2, $result['version'] );

		// Document-Step hat system_fields mit file_upload.
		$doc_step = $result['steps'][0];
		$this->assertArrayHasKey( 'system_fields', $doc_step );

		$system_keys = array_column( $doc_step['system_fields'], 'field_key' );
		$this->assertContains( 'file_upload', $system_keys );

		// Finale-Step hat system_fields mit summary und privacy_consent.
		$finale_step        = $result['steps'][1];
		$finale_system_keys = array_column( $finale_step['system_fields'], 'field_key' );
		$this->assertContains( 'summary', $finale_system_keys );
		$this->assertContains( 'privacy_consent', $finale_system_keys );
	}

	/**
	 * @test
	 */
	public function migration_leaves_v2_unchanged(): void {
		$v2_config = [
			'version' => 2,
			'steps'   => [
				[
					'id'            => 'step_1',
					'title'         => 'Test',
					'fields'        => [],
					'system_fields' => [],
				],
			],
		];

		$result = $this->config_service->migrateConfig( $v2_config );

		$this->assertEquals( $v2_config, $result );
	}

	// =========================================================================
	// Test: getActiveFields für ApplicantDetail
	// =========================================================================

	/**
	 * @test
	 */
	public function get_active_fields_returns_visible_fields_only(): void {
		$published_config = [
			'config_data' => [
				'version' => 2,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Daten',
						'fields' => [
							[ 'field_key' => 'first_name', 'is_visible' => true ],
							[ 'field_key' => 'last_name', 'is_visible' => true ],
							[ 'field_key' => 'email', 'is_visible' => true ],
							[ 'field_key' => 'phone', 'is_visible' => false ], // Nicht sichtbar!
						],
					],
					[
						'id'            => 'step_finale',
						'title'         => 'Abschluss',
						'is_finale'     => true,
						'fields'        => [],
						'system_fields' => [
							[ 'field_key' => 'file_upload', 'type' => 'file_upload' ],
							[ 'field_key' => 'privacy_consent', 'type' => 'privacy_consent' ],
						],
					],
				],
			],
		];

		$this->config_repository
			->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $published_config );

		$this->config_repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 1 );

		$result = $this->config_service->getActiveFields();

		$this->assertArrayHasKey( 'fields', $result );
		$this->assertArrayHasKey( 'system_fields', $result );

		// Nur 3 sichtbare Felder (phone ist nicht sichtbar).
		$this->assertCount( 3, $result['fields'] );

		$field_keys = array_column( $result['fields'], 'field_key' );
		$this->assertContains( 'first_name', $field_keys );
		$this->assertContains( 'last_name', $field_keys );
		$this->assertContains( 'email', $field_keys );
		$this->assertNotContains( 'phone', $field_keys );

		// System-Felder.
		$this->assertCount( 2, $result['system_fields'] );
	}

	// =========================================================================
	// Test: PlaceholderService
	// =========================================================================

	/**
	 * @test
	 */
	public function placeholder_service_replaces_all_standard_placeholders(): void {
		Functions\when( 'get_option' )->alias( function( $key, $default = false ) {
			if ( 'rp_settings' === $key ) {
				return [
					'company_name'   => 'Test GmbH',
					'company_street' => 'Musterstraße 1',
					'company_zip'    => '12345',
					'company_city'   => 'Berlin',
				];
			}
			if ( 'date_format' === $key ) {
				return 'd.m.Y';
			}
			return $default;
		} );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp = false ) {
			return date( $format, $timestamp ?: time() );
		} );

		$service = new PlaceholderService();

		$template = 'Hallo {vorname} {nachname}, Ihre Bewerbung für {stelle} bei {firma} ist eingegangen.';
		$context  = [
			'candidate' => [
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
				'email'      => 'max@example.com',
			],
			'job' => [
				'title' => 'PHP Developer',
			],
		];

		$result = $service->replace( $template, $context );

		$this->assertStringContainsString( 'Max', $result );
		$this->assertStringContainsString( 'Mustermann', $result );
		$this->assertStringContainsString( 'PHP Developer', $result );
		$this->assertStringContainsString( 'Test GmbH', $result );
		$this->assertStringNotContainsString( '{vorname}', $result );
		$this->assertStringNotContainsString( '{nachname}', $result );
	}

	/**
	 * @test
	 */
	public function placeholder_service_validates_templates(): void {
		Functions\when( 'get_option' )->justReturn( [] );

		$service = new PlaceholderService();

		// Template mit ungültigen Platzhaltern.
		$template = 'Hallo {vorname}, {unbekannter_platzhalter} und {noch_ein_fehler}.';

		$result = $service->validateTemplate( $template );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'unbekannter_platzhalter', $result['invalid'] );
		$this->assertContains( 'noch_ein_fehler', $result['invalid'] );
		$this->assertContains( 'vorname', $result['found'] );
	}

	/**
	 * @test
	 */
	public function placeholder_service_creates_formal_salutation(): void {
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'date_i18n' )->justReturn( '21.01.2025' );

		$service = new PlaceholderService();

		// Test mit "Herr".
		$result1 = $service->replace(
			'{anrede_formal}',
			[ 'candidate' => [ 'salutation' => 'Herr', 'last_name' => 'Müller' ] ]
		);
		$this->assertStringContainsString( 'Sehr geehrter Herr Müller', $result1 );

		// Test mit "Frau".
		$result2 = $service->replace(
			'{anrede_formal}',
			[ 'candidate' => [ 'salutation' => 'Frau', 'last_name' => 'Schmidt' ] ]
		);
		$this->assertStringContainsString( 'Sehr geehrte Frau Schmidt', $result2 );

		// Test ohne Anrede.
		$result3 = $service->replace(
			'{anrede_formal}',
			[ 'candidate' => [ 'salutation' => '', 'last_name' => 'Weber' ] ]
		);
		$this->assertStringContainsString( 'Guten Tag Weber', $result3 );
	}

	/**
	 * @test
	 */
	public function placeholder_service_replaces_custom_fields(): void {
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'date_i18n' )->justReturn( '21.01.2025' );

		$service = new PlaceholderService();

		$template = 'Ihre Erfahrung: {custom_experience}';
		$context  = [
			'custom' => [
				'custom_experience' => '5 Jahre PHP',
			],
		];

		$result = $service->replace( $template, $context );

		$this->assertStringContainsString( '5 Jahre PHP', $result );
	}

	// =========================================================================
	// Test: System-Feld Settings
	// =========================================================================

	/**
	 * @test
	 */
	public function system_field_settings_are_preserved_in_config(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
				[
					'id'            => 'step_documents',
					'title'         => 'Dokumente',
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'file_upload',
							'type'      => 'file_upload',
							'settings'  => [
								'max_files'      => 5,
								'max_size_mb'    => 10,
								'allowed_types'  => [ 'pdf', 'docx' ],
								'required_files' => [ 'lebenslauf' ],
							],
						],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'summary',
							'type'      => 'summary',
							'settings'  => [
								'layout' => 'compact',
							],
						],
						[
							'field_key' => 'privacy_consent',
							'type'      => 'privacy_consent',
							'settings'  => [
								'consent_text' => 'Ich stimme der {privacy_link} zu.',
							],
						],
					],
				],
			],
		];

		$saved_config = null;

		$this->config_repository
			->shouldReceive( 'saveDraft' )
			->once()
			->andReturnUsing( function( $data ) use ( &$saved_config ) {
				$saved_config = $data;
				return true;
			} );

		$result = $this->config_service->saveDraft( $config );

		$this->assertTrue( $result );

		// Prüfe ob Settings korrekt gespeichert wurden.
		$file_upload_step = null;
		foreach ( $saved_config['steps'] as $step ) {
			foreach ( $step['system_fields'] ?? [] as $sf ) {
				if ( 'file_upload' === $sf['field_key'] ) {
					$file_upload_step = $sf;
					break 2;
				}
			}
		}

		$this->assertNotNull( $file_upload_step );
		$this->assertEquals( 5, $file_upload_step['settings']['max_files'] );
		$this->assertEquals( 10, $file_upload_step['settings']['max_size_mb'] );
	}

	// =========================================================================
	// Test: isFieldRemovable
	// =========================================================================

	/**
	 * @test
	 */
	public function is_field_removable_returns_correct_values(): void {
		// Pflichtfelder sind nicht entfernbar.
		$this->assertFalse( $this->config_service->isFieldRemovable( 'first_name' ) );
		$this->assertFalse( $this->config_service->isFieldRemovable( 'last_name' ) );
		$this->assertFalse( $this->config_service->isFieldRemovable( 'email' ) );
		$this->assertFalse( $this->config_service->isFieldRemovable( 'privacy_consent' ) );

		// Optionale Felder sind entfernbar.
		$this->assertTrue( $this->config_service->isFieldRemovable( 'phone' ) );
		$this->assertTrue( $this->config_service->isFieldRemovable( 'message' ) );
		$this->assertTrue( $this->config_service->isFieldRemovable( 'salutation' ) );
		$this->assertTrue( $this->config_service->isFieldRemovable( 'custom_field_123' ) );
	}

	// =========================================================================
	// Helper: Standard System-Felder
	// =========================================================================

	/**
	 * Standard System-Felder für Tests
	 *
	 * @return FieldDefinition[]
	 */
	private function getSystemFields(): array {
		return [
			$this->createFieldDefinition( 'first_name', 'text', 'Vorname', true, true ),
			$this->createFieldDefinition( 'last_name', 'text', 'Nachname', true, true ),
			$this->createFieldDefinition( 'email', 'email', 'E-Mail', true, true ),
			$this->createFieldDefinition( 'phone', 'phone', 'Telefon', false, true ),
			$this->createFieldDefinition( 'message', 'textarea', 'Nachricht', false, true ),
			$this->createFieldDefinition( 'salutation', 'select', 'Anrede', false, true ),
		];
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 */
	private function createFieldDefinition(
		string $key,
		string $type,
		string $label,
		bool $required = false,
		bool $is_system = false
	): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'field_type'  => $type,
			'label'       => $label,
			'is_required' => $required,
			'is_enabled'  => true,
			'is_system'   => $is_system,
		];

		return new FieldDefinition( $data );
	}
}
