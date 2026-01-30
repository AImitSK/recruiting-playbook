<?php
/**
 * FieldDefinitionService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Tests für den FieldDefinitionService
 */
class FieldDefinitionServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var FieldDefinitionService
	 */
	private FieldDefinitionService $service;

	/**
	 * Mock Repository
	 *
	 * @var FieldDefinitionRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = Mockery::mock( FieldDefinitionRepository::class );
		$this->service    = new FieldDefinitionService( $this->repository );

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'get_post_meta' )->justReturn( null );
	}

	/**
	 * Test: get lädt Feld-Definition
	 */
	public function test_get_loads_field_definition(): void {
		$field = new FieldDefinition( [
			'id'         => 1,
			'field_key'  => 'test',
			'field_type' => 'text',
			'label'      => 'Test',
		] );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $field );

		$result = $this->service->get( 1 );

		$this->assertInstanceOf( FieldDefinition::class, $result );
		$this->assertEquals( 1, $result->getId() );
	}

	/**
	 * Test: get gibt null zurück wenn nicht gefunden
	 */
	public function test_get_returns_null_when_not_found(): void {
		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->get( 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test: isValidFieldKey validiert korrekt
	 */
	public function test_is_valid_field_key(): void {
		// Gültige Keys.
		$this->assertTrue( $this->service->isValidFieldKey( 'field_name' ) );
		$this->assertTrue( $this->service->isValidFieldKey( 'custom123' ) );
		$this->assertTrue( $this->service->isValidFieldKey( 'ab' ) );
		$this->assertTrue( $this->service->isValidFieldKey( 'my_custom_field_name' ) );

		// Ungültige Keys.
		$this->assertFalse( $this->service->isValidFieldKey( 'a' ) ); // Zu kurz.
		$this->assertFalse( $this->service->isValidFieldKey( '1field' ) ); // Startet mit Zahl.
		$this->assertFalse( $this->service->isValidFieldKey( 'Field' ) ); // Großbuchstaben.
		$this->assertFalse( $this->service->isValidFieldKey( 'field-name' ) ); // Bindestrich.
		$this->assertFalse( $this->service->isValidFieldKey( 'field name' ) ); // Leerzeichen.
		$this->assertFalse( $this->service->isValidFieldKey( '_field' ) ); // Startet mit Unterstrich.
	}

	/**
	 * Test: isValidFieldType validiert korrekt
	 */
	public function test_is_valid_field_type(): void {
		// Gültige Typen.
		$this->assertTrue( $this->service->isValidFieldType( 'text' ) );
		$this->assertTrue( $this->service->isValidFieldType( 'email' ) );
		$this->assertTrue( $this->service->isValidFieldType( 'select' ) );
		$this->assertTrue( $this->service->isValidFieldType( 'file' ) );
		$this->assertTrue( $this->service->isValidFieldType( 'checkbox' ) );

		// Ungültige Typen.
		$this->assertFalse( $this->service->isValidFieldType( 'invalid' ) );
		$this->assertFalse( $this->service->isValidFieldType( 'password' ) );
		$this->assertFalse( $this->service->isValidFieldType( '' ) );
	}

	/**
	 * Test: getAvailableFieldTypes gibt alle Typen zurück
	 */
	public function test_get_available_field_types(): void {
		$types = $this->service->getAvailableFieldTypes();

		$this->assertIsArray( $types );
		$this->assertCount( 12, $types ); // 12 Feldtypen.

		// Prüfe Struktur.
		$first = $types[0];
		$this->assertArrayHasKey( 'type', $first );
		$this->assertArrayHasKey( 'label', $first );
		$this->assertArrayHasKey( 'icon', $first );
		$this->assertArrayHasKey( 'group', $first );

		// Prüfe dass text, email, select vorhanden sind.
		$type_list = array_column( $types, 'type' );
		$this->assertContains( 'text', $type_list );
		$this->assertContains( 'email', $type_list );
		$this->assertContains( 'select', $type_list );
		$this->assertContains( 'file', $type_list );
	}

	/**
	 * Test: create validiert fehlende Pflichtfelder
	 */
	public function test_create_validates_required_fields(): void {
		// Fehlender field_key.
		$result = $this->service->create( [
			'field_type' => 'text',
			'label'      => 'Test',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_field_key', $result->get_error_code() );

		// Fehlender field_type.
		$result = $this->service->create( [
			'field_key' => 'test',
			'label'     => 'Test',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_field_type', $result->get_error_code() );

		// Fehlender label.
		$result = $this->service->create( [
			'field_key'  => 'test',
			'field_type' => 'text',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_label', $result->get_error_code() );
	}

	/**
	 * Test: create validiert ungültigen field_key
	 */
	public function test_create_validates_invalid_field_key(): void {
		$result = $this->service->create( [
			'field_key'  => 'Invalid-Key',
			'field_type' => 'text',
			'label'      => 'Test',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_field_key', $result->get_error_code() );
	}

	/**
	 * Test: create validiert ungültigen field_type
	 */
	public function test_create_validates_invalid_field_type(): void {
		$result = $this->service->create( [
			'field_key'  => 'test_field',
			'field_type' => 'invalid_type',
			'label'      => 'Test',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_field_type', $result->get_error_code() );
	}

	/**
	 * Test: create prüft auf Duplikate
	 */
	public function test_create_checks_for_duplicates(): void {
		$this->repository
			->shouldReceive( 'fieldKeyExists' )
			->once()
			->andReturn( true );

		$result = $this->service->create( [
			'field_key'  => 'existing_field',
			'field_type' => 'text',
			'label'      => 'Test',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'duplicate_field_key', $result->get_error_code() );
	}

	/**
	 * Test: create erstellt Feld erfolgreich
	 */
	public function test_create_success(): void {
		$field = new FieldDefinition( [
			'id'         => 1,
			'field_key'  => 'new_field',
			'field_type' => 'text',
			'label'      => 'Neues Feld',
		] );

		$this->repository
			->shouldReceive( 'fieldKeyExists' )
			->once()
			->andReturn( false );

		$this->repository
			->shouldReceive( 'getNextPosition' )
			->once()
			->andReturn( 10 );

		$this->repository
			->shouldReceive( 'create' )
			->once()
			->andReturn( $field );

		$result = $this->service->create( [
			'field_key'  => 'new_field',
			'field_type' => 'text',
			'label'      => 'Neues Feld',
		] );

		$this->assertInstanceOf( FieldDefinition::class, $result );
		$this->assertEquals( 1, $result->getId() );
	}

	/**
	 * Test: delete verhindert Löschen von System-Feldern
	 */
	public function test_delete_prevents_deleting_system_fields(): void {
		$system_field = new FieldDefinition( [
			'id'         => 1,
			'field_key'  => 'email',
			'field_type' => 'email',
			'label'      => 'E-Mail',
			'is_system'  => 1,
		] );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $system_field );

		$result = $this->service->delete( 1 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'cannot_delete_system_field', $result->get_error_code() );
	}

	/**
	 * Test: delete gibt Fehler zurück wenn nicht gefunden
	 */
	public function test_delete_returns_error_when_not_found(): void {
		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->delete( 999 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: delete löscht erfolgreich
	 */
	public function test_delete_success(): void {
		$field = new FieldDefinition( [
			'id'         => 5,
			'field_key'  => 'custom',
			'field_type' => 'text',
			'label'      => 'Custom',
			'is_system'  => 0,
		] );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 5 )
			->andReturn( $field );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( 5 )
			->andReturn( true );

		$result = $this->service->delete( 5 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: update schützt System-Feld-Eigenschaften
	 */
	public function test_update_protects_system_field_properties(): void {
		$system_field = new FieldDefinition( [
			'id'         => 1,
			'field_key'  => 'email',
			'field_type' => 'email',
			'label'      => 'E-Mail',
			'is_system'  => 1,
		] );

		$updated_field = new FieldDefinition( [
			'id'         => 1,
			'field_key'  => 'email', // Unverändert.
			'field_type' => 'email', // Unverändert.
			'label'      => 'Neue E-Mail Adresse', // Geändert.
			'is_system'  => 1,
		] );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $system_field );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( 1, Mockery::on( function( $data ) {
				// field_key und field_type sollten nicht in den Update-Daten sein.
				return ! isset( $data['field_key'] ) && ! isset( $data['field_type'] );
			} ) )
			->andReturn( $updated_field );

		$result = $this->service->update( 1, [
			'field_key'  => 'new_email', // Sollte ignoriert werden.
			'field_type' => 'text', // Sollte ignoriert werden.
			'label'      => 'Neue E-Mail Adresse',
		] );

		$this->assertInstanceOf( FieldDefinition::class, $result );
	}

	/**
	 * Test: reorder funktioniert korrekt
	 */
	public function test_reorder_works_correctly(): void {
		$positions = [ 1 => 0, 2 => 1, 3 => 2 ];

		$this->repository
			->shouldReceive( 'reorder' )
			->once()
			->with( $positions )
			->andReturn( true );

		$result = $this->service->reorder( $positions );

		$this->assertTrue( $result );
	}

	/**
	 * Test: reorder gibt Fehler bei leeren Positionen
	 */
	public function test_reorder_returns_error_for_empty_positions(): void {
		$result = $this->service->reorder( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'empty_positions', $result->get_error_code() );
	}

	/**
	 * Test: getSystemFields lädt System-Felder
	 */
	public function test_get_system_fields(): void {
		$system_fields = [
			new FieldDefinition( [ 'field_key' => 'first_name', 'field_type' => 'text', 'label' => 'Vorname', 'is_system' => 1 ] ),
			new FieldDefinition( [ 'field_key' => 'email', 'field_type' => 'email', 'label' => 'E-Mail', 'is_system' => 1 ] ),
		];

		$this->repository
			->shouldReceive( 'findSystemFields' )
			->once()
			->andReturn( $system_fields );

		$result = $this->service->getSystemFields();

		$this->assertCount( 2, $result );
		$this->assertEquals( 'first_name', $result[0]->getFieldKey() );
	}
}
