<?php
/**
 * CustomFieldsService Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\CustomFieldsService;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Services\FormValidationService;
use RecruitingPlaybook\Services\CustomFieldFileService;
use RecruitingPlaybook\Services\ConditionalLogicService;
use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\FieldTypes\TextField;
use RecruitingPlaybook\FieldTypes\EmailField;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Tests für CustomFieldsService
 */
class CustomFieldsServiceTest extends TestCase {

	private CustomFieldsService $service;
	private $field_service_mock;
	private $validation_service_mock;
	private $file_service_mock;
	private $conditional_service_mock;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-01 12:00:00' );

		// Mock Services erstellen.
		$this->field_service_mock       = Mockery::mock( FieldDefinitionService::class );
		$this->validation_service_mock  = Mockery::mock( FormValidationService::class );
		$this->file_service_mock        = Mockery::mock( CustomFieldFileService::class );
		$this->conditional_service_mock = Mockery::mock( ConditionalLogicService::class );

		// Conditional Service Standard-Verhalten.
		$this->conditional_service_mock
			->shouldReceive( 'isFieldVisible' )
			->andReturn( true )
			->byDefault();

		$this->service = new CustomFieldsService(
			$this->field_service_mock,
			$this->validation_service_mock,
			$this->file_service_mock,
			$this->conditional_service_mock
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_empty_array_when_no_fields(): void {
		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [] );

		$this->field_service_mock
			->shouldReceive( 'getActiveFields' )
			->andReturn( [] );

		$result = $this->service->processCustomFields( 1, 0, [] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @test
	 */
	public function it_filters_out_system_fields(): void {
		$system_field = $this->createFieldDefinition( 'name', 'text', true, true );
		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $system_field, $custom_field ] );

		$result = $this->service->processCustomFields( 1, 0, [ 'experience' => 'PHP Development' ] );

		$this->assertArrayHasKey( 'experience', $result );
		$this->assertArrayNotHasKey( 'name', $result );
	}

	/**
	 * @test
	 */
	public function it_filters_out_disabled_fields(): void {
		$enabled_field  = $this->createFieldDefinition( 'skills', 'text', true, false );
		$disabled_field = $this->createFieldDefinition( 'notes', 'text', false, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $enabled_field, $disabled_field ] );

		$result = $this->service->processCustomFields( 1, 0, [
			'skills' => 'PHP, JS',
			'notes'  => 'Some notes',
		] );

		$this->assertArrayHasKey( 'skills', $result );
		$this->assertArrayNotHasKey( 'notes', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_validation_error_for_required_empty_field(): void {
		$required_field = $this->createFieldDefinition( 'experience', 'text', true, false, true );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $required_field ] );

		$result = $this->service->processCustomFields( 1, 0, [ 'experience' => '' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'validation_failed', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_skips_hidden_fields_based_on_conditional_logic(): void {
		$visible_field = $this->createFieldDefinition( 'type', 'select', true, false );
		$hidden_field  = $this->createFieldDefinition( 'other_type', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $visible_field, $hidden_field ] );

		$this->conditional_service_mock
			->shouldReceive( 'isFieldVisible' )
			->with( $visible_field, Mockery::any() )
			->andReturn( true );

		$this->conditional_service_mock
			->shouldReceive( 'isFieldVisible' )
			->with( $hidden_field, Mockery::any() )
			->andReturn( false );

		$result = $this->service->processCustomFields( 1, 0, [
			'type'       => 'normal',
			'other_type' => 'something',
		] );

		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayNotHasKey( 'other_type', $result );
	}

	/**
	 * @test
	 */
	public function it_processes_file_uploads_when_application_id_provided(): void {
		$file_field = $this->createFieldDefinition( 'certificate', 'file', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $file_field ] );

		$this->file_service_mock
			->shouldReceive( 'processCustomFieldUploads' )
			->with( 123, Mockery::any(), Mockery::any() )
			->andReturn( [ 'certificate' => [ 456, 789 ] ] );

		$result = $this->service->processCustomFields( 1, 123, [], [ 'certificate' => [] ] );

		$this->assertArrayHasKey( 'certificate', $result );
		$this->assertEquals( [ 456, 789 ], $result['certificate'] );
	}

	/**
	 * @test
	 */
	public function it_returns_error_when_file_upload_fails(): void {
		$file_field = $this->createFieldDefinition( 'certificate', 'file', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $file_field ] );

		$this->file_service_mock
			->shouldReceive( 'processCustomFieldUploads' )
			->andReturn( new WP_Error( 'upload_failed', 'Upload error' ) );

		$result = $this->service->processCustomFields( 1, 123, [], [ 'certificate' => [] ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'upload_failed', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_saves_custom_fields_to_database(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_rp_applications',
				Mockery::on( function ( $data ) {
					return isset( $data['custom_fields'] ) && isset( $data['updated_at'] );
				} ),
				[ 'id' => 123 ],
				[ '%s', '%s' ],
				[ '%d' ]
			)
			->andReturn( 1 );

		$result = $this->service->saveCustomFields( 123, [ 'experience' => '5 years' ] );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_returns_false_when_save_fails(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'update' )
			->andReturn( false );

		$result = $this->service->saveCustomFields( 123, [ 'experience' => '5 years' ] );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function it_retrieves_custom_fields_from_database(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing( function ( $query, ...$args ) {
				return vsprintf( str_replace( [ '%s', '%d' ], [ "'%s'", '%d' ], $query ), $args );
			} );

		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{"experience":"5 years","skills":["PHP","JS"]}' );

		$result = $this->service->getCustomFields( 123 );

		$this->assertIsArray( $result );
		$this->assertEquals( '5 years', $result['experience'] );
		$this->assertEquals( [ 'PHP', 'JS' ], $result['skills'] );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_array_when_no_custom_fields_stored(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );

		$result = $this->service->getCustomFields( 123 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @test
	 */
	public function it_formats_custom_fields_for_display(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{"experience":"5 years"}' );

		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $custom_field ] );

		$result = $this->service->getFormattedCustomFields( 123, 1 );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'experience', $result[0]['key'] );
		$this->assertEquals( 'Experience', $result[0]['label'] );
		$this->assertEquals( '5 years', $result[0]['value'] );
		$this->assertEquals( 'text', $result[0]['type'] );
	}

	/**
	 * @test
	 */
	public function it_skips_system_fields_in_formatted_output(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{"name":"John","experience":"5 years"}' );

		$system_field = $this->createFieldDefinition( 'name', 'text', true, true );
		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $system_field, $custom_field ] );

		$result = $this->service->getFormattedCustomFields( 123, 1 );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'experience', $result[0]['key'] );
	}

	/**
	 * @test
	 */
	public function it_returns_export_values_with_labels(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{"experience":"5 years"}' );

		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $custom_field ] );

		$result = $this->service->getExportValues( 123, 1 );

		$this->assertArrayHasKey( 'Experience', $result );
		$this->assertEquals( '5 years', $result['Experience'] );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_string_for_missing_export_values(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{}' );

		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $custom_field ] );

		$result = $this->service->getExportValues( 123, 1 );

		$this->assertArrayHasKey( 'Experience', $result );
		$this->assertEquals( '', $result['Experience'] );
	}

	/**
	 * @test
	 */
	public function it_falls_back_to_active_fields_when_job_fields_empty(): void {
		$custom_field = $this->createFieldDefinition( 'experience', 'text', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [] );

		$this->field_service_mock
			->shouldReceive( 'getActiveFields' )
			->andReturn( [ $custom_field ] );

		$result = $this->service->processCustomFields( 1, 0, [ 'experience' => 'Test' ] );

		$this->assertArrayHasKey( 'experience', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_array_values_in_export(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( '{"skills":["PHP","JavaScript","Python"]}' );

		$custom_field = $this->createFieldDefinition( 'skills', 'checkbox', true, false );

		$this->field_service_mock
			->shouldReceive( 'getFieldsForJob' )
			->with( 1 )
			->andReturn( [ $custom_field ] );

		$result = $this->service->getExportValues( 123, 1 );

		$this->assertArrayHasKey( 'Skills', $result );
		$this->assertStringContainsString( 'PHP', $result['Skills'] );
		$this->assertStringContainsString( 'JavaScript', $result['Skills'] );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 *
	 * @param string $key        Feldschlüssel.
	 * @param string $type       Feldtyp.
	 * @param bool   $enabled    Aktiviert.
	 * @param bool   $is_system  System-Feld.
	 * @param bool   $required   Pflichtfeld.
	 * @return FieldDefinition
	 */
	private function createFieldDefinition(
		string $key,
		string $type,
		bool $enabled = true,
		bool $is_system = false,
		bool $required = false
	): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'type'        => $type,
			'label'       => ucfirst( str_replace( '_', ' ', $key ) ),
			'is_required' => $required,
			'is_enabled'  => $enabled,
			'is_system'   => $is_system,
		];

		return FieldDefinition::hydrate( $data );
	}
}
