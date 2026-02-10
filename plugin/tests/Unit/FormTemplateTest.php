<?php
/**
 * FormTemplate Model Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Models\FormTemplate;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey\Functions;

/**
 * Tests für das FormTemplate Model
 */
class FormTemplateTest extends TestCase {

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// WordPress-Funktionen mocken.
		Functions\when( 'get_userdata' )->alias( function( $user_id ) {
			if ( $user_id === 1 ) {
				return (object) [
					'ID'           => 1,
					'display_name' => 'Admin User',
				];
			}
			return false;
		} );

		Functions\when( 'get_avatar_url' )->justReturn( 'https://example.com/avatar.jpg' );
	}

	/**
	 * Test: Constructor mit leeren Daten erstellt Default-Werte
	 */
	public function test_constructor_creates_default_values(): void {
		$template = new FormTemplate();

		$this->assertEquals( 0, $template->getId() );
		$this->assertEquals( '', $template->getName() );
		$this->assertNull( $template->getDescription() );
		$this->assertFalse( $template->isDefault() );
		$this->assertNull( $template->getSettings() );
		$this->assertEquals( 0, $template->getCreatedBy() );
	}

	/**
	 * Test: Constructor mit Daten hydratisiert korrekt
	 */
	public function test_constructor_hydrates_from_array(): void {
		$data = [
			'id'          => 1,
			'name'        => 'Standard-Formular',
			'description' => 'Das Standard-Bewerbungsformular',
			'is_default'  => 1,
			'created_by'  => 1,
			'created_at'  => '2025-01-15 10:00:00',
			'updated_at'  => '2025-01-15 12:00:00',
		];

		$template = new FormTemplate( $data );

		$this->assertEquals( 1, $template->getId() );
		$this->assertEquals( 'Standard-Formular', $template->getName() );
		$this->assertEquals( 'Das Standard-Bewerbungsformular', $template->getDescription() );
		$this->assertTrue( $template->isDefault() );
		$this->assertEquals( 1, $template->getCreatedBy() );
		$this->assertEquals( '2025-01-15 10:00:00', $template->getCreatedAt() );
		$this->assertEquals( '2025-01-15 12:00:00', $template->getUpdatedAt() );
	}

	/**
	 * Test: Settings JSON wird korrekt dekodiert
	 */
	public function test_settings_json_is_decoded(): void {
		$template = new FormTemplate( [
			'id'       => 1,
			'name'     => 'Test',
			'settings' => '{"submit_label":"Bewerben","success_message":"Danke!"}',
		] );

		$settings = $template->getSettings();

		$this->assertIsArray( $settings );
		$this->assertEquals( 'Bewerben', $settings['submit_label'] );
		$this->assertEquals( 'Danke!', $settings['success_message'] );
	}

	/**
	 * Test: getSetting mit Standardwert
	 */
	public function test_get_setting_with_default(): void {
		$template = new FormTemplate( [
			'id'       => 1,
			'name'     => 'Test',
			'settings' => '{"submit_label":"Bewerben"}',
		] );

		$this->assertEquals( 'Bewerben', $template->getSetting( 'submit_label' ) );
		$this->assertNull( $template->getSetting( 'nonexistent' ) );
		$this->assertEquals( 'default', $template->getSetting( 'nonexistent', 'default' ) );
	}

	/**
	 * Test: getCreator gibt User-Daten zurück
	 */
	public function test_get_creator_returns_user_data(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Test',
			'created_by' => 1,
		] );

		$creator = $template->getCreator();

		$this->assertIsArray( $creator );
		$this->assertEquals( 1, $creator['id'] );
		$this->assertEquals( 'Admin User', $creator['name'] );
		$this->assertEquals( 'https://example.com/avatar.jpg', $creator['avatar'] );
	}

	/**
	 * Test: getCreator gibt null zurück wenn User nicht existiert
	 */
	public function test_get_creator_returns_null_for_invalid_user(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Test',
			'created_by' => 999,
		] );

		$this->assertNull( $template->getCreator() );
	}

	/**
	 * Test: Fields können gesetzt und geladen werden
	 */
	public function test_fields_can_be_set_and_retrieved(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Test',
		] );

		$fields = [
			new FieldDefinition( [ 'field_key' => 'field1', 'field_type' => 'text', 'label' => 'Feld 1' ] ),
			new FieldDefinition( [ 'field_key' => 'field2', 'field_type' => 'email', 'label' => 'Feld 2' ] ),
		];

		$template->setFields( $fields );

		$this->assertCount( 2, $template->getFields() );
		$this->assertInstanceOf( FieldDefinition::class, $template->getFields()[0] );
	}

	/**
	 * Test: getFields gibt leeres Array zurück wenn keine Felder gesetzt
	 */
	public function test_get_fields_returns_empty_array(): void {
		$template = new FormTemplate();

		$this->assertEquals( [], $template->getFields() );
	}

	/**
	 * Test: Usage Count kann gesetzt werden
	 */
	public function test_usage_count_can_be_set(): void {
		$template = new FormTemplate( [
			'id'          => 1,
			'name'        => 'Test',
			'usage_count' => 5,
		] );

		$this->assertEquals( 5, $template->getUsageCount() );

		$template->setUsageCount( 10 );
		$this->assertEquals( 10, $template->getUsageCount() );
	}

	/**
	 * Test: isDeleted prüft deleted_at korrekt
	 */
	public function test_is_deleted_checks_deleted_at(): void {
		$active = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Test',
			'deleted_at' => null,
		] );

		$this->assertFalse( $active->isDeleted() );

		$deleted = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Test',
			'deleted_at' => '2025-01-15 10:00:00',
		] );

		$this->assertTrue( $deleted->isDeleted() );
	}

	/**
	 * Test: toArray gibt korrektes Format zurück
	 */
	public function test_to_array_returns_correct_format(): void {
		$template = new FormTemplate( [
			'id'         => 1,
			'name'       => 'Test',
			'is_default' => true,
			'created_by' => 1,
		] );

		$array = $template->toArray();

		$this->assertArrayHasKey( 'id', $array );
		$this->assertArrayHasKey( 'name', $array );
		$this->assertArrayHasKey( 'is_default', $array );
		$this->assertArrayHasKey( 'created_by', $array );
		$this->assertArrayHasKey( 'usage_count', $array );

		$this->assertEquals( 1, $array['id'] );
		$this->assertEquals( 'Test', $array['name'] );
		$this->assertTrue( $array['is_default'] );
	}

	/**
	 * Test: toArray inkludiert Felder wenn gesetzt
	 */
	public function test_to_array_includes_fields_when_set(): void {
		$template = new FormTemplate( [
			'id'   => 1,
			'name' => 'Test',
		] );

		$template->setFields( [
			new FieldDefinition( [ 'field_key' => 'test', 'field_type' => 'text', 'label' => 'Test' ] ),
		] );

		$array = $template->toArray();

		$this->assertArrayHasKey( 'fields', $array );
		$this->assertCount( 1, $array['fields'] );
		$this->assertEquals( 'test', $array['fields'][0]['field_key'] );
	}

	/**
	 * Test: toDbArray gibt korrektes Format zurück
	 */
	public function test_to_db_array_returns_correct_format(): void {
		$template = new FormTemplate();
		$template->setName( 'Test Template' );
		$template->setDescription( 'Eine Beschreibung' );
		$template->setDefault( true );
		$template->setSettings( [ 'key' => 'value' ] );
		$template->setCreatedBy( 1 );

		$db_array = $template->toDbArray();

		$this->assertEquals( 'Test Template', $db_array['name'] );
		$this->assertEquals( 'Eine Beschreibung', $db_array['description'] );
		$this->assertEquals( 1, $db_array['is_default'] );
		$this->assertIsString( $db_array['settings'] );
		$this->assertEquals( 1, $db_array['created_by'] );
	}

	/**
	 * Test: Setters funktionieren korrekt
	 */
	public function test_setters_work_correctly(): void {
		$template = new FormTemplate();

		$template->setName( 'Neuer Name' )
			->setDescription( 'Neue Beschreibung' )
			->setDefault( true )
			->setSettings( [ 'test' => 'value' ] )
			->setCreatedBy( 5 );

		$this->assertEquals( 'Neuer Name', $template->getName() );
		$this->assertEquals( 'Neue Beschreibung', $template->getDescription() );
		$this->assertTrue( $template->isDefault() );
		$this->assertEquals( [ 'test' => 'value' ], $template->getSettings() );
		$this->assertEquals( 5, $template->getCreatedBy() );
	}

	/**
	 * Test: fromArray Factory-Methode
	 */
	public function test_from_array_factory(): void {
		$template = FormTemplate::fromArray( [
			'id'   => 42,
			'name' => 'Factory Test',
		] );

		$this->assertInstanceOf( FormTemplate::class, $template );
		$this->assertEquals( 42, $template->getId() );
		$this->assertEquals( 'Factory Test', $template->getName() );
	}
}
