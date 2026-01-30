<?php
/**
 * FieldTypeRegistry Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\FieldTypes;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\FieldTypes\TextField;
use RecruitingPlaybook\Contracts\FieldTypeInterface;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests für FieldTypeRegistry
 */
class FieldTypeRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );

		// Singleton zurücksetzen.
		FieldTypeRegistry::resetInstance();
	}

	protected function tearDown(): void {
		FieldTypeRegistry::resetInstance();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_is_a_singleton(): void {
		$instance1 = FieldTypeRegistry::getInstance();
		$instance2 = FieldTypeRegistry::getInstance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function it_registers_default_types(): void {
		$registry = FieldTypeRegistry::getInstance();

		$this->assertTrue( $registry->has( 'text' ) );
		$this->assertTrue( $registry->has( 'textarea' ) );
		$this->assertTrue( $registry->has( 'email' ) );
		$this->assertTrue( $registry->has( 'phone' ) );
		$this->assertTrue( $registry->has( 'number' ) );
		$this->assertTrue( $registry->has( 'url' ) );
		$this->assertTrue( $registry->has( 'select' ) );
		$this->assertTrue( $registry->has( 'radio' ) );
		$this->assertTrue( $registry->has( 'checkbox' ) );
		$this->assertTrue( $registry->has( 'date' ) );
		$this->assertTrue( $registry->has( 'file' ) );
		$this->assertTrue( $registry->has( 'heading' ) );
	}

	/**
	 * @test
	 */
	public function it_returns_null_for_unknown_type(): void {
		$registry = FieldTypeRegistry::getInstance();

		$result = $registry->get( 'unknown_type' );

		$this->assertNull( $result );
	}

	/**
	 * @test
	 */
	public function it_returns_correct_field_type(): void {
		$registry = FieldTypeRegistry::getInstance();

		$text_type = $registry->get( 'text' );

		$this->assertInstanceOf( FieldTypeInterface::class, $text_type );
		$this->assertEquals( 'text', $text_type->getType() );
	}

	/**
	 * @test
	 */
	public function it_can_register_custom_type(): void {
		$registry    = FieldTypeRegistry::getInstance();
		$custom_type = $this->createMock( FieldTypeInterface::class );
		$custom_type->method( 'getType' )->willReturn( 'custom' );

		$registry->register( $custom_type );

		$this->assertTrue( $registry->has( 'custom' ) );
		$this->assertSame( $custom_type, $registry->get( 'custom' ) );
	}

	/**
	 * @test
	 */
	public function it_can_deregister_type(): void {
		$registry = FieldTypeRegistry::getInstance();

		$registry->deregister( 'text' );

		$this->assertFalse( $registry->has( 'text' ) );
	}

	/**
	 * @test
	 */
	public function it_returns_all_types(): void {
		$registry = FieldTypeRegistry::getInstance();

		$all = $registry->getAll();

		$this->assertIsArray( $all );
		$this->assertArrayHasKey( 'text', $all );
		$this->assertArrayHasKey( 'email', $all );
		$this->assertCount( 12, $all );
	}

	/**
	 * @test
	 */
	public function it_returns_type_keys(): void {
		$registry = FieldTypeRegistry::getInstance();

		$keys = $registry->getTypeKeys();

		$this->assertContains( 'text', $keys );
		$this->assertContains( 'email', $keys );
		$this->assertContains( 'file', $keys );
	}

	/**
	 * @test
	 */
	public function it_groups_types_by_group(): void {
		$registry = FieldTypeRegistry::getInstance();

		$grouped = $registry->getByGroup();

		$this->assertArrayHasKey( 'text', $grouped );
		$this->assertArrayHasKey( 'choice', $grouped );
		$this->assertArrayHasKey( 'special', $grouped );
		$this->assertArrayHasKey( 'layout', $grouped );

		// Text-Gruppe sollte TextField enthalten.
		$text_types = array_map( fn( $t ) => $t->getType(), $grouped['text'] );
		$this->assertContains( 'text', $text_types );
		$this->assertContains( 'email', $text_types );
	}

	/**
	 * @test
	 */
	public function it_returns_group_labels(): void {
		$registry = FieldTypeRegistry::getInstance();

		$labels = $registry->getGroupLabels();

		$this->assertArrayHasKey( 'text', $labels );
		$this->assertArrayHasKey( 'choice', $labels );
		$this->assertArrayHasKey( 'special', $labels );
		$this->assertArrayHasKey( 'layout', $labels );
	}

	/**
	 * @test
	 */
	public function it_exports_to_array(): void {
		$registry = FieldTypeRegistry::getInstance();

		$array = $registry->toArray();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'text', $array );

		$text_data = $array['text'];
		$this->assertEquals( 'text', $text_data['type'] );
		$this->assertArrayHasKey( 'label', $text_data );
		$this->assertArrayHasKey( 'icon', $text_data );
		$this->assertArrayHasKey( 'group', $text_data );
		$this->assertArrayHasKey( 'supportsOptions', $text_data );
		$this->assertArrayHasKey( 'isFileUpload', $text_data );
		$this->assertArrayHasKey( 'defaultSettings', $text_data );
		$this->assertArrayHasKey( 'validationRules', $text_data );
	}

	/**
	 * @test
	 */
	public function it_can_reset_registry(): void {
		$registry = FieldTypeRegistry::getInstance();
		$registry->reset();

		// Nach Reset sollten keine Typen registriert sein bis zum nächsten Zugriff.
		// getAll() triggert die Registrierung.
		$all = $registry->getAll();

		$this->assertCount( 12, $all );
	}

	/**
	 * @test
	 */
	public function register_returns_self_for_chaining(): void {
		$registry    = FieldTypeRegistry::getInstance();
		$custom_type = $this->createMock( FieldTypeInterface::class );
		$custom_type->method( 'getType' )->willReturn( 'custom' );

		$result = $registry->register( $custom_type );

		$this->assertSame( $registry, $result );
	}

	/**
	 * @test
	 */
	public function deregister_returns_self_for_chaining(): void {
		$registry = FieldTypeRegistry::getInstance();

		$result = $registry->deregister( 'text' );

		$this->assertSame( $registry, $result );
	}
}
