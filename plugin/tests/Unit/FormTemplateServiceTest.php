<?php
/**
 * FormTemplateService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\FormTemplateService;
use RecruitingPlaybook\Repositories\FormTemplateRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FormTemplate;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Tests für den FormTemplateService
 */
class FormTemplateServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var FormTemplateService
	 */
	private FormTemplateService $service;

	/**
	 * Mock Template Repository
	 *
	 * @var FormTemplateRepository|Mockery\MockInterface
	 */
	private $template_repository;

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

		$this->template_repository = Mockery::mock( FormTemplateRepository::class );
		$this->field_repository    = Mockery::mock( FieldDefinitionRepository::class );
		$this->service             = new FormTemplateService(
			$this->template_repository,
			$this->field_repository
		);

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
	}

	/**
	 * Test: get lädt Template
	 */
	public function test_get_loads_template(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Standard',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$result = $this->service->get( 1 );

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertEquals( 1, $result->getId() );
	}

	/**
	 * Test: get mit Feldern lädt auch Felder
	 */
	public function test_get_with_fields_loads_fields(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Standard',
		] );

		$fields = [
			new FieldDefinition( [ 'field_key' => 'name', 'field_type' => 'text', 'label' => 'Name' ] ),
		];

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$this->field_repository
			->shouldReceive( 'findByTemplate' )
			->once()
			->with( 1 )
			->andReturn( $fields );

		$result = $this->service->get( 1, true );

		$this->assertCount( 1, $result->getFields() );
	}

	/**
	 * Test: get gibt null zurück wenn nicht gefunden
	 */
	public function test_get_returns_null_when_not_found(): void {
		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->get( 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test: getDefault lädt Standard-Template
	 */
	public function test_get_default_loads_default_template(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Standard',
			'is_default' => 1,
		] );

		$this->template_repository
			->shouldReceive( 'findDefault' )
			->once()
			->andReturn( $template );

		$result = $this->service->getDefault();

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertTrue( $result->isDefault() );
	}

	/**
	 * Test: getAll lädt alle Templates
	 */
	public function test_get_all_loads_all_templates(): void {
		$templates = [
			new FormTemplate( [ 'id' => 1, 'name' => 'Standard', 'is_default' => 1 ] ),
			new FormTemplate( [ 'id' => 2, 'name' => 'Minimal' ] ),
		];

		$this->template_repository
			->shouldReceive( 'findAll' )
			->once()
			->with( true )
			->andReturn( $templates );

		$result = $this->service->getAll();

		$this->assertCount( 2, $result );
	}

	/**
	 * Test: create validiert fehlenden Namen
	 */
	public function test_create_validates_missing_name(): void {
		$result = $this->service->create( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_name', $result->get_error_code() );
	}

	/**
	 * Test: create validiert zu langen Namen
	 */
	public function test_create_validates_name_too_long(): void {
		$result = $this->service->create( [
			'name' => str_repeat( 'a', 300 ),
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'name_too_long', $result->get_error_code() );
	}

	/**
	 * Test: create prüft auf Duplikate
	 */
	public function test_create_checks_for_duplicate_name(): void {
		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->with( 'Bestehendes Template' )
			->andReturn( true );

		$result = $this->service->create( [
			'name' => 'Bestehendes Template',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'duplicate_name', $result->get_error_code() );
	}

	/**
	 * Test: create erstellt Template erfolgreich
	 */
	public function test_create_success(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Neues Template',
		] );

		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->andReturn( false );

		$this->template_repository
			->shouldReceive( 'create' )
			->once()
			->andReturn( $template );

		$result = $this->service->create( [
			'name' => 'Neues Template',
		] );

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertEquals( 'Neues Template', $result->getName() );
	}

	/**
	 * Test: delete gibt Fehler zurück wenn nicht gefunden
	 */
	public function test_delete_returns_error_when_not_found(): void {
		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->delete( 999 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: delete verhindert Löschen wenn in Verwendung
	 */
	public function test_delete_prevents_when_in_use(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'In Verwendung',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$this->template_repository
			->shouldReceive( 'getUsageCount' )
			->once()
			->with( 1 )
			->andReturn( 3 );

		$result = $this->service->delete( 1 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'template_in_use', $result->get_error_code() );
	}

	/**
	 * Test: delete verhindert Löschen von Standard-Template wenn andere existieren
	 */
	public function test_delete_prevents_default_when_others_exist(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Standard',
			'is_default' => 1,
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$this->template_repository
			->shouldReceive( 'getUsageCount' )
			->once()
			->andReturn( 0 );

		$this->template_repository
			->shouldReceive( 'findAll' )
			->once()
			->andReturn( [
				$template,
				new FormTemplate( [ 'id' => 2, 'name' => 'Anderes' ] ),
			] );

		$result = $this->service->delete( 1 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'cannot_delete_default', $result->get_error_code() );
	}

	/**
	 * Test: delete löscht erfolgreich
	 */
	public function test_delete_success(): void {
		$template = new FormTemplate( [
			'id'   => 2,
			'name' => 'Zum Löschen',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 2 )
			->andReturn( $template );

		$this->template_repository
			->shouldReceive( 'getUsageCount' )
			->once()
			->with( 2 )
			->andReturn( 0 );

		$this->template_repository
			->shouldReceive( 'delete' )
			->once()
			->with( 2 )
			->andReturn( true );

		$result = $this->service->delete( 2 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: duplicate erstellt Kopie
	 */
	public function test_duplicate_creates_copy(): void {
		$original = new FormTemplate( [
			'id'   => 1,
			'name' => 'Original',
		] );

		$copy = new FormTemplate( [
			'id'   => 2,
			'name' => 'Original (Kopie)',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $original );

		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->andReturn( false );

		$this->template_repository
			->shouldReceive( 'duplicate' )
			->once()
			->andReturn( $copy );

		$result = $this->service->duplicate( 1 );

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertEquals( 2, $result->getId() );
	}

	/**
	 * Test: duplicate mit eigenem Namen
	 */
	public function test_duplicate_with_custom_name(): void {
		$original = new FormTemplate( [
			'id'   => 1,
			'name' => 'Original',
		] );

		$copy = new FormTemplate( [
			'id'   => 2,
			'name' => 'Meine Kopie',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $original );

		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->with( 'Meine Kopie' )
			->andReturn( false );

		$this->template_repository
			->shouldReceive( 'duplicate' )
			->once()
			->with( 1, 'Meine Kopie' )
			->andReturn( $copy );

		$result = $this->service->duplicate( 1, 'Meine Kopie' );

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertEquals( 'Meine Kopie', $result->getName() );
	}

	/**
	 * Test: setDefault setzt Template als Standard
	 */
	public function test_set_default(): void {
		$template = new FormTemplate( [
			'id'         => 2,
			'name'       => 'Neuer Standard',
			'is_default' => 0,
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 2 )
			->andReturn( $template );

		$this->template_repository
			->shouldReceive( 'setDefault' )
			->once()
			->with( 2 )
			->andReturn( true );

		$result = $this->service->setDefault( 2 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: setDefault gibt true zurück wenn bereits Standard
	 */
	public function test_set_default_already_default(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Standard',
			'is_default' => 1,
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$result = $this->service->setDefault( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: getUsageCount gibt Anzahl zurück
	 */
	public function test_get_usage_count(): void {
		$this->template_repository
			->shouldReceive( 'getUsageCount' )
			->once()
			->with( 1 )
			->andReturn( 5 );

		$result = $this->service->getUsageCount( 1 );

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test: update gibt Fehler zurück wenn nicht gefunden
	 */
	public function test_update_returns_error_when_not_found(): void {
		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->update( 999, [ 'name' => 'Neuer Name' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: update prüft auf Duplikat-Namen
	 */
	public function test_update_checks_duplicate_name(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Original',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $template );

		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->with( 'Anderes Template', 1 )
			->andReturn( true );

		$result = $this->service->update( 1, [ 'name' => 'Anderes Template' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'duplicate_name', $result->get_error_code() );
	}

	/**
	 * Test: update aktualisiert erfolgreich
	 */
	public function test_update_success(): void {
		$original = new FormTemplate( [
			'id'   => 1,
			'name' => 'Original',
		] );

		$updated = new FormTemplate( [
			'id'   => 1,
			'name' => 'Neuer Name',
		] );

		$this->template_repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $original );

		$this->template_repository
			->shouldReceive( 'nameExists' )
			->once()
			->andReturn( false );

		$this->template_repository
			->shouldReceive( 'update' )
			->once()
			->andReturn( $updated );

		$result = $this->service->update( 1, [ 'name' => 'Neuer Name' ] );

		$this->assertInstanceOf( FormTemplate::class, $result );
		$this->assertEquals( 'Neuer Name', $result->getName() );
	}
}
