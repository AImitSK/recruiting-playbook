<?php
/**
 * FormConfigController Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Api;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Api\FormConfigController;
use RecruitingPlaybook\Services\FormConfigService;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;
use WP_REST_Request;

/**
 * Tests für den FormConfigController
 */
class FormConfigControllerTest extends TestCase {

	/**
	 * Controller under test
	 *
	 * @var FormConfigController
	 */
	private FormConfigController $controller;

	/**
	 * Mock Service
	 *
	 * @var FormConfigService|Mockery\MockInterface
	 */
	private $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'register_rest_route' )->justReturn( true );

		// rp_can für Feature Gate.
		Functions\when( 'rp_can' )->justReturn( true );

		$this->controller = new FormConfigController();
	}

	/**
	 * Test: get_config_permissions_check gibt true zurück wenn Benutzer berechtigt
	 */
	public function test_get_config_permissions_check_allows_admin(): void {
		Functions\expect( 'current_user_can' )
			->twice()
			->andReturn( true );

		$request = Mockery::mock( WP_REST_Request::class );

		$result = $this->controller->get_config_permissions_check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test: get_config_permissions_check verweigert Zugriff für nicht-berechtigte Benutzer
	 */
	public function test_get_config_permissions_check_denies_unauthorized(): void {
		Functions\expect( 'current_user_can' )
			->twice()
			->andReturn( false );

		$request = Mockery::mock( WP_REST_Request::class );

		$result = $this->controller->get_config_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test: edit_config_permissions_check gibt true zurück wenn Benutzer berechtigt
	 */
	public function test_edit_config_permissions_check_allows_admin(): void {
		Functions\expect( 'current_user_can' )
			->twice()
			->andReturn( true );

		$request = Mockery::mock( WP_REST_Request::class );

		$result = $this->controller->edit_config_permissions_check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test: register_routes registriert alle Routen
	 */
	public function test_register_routes_registers_all_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 5 )  // 4 + active-fields endpoint
			->andReturn( true );

		$this->controller->register_routes();

		// Wenn wir hier ankommen ohne Exception, wurden die Routen registriert.
		$this->assertTrue( true );
	}

	/**
	 * Test: get_config gibt Builder-Daten zurück
	 */
	public function test_get_config_returns_builder_data(): void {
		// Service mocken durch Reflection.
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'getBuilderData' )
			->once()
			->andReturn( [
				'draft'             => [ 'version' => 1, 'steps' => [] ],
				'published_version' => 1,
				'has_changes'       => false,
				'available_fields'  => [],
			] );

		// Service in Controller injizieren via Reflection.
		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->get_config( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'draft', $data );
		$this->assertArrayHasKey( 'published_version', $data );
	}

	/**
	 * Test: save_config speichert Draft erfolgreich
	 */
	public function test_save_config_saves_draft(): void {
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'saveDraft' )
			->once()
			->andReturn( true );
		$service->shouldReceive( 'hasUnpublishedChanges' )
			->once()
			->andReturn( true );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_param' )
			->with( 'steps' )
			->andReturn( [ [ 'id' => 'step_1', 'title' => 'Test' ] ] );
		$request->shouldReceive( 'get_param' )
			->with( 'settings' )
			->andReturn( [] );
		$request->shouldReceive( 'get_param' )
			->with( 'version' )
			->andReturn( 1 );

		$response = $this->controller->save_config( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['has_changes'] );
	}

	/**
	 * Test: save_config gibt Fehler zurück bei Validierungsproblemen
	 */
	public function test_save_config_returns_error_on_validation_failure(): void {
		$error = new WP_Error( 'missing_steps', 'Steps fehlen' );

		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'saveDraft' )
			->once()
			->andReturn( $error );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_param' )
			->with( 'steps' )
			->andReturn( [] );
		$request->shouldReceive( 'get_param' )
			->with( 'settings' )
			->andReturn( [] );
		$request->shouldReceive( 'get_param' )
			->with( 'version' )
			->andReturn( 1 );

		$response = $this->controller->save_config( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'missing_steps', $response->get_error_code() );
	}

	/**
	 * Test: publish_config veröffentlicht Draft erfolgreich
	 */
	public function test_publish_config_publishes_draft(): void {
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'publish' )
			->once()
			->andReturn( true );
		$service->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 2 );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->publish_config( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 2, $data['published_version'] );
		$this->assertFalse( $data['has_changes'] );
	}

	/**
	 * Test: publish_config gibt Fehler zurück wenn keine Änderungen
	 */
	public function test_publish_config_returns_error_when_no_changes(): void {
		$error = new WP_Error( 'no_changes', 'Keine Änderungen' );

		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'publish' )
			->once()
			->andReturn( $error );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->publish_config( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'no_changes', $response->get_error_code() );
	}

	/**
	 * Test: discard_config verwirft Draft erfolgreich
	 */
	public function test_discard_config_discards_draft(): void {
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'discardDraft' )
			->once()
			->andReturn( true );
		$service->shouldReceive( 'getDraft' )
			->once()
			->andReturn( [ 'version' => 1, 'steps' => [] ] );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->discard_config( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertFalse( $data['has_changes'] );
		$this->assertArrayHasKey( 'draft', $data );
	}

	/**
	 * Test: get_published gibt veröffentlichte Konfiguration zurück
	 */
	public function test_get_published_returns_published_config(): void {
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( [ 'version' => 2, 'steps' => [ [ 'id' => 'step_1' ] ] ] );
		$service->shouldReceive( 'getPublishedVersion' )
			->once()
			->andReturn( 2 );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->get_published( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'config', $data );
		$this->assertEquals( 2, $data['version'] );
	}

	/**
	 * Test: get_active_fields gibt aktive Felder zurück
	 */
	public function test_get_active_fields_returns_field_list(): void {
		$activeFields = [
			'fields'        => [
				[ 'field_key' => 'first_name', 'field_type' => 'text', 'label' => 'Vorname', 'is_required' => true ],
				[ 'field_key' => 'email', 'field_type' => 'email', 'label' => 'E-Mail', 'is_required' => true ],
			],
			'system_fields' => [
				[ 'field_key' => 'file_upload', 'type' => 'file_upload', 'label' => 'Dokumente' ],
				[ 'field_key' => 'privacy_consent', 'type' => 'privacy_consent', 'label' => 'Datenschutz' ],
			],
		];

		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'getActiveFields' )
			->once()
			->andReturn( $activeFields );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->get_active_fields( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'fields', $data );
		$this->assertArrayHasKey( 'system_fields', $data );
		$this->assertCount( 2, $data['fields'] );
		$this->assertCount( 2, $data['system_fields'] );
		$this->assertEquals( 'first_name', $data['fields'][0]['field_key'] );
	}

	/**
	 * Test: get_active_fields gibt leere Arrays zurück wenn keine Config
	 */
	public function test_get_active_fields_returns_empty_when_no_config(): void {
		$service = Mockery::mock( FormConfigService::class );
		$service->shouldReceive( 'getActiveFields' )
			->once()
			->andReturn( [
				'fields'        => [],
				'system_fields' => [],
			] );

		$reflection = new \ReflectionClass( $this->controller );
		$property   = $reflection->getProperty( 'service' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $service );

		$request = Mockery::mock( WP_REST_Request::class );

		$response = $this->controller->get_active_fields( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'fields', $data );
		$this->assertArrayHasKey( 'system_fields', $data );
		$this->assertEmpty( $data['fields'] );
		$this->assertEmpty( $data['system_fields'] );
	}
}
