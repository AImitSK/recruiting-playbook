<?php
/**
 * Custom Fields Workflow Integration Tests
 *
 * Testet den kompletten Workflow von Custom Fields:
 * - Feld-Definition erstellen
 * - Formular-Rendering
 * - Validierung und Speicherung
 * - Anzeige in Admin-Bereich
 *
 * @package RecruitingPlaybook\Tests\Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Services\FormValidationService;
use RecruitingPlaybook\Services\CustomFieldsService;
use RecruitingPlaybook\Services\FormRenderService;
use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Integration Tests für Custom Fields Workflow
 */
class CustomFieldsWorkflowTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-01 12:00:00' );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'is_email' )->alias( function( $email ) {
			return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
		} );

		// FieldTypeRegistry zurücksetzen.
		FieldTypeRegistry::resetInstance();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		FieldTypeRegistry::resetInstance();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_completes_full_workflow_from_field_definition_to_validation(): void {
		// 1. Felddefinitionen erstellen.
		$fields = [
			$this->createFieldDefinition( 'experience', 'text', 'Berufserfahrung', true ),
			$this->createFieldDefinition( 'skills', 'textarea', 'Fähigkeiten', false ),
			$this->createFieldDefinition( 'available', 'date', 'Verfügbar ab', true ),
		];

		// 2. FormValidationService für Validierung.
		$validation_service = new FormValidationService();

		$form_data = [
			'experience' => '5 Jahre PHP Entwicklung',
			'skills'     => 'PHP, JavaScript, MySQL',
			'available'  => '2025-03-01',
		];

		// 3. Validierung durchführen.
		$result = $validation_service->validate( $form_data, $fields );

		$this->assertTrue( $result, 'Validierung sollte erfolgreich sein' );

		// 4. Daten sanitieren.
		$sanitized = $validation_service->sanitize( $form_data, $fields );

		$this->assertEquals( '5 Jahre PHP Entwicklung', $sanitized['experience'] );
		$this->assertEquals( 'PHP, JavaScript, MySQL', $sanitized['skills'] );
		$this->assertEquals( '2025-03-01', $sanitized['available'] );
	}

	/**
	 * @test
	 */
	public function it_validates_required_fields_correctly(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', 'Name', true ),
			$this->createFieldDefinition( 'optional', 'text', 'Optional', false ),
		];

		$validation_service = new FormValidationService();

		// Fehlende Required-Daten.
		$form_data = [
			'name'     => '',
			'optional' => 'Irgendwas',
		];

		$result = $validation_service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'validation_failed', $result->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_applies_conditional_logic_during_validation(): void {
		// Feld mit Conditional Logic: other_input nur wenn type === 'other'.
		$fields = [
			$this->createFieldDefinition( 'type', 'select', 'Typ', true, [
				[ 'value' => 'standard', 'label' => 'Standard' ],
				[ 'value' => 'other', 'label' => 'Sonstiges' ],
			] ),
			$this->createFieldDefinitionWithConditional(
				'other_input',
				'text',
				'Sonstiges eingeben',
				true,
				[
					'field'    => 'type',
					'operator' => 'equals',
					'value'    => 'other',
				]
			),
		];

		$validation_service = new FormValidationService();

		// Wenn type !== 'other', sollte other_input nicht validiert werden.
		$form_data = [
			'type'        => 'standard',
			'other_input' => '', // Leer, aber nicht relevant.
		];

		$result = $validation_service->validate( $form_data, $fields );

		$this->assertTrue( $result, 'Validierung sollte erfolgreich sein, da other_input nicht sichtbar ist' );
	}

	/**
	 * @test
	 */
	public function it_validates_conditional_field_when_visible(): void {
		$fields = [
			$this->createFieldDefinition( 'type', 'select', 'Typ', true, [
				[ 'value' => 'standard', 'label' => 'Standard' ],
				[ 'value' => 'other', 'label' => 'Sonstiges' ],
			] ),
			$this->createFieldDefinitionWithConditional(
				'other_input',
				'text',
				'Sonstiges eingeben',
				true,
				[
					'field'    => 'type',
					'operator' => 'equals',
					'value'    => 'other',
				]
			),
		];

		$validation_service = new FormValidationService();

		// Wenn type === 'other', sollte other_input validiert werden.
		$form_data = [
			'type'        => 'other',
			'other_input' => '', // Leer und required -> Fehler.
		];

		$result = $validation_service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_formats_data_for_display_correctly(): void {
		$fields = [
			$this->createFieldDefinition( 'experience', 'text', 'Berufserfahrung', false ),
			$this->createFieldDefinition( 'salary', 'number', 'Gehaltsvorstellung', false ),
		];

		$validation_service = new FormValidationService();

		$form_data = [
			'experience' => '5 Jahre',
			'salary'     => '50000',
		];

		$display = $validation_service->formatForDisplay( $form_data, $fields );

		$this->assertArrayHasKey( 'experience', $display );
		$this->assertEquals( 'Berufserfahrung', $display['experience']['label'] );
		$this->assertEquals( '5 Jahre', $display['experience']['value'] );

		$this->assertArrayHasKey( 'salary', $display );
		$this->assertEquals( 'Gehaltsvorstellung', $display['salary']['label'] );
	}

	/**
	 * @test
	 */
	public function it_generates_export_headers_from_fields(): void {
		$fields = [
			$this->createFieldDefinition( 'experience', 'text', 'Berufserfahrung', false ),
			$this->createFieldDefinition( 'section', 'heading', 'Abschnitt', false ),
			$this->createFieldDefinition( 'salary', 'number', 'Gehaltsvorstellung', false ),
		];

		$validation_service = new FormValidationService();
		$headers = $validation_service->getExportHeaders( $fields );

		// Heading-Felder sollten nicht im Export sein.
		$this->assertArrayHasKey( 'experience', $headers );
		$this->assertArrayHasKey( 'salary', $headers );
		$this->assertArrayNotHasKey( 'section', $headers );
	}

	/**
	 * @test
	 */
	public function it_handles_checkbox_arrays_in_validation(): void {
		$fields = [
			$this->createFieldDefinition( 'skills', 'checkbox', 'Fähigkeiten', true, [
				[ 'value' => 'php', 'label' => 'PHP' ],
				[ 'value' => 'js', 'label' => 'JavaScript' ],
				[ 'value' => 'python', 'label' => 'Python' ],
			] ),
		];

		$validation_service = new FormValidationService();

		$form_data = [
			'skills' => [ 'php', 'js' ],
		];

		$result = $validation_service->validate( $form_data, $fields );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_validates_email_field_format(): void {
		$fields = [
			$this->createFieldDefinition( 'contact_email', 'email', 'Kontakt-Email', true ),
		];

		$validation_service = new FormValidationService();

		// Ungültige Email.
		$form_data = [
			'contact_email' => 'not-an-email',
		];

		$result = $validation_service->validate( $form_data, $fields );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_validates_number_field_constraints(): void {
		$fields = [
			$this->createFieldDefinitionWithValidation(
				'age',
				'number',
				'Alter',
				true,
				[ 'min' => 18, 'max' => 65 ]
			),
		];

		$validation_service = new FormValidationService();

		// Zu jung.
		$result1 = $validation_service->validate( [ 'age' => '16' ], $fields );
		$this->assertInstanceOf( WP_Error::class, $result1 );

		// Gültiges Alter.
		$result2 = $validation_service->validate( [ 'age' => '30' ], $fields );
		$this->assertTrue( $result2 );

		// Zu alt.
		$result3 = $validation_service->validate( [ 'age' => '70' ], $fields );
		$this->assertInstanceOf( WP_Error::class, $result3 );
	}

	/**
	 * @test
	 */
	public function it_sanitizes_and_validates_in_one_step(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', 'Name', true ),
			$this->createFieldDefinition( 'bio', 'textarea', 'Über mich', false ),
		];

		$validation_service = new FormValidationService();

		$form_data = [
			'name' => '  Max Mustermann  ',
			'bio'  => '  Erfahrener Entwickler  ',
		];

		$result = $validation_service->validateAndSanitize( $form_data, $fields );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Max Mustermann', $result['name'] );
		$this->assertEquals( 'Erfahrener Entwickler', $result['bio'] );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 */
	private function createFieldDefinition(
		string $key,
		string $type,
		string $label,
		bool $required = false,
		array $options = []
	): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'type'        => $type,
			'label'       => $label,
			'is_required' => $required,
			'is_enabled'  => true,
			'is_system'   => false,
			'options'     => ! empty( $options ) ? json_encode( $options ) : null,
		];

		return FieldDefinition::hydrate( $data );
	}


	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition mit Validation
	 */
	private function createFieldDefinitionWithValidation(
		string $key,
		string $type,
		string $label,
		bool $required,
		array $validation
	): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'type'        => $type,
			'label'       => $label,
			'is_required' => $required,
			'is_enabled'  => true,
			'is_system'   => false,
			'validation'  => json_encode( $validation ),
		];

		return FieldDefinition::hydrate( $data );
	}
}
