<?php
/**
 * FormValidationService Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\FormValidationService;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Tests für FormValidationService
 */
class FormValidationServiceTest extends TestCase {

	private FormValidationService $service;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'sanitize_text_field' )->alias( function( $str ) {
			return trim( strip_tags( $str ) );
		});
		Functions\when( 'sanitize_email' )->alias( function( $str ) {
			return filter_var( $str, FILTER_SANITIZE_EMAIL );
		});
		Functions\when( 'is_email' )->alias( function( $str ) {
			return filter_var( $str, FILTER_VALIDATE_EMAIL ) !== false;
		});

		FieldTypeRegistry::resetInstance();
		$this->service = new FormValidationService();
	}

	protected function tearDown(): void {
		FieldTypeRegistry::resetInstance();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_validates_form_successfully(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
			] ),
			$this->createFieldDefinition( [
				'field_key' => 'email',
				'type'      => 'email',
			] ),
		];

		$form_data = [
			'name'  => 'Max Mustermann',
			'email' => 'max@example.com',
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_returns_errors_for_invalid_data(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key'   => 'email',
				'type'        => 'email',
				'is_required' => true,
			] ),
		];

		$form_data = [
			'email' => 'invalid-email',
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_required_fields(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key'   => 'name',
				'type'        => 'text',
				'is_required' => true,
			] ),
		];

		$form_data = [
			'name' => '',
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_skips_hidden_fields_by_conditional_logic(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key'   => 'show_other',
				'type'        => 'checkbox',
				'settings'    => [ 'mode' => 'single' ],
			] ),
			$this->createFieldDefinition( [
				'field_key'   => 'other_field',
				'type'        => 'text',
				'is_required' => true,
				'conditional' => [
					'field'    => 'show_other',
					'operator' => 'equals',
					'value'    => '1',
				],
			] ),
		];

		$form_data = [
			'show_other'  => false,
			'other_field' => '', // Leer, aber sollte übersprungen werden.
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_validates_conditional_visible_fields(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'show_other',
				'type'      => 'checkbox',
				'settings'  => [ 'mode' => 'single' ],
			] ),
			$this->createFieldDefinition( [
				'field_key'   => 'other_field',
				'type'        => 'text',
				'is_required' => true,
				'conditional' => [
					'field'    => 'show_other',
					'operator' => 'not_empty',
					'value'    => '',
				],
			] ),
		];

		$form_data = [
			'show_other'  => true,
			'other_field' => '', // Leer und sichtbar -> Fehler.
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_form_data(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
			] ),
			$this->createFieldDefinition( [
				'field_key' => 'email',
				'type'      => 'email',
			] ),
		];

		$form_data = [
			'name'  => '  <script>alert("xss")</script>Max  ',
			'email' => 'MAX@EXAMPLE.COM',
		];

		$result = $this->service->sanitize( $form_data, $fields );

		$this->assertEquals( 'alert("xss")Max', $result['name'] );
		$this->assertStringContainsString( '@', $result['email'] );
	}

	/**
	 * @test
	 */
	public function it_skips_layout_fields_during_sanitization(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'section_heading',
				'type'      => 'heading',
			] ),
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
			] ),
		];

		$form_data = [
			'name' => 'Test',
		];

		$result = $this->service->sanitize( $form_data, $fields );

		$this->assertArrayNotHasKey( 'section_heading', $result );
		$this->assertArrayHasKey( 'name', $result );
	}

	/**
	 * @test
	 */
	public function it_validates_and_sanitizes_in_one_call(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
			] ),
		];

		$form_data = [
			'name' => '  Test Name  ',
		];

		$result = $this->service->validateAndSanitize( $form_data, $fields );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Test Name', $result['name'] );
	}

	/**
	 * @test
	 */
	public function it_returns_error_from_validate_and_sanitize(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key'   => 'email',
				'type'        => 'email',
				'is_required' => true,
			] ),
		];

		$form_data = [
			'email' => '',
		];

		$result = $this->service->validateAndSanitize( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_formats_data_for_display(): void {
		Functions\when( 'esc_html' )->returnArg( 1 );

		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
				'label'     => 'Name',
			] ),
		];

		$form_data = [
			'name' => 'Max Mustermann',
		];

		$result = $this->service->formatForDisplay( $form_data, $fields );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertEquals( 'Name', $result['name']['label'] );
		$this->assertEquals( 'Max Mustermann', $result['name']['value'] );
		$this->assertEquals( 'text', $result['name']['type'] );
	}

	/**
	 * @test
	 */
	public function it_generates_export_headers(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'name',
				'type'      => 'text',
				'label'     => 'Name',
			] ),
			$this->createFieldDefinition( [
				'field_key' => 'email',
				'type'      => 'email',
				'label'     => 'E-Mail',
			] ),
			$this->createFieldDefinition( [
				'field_key' => 'heading',
				'type'      => 'heading',
				'label'     => 'Abschnitt',
			] ),
		];

		$result = $this->service->getExportHeaders( $fields );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'email', $result );
		$this->assertArrayNotHasKey( 'heading', $result );
		$this->assertEquals( 'Name', $result['name'] );
	}

	/**
	 * @test
	 */
	public function it_returns_conditional_operators(): void {
		$operators = $this->service->getConditionalOperators();

		$this->assertArrayHasKey( 'equals', $operators );
		$this->assertArrayHasKey( 'not_equals', $operators );
		$this->assertArrayHasKey( 'contains', $operators );
		$this->assertArrayHasKey( 'not_empty', $operators );
		$this->assertArrayHasKey( 'empty', $operators );
		$this->assertArrayHasKey( 'greater_than', $operators );
		$this->assertArrayHasKey( 'less_than', $operators );
		$this->assertArrayHasKey( 'in', $operators );
	}

	/**
	 * @test
	 */
	public function it_evaluates_in_operator_correctly(): void {
		$fields = [
			$this->createFieldDefinition( [
				'field_key' => 'category',
				'type'      => 'select',
				'options'   => [
					[ 'value' => 'a', 'label' => 'A' ],
					[ 'value' => 'b', 'label' => 'B' ],
					[ 'value' => 'c', 'label' => 'C' ],
				],
			] ),
			$this->createFieldDefinition( [
				'field_key'   => 'special_field',
				'type'        => 'text',
				'is_required' => true,
				'conditional' => [
					'field'    => 'category',
					'operator' => 'in',
					'value'    => 'a, b',
				],
			] ),
		];

		// Kategorie C -> special_field sollte übersprungen werden.
		$form_data = [
			'category'      => 'c',
			'special_field' => '',
		];

		$result = $this->service->validate( $form_data, $fields );

		$this->assertTrue( $result );
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
			'options'     => [],
			'conditional' => [],
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
