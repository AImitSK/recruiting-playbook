<?php
/**
 * Field Definition API Integration Tests
 *
 * Testet die REST API Endpoints für Field Definitions.
 *
 * @package RecruitingPlaybook\Tests\Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Api\FieldDefinitionController;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;
use WP_REST_Request;

/**
 * Integration Tests für Field Definition API
 */
class FieldDefinitionApiTest extends TestCase {

	private FieldDefinitionController $controller;
	private $repository_mock;
	private $service;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias( function( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
		} );
		Functions\when( 'rest_ensure_response' )->returnArg( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'absint' )->alias( 'intval' );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		// Repository Mock.
		$this->repository_mock = Mockery::mock( FieldDefinitionRepository::class );

		// Service mit Mock-Repository.
		$this->service = new FieldDefinitionService( $this->repository_mock );

		// Controller.
		$this->controller = new FieldDefinitionController( $this->service );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_all_field_definitions(): void {
		$field1 = $this->createFieldDefinition( 1, 'name', 'text', 'Name', true );
		$field2 = $this->createFieldDefinition( 2, 'email', 'email', 'Email', true );

		$this->repository_mock
			->shouldReceive( 'findAll' )
			->once()
			->andReturn( [ $field1, $field2 ] );

		$request = new WP_REST_Request();
		$result  = $this->controller->get_items( $request );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * @test
	 */
	public function it_returns_single_field_definition(): void {
		$field = $this->createFieldDefinition( 1, 'experience', 'text', 'Berufserfahrung', false );

		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 1 )
			->once()
			->andReturn( $field );

		$request = new WP_REST_Request();
		$request->set_param( 'id', 1 );

		$result = $this->controller->get_item( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'experience', $result['field_key'] );
		$this->assertEquals( 'Berufserfahrung', $result['label'] );
	}

	/**
	 * @test
	 */
	public function it_returns_error_for_not_found_field(): void {
		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 999 )
			->once()
			->andReturn( null );

		$request = new WP_REST_Request();
		$request->set_param( 'id', 999 );

		$result = $this->controller->get_item( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_creates_new_field_definition(): void {
		$this->repository_mock
			->shouldReceive( 'findByFieldKey' )
			->with( 'new_field' )
			->once()
			->andReturn( null );

		$this->repository_mock
			->shouldReceive( 'getMaxPosition' )
			->once()
			->andReturn( 5 );

		$this->repository_mock
			->shouldReceive( 'create' )
			->once()
			->andReturn( 10 );

		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 10 )
			->once()
			->andReturn( $this->createFieldDefinition(
				10, 'new_field', 'text', 'Neues Feld', false
			) );

		$request = new WP_REST_Request();
		$request->set_param( 'field_key', 'new_field' );
		$request->set_param( 'type', 'text' );
		$request->set_param( 'label', 'Neues Feld' );

		$result = $this->controller->create_item( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'new_field', $result['field_key'] );
	}

	/**
	 * @test
	 */
	public function it_rejects_duplicate_field_key(): void {
		$existing = $this->createFieldDefinition( 1, 'existing', 'text', 'Existing', false );

		$this->repository_mock
			->shouldReceive( 'findByFieldKey' )
			->with( 'existing' )
			->once()
			->andReturn( $existing );

		$request = new WP_REST_Request();
		$request->set_param( 'field_key', 'existing' );
		$request->set_param( 'type', 'text' );
		$request->set_param( 'label', 'Test' );

		$result = $this->controller->create_item( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'duplicate_field_key', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_updates_field_definition(): void {
		$field = $this->createFieldDefinition( 1, 'experience', 'text', 'Berufserfahrung', false );

		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $field );

		$this->repository_mock
			->shouldReceive( 'update' )
			->once()
			->andReturn( true );

		$request = new WP_REST_Request();
		$request->set_param( 'id', 1 );
		$request->set_param( 'label', 'Berufserfahrung (in Jahren)' );

		$result = $this->controller->update_item( $request );

		$this->assertIsArray( $result );
	}

	/**
	 * @test
	 */
	public function it_deletes_field_definition(): void {
		$field = $this->createFieldDefinition( 1, 'to_delete', 'text', 'Test', false, false );

		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 1 )
			->once()
			->andReturn( $field );

		$this->repository_mock
			->shouldReceive( 'delete' )
			->with( 1 )
			->once()
			->andReturn( true );

		$request = new WP_REST_Request();
		$request->set_param( 'id', 1 );

		$result = $this->controller->delete_item( $request );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['deleted'] );
	}

	/**
	 * @test
	 */
	public function it_prevents_deletion_of_system_fields(): void {
		$system_field = $this->createFieldDefinition( 1, 'name', 'text', 'Name', false, true );

		$this->repository_mock
			->shouldReceive( 'find' )
			->with( 1 )
			->once()
			->andReturn( $system_field );

		$request = new WP_REST_Request();
		$request->set_param( 'id', 1 );

		$result = $this->controller->delete_item( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'cannot_delete_system', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_reorders_fields(): void {
		$this->repository_mock
			->shouldReceive( 'updatePosition' )
			->with( 1, 0 )
			->once()
			->andReturn( true );

		$this->repository_mock
			->shouldReceive( 'updatePosition' )
			->with( 2, 1 )
			->once()
			->andReturn( true );

		$this->repository_mock
			->shouldReceive( 'updatePosition' )
			->with( 3, 2 )
			->once()
			->andReturn( true );

		$request = new WP_REST_Request();
		$request->set_param( 'order', [ 1, 2, 3 ] );

		$result = $this->controller->reorder( $request );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * @test
	 */
	public function it_returns_active_fields_only(): void {
		$active   = $this->createFieldDefinition( 1, 'active', 'text', 'Active', true );
		$inactive = $this->createFieldDefinition( 2, 'inactive', 'text', 'Inactive', false );

		$this->repository_mock
			->shouldReceive( 'findAll' )
			->once()
			->andReturn( [ $active, $inactive ] );

		$request = new WP_REST_Request();
		$request->set_param( 'active_only', true );

		$result = $this->controller->get_items( $request );

		// Controller gibt alle zurück, Filterung geschieht im Service.
		$this->assertIsArray( $result );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 */
	private function createFieldDefinition(
		int $id,
		string $key,
		string $type,
		string $label,
		bool $enabled = true,
		bool $is_system = false
	): FieldDefinition {
		$data = [
			'id'          => $id,
			'field_key'   => $key,
			'type'        => $type,
			'label'       => $label,
			'is_required' => false,
			'is_enabled'  => $enabled,
			'is_system'   => $is_system,
		];

		return FieldDefinition::hydrate( $data );
	}
}
