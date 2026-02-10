<?php
/**
 * FieldValue Model Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Models\FieldValue;
use Brain\Monkey\Functions;

/**
 * Tests für das FieldValue Model
 */
class FieldValueTest extends TestCase {

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// WordPress-Funktionen mocken.
		Functions\when( 'wp_date' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		Functions\when( 'get_option' )->justReturn( 'd.m.Y' );

		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'sanitize_email' )->returnArg();

		Functions\when( 'number_format_i18n' )->alias( function( $number, $decimals = 0 ) {
			return number_format( $number, $decimals, ',', '.' );
		} );

		Functions\when( '__' )->returnArg();
	}

	/**
	 * Test: Constructor setzt Werte korrekt
	 */
	public function test_constructor_sets_values(): void {
		$value = new FieldValue( 'email', 'test@example.com', 'email', 'E-Mail' );

		$this->assertEquals( 'email', $value->getFieldKey() );
		$this->assertEquals( 'test@example.com', $value->getValue() );
		$this->assertEquals( 'email', $value->getFieldType() );
		$this->assertEquals( 'E-Mail', $value->getLabel() );
	}

	/**
	 * Test: Default-Werte
	 */
	public function test_default_values(): void {
		$value = new FieldValue();

		$this->assertEquals( '', $value->getFieldKey() );
		$this->assertNull( $value->getValue() );
		$this->assertEquals( 'text', $value->getFieldType() );
		$this->assertEquals( '', $value->getLabel() );
	}

	/**
	 * Test: isEmpty erkennt leere Werte
	 */
	public function test_is_empty_detects_empty_values(): void {
		$null_value   = new FieldValue( 'test', null );
		$empty_string = new FieldValue( 'test', '' );
		$whitespace   = new FieldValue( 'test', '   ' );
		$empty_array  = new FieldValue( 'test', [] );
		$has_value    = new FieldValue( 'test', 'content' );

		$this->assertTrue( $null_value->isEmpty() );
		$this->assertTrue( $empty_string->isEmpty() );
		$this->assertTrue( $whitespace->isEmpty() );
		$this->assertTrue( $empty_array->isEmpty() );
		$this->assertFalse( $has_value->isEmpty() );
	}

	/**
	 * Test: getDisplayValue für leere Werte
	 */
	public function test_display_value_for_empty(): void {
		$value = new FieldValue( 'test', null, 'text' );

		$this->assertEquals( '—', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für Text
	 */
	public function test_display_value_for_text(): void {
		$value = new FieldValue( 'name', 'Max Mustermann', 'text', 'Name' );

		$this->assertEquals( 'Max Mustermann', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für Checkbox (boolean true)
	 */
	public function test_display_value_for_checkbox_true(): void {
		$value = new FieldValue( 'consent', true, 'checkbox', 'Zustimmung' );

		$this->assertEquals( 'Ja', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für Checkbox (boolean false)
	 */
	public function test_display_value_for_checkbox_false(): void {
		$value = new FieldValue( 'consent', false, 'checkbox', 'Zustimmung' );

		$this->assertEquals( 'Nein', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für Checkbox (Array/Multi)
	 */
	public function test_display_value_for_checkbox_array(): void {
		$value = new FieldValue( 'languages', [ 'Deutsch', 'Englisch' ], 'checkbox', 'Sprachen' );

		$this->assertEquals( 'Deutsch, Englisch', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für Number
	 */
	public function test_display_value_for_number(): void {
		$value = new FieldValue( 'salary', 50000.5, 'number', 'Gehalt' );

		$this->assertEquals( '50.001', $value->getDisplayValue() );
	}

	/**
	 * Test: getTypedValue für Number
	 */
	public function test_typed_value_for_number(): void {
		$value = new FieldValue( 'amount', '123.45', 'number' );

		$this->assertIsFloat( $value->getTypedValue() );
		$this->assertEquals( 123.45, $value->getTypedValue() );
	}

	/**
	 * Test: getTypedValue für Checkbox (String '1')
	 */
	public function test_typed_value_for_checkbox_string_one(): void {
		$value = new FieldValue( 'consent', '1', 'checkbox' );

		$this->assertTrue( $value->getTypedValue() );
	}

	/**
	 * Test: getTypedValue für Checkbox (String '0')
	 */
	public function test_typed_value_for_checkbox_string_zero(): void {
		$value = new FieldValue( 'consent', '0', 'checkbox' );

		$this->assertFalse( $value->getTypedValue() );
	}

	/**
	 * Test: getTypedValue für Checkbox (JSON Array)
	 */
	public function test_typed_value_for_checkbox_json_array(): void {
		$value = new FieldValue( 'options', '["a","b","c"]', 'checkbox' );

		$this->assertIsArray( $value->getTypedValue() );
		$this->assertEquals( [ 'a', 'b', 'c' ], $value->getTypedValue() );
	}

	/**
	 * Test: getDisplayValue für Date
	 */
	public function test_display_value_for_date(): void {
		$value = new FieldValue( 'birthday', '2000-01-15', 'date' );

		$display = $value->getDisplayValue();

		$this->assertStringContainsString( '15', $display );
		$this->assertStringContainsString( '01', $display );
		$this->assertStringContainsString( '2000', $display );
	}

	/**
	 * Test: getTypedValue für File (JSON)
	 */
	public function test_typed_value_for_file_json(): void {
		$file_data = [
			'original_name' => 'lebenslauf.pdf',
			'file_path'     => '/uploads/cv123.pdf',
		];

		$value = new FieldValue( 'resume', json_encode( $file_data ), 'file' );

		$typed = $value->getTypedValue();

		$this->assertIsArray( $typed );
		$this->assertEquals( 'lebenslauf.pdf', $typed['original_name'] );
	}

	/**
	 * Test: getDisplayValue für File (Single)
	 */
	public function test_display_value_for_file_single(): void {
		$value = new FieldValue(
			'resume',
			[ 'original_name' => 'lebenslauf.pdf' ],
			'file'
		);

		$this->assertEquals( 'lebenslauf.pdf', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für File (Multiple)
	 */
	public function test_display_value_for_file_multiple(): void {
		$value = new FieldValue(
			'documents',
			[
				[ 'original_name' => 'lebenslauf.pdf' ],
				[ 'original_name' => 'zeugnis.pdf' ],
			],
			'file'
		);

		$this->assertEquals( 'lebenslauf.pdf, zeugnis.pdf', $value->getDisplayValue() );
	}

	/**
	 * Test: getDisplayValue für URL
	 */
	public function test_display_value_for_url(): void {
		$value = new FieldValue( 'website', 'https://example.com/profile', 'url' );

		$display = $value->getDisplayValue();

		$this->assertStringContainsString( 'href="https://example.com/profile"', $display );
		$this->assertStringContainsString( 'target="_blank"', $display );
	}

	/**
	 * Test: getDisplayValue für Email
	 */
	public function test_display_value_for_email(): void {
		$value = new FieldValue( 'email', 'test@example.com', 'email' );

		$display = $value->getDisplayValue();

		$this->assertStringContainsString( 'mailto:test@example.com', $display );
		$this->assertStringContainsString( 'test@example.com', $display );
	}

	/**
	 * Test: getDisplayValue für Phone
	 */
	public function test_display_value_for_phone(): void {
		$value = new FieldValue( 'phone', '+49 123 456789', 'phone' );

		$display = $value->getDisplayValue();

		$this->assertStringContainsString( 'tel:+49123456789', $display );
		$this->assertStringContainsString( '+49 123 456789', $display );
	}

	/**
	 * Test: toArray gibt korrektes Format zurück
	 */
	public function test_to_array_returns_correct_format(): void {
		$value = new FieldValue( 'name', 'Max Mustermann', 'text', 'Vollständiger Name' );

		$array = $value->toArray();

		$this->assertArrayHasKey( 'field_key', $array );
		$this->assertArrayHasKey( 'field_type', $array );
		$this->assertArrayHasKey( 'label', $array );
		$this->assertArrayHasKey( 'value', $array );
		$this->assertArrayHasKey( 'display_value', $array );

		$this->assertEquals( 'name', $array['field_key'] );
		$this->assertEquals( 'text', $array['field_type'] );
		$this->assertEquals( 'Vollständiger Name', $array['label'] );
		$this->assertEquals( 'Max Mustermann', $array['value'] );
	}

	/**
	 * Test: create Factory-Methode
	 */
	public function test_create_factory(): void {
		$value = FieldValue::create( 'email', 'test@example.com', 'email', 'E-Mail' );

		$this->assertInstanceOf( FieldValue::class, $value );
		$this->assertEquals( 'email', $value->getFieldKey() );
		$this->assertEquals( 'test@example.com', $value->getValue() );
	}

	/**
	 * Test: Setters funktionieren korrekt
	 */
	public function test_setters_work_correctly(): void {
		$value = new FieldValue();

		$value->setFieldKey( 'custom' )
			->setValue( 'Test Value' )
			->setFieldType( 'textarea' )
			->setLabel( 'Custom Label' );

		$this->assertEquals( 'custom', $value->getFieldKey() );
		$this->assertEquals( 'Test Value', $value->getValue() );
		$this->assertEquals( 'textarea', $value->getFieldType() );
		$this->assertEquals( 'Custom Label', $value->getLabel() );
	}
}
