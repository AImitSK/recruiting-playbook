<?php
/**
 * SelectField Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\FieldTypes;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\FieldTypes\SelectField;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Tests für SelectField
 */
class SelectFieldTest extends TestCase {

	private SelectField $field_type;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->alias( function( $str ) {
			return trim( strip_tags( $str ) );
		});

		$this->field_type = new SelectField();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_type(): void {
		$this->assertEquals( 'select', $this->field_type->getType() );
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
	public function it_validates_option_value(): void {
		$field = $this->createFieldDefinition( [
			'options' => [
				[ 'value' => 'option1', 'label' => 'Option 1' ],
				[ 'value' => 'option2', 'label' => 'Option 2' ],
			],
		] );

		$result = $this->field_type->validate( 'invalid_option', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_accepts_valid_option(): void {
		$field = $this->createFieldDefinition( [
			'options' => [
				[ 'value' => 'option1', 'label' => 'Option 1' ],
				[ 'value' => 'option2', 'label' => 'Option 2' ],
			],
		] );

		$result = $this->field_type->validate( 'option1', $field );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_accepts_any_value_when_allow_other_is_true(): void {
		$field = $this->createFieldDefinition( [
			'options'  => [
				[ 'value' => 'option1', 'label' => 'Option 1' ],
			],
			'settings' => [ 'allow_other' => true ],
		] );

		$result = $this->field_type->validate( 'custom_value', $field );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_formats_display_value_with_label(): void {
		$field = $this->createFieldDefinition( [
			'options' => [
				[ 'value' => 'opt1', 'label' => 'Option One' ],
				[ 'value' => 'opt2', 'label' => 'Option Two' ],
			],
		] );

		$result = $this->field_type->formatDisplayValue( 'opt1', $field );

		$this->assertEquals( 'Option One', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_raw_value_when_label_not_found(): void {
		$field = $this->createFieldDefinition( [
			'options' => [],
		] );

		$result = $this->field_type->formatDisplayValue( 'custom', $field );

		$this->assertEquals( 'custom', $result );
	}

	/**
	 * @test
	 */
	public function it_renders_select_element(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'department',
			'label'     => 'Abteilung',
			'options'   => [
				[ 'value' => 'it', 'label' => 'IT' ],
				[ 'value' => 'hr', 'label' => 'HR' ],
			],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'department', $html );
		$this->assertStringContainsString( 'value="it"', $html );
		$this->assertStringContainsString( 'IT', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_other_option_when_enabled(): void {
		$field = $this->createFieldDefinition( [
			'field_key' => 'source',
			'settings'  => [ 'allow_other' => true ],
			'options'   => [
				[ 'value' => 'web', 'label' => 'Website' ],
			],
		] );

		$html = $this->field_type->render( $field );

		$this->assertStringContainsString( '__other__', $html );
		$this->assertStringContainsString( 'x-show="showOther"', $html );
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
			'field_key'   => 'test_select',
			'type'        => 'select',
			'label'       => 'Test Select',
			'is_required' => false,
			'options'     => [],
			'validation'  => [],
			'settings'    => [],
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
