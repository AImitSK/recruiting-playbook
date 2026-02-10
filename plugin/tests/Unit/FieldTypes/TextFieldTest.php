<?php
/**
 * TextField Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\FieldTypes;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\FieldTypes\TextField;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Tests für TextField
 */
class TextFieldTest extends TestCase {

	private TextField $field_type;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// WordPress Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->alias( function( $str ) {
			return trim( strip_tags( $str ) );
		});

		$this->field_type = new TextField();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_type(): void {
		$this->assertEquals( 'text', $this->field_type->getType() );
	}

	/**
	 * @test
	 */
	public function it_returns_correct_group(): void {
		$this->assertEquals( 'text', $this->field_type->getGroup() );
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
	public function it_is_not_file_upload(): void {
		$this->assertFalse( $this->field_type->isFileUpload() );
	}

	/**
	 * @test
	 */
	public function it_validates_required_field(): void {
		$field = $this->createFieldDefinition( [
			'is_required' => true,
		] );

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_passes_validation_for_optional_empty_field(): void {
		$field = $this->createFieldDefinition( [
			'is_required' => false,
		] );

		$result = $this->field_type->validate( '', $field );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_validates_min_length(): void {
		$field = $this->createFieldDefinition( [
			'validation' => [ 'min_length' => 5 ],
		] );

		$result = $this->field_type->validate( 'abc', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_max_length(): void {
		$field = $this->createFieldDefinition( [
			'validation' => [ 'max_length' => 5 ],
		] );

		$result = $this->field_type->validate( 'too long string', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_pattern(): void {
		$field = $this->createFieldDefinition( [
			'validation' => [ 'pattern' => '^[A-Z]+$' ],
		] );

		$result = $this->field_type->validate( 'lowercase', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_passes_valid_pattern(): void {
		$field = $this->createFieldDefinition( [
			'validation' => [ 'pattern' => '^[A-Z]+$' ],
		] );

		$result = $this->field_type->validate( 'UPPERCASE', $field );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_input(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->sanitize( '  <script>alert("xss")</script>Test  ', $field );

		$this->assertEquals( 'alert("xss")Test', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_string_for_empty_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->sanitize( '', $field );

		$this->assertEquals( '', $result );
	}

	/**
	 * @test
	 */
	public function it_formats_display_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->formatDisplayValue( 'Test Value', $field );

		$this->assertEquals( 'Test Value', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_dash_for_empty_display_value(): void {
		$field = $this->createFieldDefinition();

		$result = $this->field_type->formatDisplayValue( '', $field );

		$this->assertEquals( '—', $result );
	}

	/**
	 * @test
	 */
	public function it_renders_input_field(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'test_field',
			'label'     => 'Test Label',
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'test_field', $html );
		$this->assertStringContainsString( 'Test Label', $html );
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
			'field_key'   => 'test_field',
			'type'        => 'text',
			'label'       => 'Test Field',
			'is_required' => false,
			'validation'  => [],
			'settings'    => [],
		];

		$data = array_merge( $defaults, $overrides );

		// JSON-Felder encodieren.
		foreach ( [ 'validation', 'settings', 'options', 'conditional' ] as $json_field ) {
			if ( isset( $data[ $json_field ] ) && is_array( $data[ $json_field ] ) ) {
				$data[ $json_field ] = wp_json_encode( $data[ $json_field ] );
			}
		}

		return FieldDefinition::hydrate( $data );
	}
}
