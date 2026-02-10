<?php
/**
 * CheckboxField Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\FieldTypes;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\FieldTypes\CheckboxField;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Tests für CheckboxField
 */
class CheckboxFieldTest extends TestCase {

	private CheckboxField $field_type;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->alias( function( $str ) {
			return trim( strip_tags( $str ) );
		});

		$this->field_type = new CheckboxField();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_type(): void {
		$this->assertEquals( 'checkbox', $this->field_type->getType() );
	}

	/**
	 * @test
	 */
	public function it_returns_choice_group(): void {
		$this->assertEquals( 'choice', $this->field_type->getGroup() );
	}

	/**
	 * @test
	 */
	public function it_supports_options(): void {
		$this->assertTrue( $this->field_type->supportsOptions() );
	}

	/**
	 * @test
	 */
	public function it_validates_required_single_checkbox(): void {
		$field = $this->createFieldDefinition( [
			'is_required' => true,
			'settings'    => [ 'mode' => 'single' ],
		] );

		$result = $this->field_type->validate( false, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_passes_when_single_checkbox_checked(): void {
		$field = $this->createFieldDefinition( [
			'is_required' => true,
			'settings'    => [ 'mode' => 'single' ],
		] );

		$result = $this->field_type->validate( true, $field );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_validates_multi_checkbox_min_checked(): void {
		$field = $this->createFieldDefinition( [
			'settings'   => [ 'mode' => 'multi' ],
			'validation' => [ 'min_checked' => 2 ],
			'options'    => [
				[ 'value' => 'a', 'label' => 'A' ],
				[ 'value' => 'b', 'label' => 'B' ],
				[ 'value' => 'c', 'label' => 'C' ],
			],
		] );

		$result = $this->field_type->validate( [ 'a' ], $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_multi_checkbox_max_checked(): void {
		$field = $this->createFieldDefinition( [
			'settings'   => [ 'mode' => 'multi' ],
			'validation' => [ 'max_checked' => 2 ],
			'options'    => [
				[ 'value' => 'a', 'label' => 'A' ],
				[ 'value' => 'b', 'label' => 'B' ],
				[ 'value' => 'c', 'label' => 'C' ],
			],
		] );

		$result = $this->field_type->validate( [ 'a', 'b', 'c' ], $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_invalid_option_in_multi(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'multi' ],
			'options'  => [
				[ 'value' => 'a', 'label' => 'A' ],
				[ 'value' => 'b', 'label' => 'B' ],
			],
		] );

		$result = $this->field_type->validate( [ 'a', 'invalid' ], $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_single_checkbox_to_boolean(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'single' ],
		] );

		$this->assertTrue( $this->field_type->sanitize( '1', $field ) );
		$this->assertTrue( $this->field_type->sanitize( 'on', $field ) );
		$this->assertFalse( $this->field_type->sanitize( '', $field ) );
		$this->assertFalse( $this->field_type->sanitize( null, $field ) );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_multi_checkbox_to_array(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'multi' ],
		] );

		$result = $this->field_type->sanitize( [ 'a', 'b' ], $field );

		$this->assertEquals( [ 'a', 'b' ], $result );
	}

	/**
	 * @test
	 */
	public function it_formats_single_checkbox_display_value(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'single' ],
		] );

		$yes_result = $this->field_type->formatDisplayValue( true, $field );
		$no_result  = $this->field_type->formatDisplayValue( false, $field );

		$this->assertStringContainsString( 'Ja', $yes_result );
		$this->assertStringContainsString( 'Nein', $no_result );
	}

	/**
	 * @test
	 */
	public function it_formats_multi_checkbox_display_value(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'multi' ],
			'options'  => [
				[ 'value' => 'a', 'label' => 'Alpha' ],
				[ 'value' => 'b', 'label' => 'Beta' ],
			],
		] );

		$result = $this->field_type->formatDisplayValue( [ 'a', 'b' ], $field );

		$this->assertEquals( 'Alpha, Beta', $result );
	}

	/**
	 * @test
	 */
	public function it_formats_export_value(): void {
		$field = $this->createFieldDefinition( [
			'settings' => [ 'mode' => 'single' ],
		] );

		$this->assertEquals( '1', $this->field_type->formatExportValue( true, $field ) );
		$this->assertEquals( '0', $this->field_type->formatExportValue( false, $field ) );
	}

	/**
	 * @test
	 */
	public function it_renders_single_checkbox(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'privacy',
			'label'     => 'Datenschutz akzeptieren',
			'settings'  => [ 'mode' => 'single' ],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'privacy', $html );
		$this->assertStringContainsString( 'rp-form__checkbox-label--single', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_multi_checkbox(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'skills',
			'label'     => 'Skills',
			'settings'  => [ 'mode' => 'multi' ],
			'options'   => [
				[ 'value' => 'php', 'label' => 'PHP' ],
				[ 'value' => 'js', 'label' => 'JavaScript' ],
			],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( 'rp-form__checkbox-group', $html );
		$this->assertStringContainsString( 'skills[]', $html );
		$this->assertStringContainsString( 'PHP', $html );
		$this->assertStringContainsString( 'JavaScript', $html );
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
			'field_key'   => 'test_checkbox',
			'type'        => 'checkbox',
			'label'       => 'Test Checkbox',
			'is_required' => false,
			'options'     => [],
			'validation'  => [],
			'settings'    => [ 'mode' => 'single' ],
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
