<?php
/**
 * FileField Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\FieldTypes;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\FieldTypes\FileField;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Tests für FileField
 */
class FileFieldTest extends TestCase {

	private FileField $field_type;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( intval( $val ) );
		});
		Functions\when( 'wp_check_filetype' )->alias( function( $filename ) {
			$ext = pathinfo( $filename, PATHINFO_EXTENSION );
			return [ 'ext' => strtolower( $ext ), 'type' => '' ];
		});
		Functions\when( 'get_post_type' )->justReturn( 'attachment' );
		Functions\when( 'wp_get_attachment_url' )->alias( function( $id ) {
			return "https://example.com/uploads/{$id}/file.pdf";
		});
		Functions\when( 'get_attached_file' )->alias( function( $id ) {
			return "/var/www/uploads/{$id}/file.pdf";
		});
		Functions\when( 'size_format' )->alias( function( $bytes ) {
			return round( $bytes / 1024 ) . ' KB';
		});

		$this->field_type = new FileField();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_type(): void {
		$this->assertEquals( 'file', $this->field_type->getType() );
	}

	/**
	 * @test
	 */
	public function it_returns_special_group(): void {
		$this->assertEquals( 'special', $this->field_type->getGroup() );
	}

	/**
	 * @test
	 */
	public function it_is_file_upload(): void {
		$this->assertTrue( $this->field_type->isFileUpload() );
	}

	/**
	 * @test
	 */
	public function it_does_not_support_options(): void {
		$this->assertFalse( $this->field_type->supportsOptions() );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_attachment_ids(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->sanitize( [ '123', '456', '-1' ], $field );

		$this->assertEquals( [ 123, 456 ], $result );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_array_for_empty_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->sanitize( null, $field );

		$this->assertEquals( [], $result );
	}

	/**
	 * @test
	 */
	public function it_formats_export_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->formatExportValue( [ 1, 2 ], $field );

		$this->assertStringContainsString( 'https://example.com', $result );
	}

	/**
	 * @test
	 */
	public function it_renders_file_upload_zone(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'resume',
			'label'     => 'Lebenslauf',
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'type="file"', $html );
		$this->assertStringContainsString( 'resume', $html );
		$this->assertStringContainsString( 'rp-form__file-zone', $html );
		$this->assertStringContainsString( 'rp-form__file-dropzone', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_multiple_attribute_when_enabled(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'multiple' => true ],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'multiple', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_accepted_file_types(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'accepted_mime' => [ 'pdf', 'doc' ] ],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'accept=', $html );
		$this->assertStringContainsString( 'PDF', $html );
	}

	/**
	 * @test
	 */
	public function it_returns_dash_for_empty_display_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->formatDisplayValue( [], $field );

		$this->assertEquals( '—', $result );
	}

	/**
	 * @test
	 */
	public function it_has_available_validation_rules(): void {
		$rules = $this->field_type->getAvailableValidationRules();

		$keys = array_column( $rules, 'key' );

		$this->assertContains( 'max_file_size', $keys );
		$this->assertContains( 'min_files', $keys );
		$this->assertContains( 'max_files', $keys );
	}

	/**
	 * @test
	 */
	public function it_has_correct_default_settings(): void {
		$settings = $this->field_type->getDefaultSettings();

		$this->assertArrayHasKey( 'multiple', $settings );
		$this->assertArrayHasKey( 'max_files', $settings );
		$this->assertArrayHasKey( 'accepted_mime', $settings );
		$this->assertFalse( $settings['multiple'] );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 *
	 * @param array $overrides Überschreibungen.
	 * @return FieldDefinition
	 */
	private function createFieldDefinition( array $overrides = [] ): FieldDefinition {
		$defaults = [
			'id'          => 1,
			'field_key'   => 'test_file',
			'type'        => 'file',
			'label'       => 'Test File',
			'is_required' => false,
			'validation'  => [],
			'settings'    => [
				'multiple'      => false,
				'accepted_mime' => [ 'pdf', 'doc', 'docx' ],
			],
		];

		$data = array_merge( $defaults, $overrides );

		foreach ( [ 'validation', 'settings', 'options', 'conditional' ] as $json_field ) {
			if ( isset( $data[ $json_field ] ) && is_array( $data[ $json_field ] ) ) {
				$data[ $json_field ] = wp_json_encode( $data[ $json_field ] );
			}
		}

		return FieldDefinition::hydrate( $data );
	}
}
