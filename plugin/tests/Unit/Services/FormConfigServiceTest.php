<?php
/**
 * FormConfigService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\FormConfigService;
use RecruitingPlaybook\Repositories\FormConfigRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Tests für den FormConfigService
 */
class FormConfigServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var FormConfigService
	 */
	private FormConfigService $service;

	/**
	 * Mock Repository
	 *
	 * @var FormConfigRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Mock Field Repository
	 *
	 * @var FieldDefinitionRepository|Mockery\MockInterface
	 */
	private $field_repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository       = Mockery::mock( FormConfigRepository::class );
		$this->field_repository = Mockery::mock( FieldDefinitionRepository::class );
		$this->service          = new FormConfigService(
			$this->repository,
			$this->field_repository
		);

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
	}

	/**
	 * Test: getDraft lädt Draft-Konfiguration
	 */
	public function test_get_draft_loads_draft_config(): void {
		$draft_data = [
			'config_data' => [
				'version' => 1,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Persönliche Daten',
						'fields' => [],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( $draft_data );

		$result = $this->service->getDraft();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'steps', $result );
		$this->assertCount( 1, $result['steps'] );
	}

	/**
	 * Test: getDraft gibt Default-Konfiguration zurück wenn kein Draft existiert
	 */
	public function test_get_draft_returns_default_when_no_draft(): void {
		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( null );

		$result = $this->service->getDraft();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'steps', $result );
		$this->assertCount( 3, $result['steps'] ); // Default hat 3 Steps
	}

	/**
	 * Test: getPublished lädt veröffentlichte Konfiguration
	 */
	public function test_get_published_loads_published_config(): void {
		$published_data = [
			'config_data' => [
				'version' => 1,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Schritt 1',
						'fields' => [],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $published_data );

		$result = $this->service->getPublished();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'steps', $result );
	}

	/**
	 * Test: saveDraft speichert Draft erfolgreich
	 */
	public function test_save_draft_success(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'       => 'step_personal',
					'title'    => 'Persönliche Daten',
					'position' => 1,
					'fields'   => [
						[ 'field_key' => 'first_name', 'is_visible' => true, 'is_required' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true, 'is_required' => true ],
						[ 'field_key' => 'email', 'is_visible' => true, 'is_required' => true ],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'position'      => 999,
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'saveDraft' )
			->once()
			->andReturn( true );

		$result = $this->service->saveDraft( $config );

		$this->assertTrue( $result );
	}

	/**
	 * Test: saveDraft validiert fehlende Steps
	 */
	public function test_save_draft_validates_missing_steps(): void {
		$config = [
			'version' => 1,
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_steps', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert leere Steps
	 */
	public function test_save_draft_validates_empty_steps(): void {
		$config = [
			'version' => 1,
			'steps'   => [],
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'empty_steps', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlenden Finale-Step
	 */
	public function test_save_draft_validates_missing_finale(): void {
		$config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
					'fields' => [
						[ 'field_key' => 'email', 'is_visible' => true ],
						[ 'field_key' => 'privacy_consent', 'is_visible' => true ],
					],
				],
			],
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_finale', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlendes Email-Feld
	 */
	public function test_save_draft_validates_missing_email_field(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
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

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlendes Privacy-Consent-Feld
	 */
	public function test_save_draft_validates_missing_privacy_consent(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
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
					'system_fields' => [], // Kein privacy_consent!
				],
			],
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_privacy_consent', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlende Step-ID
	 */
	public function test_save_draft_validates_missing_step_id(): void {
		$config = [
			'version' => 1,
			'steps'   => [
				[
					'title'  => 'Ohne ID',
					'fields' => [],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'is_finale' => true,
					'fields'    => [
						[ 'field_key' => 'email', 'is_visible' => true ],
						[ 'field_key' => 'privacy_consent', 'is_visible' => true ],
					],
				],
			],
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_step_id', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlenden Step-Titel
	 */
	public function test_save_draft_validates_missing_step_title(): void {
		$config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_1',
					'fields' => [],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'is_finale' => true,
					'fields'    => [
						[ 'field_key' => 'email', 'is_visible' => true ],
						[ 'field_key' => 'privacy_consent', 'is_visible' => true ],
					],
				],
			],
		];

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_step_title', $result->get_error_code() );
	}

	/**
	 * Test: publish veröffentlicht Draft erfolgreich
	 */
	public function test_publish_success(): void {
		$draft_data = [
			'config_data' => [
				'version' => 1,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Schritt 1',
						'fields' => [
							[ 'field_key' => 'email', 'is_visible' => true ],
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
			],
		];

		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( $draft_data );

		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( true );

		$this->repository
			->shouldReceive( 'publish' )
			->once()
			->andReturn( true );

		$result = $this->service->publish();

		$this->assertTrue( $result );
	}

	/**
	 * Test: publish gibt Fehler zurück wenn keine Änderungen vorhanden
	 */
	public function test_publish_returns_error_when_no_changes(): void {
		$draft_data = [
			'config_data' => [
				'version' => 1,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Schritt 1',
						'fields' => [
							[ 'field_key' => 'email', 'is_visible' => true ],
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
			],
		];

		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( $draft_data );

		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( false );

		$result = $this->service->publish();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'no_changes', $result->get_error_code() );
	}

	/**
	 * Test: discardDraft verwirft Änderungen
	 */
	public function test_discard_draft_success(): void {
		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( true );

		$this->repository
			->shouldReceive( 'discardDraft' )
			->once()
			->andReturn( true );

		$result = $this->service->discardDraft();

		$this->assertTrue( $result );
	}

	/**
	 * Test: discardDraft gibt Fehler zurück wenn keine Änderungen vorhanden
	 */
	public function test_discard_draft_returns_error_when_no_changes(): void {
		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( false );

		$result = $this->service->discardDraft();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'no_changes', $result->get_error_code() );
	}

	/**
	 * Test: hasUnpublishedChanges prüft korrekt
	 */
	public function test_has_unpublished_changes(): void {
		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( true );

		$result = $this->service->hasUnpublishedChanges();

		$this->assertTrue( $result );
	}

	/**
	 * Test: getPublishedVersion gibt Version zurück
	 */
	public function test_get_published_version(): void {
		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 5 );

		$result = $this->service->getPublishedVersion();

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test: getPublishedVersion gibt 1 zurück wenn keine Version existiert
	 */
	public function test_get_published_version_returns_1_when_null(): void {
		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( null );

		$result = $this->service->getPublishedVersion();

		$this->assertEquals( 1, $result );
	}

	/**
	 * Test: getDefaultConfig gibt Standard-Konfiguration zurück
	 */
	public function test_get_default_config(): void {
		$result = $this->service->getDefaultConfig();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'settings', $result );
		$this->assertArrayHasKey( 'steps', $result );

		// Default hat 3 Steps: Personal, Documents, Finale
		$this->assertCount( 3, $result['steps'] );

		// Letzter Step sollte is_finale sein
		$finale = end( $result['steps'] );
		$this->assertTrue( $finale['is_finale'] );
	}

	/**
	 * Test: getAvailableFields lädt alle Felder
	 */
	public function test_get_available_fields(): void {
		$fields = [
			new FieldDefinition( [
				'field_key'  => 'first_name',
				'field_type' => 'text',
				'label'      => 'Vorname',
				'is_system'  => 1,
			] ),
			new FieldDefinition( [
				'field_key'  => 'email',
				'field_type' => 'email',
				'label'      => 'E-Mail',
				'is_system'  => 1,
			] ),
		];

		$this->field_repository
			->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$result = $this->service->getAvailableFields();

		$this->assertCount( 2, $result );
		$this->assertEquals( 'first_name', $result[0]['field_key'] );
		$this->assertEquals( 'email', $result[1]['field_key'] );
	}

	/**
	 * Test: getBuilderData gibt vollständige Builder-Daten zurück
	 */
	public function test_get_builder_data(): void {
		$draft_data = [
			'config_data' => [
				'version' => 2,
				'steps'   => [],
			],
		];

		$fields = [
			new FieldDefinition( [
				'field_key'  => 'email',
				'field_type' => 'email',
				'label'      => 'E-Mail',
				'is_system'  => 1,
			] ),
		];

		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( $draft_data );

		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 2 );

		$this->repository
			->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( true );

		$this->field_repository
			->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$result = $this->service->getBuilderData();

		$this->assertArrayHasKey( 'draft', $result );
		$this->assertArrayHasKey( 'published_version', $result );
		$this->assertArrayHasKey( 'has_changes', $result );
		$this->assertArrayHasKey( 'available_fields', $result );

		$this->assertEquals( 2, $result['published_version'] );
		$this->assertTrue( $result['has_changes'] );
		$this->assertCount( 1, $result['available_fields'] );
	}

	// ==========================================================================
	// Schema v2 Tests
	// ==========================================================================

	/**
	 * Test: Default-Config hat Version 2
	 */
	public function test_default_config_has_version_2(): void {
		$result = $this->service->getDefaultConfig();

		$this->assertEquals( 2, $result['version'] );
	}

	/**
	 * Test: Default-Config hat system_fields im Dokumente-Step
	 */
	public function test_default_config_has_system_fields_in_documents_step(): void {
		$result = $this->service->getDefaultConfig();

		$documents_step = null;
		foreach ( $result['steps'] as $step ) {
			if ( 'step_documents' === $step['id'] ) {
				$documents_step = $step;
				break;
			}
		}

		$this->assertNotNull( $documents_step );
		$this->assertArrayHasKey( 'system_fields', $documents_step );
		$this->assertCount( 1, $documents_step['system_fields'] );
		$this->assertEquals( 'file_upload', $documents_step['system_fields'][0]['field_key'] );
	}

	/**
	 * Test: Default-Config hat system_fields im Finale-Step
	 */
	public function test_default_config_has_system_fields_in_finale_step(): void {
		$result = $this->service->getDefaultConfig();

		$finale_step = null;
		foreach ( $result['steps'] as $step ) {
			if ( ! empty( $step['is_finale'] ) ) {
				$finale_step = $step;
				break;
			}
		}

		$this->assertNotNull( $finale_step );
		$this->assertArrayHasKey( 'system_fields', $finale_step );
		$this->assertCount( 2, $finale_step['system_fields'] );

		$system_field_keys = array_column( $finale_step['system_fields'], 'field_key' );
		$this->assertContains( 'summary', $system_field_keys );
		$this->assertContains( 'privacy_consent', $system_field_keys );
	}

	/**
	 * Test: Pflichtfelder haben is_removable: false
	 */
	public function test_required_fields_have_is_removable_false(): void {
		$result = $this->service->getDefaultConfig();

		$required_fields = [ 'first_name', 'last_name', 'email' ];
		$found_fields    = [];

		foreach ( $result['steps'] as $step ) {
			foreach ( $step['fields'] ?? [] as $field ) {
				if ( in_array( $field['field_key'], $required_fields, true ) ) {
					$found_fields[ $field['field_key'] ] = $field;
				}
			}
		}

		foreach ( $required_fields as $required_field ) {
			$this->assertArrayHasKey( $required_field, $found_fields, "Feld {$required_field} sollte vorhanden sein" );
			$this->assertFalse( $found_fields[ $required_field ]['is_removable'], "Feld {$required_field} sollte is_removable: false haben" );
		}
	}

	/**
	 * Test: privacy_consent in system_fields hat is_removable: false
	 */
	public function test_privacy_consent_in_system_fields_is_not_removable(): void {
		$result = $this->service->getDefaultConfig();

		$finale_step = null;
		foreach ( $result['steps'] as $step ) {
			if ( ! empty( $step['is_finale'] ) ) {
				$finale_step = $step;
				break;
			}
		}

		$privacy_consent = null;
		foreach ( $finale_step['system_fields'] as $field ) {
			if ( 'privacy_consent' === $field['field_key'] ) {
				$privacy_consent = $field;
				break;
			}
		}

		$this->assertNotNull( $privacy_consent );
		$this->assertFalse( $privacy_consent['is_removable'] );
	}

	/**
	 * Test: Optionale Felder haben is_removable: true
	 */
	public function test_optional_fields_have_is_removable_true(): void {
		$result = $this->service->getDefaultConfig();

		$optional_fields = [ 'phone', 'message' ];
		$found_fields    = [];

		foreach ( $result['steps'] as $step ) {
			foreach ( $step['fields'] ?? [] as $field ) {
				if ( in_array( $field['field_key'], $optional_fields, true ) ) {
					$found_fields[ $field['field_key'] ] = $field;
				}
			}
		}

		foreach ( $found_fields as $field_key => $field ) {
			$this->assertTrue( $field['is_removable'], "Feld {$field_key} sollte is_removable: true haben" );
		}
	}

	// ==========================================================================
	// isFieldRemovable Tests
	// ==========================================================================

	/**
	 * Test: isFieldRemovable gibt false für Pflichtfelder
	 */
	public function test_is_field_removable_returns_false_for_required_fields(): void {
		$this->assertFalse( $this->service->isFieldRemovable( 'first_name' ) );
		$this->assertFalse( $this->service->isFieldRemovable( 'last_name' ) );
		$this->assertFalse( $this->service->isFieldRemovable( 'email' ) );
		$this->assertFalse( $this->service->isFieldRemovable( 'privacy_consent' ) );
	}

	/**
	 * Test: isFieldRemovable gibt true für optionale Felder
	 */
	public function test_is_field_removable_returns_true_for_optional_fields(): void {
		$this->assertTrue( $this->service->isFieldRemovable( 'phone' ) );
		$this->assertTrue( $this->service->isFieldRemovable( 'message' ) );
		$this->assertTrue( $this->service->isFieldRemovable( 'salutation' ) );
		$this->assertTrue( $this->service->isFieldRemovable( 'custom_field_123' ) );
	}

	// ==========================================================================
	// Migration Tests
	// ==========================================================================

	/**
	 * Test: migrateConfig lässt v2 Config unverändert
	 */
	public function test_migrate_config_leaves_v2_unchanged(): void {
		$v2_config = [
			'version' => 2,
			'steps'   => [
				[
					'id'    => 'step_test',
					'title' => 'Test',
				],
			],
		];

		$result = $this->service->migrateConfig( $v2_config );

		$this->assertEquals( $v2_config, $result );
	}

	/**
	 * Test: migrateConfig konvertiert v1 zu v2
	 */
	public function test_migrate_config_converts_v1_to_v2(): void {
		$v1_config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_personal',
					'title'  => 'Persönliche Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'email', 'is_visible' => true ],
					],
				],
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

		$result = $this->service->migrateConfig( $v1_config );

		$this->assertEquals( 2, $result['version'] );
	}

	/**
	 * Test: migrateConfig fügt system_fields zu documents Step hinzu
	 */
	public function test_migrate_config_adds_system_fields_to_documents(): void {
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
					'fields'    => [],
				],
			],
		];

		$result = $this->service->migrateConfig( $v1_config );

		$documents_step = $result['steps'][0];
		$this->assertArrayHasKey( 'system_fields', $documents_step );
		$this->assertEquals( 'file_upload', $documents_step['system_fields'][0]['field_key'] );

		// resume sollte entfernt worden sein
		$field_keys = array_column( $documents_step['fields'], 'field_key' );
		$this->assertNotContains( 'resume', $field_keys );
	}

	/**
	 * Test: migrateConfig fügt system_fields zu finale Step hinzu
	 */
	public function test_migrate_config_adds_system_fields_to_finale(): void {
		$v1_config = [
			'version' => 1,
			'steps'   => [
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

		$result = $this->service->migrateConfig( $v1_config );

		$finale_step = $result['steps'][0];
		$this->assertArrayHasKey( 'system_fields', $finale_step );

		$system_field_keys = array_column( $finale_step['system_fields'], 'field_key' );
		$this->assertContains( 'summary', $system_field_keys );
		$this->assertContains( 'privacy_consent', $system_field_keys );

		// privacy_consent sollte aus fields entfernt worden sein
		$field_keys = array_column( $finale_step['fields'], 'field_key' );
		$this->assertNotContains( 'privacy_consent', $field_keys );
	}

	/**
	 * Test: migrateConfig fügt is_removable Flag hinzu
	 */
	public function test_migrate_config_adds_is_removable_flag(): void {
		$v1_config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_personal',
					'title'  => 'Persönliche Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'phone', 'is_visible' => true ],
					],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'is_finale' => true,
					'fields'    => [],
				],
			],
		];

		$result = $this->service->migrateConfig( $v1_config );

		$personal_step = $result['steps'][0];
		$first_name    = null;
		$phone         = null;

		foreach ( $personal_step['fields'] as $field ) {
			if ( 'first_name' === $field['field_key'] ) {
				$first_name = $field;
			}
			if ( 'phone' === $field['field_key'] ) {
				$phone = $field;
			}
		}

		$this->assertFalse( $first_name['is_removable'] );
		$this->assertTrue( $phone['is_removable'] );
	}

	/**
	 * Test: migrateConfig fügt width Flag hinzu
	 */
	public function test_migrate_config_adds_width_flag(): void {
		$v1_config = [
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_personal',
					'title'  => 'Persönliche Daten',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
						[ 'field_key' => 'last_name', 'is_visible' => true ],
						[ 'field_key' => 'phone', 'is_visible' => true ],
					],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'is_finale' => true,
					'fields'    => [],
				],
			],
		];

		$result = $this->service->migrateConfig( $v1_config );

		$personal_step = $result['steps'][0];

		foreach ( $personal_step['fields'] as $field ) {
			if ( in_array( $field['field_key'], [ 'first_name', 'last_name' ], true ) ) {
				$this->assertEquals( 'half', $field['width'] );
			} else {
				$this->assertEquals( 'full', $field['width'] );
			}
		}
	}

	// ==========================================================================
	// Erweiterte Validierung Tests
	// ==========================================================================

	/**
	 * Test: Validierung schlägt fehl bei fehlendem first_name
	 */
	public function test_validation_fails_for_missing_first_name(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
					'fields' => [
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

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * Test: Validierung schlägt fehl bei fehlendem last_name
	 */
	public function test_validation_fails_for_missing_last_name(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
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

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_required_field', $result->get_error_code() );
	}

	/**
	 * Test: Validierung erfolgreich mit Schema v2 Konfiguration
	 */
	public function test_validation_succeeds_with_v2_config(): void {
		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_personal',
					'title'  => 'Persönliche Daten',
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

		$this->repository
			->shouldReceive( 'saveDraft' )
			->once()
			->andReturn( true );

		$result = $this->service->saveDraft( $config );

		$this->assertTrue( $result );
	}

	/**
	 * Test: getDraft migriert automatisch v1 zu v2
	 */
	public function test_get_draft_auto_migrates_v1_to_v2(): void {
		Functions\when( 'get_privacy_policy_url' )->justReturn( '/datenschutz' );

		$v1_draft = [
			'config_data' => [
				'version' => 1,
				'steps'   => [
					[
						'id'     => 'step_documents',
						'title'  => 'Dokumente',
						'fields' => [],
					],
					[
						'id'        => 'step_finale',
						'title'     => 'Abschluss',
						'is_finale' => true,
						'fields'    => [],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'getDraft' )
			->once()
			->andReturn( $v1_draft );

		$result = $this->service->getDraft();

		$this->assertEquals( 2, $result['version'] );
		$this->assertArrayHasKey( 'system_fields', $result['steps'][0] );
	}

	/**
	 * Test: REQUIRED_FIELDS Konstante enthält alle Pflichtfelder
	 */
	public function test_required_fields_constant(): void {
		$required = FormConfigService::REQUIRED_FIELDS;

		$this->assertContains( 'first_name', $required );
		$this->assertContains( 'last_name', $required );
		$this->assertContains( 'email', $required );
		$this->assertContains( 'privacy_consent', $required );
		$this->assertCount( 4, $required );
	}

	// ==========================================================================
	// getActiveFields Tests
	// ==========================================================================

	/**
	 * Test: getActiveFields gibt Felder und System-Felder zurück
	 */
	public function test_get_active_fields_returns_fields_and_system_fields(): void {
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );

		$published_data = [
			'config_data' => [
				'version' => 2,
				'steps'   => [
					[
						'id'     => 'step_personal',
						'title'  => 'Persönliche Daten',
						'fields' => [
							[ 'field_key' => 'first_name', 'field_type' => 'text', 'label' => 'Vorname', 'is_visible' => true, 'is_required' => true ],
							[ 'field_key' => 'email', 'field_type' => 'email', 'label' => 'E-Mail', 'is_visible' => true, 'is_required' => true ],
							[ 'field_key' => 'phone', 'field_type' => 'phone', 'label' => 'Telefon', 'is_visible' => false ],
						],
					],
					[
						'id'            => 'step_finale',
						'title'         => 'Abschluss',
						'is_finale'     => true,
						'fields'        => [],
						'system_fields' => [
							[ 'field_key' => 'file_upload', 'type' => 'file_upload', 'label' => 'Dokumente' ],
							[ 'field_key' => 'privacy_consent', 'type' => 'privacy_consent', 'label' => 'Datenschutz' ],
						],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $published_data );

		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 1 );

		$result = $this->service->getActiveFields();

		$this->assertArrayHasKey( 'fields', $result );
		$this->assertArrayHasKey( 'system_fields', $result );

		// Nur sichtbare Felder
		$this->assertCount( 2, $result['fields'] );
		$this->assertEquals( 'first_name', $result['fields'][0]['field_key'] );
		$this->assertEquals( 'email', $result['fields'][1]['field_key'] );

		// System-Felder
		$this->assertCount( 2, $result['system_fields'] );
	}

	/**
	 * Test: getActiveFields filtert nicht-sichtbare Felder
	 */
	public function test_get_active_fields_filters_invisible_fields(): void {
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );

		$published_data = [
			'config_data' => [
				'version' => 2,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Step 1',
						'fields' => [
							[ 'field_key' => 'visible_field', 'is_visible' => true ],
							[ 'field_key' => 'hidden_field', 'is_visible' => false ],
						],
					],
					[
						'id'            => 'step_finale',
						'title'         => 'Finale',
						'is_finale'     => true,
						'fields'        => [],
						'system_fields' => [],
					],
				],
			],
		];

		$this->repository
			->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $published_data );

		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 1 );

		$result = $this->service->getActiveFields();

		$field_keys = array_column( $result['fields'], 'field_key' );
		$this->assertContains( 'visible_field', $field_keys );
		$this->assertNotContains( 'hidden_field', $field_keys );
	}

	/**
	 * Test: getActiveFields verwendet Cache
	 */
	public function test_get_active_fields_uses_cache(): void {
		$cached_data = [
			'fields'        => [ [ 'field_key' => 'cached_field' ] ],
			'system_fields' => [],
		];

		Functions\when( 'wp_cache_get' )->justReturn( $cached_data );

		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 1 );

		// getPublished sollte NICHT aufgerufen werden wenn Cache vorhanden
		$this->repository
			->shouldNotReceive( 'getPublished' );

		$result = $this->service->getActiveFields();

		$this->assertEquals( $cached_data, $result );
	}

	/**
	 * Test: getActiveFields gibt leere Arrays zurück bei fehlender Config
	 */
	public function test_get_active_fields_returns_empty_when_no_config(): void {
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );

		$this->repository
			->shouldReceive( 'getPublished' )
			->once()
			->andReturn( null );

		$this->repository
			->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 1 );

		$result = $this->service->getActiveFields();

		$this->assertArrayHasKey( 'fields', $result );
		$this->assertArrayHasKey( 'system_fields', $result );
		$this->assertEmpty( $result['fields'] );
		$this->assertEmpty( $result['system_fields'] );
	}
}
