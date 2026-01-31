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
			'version' => 1,
			'steps'   => [
				[
					'id'       => 'step_personal',
					'title'    => 'Persönliche Daten',
					'position' => 1,
					'fields'   => [
						[ 'field_key' => 'email', 'is_visible' => true, 'is_required' => true ],
					],
				],
				[
					'id'        => 'step_finale',
					'title'     => 'Abschluss',
					'position'  => 999,
					'is_finale' => true,
					'fields'    => [
						[ 'field_key' => 'privacy_consent', 'is_visible' => true, 'is_required' => true ],
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
			'version' => 1,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Schritt 1',
					'fields' => [
						[ 'field_key' => 'first_name', 'is_visible' => true ],
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

		$result = $this->service->saveDraft( $config );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_email_field', $result->get_error_code() );
	}

	/**
	 * Test: saveDraft validiert fehlendes Privacy-Consent-Feld
	 */
	public function test_save_draft_validates_missing_privacy_consent(): void {
		$config = [
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
					'fields'    => [],
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
	 * Test: getAvailableFields lädt System-Felder
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
			->shouldReceive( 'findSystemFields' )
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
				'version' => 1,
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
			->shouldReceive( 'findSystemFields' )
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
}
