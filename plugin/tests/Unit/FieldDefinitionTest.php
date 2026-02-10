<?php
/**
 * FieldDefinition Model Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Models\FieldDefinition;

/**
 * Tests für das FieldDefinition Model
 */
class FieldDefinitionTest extends TestCase {

	/**
	 * Test: Constructor mit leeren Daten erstellt Default-Werte
	 */
	public function test_constructor_creates_default_values(): void {
		$field = new FieldDefinition();

		$this->assertEquals( 0, $field->getId() );
		$this->assertEquals( '', $field->getFieldKey() );
		$this->assertEquals( 'text', $field->getFieldType() );
		$this->assertEquals( '', $field->getLabel() );
		$this->assertNull( $field->getTemplateId() );
		$this->assertNull( $field->getJobId() );
		$this->assertFalse( $field->isRequired() );
		$this->assertFalse( $field->isSystem() );
		$this->assertTrue( $field->isActive() );
	}

	/**
	 * Test: Constructor mit Daten hydratisiert korrekt
	 */
	public function test_constructor_hydrates_from_array(): void {
		$data = [
			'id'          => 1,
			'template_id' => 5,
			'job_id'      => null,
			'field_key'   => 'custom_field',
			'field_type'  => 'select',
			'label'       => 'Benutzerdefiniertes Feld',
			'placeholder' => 'Bitte wählen',
			'description' => 'Eine Beschreibung',
			'is_required' => 1,
			'is_system'   => 0,
			'is_active'   => 1,
			'position'    => 10,
		];

		$field = new FieldDefinition( $data );

		$this->assertEquals( 1, $field->getId() );
		$this->assertEquals( 5, $field->getTemplateId() );
		$this->assertNull( $field->getJobId() );
		$this->assertEquals( 'custom_field', $field->getFieldKey() );
		$this->assertEquals( 'select', $field->getFieldType() );
		$this->assertEquals( 'Benutzerdefiniertes Feld', $field->getLabel() );
		$this->assertEquals( 'Bitte wählen', $field->getPlaceholder() );
		$this->assertEquals( 'Eine Beschreibung', $field->getDescription() );
		$this->assertTrue( $field->isRequired() );
		$this->assertFalse( $field->isSystem() );
		$this->assertTrue( $field->isActive() );
		$this->assertEquals( 10, $field->getPosition() );
	}

	/**
	 * Test: JSON-Felder werden korrekt dekodiert
	 */
	public function test_json_fields_are_decoded(): void {
		$data = [
			'id'         => 1,
			'field_key'  => 'select_field',
			'field_type' => 'select',
			'label'      => 'Test',
			'options'    => '{"choices":[{"value":"a","label":"Option A"}]}',
			'validation' => '{"min_length":5}',
			'settings'   => '{"width":"half"}',
		];

		$field = new FieldDefinition( $data );

		$this->assertIsArray( $field->getOptions() );
		$this->assertArrayHasKey( 'choices', $field->getOptions() );

		$this->assertIsArray( $field->getValidation() );
		$this->assertEquals( 5, $field->getValidation()['min_length'] );

		$this->assertIsArray( $field->getSettings() );
		$this->assertEquals( 'half', $field->getSettings()['width'] );
	}

	/**
	 * Test: Bereits dekodierte Arrays werden beibehalten
	 */
	public function test_already_decoded_arrays_are_preserved(): void {
		$options = [ 'choices' => [ [ 'value' => 'x', 'label' => 'X' ] ] ];

		$field = new FieldDefinition( [
			'field_key'  => 'test',
			'field_type' => 'select',
			'label'      => 'Test',
			'options'    => $options,
		] );

		$this->assertEquals( $options, $field->getOptions() );
	}

	/**
	 * Test: toArray gibt korrektes Format zurück
	 */
	public function test_to_array_returns_correct_format(): void {
		$field = new FieldDefinition( [
			'id'          => 1,
			'field_key'   => 'test_field',
			'field_type'  => 'text',
			'label'       => 'Test',
			'is_required' => true,
			'is_system'   => false,
			'is_active'   => true,
		] );

		$array = $field->toArray();

		$this->assertArrayHasKey( 'id', $array );
		$this->assertArrayHasKey( 'field_key', $array );
		$this->assertArrayHasKey( 'field_type', $array );
		$this->assertArrayHasKey( 'label', $array );
		$this->assertArrayHasKey( 'is_required', $array );
		$this->assertArrayHasKey( 'is_system', $array );
		$this->assertArrayHasKey( 'is_active', $array );
		$this->assertEquals( 1, $array['id'] );
		$this->assertEquals( 'test_field', $array['field_key'] );
		$this->assertTrue( $array['is_required'] );
	}

	/**
	 * Test: toDbArray kodiert JSON-Felder korrekt
	 */
	public function test_to_db_array_encodes_json_fields(): void {
		$field = new FieldDefinition();
		$field->setFieldKey( 'test' );
		$field->setFieldType( 'select' );
		$field->setLabel( 'Test' );
		$field->setOptions( [ 'choices' => [] ] );
		$field->setValidation( [ 'min_length' => 5 ] );
		$field->setSettings( [ 'width' => 'full' ] );

		$db_array = $field->toDbArray();

		$this->assertIsString( $db_array['options'] );
		$this->assertIsString( $db_array['validation'] );
		$this->assertIsString( $db_array['settings'] );

		$this->assertEquals( '{"choices":[]}', $db_array['options'] );
	}

	/**
	 * Test: hasConditional gibt korrekten Wert zurück
	 */
	public function test_has_conditional_returns_correct_value(): void {
		$field_without = new FieldDefinition( [
			'field_key'  => 'test',
			'field_type' => 'text',
			'label'      => 'Test',
		] );

		$this->assertFalse( $field_without->hasConditional() );

		$field_with = new FieldDefinition( [
			'field_key'   => 'test',
			'field_type'  => 'text',
			'label'       => 'Test',
			'conditional' => [ 'action' => 'show', 'conditions' => [] ],
		] );

		$this->assertTrue( $field_with->hasConditional() );
	}

	/**
	 * Test: isDeleted prüft deleted_at korrekt
	 */
	public function test_is_deleted_checks_deleted_at(): void {
		$active_field = new FieldDefinition( [
			'field_key'  => 'test',
			'field_type' => 'text',
			'label'      => 'Test',
			'deleted_at' => null,
		] );

		$this->assertFalse( $active_field->isDeleted() );

		$deleted_field = new FieldDefinition( [
			'field_key'  => 'test',
			'field_type' => 'text',
			'label'      => 'Test',
			'deleted_at' => '2025-01-15 10:00:00',
		] );

		$this->assertTrue( $deleted_field->isDeleted() );
	}

	/**
	 * Test: Setters funktionieren korrekt
	 */
	public function test_setters_work_correctly(): void {
		$field = new FieldDefinition();

		$field->setFieldKey( 'new_key' )
			->setFieldType( 'email' )
			->setLabel( 'E-Mail' )
			->setPlaceholder( 'test@example.com' )
			->setDescription( 'Ihre E-Mail-Adresse' )
			->setTemplateId( 5 )
			->setJobId( 10 )
			->setPosition( 3 )
			->setRequired( true )
			->setActive( false );

		$this->assertEquals( 'new_key', $field->getFieldKey() );
		$this->assertEquals( 'email', $field->getFieldType() );
		$this->assertEquals( 'E-Mail', $field->getLabel() );
		$this->assertEquals( 'test@example.com', $field->getPlaceholder() );
		$this->assertEquals( 'Ihre E-Mail-Adresse', $field->getDescription() );
		$this->assertEquals( 5, $field->getTemplateId() );
		$this->assertEquals( 10, $field->getJobId() );
		$this->assertEquals( 3, $field->getPosition() );
		$this->assertTrue( $field->isRequired() );
		$this->assertFalse( $field->isActive() );
	}

	/**
	 * Test: fromArray Factory-Methode
	 */
	public function test_from_array_factory(): void {
		$field = FieldDefinition::fromArray( [
			'id'         => 42,
			'field_key'  => 'factory_test',
			'field_type' => 'text',
			'label'      => 'Factory Test',
		] );

		$this->assertInstanceOf( FieldDefinition::class, $field );
		$this->assertEquals( 42, $field->getId() );
		$this->assertEquals( 'factory_test', $field->getFieldKey() );
	}
}
