<?php
/**
 * ConditionalScriptGenerator Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\ConditionalScriptGenerator;
use RecruitingPlaybook\Services\ConditionalLogicService;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests für ConditionalScriptGenerator
 */
class ConditionalScriptGeneratorTest extends TestCase {

	private ConditionalScriptGenerator $generator;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'esc_url' )->returnArg( 1 );

		if ( ! defined( 'RP_PLUGIN_URL' ) ) {
			define( 'RP_PLUGIN_URL', 'https://example.com/wp-content/plugins/recruiting-playbook/' );
		}

		$this->generator = new ConditionalScriptGenerator();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_generates_config_for_fields(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', [] ),
			$this->createFieldDefinition( 'other', 'text', [
				'field'    => 'name',
				'operator' => 'equals',
				'value'    => 'show',
			] ),
		];

		$config = $this->generator->generateConfig( $fields );

		$this->assertArrayHasKey( 'fields', $config );
		$this->assertArrayHasKey( 'operators', $config );
		$this->assertArrayHasKey( 'dependency', $config );
		$this->assertArrayHasKey( 'other', $config['fields'] );
	}

	/**
	 * @test
	 */
	public function it_generates_empty_config_for_no_conditionals(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', [] ),
			$this->createFieldDefinition( 'email', 'email', [] ),
		];

		$config = $this->generator->generateConfig( $fields );

		$this->assertEmpty( $config['fields'] );
	}

	/**
	 * @test
	 */
	public function it_generates_inline_script(): void {
		$fields = [
			$this->createFieldDefinition( 'type', 'select', [] ),
			$this->createFieldDefinition( 'details', 'text', [
				'field'    => 'type',
				'operator' => 'equals',
				'value'    => 'other',
			] ),
		];

		$script = $this->generator->generateInlineScript( $fields );

		$this->assertStringContainsString( '<script>', $script );
		$this->assertStringContainsString( 'window.rpConditionalConfig', $script );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_string_for_no_conditionals(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', [] ),
		];

		$script = $this->generator->generateInlineScript( $fields );

		$this->assertEquals( '', $script );
	}

	/**
	 * @test
	 */
	public function it_generates_x_show_expression(): void {
		$field = $this->createFieldDefinition( 'details', 'text', [
			'field'    => 'type',
			'operator' => 'equals',
			'value'    => 'other',
		] );

		$xShow = $this->generator->generateXShow( $field );

		$this->assertStringContainsString( 'formData.type', $xShow );
		$this->assertStringContainsString( "=== 'other'", $xShow );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_x_show_for_no_conditional(): void {
		$field = $this->createFieldDefinition( 'name', 'text', [] );

		$xShow = $this->generator->generateXShow( $field );

		$this->assertEquals( '', $xShow );
	}

	/**
	 * @test
	 */
	public function it_generates_all_x_show_expressions(): void {
		$fields = [
			$this->createFieldDefinition( 'type', 'select', [] ),
			$this->createFieldDefinition( 'details', 'text', [
				'field'    => 'type',
				'operator' => 'equals',
				'value'    => 'other',
			] ),
			$this->createFieldDefinition( 'more', 'textarea', [
				'field'    => 'type',
				'operator' => 'not_empty',
			] ),
		];

		$expressions = $this->generator->generateAllXShow( $fields );

		$this->assertArrayHasKey( 'details', $expressions );
		$this->assertArrayHasKey( 'more', $expressions );
		$this->assertArrayNotHasKey( 'type', $expressions );
	}

	/**
	 * @test
	 */
	public function it_generates_hidden_styles(): void {
		$styles = $this->generator->generateHiddenStyles();

		$this->assertStringContainsString( '<style>', $styles );
		$this->assertStringContainsString( '[x-cloak]', $styles );
		$this->assertStringContainsString( 'display: none', $styles );
	}

	/**
	 * @test
	 */
	public function it_generates_watchers_for_dependencies(): void {
		$fields = [
			$this->createFieldDefinition( 'type', 'select', [] ),
			$this->createFieldDefinition( 'details', 'text', [
				'field' => 'type',
				'operator' => 'equals',
				'value' => 'other',
			] ),
		];

		$watchers = $this->generator->generateWatchers( $fields );

		$this->assertStringContainsString( '$watch', $watchers );
		$this->assertStringContainsString( 'formData.type', $watchers );
	}

	/**
	 * @test
	 */
	public function it_generates_empty_watchers_for_no_dependencies(): void {
		$fields = [
			$this->createFieldDefinition( 'name', 'text', [] ),
			$this->createFieldDefinition( 'email', 'email', [] ),
		];

		$watchers = $this->generator->generateWatchers( $fields );

		$this->assertEquals( '', $watchers );
	}

	/**
	 * @test
	 */
	public function it_generates_alpine_component(): void {
		$fields = [
			$this->createFieldDefinition( 'type', 'select', [] ),
			$this->createFieldDefinition( 'details', 'text', [
				'field' => 'type',
				'operator' => 'equals',
				'value' => 'other',
			] ),
		];

		$component = $this->generator->generateAlpineComponent( $fields, [ 'type' => '' ] );

		$this->assertStringContainsString( 'function rpFormWithConditional', $component );
		$this->assertStringContainsString( 'formData', $component );
		$this->assertStringContainsString( 'isVisible', $component );
		$this->assertStringContainsString( 'shouldValidate', $component );
	}

	/**
	 * @test
	 */
	public function it_generates_script_tags(): void {
		$tags = $this->generator->getScriptTags();

		$this->assertStringContainsString( '<script', $tags );
		$this->assertStringContainsString( 'conditional-logic.js', $tags );
		$this->assertStringContainsString( 'defer', $tags );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 *
	 * @param string $key         Feldschlüssel.
	 * @param string $type        Feldtyp.
	 * @param array  $conditional Conditional-Konfiguration.
	 * @return FieldDefinition
	 */
	private function createFieldDefinition( string $key, string $type, array $conditional ): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'type'        => $type,
			'label'       => ucfirst( $key ),
			'is_required' => false,
			'conditional' => ! empty( $conditional ) ? wp_json_encode( $conditional ) : null,
		];

		return FieldDefinition::hydrate( $data );
	}
}
