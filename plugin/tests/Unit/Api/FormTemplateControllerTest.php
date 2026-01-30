<?php
/**
 * FormTemplateController Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Api
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Api\FormTemplateController;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Tests für FormTemplateController
 */
class FormTemplateControllerTest extends TestCase {

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
		$controller = new FormTemplateController();

		$this->assertInstanceOf( FormTemplateController::class, $controller );
	}

	/**
	 * @test
	 */
	public function it_registers_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 5 ); // 5 route registrations.

		$controller = new FormTemplateController();
		$controller->register_routes();

		$this->assertTrue( true ); // If we get here, routes were registered.
	}

	/**
	 * @test
	 */
	public function it_denies_access_without_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FormTemplateController();
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

		$controller = new FormTemplateController();
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

		$controller = new FormTemplateController();
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

		$controller = new FormTemplateController();
		$request    = $this->createMock( WP_REST_Request::class );

		$result = $controller->get_items_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_has_item_schema(): void {
		$controller = new FormTemplateController();

		$schema = $controller->get_item_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'description', $schema['properties'] );
		$this->assertArrayHasKey( 'is_default', $schema['properties'] );
	}

	/**
	 * @test
	 */
	public function it_has_collection_params(): void {
		$controller = new FormTemplateController();

		$params = $controller->get_collection_params();

		$this->assertArrayHasKey( 'include_fields', $params );
		$this->assertEquals( 'boolean', $params['include_fields']['type'] );
		$this->assertFalse( $params['include_fields']['default'] );
	}

	/**
	 * @test
	 */
	public function create_permissions_match_get_permissions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FormTemplateController();
		$request    = $this->createMock( WP_REST_Request::class );

		$get_result    = $controller->get_items_permissions_check( $request );
		$create_result = $controller->create_item_permissions_check( $request );

		// Beide sollten WP_Error sein, da keine Capability.
		$this->assertInstanceOf( WP_Error::class, $get_result );
		$this->assertInstanceOf( WP_Error::class, $create_result );
	}

	/**
	 * @test
	 */
	public function update_permissions_match_create_permissions(): void {
		Functions\when( 'current_user_can' )->alias( function( $cap ) {
			return $cap === 'rp_manage_forms';
		});
		Functions\when( 'function_exists' )->justReturn( false );

		$controller = new FormTemplateController();
		$request    = $this->createMock( WP_REST_Request::class );

		$create_result = $controller->create_item_permissions_check( $request );
		$update_result = $controller->update_item_permissions_check( $request );
		$delete_result = $controller->delete_item_permissions_check( $request );

		$this->assertTrue( $create_result );
		$this->assertTrue( $update_result );
		$this->assertTrue( $delete_result );
	}
}
