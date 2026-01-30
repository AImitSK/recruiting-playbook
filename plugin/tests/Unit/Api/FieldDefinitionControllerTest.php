<?php
/**
 * FieldDefinitionController Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Api
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Api\FieldDefinitionController;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Tests für FieldDefinitionController
 */
class FieldDefinitionControllerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// WordPress Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'register_rest_route' )->justReturn( true );
		Functions\when( 'rest_url' )->alias( function( $path ) {
			return 'https://example.com/wp-json/' . $path;
		});
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_can_be_instantiated(): void {
		$controller = new FieldDefinitionController();

		$this->assertInstanceOf( FieldDefinitionController::class, $controller );
	}

	/**
	 * @test
	 */
	public function it_registers_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 7 ); // 7 route registrations.

		$controller = new FieldDefinitionController();
		$controller->register_routes();

		$this->assertTrue( true ); // If we get here, routes were registered.
	}

	/**
	 * @test
	 */
	public function it_denies_access_without_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FieldDefinitionController();
		$request    = $this->createMock( WP_REST_Request::class );

		$result = $controller->get_items_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_allows_access_with_capability(): void {
		Functions\when( 'current_user_can' )->alias( function( $cap ) {
			return $cap === 'rp_manage_forms';
		});
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FieldDefinitionController();
		$request    = $this->createMock( WP_REST_Request::class );

		$result = $controller->get_items_permissions_check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_allows_access_for_administrators(): void {
		Functions\when( 'current_user_can' )->alias( function( $cap ) {
			return $cap === 'manage_options';
		});
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FieldDefinitionController();
		$request    = $this->createMock( WP_REST_Request::class );

		$result = $controller->get_items_permissions_check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_denies_access_when_feature_is_gated(): void {
		Functions\when( 'function_exists' )->alias( function( $func ) {
			return $func === 'rp_can';
		});
		Functions\when( 'rp_can' )->alias( function( $feature ) {
			return false; // Feature nicht verfügbar.
		});

		$controller = new FieldDefinitionController();
		$request    = $this->createMock( WP_REST_Request::class );

		$result = $controller->get_items_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_returns_field_types(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FieldDefinitionController();
		$request    = $this->createMock( WP_REST_Request::class );

		$response = $controller->get_field_types( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'types', $data );
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'type_keys', $data );
	}

	/**
	 * @test
	 */
	public function it_has_item_schema(): void {
		$controller = new FieldDefinitionController();

		$schema = $controller->get_item_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'field_key', $schema['properties'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertArrayHasKey( 'label', $schema['properties'] );
	}

	/**
	 * @test
	 */
	public function it_has_collection_params(): void {
		$controller = new FieldDefinitionController();

		$params = $controller->get_collection_params();

		$this->assertArrayHasKey( 'template_id', $params );
		$this->assertArrayHasKey( 'type', $params );
		$this->assertArrayHasKey( 'is_system', $params );
	}
}
